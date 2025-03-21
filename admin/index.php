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

// Get statistics from database
try {
    // Total users count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
    $userCount = $stmt->fetch()['count'] ?? 0;
    
    // Total media count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM media");
    $mediaCount = $stmt->fetch()['count'] ?? 0;
    
    // Total categories count
    $stmt = $pdo->query("SELECT COUNT(*) as count FROM categories");
    $categoryCount = $stmt->fetch()['count'] ?? 0;
    
    // Subscription statistics
    $stmt = $pdo->query("SELECT 
        SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
        SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired,
        SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled
        FROM user_subscriptions");
    $subStats = $stmt->fetch();
    
    // Latest users
    $stmt = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC LIMIT 5");
    $latestUsers = $stmt->fetchAll();
    
    // Latest media
    $stmt = $pdo->query("SELECT m.id, m.title, m.file_name, m.thumbnail_url, c.name as category_name 
        FROM media m 
        LEFT JOIN categories c ON m.category_id = c.id 
        ORDER BY m.created_at DESC LIMIT 5");
    $latestMedia = $stmt->fetchAll();
    
    // Recent activities
    $stmt = $pdo->query("SELECT description, created_at FROM activities ORDER BY created_at DESC LIMIT 10");
    $recentActivities = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
}

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

include '../theme/admin/header.php';
include '../theme/admin/slidbar.php';
?>

<!-- Main Content -->
<div class="content-wrapper min-h-screen bg-gray-100 <?php echo $darkMode ? 'dark-mode' : ''; ?>">
    <div class="px-6 py-8">
        <div class="mb-6">
            <h1 class="text-3xl font-bold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                <?php echo $lang['dashboard'] ?? 'Dashboard'; ?>
            </h1>
            <p class="mt-2 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                <?php echo $lang['welcome'] ?? 'Welcome'; ?>, <?php echo htmlspecialchars($currentUser); ?>!
                <span class="ml-2"><?php echo $currentDateTime; ?></span>
            </p>
        </div>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <!-- Total Users -->
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>"><?php echo $lang['total_users'] ?? 'Total Users'; ?></p>
                        <p class="text-3xl font-bold mt-2"><?php echo number_format($userCount); ?></p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full <?php echo $darkMode ? 'bg-blue-900' : ''; ?>">
                        <i class="fas fa-users text-blue-500"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="/admin/users/index.php" class="text-sm text-blue-500 hover:text-blue-600">
                        <?php echo $lang['view_all'] ?? 'View All'; ?> <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
            
            <!-- Total Media -->
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>"><?php echo $lang['total_media'] ?? 'Total Media'; ?></p>
                        <p class="text-3xl font-bold mt-2"><?php echo number_format($mediaCount); ?></p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full <?php echo $darkMode ? 'bg-green-900' : ''; ?>">
                        <i class="fas fa-photo-video text-green-500"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="/admin/media/index.php" class="text-sm text-blue-500 hover:text-blue-600">
                        <?php echo $lang['view_all'] ?? 'View All'; ?> <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
            
            <!-- Total Categories -->
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>"><?php echo $lang['total_categories'] ?? 'Total Categories'; ?></p>
                        <p class="text-3xl font-bold mt-2"><?php echo number_format($categoryCount); ?></p>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full <?php echo $darkMode ? 'bg-purple-900' : ''; ?>">
                        <i class="fas fa-folder text-purple-500"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="/admin/categories/index.php" class="text-sm text-blue-500 hover:text-blue-600">
                        <?php echo $lang['view_all'] ?? 'View All'; ?> <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
            
            <!-- Today's Date -->
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>"><?php echo $lang['current_date'] ?? 'Current Date'; ?></p>
                        <p class="text-xl font-bold mt-2"><?php echo $currentDateTime; ?></p>
                    </div>
                    <div class="bg-yellow-100 p-3 rounded-full <?php echo $darkMode ? 'bg-yellow-900' : ''; ?>">
                        <i class="fas fa-calendar text-yellow-500"></i>
                    </div>
                </div>
                <div class="mt-4">
                    <p class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                        <?php echo date('l, F j, Y'); ?>
                    </p>
                </div>
            </div>
        </div>
        
        <!-- Charts & Tables Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Subscription Statistics -->
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
                <h2 class="text-xl font-bold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>"><?php echo $lang['subscription_stats'] ?? 'Subscription Statistics'; ?></h2>
                
                <div class="grid grid-cols-3 gap-4 mb-6">
                    <div class="text-center">
                        <div class="text-2xl font-bold text-green-500"><?php echo number_format($subStats['active'] ?? 0); ?></div>
                        <div class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>"><?php echo $lang['status_active'] ?? 'Active'; ?></div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-red-500"><?php echo number_format($subStats['expired'] ?? 0); ?></div>
                        <div class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>"><?php echo $lang['status_expired'] ?? 'Expired'; ?></div>
                    </div>
                    <div class="text-center">
                        <div class="text-2xl font-bold text-yellow-500"><?php echo number_format($subStats['cancelled'] ?? 0); ?></div>
                        <div class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>"><?php echo $lang['status_cancelled'] ?? 'Cancelled'; ?></div>
                    </div>
                </div>
                
                <!-- Placeholder for Chart -->
                <div class="h-64 bg-gray-100 rounded-lg flex items-center justify-center <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                    <span class="text-gray-500"><?php echo $lang['subscription_chart'] ?? 'Subscription Chart'; ?></span>
                </div>
            </div>
            
            <!-- Recent Activities -->
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
                <h2 class="text-xl font-bold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>"><?php echo $lang['recent_activities'] ?? 'Recent Activities'; ?></h2>
                
                <div class="space-y-4">
                    <?php if (isset($recentActivities) && count($recentActivities) > 0): ?>
                        <?php foreach ($recentActivities as $activity): ?>
                            <div class="flex items-start">
                                <div class="bg-blue-100 p-2 rounded-full mr-3 <?php echo $darkMode ? 'bg-blue-900' : ''; ?>">
                                    <i class="fas fa-bell text-blue-500 text-sm"></i>
                                </div>
                                <div>
                                    <p class="text-sm <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                                        <?php echo htmlspecialchars($activity['description']); ?>
                                    </p>
                                    <p class="text-xs <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?> mt-1">
                                        <?php echo date('M j, Y H:i', strtotime($activity['created_at'])); ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center py-4 <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                            <?php echo $lang['no_recent_activities'] ?? 'No recent activities found'; ?>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Latest Data Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Latest Media -->
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>"><?php echo $lang['latest_media'] ?? 'Latest Media'; ?></h2>
                    <a href="/admin/media/index.php" class="text-sm text-blue-500 hover:text-blue-600">
                        <?php echo $lang['view_all'] ?? 'View All'; ?> <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-700' : ''; ?>">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                    <?php echo $lang['thumbnail'] ?? 'Thumbnail'; ?>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                    <?php echo $lang['title'] ?? 'Title'; ?>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                    <?php echo $lang['category'] ?? 'Category'; ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-700' : ''; ?>">
                            <?php if (isset($latestMedia) && count($latestMedia) > 0): ?>
                                <?php foreach ($latestMedia as $media): ?>
                                    <tr>
                                        <td class="px-4 py-3 whitespace-nowrap">
                                            <?php if (!empty($media['thumbnail_url'])): ?>
                                                <img src="<?php echo htmlspecialchars($media['thumbnail_url']); ?>" alt="Thumbnail" class="h-12 w-12 object-cover rounded">
                                            <?php else: ?>
                                                <div class="h-12 w-12 bg-gray-200 flex items-center justify-center rounded <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                                                    <i class="fas fa-image text-gray-400"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <a href="/admin/media/edit.php?id=<?php echo $media['id']; ?>" class="text-blue-500 hover:underline">
                                                <?php echo htmlspecialchars($media['title']); ?>
                                            </a>
                                        </td>
                                        <td class="px-4 py-3">
                                            <?php echo htmlspecialchars($media['category_name'] ?? 'Uncategorized'); ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-center <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                                        <?php echo $lang['no_media_found'] ?? 'No media found'; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Latest Users -->
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
                <div class="flex justify-between items-center mb-4">
                    <h2 class="text-xl font-bold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>"><?php echo $lang['latest_users'] ?? 'Latest Users'; ?></h2>
                    <a href="/admin/users/index.php" class="text-sm text-blue-500 hover:text-blue-600">
                        <?php echo $lang['view_all'] ?? 'View All'; ?> <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-700' : ''; ?>">
                        <thead>
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                    <?php echo $lang['username'] ?? 'Username'; ?>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                    <?php echo $lang['email'] ?? 'Email'; ?>
                                </th>
                                <th class="px-4 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                    <?php echo $lang['role'] ?? 'Role'; ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-700' : ''; ?>">
                            <?php if (isset($latestUsers) && count($latestUsers) > 0): ?>
                                <?php foreach ($latestUsers as $user): ?>
                                    <tr>
                                        <td class="px-4 py-3">
                                            <a href="/admin/users/edit.php?id=<?php echo $user['id']; ?>" class="text-blue-500 hover:underline">
                                                <?php echo htmlspecialchars($user['username']); ?>
                                            </a>
                                        </td>
                                        <td class="px-4 py-3">
                                            <?php echo htmlspecialchars($user['email']); ?>
                                        </td>
                                        <td class="px-4 py-3">
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                            <?php 
                                                switch($user['role']) {
                                                    case 'admin': 
                                                        echo $darkMode ? 'bg-red-900 text-red-200' : 'bg-red-100 text-red-800';
                                                        break;
                                                    case 'subscriber': 
                                                        echo $darkMode ? 'bg-green-900 text-green-200' : 'bg-green-100 text-green-800';
                                                        break;
                                                    default: 
                                                        echo $darkMode ? 'bg-gray-700 text-gray-300' : 'bg-gray-100 text-gray-800';
                                                }
                                            ?>">
                                                <?php echo htmlspecialchars($user['role']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="px-4 py-3 text-center <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                                        <?php echo $lang['no_users_found'] ?? 'No users found'; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<?php include '../theme/admin/footer.php'; ?>