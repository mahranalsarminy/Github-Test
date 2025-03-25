<?php
// Set page title
$pageTitle = 'User Reports - WallPix Admin';

// Include header
require_once '../../theme/admin/header.php';

// Current date and time in UTC
$currentDateTime = '2025-03-24 12:09:58';
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
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$roleFilter = isset($_GET['role']) ? $_GET['role'] : 'all';

// Get user data
try {
    // Base query parts
    $baseSelect = "SELECT u.id, u.username, u.email, u.role, u.created_at";
    $baseFrom = " FROM users u";
    $baseWhere = " WHERE u.created_at BETWEEN ? AND ?";
    $baseParams = [$startDate . ' 00:00:00', $endDate . ' 23:59:59'];

    // Add search condition if provided
    if (!empty($search)) {
        $baseWhere .= " AND (u.username LIKE ? OR u.email LIKE ?)";
        $baseParams[] = "%{$search}%";
        $baseParams[] = "%{$search}%";
    }

    // Add role filter if not 'all'
    if ($roleFilter !== 'all') {
        $baseWhere .= " AND u.role = ?";
        $baseParams[] = $roleFilter;
    }

    // Count total for pagination
    $countQuery = $pdo->prepare("SELECT COUNT(*) as total" . $baseFrom . $baseWhere);
    $countQuery->execute($baseParams);
    $totalUsers = $countQuery->fetchColumn();
    $totalPages = ceil($totalUsers / $perPage);
    $page = min($page, max(1, $totalPages));
    $offset = ($page - 1) * $perPage;

    // Get paginated users with download counts
    $query = $baseSelect . ", 
                (SELECT COUNT(*) FROM media_downloads WHERE user_id = u.id) as download_count,
                (SELECT COUNT(*) FROM media_downloads WHERE user_id = u.id AND downloaded_at BETWEEN ? AND ?) as period_downloads" 
             . $baseFrom . $baseWhere . " ORDER BY u.created_at DESC LIMIT ? OFFSET ?";
    
    // Add date range parameters for download counts
    $params = $baseParams;
    $params[] = $startDate . ' 00:00:00';
    $params[] = $endDate . ' 23:59:59';
    $params[] = $perPage;
    $params[] = $offset;

    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get user count by role
    $roleQuery = $pdo->prepare("
        SELECT COALESCE(role, 'Unknown') as role, COUNT(*) as count 
        FROM users 
        WHERE created_at BETWEEN ? AND ?
        GROUP BY role
    ");
    $roleQuery->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    $roleStats = $roleQuery->fetchAll(PDO::FETCH_ASSOC);
    
    // Get signup trends by day
    $trendQuery = $pdo->prepare("
        SELECT DATE(created_at) as date, COUNT(*) as count
        FROM users
        WHERE created_at BETWEEN ? AND ?
        GROUP BY DATE(created_at)
        ORDER BY date
    ");
    $trendQuery->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    $trends = $trendQuery->fetchAll(PDO::FETCH_ASSOC);
    
    // Process trend data for chart
    $trendDates = [];
    $trendCounts = [];
    foreach ($trends as $trend) {
        $trendDates[] = $trend['date'];
        $trendCounts[] = $trend['count'];
    }
    
    // Process role stats for chart
    $roleLabels = [];
    $roleCounts = [];
    foreach ($roleStats as $stat) {
        $roleLabels[] = ucfirst($stat['role']);
        $roleCounts[] = $stat['count'];
    }
    
} catch (PDOException $e) {
    $errorMessage = "Database error: " . $e->getMessage();
    $users = [];
    $totalUsers = 0;
    $totalPages = 0;
}

// Include sidebar
require_once '../../theme/admin/slidbar.php';
?>

<!-- Main Content -->
<div class="content-wrapper p-4 sm:ml-64">
    <div class="p-4 mt-14">
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold <?php echo isset($darkMode) && $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                    <?php echo $lang['user_reports'] ?? 'User Reports'; ?>
                </h1>
                <p class="mt-2 text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                    <?php echo $lang['view_user_statistics'] ?? 'View and analyze user statistics and trends'; ?>
                </p>
            </div>
            <div class="mt-4 md:mt-0">
                <a href="export.php?type=users&period=<?php echo $period; ?>&start=<?php echo $startDate; ?>&end=<?php echo $endDate; ?>" class="btn bg-green-500 hover:bg-green-600 text-white">
                    <i class="fas fa-file-excel mr-2"></i> <?php echo $lang['export_csv'] ?? 'Export CSV'; ?>
                </a>
            </div>
        </div>

        <!-- Period Selector -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6 <?php echo isset($darkMode) && $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
            <form action="" method="GET" class="flex flex-wrap items-end gap-4">
                <div class="w-full md:w-auto">
                    <label for="period" class="block text-sm font-medium mb-2 <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
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
                <div id="customDateFields" class="flex flex-wrap gap-4 <?php echo $period === 'custom' ? '' : 'hidden'; ?>">
                    <div class="w-full md:w-auto">
                        <label for="start" class="block text-sm font-medium mb-2 <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                            <?php echo $lang['start_date'] ?? 'Start Date'; ?>
                        </label>
                        <input type="date" id="start" name="start" value="<?php echo $startDate; ?>"
                            class="w-full md:w-auto p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="w-full md:w-auto">
                        <label for="end" class="block text-sm font-medium mb-2 <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                            <?php echo $lang['end_date'] ?? 'End Date'; ?>
                        </label>
                        <input type="date" id="end" name="end" value="<?php echo $endDate; ?>"
                            class="w-full md:w-auto p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                
                <!-- Role filter -->
                <div class="w-full md:w-auto">
                    <label for="role" class="block text-sm font-medium mb-2 <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                        <?php echo $lang['role'] ?? 'Role'; ?>
                    </label>
                    <select id="role" name="role" class="w-full md:w-40 p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="all" <?php echo $roleFilter === 'all' ? 'selected' : ''; ?>><?php echo $lang['all_roles'] ?? 'All Roles'; ?></option>
                        <option value="admin" <?php echo $roleFilter === 'admin' ? 'selected' : ''; ?>><?php echo $lang['admin'] ?? 'Admin'; ?></option>
                        <option value="subscriber" <?php echo $roleFilter === 'subscriber' ? 'selected' : ''; ?>><?php echo $lang['subscriber'] ?? 'Subscriber'; ?></option>
                        <option value="user" <?php echo $roleFilter === 'user' ? 'selected' : ''; ?>><?php echo $lang['user'] ?? 'User'; ?></option>
                    </select>
                </div>
                
                <!-- Search box -->
                <div class="w-full md:w-auto flex-grow">
                    <label for="search" class="block text-sm font-medium mb-2 <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                        <?php echo $lang['search'] ?? 'Search'; ?>
                    </label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="<?php echo $lang['search_users'] ?? 'Search by username or email'; ?>"
                        class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <!-- Apply button -->
                <div class="w-full md:w-auto">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <i class="fas fa-filter mr-2"></i> <?php echo $lang['apply'] ?? 'Apply'; ?>
                    </button>
                </div>
                
                <!-- Keep the page parameter if it exists -->
                <?php if (isset($_GET['page'])): ?>
                <input type="hidden" name="page" value="<?php echo $page; ?>">
                <?php endif; ?>
            </form>
        </div>
        
        <!-- User Statistics Summary -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- User Signup Trend Chart -->
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo isset($darkMode) && $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
                <h3 class="text-lg font-semibold mb-4 <?php echo isset($darkMode) && $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                    <?php echo $lang['user_signup_trends'] ?? 'User Signup Trends'; ?>
                </h3>
                <div class="relative" style="height: 300px;">
                    <canvas id="userTrendChart"></canvas>
                </div>
            </div>
            
            <!-- User Role Distribution -->
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo isset($darkMode) && $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
                <h3 class="text-lg font-semibold mb-4 <?php echo isset($darkMode) && $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                    <?php echo $lang['user_role_distribution'] ?? 'User Role Distribution'; ?>
                </h3>
                <div class="relative" style="height: 300px;">
                    <canvas id="roleDistributionChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- User List -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6 <?php echo isset($darkMode) && $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
            <h3 class="text-lg font-semibold mb-4 <?php echo isset($darkMode) && $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                <?php echo $lang['user_list'] ?? 'User List'; ?>
            </h3>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 <?php echo isset($darkMode) && $darkMode ? 'divide-gray-700' : ''; ?>">
                    <thead class="<?php echo isset($darkMode) && $darkMode ? 'bg-gray-700' : 'bg-gray-50'; ?>">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                <?php echo $lang['username'] ?? 'Username'; ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                <?php echo $lang['email'] ?? 'Email'; ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                <?php echo $lang['role'] ?? 'Role'; ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                <?php echo $lang['total_downloads'] ?? 'Total Downloads'; ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                <?php echo $lang['period_downloads'] ?? 'Period Downloads'; ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                <?php echo $lang['join_date'] ?? 'Join Date'; ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="<?php echo isset($darkMode) && $darkMode ? 'bg-gray-800 divide-gray-700' : 'bg-white divide-gray-200'; ?>">
                        <?php if (count($users) > 0): ?>
                            <?php foreach($users as $user): ?>
                                <tr class="<?php echo isset($darkMode) && $darkMode ? 'hover:bg-gray-700' : 'hover:bg-gray-50'; ?>">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                        <?php echo htmlspecialchars($user['username']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                        <?php echo htmlspecialchars($user['email']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php 
                                            $role = strtolower($user['role'] ?? 'user');
                                            switch ($role) {
                                                case 'admin':
                                                    echo $darkMode ? 'bg-red-900 text-red-200' : 'bg-red-100 text-red-800';
                                                    break;
                                                case 'subscriber':
                                                    echo $darkMode ? 'bg-green-900 text-green-200' : 'bg-green-100 text-green-800';
                                                    break;
                                                default:
                                                    echo $darkMode ? 'bg-blue-900 text-blue-200' : 'bg-blue-100 text-blue-800';
                                            }
                                            ?>">
                                            <?php echo ucfirst(htmlspecialchars($user['role'] ?? 'User')); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                        <?php echo number_format($user['download_count']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                        <?php echo number_format($user['period_downloads']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                        <?php echo date('Y-m-d', strtotime($user['created_at'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="px-6 py-4 text-center text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    <?php 
                                    if (!empty($search)) {
                                        echo $lang['no_users_found_search'] ?? 'No users found matching your search criteria.';
                                    } elseif ($roleFilter !== 'all') {
                                        echo $lang['no_users_found_role'] ?? 'No users found with this role in the selected period.';
                                    } else {
                                        echo $lang['no_users_found'] ?? 'No users found for the selected period.';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="bg-white rounded-lg shadow-md p-6 <?php echo isset($darkMode) && $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
            <div class="flex justify-between items-center">
                <div class="text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                    <?php echo $lang['showing'] ?? 'Showing'; ?> 
                    <?php echo number_format(($page - 1) * $perPage + 1); ?> 
                    <?php echo $lang['to'] ?? 'to'; ?> 
                    <?php echo number_format(min($page * $perPage, $totalUsers)); ?> 
                    <?php echo $lang['of'] ?? 'of'; ?> 
                    <?php echo number_format($totalUsers); ?> 
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
                        <a href="<?php echo $prevLink; ?>" class="px-3 py-2 text-sm font-medium rounded-md <?php echo isset($darkMode) && $darkMode ? 'bg-gray-700 text-gray-300 hover:bg-gray-600' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                        <?php
                    } else {
                        ?>
                        <span class="px-3 py-2 text-sm font-medium rounded-md <?php echo isset($darkMode) && $darkMode ? 'bg-gray-700 text-gray-500' : 'bg-gray-100 text-gray-400'; ?> cursor-not-allowed">
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
                        <a href="<?php echo $nextLink; ?>" class="px-3 py-2 text-sm font-medium rounded-md <?php echo isset($darkMode) && $darkMode ? 'bg-gray-700 text-gray-300 hover:bg-gray-600' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'; ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                        <?php
                    } else {
                        ?>
                        <span class="px-3 py-2 text-sm font-medium rounded-md <?php echo isset($darkMode) && $darkMode ? 'bg-gray-700 text-gray-500' : 'bg-gray-100 text-gray-400'; ?> cursor-not-allowed">
                            <i class="fas fa-chevron-right"></i>
                        </span>
                        <?php
                    }
                    ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Last Update Info -->
        <div class="mt-6 text-right text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
            <?php echo $lang['last_updated'] ?? 'Last Updated'; ?>: 
            <?php echo $currentDateTime; ?>
            | <?php echo $lang['user'] ?? 'User'; ?>: 
            <?php echo htmlspecialchars($currentUser); ?>
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
    
    // User Trend Chart
    const userTrendCtx = document.getElementById('userTrendChart').getContext('2d');
    const userTrendChart = new Chart(userTrendCtx, {
        type: 'line',
        data: {
            labels: <?php echo json_encode($trendDates ?? []); ?>,
            datasets: [{
                label: '<?php echo $lang['new_signups'] ?? 'New Signups'; ?>',
                data: <?php echo json_encode($trendCounts ?? []); ?>,
                backgroundColor: 'rgba(59, 130, 246, 0.2)',
                borderColor: 'rgba(59, 130, 246, 1)',
                borderWidth: 2,
                tension: 0.3,
                pointRadius: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        color: '<?php echo isset($darkMode) && $darkMode ? "rgba(255, 255, 255, 0.8)" : "rgba(0, 0, 0, 0.8)"; ?>'
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
                        color: '<?php echo isset($darkMode) && $darkMode ? "rgba(255, 255, 255, 0.8)" : "rgba(0, 0, 0, 0.8)"; ?>'
                    },
                    ticks: {
                        color: '<?php echo isset($darkMode) && $darkMode ? "rgba(255, 255, 255, 0.6)" : "rgba(0, 0, 0, 0.6)"; ?>'
                    },
                    grid: {
                        color: '<?php echo isset($darkMode) && $darkMode ? "rgba(255, 255, 255, 0.1)" : "rgba(0, 0, 0, 0.1)"; ?>'
                    }
                },
                y: {
                    display: true,
                    title: {
                        display: true,
                        text: '<?php echo $lang['users'] ?? 'Users'; ?>',
                        color: '<?php echo isset($darkMode) && $darkMode ? "rgba(255, 255, 255, 0.8)" : "rgba(0, 0, 0, 0.8)"; ?>'
                    },
                    ticks: {
                        color: '<?php echo isset($darkMode) && $darkMode ? "rgba(255, 255, 255, 0.6)" : "rgba(0, 0, 0, 0.6)"; ?>'
                    },
                    grid: {
                        color: '<?php echo isset($darkMode) && $darkMode ? "rgba(255, 255, 255, 0.1)" : "rgba(0, 0, 0, 0.1)"; ?>'
                    }
                }
            }
        }
    });
    
    // Role Distribution Chart
    const roleDistCtx = document.getElementById('roleDistributionChart').getContext('2d');
    const roleDistChart = new Chart(roleDistCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($roleLabels ?? ['User']); ?>,
            datasets: [{
                data: <?php echo json_encode($roleCounts ?? [0]); ?>,
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
                    position: 'right',
                    labels: {
                        color: '<?php echo isset($darkMode) && $darkMode ? "rgba(255, 255, 255, 0.8)" : "rgba(0, 0, 0, 0.8)"; ?>',
                        font: {
                            size: 12
                        },
                        boxWidth: 15
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
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