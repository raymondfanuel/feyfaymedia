<?php
/**
 * TinyMCE image upload - FeyFay Media
 * Accepts POST image upload, saves to uploads, returns JSON { location: "url" }
 */
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json');
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || empty($_FILES['file'])) {
    echo json_encode(['error' => 'No file']);
    exit;
}

$file = $_FILES['file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['error' => 'Upload error']);
    exit;
}

$allowed = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
$finfo = new finfo(FILEINFO_MIME_TYPE);
$mime = $finfo->file($file['tmp_name']);
if (!in_array($mime, $allowed)) {
    echo json_encode(['error' => 'Invalid file type']);
    exit;
}

$ext = mime_to_ext($mime);
$filename = 'inline-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
ensure_upload_dir();
$full_path = rtrim(UPLOAD_PATH, '/') . '/' . $filename;
if (!move_uploaded_file($file['tmp_name'], $full_path)) {
    echo json_encode(['error' => 'Save failed']);
    exit;
}

$location = base_url(rtrim(UPLOAD_URL, '/') . '/' . $filename);
echo json_encode(['location' => $location]);
