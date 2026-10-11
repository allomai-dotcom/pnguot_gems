<?php
require_once __DIR__ . '/../../config/helpers.php';
set_cors_headers();

$user = require_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') json_error('Method not allowed.', 405);

if (empty($_FILES['avatar'])) json_error('No file uploaded.');

$file     = $_FILES['avatar'];
$maxSize  = 2 * 1024 * 1024; // 2MB
$allowed  = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
$mime     = mime_content_type($file['tmp_name']);

if ($file['error'] !== UPLOAD_ERR_OK)  json_error('Upload error.');
if ($file['size'] > $maxSize)          json_error('File too large. Max 2MB.');
if (!in_array($mime, $allowed))        json_error('Only JPG, PNG, GIF, or WEBP allowed.');

// Store avatars in uploads/avatars/ (web-accessible for display)
$avatarDir = rtrim($_SERVER['DOCUMENT_ROOT'], '/') . '/uploads/avatars/';
if (!is_dir($avatarDir)) mkdir($avatarDir, 0755, true);

// Delete old avatar if exists
$db = Database::getInstance();
$old = $db->prepare('SELECT avatar_path FROM users WHERE id = ?');
$old->execute([$user['id']]);
$oldPath = $old->fetchColumn();
if ($oldPath && file_exists($avatarDir . basename($oldPath))) {
    unlink($avatarDir . basename($oldPath));
}

$ext      = $mime === 'image/png' ? 'png' : ($mime === 'image/gif' ? 'gif' : ($mime === 'image/webp' ? 'webp' : 'jpg'));
$filename = 'avatar_' . $user['id'] . '_' . time() . '.' . $ext;
$dest     = $avatarDir . $filename;

if (!move_uploaded_file($file['tmp_name'], $dest)) json_error('Failed to save file.');

$db->prepare('UPDATE users SET avatar_path = ? WHERE id = ?')->execute([$filename, $user['id']]);

audit($user['id'], 'AVATAR_UPLOAD', 'Profile picture updated');

json_success('Avatar updated.', ['avatar_url' => '/uploads/avatars/' . $filename]);
