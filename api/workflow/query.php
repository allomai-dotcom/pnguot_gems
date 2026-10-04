<?php
// POST /api/workflow/query.php — Raise a query / respond to a query
require_once __DIR__ . '/../../config/helpers.php';
set_cors_headers();

$user   = require_auth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Method not allowed.', 405);

$b      = get_json_body();
$action = $b['action'] ?? ''; // 'raise' | 'respond'

if ($action === 'raise') {
    $taskId  = (int)($b['task_id'] ?? 0);
    $message = trim($b['message'] ?? '');
    if (!$taskId)          json_error('task_id required.');
    if (strlen($message) < 10) json_error('Query message must be at least 10 characters.');

    $db = Database::getInstance();
    $stmt = $db->prepare(
        'SELECT wt.*, wi.ge_id, ge.claimant_id, ge.ge_number
         FROM workflow_tasks wt
         JOIN workflow_instances wi ON wi.id = wt.instance_id
         JOIN general_expenses ge ON ge.id = wi.ge_id
         WHERE wt.id = ? AND wt.assignee_id = ?'
    );
    $stmt->execute([$taskId, $user['id']]);
    $task = $stmt->fetch();
    if (!$task) json_error('Task not found.', 404);

    $db->prepare(
        'INSERT INTO ge_queries (ge_id, task_id, raised_by, message) VALUES (?,?,?,?)'
    )->execute([$task['ge_id'], $taskId, $user['id'], $message]);

    $db->prepare(
        'UPDATE workflow_tasks SET status = "QUERIED" WHERE id = ?'
    )->execute([$taskId]);
    $db->prepare(
        'UPDATE workflow_instances SET status = "SUSPENDED" WHERE ge_id = ?'
    )->execute([$task['ge_id']]);
    $db->prepare(
        'UPDATE general_expenses SET status = "QUERIED", version = version + 1 WHERE id = ?'
    )->execute([$task['ge_id']]);

    notify($task['claimant_id'], 'GE_QUERIED',
           "A query was raised on GE {$task['ge_number']}: $message", $task['ge_id']);
    audit($user['id'], 'QUERY_RAISED', "Query raised: $message", $task['ge_id']);
    json_success('Query raised. Claimant has been notified.');

} elseif ($action === 'respond') {
    $queryId  = (int)($b['query_id'] ?? 0);
    $response = trim($b['response'] ?? '');
    if (!$queryId)  json_error('query_id required.');
    if (!$response) json_error('Response message required.');

    $db = Database::getInstance();
    $stmt = $db->prepare(
        'SELECT gq.*, ge.claimant_id, ge.ge_number, wt.assignee_id, wt.id AS task_id
         FROM ge_queries gq
         JOIN general_expenses ge ON ge.id = gq.ge_id
         JOIN workflow_tasks wt ON wt.id = gq.task_id
         WHERE gq.id = ? AND ge.claimant_id = ? AND gq.status = "OPEN"'
    );
    $stmt->execute([$queryId, $user['id']]);
    $query = $stmt->fetch();
    if (!$query) json_error('Query not found or already resolved.', 404);

    $db->prepare(
        'INSERT INTO ge_query_responses (query_id, responded_by, message) VALUES (?,?,?)'
    )->execute([$queryId, $user['id'], $response]);

    $db->prepare(
        'UPDATE ge_queries SET status = "RESOLVED", resolved_at = NOW() WHERE id = ?'
    )->execute([$queryId]);
    $db->prepare(
        'UPDATE workflow_tasks SET status = "IN_PROGRESS" WHERE id = ?'
    )->execute([$query['task_id']]);
    $db->prepare(
        'UPDATE workflow_instances SET status = "ACTIVE" WHERE ge_id = ?'
    )->execute([$query['ge_id']]);
    $db->prepare(
        'UPDATE general_expenses SET status = (SELECT ge_status_on_reach FROM workflow_steps
         WHERE id = (SELECT current_step_id FROM workflow_instances WHERE ge_id = ?)),
         version = version + 1 WHERE id = ?'
    )->execute([$query['ge_id'], $query['ge_id']]);

    if ($query['assignee_id']) {
        notify($query['assignee_id'], 'QUERY_RESPONDED',
               "Claimant responded to query on GE {$query['ge_number']}: $response",
               $query['ge_id']);
    }
    audit($user['id'], 'QUERY_RESPONDED', "Responded to query: $response", $query['ge_id']);
    json_success('Response submitted. Workflow resumed.');
} else {
    json_error('action must be "raise" or "respond".');
}
