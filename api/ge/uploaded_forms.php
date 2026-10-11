<?php
// GET  /api/ge/uploaded_forms.php        — List uploaded GE forms for current user
// DELETE /api/ge/uploaded_forms.php?delete=1 — Soft-delete one uploaded form
require_once __DIR__ . '/../../config/helpers.php';
set_cors_headers();

$user = require_auth();
$db   = Database::getInstance();

// ── LIST ─────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $db->prepare(
        'SELECT f.id, f.title, f.ge_reference, f.original_name, f.mime_type,
                f.file_size, f.notes, f.uploaded_at,
                d.name AS department_name
         FROM ge_uploaded_forms f
         LEFT JOIN departments d ON d.id = f.department_id
         WHERE f.uploader_id = ? AND f.deleted_at IS NULL
         ORDER BY f.uploaded_at DESC'
    );
    $stmt->execute([$user['id']]);
    $forms = $stmt->fetchAll();
    json_success('OK', ['forms' => $forms]);
}

// ── DELETE (soft) ─────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_GET['delete'])) {
    $b  = get_json_body();
    $id = (int)($b['id'] ?? 0);
    if (!$id) json_error('id required.');

    // Verify ownership
    $chk = $db->prepare('SELECT id FROM ge_uploaded_forms WHERE id = ? AND uploader_id = ? AND deleted_at IS NULL');
    $chk->execute([$id, $user['id']]);
    if (!$chk->fetch()) json_error('Record not found or access denied.', 404);

    $db->prepare('UPDATE ge_uploaded_forms SET deleted_at = NOW() WHERE id = ?')->execute([$id]);
    audit($user['id'], 'GE_FORM_DELETED', "Uploaded GE form $id deleted");
    json_success('Record deleted.');
}

json_error('Method not allowed.', 405);
