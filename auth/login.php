<?php
ob_start(); // Start output buffering

// تضمين ملف الهيكل الرئيسي للموقع
require_once __DIR__ . '/../templates/header.php';

// التحقق مما إذا كان المستخدم مسجل الدخول بالفعل
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    header("Location: /");
    exit;
}

// معالجة نموذج تسجيل الدخول
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // التحقق من صحة البريد الإلكتروني وكلمة المرور
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        // التحقق من قاعدة البيانات
        require_once __DIR__ . '/../includes/db.php'; // تضمين ملف قاعدة البيانات
        global $pdo; // التأكد من أن $pdo متاح
        if (!isset($pdo)) {
            die("Error: Database connection not established.");
        }

        // البحث عن المستخدم في قاعدة البيانات
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($password, $user['password'])) {
            $error_message = "Invalid email or password.";
        } else {
            // تسجيل الدخول الناجح
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['role'];

            // إعادة التوجيه بناءً على الدور
            if ($user['role'] === 'admin') {
                header("Location: /admin/dashboard.php");
            } else {
                header("Location: /");
            }
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* التأكد من أن الصفحة تملأ الشاشة بالكامل */
        html, body {
            height: 100%;
            margin: 0;
            display: flex;
            flex-direction: column;
        }

        /* جعل main يملأ المساحة المتاحة */
        main {
            flex-grow: 1;
        }
    </style>
</head>
<body>

    <!-- المحتوى الرئيسي -->
    <main class="flex flex-col items-center justify-center container mx-auto p-4">
        <h1 class="text-3xl font-bold text-center"><?php echo $lang['login']; ?></h1>
        
        <?php if (!empty($error_message)): ?>
            <div class="bg-red-200 text-red-800 p-4 mb-4"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>
        
<form class="max-w-xl mx-auto mt-8 p-8 bg-gray-200 rounded-xl shadow-xl" method="POST" action="/login">
    <div class="mb-6">
        <label class="block text-gray-700 font-semibold text-lg"><?php echo $lang['email']; ?></label>
        <input type="email" name="email" class="w-full p-4 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
    </div>
    <div class="mb-6">
        <label class="block text-gray-700 font-semibold text-lg"><?php echo $lang['password']; ?></label>
        <input type="password" name="password" class="w-full p-4 border-2 border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500" required>
    </div>
    <button type="submit" class="w-full bg-blue-600 text-white p-4 rounded-lg font-semibold hover:bg-blue-700 transition duration-300"><?php echo $lang['login']; ?></button>
</form>
    </main>

    <!-- الـ Footer في الأسفل دائمًا -->
    <footer class="bg-gray-800 text-white text-center py-4">
        <?php require_once __DIR__ . '/../templates/footer.php'; ?>
    </footer>

</body>
</html>

<?php
ob_end_flush();
?>
