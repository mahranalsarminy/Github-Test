<?php
// Load environment variables
require_once __DIR__ . '/env.php';
// تعديل المسار لتحميل .env من الجذر
loadEnv(__DIR__ . '/../..' . '/.env');


try {
    // إنشاء الاتصال باستخدام المتغيرات التي تم تحميلها من .env
    $pdo = new PDO(
        "mysql:host=" . getenv('DB_HOST') . ";port=" . getenv('DB_PORT') . ";dbname=" . getenv('DB_NAME'),
        getenv('DB_USER'),
        getenv('DB_PASSWORD'),
        array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'")
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    error_log("Database Connection Error: " . $e->getMessage());
    die("Connection failed: " . $e->getMessage());
}

// Make connection available globally
global $pdo;
