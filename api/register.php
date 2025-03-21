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
    $name = trim($data['name'] ?? '');
    $email = trim($data['email'] ?? '');
    $password = trim($data['password'] ?? '');

    if (empty($name) || empty($email) || empty($password)) {
        http_response_code(400);
        echo json_encode(['error' => $lang['all_fields_required']]);
        exit;
    }

    // Check if email already exists
    $existing_user = db_fetch_one("SELECT id FROM users WHERE email = :email", ['email' => $email]);
    if ($existing_user) {
        http_response_code(409);
        echo json_encode(['error' => $lang['email_already_registered']]);
        exit;
    }

    // Hash password and insert user
    $password_hash = hash_password($password);
    db_query("INSERT INTO users (name, email, password_hash, role, verified, created_at) VALUES (:name, :email, :password_hash, 'guest', 0, NOW())", [
        'name' => $name,
        'email' => $email,
        'password_hash' => $password_hash
    ]);

    echo json_encode(['message' => $lang['user_registered']]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An unexpected error occurred: ' . $e->getMessage()]);
}