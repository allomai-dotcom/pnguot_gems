<?php
// POST /api/ge/upload_form.php — Upload a filled paper GE form for archiving
require_once __DIR__ . '/../../config/helpers.php';
set_cors_headers();

$user = require_auth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Method not allowed.', 405);

$title      = trim($_POST['title']        ?? '');
$reference  = trim($_POST['ge_reference'] ?? '');
$notes      = trim($_POST['notes']        ?? '');
$deptId     = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : ($user['department_id'] ?? null);

if (!$title) json_error('Title / description is required.');

if (!isset($_FILES['ge_form'])) json_error('No file uploaded.');
$file = $_FILES['ge_form'];
if ($file['error'] !== UPLOAD_ERR_OK) json_error('File upload error: ' . $file['error']);
if ($file['size'] > MAX_UPLOAD_SIZE) json_error('File exceeds maximum size of 10 MB.');

// Validate MIME type — accept all common document and image formats
$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);
$allowed  = [
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'application/vnd.ms-excel',
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
    'application/vnd.ms-powerpoint',
    'application/vnd.openxmlformats-officedocument.presentationml.presentation',
    'text/plain',
    'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp', 'image/tiff',
];
if (!in_array($mimeType, $allowed)) {
    json_error('Unsupported file type. Accepted: PDF, Word, Excel, PowerPoint, images, and text files.');
}

// Ensure upload dir exists
$uploadDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/uploads/documents/';
if (!is_dir($uploadDir)) {
    if (!mkdir($uploadDir, 0750, true)) {
        json_error('Upload directory could not be created.', 500);
    }
}

$ext        = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$storedName = generate_uuid() . ($ext ? ".$ext" : '');
$destPath   = $uploadDir . $storedName;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    json_error('Failed to save uploaded file.', 500);
}

$db = Database::getInstance();
try {
    $db->prepare(
        'INSERT INTO ge_uploaded_forms
             (uploader_id, department_id, title, ge_reference, original_name, stored_name, mime_type, file_size, notes)
         VALUES (?,?,?,?,?,?,?,?,?)'
    )->execute([
        $user['id'],
        $deptId ?: null,
        $title,
        $reference ?: null,
        $file['name'],
        $storedName,
        $mimeType,
        $file['size'],
        $notes ?: null,
    ]);
    $docId = (int)$db->lastInsertId();
} catch (PDOException $e) {
    @unlink($destPath);
    error_log('upload_form insert error: ' . $e->getMessage());
    json_error('Failed to save record. Please try again.', 500);
}

audit($user['id'], 'GE_FORM_UPLOADED', "Paper GE form uploaded: $title ($storedName)");
json_success('GE form uploaded successfully.', [
    'id'            => $docId,
    'title'         => $title,
    'original_name' => $file['name'],
    'mime_type'     => $mimeType,
    'file_size'     => $file['size'],
    'uploaded_at'   => date('Y-m-d H:i:s'),
], 201);
