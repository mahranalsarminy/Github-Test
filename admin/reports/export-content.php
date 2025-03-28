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

// Get parameters
$period = isset($_GET['period']) ? $_GET['period'] : 'month';
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

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="wallpix-content-report-' . $startDate . '-to-' . $endDate . '.csv"');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write header row
fputcsv($output, [
    'Title',
    'Description',
    'Category',
    'File Type',
    'File Size',
    'Downloads',
    'Upload Date',
    'Uploaded By',
    'Featured',
    'Status'
]);

// Get content data
try {
    $stmt = $pdo->prepare("
        SELECT 
            m.id,
            m.title,
            m.description,
            c.name as category_name,
            m.file_type,
            m.file_size,
            COUNT(d.id) as download_count,
            m.created_at,
            u.username as uploader,
            m.is_featured,
            m.status
        FROM media m
        LEFT JOIN downloads d ON m.id = d.media_id AND d.download_date BETWEEN ? AND ?
        LEFT JOIN categories c ON m.category_id = c.id
        LEFT JOIN users u ON m.user_id = u.id
        WHERE m.created_at BETWEEN ? AND ?
        GROUP BY m.id, m.title, m.description, c.name, m.file_type, m.file_size, m.created_at, u.username, m.is_featured, m.status
        ORDER BY download_count DESC
    ");
    $stmt->execute([
        $startDate . ' 00:00:00', 
        $endDate . ' 23:59:59',
        $startDate . ' 00:00:00', 
        $endDate . ' 23:59:59'
    ]);
    
    // Write data rows
    while ($row = $stmt->fetch()) {
        // Format file size
        $fileSizeFormatted = '';
        if ($row['file_size']) {
            $fileSize = $row['file_size'];
            $units = ['B', 'KB', 'MB', 'GB'];
            $i = 0;
            while ($fileSize >= 1024 && $i < count($units) - 1) {
                $fileSize /= 1024;
                $i++;
            }
            $fileSizeFormatted = round($fileSize, 2) . ' ' . $units[$i];
        }
        
        // Format status and featured
        $status = ucfirst($row['status']);
        $featured = $row['is_featured'] ? 'Yes' : 'No';
        
        fputcsv($output, [
            $row['title'],
            $row['description'],
            $row['category_name'] ?: 'Uncategorized',
            strtoupper($row['file_type']),
            $fileSizeFormatted,
            $row['download_count'],
            date('Y-m-d', strtotime($row['created_at'])),
            $row['uploader'],
            $featured,
            $status
        ]);
    }
    
    // Add a blank row
    fputcsv($output, []);
    
    // Add summary
    fputcsv($output, ['Report Summary', '', '', '', '', '', '', '', '', '']);
    fputcsv($output, ['Date Range', $startDate . ' to ' . $endDate, '', '', '', '', '', '', '', '']);
    fputcsv($output, ['Generated On', date('Y-m-d H:i:s'), '', '', '', '', '', '', '', '']); // Current timestamp: 2025-03-18 12:16:55
    fputcsv($output, ['Generated By', $_SESSION['username'] ?: 'mahranalsarminy', '', '', '', '', '', '', '', '']); // Current username: mahranalsarminy
    
    // Get total media stats
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) as total_media,
            SUM(CASE WHEN m.created_at BETWEEN ? AND ? THEN 1 ELSE 0 END) as new_media,
            SUM(CASE WHEN m.is_featured = 1 THEN 1 ELSE 0 END) as featured_media,
            COUNT(DISTINCT m.file_type) as format_count
        FROM media m
    ");
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    $summary = $stmt->fetch();
    
    fputcsv($output, ['Total Media', $summary['total_media'], '', '', '', '', '', '', '', '']);
    fputcsv($output, ['New Media (Period)', $summary['new_media'], '', '', '', '', '', '', '', '']);
    fputcsv($output, ['Featured Media', $summary['featured_media'], '', '', '', '', '', '', '', '']);
    fputcsv($output, ['Format Count', $summary['format_count'], '', '', '', '', '', '', '', '']);
    
    // Get file type distribution
    $stmt = $pdo->prepare("
        SELECT 
            file_type, 
            COUNT(*) as count 
        FROM media 
        GROUP BY file_type 
        ORDER BY count DESC
    ");
    $stmt->execute();
    $fileTypes = $stmt->fetchAll();
    
    // Add a blank row
    fputcsv($output, []);
    fputcsv($output, ['File Type Distribution', '', '', '', '', '', '', '', '', '']);
    
    foreach ($fileTypes as $type) {
        fputcsv($output, [
            strtoupper($type['file_type']), 
            $type['count'], 
            '', '', '', '', '', '', '', ''
        ]);
    }
    
} catch (PDOException $e) {
    // Handle error - write error to the CSV
    fputcsv($output, ['Error occurred while generating report: ' . $e->getMessage()]);
}

// Close the file pointer
fclose($output);
exit;
?>