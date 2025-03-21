<?php
session_start();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['dark_mode'])) {
    $_SESSION['admin_dark_mode'] = $_POST['dark_mode'] === '1';
    echo json_encode(['success' => true]);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
?>
