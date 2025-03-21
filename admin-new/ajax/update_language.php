<?php
// Initialize session if not already started
if (session_status() === PHP_SESSION_INACTIVE) {
    session_start();
}

// Check if the request is a POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update the language setting in the session
    if (isset($_POST['language']) && in_array($_POST['language'], ['en', 'ar'])) {
        $_SESSION['admin_language'] = $_POST['language'];
        echo json_encode(['success' => true]);
        exit;
    }
}

// If not a valid request, return error
echo json_encode(['success' => false, 'message' => 'Invalid request']);