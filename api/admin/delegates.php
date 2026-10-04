<?php
// /api/admin/delegates.php — Manage delegate assignments (SYSTEM_ADMIN only)
require_once __DIR__ . '/../../config/helpers.php';
set_cors_headers();

$user   = require_role(['SYSTEM_ADMIN']);
$db     = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $db->prepare(
        'SELECT da.id, r.code AS role_code, r.label AS role_label,
                CONCAT(u.first_name," ",u.last_name) AS user_name, u.staff_id,
                d.name AS department_name, da.faculty_code, da.is_active, da.assigned_at
         FROM delegate_assignments da
         JOIN roles r ON r.id = da.role_id
         JOIN users u ON u.id = da.user_id
         LEFT JOIN departments d ON d.id = da.department_id
         ORDER BY r.code, d.name'
    );
    $stmt->execute();
    json_success('OK', ['delegates' => $stmt->fetchAll()]);
}

if ($method === 'POST') {
    $b = get_json_body();
    if (empty($b['role_code']) || empty($b['user_id'])) json_error('role_code and user_id required.');

    $roleStmt = $db->prepare('SELECT id FROM roles WHERE code = ?');
    $roleStmt->execute([strtoupper($b['role_code'])]);
    $role = $roleStmt->fetch();
    if (!$role) json_error('Role not found.');

    $db->prepare(
        'INSERT INTO delegate_assignments (role_id, user_id, department_id, faculty_code, assigned_by)
         VALUES (?,?,?,?,?)'
    )->execute([
        $role['id'],
        (int)$b['user_id'],
        !empty($b['department_id']) ? (int)$b['department_id'] : null,
        !empty($b['faculty_code']) ? $b['faculty_code'] : null,
        $user['id'],
    ]);
    audit($user['id'], 'DELEGATE_ASSIGNED', "Delegate assigned: {$b['role_code']} → user {$b['user_id']}");
    json_success('Delegate assigned.', ['id' => (int)$db->lastInsertId()], 201);
}

if ($method === 'DELETE' || ($method === 'POST' && isset($_GET['delete']))) {
    $b  = get_json_body();
    $id = (int)($b['id'] ?? 0);
    if (!$id) json_error('id required.');
    $db->prepare('UPDATE delegate_assignments SET is_active = 0 WHERE id = ?')->execute([$id]);
    audit($user['id'], 'DELEGATE_REMOVED', "Delegate assignment $id deactivated");
    json_success('Delegate removed.');
}
