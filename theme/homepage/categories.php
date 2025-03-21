<?php
require_once(__DIR__ . '/../../includes/init.php');

// جلب الفئات من قاعدة البيانات مع ترتيبها
$stmt = $pdo->query("SELECT id, name, icon_url, bg_color, slug, description FROM categories 
                     WHERE is_active = 1 ORDER BY display_order ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="categories-section py-12 bg-gray-50 dark:bg-gray-800 transition-colors">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-8 text-gray-900 dark:text-white">
            Browse Categories
        </h2>
        
        <div class="relative">
            <!-- زر التمرير للخلف (للشاشات المتوسطة والكبيرة) -->
            <button id="scrollLeft" 
                    class="absolute left-0 top-1/2 transform -translate-y-1/2 z-10 bg-white dark:bg-gray-900 bg-opacity-70 dark:bg-opacity-70 rounded-full p-3 shadow-md hidden md:block" 
                    aria-label="Scroll left">
                <i class="fas fa-chevron-left text-gray-800 dark:text-gray-200"></i>
            </button>
            
            <!-- قائمة الفئات مع إمكانية التمرير -->
            <div class="categories-container overflow-x-auto scrollbar-hide">
                <div class="flex space-x-4 py-4 px-2">
                    <?php foreach ($categories as $category): 
                          // تطبيق اللون الافتراضي إذا لم يكن اللون محددًا
                          $bg_color = $category['bg_color'] ?? '#4A90E2'; 
                    ?>
                    <a href="/category.php?slug=<?php echo urlencode($category['slug']); ?>" 
                       class="category-card flex-none transform transition-transform hover:scale-105"
                       style="width: 180px; height: 120px;">
                        <div class="rounded-lg shadow-md h-full flex flex-col items-center justify-center p-4 text-white"
                             style="background-color: <?php echo $bg_color; ?>">
                            <?php if (!empty($category['icon_url'])): ?>
                            <img src="<?php echo htmlspecialchars($category['icon_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($category['name']); ?>" 
                                 class="w-12 h-12 mb-3">
                            <?php else: ?>
                            <i class="fas fa-image w-12 h-12 mb-3 text-3xl flex items-center justify-center"></i>
                            <?php endif; ?>
                            <h3 class="font-medium text-center">
                                <?php echo htmlspecialchars($category['name']); ?>
                            </h3>
                        </div>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- زر التمرير للأمام (للشاشات المتوسطة والكبيرة) -->
            <button id="scrollRight" 
                    class="absolute right-0 top-1/2 transform -translate-y-1/2 z-10 bg-white dark:bg-gray-900 bg-opacity-70 dark:bg-opacity-70 rounded-full p-3 shadow-md hidden md:block" 
                    aria-label="Scroll right">
                <i class="fas fa-chevron-right text-gray-800 dark:text-gray-200"></i>
            </button>
        </div>
    </div>
</section>