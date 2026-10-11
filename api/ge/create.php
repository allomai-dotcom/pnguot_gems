<?php
// POST /api/ge/create.php — Create a new GE draft
require_once __DIR__ . '/../../config/helpers.php';
set_cors_headers();

$user = require_auth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Method not allowed.', 405);

$b = get_json_body();
$db = Database::getInstance();

// Resolve department (default to claimant's department)
$deptId = (int)($b['department_id'] ?? $user['department_id'] ?? 0);
if (!$deptId) json_error('Department is required.');

// Verify department exists in the database
$deptCheck = $db->prepare('SELECT id FROM departments WHERE id = ?');
$deptCheck->execute([$deptId]);
if (!$deptCheck->fetch()) {
    json_error('Invalid department. Please select a valid department.', 400);
}

try {
    $db->prepare(
        'INSERT INTO general_expenses
            (status, claimant_id, department_id, payee_name, departmental_reference,
             claimant_reference, description, expense_category, procurement_type, is_capital_item, total_amount)
         VALUES (?,?,?,?,?,?,?,?,?,?,?)'
    )->execute([
        'DRAFT',
        $user['id'],
        $deptId,
        trim($b['payee_name'] ?? ''),
        trim($b['departmental_reference'] ?? ''),
        trim($b['claimant_reference'] ?? ''),
        trim($b['description'] ?? ''),
        trim($b['expense_category'] ?? ''),
        in_array($b['procurement_type'] ?? '', ['ICT','STANDARD']) ? $b['procurement_type'] : 'STANDARD',
        isset($b['is_capital_item']) && $b['is_capital_item'] ? 1 : 0,
        0.00,
    ]);
    $geId = (int)$db->lastInsertId();

    // Assign GE number — use custom value if provided, otherwise auto-generate
    $customNumber = trim($b['ge_number'] ?? '');
    if ($customNumber !== '') {
        // Check uniqueness
        $dupCheck = $db->prepare('SELECT id FROM general_expenses WHERE ge_number = ?');
        $dupCheck->execute([$customNumber]);
        if ($dupCheck->fetch()) {
            json_error('GE number already exists. Please use a different number.', 409);
        }
        $geNumber = $customNumber;
    } else {
        $geNumber = generate_ge_number();
    }
    $db->prepare('UPDATE general_expenses SET ge_number = ? WHERE id = ?')->execute([$geNumber, $geId]);

    audit($user['id'], 'GE_CREATED', "GE draft created: $geNumber", $geId);
    json_success('GE draft created.', ['ge_id' => $geId, 'ge_number' => $geNumber], 201);
} catch (PDOException $e) {
    error_log('GE create error: ' . $e->getMessage());
    json_error('Failed to create GE. Please try again.', 500);
}
