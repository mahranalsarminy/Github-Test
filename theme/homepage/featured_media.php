<?php
require_once(__DIR__ . '/../../includes/init.php');

// جلب إعدادات عدد الوسائط المميزة من قاعدة البيانات
$stmt = $pdo->query("SELECT featured_media_count FROM site_settings WHERE id = 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);
$featured_media_count = $settings['featured_media_count'] ?? 10;

// جلب الوسائط المميزة من قاعدة البيانات
$stmt = $pdo->prepare("SELECT m.*, c.name as category_name 
                      FROM media m 
                      LEFT JOIN categories c ON m.category_id = c.id 
                      WHERE m.featured = 1 AND m.status = 1 
                      ORDER BY m.created_at DESC LIMIT ?");
$stmt->execute([$featured_media_count]);
$featured_media = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="featured-media py-16 bg-white dark:bg-gray-900 transition-colors">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-8 text-gray-900 dark:text-white">
            Featured Media
        </h2>
        
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-4">
            <?php foreach ($featured_media as $media): ?>
                <?php
                // تحديد مسار الصورة (محلية أو خارجية)
                $file_path = $media['file_path'];
                $external_url = $media['external_url'];
                $thumbnail_url = $media['thumbnail_url'];

                if ($file_path) {
                    if (strpos($file_path, 'uploads/') === false) {
                        $file_path = 'uploads/' . $file_path;
                    }
                    $image_path = $file_path;
                } elseif ($external_url) {
                    $image_path = $external_url;
                } elseif ($thumbnail_url) {
                    $image_path = $thumbnail_url;
                } else {
                    $image_path = '/assets/images/placeholder.jpg'; // صورة افتراضية
                }
                
                // تحديد نوع الوسائط (فيديو، صورة، إلخ)
                $is_video = strpos($media['file_type'], 'video') !== false;
                ?>
                <a href="/media.php?id=<?php echo $media['id']; ?>" 
                   class="block bg-white dark:bg-gray-700 rounded-lg overflow-hidden shadow-sm hover:shadow-lg transition-all duration-300">
                    <div class="relative" style="aspect-ratio: 9/16;">
                        <img src="<?php echo $image_path; ?>" 
                             alt="<?php echo htmlspecialchars($media['title']); ?>" 
                             class="w-full h-full object-cover">
                        
                        <!-- أيقونة نوع الوسائط -->
                        <?php if ($is_video): ?>
                        <div class="absolute inset-0 flex items-center justify-center">
                            <div class="bg-black bg-opacity-30 rounded-full p-3">
                                <i class="fas fa-play text-white text-xl"></i>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- اسم الفئة -->
                        <?php if (!empty($media['category_name'])): ?>
                        <div class="absolute top-2 left-2">
                            <span class="bg-blue-500 text-white text-xs px-2 py-1 rounded-full">
                                <?php echo htmlspecialchars($media['category_name']); ?>
                            </span>
                        </div>
                        <?php endif; ?>
                        
                        <!-- معلومات الوسائط -->
                        <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-60 p-2">
                            <h3 class="text-white text-sm font-medium truncate">
                                <?php echo htmlspecialchars($media['title']); ?>
                            </h3>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-8">
            <a href="/gallery.php?type=media" 
               class="inline-block bg-blue-500 hover:bg-blue-600 text-white font-medium px-6 py-3 rounded-lg transition-colors">
                Browse All Media
            </a>
        </div>
    </div>
</section>