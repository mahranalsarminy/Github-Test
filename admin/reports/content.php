<?php
// Set page title
$pageTitle = 'Content Reports - WallPix Admin';

// Include header
require_once '../../theme/admin/header.php';

// Current date and time in UTC
$currentDateTime = date('Y-m-d H:i:s');
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
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Get file type statistics
try {
    $stmt = $pdo->prepare("
        SELECT file_type, COUNT(DISTINCT md.id) as download_count
        FROM media m
        LEFT JOIN media_downloads md ON m.id = md.media_id
        WHERE md.downloaded_at BETWEEN ? AND ?
        GROUP BY file_type
        ORDER BY download_count DESC
    ");
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    $fileTypeStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Database error: " . $e->getMessage();
    $fileTypeStats = [];
}

// Get popular content
try {
    // Count total items for pagination
    $countStmt = $pdo->prepare("
        SELECT COUNT(DISTINCT m.id) as total 
        FROM media m
        LEFT JOIN media_downloads md ON m.id = md.media_id
        LEFT JOIN categories c ON m.category_id = c.id
        WHERE md.downloaded_at BETWEEN ? AND ?
    ");
    $countStmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    $totalCount = $countStmt->fetchColumn();
    $totalPages = ceil($totalCount / $perPage);
    
    // Fetch paginated content
    $stmt = $pdo->prepare("
        SELECT m.id, m.title, m.file_type, c.name as category_name, 
               COUNT(DISTINCT md.id) as download_count, m.created_at
        FROM media m
        LEFT JOIN media_downloads md ON m.id = md.media_id
        LEFT JOIN categories c ON m.category_id = c.id
        WHERE md.downloaded_at BETWEEN ? AND ?
        GROUP BY m.id, m.title, m.file_type, c.name, m.created_at
        ORDER BY download_count DESC
        LIMIT ? OFFSET ?
    ");
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59', $perPage, $offset]);
    $contentItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = "Database error: " . $e->getMessage();
    $contentItems = [];
    $totalCount = 0;
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
                    <?php echo $lang['content_reports'] ?? 'Content Reports'; ?>
                </h1>
                <p class="mt-2 text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                    <?php echo $lang['content_analysis'] ?? 'Analyze content popularity and downloads'; ?>
                </p>
            </div>
            <div class="mt-4 md:mt-0">
                <a href="export.php?type=content&period=<?php echo $period; ?>&start=<?php echo $startDate; ?>&end=<?php echo $endDate; ?>" class="btn bg-green-500 hover:bg-green-600 text-white">
                    <i class="fas fa-file-excel mr-2"></i> <?php echo $lang['export_csv'] ?? 'Export CSV'; ?>
                </a>
            </div>
        </div>

        <!-- Period Selector -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6 <?php echo isset($darkMode) && $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
            <form action="" method="GET" class="flex flex-wrap items-end space-y-4 md:space-y-0 space-x-0 md:space-x-4">
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
                <div id="customDateFields" class="flex flex-wrap space-y-4 md:space-y-0 space-x-0 md:space-x-4 <?php echo $period === 'custom' ? '' : 'hidden'; ?>">
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
        
        <!-- File Type Distribution Chart -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6 <?php echo isset($darkMode) && $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
            <h3 class="text-lg font-semibold mb-4 <?php echo isset($darkMode) && $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                <?php echo $lang['downloads_by_file_type'] ?? 'Downloads by File Type'; ?>
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="relative" style="height: 300px;">
                    <canvas id="fileTypeChart"></canvas>
                </div>
                <div class="md:col-span-2 overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 <?php echo isset($darkMode) && $darkMode ? 'divide-gray-700' : ''; ?>">
                        <thead class="<?php echo isset($darkMode) && $darkMode ? 'bg-gray-700' : 'bg-gray-50'; ?>">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium <?php echo isset($darkMode) && $darkMode ? 'text-gray-400' : 'text-gray-500'; ?> uppercase tracking-wider">
                                    <?php echo $lang['file_type'] ?? 'File Type'; ?>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium <?php echo isset($darkMode) && $darkMode ? 'text-gray-400' : 'text-gray-500'; ?> uppercase tracking-wider">
                                    <?php echo $lang['downloads'] ?? 'Downloads'; ?>
                                </th>
                                <th class="px-6 py-3 text-left text-xs font-medium <?php echo isset($darkMode) && $darkMode ? 'text-gray-400' : 'text-gray-500'; ?> uppercase tracking-wider">
                                    <?php echo $lang['percentage'] ?? 'Percentage'; ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="<?php echo isset($darkMode) && $darkMode ? 'bg-gray-800 divide-gray-700' : 'bg-white divide-gray-200'; ?>">
                            <?php
                            // Calculate total downloads
                            $totalDownloads = 0;
                            foreach ($fileTypeStats as $stat) {
                                $totalDownloads += $stat['download_count'];
                            }
                            
                            if (count($fileTypeStats) > 0) {
                                foreach ($fileTypeStats as $stat) {
                                    $percentage = $totalDownloads > 0 ? round(($stat['download_count'] / $totalDownloads) * 100, 1) : 0;
                                    ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                            <?php echo strtoupper(htmlspecialchars($stat['file_type'] ?: 'Unknown')); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                            <?php echo number_format($stat['download_count']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <span class="text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                                    <?php echo $percentage; ?>%
                                                </span>
                                                <div class="ml-2 w-32 bg-gray-200 rounded-full h-2.5 <?php echo isset($darkMode) && $darkMode ? 'bg-gray-700' : ''; ?>">
                                                    <div class="bg-blue-600 h-2.5 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php
                                }
                            } else {
                                ?>
                                <tr>
                                    <td colspan="3" class="px-6 py-4 text-center text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                        <?php echo $lang['no_data_available'] ?? 'No data available for selected period'; ?>
                                    </td>
                                </tr>
                                <?php
                            }
                            
                            // Prepare data for chart
                            $fileTypeLabels = [];
                            $fileTypeValues = [];
                            foreach ($fileTypeStats as $stat) {
                                $fileTypeLabels[] = strtoupper($stat['file_type'] ?: 'Unknown');
                                $fileTypeValues[] = $stat['download_count'];
                            }
                            ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Top Content Items Table -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6 <?php echo isset($darkMode) && $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
            <h3 class="text-lg font-semibold mb-4 <?php echo isset($darkMode) && $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                <?php echo $lang['popular_content'] ?? 'Popular Content'; ?>
            </h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 <?php echo isset($darkMode) && $darkMode ? 'divide-gray-700' : ''; ?>">
                    <thead class="<?php echo isset($darkMode) && $darkMode ? 'bg-gray-700' : 'bg-gray-50'; ?>">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo isset($darkMode) && $darkMode ? 'text-gray-400' : 'text-gray-500'; ?> uppercase tracking-wider">
                                <?php echo $lang['title'] ?? 'Title'; ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo isset($darkMode) && $darkMode ? 'text-gray-400' : 'text-gray-500'; ?> uppercase tracking-wider">
                                <?php echo $lang['category'] ?? 'Category'; ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo isset($darkMode) && $darkMode ? 'text-gray-400' : 'text-gray-500'; ?> uppercase tracking-wider">
                                <?php echo $lang['type'] ?? 'Type'; ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo isset($darkMode) && $darkMode ? 'text-gray-400' : 'text-gray-500'; ?> uppercase tracking-wider">
                                <?php echo $lang['downloads'] ?? 'Downloads'; ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo isset($darkMode) && $darkMode ? 'text-gray-400' : 'text-gray-500'; ?> uppercase tracking-wider">
                                <?php echo $lang['date_added'] ?? 'Date Added'; ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="<?php echo isset($darkMode) && $darkMode ? 'bg-gray-800 divide-gray-700' : 'bg-white divide-gray-200'; ?>">
                        <?php if (isset($contentItems) && count($contentItems) > 0): ?>
                            <?php foreach ($contentItems as $item): ?>
                                <tr class="<?php echo isset($darkMode) && $darkMode ? 'hover:bg-gray-700' : 'hover:bg-gray-50'; ?>">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                        <?php echo htmlspecialchars($item['title']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                        <?php echo htmlspecialchars($item['category_name'] ?: 'Uncategorized'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php
                                            switch (strtolower($item['file_type'])) {
                                                case 'jpg':
                                                case 'jpeg':
                                                case 'png':
                                                case 'gif':
                                                    echo 'bg-blue-100 text-blue-800';
                                                    break;
                                                case 'mp4':
                                                case 'mov':
                                                case 'avi':
                                                    echo 'bg-purple-100 text-purple-800';
                                                    break;
                                                case 'svg':
                                                    echo 'bg-green-100 text-green-800';
                                                    break;
                                                default:
                                                    echo 'bg-gray-100 text-gray-800';
                                            }
                                            if (isset($darkMode) && $darkMode) {
                                                switch (strtolower($item['file_type'])) {
                                                    case 'jpg':
                                                    case 'jpeg':
                                                    case 'png':
                                                    case 'gif':
                                                        echo ' bg-blue-900 text-blue-200';
                                                        break;
                                                    case 'mp4':
                                                    case 'mov':
                                                    case 'avi':
                                                        echo ' bg-purple-900 text-purple-200';
                                                        break;
                                                    case 'svg':
                                                        echo ' bg-green-900 text-green-200';
                                                        break;
                                                    default:
                                                        echo ' bg-gray-700 text-gray-300';
                                                }
                                            }
                                            ?>">
                                            <?php echo strtoupper(htmlspecialchars($item['file_type'] ?: 'Unknown')); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                        <?php echo number_format($item['download_count']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                        <?php echo date('Y-m-d', strtotime($item['created_at'])); ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    <?php echo $lang['no_content_found'] ?? 'No content found for the selected period.'; ?>
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
                    <?php echo number_format(min($page * $perPage, $totalCount)); ?> 
                    <?php echo $lang['of'] ?? 'of'; ?> 
                    <?php echo number_format($totalCount); ?> 
                    <?php echo $lang['items'] ?? 'items'; ?>
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
    
    // File Type Chart
    const fileTypeCtx = document.getElementById('fileTypeChart').getContext('2d');
    const fileTypeChart = new Chart(fileTypeCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($fileTypeLabels); ?>,
            datasets: [{
                data: <?php echo json_encode($fileTypeValues); ?>,
                backgroundColor: [
                    'rgba(59, 130, 246, 0.7)',
                    'rgba(16, 185, 129, 0.7)',
                    'rgba(245, 158, 11, 0.7)',
                    'rgba(239, 68, 68, 0.7)',
                    'rgba(139, 92, 246, 0.7)',
                    'rgba(236, 72, 153, 0.7)',
                    'rgba(168, 85, 247, 0.7)',
                    'rgba(59, 130, 246, 0.5)',
                    'rgba(16, 185, 129, 0.5)',
                    'rgba(245, 158, 11, 0.5)'
                ],
                borderColor: [
                    'rgba(59, 130, 246, 1)',
                    'rgba(16, 185, 129, 1)',
                    'rgba(245, 158, 11, 1)',
                    'rgba(239, 68, 68, 1)',
                    'rgba(139, 92, 246, 1)',
                    'rgba(236, 72, 153, 1)',
                    'rgba(168, 85, 247, 1)',
                    'rgba(59, 130, 246, 0.8)',
                    'rgba(16, 185, 129, 0.8)',
                    'rgba(245, 158, 11, 0.8)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'right',
                    labels: {
                        color: '<?php echo isset($darkMode) && $darkMode ? "rgba(255, 255, 255, 0.8)" : "rgba(0, 0, 0, 0.8)"; ?>',
                        font: {
                            size: 10
                        },
                        boxWidth: 12
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