<?php
/**
 * admin/login.php - صفحة تسجيل دخول المشرفين
 * 
 * @author Mahran Alsarminy
 * @version 2.1
 * @update 2025-03-17
 */

session_start(); // التأكد من بدء الجلسة

// تضمين ملف التهيئة
require_once '../includes/init.php';

// تعريف المتغيرات
$error = '';
$email = '';

// التحقق من جلسة المشرف
if (!empty($_SESSION['user_id']) && !empty($_SESSION['is_admin']) && $_SESSION['is_admin'] === true) {
    header('Location: index.php', true, 303);
    exit();
}

// معالجة نموذج تسجيل الدخول
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // التحقق من CSRF
        if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
            throw new Exception('جلسة غير صالحة، يرجى المحاولة مرة أخرى');
        }

        // تنظيف المدخلات
        $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';

        // التحقق من المدخلات
        if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('يرجى إدخال بريد إلكتروني صالح');
        }

        if (empty($password)) {
            throw new Exception('يرجى إدخال كلمة المرور');
        }

        // البحث عن المستخدم
        $stmt = $pdo->prepare("
            SELECT id, username, password, role 
            FROM users 
            WHERE email = ? 
            AND role = 'admin' 
            LIMIT 1
        ");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // التحقق من المستخدم وكلمة المرور
        if (!$user || !password_verify($password, $user['password'])) {
            throw new Exception('البريد الإلكتروني أو كلمة المرور غير صحيحة');
        }

        // تنظيف وبدء جلسة جديدة
        session_regenerate_id(true);
        $_SESSION = [];

        // تعيين بيانات الجلسة
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = 'admin';
        $_SESSION['is_admin'] = true;
        $_SESSION['admin_login_time'] = time();

        // تحديث آخر تسجيل دخول
        $stmt = $pdo->prepare("
            UPDATE users 
            SET last_login = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$user['id']]);

        // إعادة التوجيه إلى لوحة التحكم
        header('Location: index.php', true, 303);
        exit();

    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("[Admin Login Error] {$e->getMessage()} | IP: {$_SERVER['REMOTE_ADDR']}");
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>تسجيل دخول المشرفين</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <div>
                <h2 class="mt-6 text-center text-3xl font-extrabold text-gray-900">
                    تسجيل دخول المشرفين
                </h2>
            </div>

            <?php if ($error): ?>
                <div class="rounded-md bg-red-50 p-4">
                    <div class="text-sm text-red-700">
                        <?php echo htmlspecialchars($error); ?>
                    </div>
                </div>
            <?php endif; ?>

            <form class="mt-8 space-y-6" method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                <div class="rounded-md shadow-sm -space-y-px">
                    <div>
                        <input id="email" 
                               name="email" 
                               type="email" 
                               required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
                               placeholder="البريد الإلكتروني"
                               value="<?php echo htmlspecialchars($email); ?>">
                    </div>
                    <div>
                        <input id="password" 
                               name="password" 
                               type="password" 
                               required 
                               class="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 focus:z-10 sm:text-sm" 
                               placeholder="كلمة المرور">
                    </div>
                </div>

                <div>
                    <button type="submit" 
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                        تسجيل الدخول
                    </button>
                </div>
            </form>

            <div class="text-center">
                <a href="../index.php" class="text-sm text-indigo-600 hover:text-indigo-500">
                    العودة إلى الموقع الرئيسي
                </a>
            </div>
        </div>
    </div>
</body>
</html>