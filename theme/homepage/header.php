<?php
require_once(__DIR__ . '/../../includes/init.php');

$current_language = $_SESSION['lang'] ?? 'en';
$theme = $_SESSION['theme'] ?? 'light';

// جلب بيانات القائمة العليا
$stmt = $pdo->query("SELECT header_menu_id FROM site_settings WHERE id = 1");
$site_settings = $stmt->fetch(PDO::FETCH_ASSOC);
$header_menu_id = $site_settings['header_menu_id'] ?? null;

$menu_items = [];
if ($header_menu_id) {
    $stmt = $pdo->prepare("SELECT name, url FROM menu_items WHERE menu_id = ? ORDER BY sort_order ASC");
    $stmt->execute([$header_menu_id]);
    $menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// جلب الشعار من قاعدة البيانات
$stmt = $pdo->query("SELECT site_logo FROM site_settings WHERE id = 1");
$logo = $stmt->fetch(PDO::FETCH_ASSOC);
$site_logo = $logo['site_logo'] ?? 'uploads/logo.svg';
?>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link rel="stylesheet" href="/assets/css/styles.css">
<link rel="stylesheet" href="/assets/css/dark-mode.css">
<header class="bg-white dark:bg-gray-900 shadow-sm transition-colors duration-300">
    <div class="container mx-auto px-4 py-3">
        <div class="flex justify-between items-center">
            <!-- الشعار واسم الموقع -->
            <a href="/" class="flex items-center gap-2">
                <img src="uploads/<?php echo $site_logo; ?>" alt="Logo" class="h-10 w-auto">
                <span class="text-2xl font-bold text-gray-800 dark:text-white">WallPix</span>
            </a>

            <!-- القائمة العليا للشاشات الكبيرة -->
            <nav class="hidden md:flex items-center space-x-6">
                <?php foreach ($menu_items as $item): ?>
                <a href="<?php echo htmlspecialchars($item['url']); ?>" 
                   class="text-gray-700 dark:text-gray-200 hover:text-blue-500 dark:hover:text-blue-400 transition-colors">
                    <?php echo htmlspecialchars($item['name']); ?>
                </a>
                <?php endforeach; ?>
            </nav>

            <!-- أزرار الإعدادات -->
            <div class="flex items-center space-x-4">
                <!-- زر تبديل اللغة -->
                <div class="relative group">
                    <button class="text-gray-700 dark:text-gray-200 hover:text-blue-500 dark:hover:text-blue-400"
                            aria-label="Change language">
                        <i class="fas fa-language text-xl"></i>
                    </button>
                    <div class="absolute right-0 mt-2 w-32 bg-white dark:bg-gray-800 rounded shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all z-10">
                        <a href="?lang=en" class="block px-4 py-2 text-gray-800 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700">English</a>
                        <a href="?lang=ar" class="block px-4 py-2 text-gray-800 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700">العربية</a>
                    </div>
                </div>

                <!-- زر تبديل المظهر -->
                <button id="theme-toggle" class="text-gray-700 dark:text-gray-200 hover:text-blue-500 dark:hover:text-blue-400"
                        aria-label="Toggle dark mode">
                    <i class="fas <?php echo ($theme === 'dark') ? 'fa-sun' : 'fa-moon'; ?> text-xl"></i>
                </button>

                <!-- زر تسجيل الدخول -->
                <a href="/login.php" class="text-gray-700 dark:text-gray-200 hover:text-blue-500 dark:hover:text-blue-400"
                   aria-label="Login">
                    <i class="fas fa-user text-xl"></i>
                </a>

                <!-- زر القائمة للجوال -->
                <button id="mobile-menu-toggle" class="md:hidden text-gray-700 dark:text-gray-200 hover:text-blue-500 dark:hover:text-blue-400"
                        aria-label="Open menu">
                    <i class="fas fa-bars text-xl"></i>
                </button>
            </div>
        </div>
    </div>

    <!-- قائمة الجوال -->
    <div id="mobile-menu" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
        <div class="bg-white dark:bg-gray-900 w-64 h-full p-5 transform transition-transform">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white">Menu</h2>
                <button id="close-mobile-menu" class="text-gray-700 dark:text-gray-200">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            <nav class="space-y-4">
                <?php foreach ($menu_items as $item): ?>
                <a href="<?php echo htmlspecialchars($item['url']); ?>" 
                   class="block py-2 text-gray-700 dark:text-gray-200 hover:text-blue-500 dark:hover:text-blue-400 border-b border-gray-200 dark:border-gray-700">
                    <?php echo htmlspecialchars($item['name']); ?>
                </a>
                <?php endforeach; ?>
            </nav>
        </div>
    </div>
</header>