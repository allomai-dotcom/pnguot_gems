<?php
// GET /api/ge/my_reports.php — GE report for claimant (own GEs) or accounts officer (all GEs)
require_once __DIR__ . '/../../config/helpers.php';
set_cors_headers();

$user = require_auth();
$db   = Database::getInstance();

$dateFrom = trim($_GET['date_from'] ?? '');
$dateTo   = trim($_GET['date_to']   ?? '');
if (!$dateFrom || !$dateTo) json_error('date_from and date_to are required.');

$isAccountsOfficer = in_array('ACCOUNTS_OFFICER', $user['roles'])
                  || in_array('SYSTEM_ADMIN',      $user['roles']);

// Build WHERE clause — claimants see only their own GEs
$where  = ['ge.created_at BETWEEN :df AND :dt'];
$params = [':df' => $dateFrom . ' 00:00:00', ':dt' => $dateTo . ' 23:59:59'];

if (!$isAccountsOfficer) {
    $where[]           = 'ge.claimant_id = :uid';
    $params[':uid']    = $user['id'];
}

// Optional filters
if (!empty($_GET['status'])) {
    $where[]         = 'ge.status = :status';
    $params[':status'] = $_GET['status'];
}
if (!empty($_GET['dept_id'])) {
    $where[]           = 'ge.department_id = :dept';
    $params[':dept']   = (int)$_GET['dept_id'];
}
if (!empty($_GET['expense_category'])) {
    $where[]               = 'ge.expense_category = :ecat';
    $params[':ecat']       = $_GET['expense_category'];
}

$whereSQL = implode(' AND ', $where);

// ── GE list ───────────────────────────────────────────────────
$stmt = $db->prepare(
    "SELECT ge.id, ge.ge_number, ge.payee_name, ge.description,
            ge.procurement_type, ge.is_capital_item, ge.expense_category,
            ge.total_amount, ge.status, ge.submitted_at,
            ge.completed_at, ge.created_at,
            ge.departmental_reference, ge.claimant_reference,
            CONCAT(u.first_name,' ',u.last_name) AS claimant_name,
            u.email AS claimant_email,
            d.name AS department_name, d.code AS dept_code
     FROM general_expenses ge
     JOIN users u ON u.id = ge.claimant_id
     JOIN departments d ON d.id = ge.department_id
     WHERE $whereSQL
     ORDER BY ge.created_at DESC"
);
$stmt->execute($params);
$ges = $stmt->fetchAll();

foreach ($ges as &$g) {
    $g['status_label']    = ge_status_label($g['status']);
    $g['is_capital_item'] = (bool)$g['is_capital_item'];
}
unset($g);

$totalAmount = array_sum(array_column($ges, 'total_amount'));

// ── Summary by status ────────────────────────────────────────
$byStatus = [];
foreach ($ges as $g) {
    $s = $g['status'];
    if (!isset($byStatus[$s])) {
        $byStatus[$s] = ['label' => $g['status_label'], 'count' => 0, 'total' => 0.0];
    }
    $byStatus[$s]['count']++;
    $byStatus[$s]['total'] += (float)$g['total_amount'];
}

// ── Summary by department (accounts officer only) ────────────
$byDept = [];
if ($isAccountsOfficer) {
    foreach ($ges as $g) {
        $d = $g['department_name'];
        if (!isset($byDept[$d])) $byDept[$d] = ['count' => 0, 'total' => 0.0];
        $byDept[$d]['count']++;
        $byDept[$d]['total'] += (float)$g['total_amount'];
    }
    arsort($byDept);
}

// ── Summary by expense category ──────────────────────────────
$byType = [];
foreach ($ges as $g) {
    $key = !empty($g['expense_category'])
         ? $g['expense_category']
         : ($g['is_capital_item'] ? 'Capital Item'
            : ($g['procurement_type'] === 'ICT' ? 'ICT Purchase' : 'Standard Purchase'));
    if (!isset($byType[$key])) $byType[$key] = ['count' => 0, 'total' => 0.0];
    $byType[$key]['count']++;
    $byType[$key]['total'] += (float)$g['total_amount'];
}
arsort($byType);

json_success('OK', [
    'ges'          => $ges,
    'total_amount' => (float)$totalAmount,
    'ge_count'     => count($ges),
    'by_status'    => $byStatus,
    'by_dept'      => $byDept,
    'by_type'      => $byType,
    'date_from'    => $dateFrom,
    'date_to'      => $dateTo,
    'generated_at' => date('Y-m-d H:i:s'),
    'generated_by' => $user['first_name'] . ' ' . $user['last_name'],
    'is_full_view' => $isAccountsOfficer,
]);
