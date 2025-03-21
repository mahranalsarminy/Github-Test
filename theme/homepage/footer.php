<?php
require_once(__DIR__ . '/../../includes/init.php');

// جلب إعدادات الفوتر من قاعدة البيانات
$stmt = $pdo->query("SELECT site_name, footer_content, facebook_url, twitter_url, instagram_url, youtube_url, footer_menu_id FROM site_settings WHERE id = 1");
$site_settings = $stmt->fetch(PDO::FETCH_ASSOC);

// جلب روابط قائمة الفوتر
$footer_menu_id = $site_settings['footer_menu_id'] ?? null;
$menu_items = [];

if ($footer_menu_id) {
    $stmt = $pdo->prepare("SELECT name, url FROM menu_items WHERE menu_id = ? ORDER BY sort_order ASC");
    $stmt->execute([$footer_menu_id]);
    $menu_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// استخراج العام الحالي
$current_year = date('Y');
?>

<footer class="bg-gray-900 text-white py-12">
    <div class="container mx-auto px-4">
        <!-- القسم العلوي من الفوتر -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8 mb-8">
            <!-- معلومات الموقع -->
            <div>
                <h3 class="text-xl font-bold mb-4">
                    <?php echo htmlspecialchars($site_settings['site_name'] ?? 'WallPix'); ?>
                </h3>
                <div class="text-gray-400 mb-4">
                    <?php echo $site_settings['footer_content'] ?? 'Premium wallpapers and media for all your devices.'; ?>
                </div>
                
                <!-- وسائل التواصل الاجتماعي -->
                <div class="flex space-x-4 mt-4">
                    <?php if (!empty($site_settings['facebook_url'])): ?>
                    <a href="<?php echo htmlspecialchars($site_settings['facebook_url']); ?>" target="_blank" rel="noopener noreferrer"
                       class="text-gray-400 hover:text-white transition-colors" aria-label="Facebook">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($site_settings['twitter_url'])): ?>
                    <a href="<?php echo htmlspecialchars($site_settings['twitter_url']); ?>" target="_blank" rel="noopener noreferrer"
                       class="text-gray-400 hover:text-white transition-colors" aria-label="Twitter">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($site_settings['instagram_url'])): ?>
                    <a href="<?php echo htmlspecialchars($site_settings['instagram_url']); ?>" target="_blank" rel="noopener noreferrer"
                       class="text-gray-400 hover:text-white transition-colors" aria-label="Instagram">
                        <i class="fab fa-instagram"></i>
                    </a>
                    <?php endif; ?>
                    
                    <?php if (!empty($site_settings['youtube_url'])): ?>
                    <a href="<?php echo htmlspecialchars($site_settings['youtube_url']); ?>" target="_blank" rel="noopener noreferrer"
                       class="text-gray-400 hover:text-white transition-colors" aria-label="YouTube">
                        <i class="fab fa-youtube"></i>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- روابط سريعة -->
            <div>
                <h3 class="text-xl font-bold mb-4">Quick Links</h3>
                <ul class="space-y-2">
                    <li><a href="/pages/about.php" class="text-gray-400 hover:text-white transition-colors">About Us</a></li>
                    <li><a href="/pages/contact.php" class="text-gray-400 hover:text-white transition-colors">Contact Us</a></li>
                    <li><a href="/pages/privacy.php" class="text-gray-400 hover:text-white transition-colors">Privacy Policy</a></li>
                    <li><a href="/pages/terms.php" class="text-gray-400 hover:text-white transition-colors">Terms & Conditions</a></li>
                </ul>
            </div>
            
            <!-- قائمة الفوتر المخصصة -->
            <div>
                <h3 class="text-xl font-bold mb-4">Explore</h3>
                <ul class="space-y-2">
                    <?php foreach ($menu_items as $item): ?>
                    <li>
                        <a href="<?php echo htmlspecialchars($item['url']); ?>" 
                           class="text-gray-400 hover:text-white transition-colors">
                            <?php echo htmlspecialchars($item['name']); ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
        
        <!-- القسم السفلي من الفوتر -->
        <div class="border-t border-gray-800 pt-8 mt-8 flex flex-col md:flex-row justify-between items-center">
            <div class="text-gray-400 mb-4 md:mb-0">
                &copy; <?php echo $current_year; ?> <?php echo htmlspecialchars($site_settings['site_name'] ?? 'WallPix'); ?>. All rights reserved.
            </div>
            
            <!-- رابط العودة للأعلى -->
            <a href="#" id="back-to-top" class="text-gray-400 hover:text-white transition-colors">
                <span>Back to top</span>
                <i class="fas fa-arrow-up ml-1"></i>
            </a>
        </div>
    </div>
</footer>