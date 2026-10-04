<?php
// GET /api/workflow/my_tasks.php — Pending approval tasks for logged-in delegate
require_once __DIR__ . '/../../config/helpers.php';
set_cors_headers();

$user = require_auth();
$db   = Database::getInstance();

$stmt = $db->prepare(
    'SELECT wt.id AS task_id, wt.status AS task_status,
            ge.id AS ge_id, ge.ge_number, ge.payee_name, ge.total_amount,
            ge.procurement_type, ge.is_capital_item, ge.status AS ge_status,
            ge.submitted_at,
            CONCAT(u.first_name," ",u.last_name) AS claimant_name,
            d.name AS department_name,
            ws.label AS step_label, ws.step_code, ws.requires_otp
     FROM workflow_tasks wt
     JOIN workflow_instances wi ON wi.id = wt.instance_id
     JOIN general_expenses ge ON ge.id = wi.ge_id
     JOIN users u ON u.id = ge.claimant_id
     JOIN departments d ON d.id = ge.department_id
     JOIN workflow_steps ws ON ws.id = wt.step_id
     WHERE wt.assignee_id = ? AND wt.status IN ("PENDING","IN_PROGRESS","QUERIED")
     ORDER BY wt.assigned_at ASC'
);
$stmt->execute([$user['id']]);
$tasks = $stmt->fetchAll();
foreach ($tasks as &$t) {
    $t['ge_status_label']  = ge_status_label($t['ge_status']);
    $t['is_capital_item']  = (bool)$t['is_capital_item'];
    $t['requires_otp']     = (bool)$t['requires_otp'];
}

json_success('OK', ['tasks' => $tasks, 'count' => count($tasks)]);
