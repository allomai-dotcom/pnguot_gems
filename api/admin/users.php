<?php
// /api/admin/users.php — CRUD for users (SYSTEM_ADMIN only)
require_once __DIR__ . '/../../config/helpers.php';
set_cors_headers();

$user = require_role(['SYSTEM_ADMIN']);
$db   = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];

// ── LIST ──────────────────────────────────────────────────────
if ($method === 'GET' && !isset($_GET['id'])) {
    $search = trim($_GET['search'] ?? '');
    $params = [];
    $where  = '';
    if ($search) {
        $where    = 'WHERE u.first_name LIKE ? OR u.last_name LIKE ? OR u.email LIKE ? OR u.staff_id LIKE ?';
        $like     = "%$search%";
        $params   = [$like,$like,$like,$like];
    }
    $stmt = $db->prepare(
        "SELECT u.id, u.staff_id, u.first_name, u.last_name, u.email, u.phone,
                u.is_active, u.created_at, d.name AS department_name,
                GROUP_CONCAT(r.code ORDER BY r.code SEPARATOR ',') AS roles
         FROM users u
         LEFT JOIN departments d ON d.id = u.department_id
         LEFT JOIN user_roles ur ON ur.user_id = u.id
         LEFT JOIN roles r ON r.id = ur.role_id
         $where
         GROUP BY u.id ORDER BY u.last_name, u.first_name"
    );
    $stmt->execute($params);
    $rows = $stmt->fetchAll();
    foreach ($rows as &$r) {
        $r['roles']     = $r['roles'] ? explode(',', $r['roles']) : [];
        $r['is_active'] = (bool)$r['is_active'];
    }
    json_success('OK', ['users' => $rows]);
}

// ── GET ONE ───────────────────────────────────────────────────
if ($method === 'GET' && isset($_GET['id'])) {
    $stmt = $db->prepare(
        'SELECT u.*, d.name AS dept_name,
                GROUP_CONCAT(r.code SEPARATOR ",") AS roles
         FROM users u LEFT JOIN departments d ON d.id = u.department_id
         LEFT JOIN user_roles ur ON ur.user_id = u.id
         LEFT JOIN roles r ON r.id = ur.role_id
         WHERE u.id = ? GROUP BY u.id'
    );
    $stmt->execute([(int)$_GET['id']]);
    $row = $stmt->fetch();
    if (!$row) json_error('User not found.', 404);
    $row['roles']     = $row['roles'] ? explode(',', $row['roles']) : [];
    $row['is_active'] = (bool)$row['is_active'];
    unset($row['password_hash']);
    json_success('OK', ['user' => $row]);
}

// ── CREATE ────────────────────────────────────────────────────
if ($method === 'POST') {
    $b = get_json_body();
    $required = ['staff_id','first_name','last_name','email','password'];
    foreach ($required as $f) if (empty($b[$f])) json_error("$f is required.");
    if (strlen($b['password']) < 8) json_error('Password must be at least 8 characters.');

    // Enforce university email domain
    $email = strtolower(trim($b['email']));
    if (!str_ends_with($email, '@pnguot.ac.pg')) {
        json_error('Email must use the university domain: @pnguot.ac.pg');
    }

    $check = $db->prepare('SELECT id FROM users WHERE email = ? OR staff_id = ?');
    $check->execute([$email, strtoupper(trim($b['staff_id']))]);
    if ($check->fetch()) json_error('Email or staff ID already exists.', 409);

    $hash = password_hash($b['password'], PASSWORD_BCRYPT, ['cost' => 12]);
    $db->prepare(
        'INSERT INTO users (staff_id, first_name, last_name, email, password_hash, phone, department_id)
         VALUES (?,?,?,?,?,?,?)'
    )->execute([
        strtoupper(trim($b['staff_id'])),
        trim($b['first_name']), trim($b['last_name']),
        $email, $hash,
        trim($b['phone'] ?? ''),
        $b['department_id'] ? (int)$b['department_id'] : null,
    ]);
    $newId = (int)$db->lastInsertId();

    // Assign roles
    if (!empty($b['roles']) && is_array($b['roles'])) {
        assign_roles($newId, $b['roles'], $user['id'], $db);
    }
    audit($user['id'], 'USER_CREATED', "User created: {$b['email']}");
    json_success('User created.', ['user_id' => $newId], 201);
}

// ── UPDATE ────────────────────────────────────────────────────
if ($method === 'PATCH' || ($method === 'POST' && isset($_GET['update']))) {
    $b  = get_json_body();
    $id = (int)($b['id'] ?? 0);
    if (!$id) json_error('id required.');

    $fields = []; $vals = [];
    foreach (['first_name','last_name','email','phone','department_id','is_active'] as $f) {
        if (array_key_exists($f, $b)) { $fields[] = "$f = ?"; $vals[] = $b[$f]; }
    }
    if (!empty($b['password'])) {
        if (strlen($b['password']) < 8) json_error('Password must be at least 8 characters.');
        $fields[] = 'password_hash = ?';
        $vals[]   = password_hash($b['password'], PASSWORD_BCRYPT, ['cost' => 12]);
    }
    if ($fields) {
        $vals[] = $id;
        $db->prepare('UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?')->execute($vals);
    }
    if (isset($b['roles']) && is_array($b['roles'])) {
        $db->prepare('DELETE FROM user_roles WHERE user_id = ?')->execute([$id]);
        assign_roles($id, $b['roles'], $user['id'], $db);
    }
    audit($user['id'], 'USER_UPDATED', "User $id updated");
    json_success('User updated.');
}

function assign_roles(int $userId, array $roleCodes, int $assignedBy, PDO $db): void {
    $roleStmt = $db->prepare('SELECT id FROM roles WHERE code = ?');
    $insStmt  = $db->prepare(
        'INSERT IGNORE INTO user_roles (user_id, role_id, assigned_by) VALUES (?,?,?)'
    );
    foreach ($roleCodes as $code) {
        $roleStmt->execute([strtoupper($code)]);
        $role = $roleStmt->fetch();
        if ($role) $insStmt->execute([$userId, $role['id'], $assignedBy]);
    }
}
