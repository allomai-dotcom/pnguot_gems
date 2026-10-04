<?php
// POST /api/ge/cancel.php — Cancel a GE (before SUBMITTED)
require_once __DIR__ . '/../../config/helpers.php';
set_cors_headers();

$user = require_auth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Method not allowed.', 405);

$b      = get_json_body();
$id     = (int)($b['id'] ?? 0);
$reason = trim($b['reason'] ?? '');
if (!$id)     json_error('GE id required.');
if (!$reason) json_error('Cancellation reason is required.');

$db   = Database::getInstance();
$stmt = $db->prepare('SELECT * FROM general_expenses WHERE id = ? AND claimant_id = ?');
$stmt->execute([$id, $user['id']]);
$ge = $stmt->fetch();
if (!$ge) json_error('GE not found or access denied.', 404);

$cancellableStatuses = ['DRAFT','READY_FOR_DOCUMENTS','SUBMITTED'];
if (!in_array($ge['status'], $cancellableStatuses)) {
    json_error('GE cannot be cancelled at status: ' . $ge['status']);
}

$db->prepare(
    'UPDATE general_expenses SET status = "CANCELLED", cancel_reason = ?, cancelled_at = NOW() WHERE id = ?'
)->execute([$reason, $id]);

// If workflow started, cancel instance
$db->prepare(
    'UPDATE workflow_instances SET status = "CANCELLED" WHERE ge_id = ?'
)->execute([$id]);

audit($user['id'], 'GE_CANCELLED', "GE cancelled: $reason", $id);
json_success('GE cancelled successfully.');
