<?php
require_once __DIR__ . '/../../config/helpers.php';
set_cors_headers();
start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Method not allowed.', 405);

$body  = get_json_body();
$email = trim($body['email'] ?? '');
$pass  = $body['password'] ?? '';

if (!$email || !$pass) json_error('Email and password are required.');

$db   = Database::getInstance();
$stmt = $db->prepare(
    'SELECT u.id, u.staff_id, u.first_name, u.last_name, u.email,
            u.password_hash, u.is_active, u.department_id,
            d.code AS dept_code, d.name AS dept_name,
            GROUP_CONCAT(r.code ORDER BY r.code SEPARATOR ",") AS roles
     FROM users u
     LEFT JOIN departments d ON d.id = u.department_id
     LEFT JOIN user_roles ur ON ur.user_id = u.id
     LEFT JOIN roles r ON r.id = ur.role_id
     WHERE u.email = ?
     GROUP BY u.id'
);
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user || !password_verify($pass, $user['password_hash'])) {
    json_error('Invalid email or password.', 401);
}

if (!$user['is_active']) json_error('Your account is inactive. Contact admin.', 403);

// Regenerate session
session_regenerate_id(true);
$_SESSION['user_id']   = $user['id'];
$_SESSION['logged_in'] = true;

$user['roles'] = $user['roles'] ? explode(',', $user['roles']) : [];
unset($user['password_hash'], $user['is_active']);

// Count unread notifications
$nStmt = $db->prepare('SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND is_read = 0');
$nStmt->execute([$user['id']]);
$user['unread_notifications'] = (int)$nStmt->fetch()['cnt'];

audit($user['id'], 'LOGIN', 'User logged in');
json_success('Login successful.', ['user' => $user]);
