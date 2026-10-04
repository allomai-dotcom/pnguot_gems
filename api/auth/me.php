<?php
require_once __DIR__ . '/../../config/helpers.php';
set_cors_headers();

$user = require_auth();
$db   = Database::getInstance();

$nStmt = $db->prepare('SELECT COUNT(*) AS cnt FROM notifications WHERE user_id = ? AND is_read = 0');
$nStmt->execute([$user['id']]);
$user['unread_notifications'] = (int)$nStmt->fetch()['cnt'];

json_success('OK', ['user' => $user]);
