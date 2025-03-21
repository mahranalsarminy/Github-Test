<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // Load language file
    $lang = require __DIR__ . '/../lang/api/' . ($_SESSION['language'] ?? 'en') . '.php';

    // Fetch featured images
    $images = db_fetch_all("SELECT id, title, file_path, category FROM media WHERE type = 'image' AND featured = 1 LIMIT 20");

    // Fetch featured videos
    $videos = db_fetch_all("SELECT id, title, file_path, category FROM media WHERE type = 'video' AND featured = 1 LIMIT 10");

    echo json_encode([
        'message' => $lang['featured_media_fetched'],
        'images' => $images,
        'videos' => $videos
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An unexpected error occurred: ' . $e->getMessage()]);
}