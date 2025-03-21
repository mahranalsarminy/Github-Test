<?php
// Initialize session if not already started
if (session_status() === PHP_SESSION_INACTIVE) {
    session_start();
}

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update the dark mode setting in the session
    if (isset($_POST['dark_mode'])) {
        $_SESSION['admin_dark_mode'] = $_POST['dark_mode'] === '1';
        echo json_encode(['success' => true]);
        exit;
    }
}

// If not a valid request, return error
echo json_encode(['success' => false, 'message' => 'Invalid request']);