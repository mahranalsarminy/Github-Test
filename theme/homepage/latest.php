<?php
require_once(__DIR__ . '/../../includes/init.php');

// جلب إعدادات عدد العناصر من قاعدة البيانات
$stmt = $pdo->query("SELECT latest_items_count FROM site_settings WHERE id = 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);
$latest_items_count = $settings['latest_items_count'] ?? 10;

// جلب أحدث الإضافات من قاعدة البيانات
$stmt = $pdo->prepare("SELECT * FROM media WHERE status = 1 ORDER BY created_at DESC LIMIT ?");
$stmt->execute([$latest_items_count]);
$latest_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<section class="latest-additions py-16 bg-white dark:bg-gray-900 transition-colors">
    <div class="container mx-auto px-4">
        <h2 class="text-3xl font-bold text-center mb-8 text-gray-900 dark:text-white">
            Latest Additions
        </h2>
        
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4">
            <?php foreach ($latest_items as $item): ?>
                <?php
                // تحديد مسار الصورة (محلية أو خارجية)
                $image_path = '';
                if (!empty($item['file_path'])) {
                    $image_path = strpos($item['file_path'], 'uploads/') === false 
                                  ? 'uploads/' . $item['file_path'] 
                                  : $item['file_path'];
                } elseif (!empty($item['external_url'])) {
                    $image_path = $item['external_url'];
                } elseif (!empty($item['thumbnail_url'])) {
                    $image_path = $item['thumbnail_url'];
                }
                ?>
                <a href="/media.php?id=<?php echo $item['id']; ?>" 
                   class="block bg-gray-100 dark:bg-gray-800 rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                    <div class="relative" style="aspect-ratio: 9/16;">
                        <img src="<?php echo $image_path; ?>" 
                             alt="<?php echo htmlspecialchars($item['title']); ?>" 
                             class="w-full h-full object-cover">
                        <div class="absolute bottom-0 left-0 right-0 bg-gradient-to-t from-black to-transparent p-3">
                            <h3 class="text-white text-sm font-medium truncate">
                                <?php echo htmlspecialchars($item['title']); ?>
                            </h3>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-8">
            <a href="/gallery.php?sort=newest" 
               class="inline-block bg-blue-500 hover:bg-blue-600 text-white font-medium px-6 py-3 rounded-lg transition-colors">
                View All New Additions
            </a>
        </div>
    </div>
</section>