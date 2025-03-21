<?php
// Include necessary files
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';

// Verify admin is logged in
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header('Location: ../login.php');
    exit;
}

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="wallpix-system-report-' . date('Y-m-d') . '.csv"');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Server information
$serverInfo = [
    'operating_system' => PHP_OS,
    'php_version' => PHP_VERSION,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time') . ' seconds',
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_input_time' => ini_get('max_input_time') . ' seconds',
    'display_errors' => ini_get('display_errors'),
    'file_uploads' => ini_get('file_uploads') === '1' ? 'Enabled' : 'Disabled',
    'session_name' => ini_get('session.name')
];

// Database version
try {
    $stmt = $pdo->query("SELECT VERSION() as version");
    $result = $stmt->fetch();
    $serverInfo['database_version'] = $result['version'];
} catch (PDOException $e) {
    $serverInfo['database_version'] = 'Error retrieving database version';
}

// Write header row - Server Information
fputcsv($output, ['Server Information', '', '']);
fputcsv($output, ['Parameter', 'Value', '']);

// Write server information
foreach ($serverInfo as $key => $value) {
    fputcsv($output, [ucwords(str_replace('_', ' ', $key)), $value, '']);
}

// Add a blank row
fputcsv($output, []);

try {
    // Database Statistics
    fputcsv($output, ['Database Statistics', '', '']);
    
    // Get table sizes
    $stmt = $pdo->query("
        SELECT 
            table_name, 
            table_rows,
            data_length,
            index_length,
            ROUND((data_length + index_length) / 1024 / 1024, 2) as size_mb,
            engine,
            table_collation,
            create_time,
            update_time
        FROM information_schema.tables
        WHERE table_schema = DATABASE()
        ORDER BY (data_length + index_length) DESC
    ");
    $tables = $stmt->fetchAll();
    
    fputcsv($output, ['Table Name', 'Rows', 'Size (MB)', 'Engine', 'Collation', 'Created', 'Last Updated']);
    
    $totalRows = 0;
    $totalSizeMB = 0;
    
    foreach ($tables as $table) {
        $totalRows += $table['table_rows'];
        $totalSizeMB += $table['size_mb'];
        
        fputcsv($output, [
            $table['table_name'],
            $table['table_rows'],
            $table['size_mb'],
            $table['engine'],
            $table['table_collation'],
            $table['create_time'],
            $table['update_time'] ?: 'N/A'
        ]);
    }
    
    // Add summary row
    fputcsv($output, ['TOTAL', $totalRows, $totalSizeMB, '', '', '', '']);
    
    // Add a blank row
    fputcsv($output, []);
    
    // PHP Extensions
    fputcsv($output, ['PHP Extensions', '', '']);
    fputcsv($output, ['Extension', 'Status', '']);
    
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
    
    foreach ($requiredExtensions as $ext => $name) {
        $status = extension_loaded($ext) ? 'Loaded' : 'Not Loaded';
        fputcsv($output, [$name, $status, '']);
    }
    
    // Add a blank row
    fputcsv($output, []);
    
    // System Metrics
    fputcsv($output, ['System Metrics', '', '']);
    
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
    $totalStorageMB = round($totalStorageBytes / 1024 / 1024, 2);
    
    fputcsv($output, ['Total Users', $totalUsers, '']);
    fputcsv($output, ['Total Media Items', $totalMedia, '']);
    fputcsv($output, ['Total Categories', $totalCategories, '']);
    fputcsv($output, ['Total Downloads', $totalDownloads, '']);
    fputcsv($output, ['Total Storage Used (MB)', $totalStorageMB, '']);
        // Add a blank row
    fputcsv($output, []);
    
    // Activity Logs
    fputcsv($output, ['Recent System Activity', '', '', '', '', '']);
    fputcsv($output, ['Type', 'User', 'Action', 'IP Address', 'User Agent', 'Timestamp']);
    
    // Get recent activity logs
    $stmt = $pdo->query("
        SELECT
            l.log_type,
            u.username,
            l.action,
            l.ip_address,
            l.user_agent,
            l.created_at
        FROM activity_logs l
        LEFT JOIN users u ON l.user_id = u.id
        ORDER BY l.created_at DESC
        LIMIT 50
    ");
    $logs = $stmt->fetchAll();
    
    foreach ($logs as $log) {
        fputcsv($output, [
            $log['log_type'],
            $log['username'] ?: 'System',
            $log['action'],
            $log['ip_address'],
            $log['user_agent'],
            $log['created_at']
        ]);
    }
    // Add a blank row
    fputcsv($output, []);
    
    // Error Logs
    fputcsv($output, ['Recent Error Logs', '', '', '', '', '']);
    fputcsv($output, ['Error Level', 'Message', 'File', 'Line', 'IP Address', 'Timestamp']);
    
    // Get recent error logs
    $stmt = $pdo->query("
        SELECT
            error_level,
            message,
            file,
            line,
            ip_address,
            created_at
        FROM error_logs
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $errors = $stmt->fetchAll();
    
    foreach ($errors as $error) {
        fputcsv($output, [
            $error['error_level'],
            $error['message'],
            $error['file'],
            $error['line'],
            $error['ip_address'],
            $error['created_at']
        ]);
    }
    
    // Add a blank row
    fputcsv($output, []);
    // Cache Status
    fputcsv($output, ['Cache Status', '', '']);
    fputcsv($output, ['Cache Type', 'Status', 'Size']);
    
    // Check if cache directories exist and are writable
    $cacheDirectories = [
        'Page Cache' => [
            'path' => '../../cache/pages',
            'status' => 'N/A',
            'size' => 0
        ],
        'Image Cache' => [
            'path' => '../../cache/images',
            'status' => 'N/A',
            'size' => 0
        ],
        'API Cache' => [
            'path' => '../../cache/api',
            'status' => 'N/A',
            'size' => 0
        ]
    ];
    
    // Function to get directory size
    function getDirSize($dir) {
        $size = 0;
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object)) {
                        $size += getDirSize($dir . "/" . $object);
                    } else {
                        $size += filesize($dir . "/" . $object);
                    }
                }
            }
        }
        return $size;
    }
    
    // Function to format bytes
    function formatBytes($bytes, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
    
    foreach ($cacheDirectories as $name => &$cache) {
        if (is_dir($cache['path'])) {
            $isWritable = is_writable($cache['path']);
            $cache['status'] = $isWritable ? 'Writable' : 'Not Writable';
            $cache['size'] = formatBytes(getDirSize($cache['path']));
        } else {
            $cache['status'] = 'Directory Not Found';
            $cache['size'] = '0 B';
        }
        fputcsv($output, [$name, $cache['status'], $cache['size']]);
    }
    // Add a blank row
    fputcsv($output, []);
    
    // Report Metadata
    fputcsv($output, ['Report Metadata', '', '']);
    fputcsv($output, ['Generated On', date('Y-m-d H:i:s'), '']); // Current timestamp: 2025-03-18 12:43:59
    fputcsv($output, ['Generated By', $_SESSION['username'] ?: 'mahranalsarminy', '']); // Current username: mahranalsarminy
    fputcsv($output, ['Server Time', date('Y-m-d H:i:s'), '']);
    fputcsv($output, ['PHP Version', PHP_VERSION, '']);
    fputcsv($output, ['MySQL Version', $serverInfo['database_version'], '']);
    fputcsv($output, ['WallPix Version', '1.0.0', '']);
    
} catch (PDOException $e) {
    // Handle error - write error to the CSV
    fputcsv($output, ['Error occurred while generating report: ' . $e->getMessage()]);
}

// Close the file pointer
fclose($output);
exit;
?>    