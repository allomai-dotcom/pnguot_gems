<?php
require_once __DIR__ . '/../../config/helpers.php';
set_cors_headers();

$user = require_auth();
$db   = Database::getInstance();

$nStmt = $db->prepare('SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND is_read = 0');
$nStmt->execute([$user['id']]);
$user['unread_notifications'] = (int)$nStmt->fetch()['cnt'];

// Resolve avatar URL
$user['avatar_url'] = null;
if (!empty($user['avatar_path'])) {
    $user['avatar_url'] = '/uploads/avatars/' . $user['avatar_path'];
}

json_success('OK', ['user' => $user]);
