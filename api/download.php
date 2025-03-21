<?php
require_once(__DIR__ . '/../includes/init.php');

// Set JSON response headers
header('Content-Type: application/json');

// Verify request method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get JSON body
$json = file_get_contents('php://input');
$data = json_decode($json, true);

if (!$data || !isset($data['token'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid request data']);
    exit;
}

// Validate download token
$token = $data['token'];
if (!isset($_SESSION['download_tokens'][$token])) {
    http_response_code(403);
    echo json_encode(['error' => 'Invalid or expired token']);
    exit;
}

// Get media ID from token
$download_info = $_SESSION['download_tokens'][$token];
$media_id = $download_info['media_id'];
unset($_SESSION['download_tokens'][$token]);

// Get media details
$stmt = $pdo->prepare("SELECT * FROM media WHERE id = ?");
$stmt->execute([$media_id]);
$media = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$media) {
    http_response_code(404);
    echo json_encode(['error' => 'Media not found']);
    exit;
}

// Update download count
$stmt = $pdo->prepare("
    INSERT INTO media_downloads (media_id, user_id, session_id, downloaded_at)
    VALUES (?, ?, ?, NOW())
");
$stmt->execute([
    $media_id,
    $_SESSION['user_id'] ?? null,
    session_id()
]);

// Prepare download URL
$download_url = $media['file_path'] ?? $media['external_url'];

echo json_encode([
    'success' => true,
    'url' => $download_url
]);