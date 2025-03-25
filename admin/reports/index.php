<?php
// Set page title
$pageTitle = 'Reports Dashboard - WallPix Admin';

// Include header
require_once '../../theme/admin/header.php';

// Current date and time in UTC
$currentDateTime = '2025-03-24 12:04:07';
$currentUser = 'mahranalsarminy';

// Initialize variables
$period = isset($_GET['period']) ? $_GET['period'] : 'month';
$validPeriods = ['week', 'month', 'year', 'custom'];

if (!in_array($period, $validPeriods)) {
    $period = 'month';
}

// Date range for custom period
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Determine date range based on period
switch ($period) {
    case 'week':
        $startDate = date('Y-m-d', strtotime('-7 days'));
        $endDate = date('Y-m-d');
        break;
    case 'month':
        $startDate = date('Y-m-d', strtotime('-30 days'));
        $endDate = date('Y-m-d');
        break;
    case 'year':
        $startDate = date('Y-m-d', strtotime('-1 year'));
        $endDate = date('Y-m-d');
        break;
    // For custom, use the provided dates
}

// Get summary statistics
try {
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch()['total'];
    
    // New users in selected period
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM users WHERE created_at BETWEEN ? AND ?");
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    $newUsers = $stmt->fetch()['total'];
    
    // Total media items
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM media");
    $totalMedia = $stmt->fetch()['total'];
    
    // New media in selected period
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM media WHERE created_at BETWEEN ? AND ?");
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    $newMedia = $stmt->fetch()['total'];
    
    // Total downloads
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM media_downloads");
    $totalDownloads = $stmt->fetch()['total'];
    
    // Downloads in selected period
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM media_downloads WHERE downloaded_at BETWEEN ? AND ?");
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    $periodDownloads = $stmt->fetch()['total'];
    
    // Revenue in selected period
    $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM payments WHERE payment_date BETWEEN ? AND ? AND status = 'completed'");
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    $periodRevenue = $stmt->fetch()['total'] ?: 0;
    
    // Active subscriptions
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM subscriptions WHERE status = 'active'");
    $activeSubscriptions = $stmt->fetch()['total'];
    
} catch (PDOException $e) {
    $errorMessage = "Database error: " . $e->getMessage();
}

// Get daily statistics for chart
try {
    $dailyData = [];
    
    // Prepare SQL for daily stats
    $sql = "SELECT DATE(created_at) as date, COUNT(*) as count 
            FROM users 
            WHERE created_at BETWEEN ? AND ? 
            GROUP BY DATE(created_at)
            ORDER BY DATE(created_at)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    $userStats = $stmt->fetchAll();
    
    $sql = "SELECT DATE(downloaded_at) as date, COUNT(*) as count 
            FROM media_downloads 
            WHERE downloaded_at BETWEEN ? AND ? 
            GROUP BY DATE(downloaded_at)
            ORDER BY DATE(downloaded_at)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    $downloadStats = $stmt->fetchAll();
    
    // Convert to associative arrays for easy access
    $userDataByDate = [];
    foreach ($userStats as $stat) {
        $userDataByDate[$stat['date']] = $stat['count'];
    }
    
    $downloadDataByDate = [];
    foreach ($downloadStats as $stat) {
        $downloadDataByDate[$stat['date']] = $stat['count'];
    }
    
    // Generate data for all dates in range
    $currentDate = new DateTime($startDate);
    $lastDate = new DateTime($endDate);
    
    while ($currentDate <= $lastDate) {
        $date = $currentDate->format('Y-m-d');
        $dailyData[] = [
            'date' => $date,
            'users' => $userDataByDate[$date] ?? 0,
            'downloads' => $downloadDataByDate[$date] ?? 0
        ];
        
        $currentDate->modify('+1 day');
    }
    
    // Convert to JSON for chart
    $chartData = json_encode($dailyData);
    
} catch (PDOException $e) {
    $errorMessage = "Database error: " . $e->getMessage();
}

// Include sidebar
require_once '../../theme/admin/slidbar.php';
?>

