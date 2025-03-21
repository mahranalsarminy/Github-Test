<?php
// تمكين تسجيل الأخطاء لمساعدتك في اكتشاف أي مشكلات
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// بدء الجلسة إذا لم تكن مفعلة
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// تحديد المسار الصحيح لملف db.php
$path = $_SERVER['DOCUMENT_ROOT'] . '/includes/db.php';

if (!file_exists($path)) {
    die("Error: File not found - " . $path);
}

// تضمين ملف قاعدة البيانات
require_once $path;

// التحقق من أن الطلب يتم عبر POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // استقبال البيانات من النموذج
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    // التحقق من صحة البريد الإلكتروني
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Please enter a valid email address.";
        header("Location: /auth/login");
        exit;
    }

    try {
        // الاتصال بقاعدة البيانات والتحقق من المستخدم
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            $_SESSION['error_message'] = "Invalid email or password.";
            header("Location: /auth/login");
            exit;
        }

        // تسجيل الدخول الناجح
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['success_message'] = "Login successful!";
        header("Location: /");
        exit;
    } catch (Exception $e) {
        // تسجيل الخطأ في ملف السجل
        error_log("Database Error: " . $e->getMessage());
        die("An error occurred while processing your request.");
    }
} else {
    // إعادة التوجيه إذا لم يكن الطلب عبر POST
    header("Location: /auth/login");
    exit;
}
