<?php
// GET /api/documents/view.php?doc_id=X — Stream document file (authenticated)
require_once __DIR__ . '/../../config/helpers.php';
set_cors_headers();

$user  = require_auth();
$docId = (int)($_GET['doc_id'] ?? 0);
if (!$docId) json_error('doc_id required.');

$db   = Database::getInstance();
$stmt = $db->prepare(
    'SELECT gd.*, ge.claimant_id
     FROM ge_documents gd JOIN general_expenses ge ON ge.id = gd.ge_id
     WHERE gd.id = ? AND gd.deleted_at IS NULL'
);
$stmt->execute([$docId]);
$doc = $stmt->fetch();
if (!$doc) json_error('Document not found.', 404);

// Auth: owner, admin, or assigned approver
$isOwner = $doc['claimant_id'] == $user['id'];
$isAdmin = in_array('SYSTEM_ADMIN', $user['roles']);
if (!$isOwner && !$isAdmin) {
    $chk = $db->prepare(
        'SELECT 1 FROM workflow_tasks wt
         JOIN workflow_instances wi ON wi.id = wt.instance_id
         WHERE wi.ge_id = ? AND wt.assignee_id = ? LIMIT 1'
    );
    $chk->execute([$doc['ge_id'], $user['id']]);
    if (!$chk->fetch()) json_error('Forbidden.', 403);
}

$filePath = UPLOAD_BASE_PATH . $doc['stored_name'];
if (!file_exists($filePath)) json_error('File not found on server.', 404);

$inline = isset($_GET['download']) ? 'attachment' : 'inline';
header('Content-Type: ' . ($doc['mime_type'] ?: 'application/octet-stream'));
header('Content-Disposition: ' . $inline . '; filename="' . addslashes($doc['original_name']) . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: private, no-cache');
readfile($filePath);
exit;
