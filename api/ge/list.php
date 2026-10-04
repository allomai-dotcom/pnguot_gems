<?php
// GET /api/ge/list.php — List GEs for current user (claimant or approver)
require_once __DIR__ . '/../../config/helpers.php';
set_cors_headers();

$user = require_auth();
$db   = Database::getInstance();

$isAdmin    = in_array('SYSTEM_ADMIN', $user['roles']);
$isApprover = !empty(array_intersect($user['roles'],
    ['HEAD_OF_SCHOOL','DEAN','ICT_DIRECTOR','PROCUREMENT_MANAGER','ACCOUNTS_OFFICER','VICE_CHANCELLOR']));

$page     = max(1, (int)($_GET['page'] ?? 1));
$limit    = min(50, max(5, (int)($_GET['limit'] ?? 20)));
$offset   = ($page - 1) * $limit;
$status   = $_GET['status'] ?? '';
$search   = trim($_GET['search'] ?? '');

// Base query
$where  = [];
$params = [];

if ($isAdmin) {
    // Admin sees all
} elseif ($isApprover) {
    // Approvers see their assigned tasks + own claims
    $where[]  = '(ge.claimant_id = ? OR wt.assignee_id = ?)';
    $params[] = $user['id'];
    $params[] = $user['id'];
} else {
    // Claimants see only their own
    $where[]  = 'ge.claimant_id = ?';
    $params[] = $user['id'];
}

if ($status) { $where[] = 'ge.status = ?'; $params[] = $status; }
if ($search) {
    $where[]  = '(ge.ge_number LIKE ? OR ge.payee_name LIKE ? OR ge.claimant_reference LIKE ?)';
    $like = "%$search%";
    array_push($params, $like, $like, $like);
}

$whereClause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$countSql = "SELECT COUNT(DISTINCT ge.id) AS total
             FROM general_expenses ge
             LEFT JOIN workflow_tasks wt ON wt.instance_id IN (
                 SELECT id FROM workflow_instances WHERE ge_id = ge.id
             )
             $whereClause";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$total = (int)$countStmt->fetch()['total'];

$sql = "SELECT DISTINCT ge.id, ge.ge_number, ge.status, ge.payee_name,
               ge.departmental_reference, ge.claimant_reference,
               ge.procurement_type, ge.is_capital_item, ge.total_amount,
               ge.created_at, ge.updated_at, ge.submitted_at,
               CONCAT(u.first_name,' ',u.last_name) AS claimant_name,
               d.name AS department_name
        FROM general_expenses ge
        LEFT JOIN users u ON u.id = ge.claimant_id
        LEFT JOIN departments d ON d.id = ge.department_id
        LEFT JOIN workflow_tasks wt ON wt.instance_id IN (
            SELECT id FROM workflow_instances WHERE ge_id = ge.id
        )
        $whereClause
        ORDER BY ge.updated_at DESC
        LIMIT $limit OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

foreach ($rows as &$r) {
    $r['status_label']    = ge_status_label($r['status']);
    $r['is_capital_item'] = (bool)$r['is_capital_item'];
}

json_success('OK', [
    'data'       => $rows,
    'pagination' => ['page' => $page, 'limit' => $limit, 'total' => $total,
                     'pages' => (int)ceil($total / $limit)],
]);
