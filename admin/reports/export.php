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
header('Content-Disposition: attachment; filename="wallpix-report-' . $startDate . '-to-' . $endDate . '.csv"');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel compatibility
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write header row
fputcsv($output, [
    'Report Type',
    'Date Range',
    'Generated On',
    'Generated By',
    'Total Users',
    'New Users',
    'Total Media',
    'New Media',
    'Total Downloads',
    'Period Downloads',
    'Period Revenue',
    'Active Subscriptions'
]);

// Get data for report
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
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM downloads");
    $totalDownloads = $stmt->fetch()['total'];
    
    // Downloads in selected period
    $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM downloads WHERE download_date BETWEEN ? AND ?");
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    $periodDownloads = $stmt->fetch()['total'];
    
    // Revenue in selected period
    $stmt = $pdo->prepare("SELECT SUM(amount) as total FROM payments WHERE payment_date BETWEEN ? AND ? AND status = 'completed'");
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    $periodRevenue = $stmt->fetch()['total'] ?: 0;
    
    // Active subscriptions
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM subscriptions WHERE status = 'active'");
    $activeSubscriptions = $stmt->fetch()['total'];
    
    // Write summary data
    fputcsv($output, [
        'Summary',
        $startDate . ' to ' . $endDate,
        date('Y-m-d H:i:s'), // Current date time
        $_SESSION['username'], // Current user
        $totalUsers,
        $newUsers,
        $totalMedia,
        $newMedia,
        $totalDownloads,
        $periodDownloads,
        $periodRevenue,
        $activeSubscriptions
    ]);
    
    // Add a blank row
    fputcsv($output, []);
    
    // Write daily statistics
    fputcsv($output, ['Daily Statistics', '', '', '', '', '', '', '', '', '', '']);
    fputcsv($output, ['Date', 'New Users', 'New Media', 'Downloads', 'Revenue']);
    
    // Get daily stats
    $sql = "SELECT 
        DATE(a.date) as date, 
        COALESCE(u.user_count, 0) as new_users,
        COALESCE(m.media_count, 0) as new_media,
        COALESCE(d.download_count, 0) as downloads,
        COALESCE(p.revenue, 0) as revenue
    FROM (
        SELECT DATE(?) + INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY as date
        FROM (SELECT 0 as a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) a
        CROSS JOIN (SELECT 0 as a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) b
        CROSS JOIN (SELECT 0 as a UNION SELECT 1 UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5 UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9) c
        WHERE DATE(?) + INTERVAL (a.a + (10 * b.a) + (100 * c.a)) DAY <= ?
    ) as a
    LEFT JOIN (
        SELECT DATE(created_at) as date, COUNT(*) as user_count 
        FROM users 
        WHERE created_at BETWEEN ? AND ? 
        GROUP BY DATE(created_at)
    ) u ON a.date = u.date
    LEFT JOIN (
                SELECT DATE(created_at) as date, COUNT(*) as media_count 
        FROM media 
        WHERE created_at BETWEEN ? AND ? 
        GROUP BY DATE(created_at)
    ) m ON a.date = m.date
    LEFT JOIN (
        SELECT DATE(download_date) as date, COUNT(*) as download_count 
        FROM downloads 
        WHERE download_date BETWEEN ? AND ? 
        GROUP BY DATE(download_date)
    ) d ON a.date = d.date
    LEFT JOIN (
        SELECT DATE(payment_date) as date, SUM(amount) as revenue 
        FROM payments 
        WHERE payment_date BETWEEN ? AND ? AND status = 'completed'
        GROUP BY DATE(payment_date)
    ) p ON a.date = p.date
    ORDER BY a.date ASC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $startDate, 
        $startDate, 
        $endDate,
        $startDate . ' 00:00:00', 
        $endDate . ' 23:59:59',
        $startDate . ' 00:00:00', 
        $endDate . ' 23:59:59',
        $startDate . ' 00:00:00', 
        $endDate . ' 23:59:59'
    ]);
    $dailyStats = $stmt->fetchAll();

    // Write daily stats
    foreach ($dailyStats as $stat) {
        fputcsv($output, [
            $stat['date'],
            $stat['new_users'],
            $stat['new_media'],
            $stat['downloads'],
            $stat['revenue']
        ]);
    }
    // Add a blank row
    fputcsv($output, []);
    
    // Write top users
    fputcsv($output, ['Top Users by Downloads', '', '', '', '', '', '', '', '', '', '']);
    fputcsv($output, ['Username', 'Email', 'Role', 'Country', 'Downloads', 'Registration Date']);
    
    // Get top users
    $stmt = $pdo->prepare("
        SELECT 
            u.username,
            u.email,
            u.role,
            u.country,
            COUNT(d.id) as download_count,
            u.created_at
        FROM users u
        JOIN downloads d ON u.id = d.user_id AND d.download_date BETWEEN ? AND ?
        GROUP BY u.id, u.username, u.email, u.role, u.country, u.created_at
        ORDER BY download_count DESC
        LIMIT 50
    ");
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    $topUsers = $stmt->fetchAll();
    
    // Write top users
    foreach ($topUsers as $user) {
        fputcsv($output, [
            $user['username'],
            $user['email'],
            $user['role'],
            $user['country'] ?: 'Unknown',
            $user['download_count'],
            date('Y-m-d', strtotime($user['created_at']))
        ]);
    }
    // Add a blank row
    fputcsv($output, []);
    
    // Write regional data
    fputcsv($output, ['Regional Distribution', '', '', '', '', '', '', '', '', '', '']);
    fputcsv($output, ['Country', 'Users', 'Downloads', 'Revenue']);
    
    // Get regional data
    $stmt = $pdo->prepare("
        SELECT 
            u.country,
            COUNT(DISTINCT u.id) as user_count,
            COUNT(d.id) as download_count,
            SUM(p.amount) as revenue
        FROM users u
        LEFT JOIN downloads d ON u.id = d.user_id AND d.download_date BETWEEN ? AND ?
        LEFT JOIN payments p ON u.id = p.user_id AND p.payment_date BETWEEN ? AND ? AND p.status = 'completed'
        WHERE u.country IS NOT NULL AND u.country != ''
        GROUP BY u.country
        ORDER BY user_count DESC
    ");
    $stmt->execute([
        $startDate . ' 00:00:00', 
        $endDate . ' 23:59:59',
        $startDate . ' 00:00:00', 
        $endDate . ' 23:59:59'
    ]);
    $regions = $stmt->fetchAll();
    
    // Write regional data
    foreach ($regions as $region) {
        fputcsv($output, [
            $region['country'],
            $region['user_count'],
            $region['download_count'],
            $region['revenue'] ?: '0'
        ]);
    }
    
    // Add a blank row
    fputcsv($output, []);
    
    // Write report metadata
    fputcsv($output, ['Report Metadata', '', '', '', '', '', '', '', '', '', '']);
    fputcsv($output, ['Report Generated', date('Y-m-d H:i:s')]);  // Current timestamp: 2025-03-18 11:31:16
    fputcsv($output, ['Generated By', $_SESSION['username'] ?: 'mahranalsarminy']);  // Current username: mahranalsarminy
    fputcsv($output, ['Period', $startDate . ' to ' . $endDate]);
    fputcsv($output, ['Report Type', 'Full Analytics Export']);
    fputcsv($output, ['Website', 'WallPix.Top']);
    fputcsv($output, ['Version', '1.0.0']);
    
} catch (PDOException $e) {
    // Handle error - write error to the CSV
    fputcsv($output, ['Error occurred while generating report: ' . $e->getMessage()]);
}

// Close the file pointer
fclose($output);
exit;
?>    