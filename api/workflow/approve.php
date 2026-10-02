<?php
// POST /api/workflow/approve.php — Approve / Reject a workflow task
require_once __DIR__ . '/../../config/helpers.php';
set_cors_headers();

$user = require_auth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Method not allowed.', 405);

$b      = get_json_body();
$taskId = (int)($b['task_id'] ?? 0);
$action = strtoupper(trim($b['action'] ?? '')); // APPROVED | REJECTED
$comment= trim($b['comments'] ?? '');
$otp    = trim($b['otp'] ?? '');

if (!$taskId)                           json_error('task_id required.');
if (!in_array($action, ['APPROVED','REJECTED'])) json_error('action must be APPROVED or REJECTED.');
if ($action === 'REJECTED' && !$comment) json_error('Comments are required when rejecting.');

$db = Database::getInstance();

// Fetch task
$stmt = $db->prepare(
    'SELECT wt.*, ws.requires_otp, ws.step_code, ws.workflow_id,
            wi.ge_id, wi.id AS instance_id,
            ge.status AS ge_status, ge.version AS ge_version, ge.ge_number,
            ge.claimant_id
     FROM workflow_tasks wt
     JOIN workflow_steps ws ON ws.id = wt.step_id
     JOIN workflow_instances wi ON wi.id = wt.instance_id
     JOIN general_expenses ge ON ge.id = wi.ge_id
     WHERE wt.id = ? AND wt.assignee_id = ? AND wt.status IN ("PENDING","IN_PROGRESS")'
);
$stmt->execute([$taskId, $user['id']]);
$task = $stmt->fetch();
if (!$task) json_error('Task not found or already completed.', 404);

// OTP verification
if ($task['requires_otp'] && !DEV_BYPASS_OTP) {
    if (!$otp) json_error('OTP is required for this step.');
    $otpStmt = $db->prepare(
        'SELECT * FROM otp_tokens WHERE user_id = ? AND task_id = ? AND is_used = 0
         AND expires_at > NOW() ORDER BY created_at DESC LIMIT 1'
    );
    $otpStmt->execute([$user['id'], $taskId]);
    $otpRow = $otpStmt->fetch();
    if (!$otpRow) json_error('OTP not found or expired. Please request a new one.', 400);
    if ((int)$otpRow['attempts'] >= OTP_MAX_ATTEMPTS) json_error('Too many OTP attempts.', 429);

    $db->prepare('UPDATE otp_tokens SET attempts = attempts + 1 WHERE id = ?')
       ->execute([$otpRow['id']]);

    if ($otpRow['code'] !== $otp) json_error('Invalid OTP.', 400);

    // Mark used
    $db->prepare('UPDATE otp_tokens SET is_used = 1 WHERE id = ?')->execute([$otpRow['id']]);
}

// HOD Certification validation
$hodSigData   = trim($b['hod_signature_data'] ?? '');
$hodDesig     = trim($b['hod_designation']    ?? '');
$hodCertAt    = trim($b['hod_certified_at']   ?? '');

if ($task['step_code'] === 'HEAD_OF_SCHOOL' && $action === 'APPROVED') {
    if ($hodSigData === '' || $hodDesig === '') {
        json_error('HOD signature and designation are required for this step.', 400);
    }
}

// Complete task
$db->prepare(
    'UPDATE workflow_tasks SET status = "COMPLETED", action_taken = ?,
     comments = ?, otp_verified = ?, ge_version_at_action = ?,
     hod_signature_data = ?, hod_designation = ?, hod_certified_at = ?,
     completed_at = NOW()
     WHERE id = ?'
)->execute([
    $action, $comment,
    ($task['requires_otp'] && !DEV_BYPASS_OTP) ? 1 : (DEV_BYPASS_OTP ? 1 : 0),
    $task['ge_version'],
    ($hodSigData !== '' ? $hodSigData : null),
    ($hodDesig   !== '' ? $hodDesig   : null),
    ($hodCertAt  !== '' ? $hodCertAt  : (($hodSigData !== '') ? date('Y-m-d H:i:s') : null)),
    $taskId,
]);

