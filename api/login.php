<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // Load language file
    $lang = require __DIR__ . '/../lang/api/' . ($_SESSION['language'] ?? 'en') . '.php';

    // Validate input
    $data = json_decode(file_get_contents('php://input'), true);
    $email = trim($data['email'] ?? '');
    $password = trim($data['password'] ?? '');

    if (empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => $lang['invalid_credentials']]);
        exit;
    }

    // Authenticate user
    $user = db_fetch_one("SELECT * FROM users WHERE email = :email", ['email' => $email]);
    if (!$user || !verify_password($password, $user['password_hash'])) {
        http_response_code(401);
        echo json_encode(['error' => $lang['invalid_credentials']]);
        exit;
    }

    // Generate JWT token
    $payload = [
        'user_id' => $user['id'],
        'role' => $user['role'],
        'exp' => time() + 3600 // Token expires in 1 hour
    ];
    $token = generate_jwt($payload);

    echo json_encode(['message' => $lang['login_successful'], 'token' => $token]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An unexpected error occurred: ' . $e->getMessage()]);
}