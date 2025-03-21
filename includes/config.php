<?php

// Define the root directory as the parent of the current folder
define('ROOT_DIR', dirname(__DIR__));

// Load environment variables from .env file
$envPath = ROOT_DIR . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue; // Ignore comments
        list($name, $value) = explode('=', $line, 2);
        putenv(trim($name) . '=' . trim($value)); // Load into environment
    }
} else {
    die("Error: .env file not found in " . ROOT_DIR);
}

// General Configuration
date_default_timezone_set('UTC'); // Set timezone (update to your preferred timezone)
error_reporting(E_ALL); // Enable error reporting for debugging
ini_set('display_errors', 1);

// Database Configuration
try {
    $db_host = getenv('DB_HOST') ?: 'localhost';
    $db_name = getenv('DB_NAME') ?: '';
    $db_user = getenv('DB_USER') ?: '';
    $db_password = getenv('DB_PASSWORD') ?: '';

    // Create PDO connection
    $dsn = "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Enable exceptions for errors
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Fetch associative arrays by default
        PDO::ATTR_EMULATE_PREPARES => false, // Disable emulated prepared statements
    ];

    $pdo = new PDO($dsn, $db_user, $db_password, $options);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Language Configuration
$supported_languages = ['en', 'ar']; // Supported languages
$default_language = 'en'; // Default language
$current_language = isset($_SESSION['language']) && in_array($_SESSION['language'], $supported_languages)
    ? $_SESSION['language']
    : $default_language;

// Site Configuration
define('BASE_URL', getenv('BASE_URL') ?: ''); // Base URL of the site
define('JWT_SECRET', getenv('JWT_SECRET') ?: ''); // Secret key for JWT authentication

// Google reCAPTCHA Configuration
define('RECAPTCHA_SITE_KEY', getenv('RECAPTCHA_SITE_KEY') ?: '');
define('RECAPTCHA_SECRET_KEY', getenv('RECAPTCHA_SECRET_KEY') ?: '');

// Payment Gateway Configuration
define('STRIPE_PUBLISHABLE_KEY', getenv('STRIPE_PUBLISHABLE_KEY') ?: '');
define('STRIPE_SECRET_KEY', getenv('STRIPE_SECRET_KEY') ?: '');
define('PAYPAL_CLIENT_ID', getenv('PAYPAL_CLIENT_ID') ?: '');
define('PAYPAL_SECRET', getenv('PAYPAL_SECRET') ?: '');

// Pexels API Configuration
define('PEXELS_API_KEY', getenv('PEXELS_API_KEY') ?: '');

// AdSense Configuration
define('ADSENSE_CODE', getenv('ADSENSE_CODE') ?: '');

// Export global variables
$GLOBALS['pdo'] = $pdo;
$GLOBALS['current_language'] = $current_language;

// Uploads directory
define('UPLOADS_DIR', __DIR__ . '/../uploads');