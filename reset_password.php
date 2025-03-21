<?php
// Include initialization file
require_once(__DIR__ . '/includes/init.php');

$error = '';
$success = '';

// Get token from URL
$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);

// Check if token exists
if (!$token) {
    $error = 'رابط إعادة تعيين كلمة المرور غير صالح أو منتهي الصلاحية.';
} else {
    // Check if token is valid
    $stmt = $pdo->prepare("SELECT * FROM users WHERE reset_token = ? AND reset_expiry > NOW()");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        $error = 'رابط إعادة تعيين كلمة المرور غير صالح أو منتهي الصلاحية.';
    } else {
        // If form is submitted
        if ($_SERVER['REQUEST_METHOD'] == 'POST') {
            $password = $_POST['password'];
            $confirm_password = $_POST['confirm_password'];
            
            // Validate password
            if (empty($password) || strlen($password) < 8) {
                $error = 'يجب أن تكون كلمة المرور 8 أحرف على الأقل';
            } elseif ($password !== $confirm_password) {
                $error = 'كلمات المرور غير متطابقة';
            } else {
                try {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Update password and remove reset token
                    $stmt = $pdo->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL, updated_at = NOW() WHERE id = ?");
                    $stmt->execute([$hashed_password, $user['id']]);
                    
                    // Log activity
                    $activity_stmt = $pdo->prepare("INSERT INTO activities (user_id, description) VALUES (?, ?)");
                    $activity_stmt->execute([$user['id'], 'Password was reset successfully']);
                    
                    $success = 'تم تحديث كلمة المرور بنجاح! يمكنك الآن تسجيل الدخول باستخدام كلمة المرور الجديدة.';
                } catch (PDOException $e) {
                    $error = 'حدث خطأ أثناء تحديث كلمة المرور. يرجى المحاولة مرة أخرى لاحقًا.';
                    error_log("Password reset error: " . $e->getMessage());
                }
            }
        }
    }
}

// Get site settings
$stmt = $pdo->query("SELECT site_name, site_logo, dark_mode, language FROM site_settings WHERE id = 1");
$site_settings = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?php echo $site_settings['language']; ?>" dir="<?php echo $site_settings['language'] === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إعادة تعيين كلمة المرور - <?php echo $site_settings['site_name']; ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/dark-mode.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Cairo|Roboto&display=swap">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Tailwind CSS via CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="<?php echo $site_settings['dark_mode'] ? 'dark-mode' : 'light-mode'; ?> bg-gray-100 dark:bg-gray-900">
    <!-- Header -->
    <?php include 'theme/homepage/header.php'; ?>

    <div class="min-h-screen flex justify-center items-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full bg-white dark:bg-gray-800 rounded-lg shadow-md p-8">
            <div class="text-center">
                <?php if (!empty($site_settings['site_logo'])): ?>
                    <img class="mx-auto h-16 w-auto" src="<?php echo $site_settings['site_logo']; ?>" alt="<?php echo $site_settings['site_name']; ?> Logo">
                <?php else: ?>
                    <h2 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo $site_settings['site_name']; ?></h2>
                <?php endif; ?>
                <h2 class="mt-4 text-2xl font-extrabold text-gray-900 dark:text-white">إعادة تعيين كلمة المرور</h2>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mt-4" role="alert">
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
                
                <?php if (strpos($error, 'غير صالح') !== false || strpos($error, 'منتهي') !== false): ?>
                    <div class="mt-6">
                        <a href="forgot_password.php" class="block w-full text-center py-2 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700">
                            طلب رابط جديد لإعادة تعيين كلمة المرور
                        </a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mt-4" role="alert">
                    <span class="block sm:inline"><?php echo $success; ?></span>
                </div>
                
                <div class="mt-6">
                    <a href="login.php" class="block w-full text-center py-2 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700">
                        تسجيل الدخول
                    </a>
                </div>
            <?php else: ?>
                <?php if (empty($error) || (strpos($error, 'غير صالح') === false && strpos($error, 'منتهي') === false)): ?>
                    <form class="mt-8 space-y-6" method="POST" action="">
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">كلمة المرور الجديدة</label>
                            <input id="password" name="password" type="password" required class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white dark:bg-gray-700 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10" placeholder="كلمة المرور الجديدة (8 أحرف على الأقل)">
                            <div id="password-strength" class="h-2 mt-1 rounded"></div>
                            <p id="password-feedback" class="text-xs text-gray-500 dark:text-gray-400 mt-1"></p>
                        </div>
                        
                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">تأكيد كلمة المرور</label>
                            <input id="confirm_password" name="confirm_password" type="password" required class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white dark:bg-gray-700 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10" placeholder="تأكيد كلمة المرور الجديدة">
                        </div>

                        <div>
                            <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                                <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                    <i class="fas fa-key"></i>
                                </span>
                                إعادة تعيين كلمة المرور
                            </button>
                        </div>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'theme/homepage/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="/assets/js/scripts.js"></script>
    <script>
    // Password strength validation
    document.getElementById('password').addEventListener('input', function() {
        const password = this.value;
        const strengthBar = document.getElementById('password-strength');
        const feedback = document.getElementById('password-feedback');
        
        // Check password strength
        const hasLowerCase = /[a-z]/.test(password);
        const hasUpperCase = /[A-Z]/.test(password);
        const hasNumber = /\d/.test(password);
        const hasSpecialChar = /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password);
        const isLongEnough = password.length >= 8;
        
        let strength = 0;
        if (hasLowerCase) strength += 1;
        if (hasUpperCase) strength += 1;
        if (hasNumber) strength += 1;
        if (hasSpecialChar) strength += 1;
        if (isLongEnough) strength += 1;
        
        // Update UI based on strength
        let strengthText = '';
        let strengthClass = '';
        
        switch(strength) {
            case 0:
            case 1:
                strengthText = 'ضعيفة جداً';
                strengthClass = 'bg-red-500';
                break;
            case 2:
                strengthText = 'ضعيفة';
                strengthClass = 'bg-orange-500';
                break;
            case 3:
                strengthText = 'متوسطة';
                strengthClass = 'bg-yellow-500';
                break;
            case 4:
                strengthText = 'قوية';
                strengthClass = 'bg-blue-500';
                break;
            case 5:
                strengthText = 'قوية جداً';
                strengthClass = 'bg-green-500';
                break;
        }
        
        // Update the strength indicator
        if (password.length > 0) {
            feedback.textContent = `قوة كلمة المرور: ${strengthText}`;
            strengthBar.className = `h-2 mt-1 rounded ${strengthClass}`;
            strengthBar.style.width = `${(strength / 5) * 100}%`;
        } else {
            feedback.textContent = '';
            strengthBar.style.width = '0';
        }
    });
    </script>
</body>
</html>