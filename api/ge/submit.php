<?php
// POST /api/ge/submit.php — Submit GE (starts approval workflow)
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
if ($ge['status'] !== 'READY_FOR_DOCUMENTS') {
    json_error('GE must be in READY_FOR_DOCUMENTS status to submit.');
}

// Check required documents
$requiredTypes = ['QUOTATION_1','QUOTATION_2','QUOTATION_3','JUSTIFICATION_LETTER'];
$docStmt = $db->prepare(
    'SELECT document_type FROM ge_documents WHERE ge_id = ? AND deleted_at IS NULL'
);
$docStmt->execute([$id]);
$uploaded = array_column($docStmt->fetchAll(), 'document_type');
$missing  = array_diff($requiredTypes, $uploaded);
if ($missing) {
    json_error('Missing required documents: ' . implode(', ', $missing), 422,
               ['missing_documents' => array_values($missing)]);
}

// Determine workflow
$wfCode = 'STANDARD_PURCHASE';
if ((bool)$ge['is_capital_item']) {
    $wfCode = 'CAPITAL_ITEM';
} elseif ($ge['procurement_type'] === 'ICT') {
    $wfCode = 'ICT_PURCHASE';
}

$wfStmt = $db->prepare('SELECT * FROM workflow_definitions WHERE code = ? AND is_active = 1');
$wfStmt->execute([$wfCode]);
$wf = $wfStmt->fetch();
if (!$wf) {
    $db->prepare('UPDATE general_expenses SET status = "WORKFLOW_CONFIGURATION_ERROR" WHERE id = ?')
       ->execute([$id]);
    json_error('Workflow configuration error. Admin has been notified.', 500);
}

// Get ordered steps
$stepStmt = $db->prepare(
    'SELECT * FROM workflow_steps WHERE workflow_id = ? ORDER BY step_order'
);
$stepStmt->execute([$wf['id']]);
$steps = $stepStmt->fetchAll();
if (!$steps) {
    $db->prepare('UPDATE general_expenses SET status = "WORKFLOW_CONFIGURATION_ERROR" WHERE id = ?')
       ->execute([$id]);
    json_error('No workflow steps configured.', 500);
}

// Resolve first step delegate
$firstStep = $steps[0];
$delegate  = resolve_delegate($firstStep, $ge, $db);

// Create workflow instance
$db->prepare(
    'INSERT INTO workflow_instances (ge_id, workflow_id, current_step_id, status)
     VALUES (?, ?, ?, "ACTIVE")'
)->execute([$id, $wf['id'], $firstStep['id']]);
$instanceId = (int)$db->lastInsertId();

// Create first task
$db->prepare(
    'INSERT INTO workflow_tasks (instance_id, step_id, assignee_id, status)
     VALUES (?, ?, ?, "PENDING")'
)->execute([$instanceId, $firstStep['id'], $delegate ? $delegate['id'] : null]);

// Update GE status
$newStatus = $firstStep['ge_status_on_reach'];
$db->prepare(
    'UPDATE general_expenses SET status = ?, submitted_at = NOW(), version = version + 1 WHERE id = ?'
)->execute([$newStatus, $id]);

// Notify assignee
if ($delegate) {
    notify($delegate['id'], 'APPROVAL_REQUIRED',
           "New GE {$ge['ge_number']} requires your approval ({$firstStep['label']}).", $id);
}

audit($user['id'], 'GE_SUBMITTED', "GE submitted, workflow $wfCode started, step: {$firstStep['step_code']}", $id);
json_success('GE submitted successfully.', ['workflow' => $wfCode, 'first_step' => $firstStep['label']]);


// ── Delegate resolution helper ────────────────────────────────
function resolve_delegate(array $step, array $ge, PDO $db): ?array {
    $roleStmt = $db->prepare('SELECT id FROM roles WHERE code = ?');
    $roleStmt->execute([$step['role_required']]);
    $role = $roleStmt->fetch();
    if (!$role) return null;

    // 1. Explicit dept-scoped assignment
    $s = $db->prepare(
        'SELECT u.* FROM delegate_assignments da JOIN users u ON u.id = da.user_id
         WHERE da.role_id = ? AND da.department_id = ? AND da.is_active = 1 LIMIT 1'
    );
    $s->execute([$role['id'], $ge['department_id']]);
    if ($u = $s->fetch()) return $u;

    // 2. Faculty-scoped
    $deptRow = $db->prepare('SELECT faculty_code FROM departments WHERE id = ?');
    $deptRow->execute([$ge['department_id']]);
    $dept = $deptRow->fetch();
    if ($dept && $dept['faculty_code']) {
        $s2 = $db->prepare(
            'SELECT u.* FROM delegate_assignments da JOIN users u ON u.id = da.user_id
             WHERE da.role_id = ? AND da.faculty_code = ? AND da.is_active = 1 LIMIT 1'
        );
        $s2->execute([$role['id'], $dept['faculty_code']]);
        if ($u2 = $s2->fetch()) return $u2;
    }

    // 3. Global / central
    $s3 = $db->prepare(
        'SELECT u.* FROM delegate_assignments da JOIN users u ON u.id = da.user_id
         WHERE da.role_id = ? AND da.department_id IS NULL AND da.faculty_code IS NULL
         AND da.is_active = 1 LIMIT 1'
    );
    $s3->execute([$role['id']]);
    if ($u3 = $s3->fetch()) return $u3;

    // 4. Any user with the role
    $s4 = $db->prepare(
        'SELECT u.* FROM users u
         JOIN user_roles ur ON ur.user_id = u.id
         WHERE ur.role_id = ? AND u.is_active = 1 LIMIT 1'
    );
    $s4->execute([$role['id']]);
    return $s4->fetch() ?: null;
}
