<?php
// POST /api/workflow/otp.php — Request OTP for a workflow task
require_once __DIR__ . '/../../config/helpers.php';
set_cors_headers();

$user = require_auth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Method not allowed.', 405);

$b      = get_json_body();
$taskId = (int)($b['task_id'] ?? 0);
if (!$taskId) json_error('task_id required.');

$db   = Database::getInstance();
$stmt = $db->prepare(
    'SELECT wt.*, ws.requires_otp FROM workflow_tasks wt
     JOIN workflow_steps ws ON ws.id = wt.step_id
     WHERE wt.id = ? AND wt.assignee_id = ? AND wt.status IN ("PENDING","IN_PROGRESS")'
);
$stmt->execute([$taskId, $user['id']]);
$task = $stmt->fetch();
if (!$task) json_error('Task not found.', 404);
if (!$task['requires_otp']) json_error('This step does not require OTP.');

// Check resend cooldown
$recentStmt = $db->prepare(
    'SELECT created_at FROM otp_tokens WHERE user_id = ? AND task_id = ? AND is_used = 0
     ORDER BY created_at DESC LIMIT 1'
);
$recentStmt->execute([$user['id'], $taskId]);
$recent = $recentStmt->fetch();
if ($recent) {
    $secondsAgo = time() - strtotime($recent['created_at']);
    if ($secondsAgo < OTP_RESEND_COOLDOWN) {
        json_error('Please wait ' . (OTP_RESEND_COOLDOWN - $secondsAgo) . ' seconds before requesting a new OTP.', 429);
    }
}

// Invalidate old tokens
$db->prepare('UPDATE otp_tokens SET is_used = 1 WHERE user_id = ? AND task_id = ? AND is_used = 0')
   ->execute([$user['id'], $taskId]);

if (DEV_BYPASS_OTP) {
    // Dev mode: return OTP in response (never in production)
    $code = '000000';
    $db->prepare(
        'INSERT INTO otp_tokens (user_id, task_id, code, expires_at)
         VALUES (?,?,?, DATE_ADD(NOW(), INTERVAL ? MINUTE))'
    )->execute([$user['id'], $taskId, $code, OTP_EXPIRY_MINUTES]);
    json_success('[DEV] OTP generated (bypass mode).', ['otp' => $code, 'dev_mode' => true]);
}

// Production: generate & SMS OTP (SMS integration placeholder)
$code = str_pad((string)random_int(0, 999999), OTP_LENGTH, '0', STR_PAD_LEFT);
$db->prepare(
    'INSERT INTO otp_tokens (user_id, task_id, code, expires_at)
     VALUES (?,?,?, DATE_ADD(NOW(), INTERVAL ? MINUTE))'
)->execute([$user['id'], $taskId, $code, OTP_EXPIRY_MINUTES]);

// TODO: integrate SMS gateway — $user['phone']
// send_sms($user['phone'], "Your PNGUOT GEMS OTP is $code. Valid for " . OTP_EXPIRY_MINUTES . " minutes.");

audit($user['id'], 'OTP_REQUESTED', "OTP requested for task $taskId");
json_success('OTP sent to your registered phone number.');
