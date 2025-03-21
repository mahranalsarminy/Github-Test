<?php
// تمكين عرض الأخطاء
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define the project root directory
define('ROOT_DIR', dirname(__DIR__));

// Include the centralized initialization file
require_once ROOT_DIR . '/includes/init.php';

// Ensure only admins can access this page
require_admin();

try {
    // Fetch site statistics
    $stats = [
        'total_users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'total_media' => $pdo->query("SELECT COUNT(*) FROM media")->fetchColumn(),
        'recent_activities' => $pdo->query("SELECT * FROM activities ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC),
    ];
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
    exit;
}
// Fetch data for the dashboard
try {
    $media_count = get_media_count();
    $user_count = get_user_count();
    $subscription_stats = get_subscription_stats();
    $recent_media = get_recent_media(5);
    $recent_users = get_recent_users(5);
} catch (Exception $e) {
    $error_message = "Error fetching data: " . $e->getMessage();
}

include 'theme/header.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang['code']; ?>" dir="<?php echo $lang['dir']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Settings - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        input, textarea {
            display: block !important;
            width: 100% !important;
            padding: 0.5rem !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 0.375rem !important;
            background-color: white !important;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
    </style>
</head>
<div class="flex min-h-screen <'flex-row-reverse' : 'flex-row'; ?>">
    <?php include 'theme/sidebar.php'; ?>
    <div class="main-container p-6 w-full">
        <h2 class="text-3xl font-bold mb-4">Admin Dashboard</h2>

        <!-- Site Statistics -->
        <section class="statistics mb-6">
            <h3 class="text-2xl font-semibold mb-2">Site Statistics</h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full">
                <div class="dark-mode-card p-6 rounded-lg text-center shadow-lg w-full">
                    <h4 class="text-lg font-medium">Total Users</h4>
                    <p class="text-2xl"><?= $stats['total_users'] ?></p>
                </div>
                <div class="dark-mode-card p-6 rounded-lg text-center shadow-lg w-full">
                    <h4 class="text-lg font-medium">Total Media</h4>
                    <p class="text-2xl"><?= $stats['total_media'] ?></p>
                </div>
                <div class="dark-mode-card p-6 rounded-lg text-center shadow-lg w-full">
                    <h4 class="text-lg font-medium">Active Subscriptions</h4>
                    <p class="text-2xl"><?= $stats['total_subscriptions'] ?? '0' ?></p>
                </div>
            </div>
        </section>

        <!-- Recent Activities -->
        <section class="recent-activities mb-6 w-full">
            <h3 class="text-2xl font-semibold mb-2">Recent Activities</h3>
            <ul class="w-full">
                <?php foreach ($stats['recent_activities'] as $activity): ?>
                    <li><?= htmlspecialchars($activity['description']) ?> - <?= htmlspecialchars($activity['created_at']) ?></li>
                <?php endforeach; ?>
            </ul>
        </section>

        <!-- Quick Links -->
        <section class="grid grid-cols-1 md:grid-cols-3 gap-6 w-full">
            <div class="dark-mode-card p-6 rounded-lg text-center shadow-lg w-full">
                <h2 class="text-xl font-bold">Total Media</h2>
                <p class="text-2xl font-semibold"><?= htmlspecialchars($media_count ?? '0'); ?></p>
            </div>
            <div class="dark-mode-card p-6 rounded-lg text-center shadow-lg w-full">
                <h2 class="text-xl font-bold">Total Users</h2>
                <p class="text-2xl font-semibold"><?= htmlspecialchars($user_count ?? '0'); ?></p>
            </div>
            <div class="dark-mode-card p-6 rounded-lg text-center shadow-lg w-full">
                <h2 class="text-xl font-bold">Active Subscriptions</h2>
                <p class="text-2xl font-semibold"><?= htmlspecialchars($subscription_stats['active'] ?? '0'); ?></p>
            </div>
        </section>

        <!-- Quick Access Links -->
        <section class="mt-8 w-full">
            <h2 class="text-2xl font-bold text-center mb-4">Quick Access</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 w-full">
                <a href="/admin/media.php" class="bg-blue-500 text-white p-4 rounded-lg text-center hover:bg-blue-600 transition-colors w-full">
                    Manage Media
                </a>
                <a href="/admin/users.php" class="bg-green-500 text-white p-4 rounded-lg text-center hover:bg-green-600 transition-colors w-full">
                    Manage Users
                </a>
                <a href="/admin/plans.php" class="bg-yellow-500 text-white p-4 rounded-lg text-center hover:bg-yellow-600 transition-colors w-full">
                    Manage Plans
                </a>
                <a href="/admin/settings.php" class="bg-purple-500 text-white p-4 rounded-lg text-center hover:bg-purple-600 transition-colors w-full">
                    Site Settings
                </a>
                <a href="/admin/reports.php" class="bg-red-500 text-white p-4 rounded-lg text-center hover:bg-red-600 transition-colors w-full">
                    Analytics Reports
                </a>
            </div>
        </section>

        <!-- Administrator Comments -->
        <section class="admin-comments mb-6 w-full">
            <h3 class="text-2xl font-semibold mb-2">Administrator Comments</h3>
            <textarea class="w-full p-2 border rounded" rows="5" placeholder="Enter your comments here..."></textarea>
        </section>

        <!-- Developer Information Card -->
        <section class="developer-info mb-6 w-full">
            <div class="bg-white dark:bg-gray-800 p-4 rounded shadow text-center">
                <p>Developed by: Mahran Al-Sarminy</p>
                <p>Website: <a href="https://mahran.online" target="_blank" class="text-blue-500 dark:text-blue-300">Mahran.online</a></p>
                <p>Email: <a href="mailto:contact@mahran.online" class="text-blue-500 dark:text-blue-300">contact@mahran.online</a></p>
                <p>Version: 1.0</p>
                <button class="bg-green-500 text-white py-2 px-4 rounded mt-2" onclick="window.open('http://wallpixapiupdate.mahran.online', '_blank')">Check for Updates</button>
            </div>
        </section>
    </div>
</div>

</div>
<?php include 'theme/footer.php'; ?>