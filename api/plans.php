<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // Load language file
    $lang = require __DIR__ . '/../lang/api/' . ($_SESSION['language'] ?? 'en') . '.php';

    // Fetch subscription plans
    $plans = db_fetch_all("SELECT name, price, daily_image_limit, daily_video_limit, watermark FROM subscriptions");

    echo json_encode([
        'message' => $lang['plans_fetched'],
        'plans' => $plans
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'An unexpected error occurred: ' . $e->getMessage()]);
}