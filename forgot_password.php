<?php
// Include initialization file
require_once(__DIR__ . '/includes/init.php');

$error = '';
$success = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    
    // Validate email
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'يرجى إدخال بريد إلكتروني صالح';
    } else {
        // Check if email exists
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Generate reset token
            $reset_token = bin2hex(random_bytes(32));
            $reset_expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));
            
            try {
                // Store token in database
                $stmt = $pdo->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE id = ?");
                $stmt->execute([$reset_token, $reset_expiry, $user['id']]);
                
                // Send reset email
                $reset_url = "https://{$_SERVER['HTTP_HOST']}/reset_password.php?token=$reset_token";
                $subject = "إعادة تعيين كلمة المرور في {$site_settings['site_name']}";
                $message = "مرحبًا {$user['username']},\n\n";
                $message .= "لقد تلقينا طلبًا لإعادة تعيين كلمة المرور الخاصة بحسابك. لإعادة تعيين كلمة المرور، انقر على الرابط أدناه:\n\n";
                $message .= "$reset_url\n\n";
                $message .= "هذا الرابط صالح لمدة ساعة واحدة فقط.\n\n";
                $message .= "إذا لم تطلب إعادة تعيين كلمة المرور، يرجى تجاهل هذا البريد الإلكتروني.\n\n";
                $message .= "مع تحيات،\n{$site_settings['site_name']} فريق";
                
                // Send email (this is a placeholder, use your preferred email method)
                mail($email, $subject, $message);
                
                // Log activity
                $activity_stmt = $pdo->prepare("INSERT INTO activities (user_id, description) VALUES (?, ?)");
                $activity_stmt->execute([$user['id'], 'Password reset requested']);
                
                $success = 'تم إرسال تعليمات إعادة تعيين كلمة المرور إلى بريدك الإلكتروني.';
            } catch (PDOException $e) {
                $error = 'حدث خطأ أثناء معالجة طلبك. يرجى المحاولة مرة أخرى لاحقًا.';
                error_log("Password reset error: " . $e->getMessage());
            }
        } else {
            // Don't reveal if email exists or not for security
            $success = 'إذا كان هذا البريد الإلكتروني مسجلاً، فسيتم إرسال تعليمات إعادة تعيين كلمة المرور.';
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
    <title>نسيت كلمة المرور - <?php echo $site_settings['site_name']; ?></title>
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
                <h2 class="mt-4 text-2xl font-extrabold text-gray-900 dark:text-white">نسيت كلمة المرور</h2>
                <p class="mt-2 text-center text-sm text-gray-600 dark:text-gray-400">
                    أدخل بريدك الإلكتروني وسنرسل لك رابطًا لإعادة تعيين كلمة المرور
                </p>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mt-4" role="alert">
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mt-4" role="alert">
                    <span class="block sm:inline"><?php echo $success; ?></span>
                </div>
            <?php endif; ?>

            <form class="mt-8 space-y-6" method="POST" action="">
                <div>
                    <label for="email" class="sr-only">البريد الإلكتروني</label>
                    <input id="email" name="email" type="email" required class="appearance-none rounded-lg relative block w-full px-3 py-2 border border-gray-300 dark:border-gray-600 placeholder-gray-500 dark:placeholder-gray-400 text-gray-900 dark:text-white dark:bg-gray-700 focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10" placeholder="البريد الإلكتروني">
                </div>

                <div>
                    <button type="submit" class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <i class="fas fa-key"></i>
                        </span>
                        إرسال رابط إعادة تعيين كلمة المرور
                    </button>
                </div>
                
                <div class="text-center">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <a href="login.php" class="font-medium text-blue-600 hover:text-blue-500 dark:text-blue-400">
                            <i class="fas fa-arrow-left mr-2"></i> العودة إلى صفحة تسجيل الدخول
                        </a>
                    </p>
                </div>
            </form>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'theme/homepage/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="/assets/js/scripts.js"></script>
</body>
</html>