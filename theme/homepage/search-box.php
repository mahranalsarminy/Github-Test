<?php
require_once(__DIR__ . '/../../includes/init.php');

// جلب إعدادات صندوق البحث
$stmt = $pdo->query("SELECT * FROM search_box_settings WHERE id = 1");
$search_settings = $stmt->fetch(PDO::FETCH_ASSOC);

$bg_type = $search_settings['background_type'] ?? 'color';
$bg_value = $search_settings['background_value'] ?? '#4A90E2';
$categories_str = $search_settings['categories'] ?? '';
$categories_array = explode(',', $categories_str);

// جلب اقتراحات البحث الأكثر استخدامًا
$stmt = $pdo->query("SELECT keyword FROM search_suggestions ORDER BY search_count DESC LIMIT 5");
$popular_searches = $stmt->fetchAll(PDO::FETCH_ASSOC);

// تحديد نمط الخلفية
$bg_style = '';
if ($bg_type === 'color') {
    $bg_style = "background-color: $bg_value;";
} elseif ($bg_type === 'image') {
    $bg_style = "background-image: url('$bg_value'); background-size: cover; background-position: center;";
}
?>

<section class="search-hero" style="<?php echo $bg_style; ?>">
    <div class="container mx-auto px-4 py-16 md:py-32">
        <div class="max-w-2xl mx-auto text-center">
            <h1 class="text-4xl md:text-5xl font-bold text-white mb-6 drop-shadow-lg">
                Find Your Perfect Wallpaper
            </h1>
            <p class="text-xl text-white mb-8 drop-shadow-md">
                Explore thousands of high-quality wallpapers for your desktop and mobile devices
            </p>
            
            <!-- صندوق البحث الذكي -->
            <div class="relative max-w-xl mx-auto">
                <div class="flex overflow-hidden rounded-lg shadow-lg">
                    <input type="text" 
                           id="search-input" 
                           class="flex-grow px-6 py-4 text-gray-700 focus:outline-none" 
                           placeholder="Search wallpapers, photos, and designs..." 
                           autocomplete="off"
                           aria-label="Search wallpapers">
                    <button type="submit" 
                            class="bg-blue-500 hover:bg-blue-600 text-white px-6 py-4 transition-colors"
                            aria-label="Search">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
                
                <!-- قائمة الاقتراحات -->
                <div id="search-suggestions" class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 rounded-lg shadow-lg hidden">
                    <div class="py-2">
                        <!-- سيتم ملء هذا القسم ديناميكيًا عبر جافا سكريبت -->
                    </div>
                </div>
                
                <!-- كلمات البحث الشائعة -->
                <?php if (!empty($popular_searches)): ?>
                <div class="mt-4 flex flex-wrap justify-center">
                    <span class="text-white mr-2">Popular:</span>
                    <?php foreach ($popular_searches as $item): ?>
                    <a href="/search.php?q=<?php echo urlencode($item['keyword']); ?>" 
                       class="text-white opacity-80 hover:opacity-100 hover:underline mx-1">
                        <?php echo htmlspecialchars($item['keyword']); ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>