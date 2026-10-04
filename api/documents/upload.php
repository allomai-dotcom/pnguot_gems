<?php
// POST /api/documents/upload.php — Upload a supporting document
require_once __DIR__ . '/../../config/helpers.php';
set_cors_headers();

$user = require_auth();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Method not allowed.', 405);

$geId   = (int)($_POST['ge_id'] ?? 0);
$type   = trim($_POST['document_type'] ?? '');
if (!$geId || !$type) json_error('ge_id and document_type are required.');

$validTypes = [
    'QUOTATION_1','QUOTATION_2','QUOTATION_3','JUSTIFICATION_LETTER',
    'PURCHASE_ORDER','REMITTANCE_ADVICE','INVOICE','DELIVERY',
];
if (!in_array($type, $validTypes)) json_error('Invalid document_type.');

$db   = Database::getInstance();
$stmt = $db->prepare('SELECT * FROM general_expenses WHERE id = ? AND claimant_id = ?');
$stmt->execute([$geId, $user['id']]);
$ge = $stmt->fetch();
if (!$ge) json_error('GE not found or access denied.', 404);

if (!isset($_FILES['document'])) json_error('No file uploaded.');
$file = $_FILES['document'];
if ($file['error'] !== UPLOAD_ERR_OK) json_error('File upload error: ' . $file['error']);
if ($file['size'] > MAX_UPLOAD_SIZE) json_error('File exceeds maximum size of 10 MB.');

// Validate MIME type
$finfo    = new finfo(FILEINFO_MIME_TYPE);
$mimeType = $finfo->file($file['tmp_name']);
if (!in_array($mimeType, ALLOWED_MIME_TYPES)) {
    json_error('Only PDF, JPEG, PNG, and GIF files are accepted.');
}

// Ensure upload dir exists
if (!is_dir(UPLOAD_BASE_PATH)) mkdir(UPLOAD_BASE_PATH, 0750, true);

// UUID-based stored filename
$ext        = pathinfo($file['name'], PATHINFO_EXTENSION);
$storedName = generate_uuid() . ($ext ? ".$ext" : '');
$destPath   = UPLOAD_BASE_PATH . $storedName;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    json_error('Failed to save uploaded file.', 500);
}

$labels = [
    'QUOTATION_1'         => 'Supplier Quotation 1',
    'QUOTATION_2'         => 'Supplier Quotation 2',
    'QUOTATION_3'         => 'Supplier Quotation 3',
    'JUSTIFICATION_LETTER'=> 'Justification Letter',
    'PURCHASE_ORDER'      => 'Purchase Order',
    'REMITTANCE_ADVICE'   => 'Remittance Advice',
    'INVOICE'             => 'Invoice',
    'DELIVERY'            => 'Delivery Docket',
];

// Remove previous document of same type for this GE (soft-delete)
$db->prepare(
    'UPDATE ge_documents SET deleted_at = NOW() WHERE ge_id = ? AND document_type = ? AND deleted_at IS NULL'
)->execute([$geId, $type]);

$insStmt = $db->prepare(
    'INSERT INTO ge_documents (ge_id, document_type, label, original_name, stored_name, mime_type, file_size, uploaded_by)
     VALUES (?,?,?,?,?,?,?,?)'
);
$insStmt->execute([
    $geId, $type, $labels[$type] ?? $type,
    $file['name'], $storedName, $mimeType, $file['size'], $user['id'],
]);
$docId = $db->lastInsertId();

audit($user['id'], 'DOCUMENT_UPLOADED', "Document uploaded: $type", $geId);
json_success('Document uploaded successfully.', ['document_id' => $docId, 'stored_name' => $storedName], 201);
