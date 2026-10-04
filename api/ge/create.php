<?php
// POST /api/ge/create.php — Create a new GE draft
require_once __DIR__ . '/../../config/helpers.php';
set_cors_headers();

$user = require_auth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Method not allowed.', 405);

$b = get_json_body();
$db = Database::getInstance();

// Resolve department (default to claimant's department)
$deptId = (int)($b['department_id'] ?? $user['department_id']);
if (!$deptId) json_error('Department is required.');

$db->prepare(
    'INSERT INTO general_expenses
        (status, claimant_id, department_id, payee_name, departmental_reference,
         claimant_reference, description, procurement_type, is_capital_item, total_amount)
     VALUES (?,?,?,?,?,?,?,?,?,?)'
)->execute([
    'DRAFT',
    $user['id'],
    $deptId,
    trim($b['payee_name'] ?? ''),
    trim($b['departmental_reference'] ?? ''),
    trim($b['claimant_reference'] ?? ''),
    trim($b['description'] ?? ''),
    in_array($b['procurement_type'] ?? '', ['ICT','STANDARD']) ? $b['procurement_type'] : 'STANDARD',
    isset($b['is_capital_item']) && $b['is_capital_item'] ? 1 : 0,
    0.00,
]);
$geId = (int)$db->lastInsertId();

// Assign GE number immediately
$geNumber = generate_ge_number();
$db->prepare('UPDATE general_expenses SET ge_number = ? WHERE id = ?')->execute([$geNumber, $geId]);

audit($user['id'], 'GE_CREATED', "GE draft created: $geNumber", $geId);
json_success('GE draft created.', ['ge_id' => $geId, 'ge_number' => $geNumber], 201);
