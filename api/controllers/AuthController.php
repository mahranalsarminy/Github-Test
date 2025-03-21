<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../helpers/JWTAuth.php';

function register_user() {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['email']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Email and password are required']);
        return;
    }

    $user = new User();
    $result = $user->register($data['email'], $data['password']);
    if ($result['success']) {
        echo json_encode(['message' => 'User registered successfully']);
    } else {
        http_response_code(400);
        echo json_encode(['error' => $result['message']]);
    }
}

function login_user() {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!isset($data['email']) || !isset($data['password'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Email and password are required']);
        return;
    }

    $user = new User();
    $result = $user->login($data['email'], $data['password']);
    if ($result['success']) {
        $token = generate_jwt(['user_id' => $result['user_id']]);
        echo json_encode(['token' => $token]);
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials']);
    }
}
?>