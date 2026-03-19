<?php
/**
 * AJAX: Submit comment - FeyFay Media
 * POST: post_id, name, email, message
 * Returns JSON: { success, message }
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$post_id = (int)($_POST['post_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$message = trim($_POST['message'] ?? '');

if (!$post_id || !$name || !$email || !$message) {
    echo json_encode(['success' => false, 'message' => 'All fields are required.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email.']);
    exit;
}

// Verify post exists and is visible on public site
$stmt = $pdo->prepare("SELECT id FROM posts WHERE id = ? AND " . posts_public_visibility_sql('posts'));
$stmt->execute([$post_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => 'Article not found.']);
    exit;
}

$stmt = $pdo->prepare("INSERT INTO comments (post_id, name, email, message, status) VALUES (?, ?, ?, ?, 'pending')");
$stmt->execute([$post_id, $name, $email, $message]);
echo json_encode(['success' => true, 'message' => 'Comment submitted. It will appear after approval.']);
