<?php
/**
 * AJAX: Search posts (JSON) - FeyFay Media
 * GET: q (search term)
 */
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

$q = trim($_GET['q'] ?? '');
$results = [];
if (strlen($q) >= 2) {
    $results = search_posts($pdo, $q, 10);
    // Return minimal data for suggestions
    $results = array_map(function ($p) {
        return [
            'id' => (int)$p['id'],
            'title' => $p['title'],
            'slug' => $p['slug'],
            'category_name' => $p['category_name'],
        ];
    }, $results);
}
echo json_encode(['results' => $results]);
