<?php
// تمكين تسجيل الأخطاء (للتطوير فقط)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// بدء الجلسة إذا لم تكن مفعلة
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// تضمين مكتبة Dotenv لتحميل متغيرات البيئة
require_once __DIR__ . '/vendor/autoload.php';

if (file_exists(__DIR__ . '/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} else {
    die("Error: .env file not found.");
}

// تحقق مما إذا كان المستخدم قد اختار لغة جديدة
if (isset($_GET['lang']) && preg_match('/^[a-z]{2}$/', $_GET['lang'])) {
    $_SESSION['language'] = $_GET['lang'];
}

// تحديد اللغة الحالية من الجلسة أو الافتراضية
$current_language = $_SESSION['language'] ?? 'en';

// التأكد من أن قيمة اللغة صحيحة (مثال: en، ar فقط)
if (!in_array($current_language, ['en', 'ar'])) {
    $current_language = 'en';
}

// تحميل إعدادات اللغة
$current_language = $_SESSION['language'] ?? 'en';

// التأكد من أن قيمة اللغة صحيحة (مثال: en، ar، fr فقط)
if (!preg_match('/^[a-z]{2}$/', $current_language)) {
    $current_language = 'en';
}

$lang_file = __DIR__ . "/lang/templates/{$current_language}.php";
if (!file_exists($lang_file)) {
    die("Error: Language file '{$current_language}.php' not found.");
}
$lang = require_once $lang_file;

// الحصول على المسار المطلوب
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$route = trim($request_uri, '/');

// تعريف المسارات والملفات المقابلة لها
$routes = [
    '' => 'home.php', 
    'features' => 'pages/features.php',
    'plans' => 'pages/plans.php',
    'about-us' => 'pages/about.php',
    'login' => 'auth/login.php',
    'signup' => 'auth/signup.php',
    'reset-password' => 'auth/reset-password.php',
    'admin/dashboard' => 'admin/dashboard.php',
    'terms' => 'pages/terms.php',
    'privacy' => 'pages/privacy.php',
    'contact' => 'pages/contact.php',
];

// التحقق مما إذا كان المسار موجودًا
if (isset($routes[$route])) {
    $page_path = __DIR__ . '/' . $routes[$route];

    if (file_exists($page_path)) {
        require_once $page_path;
        exit;
    }
}

// إذا لم يتم العثور على الصفحة، إظهار خطأ 404
http_response_code(404);
echo "<h1>404 - Page Not Found</h1>";
exit;
?>