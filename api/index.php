<?php
// Initialize API
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/routes.php';

// Handle incoming requests
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];
route_request($uri, $method);
?>