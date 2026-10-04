<?php
require_once __DIR__ . '/../../config/helpers.php';
set_cors_headers();

$user = require_auth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Method not allowed.', 405);

$body    = get_json_body();
$current = $body['current_password'] ?? '';
$newPass = $body['new_password'] ?? '';

if (!$current || !$newPass) json_error('Current and new password are required.');
if (strlen($newPass) < 8) json_error('New password must be at least 8 characters.');

$db   = Database::getInstance();
$stmt = $db->prepare('SELECT password_hash FROM users WHERE id = ?');
$stmt->execute([$user['id']]);
$row  = $stmt->fetch();

if (!password_verify($current, $row['password_hash'])) json_error('Current password is incorrect.', 401);

$hash = password_hash($newPass, PASSWORD_BCRYPT, ['cost' => 12]);
$db->prepare('UPDATE users SET password_hash = ? WHERE id = ?')->execute([$hash, $user['id']]);
audit($user['id'], 'CHANGE_PASSWORD', 'Password changed');
json_success('Password changed successfully.');
