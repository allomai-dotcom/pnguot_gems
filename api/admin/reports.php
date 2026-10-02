<?php
// GET /api/admin/reports.php — Dashboard stats and reports (SYSTEM_ADMIN only)
require_once __DIR__ . '/../../config/helpers.php';
set_cors_headers();

$user = require_role(['SYSTEM_ADMIN','ACCOUNTS_OFFICER']);
$db   = Database::getInstance();

// ── NEW: Expenditure report path ─────────────────────────────
if (isset($_GET['report']) && $_GET['report'] === 'expenditure') {
    $date_from = trim($_GET['date_from'] ?? '');
    $date_to   = trim($_GET['date_to']   ?? '');

    if ($date_from === '' || $date_to === '') {
        json_error('date_from and date_to are required');
    }

    // Sanitise / cast optional filters
    $status_filter = isset($_GET['status']) && $_GET['status'] !== '' ? $_GET['status'] : null;
    $type_filter   = isset($_GET['type'])   && $_GET['type']   !== '' ? $_GET['type']   : null;

    // ── Query 1: GE Claims ───────────────────────────────────
    $where  = ['ge.created_at BETWEEN :date_from AND :date_to'];
    $params = [':date_from' => $date_from . ' 00:00:00', ':date_to' => $date_to . ' 23:59:59'];

    if ($status_filter !== null) {
        $where[]              = 'ge.status = :status';
        $params[':status']    = $status_filter;
    }
    if ($type_filter !== null) {
        $where[]           = 'ge.procurement_type = :ptype';
        $params[':ptype']  = $type_filter;
    }

    $whereSQL = implode(' AND ', $where);

    $claimStmt = $db->prepare(
        "SELECT ge.id, ge.ge_number, ge.payee_name, ge.description, ge.procurement_type,
                ge.is_capital_item, ge.total_amount, ge.status, ge.submitted_at,
                ge.completed_at, ge.created_at, ge.claimant_full_name,
                CONCAT(u.first_name,' ',u.last_name) AS claimant_name,
                d.name AS department_name, d.code AS dept_code
         FROM general_expenses ge
         JOIN users u ON u.id = ge.claimant_id
         JOIN departments d ON d.id = ge.department_id
         WHERE {$whereSQL}
         ORDER BY ge.created_at DESC"
    );
    $claimStmt->execute($params);
    $claims = $claimStmt->fetchAll(PDO::FETCH_ASSOC);

    // Add status labels
    foreach ($claims as &$row) {
        $row['status_label'] = ge_status_label($row['status']);
    }
    unset($row);

    // Total expenditure
    $total_expenditure = array_sum(array_column($claims, 'total_amount'));

    // ── Query 2: Linked Quotations ───────────────────────────
    $quotations = [];
    if (!empty($claims)) {
        $ge_ids = array_column($claims, 'id');
        $placeholders = implode(',', array_fill(0, count($ge_ids), '?'));

        $quotStmt = $db->prepare(
            "SELECT gd.id, gd.ge_id, gd.document_type, gd.label, gd.original_name,
                    gd.file_size, gd.uploaded_at,
                    ge.ge_number, ge.payee_name
             FROM ge_documents gd
             JOIN general_expenses ge ON ge.id = gd.ge_id
             WHERE gd.ge_id IN ({$placeholders})
               AND gd.document_type IN ('QUOTATION_1','QUOTATION_2','QUOTATION_3')
               AND gd.deleted_at IS NULL
             ORDER BY ge.ge_number, gd.document_type"
        );
        $quotStmt->execute($ge_ids);
        $quotations = $quotStmt->fetchAll(PDO::FETCH_ASSOC);

        // Compute auto-reference
        foreach ($quotations as &$q) {
            $slot = substr($q['document_type'], -1); // '1', '2', or '3'
            $q['auto_reference'] = 'QT-' . $q['ge_number'] . '-' . $slot;
        }
        unset($q);
    }

    // ── Query 3: Expenditure by Category ────────────────────
    $by_category = [];

    $catStmt = $db->prepare(
        "SELECT ge.procurement_type, ge.is_capital_item,
                COUNT(*) AS ge_count, SUM(ge.total_amount) AS total_amount
         FROM general_expenses ge
         JOIN users u ON u.id = ge.claimant_id
         JOIN departments d ON d.id = ge.department_id
         WHERE {$whereSQL}
         GROUP BY ge.procurement_type, ge.is_capital_item"
    );
    $catStmt->execute($params);
    $cat_rows = $catStmt->fetchAll(PDO::FETCH_ASSOC);

    $cat_buckets = [
        'Standard Purchase' => ['label' => 'Standard Purchase', 'ge_count' => 0, 'total_amount' => 0],
        'ICT Purchase'      => ['label' => 'ICT Purchase',      'ge_count' => 0, 'total_amount' => 0],
        'Capital Item'      => ['label' => 'Capital Item',      'ge_count' => 0, 'total_amount' => 0],
    ];

    foreach ($cat_rows as $cr) {
        if ((int)$cr['is_capital_item'] === 1) {
            $key = 'Capital Item';
        } elseif ($cr['procurement_type'] === 'ICT') {
            $key = 'ICT Purchase';
        } else {
            $key = 'Standard Purchase';
        }
        $cat_buckets[$key]['ge_count']    += (int)$cr['ge_count'];
        $cat_buckets[$key]['total_amount'] += (float)$cr['total_amount'];
    }

    $grand_total = max((float)$total_expenditure, 0.01); // avoid division by zero
    foreach ($cat_buckets as &$bucket) {
        $bucket['percentage'] = $total_expenditure > 0
            ? round(($bucket['total_amount'] / $grand_total) * 100, 1)
            : 0;
    }
    unset($bucket);
    $by_category = array_values($cat_buckets);

    // ── Expenditure by Department ────────────────────────────
    $deptStmt = $db->prepare(
        "SELECT d.name AS department_name, COUNT(*) AS ge_count,
                SUM(ge.total_amount) AS total_amount
         FROM general_expenses ge
         JOIN users u ON u.id = ge.claimant_id
         JOIN departments d ON d.id = ge.department_id
         WHERE {$whereSQL}
         GROUP BY ge.department_id, d.name
         ORDER BY total_amount DESC"
    );
    $deptStmt->execute($params);
    $dept_rows = $deptStmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($dept_rows as &$dr) {
        $dr['ge_count']    = (int)$dr['ge_count'];
        $dr['total_amount'] = (float)$dr['total_amount'];
        $dr['percentage']   = $total_expenditure > 0
            ? round(($dr['total_amount'] / $grand_total) * 100, 1)
            : 0;
    }
    unset($dr);

    json_success('OK', [
        'report_data' => [
            'claims'            => $claims,
            'quotations'        => $quotations,
            'by_category'       => $by_category,
            'by_department'     => $dept_rows,
            'total_expenditure' => (float)$total_expenditure,
            'date_from'         => $date_from,
            'date_to'           => $date_to,
            'generated_at'      => date('Y-m-d H:i:s'),
        ]
    ]);
    // json_success calls exit — nothing below runs for this path
}

