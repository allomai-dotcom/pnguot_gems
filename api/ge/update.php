<?php
// PATCH /api/ge/update.php — Update GE draft (header + line items + accounting lines)
require_once __DIR__ . '/../../config/helpers.php';
set_cors_headers();

$user = require_auth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Method not allowed.', 405);

$b  = get_json_body();
$id = (int)($b['id'] ?? 0);
if (!$id) json_error('GE id required.');

$db = Database::getInstance();

// Fetch GE without claimant restriction first
$stmt = $db->prepare('SELECT * FROM general_expenses WHERE id = ?');
$stmt->execute([$id]);
$ge = $stmt->fetch();
if (!$ge) json_error('GE not found.', 404);

$isOwner       = ((int)$ge['claimant_id'] === (int)$user['id']);
$isAdmin       = in_array('SYSTEM_ADMIN', $user['roles'] ?? []);
$isAccounts    = in_array('ACCOUNTS_OFFICER', $user['roles'] ?? []);
$isFinanceRole = $isAdmin || $isAccounts;

// Must be owner or a finance role
if (!$isOwner && !$isFinanceRole) {
    json_error('Access denied.', 403);
}

// Finance roles (accounts/admin) may save CFC/CN on any GE regardless of status.
// Everyone else is restricted to editable statuses.
$financeOnlyEdit = (!$isOwner && $isFinanceRole);

if (!$financeOnlyEdit) {
    if (!in_array($ge['status'], ['DRAFT', 'READY_FOR_DOCUMENTS', 'QUERIED'])) {
        json_error('GE cannot be edited in its current status: ' . $ge['status']);
    }
}

// Update header fields
$fields = [];
$vals   = [];

// Finance-role non-owners may only update CFC/CN
if ($financeOnlyEdit) {
    $allowed = ['cfc_number', 'commitment_number'];
} else {
    $allowed = ['payee_name','departmental_reference','claimant_reference',
                'description','expense_category','procurement_type','is_capital_item',
                'claimant_full_name','claimant_declaration_date',
                'department_id','cfc_number','commitment_number',
                'hod_name','hod_designation','hod_certification_date'];
}

foreach ($allowed as $f) {
    if (array_key_exists($f, $b)) {
        $fields[] = "$f = ?";
        $vals[]   = $f === 'is_capital_item' ? (int)(bool)$b[$f] : $b[$f];
    }
}

if (isset($b['hod_approved'])) {
    $fields[] = 'hod_approved = ?';
    $vals[]   = (int)(bool)$b['hod_approved'];
}
if (isset($b['hod_sent_to_accounts'])) {
    $fields[] = 'hod_sent_to_accounts = ?';
    $vals[]   = (int)(bool)$b['hod_sent_to_accounts'];
}

if ($fields) {
    $vals[] = $id;
    $db->prepare('UPDATE general_expenses SET ' . implode(', ', $fields) . ' WHERE id = ?')
       ->execute($vals);
}

// Replace line items if provided (owner only)
if (!$financeOnlyEdit && isset($b['line_items']) && is_array($b['line_items'])) {
    $db->prepare('DELETE FROM ge_line_items WHERE ge_id = ?')->execute([$id]);
    $liStmt = $db->prepare(
        'INSERT INTO ge_line_items (ge_id, sort_order, description, quantity, unit_price, gst_percent)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $total = 0;
    foreach ($b['line_items'] as $i => $li) {
        $qty   = max(0, (float)($li['quantity']   ?? 1));
        $price = max(0, (float)($li['unit_price']  ?? 0));
        $gst   = max(0, min(100, (float)($li['gst_percent'] ?? 0)));

        // If unit_price is 0 but the client supplied a direct amount, back-calculate
        // unit_price so the generated total_price column stays consistent.
        $clientAmt = max(0, (float)($li['amount'] ?? 0));
        if ($price == 0 && $clientAmt > 0) {
            $divisor = $qty > 0 ? ($qty * (1 + $gst / 100)) : 1;
            $price   = $clientAmt / $divisor;
        }

        $liStmt->execute([$id, $i, trim($li['description'] ?? ''), $qty, $price, $gst]);
        $total += $qty * $price * (1 + $gst / 100);
    }
    $db->prepare('UPDATE general_expenses SET total_amount = ? WHERE id = ?')->execute([$total, $id]);
}

// Replace accounting lines if provided (owner only)
if (!$financeOnlyEdit && isset($b['accounting_lines']) && is_array($b['accounting_lines'])) {
    $db->prepare('DELETE FROM ge_accounting_lines WHERE ge_id = ?')->execute([$id]);
    $alStmt = $db->prepare(
        'INSERT INTO ge_accounting_lines
         (ge_id, sort_order, account_code, account_name,
          budget_div, budget_fn, budget_act, budget_item, budget_si, budget_d,
          amount, notes)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
    );
    foreach ($b['accounting_lines'] as $i => $al) {
        $alStmt->execute([
            $id, $i,
            trim($al['account_code'] ?? ''),
            trim($al['account_name'] ?? ''),
            trim($al['budget_div']   ?? ''),
            trim($al['budget_fn']    ?? ''),
            trim($al['budget_act']   ?? ''),
            trim($al['budget_item']  ?? ''),
            trim($al['budget_si']    ?? ''),
            trim($al['budget_d']     ?? ''),
            max(0, (float)($al['amount'] ?? 0)),
            trim($al['notes'] ?? ''),
        ]);
    }
}

// Claimant signature — only the owner (claimant) may save this
if (!$financeOnlyEdit && $ge['claimant_id'] === $user['id'] &&
    isset($b['claimant_signature_data']) && trim($b['claimant_signature_data']) !== '') {
    $db->prepare(
        'UPDATE general_expenses
         SET claimant_signature_data = ?, claimant_signed_at = NOW()
         WHERE id = ?'
    )->execute([$b['claimant_signature_data'], $id]);
}

// HOD signature — claimant fills on behalf of HOD
if (!$financeOnlyEdit && $ge['claimant_id'] === $user['id'] &&
    isset($b['hod_signature_data']) && trim($b['hod_signature_data']) !== '') {
    $db->prepare(
        'UPDATE general_expenses SET hod_signature_data = ? WHERE id = ?'
    )->execute([$b['hod_signature_data'], $id]);
}

// Bump version
$db->prepare('UPDATE general_expenses SET version = version + 1 WHERE id = ?')->execute([$id]);
audit($user['id'], 'GE_UPDATED', 'GE draft updated', $id);
json_success('GE updated successfully.');
