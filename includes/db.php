<?php
require_once __DIR__ . '/../vendor/autoload.php'; // تأكد من تضمين autoload.php

use Dotenv\Dotenv;

// Load environment variables
$envPath = __DIR__ . '/../';
if (file_exists($envPath . '.env')) {
    $dotenv = Dotenv::createImmutable($envPath); // لا داعي لتكرار Dotenv\Dotenv
    $dotenv->load();
} else {
    die("Error: .env file not found.");
}

// Retrieve database credentials from environment variables
$dbHost = $_ENV['DB_HOST'] ?? '';
$dbPort = $_ENV['DB_PORT'] ?? '3306'; // Default MySQL port
$dbName = $_ENV['DB_NAME'] ?? '';
$dbUser = $_ENV['DB_USER'] ?? '';
$dbPass = $_ENV['DB_PASSWORD'] ?? '';

// Validate required database credentials
if (empty($dbHost) || empty($dbName) || empty($dbUser)) {
    die("Error: Missing required database configuration in .env file.");
}

try {
    // Create a PDO connection
    $dsn = "mysql:host=$dbHost;port=$dbPort;dbname=$dbName;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Enable exceptions for errors
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Fetch results as associative arrays
        PDO::ATTR_EMULATE_PREPARES => false, // Disable emulated prepares for better security
    ];

    // Establish the database connection
    $pdo = new PDO($dsn, $dbUser, $dbPass, $options);

    // Optional: Log successful connection (for debugging purposes)
    // error_log("Database connection established successfully.");
} catch (PDOException $e) {
    // Handle connection errors gracefully
    die("Unable to connect to the database. Error: " . $e->getMessage());
}

// Export the PDO instance for use in other files
return $pdo;
