<?php
// Include initialization file
require_once(__DIR__ . '/includes/init.php');

$error = '';
$success = '';

// Get token from URL
$token = filter_input(INPUT_GET, 'token', FILTER_SANITIZE_STRING);

if ($token) {
    // Check if token exists and is valid
    $stmt = $pdo->prepare("SELECT * FROM users WHERE verification_token = ? AND verification_expiry > NOW() AND is_verified = 0");
    $stmt->execute([$token]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        try {
            // Update user as verified
            $stmt = $pdo->prepare("UPDATE users SET is_verified = 1, verification_token = NULL, verified_at = NOW() WHERE id = ?");
            $stmt->execute([$user['id']]);
            
            // Log activity
            $activity_stmt = $pdo->prepare("INSERT INTO activities (user_id, description) VALUES (?, ?)");
            $activity_stmt->execute([$user['id'], 'Email verification completed']);
            
            $success = 'تم التحقق من حسابك بنجاح! يمكنك الآن تسجيل الدخول.';
            
        } catch (PDOException $e) {
            $error = 'حدث خطأ أثناء التحقق من حسابك. يرجى المحاولة مرة أخرى لاحقًا.';
            error_log("Verification error: " . $e->getMessage());
        }
    } else {
        // Check if token is expired
        $stmt = $pdo->prepare("SELECT * FROM users WHERE verification_token = ? AND verification_expiry <= NOW()");
        $stmt->execute([$token]);
        $expired_user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($expired_user) {
            $error = 'انتهت صلاحية رابط التحقق. يرجى طلب رابط جديد.';
        } else {
            // Check if user is already verified
            $stmt = $pdo->prepare("SELECT * FROM users WHERE verification_token = ? AND is_verified = 1");
            $stmt->execute([$token]);
            $verified_user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($verified_user) {
                $success = 'تم التحقق من حسابك بالفعل. يمكنك تسجيل الدخول.';
            } else {
                $error = 'رابط التحقق غير صالح. يرجى التأكد من الرابط أو طلب رابط جديد.';
            }
        }
    }
} else {
    $error = 'رابط التحقق مفقود. يرجى التأكد من الرابط.';
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
    <title>التحقق من البريد الإلكتروني - <?php echo $site_settings['site_name']; ?></title>
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
                <h2 class="mt-4 text-2xl font-extrabold text-gray-900 dark:text-white">التحقق من البريد الإلكتروني</h2>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mt-6" role="alert">
                    <span class="block sm:inline"><?php echo $error; ?></span>
                </div>
                
                <div class="mt-6">
                    <a href="resend_verification.php" class="block w-full text-center py-2 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700">
                        إعادة إرسال رابط التحقق
                    </a>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($success)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mt-6" role="alert">
                    <span class="block sm:inline"><?php echo $success; ?></span>
                </div>
                
                <div class="mt-6">
                    <a href="login.php" class="block w-full text-center py-2 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700">
                        تسجيل الدخول
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'theme/homepage/footer.php'; ?>
    
    <!-- Scripts -->
    <script src="/assets/js/scripts.js"></script>
</body>
</html>