<?php
// POST /api/documents/delete.php
require_once __DIR__ . '/../../config/helpers.php';
set_cors_headers();

$user  = require_auth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Method not allowed.', 405);

$b     = get_json_body();
$docId = (int)($b['doc_id'] ?? 0);
if (!$docId) json_error('doc_id required.');

$db   = Database::getInstance();
$stmt = $db->prepare(
    'SELECT gd.*, ge.claimant_id, ge.status
     FROM ge_documents gd JOIN general_expenses ge ON ge.id = gd.ge_id
     WHERE gd.id = ? AND gd.deleted_at IS NULL'
);
$stmt->execute([$docId]);
$doc = $stmt->fetch();
if (!$doc) json_error('Document not found.', 404);
if ($doc['claimant_id'] != $user['id'] && !in_array('SYSTEM_ADMIN', $user['roles'])) {
    json_error('Forbidden.', 403);
}
if (!in_array($doc['status'], ['DRAFT','READY_FOR_DOCUMENTS','QUERIED'])) {
    json_error('Documents cannot be deleted at this GE status.');
}

$db->prepare('UPDATE ge_documents SET deleted_at = NOW() WHERE id = ?')->execute([$docId]);
audit($user['id'], 'DOCUMENT_DELETED', "Document deleted: {$doc['document_type']}", $doc['ge_id']);
json_success('Document deleted.');
