<?php
// POST /api/ge/ready_for_documents.php — Validate GE and move to READY_FOR_DOCUMENTS
require_once __DIR__ . '/../../config/helpers.php';
set_cors_headers();

$user = require_auth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Method not allowed.', 405);

$b  = get_json_body();
$id = (int)($b['id'] ?? 0);
if (!$id) json_error('GE id required.');

$db   = Database::getInstance();
$stmt = $db->prepare('SELECT * FROM general_expenses WHERE id = ? AND claimant_id = ?');
$stmt->execute([$id, $user['id']]);
$ge = $stmt->fetch();
if (!$ge) json_error('GE not found or access denied.', 404);
if ($ge['status'] !== 'DRAFT') json_error('Only DRAFT GEs can be validated.');

$errors = [];

// Required header fields
if (!trim($ge['payee_name']))             $errors[] = 'Payee name is required.';
if (!trim($ge['departmental_reference'])) $errors[] = 'Departmental reference is required.';

// Line items
$liStmt = $db->prepare('SELECT * FROM ge_line_items WHERE ge_id = ?');
$liStmt->execute([$id]);
$lineItems = $liStmt->fetchAll();
if (count($lineItems) === 0) $errors[] = 'At least one line item is required.';
foreach ($lineItems as $i => $li) {
    if (!trim($li['description'])) $errors[] = "Line item " . ($i+1) . ": description is required.";
    if ((float)$li['unit_price'] <= 0) $errors[] = "Line item " . ($i+1) . ": unit price must be > 0.";
}

// Accounting lines total must equal GE total
// Recompute GE total fresh from stored line items (total_price is a generated column)
// so stale total_amount values from older saves don't cause false mismatches.
$alStmt = $db->prepare('SELECT SUM(amount) AS al_total FROM ge_accounting_lines WHERE ge_id = ?');
$alStmt->execute([$id]);
$alTotal = (float)$alStmt->fetch()['al_total'];

$liTotalStmt = $db->prepare('SELECT COALESCE(SUM(total_price), 0) AS li_total FROM ge_line_items WHERE ge_id = ?');
$liTotalStmt->execute([$id]);
$geTotal = (float)$liTotalStmt->fetch()['li_total'];

// Also sync total_amount in case it was stale
$db->prepare('UPDATE general_expenses SET total_amount = ? WHERE id = ?')->execute([$geTotal, $id]);

// Count saved accounting lines
$alCountStmt = $db->prepare('SELECT COUNT(*) AS cnt FROM ge_accounting_lines WHERE ge_id = ?');
$alCountStmt->execute([$id]);
$alCount = (int)$alCountStmt->fetch()['cnt'];

if ($alCount === 0) {
    $errors[] = 'At least one accounting line is required.';
} elseif ($alTotal === 0.0) {
    $errors[] = 'Accounting lines must have a non-zero total amount. Please fill in the Amount column in the "For Departmental Use Only" section.';
} elseif (abs($alTotal - $geTotal) > 0.01) {
    $errors[] = sprintf(
        'Accounting total (K %s) must equal GE total (K %s).',
        number_format($alTotal, 2),
        number_format($geTotal, 2)
    );
}

if ($errors) json_error('Validation failed.', 422, ['errors' => $errors]);

$db->prepare(
    'UPDATE general_expenses SET status = ?, version = version + 1 WHERE id = ?'
)->execute(['READY_FOR_DOCUMENTS', $id]);

audit($user['id'], 'GE_READY_FOR_DOCUMENTS', 'GE validated and ready for document upload', $id);
json_success('GE is ready for document upload.', ['ge_id' => $id]);