if ($action === 'REJECTED') {
    // Cancel GE and workflow
    $db->prepare(
        'UPDATE general_expenses SET status = "CANCELLED",
         cancel_reason = ?, cancelled_at = NOW() WHERE id = ?'
    )->execute(["Rejected by {$user['first_name']} {$user['last_name']}: $comment", $task['ge_id']]);
    $db->prepare('UPDATE workflow_instances SET status = "CANCELLED" WHERE id = ?')
       ->execute([$task['instance_id']]);
    notify($task['claimant_id'], 'GE_REJECTED',
           "Your GE {$task['ge_number']} was rejected at step {$task['step_code']}: $comment",
           $task['ge_id']);
    audit($user['id'], 'TASK_REJECTED', "Task rejected: $comment", $task['ge_id']);
    json_success('GE rejected and cancelled.');
}

// Advance to next step
$nextStmt = $db->prepare(
    'SELECT * FROM workflow_steps WHERE workflow_id = ?
     AND step_order > (SELECT step_order FROM workflow_steps WHERE id = ?)
     ORDER BY step_order ASC LIMIT 1'
);
$nextStmt->execute([$task['workflow_id'], $task['step_id']]);
$nextStep = $nextStmt->fetch();

if ($nextStep) {
    // Resolve next delegate
    $geRow = $db->prepare('SELECT * FROM general_expenses WHERE id = ?');
    $geRow->execute([$task['ge_id']]);
    $ge = $geRow->fetch();

    // Inline delegate resolver
    $delegate = resolve_step_delegate($nextStep, $ge, $db);

    $db->prepare(
        'INSERT INTO workflow_tasks (instance_id, step_id, assignee_id, status)
         VALUES (?,?,?,"PENDING")'
    )->execute([$task['instance_id'], $nextStep['id'], $delegate ? $delegate['id'] : null]);

    $db->prepare(
        'UPDATE workflow_instances SET current_step_id = ? WHERE id = ?'
    )->execute([$nextStep['id'], $task['instance_id']]);

    $db->prepare(
        'UPDATE general_expenses SET status = ?, version = version + 1 WHERE id = ?'
    )->execute([$nextStep['ge_status_on_reach'], $task['ge_id']]);

    if ($delegate) {
        notify($delegate['id'], 'APPROVAL_REQUIRED',
               "GE {$task['ge_number']} requires your approval: {$nextStep['label']}.",
               $task['ge_id']);
    }
    audit($user['id'], 'TASK_APPROVED', "Task approved. Advanced to: {$nextStep['step_code']}", $task['ge_id']);
    json_success('Approved. Workflow advanced to ' . $nextStep['label'] . '.');
} else {
    // All steps complete
    $db->prepare(
        'UPDATE general_expenses SET status = "COMPLETED", completed_at = NOW(), version = version + 1 WHERE id = ?'
    )->execute([$task['ge_id']]);
    $db->prepare(
        'UPDATE workflow_instances SET status = "COMPLETED", completed_at = NOW() WHERE id = ?'
    )->execute([$task['instance_id']]);
    notify($task['claimant_id'], 'GE_COMPLETED',
           "Your GE {$task['ge_number']} has been fully approved and completed.",
           $task['ge_id']);
    audit($user['id'], 'GE_COMPLETED', 'All workflow steps complete', $task['ge_id']);
    json_success('GE fully approved and completed.');
}

function resolve_step_delegate(array $step, array $ge, PDO $db): ?array {
    $roleStmt = $db->prepare('SELECT id FROM roles WHERE code = ?');
    $roleStmt->execute([$step['role_required']]);
    $role = $roleStmt->fetch();
    if (!$role) return null;

    $s = $db->prepare(
        'SELECT u.* FROM delegate_assignments da JOIN users u ON u.id = da.user_id
         WHERE da.role_id = ? AND da.department_id = ? AND da.is_active = 1 LIMIT 1'
    );
    $s->execute([$role['id'], $ge['department_id']]);
    if ($u = $s->fetch()) return $u;

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
    $s3 = $db->prepare(
        'SELECT u.* FROM delegate_assignments da JOIN users u ON u.id = da.user_id
         WHERE da.role_id = ? AND da.department_id IS NULL AND da.faculty_code IS NULL
         AND da.is_active = 1 LIMIT 1'
    );
    $s3->execute([$role['id']]);
    if ($u3 = $s3->fetch()) return $u3;

    $s4 = $db->prepare(
        'SELECT u.* FROM users u JOIN user_roles ur ON ur.user_id = u.id
         WHERE ur.role_id = ? AND u.is_active = 1 LIMIT 1'
    );
    $s4->execute([$role['id']]);
    return $s4->fetch() ?: null;
}
