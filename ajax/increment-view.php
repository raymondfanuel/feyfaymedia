<?php
/**
 * AJAX: Increment post view count - FeyFay Media
 * GET or POST: post_id
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$post_id = (int)($_REQUEST['post_id'] ?? 0);
if ($post_id <= 0) {
    echo json_encode(['success' => false]);
    exit;
}

$stmt = $pdo->prepare("UPDATE posts SET views = views + 1 WHERE id = ? AND " . posts_public_visibility_sql('posts'));
$stmt->execute([$post_id]);
echo json_encode(['success' => true]);