// ── EXISTING: Dashboard stats path ──────────────────────────

// Summary counts by status
$statusStmt = $db->query(
    'SELECT status, COUNT(*) AS cnt, SUM(total_amount) AS total
     FROM general_expenses GROUP BY status'
);
$byStatus = [];
foreach ($statusStmt->fetchAll() as $r) {
    $byStatus[$r['status']] = [
        'count' => (int)$r['cnt'],
        'total' => (float)$r['total'],
        'label' => ge_status_label($r['status']),
    ];
}

// Monthly totals (last 12 months)
$monthlyStmt = $db->query(
    "SELECT DATE_FORMAT(submitted_at,'%Y-%m') AS month,
            COUNT(*) AS cnt, SUM(total_amount) AS total
     FROM general_expenses
     WHERE submitted_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
     GROUP BY month ORDER BY month"
);
$monthly = $monthlyStmt->fetchAll();

// Top departments by amount
$deptStmt = $db->query(
    'SELECT d.name AS department, COUNT(*) AS ge_count, SUM(ge.total_amount) AS total_amount
     FROM general_expenses ge JOIN departments d ON d.id = ge.department_id
     WHERE ge.status = "COMPLETED"
     GROUP BY ge.department_id ORDER BY total_amount DESC LIMIT 10'
);
$byDept = $deptStmt->fetchAll();

// Pending approvals count by role
$pendingStmt = $db->query(
    'SELECT ws.role_required, COUNT(*) AS cnt
     FROM workflow_tasks wt JOIN workflow_steps ws ON ws.id = wt.step_id
     WHERE wt.status = "PENDING"
     GROUP BY ws.role_required'
);
$pendingByRole = $pendingStmt->fetchAll();

// Total users
$userCount = (int)$db->query('SELECT COUNT(*) FROM users WHERE is_active = 1')->fetchColumn();

// Recent GEs
$recentStmt = $db->query(
    'SELECT ge.ge_number, ge.status, ge.total_amount, ge.submitted_at,
            CONCAT(u.first_name," ",u.last_name) AS claimant_name
     FROM general_expenses ge JOIN users u ON u.id = ge.claimant_id
     ORDER BY ge.updated_at DESC LIMIT 10'
);
$recentGEs = $recentStmt->fetchAll();
foreach ($recentGEs as &$r) $r['status_label'] = ge_status_label($r['status']);

json_success('OK', [
    'by_status'       => $byStatus,
    'monthly_trends'  => $monthly,
    'by_department'   => $byDept,
    'pending_by_role' => $pendingByRole,
    'user_count'      => $userCount,
    'recent_ges'      => $recentGEs,
]);
