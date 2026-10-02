<?php
// POST /api/ge/send_to_accounts.php — Mark a GE as sent to accounts
require_once __DIR__ . '/../../config/helpers.php';
set_cors_headers();

$user = require_auth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Method not allowed.', 405);

$allowed_roles = ['ACCOUNTS_OFFICER', 'SYSTEM_ADMIN'];
if (empty(array_intersect($user['roles'], $allowed_roles))) {
    json_error('Forbidden. Only Accounts Officers may perform this action.', 403);
}

$b  = get_json_body();
$id = (int)($b['id'] ?? 0);
if (!$id) json_error('GE id required.');

$db = Database::getInstance();
$stmt = $db->prepare('SELECT id, ge_number, sent_to_accounts FROM general_expenses WHERE id = ?');
$stmt->execute([$id]);
$ge = $stmt->fetch();
if (!$ge) json_error('GE not found.', 404);

if ($ge['sent_to_accounts']) {
    json_error('This GE has already been sent to Accounts.');
}

$db->prepare(
    'UPDATE general_expenses
     SET sent_to_accounts = 1, sent_to_accounts_at = NOW(), sent_to_accounts_by = ?
     WHERE id = ?'
)->execute([$user['id'], $id]);

audit($user['id'], 'GE_SENT_TO_ACCOUNTS', 'GE marked as sent to Accounts', $id);

json_success('GE marked as sent to Accounts.');
