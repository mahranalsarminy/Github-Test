<?php
// تضمين ملف الإعدادات الأساسي
require_once __DIR__ . '/../includes/config.php';

// بدء الجلسة إذا لم تكن مفعلة
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// التحقق من أن الطلب يتم عبر POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // استقبال البيانات من النموذج
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // التحقق من صحة البريد الإلكتروني
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Please enter a valid email address.";
        header("Location: /auth/signup");
        exit;
    }

    // التحقق من تطابق كلمتي المرور
    if ($password !== $confirm_password) {
        $_SESSION['error_message'] = "Passwords do not match.";
        header("Location: /auth/signup");
        exit;
    }

    // التحقق من طول كلمة المرور
    if (strlen($password) < 8) {
        $_SESSION['error_message'] = "Password must be at least 8 characters long.";
        header("Location: /auth/signup");
        exit;
    }

    // التحقق من وجود البريد الإلكتروني في قاعدة البيانات
    require_once __DIR__ . '/../includes/db.php'; // افترض أن لديك ملف قاعدة بيانات
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $_SESSION['error_message'] = "This email is already registered.";
        header("Location: /auth/signup");
        exit;
    }

    // إنشاء حساب جديد
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (:email, :password, 'Free')");
    $stmt->execute(['email' => $email, 'password' => $hashed_password]);

    // تسجيل الدخول تلقائيًا
    $_SESSION['user_id'] = $pdo->lastInsertId();
    $_SESSION['role'] = 'Free';
    $_SESSION['success_message'] = "Account created successfully! You are now logged in.";
    header("Location: /");
    exit;
} else {
    // إعادة التوجيه إذا لم يكن الطلب عبر POST
    header("Location: /auth/signup");
    exit;
}