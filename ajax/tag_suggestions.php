<?php
/**
 * Tag Suggestions AJAX Endpoint
 *
 * @package WallPix
 * @version 1.0.0
 */

// Include required files
require_once __DIR__ . '/../includes/init.php';

// Set content type to JSON
header('Content-Type: application/json');

// Get search query
$query = trim($_GET['q'] ?? '');

// Return empty array if query is too short
if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

try {
    // Search for matching tags
    $stmt = $pdo->prepare("
        SELECT id, name, COUNT(mt.media_id) as count
        FROM tags t
        LEFT JOIN media_tags mt ON t.id = mt.tag_id
        WHERE t.name LIKE :query
        GROUP BY t.id
        ORDER BY count DESC, t.name ASC
        LIMIT 10
    ");
    $stmt->execute([':query' => '%' . $query . '%']);
    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($tags);
} catch (Exception $e) {
    // Return empty array on error
    echo json_encode([]);
}