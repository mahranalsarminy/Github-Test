<?php
// Set page title
$pageTitle = 'User Reports - WallPix Admin';

// Include header
require_once '../../theme/admin/header.php';

// Current date and time in UTC
$currentDateTime = '2025-03-18 10:45:45';
$currentUser = 'mahranalsarminy';

// Initialize variables
$period = isset($_GET['period']) ? $_GET['period'] : 'month';
$validPeriods = ['week', 'month', 'year', 'custom'];

if (!in_array($period, $validPeriods)) {
    $period = 'month';
}

// Date range for custom period
$startDate = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d');

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

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get users data
try {
    // Total count for pagination
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM users
        WHERE created_at BETWEEN ? AND ?
    ");
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    $totalCount = $stmt->fetch()['total'];
    $totalPages = ceil($totalCount / $perPage);
    
    // Get paginated users
    $stmt = $pdo->prepare("
        SELECT 
            u.id,
            u.username,
            u.email,
            u.role,
            u.created_at,
            u.country,
            COUNT(d.id) as download_count,
            MAX(s.status) as subscription_status
        FROM users u
        LEFT JOIN downloads d ON u.id = d.user_id AND d.download_date BETWEEN ? AND ?
        LEFT JOIN subscriptions s ON u.id = s.user_id AND s.status = 'active'
        WHERE u.created_at BETWEEN ? AND ?
        GROUP BY u.id, u.username, u.email, u.role, u.created_at, u.country
        ORDER BY u.created_at DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([
        $startDate . ' 00:00:00', 
        $endDate . ' 23:59:59',
        $startDate . ' 00:00:00', 
        $endDate . ' 23:59:59',
        $perPage,
        $offset
    ]);
    $users = $stmt->fetchAll();
    
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
                    <?php echo $lang['user_reports'] ?? 'User Reports'; ?>
                </h1>
                <p class="mt-2 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                    <?php echo $lang['detailed_user_statistics'] ?? 'Detailed user statistics and analytics'; ?>
                    <span class="ml-2"><?php echo $startDate; ?> - <?php echo $endDate; ?></span>
                </p>
            </div>
            <div class="mt-4 md:mt-0 flex space-x-2">
                <a href="index.php" class="btn bg-gray-500 hover:bg-gray-600 text-white">
                    <i class="fas fa-arrow-left mr-2"></i> <?php echo $lang['back_to_dashboard'] ?? 'Back to Dashboard'; ?>
                </a>
                <a href="export-users.php?period=<?php echo $period; ?>&start=<?php echo $startDate; ?>&end=<?php echo $endDate; ?>" class="btn bg-green-500 hover:bg-green-600 text-white">
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
                
                <!-- Custom date fields -->
                <div id="customDateFields" class="flex flex-wrap space-y-4 md:space-y-0 space-x-0 md:space-x-4 <?php echo $period === 'custom' ? '' : 'hidden'; ?>">
                    <div class="w-full md:w-auto">
                        <label for="start" class="block text-sm font-medium mb-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                            <?php echo $lang['start_date'] ?? 'Start Date'; ?>
                        </label>
                        <input type="date" id="start" name="start" value="<?php echo $startDate; ?>"
                            class="w-full md:w-auto p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="w-full md:w-auto">
                        <label for="end" class="block text-sm font-medium mb-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                            <?php echo $lang['end_date'] ?? 'End Date'; ?>
                        </label>
                        <input type="date" id="end" name="end" value="<?php echo $endDate; ?>"
                            class="w-full md:w-auto p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                
                <div class="w-full md:w-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter mr-2"></i> <?php echo $lang['apply'] ?? 'Apply'; ?>
                    </button>
                </div>
                
                <!-- Keep the page parameter if it exists -->
                <?php if (isset($_GET['page'])): ?>
                <input type="hidden" name="page" value="<?php echo $page; ?>">
                <?php endif; ?>
            </form>
        </div>
        
        <!-- Users Data Table -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-700' : ''; ?>">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                <?php echo $lang['user'] ?? 'User'; ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                <?php echo $lang['role'] ?? 'Role'; ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                <?php echo $lang['country'] ?? 'Country'; ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                <?php echo $lang['downloads'] ?? 'Downloads'; ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                <?php echo $lang['subscription'] ?? 'Subscription'; ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                <?php echo $lang['registration_date'] ?? 'Registration Date'; ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-700' : ''; ?>">
                        <?php if (isset($users) && count($users) > 0): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                                                    <i class="fas fa-user text-gray-400"></i>
                                                </div>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                                    <?php echo htmlspecialchars($user['username']); ?>
                                                </div>
                                                <div class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                                    <?php echo htmlspecialchars($user['email']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php 
                                            switch ($user['role']) {
                                                case 'admin':
                                                    echo 'bg-red-100 text-red-800';
                                                    break;
                                                case 'subscriber':
                                                    echo 'bg-green-100 text-green-800';
                                                    break;
                                                default:
                                                    echo 'bg-gray-100 text-gray-800';
                                            }
                                            ?>">
                                                                                        <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                        <?php echo htmlspecialchars($user['country'] ?: 'Unknown'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                        <?php echo number_format($user['download_count']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <?php if ($user['subscription_status'] == 'active'): ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                <?php echo $lang['active'] ?? 'Active'; ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                <?php echo $lang['inactive'] ?? 'Inactive'; ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                        <?php echo date('Y-m-d', strtotime($user['created_at'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    <?php echo $lang['no_users_found'] ?? 'No users found for the selected period.'; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
            <div class="flex justify-between items-center">
                <div class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                    <?php echo $lang['showing'] ?? 'Showing'; ?> 
                    <?php echo number_format(($page - 1) * $perPage + 1); ?> 
                    <?php echo $lang['to'] ?? 'to'; ?> 
                    <?php echo number_format(min($page * $perPage, $totalCount)); ?> 
                    <?php echo $lang['of'] ?? 'of'; ?> 
                    <?php echo number_format($totalCount); ?> 
                    <?php echo $lang['users'] ?? 'users'; ?>
                </div>
                <div class="flex space-x-1">
                    <?php
                    $queryParams = $_GET;
                    
                    // Previous button
                    if ($page > 1) {
                        $queryParams['page'] = $page - 1;
                        $prevLink = '?' . http_build_query($queryParams);
                        ?>
                        <a href="<?php echo $prevLink; ?>" class="px-3 py-2 text-sm font-medium rounded-md <?php echo $darkMode ? 'bg-gray-700 text-gray-300 hover:bg-gray-600' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <?php
                    } else {
                        ?>
                        <span class="px-3 py-2 text-sm font-medium rounded-md <?php echo $darkMode ? 'bg-gray-700 text-gray-500' : 'bg-gray-100 text-gray-400'; ?> cursor-not-allowed">
                            <i class="fas fa-chevron-left"></i>
                        </span>
                        <?php
                    }
                    
                    // Page numbers
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    for ($i = $startPage; $i <= $endPage; $i++) {
                        $queryParams['page'] = $i;
                        $pageLink = '?' . http_build_query($queryParams);
                        ?>
                        <a href="<?php echo $pageLink; ?>" class="px-3 py-2 text-sm font-medium rounded-md <?php echo $i == $page ? ($darkMode ? 'bg-blue-600 text-white' : 'bg-blue-500 text-white') : ($darkMode ? 'bg-gray-700 text-gray-300 hover:bg-gray-600' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'); ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php
                    }
                    
                    // Next button
                    if ($page < $totalPages) {
                        $queryParams['page'] = $page + 1;
                        $nextLink = '?' . http_build_query($queryParams);
                        ?>
                        <a href="<?php echo $nextLink; ?>" class="px-3 py-2 text-sm font-medium rounded-md <?php echo $darkMode ? 'bg-gray-700 text-gray-300 hover:bg-gray-600' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php
                    } else {
                        ?>
                        <span class="px-3 py-2 text-sm font-medium rounded-md <?php echo $darkMode ? 'bg-gray-700 text-gray-500' : 'bg-gray-100 text-gray-400'; ?> cursor-not-allowed">
                            <i class="fas fa-chevron-right"></i>
                        </span>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
// Toggle custom date fields
function toggleCustomDateFields() {
    const periodSelect = document.getElementById('period');
    const customDateFields = document.getElementById('customDateFields');
    
    if (periodSelect.value === 'custom') {
        customDateFields.classList.remove('hidden');
    } else {
        customDateFields.classList.add('hidden');
    }
}
</script>

<?php
// Include footer
require_once '../../theme/admin/footer.php';
?>        