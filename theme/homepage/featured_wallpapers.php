<?php
require_once(__DIR__ . '/../../includes/init.php');

// جلب إعدادات عدد الخلفيات المميزة من قاعدة البيانات
$stmt = $pdo->query("SELECT featured_wallpapers_count FROM site_settings WHERE id = 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);
$featured_count = $settings['featured_wallpapers_count'] ?? 10;

// جلب الخلفيات المميزة من قاعدة البيانات
$stmt = $pdo->prepare("SELECT * FROM media WHERE featured = 1 AND status = 1 ORDER BY created_at DESC LIMIT ?");
$stmt->execute([$featured_count]);
$featured_wallpapers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="featured-wallpapers py-16 bg-gray-50 dark:bg-gray-800 transition-colors">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-8 text-gray-900 dark:text-white">
            Most Popular Wallpapers
        </h2>
        
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
            <?php foreach ($featured_wallpapers as $wallpaper): ?>
                <?php
                // تحديد مسار الصورة (محلية أو خارجية)
                $file_path = $wallpaper['file_path'];
                $external_url = $wallpaper['external_url'];

                if ($file_path) {
                    if (strpos($file_path, 'uploads/') === false) {
                        $file_path = 'uploads/' . $file_path;
                    }
                    $image_path = $file_path;
                } elseif ($external_url) {
                    $image_path = $external_url;
                } else {
                    $image_path = '/assets/images/placeholder.jpg'; // صورة افتراضية
                }
                ?>
                <a href="/media.php?id=<?php echo $wallpaper['id']; ?>" 
                   class="block bg-white dark:bg-gray-700 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-all transform hover:scale-102">
                    <div class="relative" style="aspect-ratio: 9/16;">
                        <img src="<?php echo $image_path; ?>" 
                             alt="<?php echo htmlspecialchars($wallpaper['title']); ?>" 
                             class="w-full h-full object-cover">
                        
                        <!-- بادج المميز -->
                        <div class="absolute top-2 right-2">
                            <span class="bg-yellow-500 text-xs text-white px-2 py-1 rounded-full">
                                <i class="fas fa-star text-xs mr-1"></i> Featured
                            </span>
                        </div>
                        
                        <!-- اسم الخلفية -->
                        <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-50 p-2">
                            <h3 class="text-white text-sm truncate">
                                <?php echo htmlspecialchars($wallpaper['title']); ?>
                            </h3>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-8">
            <a href="/gallery.php?filter=featured" 
               class="inline-block bg-blue-500 hover:bg-blue-600 text-white font-medium px-6 py-3 rounded-lg transition-colors">
                View All Popular Wallpapers
            </a>
        </div>
    </div>
</section>