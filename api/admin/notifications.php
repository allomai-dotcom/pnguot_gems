<?php
// GET/POST /api/admin/notifications.php — Notifications for current user
require_once __DIR__ . '/../../config/helpers.php';
set_cors_headers();

$user = require_auth();
$db   = Database::getInstance();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->prepare(
        'SELECT n.id, n.type, n.message, n.is_read, n.created_at,
                ge.ge_number
         FROM notifications n
         LEFT JOIN general_expenses ge ON ge.id = n.ge_id
         WHERE n.user_id = ?
         ORDER BY n.created_at DESC LIMIT 50'
    );
    $stmt->execute([$user['id']]);
    $notes = $stmt->fetchAll();
    foreach ($notes as &$n) $n['is_read'] = (bool)$n['is_read'];
    json_success('OK', ['notifications' => $notes]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $b = get_json_body();
    if (isset($b['mark_read_all']) && $b['mark_read_all']) {
        $db->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ?')->execute([$user['id']]);
        json_success('All notifications marked as read.');
    }
    if (!empty($b['id'])) {
        $db->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?')
           ->execute([(int)$b['id'], $user['id']]);
        json_success('Notification marked as read.');
    }
    json_error('Invalid request.');
}
