<?php
require_once __DIR__ . '/../models/Rating.php';

function rate_media($media_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['rating'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Rating is required']);
        return;
    }

    $rating = new Rating();
    $result = $rating->add($media_id, $data['rating']);
    if ($result['success']) {
        echo json_encode(['message' => 'Rating submitted successfully']);
    } else {
        http_response_code(400);
        echo json_encode(['error' => $result['message']]);
    }
}
?>