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
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/database.php';
require_once __DIR__ . '/../includes/functions.php';

// Load language file
$lang = require __DIR__ . '/../templates/lang/' . ($_SESSION['language'] ?? 'en') . '.php';

// التحقق مما إذا كان المستخدم مسجل الدخول بالفعل
if (isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
    // إذا كان المستخدم مسجل الدخول بالفعل، يتم تحويله مباشرة للصفحة الرئيسية
    header("Location: /");
    exit;
}

// معالجة نموذج تسجيل الحساب
$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // التحقق من صحة البريد الإلكتروني وكلمة المرور
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } elseif ($password !== $confirm_password) {
        $error_message = "Passwords do not match.";
    } elseif (strlen($password) < 8) {
        $error_message = "Password must be at least 8 characters long.";
    } else {
        // التحقق من وجود البريد الإلكتروني في قاعدة البيانات
        require_once __DIR__ . '/../includes/db.php'; // افترض أن لديك ملف قاعدة بيانات
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $error_message = "This email is already registered.";
        } else {
            // إنشاء حساب جديد
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (email, password, role) VALUES (:email, :password, 'Free')");
            $stmt->execute(['email' => $email, 'password' => $hashed_password]);

            // تسجيل الدخول تلقائيًا
            $_SESSION['user_id'] = $pdo->lastInsertId();
            $_SESSION['role'] = 'Free';
            header("Location: /");
            exit;
        }
    }
}

// تضمين ملف الهيكل الرئيسي للموقع
require_once __DIR__ . '/../templates/header.php';
?>

<main class="container mx-auto mt-8">
    <h1 class="text-3xl font-bold text-center"><?php echo $lang['signup']; ?></h1>
    <?php if (!empty($error_message)): ?>
        <div class="bg-red-200 text-red-800 p-4 mb-4"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>

    <form class="max-w-md mx-auto mt-8 p-6 bg-gray-200 rounded-lg" method="POST" action="">
        <div class="mb-4">
            <label class="block text-gray-700"><?php echo $lang['email']; ?></label>
            <input type="email" name="email" class="w-full p-2 border rounded" required>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700"><?php echo $lang['password']; ?></label>
            <input type="password" name="password" class="w-full p-2 border rounded" required>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700"><?php echo $lang['confirm_password']; ?></label>
            <input type="password" name="confirm_password" class="w-full p-2 border rounded" required>
        </div>
        <button type="submit" class="w-full bg-green-500 text-white p-2 rounded"><?php echo $lang['signup']; ?></button>
    </form>
</main>

<?php
// تضمين ملف الهيكل السفلي للموقع
require_once __DIR__ . '/../templates/footer.php';
?>
