<?php
// Include the database connection file
require_once __DIR__ . '/includes/db.php';

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
$mediaId = isset($data['media_id']) ? (int)$data['media_id'] : 0;
$rating = isset($data['rating']) ? (int)$data['rating'] : 0;

if ($mediaId > 0 && $rating > 0 && $rating <= 5) {
    // Insert or update rating in the database
    $stmt = $pdo->prepare("INSERT INTO ratings (media_id, user_id, rating) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE rating = ?");
    $stmt->execute([$mediaId, $_SESSION['user_id'], $rating, $rating]);

    echo json_encode(['success' => true, 'message' => 'Rating saved successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
}
?>