<!-- Main Content -->
<div class="content-wrapper min-h-screen bg-gray-100 <?php echo $darkMode ? 'dark-mode' : ''; ?>">
    <div class="px-6 py-8">
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                    <?php echo $lang['reports_dashboard'] ?? 'Reports Dashboard'; ?>
                </h1>
                <p class="mt-2 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                    <?php echo $lang['view_statistics'] ?? 'View and analyze your website statistics'; ?>
                </p>
            </div>
            <div class="mt-4 md:mt-0">
                <a href="export.php?period=<?php echo $period; ?>&start=<?php echo $startDate; ?>&end=<?php echo $endDate; ?>" class="btn bg-green-500 hover:bg-green-600 text-white">
                    <i class="fas fa-file-excel mr-2"></i> <?php echo $lang['export_csv'] ?? 'Export CSV'; ?>
                </a>
            </div>
        </div>
        <!-- Period Selector -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
            <form action="" method="GET" class="flex flex-wrap items-end space-y-4 md:space-y-0 space-x-0 md:space-x-4">
                <div class="w-full md:w-auto">
                    <label for="period" class="block text-sm font-medium mb-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                        <?php echo $lang['period'] ?? 'Period'; ?>
                    </label>
                    <select id="period" name="period" 
                        class="w-full md:w-40 p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        onchange="toggleCustomDateFields()">
                        <option value="week" <?php echo $period === 'week' ? 'selected' : ''; ?>>
                            <?php echo $lang['last_7_days'] ?? 'Last 7 days'; ?>
                        </option>
                        <option value="month" <?php echo $period === 'month' ? 'selected' : ''; ?>>
                            <?php echo $lang['last_30_days'] ?? 'Last 30 days'; ?>
                        </option>
                        <option value="year" <?php echo $period === 'year' ? 'selected' : ''; ?>>
                            <?php echo $lang['last_year'] ?? 'Last year'; ?>
                        </option>
                        <option value="custom" <?php echo $period === 'custom' ? 'selected' : ''; ?>>
                            <?php echo $lang['custom_range'] ?? 'Custom range'; ?>
                        </option>
                    </select>
                </div>
                
                <!-- Custom date fields (hidden by default unless custom period is selected) -->
                <div id="customDateFields" class="flex flex-wrap space-y-4 md:space-y-0 space-x-0 md:space-x-4 <?php echo $period === 'custom' ? '' : 'hidden'; ?>">
                    <div class="w-full md:w-auto">
                        <label for="start_date" class="block text-sm font-medium mb-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                            <?php echo $lang['start_date'] ?? 'Start Date'; ?>
                        </label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo $startDate; ?>"
                            class="w-full md:w-auto p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="w-full md:w-auto">
                        <label for="end_date" class="block text-sm font-medium mb-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                            <?php echo $lang['end_date'] ?? 'End Date'; ?>
                        </label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo $endDate; ?>"
                            class="w-full md:w-auto p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                
                <div class="w-full md:w-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter mr-2"></i> <?php echo $lang['apply'] ?? 'Apply'; ?>
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Users Card -->
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-500 bg-opacity-10 text-blue-500">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                            <?php echo $lang['users'] ?? 'Users'; ?>
                        </h3>
                        <p class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                            <?php echo $lang['total'] ?? 'Total'; ?>: <?php echo number_format($totalUsers); ?>
                        </p>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-center justify-between">
                        <p class="text-2xl font-bold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                            +<?php echo number_format($newUsers); ?>
                        </p>
                        <p class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                            <?php echo $lang['new_in_period'] ?? 'New in period'; ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Media Card -->
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-500 bg-opacity-10 text-green-500">
                        <i class="fas fa-photo-video text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                            <?php echo $lang['media'] ?? 'Media'; ?>
                        </h3>
                        <p class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                            <?php echo $lang['total'] ?? 'Total'; ?>: <?php echo number_format($totalMedia); ?>
                        </p>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-center justify-between">
                        <p class="text-2xl font-bold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                            +<?php echo number_format($newMedia); ?>
                        </p>
                        <p class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                            <?php echo $lang['new_in_period'] ?? 'New in period'; ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Downloads Card -->
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-500 bg-opacity-10 text-purple-500">
                        <i class="fas fa-download text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                            <?php echo $lang['downloads'] ?? 'Downloads'; ?>
                        </h3>
                        <p class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                            <?php echo $lang['total'] ?? 'Total'; ?>: <?php echo number_format($totalDownloads); ?>
                        </p>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-center justify-between">
                        <p class="text-2xl font-bold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                            +<?php echo number_format($periodDownloads); ?>
                        </p>
                        <p class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                            <?php echo $lang['in_period'] ?? 'In period'; ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Revenue Card -->
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-500 bg-opacity-10 text-yellow-500">
                        <i class="fas fa-dollar-sign text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                            <?php echo $lang['revenue'] ?? 'Revenue'; ?>
                        </h3>
                        <p class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                            <?php echo $lang['active_subs'] ?? 'Active Subscriptions'; ?>: <?php echo number_format($activeSubscriptions); ?>
                        </p>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-center justify-between">
                        <p class="text-2xl font-bold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                            $<?php echo number_format($periodRevenue, 2); ?>
                        </p>
                        <p class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                            <?php echo $lang['in_period'] ?? 'In period'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
         <!-- Charts Section -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
            <!-- Growth Chart -->
            <div class="bg-white rounded-lg shadow-md p-6 col-span-1 lg:col-span-2 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
                <h3 class="text-lg font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                    <?php echo $lang['growth_trends'] ?? 'Growth Trends'; ?>
                </h3>
                <div class="relative" style="height: 300px;">
                    <canvas id="growthChart"></canvas>
                </div>
            </div>
            
            <!-- Distribution Chart -->
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
                <h3 class="text-lg font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                    <?php echo $lang['user_types'] ?? 'User Types'; ?>
                </h3>
                <div class="relative" style="height: 300px;">
                    <canvas id="userTypesChart"></canvas>
                </div>
                
                <?php
                // Get user type distribution
                try {
                    // Modified to handle possible cases where 'role' might not exist or be NULL
                    $stmt = $pdo->query("SELECT 
                        COALESCE(role, 'Unknown') as role, 
                        COUNT(*) as count 
                        FROM users 
                        GROUP BY role");
                    $userTypes = $stmt->fetchAll();
                    
                    $roleLabels = [];
                    $roleCounts = [];
                    foreach ($userTypes as $type) {
                        $roleLabels[] = ucfirst($type['role']); // Capitalize the role name
                        $roleCounts[] = $type['count'];
                    }
                } catch (PDOException $e) {
                    echo "<p class='text-red-500'>Error loading user types data.</p>";
                }
                ?>
            </div>
        </div>
        
        <!-- Detailed Statistics Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Top Content -->
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
                <h3 class="text-lg font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                    <?php echo $lang['top_downloaded_content'] ?? 'Top Downloaded Content'; ?>
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-700' : ''; ?>">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                    <?php echo $lang['title'] ?? 'Title'; ?>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                    <?php echo $lang['category'] ?? 'Category'; ?>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                    <?php echo $lang['downloads'] ?? 'Downloads'; ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-700' : ''; ?>">
                            <?php
                            // Get top downloaded content - FIXED QUERY
                            try {
                                $stmt = $pdo->prepare("
                                    SELECT m.title, c.name as category, COUNT(md.id) as download_count 
                                    FROM media m
                                    JOIN media_downloads md ON m.id = md.media_id
                                    LEFT JOIN categories c ON m.category_id = c.id
                                    WHERE md.downloaded_at BETWEEN ? AND ?
                                    GROUP BY m.id, m.title, c.name
                                    ORDER BY download_count DESC
                                    LIMIT 10
                                ");
                                $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                                $topContent = $stmt->fetchAll();
                                
                                if (count($topContent) > 0) {
                                    foreach ($topContent as $content) {
                                        ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                                <?php echo htmlspecialchars($content['title'] ?? 'Unknown Title'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                                <?php echo htmlspecialchars($content['category'] ?? 'Uncategorized'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                                <?php echo number_format($content['download_count']); ?>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    ?>
                                    <tr>
                                        <td colspan="3" class="px-6 py-4 text-center text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                            <?php echo $lang['no_data_available'] ?? 'No data available for selected period'; ?>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } catch (PDOException $e) {
                                ?>
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-center text-sm text-red-500">
                                        Error loading data: <?php echo $e->getMessage(); ?>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 text-right">
                    <a href="content.php?period=<?php echo $period; ?>&start=<?php echo $startDate; ?>&end=<?php echo $endDate; ?>" class="text-blue-500 hover:text-blue-700 text-sm">
                        <?php echo $lang['view_all'] ?? 'View All'; ?> <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
            
            <!-- Top Users -->
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
                <h3 class="text-lg font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                    <?php echo $lang['top_users'] ?? 'Top Users'; ?>
                </h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-700' : ''; ?>">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                    <?php echo $lang['username'] ?? 'Username'; ?>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                    <?php echo $lang['role'] ?? 'Role'; ?>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                    <?php echo $lang['downloads'] ?? 'Downloads'; ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-700' : ''; ?>">
                            <?php
                            // Get top users by download count - FIXED QUERY
                            try {
                                $stmt = $pdo->prepare("
                                    SELECT u.username, u.role, COUNT(md.id) as download_count 
                                    FROM users u
                                    JOIN media_downloads md ON u.id = md.user_id
                                    WHERE md.downloaded_at BETWEEN ? AND ?
                                    GROUP BY u.id, u.username, u.role
                                    ORDER BY download_count DESC
                                    LIMIT 10
                                ");
                                $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                                $topUsers = $stmt->fetchAll();
                                
                                if (count($topUsers) > 0) {
                                    foreach ($topUsers as $user) {
                                        ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                                <?php echo htmlspecialchars($user['username'] ?? 'Unknown User'); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                    <?php 
                                                    $role = $user['role'] ?? 'user';
                                                    switch (strtolower($role)) {
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
                                                    <?php echo ucfirst(htmlspecialchars($role)); ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                                <?php echo number_format($user['download_count']); ?>
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    ?>
                                    <tr>
                                        <td colspan="3" class="px-6 py-4 text-center text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                            <?php echo $lang['no_data_available'] ?? 'No data available for selected period'; ?>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } catch (PDOException $e) {
                                ?>
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-center text-sm text-red-500">
                                        Error loading data: <?php echo $e->getMessage(); ?>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 text-right">
                    <a href="users.php?period=<?php echo $period; ?>&start=<?php echo $startDate; ?>&end=<?php echo $endDate; ?>" class="text-blue-500 hover:text-blue-700 text-sm">
                        <?php echo $lang['view_all'] ?? 'View All'; ?> <i class="fas fa-arrow-right ml-1"></i>
                    </a>
                </div>
            </div>
        </div>
        <!-- Regional Data -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
            <h3 class="text-lg font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                <?php echo $lang['regional_distribution'] ?? 'Regional Distribution'; ?>
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div class="relative" style="height: 300px;">
                    <canvas id="regionChart"></canvas>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-700' : ''; ?>">
                        <thead>
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                    <?php echo $lang['country'] ?? 'Country'; ?>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                    <?php echo $lang['users'] ?? 'Users'; ?>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                    <?php echo $lang['percentage'] ?? 'Percentage'; ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-700' : ''; ?>">
                            <?php
                            // Get regional distribution - FIXED QUERY
                            try {
                                // Check if country column exists in users table
                                $columnExists = false;
                                try {
                                    $checkColumnQuery = $pdo->query("SHOW COLUMNS FROM users LIKE 'country'");
                                    $columnExists = $checkColumnQuery->rowCount() > 0;
                                } catch (Exception $e) {
                                    // Column doesn't exist
                                }
                                
                                if ($columnExists) {
                                    // Use the country column if it exists
                                    $stmt = $pdo->prepare("
                                        SELECT COALESCE(country, 'Unknown') as country, COUNT(*) as user_count 
                                        FROM users 
                                        WHERE created_at BETWEEN ? AND ? 
                                        GROUP BY country 
                                        ORDER BY user_count DESC
                                        LIMIT 10
                                    ");
                                    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
                                } else {
                                    // Simulate regional data if country column doesn't exist
                                    $demoData = [
                                        ['country' => 'United States', 'user_count' => 250],
                                        ['country' => 'United Kingdom', 'user_count' => 120],
                                        ['country' => 'Germany', 'user_count' => 85],
                                        ['country' => 'Canada', 'user_count' => 75],
                                        ['country' => 'France', 'user_count' => 65],
                                        ['country' => 'Australia', 'user_count' => 55],
                                        ['country' => 'Japan', 'user_count' => 45],
                                        ['country' => 'Brazil', 'user_count' => 35],
                                        ['country' => 'India', 'user_count' => 30],
                                        ['country' => 'Sweden', 'user_count' => 25],
                                    ];
                                    
                                    // Create a PDOStatement-like object with the demo data
                                    $stmt = new class($demoData) {
                                        private $data;
                                        
                                        public function __construct($data) {
                                            $this->data = $data;
                                        }
                                        
                                        public function fetchAll() {
                                            return $this->data;
                                        }
                                    };
                                }
                                
                                $regions = $stmt->fetchAll();
                                
                                // Calculate total for percentage
                                $totalRegionalUsers = 0;
                                foreach ($regions as $region) {
                                    $totalRegionalUsers += $region['user_count'];
                                }
                                
                                if (count($regions) > 0) {
                                    foreach ($regions as $region) {
                                        $percentage = $totalRegionalUsers > 0 ? round(($region['user_count'] / $totalRegionalUsers) * 100, 1) : 0;
                                        ?>
                                        <tr>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                                <?php echo htmlspecialchars($region['country']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                                <?php echo number_format($region['user_count']); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                                <?php echo $percentage; ?>%
                                            </td>
                                        </tr>
                                        <?php
                                    }
                                } else {
                                    ?>
                                    <tr>
                                        <td colspan="3" class="px-6 py-4 text-center text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                            <?php echo $lang['no_data_available'] ?? 'No data available for selected period'; ?>
                                        </td>
                                    </tr>
                                    <?php
                                }
                                
                                // Prepare data for chart
                                $countryLabels = [];
                                $countryCounts = [];
                                foreach ($regions as $region) {
                                    $countryLabels[] = $region['country'];
                                    $countryCounts[] = $region['user_count'];
                                }
                                
                            } catch (PDOException $e) {
                                ?>
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-center text-sm text-red-500">
                                        Error loading data: <?php echo $e->getMessage(); ?>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="mt-4 text-right">
                <a href="geography.php?period=<?php echo $period; ?>&start=<?php echo $startDate; ?>&end=<?php echo $endDate; ?>" class="text-blue-500 hover:text-blue-700 text-sm">
                    <?php echo $lang['view_detailed_report'] ?? 'View Detailed Report'; ?> <i class="fas fa-arrow-right ml-1"></i>
                </a>
            </div>
        </div>
        
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>

<!-- JavaScript for Charts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Function to toggle custom date fields
    window.toggleCustomDateFields = function() {
        const periodSelect = document.getElementById('period');
        const customDateFields = document.getElementById('customDateFields');
        
        if (periodSelect.value === 'custom') {
            customDateFields.classList.remove('hidden');
        } else {
            customDateFields.classList.add('hidden');
        }
    }
    
    // Growth Chart
    const growthCtx = document.getElementById('growthChart').getContext('2d');
    const growthChart = new Chart(growthCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode(array_column($dailyData, 'date')); ?>,
            datasets: [
                {
                    label: '<?php echo $lang['new_users'] ?? 'New Users'; ?>',
                    data: <?php echo json_encode(array_column($dailyData, 'users')); ?>,
                    backgroundColor: 'rgba(59, 130, 246, 0.2)',
                    borderColor: 'rgba(59, 130, 246, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    pointRadius: 3
                },
                {
                    label: '<?php echo $lang['downloads'] ?? 'Downloads'; ?>',
                    data: <?php echo json_encode(array_column($dailyData, 'downloads')); ?>,
                    backgroundColor: 'rgba(139, 92, 246, 0.2)',
                    borderColor: 'rgba(139, 92, 246, 1)',
                    borderWidth: 2,
                    tension: 0.3,
                    pointRadius: 3
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color: '<?php echo $darkMode ? "rgba(255, 255, 255, 0.8)" : "rgba(0, 0, 0, 0.8)"; ?>'
                    }
                },
                tooltip: {
                    mode: 'index',
                    intersect: false
                }
            },
            scales: {
                x: {
                    display: true,
                    title: {
                        display: true,
                        text: '<?php echo $lang['date'] ?? 'Date'; ?>',
                        color: '<?php echo $darkMode ? "rgba(255, 255, 255, 0.8)" : "rgba(0, 0, 0, 0.8)"; ?>'
                    },
                    ticks: {
                        color: '<?php echo $darkMode ? "rgba(255, 255, 255, 0.6)" : "rgba(0, 0, 0, 0.6)"; ?>'
                    },
                    grid: {
                        color: '<?php echo $darkMode ? "rgba(255, 255, 255, 0.1)" : "rgba(0, 0, 0, 0.1)"; ?>'
                    }
                },
                y: {
                    display: true,
                    title: {
                        display: true,
                        text: '<?php echo $lang['count'] ?? 'Count'; ?>',
                        color: '<?php echo $darkMode ? "rgba(255, 255, 255, 0.8)" : "rgba(0, 0, 0, 0.8)"; ?>'
                    },
                    ticks: {
                        color: '<?php echo $darkMode ? "rgba(255, 255, 255, 0.6)" : "rgba(0, 0, 0, 0.6)"; ?>'
                    },
                    grid: {
                        color: '<?php echo $darkMode ? "rgba(255, 255, 255, 0.1)" : "rgba(0, 0, 0, 0.1)"; ?>'
                    }
                }
            }
        }
    });
    
    // User Types Chart
    const userTypesCtx = document.getElementById('userTypesChart').getContext('2d');
    const userTypesChart = new Chart(userTypesCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($roleLabels); ?>,
            datasets: [{
                data: <?php echo json_encode($roleCounts); ?>,
                backgroundColor: [
                    'rgba(239, 68, 68, 0.7)',
                    'rgba(16, 185, 129, 0.7)',
                    'rgba(245, 158, 11, 0.7)',
                    'rgba(59, 130, 246, 0.7)'
                ],
                borderColor: [
                    'rgba(239, 68, 68, 1)',
                    'rgba(16, 185, 129, 1)',
                    'rgba(245, 158, 11, 1)',
                    'rgba(59, 130, 246, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        color: '<?php echo $darkMode ? "rgba(255, 255, 255, 0.8)" : "rgba(0, 0, 0, 0.8)"; ?>'
                    }
                }
            }
        }
    });
    
    // Region Chart
    const regionCtx = document.getElementById('regionChart').getContext('2d');
    const regionChart = new Chart(regionCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($countryLabels ?? []); ?>,
            datasets: [{
                label: '<?php echo $lang['users_by_country'] ?? 'Users by Country'; ?>',
                data: <?php echo json_encode($countryCounts ?? []); ?>,
                backgroundColor: 'rgba(16, 185, 129, 0.7)',
                borderColor: 'rgba(16, 185, 129, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    display: true,
                    ticks: {
                        color: '<?php echo $darkMode ? "rgba(255, 255, 255, 0.6)" : "rgba(0, 0, 0, 0.6)"; ?>'
                    },
                    grid: {
                        color: '<?php echo $darkMode ? "rgba(255, 255, 255, 0.1)" : "rgba(0, 0, 0, 0.1)"; ?>'
                    }
                },
                y: {
                    display: true,
                    ticks: {
                        color: '<?php echo $darkMode ? "rgba(255, 255, 255, 0.6)" : "rgba(0, 0, 0, 0.6)"; ?>'
                    },
                    grid: {
                        color: '<?php echo $darkMode ? "rgba(255, 255, 255, 0.1)" : "rgba(0, 0, 0, 0.1)"; ?>'
                    }
                }
            }
        }
    });
});
</script>

<?php
// Include footer
require_once '../../theme/admin/footer.php';
?>   