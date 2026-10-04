<?php
// GET /api/ge/get.php?id=X — Full GE detail
require_once __DIR__ . '/../../config/helpers.php';
set_cors_headers();

$user = require_auth();
$db   = Database::getInstance();
$id   = (int)($_GET['id'] ?? 0);
if (!$id) json_error('GE id required.');

$stmt = $db->prepare(
    'SELECT ge.*,
            CONCAT(u.first_name," ",u.last_name) AS claimant_name,
            u.email AS claimant_email, u.staff_id,
            d.name AS department_name, d.code AS dept_code, d.faculty_code
     FROM general_expenses ge
     JOIN users u ON u.id = ge.claimant_id
     JOIN departments d ON d.id = ge.department_id
     WHERE ge.id = ?'
);
$stmt->execute([$id]);
$ge = $stmt->fetch();
if (!$ge) json_error('GE not found.', 404);

// Authorisation: claimant, assigned approver, or admin
$isOwner = $ge['claimant_id'] == $user['id'];
$isAdmin = in_array('SYSTEM_ADMIN', $user['roles']);

if (!$isOwner && !$isAdmin) {
    // Check if user is an assigned approver for this GE
    $chk = $db->prepare(
        'SELECT 1 FROM workflow_tasks wt
         JOIN workflow_instances wi ON wi.id = wt.instance_id
         WHERE wi.ge_id = ? AND wt.assignee_id = ? LIMIT 1'
    );
    $chk->execute([$id, $user['id']]);
    if (!$chk->fetch()) json_error('Forbidden.', 403);
}

// Line items
$liStmt = $db->prepare('SELECT * FROM ge_line_items WHERE ge_id = ? ORDER BY sort_order');
$liStmt->execute([$id]);
$ge['line_items'] = $liStmt->fetchAll();

// Accounting lines
$alStmt = $db->prepare('SELECT * FROM ge_accounting_lines WHERE ge_id = ? ORDER BY sort_order');
$alStmt->execute([$id]);
$ge['accounting_lines'] = $alStmt->fetchAll();

// Documents
$docStmt = $db->prepare(
    'SELECT id, document_type, label, original_name, mime_type, file_size, uploaded_at
     FROM ge_documents WHERE ge_id = ? AND deleted_at IS NULL ORDER BY uploaded_at'
);
$docStmt->execute([$id]);
$ge['documents'] = $docStmt->fetchAll();

// Audit trail
$auditStmt = $db->prepare(
    'SELECT al.action, al.description, al.created_at,
            CONCAT(u.first_name," ",u.last_name) AS performed_by
     FROM audit_log al LEFT JOIN users u ON u.id = al.user_id
     WHERE al.ge_id = ? ORDER BY al.created_at DESC LIMIT 50'
);
$auditStmt->execute([$id]);
$ge['audit_trail'] = $auditStmt->fetchAll();

// Workflow instance & tasks
$wiStmt = $db->prepare(
    'SELECT wi.*, wd.label AS workflow_label
     FROM workflow_instances wi JOIN workflow_definitions wd ON wd.id = wi.workflow_id
     WHERE wi.ge_id = ?'
);
$wiStmt->execute([$id]);
$wi = $wiStmt->fetch();
if ($wi) {
    $wtStmt = $db->prepare(
        'SELECT wt.*, ws.label AS step_label, ws.step_code,
                CONCAT(u.first_name," ",u.last_name) AS assignee_name
         FROM workflow_tasks wt
         JOIN workflow_steps ws ON ws.id = wt.step_id
         LEFT JOIN users u ON u.id = wt.assignee_id
         WHERE wt.instance_id = ? ORDER BY ws.step_order'
    );
    $wtStmt->execute([$wi['id']]);
    $wi['tasks'] = $wtStmt->fetchAll();
}
$ge['workflow'] = $wi ?: null;

$ge['status_label']    = ge_status_label($ge['status']);
$ge['is_capital_item'] = (bool)$ge['is_capital_item'];

json_success('OK', ['ge' => $ge]);
