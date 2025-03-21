<?php
// Set page title
$pageTitle = 'System Reports - WallPix Admin';

// Include header
require_once '../../theme/admin/header.php';

// Current date and time in UTC
$currentDateTime = '2025-03-18 11:39:59';
$currentUser = 'mahranalsarminy';

// Server information
$serverInfo = [
    'operating_system' => PHP_OS,
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'database_version' => '',
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time') . ' seconds',
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size')
];

// Database version
try {
    $stmt = $pdo->query("SELECT VERSION() as version");
    $result = $stmt->fetch();
    $serverInfo['database_version'] = $result['version'];
} catch (PDOException $e) {
    $serverInfo['database_version'] = 'Error retrieving database version';
}

// Get system statistics
try {
    // Total users
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
    $totalUsers = $stmt->fetch()['total'];
    
    // Total media items
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM media");
    $totalMedia = $stmt->fetch()['total'];
    
    // Total categories
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM categories");
    $totalCategories = $stmt->fetch()['total'];
    
    // Total downloads
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM downloads");
    $totalDownloads = $stmt->fetch()['total'];
    
    // Total storage used
    $stmt = $pdo->query("SELECT SUM(file_size) as total_size FROM media");
    $totalStorageBytes = $stmt->fetch()['total_size'] ?: 0;
    
    // Convert bytes to readable format
    function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    $totalStorage = formatBytes($totalStorageBytes);
    
    // Get table sizes
    $stmt = $pdo->query("
        SELECT 
            table_name, 
            ROUND((data_length + index_length) / 1024 / 1024, 2) as size_mb
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
        ORDER BY (data_length + index_length) DESC
    ");
    $tableSizes = $stmt->fetchAll();
    
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
                    <?php echo $lang['system_reports'] ?? 'System Reports'; ?>
                </h1>
                <p class="mt-2 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                    <?php echo $lang['system_information_and_statistics'] ?? 'System information and database statistics'; ?>
                </p>
            </div>
            <div class="mt-4 md:mt-0 flex space-x-2">
                <a href="index.php" class="btn bg-gray-500 hover:bg-gray-600 text-white">
                    <i class="fas fa-arrow-left mr-2"></i> <?php echo $lang['back_to_dashboard'] ?? 'Back to Dashboard'; ?>
                </a>
                <a href="export-system.php" class="btn bg-green-500 hover:bg-green-600 text-white">
                    <i class="fas fa-file-excel mr-2"></i> <?php echo $lang['export_csv'] ?? 'Export CSV'; ?>
                </a>
            </div>
        </div>
        
        <!-- System Summary -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Users Card -->
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-500 bg-opacity-10 text-blue-500">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                            <?php echo $lang['total_users'] ?? 'Total Users'; ?>
                        </h3>
                    </div>
                </div>
                <div class="mt-4 text-center">
                    <p class="text-3xl font-bold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                        <?php echo number_format($totalUsers); ?>
                    </p>
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
                            <?php echo $lang['total_media'] ?? 'Total Media'; ?>
                        </h3>
                    </div>
                </div>
                <div class="mt-4 text-center">
                    <p class="text-3xl font-bold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                        <?php echo number_format($totalMedia); ?>
                    </p>
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
                            <?php echo $lang['total_downloads'] ?? 'Total Downloads'; ?>
                        </h3>
                    </div>
                </div>
                <div class="mt-4 text-center">
                    <p class="text-3xl font-bold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                        <?php echo number_format($totalDownloads); ?>
                    </p>
                </div>
            </div>
            
            <!-- Storage Card -->
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-500 bg-opacity-10 text-yellow-500">
                        <i class="fas fa-database text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                            <?php echo $lang['storage_used'] ?? 'Storage Used'; ?>
                        </h3>
                    </div>
                </div>
                <div class="mt-4 text-center">
                    <p class="text-3xl font-bold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                        <?php echo $totalStorage; ?>
                    </p>
                </div>
            </div>
        </div>
        <!-- Server Information -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
            <h3 class="text-lg font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                <?php echo $lang['server_information'] ?? 'Server Information'; ?>
            </h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-700' : ''; ?>">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                <?php echo $lang['parameter'] ?? 'Parameter'; ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                <?php echo $lang['value'] ?? 'Value'; ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-700' : ''; ?>">
                        <?php foreach ($serverInfo as $key => $value): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                    <?php echo ucwords(str_replace('_', ' ', $key)); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                    <?php echo htmlspecialchars($value); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Database Tables -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
            <h3 class="text-lg font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                <?php echo $lang['database_tables'] ?? 'Database Tables'; ?>
            </h3>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-700' : ''; ?>">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                <?php echo $lang['table_name'] ?? 'Table Name'; ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                <?php echo $lang['size'] ?? 'Size (MB)'; ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-700' : ''; ?>">
                        <?php foreach ($tableSizes as $table): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                    <?php echo htmlspecialchars($table['table_name']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                    <?php echo number_format($table['size_mb'], 2); ?> MB
                                    <div class="w-32 mt-1 bg-gray-200 rounded-full h-1.5 <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                                        <div class="bg-blue-600 h-1.5 rounded-full" style="width: <?php echo min(100, ($table['size_mb'] / 10) * 100); ?>%"></div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- PHP Extensions -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
            <h3 class="text-lg font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                <?php echo $lang['php_extensions'] ?? 'PHP Extensions'; ?>
            </h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <?php 
                $requiredExtensions = [
                    'mysqli' => 'MySQLi',
                    'pdo' => 'PDO',
                    'pdo_mysql' => 'PDO MySQL',
                    'gd' => 'GD',
                    'json' => 'JSON',
                    'curl' => 'cURL',
                    'mbstring' => 'Multibyte String',
                    'fileinfo' => 'Fileinfo',
                    'zip' => 'ZIP',
                    'intl' => 'Intl',
                    'xml' => 'XML',
                    'exif' => 'EXIF'
                ];
                
                foreach ($requiredExtensions as $ext => $name): 
                    $isLoaded = extension_loaded($ext);
                ?>
                    <div class="p-3 rounded-md <?php echo $isLoaded ? ($darkMode ? 'bg-green-900' : 'bg-green-100') : ($darkMode ? 'bg-red-900' : 'bg-red-100'); ?>">
                        <div class="flex items-center">
                            <div class="mr-2">
                                <?php if ($isLoaded): ?>
                                    <i class="fas fa-check-circle text-green-500"></i>
                                <?php else: ?>
                                    <i class="fas fa-times-circle text-red-500"></i>
                                <?php endif; ?>
                            </div>
                            <span class="text-sm font-medium <?php echo $darkMode ? 'text-gray-200' : 'text-gray-800'; ?>">
                                <?php echo $name; ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        <!-- System Status -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
            <h3 class="text-lg font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                <?php echo $lang['system_status'] ?? 'System Status'; ?>
            </h3>
            
            <?php
            // Check for common issues
            $issues = [];
            
            // Check PHP version
            if (version_compare(PHP_VERSION, '7.4.0', '<')) {
                $issues[] = [
                    'type' => 'warning',
                    'message' => 'PHP version is below recommended (7.4 or higher)'
                ];
            }
            
            // Check memory limit
            $memory_limit = ini_get('memory_limit');
            if (preg_match('/^(\d+)(.)$/', $memory_limit, $matches)) {
                if ($matches[2] == 'M') {
                    $memory_limit_mb = $matches[1];
                    if ($memory_limit_mb < 128) {
                        $issues[] = [
                            'type' => 'warning',
                            'message' => 'Memory limit is low (' . $memory_limit . '). Recommend at least 128M.'
                        ];
                    }
                }
            }
            
            // Check file uploads
            if (!ini_get('file_uploads')) {
                $issues[] = [
                    'type' => 'error',
                    'message' => 'File uploads are disabled in PHP configuration'
                ];
            }
            
            // Check max upload size
            $max_upload = (int)(ini_get('upload_max_filesize'));
            if ($max_upload < 8) {
                $issues[] = [
                    'type' => 'warning',
                    'message' => 'Maximum upload size is low (' . ini_get('upload_max_filesize') . '). Recommend at least 8M.'
                ];
            }
            
            // Check GD library
            if (!extension_loaded('gd')) {
                $issues[] = [
                    'type' => 'error',
                    'message' => 'GD library is not installed. Required for image processing.'
                ];
            }
            
            // Display issues if any
            if (count($issues) > 0) {
                echo '<div class="mb-4">';
                foreach ($issues as $issue) {
                    $alertClass = ($issue['type'] == 'error') ? 
                        ($darkMode ? 'bg-red-900 text-red-200' : 'bg-red-100 text-red-800') : 
                        ($darkMode ? 'bg-yellow-900 text-yellow-200' : 'bg-yellow-100 text-yellow-800');
                    
                    $icon = ($issue['type'] == 'error') ? 'fa-exclamation-circle' : 'fa-exclamation-triangle';
                    
                    echo '<div class="p-4 mb-2 rounded-md ' . $alertClass . '">';
                    echo '<div class="flex items-center">';
                    echo '<div class="flex-shrink-0">';
                    echo '<i class="fas ' . $icon . '"></i>';
                    echo '</div>';
                    echo '<div class="ml-3">';
                    echo '<p class="text-sm">' . $issue['message'] . '</p>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
                echo '</div>';
            } else {
                // All good
                echo '<div class="p-4 mb-4 rounded-md ' . ($darkMode ? 'bg-green-900 text-green-200' : 'bg-green-100 text-green-800') . '">';
                echo '<div class="flex items-center">';
                echo '<div class="flex-shrink-0">';
                echo '<i class="fas fa-check-circle"></i>';
                echo '</div>';
                echo '<div class="ml-3">';
                echo '<p class="text-sm">All system checks passed. No issues detected.</p>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
            ?>
            
            <div class="mt-6 text-right text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                <?php echo $lang['last_updated'] ?? 'Last Updated'; ?>: 
                <?php echo $currentDateTime; // Using the timestamp you provided: 2025-03-18 11:44:56 ?>
                | <?php echo $lang['user'] ?? 'User'; ?>: 
                <?php echo htmlspecialchars($currentUser); // Using the username you provided: mahranalsarminy ?>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../../theme/admin/footer.php';
?>        