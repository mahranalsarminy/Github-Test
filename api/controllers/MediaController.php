<?php
require_once __DIR__ . '/../models/Media.php';

function get_featured_media() {
    $media = new Media();
    $featured = $media->get_featured();
    echo json_encode(['data' => $featured]);
}

function search_media() {
    $query = $_GET['query'] ?? '';
    if (empty($query)) {
        http_response_code(400);
        echo json_encode(['error' => 'Search query is required']);
        return;
    }

    $media = new Media();
    $results = $media->search($query);
    echo json_encode(['data' => $results]);
}

function get_media_details($id) {
    $media = new Media();
    $details = $media->get_by_id($id);
    if ($details) {
        echo json_encode(['data' => $details]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Media not found']);
    }
}
?>