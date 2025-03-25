<?php
require_once(__DIR__ . '/includes/init.php');
$stmt = $pdo->query("SELECT site_name, site_logo, dark_mode, language, 
                     latest_items_count, featured_wallpapers_count, featured_media_count 
                     FROM site_settings WHERE id = 1");
$site_settings = $stmt->fetch(PDO::FETCH_ASSOC);

// تعيين القيم الافتراضية إذا لم تكن موجودة
$latest_items_count = $site_settings['latest_items_count'] ?? 10;
$featured_wallpapers_count = $site_settings['featured_wallpapers_count'] ?? 10;
$featured_media_count = $site_settings['featured_media_count'] ?? 10;
?>
<!DOCTYPE html>
<html lang="<?php echo $site_settings['language']; ?>" dir="<?php echo $site_settings['language'] === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_settings['site_name']; ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/dark-mode.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Cairo|Roboto&display=swap">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- إضافة الوصف والكلمات المفتاحية من قاعدة البيانات -->
    <meta name="description" content="<?php echo htmlspecialchars($site_settings['site_description'] ?? ''); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($site_settings['site_keywords'] ?? ''); ?>">
</head>
<body class="<?php echo $site_settings['dark_mode'] ? 'dark-mode' : 'light-mode'; ?>">
    <!-- Skip to main content link for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <!-- الطبقة الأولى: الهيدر والخلفية -->
    <?php include 'theme/homepage/header.php'; ?>

    <!-- Main Content -->
    <main id="main-content" role="main">
        <!-- الخلفية مع صندوق البحث الذكي -->
        <?php include 'theme/homepage/search-box.php'; ?>

        <!-- الطبقة الثانية: قسم الفئات -->
        <?php include 'theme/homepage/categories.php'; ?>

        <!-- الطبقة الثالثة: أحدث الإضافات -->
        <?php include 'theme/homepage/latest.php'; ?>

        <!-- الطبقة الرابعة: الخلفيات المميزة -->
        <?php include 'theme/homepage/featured_wallpapers.php'; ?>

        <!-- الطبقة الخامسة: الوسائط المميزة -->
        <?php include 'theme/homepage/featured_media.php'; ?>
    </main>

    <!-- الطبقة السادسة: الفوتر -->
    <?php include 'theme/homepage/footer.php'; ?>

    <!-- زر إمكانية الوصول -->
    <div id="accessibility-toggle" class="accessibility-button" role="button" aria-label="Accessibility options" tabindex="0">
        <i class="fas fa-universal-access"></i>
    </div>

    <!-- قائمة إمكانية الوصول -->
    <?php include 'theme/homepage/accessibility.php'; ?>

    <!-- Scripts -->
    <script src="/assets/js/scripts.js"></script>
    <script src="/assets/js/search.js"></script>
    <script src="/assets/js/accessibility.js"></script>
</body>
</html>