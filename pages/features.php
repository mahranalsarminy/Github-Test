<?php
// تحديد المسار الرئيسي
define('ROOT_DIR', dirname(__DIR__));

// تضمين الملفات الأساسية
require_once ROOT_DIR . '/includes/init.php';
require_once ROOT_DIR . '/templates/header.php';

// جلب المميزات النشطة من قاعدة البيانات
try {
    $stmt = $pdo->query("SELECT * FROM features WHERE is_active = 1 ORDER BY sort_order ASC");
    $features = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $features = [];
    error_log("Database Error: " . $e->getMessage());
}
?>

<!-- Main Content -->
<main class="min-h-screen flex flex-col justify-between">
    <section class="container mx-auto mt-8 px-4">
        <h1 class="text-3xl font-semibold text-center text-gray-800 mb-8">
            <?php echo isset($lang['features']) ? $lang['features'] : 'Features'; ?>
        </h1>

        <!-- Features Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php if (!empty($features)): ?>
                <?php foreach ($features as $feature): ?>
                    <div class="bg-white rounded-lg shadow-lg overflow-hidden transform transition duration-300 hover:scale-105">
                        <!-- Feature Image -->
                        <?php if (!empty($feature['image_url'])): ?>
                            <div class="relative h-48 flex items-center justify-center overflow-hidden">
                                <img src="<?php echo htmlspecialchars($feature['image_url']); ?>"
                                     alt="<?php echo htmlspecialchars($feature['title']); ?>"
                                     class="max-w-[60px] max-h-[60px] object-contain transition-transform duration-300 hover:scale-110">
                            </div>
                        <?php endif; ?>

                        <!-- Feature Content -->
                        <div class="p-6">
                            <h3 class="text-xl font-semibold text-gray-800 mb-4 text-center">
                                <?php echo htmlspecialchars($feature['title']); ?>
                            </h3>

                            <div class="prose prose-sm max-w-none text-gray-600">
                                <?php echo $feature['description']; // Allows HTML content ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <!-- Fallback message when no features are available -->
                <div class="col-span-full text-center text-gray-600">
                    <p>Features will be available soon.</p>
                </div>
            <?php endif; ?>
        </div>
    </section>
</main>

    <!-- Footer -->
    <div class="mt-16">
        <?php require_once ROOT_DIR . '/templates/footer.php'; ?>
    </div>
