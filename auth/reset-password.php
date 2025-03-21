<?php
// تضمين ملف الهيكل الرئيسي للموقع
require_once __DIR__ . '/../templates/header.php';

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);

    // التحقق من صحة البريد الإلكتروني
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        // التحقق من وجود البريد الإلكتروني في قاعدة البيانات
        require_once __DIR__ . '/../includes/db.php'; // افترض أن لديك ملف قاعدة بيانات
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = :email");
        $stmt->execute(['email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $error_message = "No account found with this email.";
        } else {
            // إرسال رابط إعادة تعيين كلمة المرور عبر البريد الإلكتروني
            $reset_token = bin2hex(random_bytes(16));
            $expiry_time = date('Y-m-d H:i:s', strtotime('+1 hour'));

            $stmt = $pdo->prepare("UPDATE users SET reset_token = :token, reset_token_expiry = :expiry WHERE id = :id");
            $stmt->execute(['token' => $reset_token, 'expiry' => $expiry_time, 'id' => $user['id']]);

            // إرسال البريد الإلكتروني (يمكنك استخدام PHPMailer أو أي مكتبة أخرى)
            $reset_link = "http://pixcodehub.com/auth/reset-password-confirm.php?token=$reset_token";
            $message = "Click the following link to reset your password: $reset_link";

            // هنا يمكنك إضافة كود لإرسال البريد الإلكتروني
            $success_message = "A password reset link has been sent to your email.";
        }
    }
}
?>

<main class="container mx-auto mt-8">
    <h1 class="text-3xl font-bold text-center"><?php echo $lang['reset_password']; ?></h1>
    <?php if (!empty($error_message)): ?>
        <div class="bg-red-200 text-red-800 p-4 mb-4"><?php echo htmlspecialchars($error_message); ?></div>
    <?php endif; ?>
    <?php if (!empty($success_message)): ?>
        <div class="bg-green-200 text-green-800 p-4 mb-4"><?php echo htmlspecialchars($success_message); ?></div>
    <?php endif; ?>

    <form class="max-w-md mx-auto mt-8 p-6 bg-gray-200 rounded-lg" method="POST" action="/auth/reset-password.php">
        <div class="mb-4">
            <label class="block text-gray-700"><?php echo $lang['email']; ?></label>
            <input type="email" name="email" class="w-full p-2 border rounded" required>
        </div>
        <button type="submit" class="w-full bg-yellow-500 text-white p-2 rounded"><?php echo $lang['reset_password']; ?></button>
    </form>
</main>

<?php
// تضمين ملف الهيكل السفلي للموقع
require_once __DIR__ . '/../templates/footer.php';