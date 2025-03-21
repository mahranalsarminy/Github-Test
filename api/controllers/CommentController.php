<?php
require_once __DIR__ . '/../models/Comment.php';

function add_comment($media_id) {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['comment'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Comment is required']);
        return;
    }

    $comment = new Comment();
    $result = $comment->add($media_id, $data['comment']);
    if ($result['success']) {
        echo json_encode(['message' => 'Comment added successfully']);
    } else {
        http_response_code(400);
        echo json_encode(['error' => $result['message']]);
    }
}
?>