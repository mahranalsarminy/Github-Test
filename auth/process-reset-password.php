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
    $token = trim($_POST['token']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // التحقق من صحة رمز إعادة التعيين
    if (empty($token)) {
        $_SESSION['error_message'] = "Invalid reset token.";
        header("Location: /auth/reset-password-confirm?token=$token");
        exit;
    }

    // التحقق من تطابق كلمتي المرور
    if ($password !== $confirm_password) {
        $_SESSION['error_message'] = "Passwords do not match.";
        header("Location: /auth/reset-password-confirm?token=$token");
        exit;
    }

    // التحقق من طول كلمة المرور
    if (strlen($password) < 8) {
        $_SESSION['error_message'] = "Password must be at least 8 characters long.";
        header("Location: /auth/reset-password-confirm?token=$token");
        exit;
    }

    // التحقق من قاعدة البيانات
    require_once __DIR__ . '/../includes/db.php'; // افترض أن لديك ملف قاعدة بيانات
    $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = :token AND reset_token_expiry > NOW()");
    $stmt->execute(['token' => $token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        $_SESSION['error_message'] = "Invalid or expired reset token.";
        header("Location: /auth/reset-password");
        exit;
    }

    // تحديث كلمة المرور
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE users SET password = :password, reset_token = NULL, reset_token_expiry = NULL WHERE id = :id");
    $stmt->execute(['password' => $hashed_password, 'id' => $user['id']]);

    // إرسال رسالة نجاح
    $_SESSION['success_message'] = "Your password has been reset successfully!";
    header("Location: /auth/login");
    exit;
} else {
    // إعادة التوجيه إذا لم يكن الطلب عبر POST
    header("Location: /auth/reset-password");
    exit;
}