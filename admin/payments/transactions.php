<?php
/**
 * Payment Transactions Management Page
 * 
 * Allows administrators to view, filter, search, and manage payment transactions
 * 
 * @package Admin
 * @subpackage Payments
 */

// Include necessary files
// Enable error display
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define the project root directory
define('ROOT_DIR', dirname(dirname(__DIR__)));

// Include the centralized initialization file
require_once ROOT_DIR . '/includes/init.php';

// Ensure only admins can access this page
require_admin();

// Set page title and include header
$pageTitle = $lang['payment_transactions'] ?? 'Payment Transactions';
include '../../theme/admin/header.php';


// Set default values for filters
$statusFilter = isset($_GET['status']) ? $_GET['status'] : '';
$methodFilter = isset($_GET['method']) ? $_GET['method'] : '';
$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : '';
$searchQuery = isset($_GET['search']) ? $_GET['search'] : '';

// Set default sorting
$sortBy = isset($_GET['sort']) ? $_GET['sort'] : 'created_at';
$sortDir = isset($_GET['dir']) && $_GET['dir'] == 'asc' ? 'asc' : 'desc';

// Pagination settings
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 15;
$offset = ($page - 1) * $perPage;

// Check for action parameters
$action = isset($_GET['action']) ? $_GET['action'] : '';
$transactionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle actions (update status)
$actionMessage = '';
$actionType = '';

if ($action == 'update' && !empty($transactionId) && isset($_POST['status'])) {
    try {
        // Update transaction status
        $stmt = $pdo->prepare("UPDATE payments SET status = :status, updated_at = NOW() WHERE id = :id");
        $stmt->execute([
            ':status' => $_POST['status'],
            ':id' => $transactionId
        ]);
        
        // Log the action
        logAdminAction('Updated payment status: ID #' . $transactionId . ' to ' . $_POST['status']);
        
        // Set success message
        $actionMessage = sprintf($lang['transaction_updated'] ?? 'Transaction #%d updated successfully', $transactionId);
        $actionType = 'success';
        
    } catch (PDOException $e) {
        // Set error message
        $actionMessage = $lang['update_failed'] ?? 'Failed to update transaction';
        $actionType = 'error';
    }
}

// Build the SQL query with filters
$sql = "SELECT p.*, u.username, u.email 
        FROM payments p 
        LEFT JOIN users u ON p.user_id = u.id 
        WHERE 1=1";

$sqlParams = [];

// Apply filters
if (!empty($statusFilter)) {
    $sql .= " AND p.status = :status";
    $sqlParams[':status'] = $statusFilter;
}

if (!empty($methodFilter)) {
    $sql .= " AND p.payment_method = :method";
    $sqlParams[':method'] = $methodFilter;
}

if (!empty($startDate)) {
    $sql .= " AND p.created_at >= :start_date";
    $sqlParams[':start_date'] = $startDate . ' 00:00:00';
}

if (!empty($endDate)) {
    $sql .= " AND p.created_at <= :end_date";
    $sqlParams[':end_date'] = $endDate . ' 23:59:59';
}

if (!empty($searchQuery)) {
    $sql .= " AND (p.id LIKE :search OR p.transaction_id LIKE :search OR u.username LIKE :search OR u.email LIKE :search)";
    $sqlParams[':search'] = "%$searchQuery%";
}

// Add sorting
$sql .= " ORDER BY p.$sortBy $sortDir";

// Count total results for pagination
$countSql = str_replace("SELECT p.*, u.username, u.email", "SELECT COUNT(*) as count", $sql);
$stmt = $pdo->prepare($countSql);
$stmt->execute($sqlParams);
$totalRows = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
$totalPages = ceil($totalRows / $perPage);

// Add pagination
$sql .= " LIMIT :offset, :limit";
$sqlParams[':offset'] = $offset;
$sqlParams[':limit'] = $perPage;

// Fetch transactions
try {
    $stmt = $pdo->prepare($sql);
    foreach ($sqlParams as $param => $value) {
        if ($param == ':offset' || $param == ':limit') {
            $stmt->bindValue($param, $value, PDO::PARAM_INT);
        } else {
            $stmt->bindValue($param, $value);
        }
    }
    $stmt->execute();
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $transactions = [];
    $actionMessage = $lang['load_failed'] ?? 'Failed to load transactions';
    $actionType = 'error';
}

// Get distinct payment methods for filter
try {
    $methodStmt = $pdo->query("SELECT DISTINCT payment_method FROM payments");
    $paymentMethods = $methodStmt->fetchAll(PDO::FETCH_COLUMN);
} catch (PDOException $e) {
    $paymentMethods = [];
}

// Get transaction count by status for dashboard stats
try {
    $statsStmt = $pdo->query("SELECT status, COUNT(*) as count FROM payments GROUP BY status");
    $stats = [];
    while ($row = $statsStmt->fetch(PDO::FETCH_ASSOC)) {
        $stats[$row['status']] = $row['count'];
    }
} catch (PDOException $e) {
    $stats = [];
}

// Function to generate sort URL
function getSortUrl($column, $currentSort, $currentDir) {
    $params = $_GET;
    $params['sort'] = $column;
    $params['dir'] = ($currentSort == $column && $currentDir == 'desc') ? 'asc' : 'desc';
    return '?' . http_build_query($params);
}

// Function to generate filter URL
function getFilterUrl($params) {
    $currentParams = $_GET;
    $mergedParams = array_merge($currentParams, $params);
    // Remove pagination when changing filters
    unset($mergedParams['page']);
    return '?' . http_build_query($mergedParams);
}

// Function to generate pagination URL
function getPaginationUrl($page) {
    $params = $_GET;
    $params['page'] = $page;
    return '?' . http_build_query($params);
}

// Get dark mode setting
$darkMode = isset($_COOKIE['darkMode']) && $_COOKIE['darkMode'] === 'true';
$rtl = isset($_COOKIE['rtl']) && $_COOKIE['rtl'] === 'true';

// Get export parameters
$exportUrl = $_SERVER['PHP_SELF'] . '?' . http_build_query(array_merge($_GET, ['export' => 'csv']));
?>

<div class="flex-1 h-full overflow-x-hidden overflow-y-auto">
    <div class="container mx-auto px-4 py-6">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                    <?php echo $pageTitle; ?>
                </h1>
                <p class="mt-1 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                    <?php echo $lang['transactions_subtitle'] ?? 'View and manage all payment transactions'; ?>
                </p>
            </div>
            
            <div class="mt-4 md:mt-0 space-x-2">
                <a href="<?php echo $exportUrl; ?>" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm <?php echo $darkMode ? 'bg-gray-700 hover:bg-gray-600 text-white' : 'bg-white hover:bg-gray-50 text-gray-700'; ?> focus:outline-none">
                    <i class="fas fa-file-export mr-2"></i> <?php echo $lang['export'] ?? 'Export'; ?>
                </a>
                <a href="<?php echo $adminUrl; ?>/payments/" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm <?php echo $darkMode ? 'bg-gray-700 hover:bg-gray-600 text-white' : 'bg-white hover:bg-gray-50 text-gray-700'; ?> focus:outline-none">
                    <i class="fas fa-cog mr-2"></i> <?php echo $lang['payment_settings'] ?? 'Payment Settings'; ?>
                </a>
            </div>
        </div>
        
        <?php if (!empty($actionMessage)): ?>
        <div class="mb-6 px-4 py-3 rounded-lg <?php echo $actionType === 'success' ? ($darkMode ? 'bg-green-900 text-green-200' : 'bg-green-100 text-green-800') : ($darkMode ? 'bg-red-900 text-red-200' : 'bg-red-100 text-red-800'); ?>">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas <?php echo $actionType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> mt-0.5"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm font-medium"><?php echo $actionMessage; ?></p>
                </div>
                <div class="ml-auto pl-3">
                    <div class="-mx-1.5 -my-1.5">
                        <button type="button" onclick="this.parentElement.parentElement.parentElement.remove()" class="inline-flex rounded-md p-1.5 <?php echo $actionType === 'success' ? ($darkMode ? 'text-green-200 hover:bg-green-800' : 'text-green-500 hover:bg-green-200') : ($darkMode ? 'text-red-200 hover:bg-red-800' : 'text-red-500 hover:bg-red-200'); ?> focus:outline-none">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white shadow rounded-lg <?php echo $darkMode ? 'bg-gray-800' : ''; ?> overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-500 rounded-md p-3">
                            <i class="fas fa-money-bill-wave text-white"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium truncate <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>"><?php echo $lang['total_transactions'] ?? 'Total Transactions'; ?></dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                        <?php echo $totalRows; ?>
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white shadow rounded-lg <?php echo $darkMode ? 'bg-gray-800' : ''; ?> overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-500 rounded-md p-3">
                            <i class="fas fa-check-circle text-white"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium truncate <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>"><?php echo $lang['completed_transactions'] ?? 'Completed'; ?></dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                        <?php echo isset($stats['completed']) ? $stats['completed'] : 0; ?>
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white shadow rounded-lg <?php echo $darkMode ? 'bg-gray-800' : ''; ?> overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-yellow-500 rounded-md p-3">
                            <i class="fas fa-clock text-white"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                <dt class="text-sm font-medium truncate <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>"><?php echo $lang['pending_transactions'] ?? 'Pending'; ?></dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                        <?php echo isset($stats['pending']) ? $stats['pending'] : 0; ?>
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="bg-white shadow rounded-lg <?php echo $darkMode ? 'bg-gray-800' : ''; ?> overflow-hidden">
                <div class="px-4 py-5 sm:p-6">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-red-500 rounded-md p-3">
                            <i class="fas fa-times-circle text-white"></i>
                        </div>
                        <div class="ml-5 w-0 flex-1">
                            <dl>
                                                                <dt class="text-sm font-medium truncate <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>"><?php echo $lang['failed_transactions'] ?? 'Failed'; ?></dt>
                                <dd class="flex items-baseline">
                                    <div class="text-2xl font-semibold <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                        <?php echo isset($stats['failed']) ? $stats['failed'] : 0; ?>
                                    </div>
                                </dd>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="bg-white shadow rounded-lg overflow-hidden mb-6 <?php echo $darkMode ? 'bg-gray-800' : ''; ?>">
            <div class="px-4 py-5 sm:p-6">
                <h3 class="text-lg leading-6 font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>"><?php echo $lang['filters'] ?? 'Filters'; ?></h3>
                
                <form action="" method="GET" class="mt-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-4">
                    <!-- Status Filter -->
                    <div>
                        <label for="status" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>"><?php echo $lang['status'] ?? 'Status'; ?></label>
                        <select id="status" name="status" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo $darkMode ? 'bg-gray-700 text-white border-gray-600' : ''; ?>">
                            <option value=""><?php echo $lang['all_statuses'] ?? 'All Statuses'; ?></option>
                            <option value="completed" <?php echo $statusFilter === 'completed' ? 'selected' : ''; ?>><?php echo $lang['completed'] ?? 'Completed'; ?></option>
                            <option value="pending" <?php echo $statusFilter === 'pending' ? 'selected' : ''; ?>><?php echo $lang['pending'] ?? 'Pending'; ?></option>
                            <option value="failed" <?php echo $statusFilter === 'failed' ? 'selected' : ''; ?>><?php echo $lang['failed'] ?? 'Failed'; ?></option>
                            <option value="refunded" <?php echo $statusFilter === 'refunded' ? 'selected' : ''; ?>><?php echo $lang['refunded'] ?? 'Refunded'; ?></option>
                        </select>
                    </div>
                    
                    <!-- Payment Method Filter -->
                    <div>
                        <label for="method" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>"><?php echo $lang['payment_method'] ?? 'Payment Method'; ?></label>
                        <select id="method" name="method" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo $darkMode ? 'bg-gray-700 text-white border-gray-600' : ''; ?>">
                            <option value=""><?php echo $lang['all_methods'] ?? 'All Methods'; ?></option>
                            <?php foreach($paymentMethods as $method): ?>
                            <option value="<?php echo htmlspecialchars($method); ?>" <?php echo $methodFilter === $method ? 'selected' : ''; ?>>
                                <?php echo ucfirst(htmlspecialchars($method)); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <!-- Date Range Filters -->
                    <div>
                        <label for="start_date" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>"><?php echo $lang['from_date'] ?? 'From Date'; ?></label>
                        <input type="date" id="start_date" name="start_date" value="<?php echo htmlspecialchars($startDate); ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo $darkMode ? 'bg-gray-700 text-white border-gray-600' : ''; ?>">
                    </div>
                    
                    <div>
                        <label for="end_date" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>"><?php echo $lang['to_date'] ?? 'To Date'; ?></label>
                        <input type="date" id="end_date" name="end_date" value="<?php echo htmlspecialchars($endDate); ?>" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo $darkMode ? 'bg-gray-700 text-white border-gray-600' : ''; ?>">
                    </div>
                    
                    <!-- Search Box -->
                    <div>
                        <label for="search" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>"><?php echo $lang['search'] ?? 'Search'; ?></label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($searchQuery); ?>" placeholder="<?php echo $lang['search_placeholder'] ?? 'Search transaction...'; ?>" class="block w-full pr-10 border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo $darkMode ? 'bg-gray-700 text-white border-gray-600 placeholder-gray-400' : 'placeholder-gray-500'; ?>">
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center">
                                <i class="fas fa-search <?php echo $darkMode ? 'text-gray-400' : 'text-gray-400'; ?>"></i>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Hidden inputs to preserve other query params -->
                    <?php if (!empty($sortBy)): ?>
                    <input type="hidden" name="sort" value="<?php echo htmlspecialchars($sortBy); ?>">
                    <?php endif; ?>
                    
                    <?php if (!empty($sortDir)): ?>
                    <input type="hidden" name="dir" value="<?php echo htmlspecialchars($sortDir); ?>">
                    <?php endif; ?>
                    
                    <div class="sm:col-span-2 lg:col-span-5 flex justify-end space-x-3">
                        <button type="submit" class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 <?php echo $darkMode ? 'focus:ring-offset-gray-900' : ''; ?>">
                            <i class="fas fa-filter mr-2"></i> <?php echo $lang['apply_filters'] ?? 'Apply Filters'; ?>
                        </button>
                        
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="inline-flex justify-center py-2 px-4 border shadow-sm text-sm font-medium rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 <?php echo $darkMode ? 'bg-gray-700 text-gray-200 border-gray-600 hover:bg-gray-600 focus:ring-offset-gray-900' : 'bg-white text-gray-700 border-gray-300 hover:bg-gray-50'; ?>">
                            <i class="fas fa-times mr-2"></i> <?php echo $lang['reset'] ?? 'Reset'; ?>
                        </a>
                    </div>
                </form>
            </div>
        </div>
        
        <!-- Transactions Table -->
        <div class="bg-white shadow overflow-hidden sm:rounded-md <?php echo $darkMode ? 'bg-gray-800' : ''; ?>">
            <?php if (empty($transactions)): ?>
            <div class="px-4 py-5 sm:p-6 text-center">
                <p class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>"><?php echo $lang['no_transactions_found'] ?? 'No transactions found with current filters.'; ?></p>
                <?php if (!empty($searchQuery) || !empty($statusFilter) || !empty($methodFilter) || !empty($startDate) || !empty($endDate)): ?>
                <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="mt-3 inline-flex items-center px-2.5 py-1.5 border border-transparent text-xs font-medium rounded text-indigo-700 bg-indigo-100 hover:bg-indigo-200 focus:outline-none">
                    <?php echo $lang['clear_filters'] ?? 'Clear all filters'; ?>
                </a>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-700' : ''; ?>">
                    <thead class="<?php echo $darkMode ? 'bg-gray-700' : 'bg-gray-50'; ?>">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                <a href="<?php echo getSortUrl('id', $sortBy, $sortDir); ?>" class="hover:text-gray-400 flex items-center">
                                    <?php echo $lang['id'] ?? 'ID'; ?>
                                    <?php if ($sortBy === 'id'): ?>
                                    <i class="fas fa-sort-<?php echo $sortDir === 'asc' ? 'up' : 'down'; ?> ml-1"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                <a href="<?php echo getSortUrl('username', $sortBy, $sortDir); ?>" class="hover:text-gray-400 flex items-center">
                                    <?php echo $lang['user'] ?? 'User'; ?>
                                    <?php if ($sortBy === 'username'): ?>
                                    <i class="fas fa-sort-<?php echo $sortDir === 'asc' ? 'up' : 'down'; ?> ml-1"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                <a href="<?php echo getSortUrl('amount', $sortBy, $sortDir); ?>" class="hover:text-gray-400 flex items-center">
                                    <?php echo $lang['amount'] ?? 'Amount'; ?>
                                    <?php if ($sortBy === 'amount'): ?>
                                    <i class="fas fa-sort-<?php echo $sortDir === 'asc' ? 'up' : 'down'; ?> ml-1"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                <a href="<?php echo getSortUrl('payment_method', $sortBy, $sortDir); ?>" class="hover:text-gray-400 flex items-center">
                                    <?php echo $lang['method'] ?? 'Method'; ?>
                                    <?php if ($sortBy === 'payment_method'): ?>
                                    <i class="fas fa-sort-<?php echo $sortDir === 'asc' ? 'up' : 'down'; ?> ml-1"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                <a href="<?php echo getSortUrl('status', $sortBy, $sortDir); ?>" class="hover:text-gray-400 flex items-center">
                                    <?php echo $lang['status'] ?? 'Status'; ?>
                                    <?php if ($sortBy === 'status'): ?>
                                    <i class="fas fa-sort-<?php echo $sortDir === 'asc' ? 'up' : 'down'; ?> ml-1"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                <a href="<?php echo getSortUrl('created_at', $sortBy, $sortDir); ?>" class="hover:text-gray-400 flex items-center">
                                    <?php echo $lang['date'] ?? 'Date'; ?>
                                    <?php if ($sortBy === 'created_at'): ?>
                                    <i class="fas fa-sort-<?php echo $sortDir === 'asc' ? 'up' : 'down'; ?> ml-1"></i>
                                    <?php endif; ?>
                                </a>
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                <?php echo $lang['actions'] ?? 'Actions'; ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="<?php echo $darkMode ? 'bg-gray-800 divide-gray-700' : 'bg-white divide-gray-200'; ?>">
                        <?php foreach($transactions as $transaction): ?>
                                                <tr class="<?php echo $darkMode ? 'hover:bg-gray-700' : 'hover:bg-gray-50'; ?>">
                            <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-800'; ?>">
                                <?php echo $transaction['id']; ?>
                                <?php if (!empty($transaction['transaction_id'])): ?>
                                <div class="text-xs <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?> mt-1">
                                    <?php echo $lang['transaction_id'] ?? 'Transaction ID'; ?>: <?php echo htmlspecialchars(substr($transaction['transaction_id'], 0, 15)) . (strlen($transaction['transaction_id']) > 15 ? '...' : ''); ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-800'; ?>">
                                <div class="font-medium">
                                    <?php echo htmlspecialchars($transaction['username'] ?? 'Unknown'); ?>
                                </div>
                                <?php if (!empty($transaction['email'])): ?>
                                <div class="text-xs <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?> mt-1">
                                    <?php echo htmlspecialchars($transaction['email']); ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-800'; ?>">
                                <?php echo htmlspecialchars($transaction['currency']); ?> <?php echo number_format($transaction['amount'], 2); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-800'; ?>">
                                <?php echo ucfirst(htmlspecialchars($transaction['payment_method'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php 
                                $statusClass = '';
                                switch(strtolower($transaction['status'])) {
                                    case 'completed':
                                        $statusClass = $darkMode ? 'bg-green-900 text-green-200' : 'bg-green-100 text-green-800';
                                        break;
                                    case 'pending':
                                        $statusClass = $darkMode ? 'bg-yellow-900 text-yellow-200' : 'bg-yellow-100 text-yellow-800';
                                        break;
                                    case 'failed':
                                        $statusClass = $darkMode ? 'bg-red-900 text-red-200' : 'bg-red-100 text-red-800';
                                        break;
                                    case 'refunded':
                                        $statusClass = $darkMode ? 'bg-blue-900 text-blue-200' : 'bg-blue-100 text-blue-800';
                                        break;
                                    default:
                                        $statusClass = $darkMode ? 'bg-gray-700 text-gray-300' : 'bg-gray-100 text-gray-800';
                                }
                                ?>
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $statusClass; ?>">
                                    <?php echo ucfirst(htmlspecialchars($transaction['status'])); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">
                                <?php echo date('M j, Y H:i', strtotime($transaction['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                <div class="flex justify-end space-x-2">
                                    <button type="button" onclick="openTransactionDetails(<?php echo $transaction['id']; ?>)" class="text-blue-600 hover:text-blue-900 <?php echo $darkMode ? 'hover:text-blue-400' : ''; ?>">
                                        <span class="sr-only"><?php echo $lang['view_details'] ?? 'View details'; ?></span>
                                        <i class="fas fa-eye"></i>
                                    </button>
                                    
                                    <button type="button" onclick="openStatusModal(<?php echo $transaction['id']; ?>, '<?php echo htmlspecialchars($transaction['status']); ?>')" class="text-indigo-600 hover:text-indigo-900 <?php echo $darkMode ? 'hover:text-indigo-400' : ''; ?>">
                                        <span class="sr-only"><?php echo $lang['change_status'] ?? 'Change status'; ?></span>
                                        <i class="fas fa-edit"></i>
                                    </button>
                                    
                                    <?php if ($transaction['status'] === 'completed'): ?>
                                    <button type="button" onclick="confirmRefund(<?php echo $transaction['id']; ?>)" class="text-yellow-600 hover:text-yellow-800 <?php echo $darkMode ? 'hover:text-yellow-400' : ''; ?>">
                                        <span class="sr-only"><?php echo $lang['refund'] ?? 'Refund'; ?></span>
                                        <i class="fas fa-undo"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="px-4 py-3 flex items-center justify-between border-t <?php echo $darkMode ? 'border-gray-700 bg-gray-800' : 'border-gray-200 bg-gray-50'; ?> sm:px-6">
                <div class="flex-1 flex justify-between sm:hidden">
                    <?php if ($page > 1): ?>
                    <a href="<?php echo getPaginationUrl($page - 1); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-gray-200 hover:bg-gray-600' : 'bg-white text-gray-700 hover:bg-gray-50'; ?>">
                        <?php echo $lang['previous'] ?? 'Previous'; ?>
                    </a>
                    <?php endif; ?>
                    
                    <?php if ($page < $totalPages): ?>
                    <a href="<?php echo getPaginationUrl($page + 1); ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-gray-200 hover:bg-gray-600' : 'bg-white text-gray-700 hover:bg-gray-50'; ?>">
                        <?php echo $lang['next'] ?? 'Next'; ?>
                    </a>
                    <?php endif; ?>
                </div>
                
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                            <?php 
                            $fromResult = min(($page - 1) * $perPage + 1, $totalRows);
                            $toResult = min($page * $perPage, $totalRows);
                            
                            echo sprintf($lang['showing_results'] ?? 'Showing %d to %d of %d results', 
                                $fromResult, 
                                $toResult, 
                                $totalRows
                            ); 
                            ?>
                        </p>
                    </div>
                    
                    <div>
                        <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                            <?php if ($page > 1): ?>
                            <a href="<?php echo getPaginationUrl($page - 1); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-gray-300 hover:bg-gray-600' : 'bg-white text-gray-500 hover:bg-gray-50'; ?>">
                                <span class="sr-only"><?php echo $lang['previous'] ?? 'Previous'; ?></span>
                                <i class="fas fa-chevron-left text-xs"></i>
                            </a>
                            <?php endif; ?>
                            
                            <?php
                            $showEllipsisStart = ($page > 3);
                            $showEllipsisEnd = ($page < $totalPages - 2);
                            $startPage = max(1, min($page - 1, $totalPages - 4));
                            $endPage = min($totalPages, max($page + 1, 5));
                            
                            if ($totalPages > 5) {
                                if ($page > 3) {
                                    // Always show first page
                                    ?>
                                    <a href="<?php echo getPaginationUrl(1); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-gray-300 hover:bg-gray-600' : 'bg-white text-gray-500 hover:bg-gray-50'; ?>">
                                        1
                                    </a>
                                    
                                    <?php if ($page > 4): ?>
                                    <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 <?php echo $darkMode ? 'bg-gray-800 border-gray-600 text-gray-300' : 'bg-white text-gray-700'; ?>">
                                        ...
                                    </span>
                                    <?php endif;
                                }
                                
                                for ($i = max(1, $page - 1); $i <= min($totalPages, $page + 1); $i++) {
                                    $isCurrentPage = $i === $page;
                                    ?>
                                    <a href="<?php echo getPaginationUrl($i); ?>" aria-current="<?php echo $isCurrentPage ? 'page' : 'false'; ?>" class="relative inline-flex items-center px-4 py-2 border <?php echo $isCurrentPage ? ($darkMode ? 'bg-gray-900 border-gray-600 text-blue-400 z-10' : 'bg-blue-50 border-blue-500 text-blue-600 z-10') : ($darkMode ? 'bg-gray-700 border-gray-600 text-gray-300 hover:bg-gray-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                    <?php
                                }
                                
                                if ($page < $totalPages - 2) {
                                    if ($page < $totalPages - 3): ?>
                                    <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 <?php echo $darkMode ? 'bg-gray-800 border-gray-600 text-gray-300' : 'bg-white text-gray-700'; ?>">
                                        ...
                                    </span>
                                    <?php endif; ?>
                                    
                                    <!-- Always show last page -->
                                    <a href="<?php echo getPaginationUrl($totalPages); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-gray-300 hover:bg-gray-600' : 'bg-white text-gray-500 hover:bg-gray-50'; ?>">
                                        <?php echo $totalPages; ?>
                                    </a>
                                    <?php
                                }
                            } else {
                                // For fewer pages, show all page numbers
                                for ($i = 1; $i <= $totalPages; $i++) {
                                    $isCurrentPage = $i === $page;
                                    ?>
                                    <a href="<?php echo getPaginationUrl($i); ?>" aria-current="<?php echo $isCurrentPage ? 'page' : 'false'; ?>" class="relative inline-flex items-center px-4 py-2 border <?php echo $isCurrentPage ? ($darkMode ? 'bg-gray-900 border-gray-600 text-blue-400 z-10' : 'bg-blue-50 border-blue-500 text-blue-600 z-10') : ($darkMode ? 'bg-gray-700 border-gray-600 text-gray-300 hover:bg-gray-600' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-50'); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                    <?php
                                }
                            }
                            ?>
                            
                            <?php if ($page < $totalPages): ?>
                            <a href="<?php echo getPaginationUrl($page + 1); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-gray-300 hover:bg-gray-600' : 'bg-white text-gray-500 hover:bg-gray-50'; ?>">
                                <span class="sr-only"><?php echo $lang['next'] ?? 'Next'; ?></span>
                                <i class="fas fa-chevron-right text-xs"></i>
                            </a>
                            <?php endif; ?>
                        </nav>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Transaction Details Modal -->
<div id="transactionDetailsModal" class="fixed inset-0 z-10 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                <div class="inline-block align-bottom rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="<?php echo $darkMode ? 'bg-gray-800' : 'bg-white'; ?> px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full <?php echo $darkMode ? 'bg-blue-900' : 'bg-blue-100'; ?> sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-receipt <?php echo $darkMode ? 'text-blue-400' : 'text-blue-600'; ?>"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>" id="modal-title">
                            <?php echo $lang['transaction_details'] ?? 'Transaction Details'; ?>
                        </h3>
                        <div class="mt-4 border-t <?php echo $darkMode ? 'border-gray-700' : 'border-gray-200'; ?>">
                            <dl class="divide-y <?php echo $darkMode ? 'divide-gray-700' : 'divide-gray-200'; ?>">
                                <div class="py-3 grid grid-cols-3">
                                    <dt class="text-sm font-medium <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>"><?php echo $lang['transaction_id'] ?? 'Transaction ID'; ?></dt>
                                    <dd class="text-sm font-medium col-span-2 <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>" id="detail-id">-</dd>
                                </div>
                                <div class="py-3 grid grid-cols-3">
                                    <dt class="text-sm font-medium <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>"><?php echo $lang['user'] ?? 'User'; ?></dt>
                                    <dd class="text-sm font-medium col-span-2 <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>" id="detail-user">-</dd>
                                </div>
                                <div class="py-3 grid grid-cols-3">
                                    <dt class="text-sm font-medium <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>"><?php echo $lang['amount'] ?? 'Amount'; ?></dt>
                                    <dd class="text-sm font-medium col-span-2 <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>" id="detail-amount">-</dd>
                                </div>
                                <div class="py-3 grid grid-cols-3">
                                    <dt class="text-sm font-medium <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>"><?php echo $lang['payment_method'] ?? 'Payment Method'; ?></dt>
                                    <dd class="text-sm font-medium col-span-2 <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>" id="detail-method">-</dd>
                                </div>
                                <div class="py-3 grid grid-cols-3">
                                    <dt class="text-sm font-medium <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>"><?php echo $lang['status'] ?? 'Status'; ?></dt>
                                    <dd class="text-sm font-medium col-span-2" id="detail-status-container">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full" id="detail-status">-</span>
                                    </dd>
                                </div>
                                <div class="py-3 grid grid-cols-3">
                                    <dt class="text-sm font-medium <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>"><?php echo $lang['date'] ?? 'Date'; ?></dt>
                                    <dd class="text-sm font-medium col-span-2 <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>" id="detail-date">-</dd>
                                </div>
                                <div class="py-3 grid grid-cols-3" id="detail-subscription-container">
                                    <dt class="text-sm font-medium <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>"><?php echo $lang['subscription'] ?? 'Subscription'; ?></dt>
                                    <dd class="text-sm font-medium col-span-2 <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>" id="detail-subscription">-</dd>
                                </div>
                                <div class="py-3" id="detail-notes-container">
                                    <dt class="text-sm font-medium <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>"><?php echo $lang['additional_details'] ?? 'Additional Details'; ?></dt>
                                    <dd class="mt-1 text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                        <pre id="detail-notes" class="whitespace-pre-wrap font-mono text-xs p-2 rounded <?php echo $darkMode ? 'bg-gray-900' : 'bg-gray-50'; ?> overflow-auto max-h-40"></pre>
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
            <div class="<?php echo $darkMode ? 'bg-gray-800' : 'bg-gray-50'; ?> px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 <?php echo $darkMode ? 'text-white bg-blue-600 hover:bg-blue-700' : 'bg-blue-600 text-white hover:bg-blue-700'; ?> focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" id="printTransactionBtn">
                    <i class="fas fa-print mr-2"></i> <?php echo $lang['print'] ?? 'Print'; ?>
                </button>
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border <?php echo $darkMode ? 'border-gray-600 bg-gray-700 text-gray-300 hover:bg-gray-600' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'; ?> shadow-sm px-4 py-2 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm" onclick="closeTransactionModal()">
                    <?php echo $lang['close'] ?? 'Close'; ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Update Status Modal -->
<div id="updateStatusModal" class="fixed inset-0 z-10 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="<?php echo $darkMode ? 'bg-gray-800' : 'bg-white'; ?> px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full <?php echo $darkMode ? 'bg-yellow-900' : 'bg-yellow-100'; ?> sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-edit <?php echo $darkMode ? 'text-yellow-400' : 'text-yellow-600'; ?>"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                        <h3 class="text-lg leading-6 font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>" id="status-modal-title">
                            <?php echo $lang['update_transaction_status'] ?? 'Update Transaction Status'; ?>
                        </h3>
                        <form id="updateStatusForm" method="post" class="mt-4">
                            <input type="hidden" name="action" value="update">
                            <div>
                                <label for="status" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>"><?php echo $lang['status'] ?? 'Status'; ?></label>
                                <select id="update-status" name="status" class="mt-1 block w-full pl-3 pr-10 py-2 text-base border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo $darkMode ? 'bg-gray-700 text-white border-gray-600' : ''; ?>">
                                    <option value="pending"><?php echo $lang['pending'] ?? 'Pending'; ?></option>
                                    <option value="completed"><?php echo $lang['completed'] ?? 'Completed'; ?></option>
                                    <option value="failed"><?php echo $lang['failed'] ?? 'Failed'; ?></option>
                                    <option value="refunded"><?php echo $lang['refunded'] ?? 'Refunded'; ?></option>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            <div class="<?php echo $darkMode ? 'bg-gray-800' : 'bg-gray-50'; ?> px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm" id="confirmStatusUpdate">
                    <?php echo $lang['update'] ?? 'Update'; ?>
                </button>
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border <?php echo $darkMode ? 'border-gray-600 bg-gray-700 text-gray-300 hover:bg-gray-600' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'; ?> shadow-sm px-4 py-2 sm:mt-0 sm:w-auto sm:text-sm" onclick="closeStatusModal()">
                    <?php echo $lang['cancel'] ?? 'Cancel'; ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Refund Confirmation Modal -->
<div id="refundModal" class="fixed inset-0 z-10 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="<?php echo $darkMode ? 'bg-gray-800' : 'bg-white'; ?> px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full <?php echo $darkMode ? 'bg-red-900' : 'bg-red-100'; ?> sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-exclamation-triangle <?php echo $darkMode ? 'text-red-400' : 'text-red-600'; ?>"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>" id="refund-modal-title">
                            <?php echo $lang['confirm_refund'] ?? 'Confirm Refund'; ?>
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">
                                <?php echo $lang['refund_confirmation'] ?? 'Are you sure you want to refund this transaction? This action cannot be undone.'; ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="<?php echo $darkMode ? 'bg-gray-800' : 'bg-gray-50'; ?> px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <form id="refundForm" method="post">
                    <input type="hidden" name="action" value="refund">
                    <input type="hidden" name="id" id="refund-transaction-id" value="">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        <?php echo $lang['confirm'] ?? 'Confirm Refund'; ?>
                    </button>
                </form>
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border <?php echo $darkMode ? 'border-gray-600 bg-gray-700 text-gray-300 hover:bg-gray-600' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'; ?> shadow-sm px-4 py-2 text-base font-medium focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm" onclick="closeRefundModal()">
                    <?php echo $lang['cancel'] ?? 'Cancel'; ?>
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Transaction Details Modal Functions
    function openTransactionDetails(transactionId) {
        fetch('<?php echo $adminUrl; ?>/api/transactions.php?id=' + transactionId, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const transaction = data.transaction;
                
                // Set transaction details
                document.getElementById('detail-id').textContent = transaction.id + (transaction.transaction_id ? ' (' + transaction.transaction_id + ')' : '');
                document.getElementById('detail-user').textContent = transaction.username + ' (' + transaction.email + ')';
                document.getElementById('detail-amount').textContent = transaction.currency + ' ' + parseFloat(transaction.amount).toFixed(2);
                document.getElementById('detail-method').textContent = transaction.payment_method.charAt(0).toUpperCase() + transaction.payment_method.slice(1);
                
                const statusElement = document.getElementById('detail-status');
                let statusClass = '';
                
                switch(transaction.status.toLowerCase()) {
                    case 'completed':
                        statusClass = <?php echo $darkMode ? "'bg-green-900 text-green-200'" : "'bg-green-100 text-green-800'"; ?>;
                        break;
                    case 'pending':
                        statusClass = <?php echo $darkMode ? "'bg-yellow-900 text-yellow-200'" : "'bg-yellow-100 text-yellow-800'"; ?>;
                        break;
                    case 'failed':
                        statusClass = <?php echo $darkMode ? "'bg-red-900 text-red-200'" : "'bg-red-100 text-red-800'"; ?>;
                        break;
                    case 'refunded':
                        statusClass = <?php echo $darkMode ? "'bg-blue-900 text-blue-200'" : "'bg-blue-100 text-blue-800'"; ?>;
                        break;
                    default:
                        statusClass = <?php echo $darkMode ? "'bg-gray-700 text-gray-300'" : "'bg-gray-100 text-gray-800'"; ?>;
                }
                
                statusElement.className = `px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${statusClass}`;
                statusElement.textContent = transaction.status.charAt(0).toUpperCase() + transaction.status.slice(1);
                
                document.getElementById('detail-date').textContent = new Date(transaction.created_at).toLocaleString();
                
                // Subscription details
                const subscriptionContainer = document.getElementById('detail-subscription-container');
                if (transaction.subscription_id) {
                    subscriptionContainer.classList.remove('hidden');
                    document.getElementById('detail-subscription').textContent = '#' + transaction.subscription_id;
                } else {
                    subscriptionContainer.classList.add('hidden');
                }
                
                // Additional details from JSON
                const notesContainer = document.getElementById('detail-notes-container');
                const notesElement = document.getElementById('detail-notes');
                
                if (transaction.details) {
                    const details = typeof transaction.details === 'string' ? JSON.parse(transaction.details) : transaction.details;
                    notesContainer.classList.remove('hidden');
                    notesElement.textContent = JSON.stringify(details, null, 2);
                } else {
                    notesContainer.classList.add('hidden');
                    notesElement.textContent = '';
                }
                
                // Show modal
                document.getElementById('transactionDetailsModal').classList.remove('hidden');
            } else {
                alert('<?php echo $lang['error_loading_transaction'] ?? 'Error loading transaction details'; ?>');
            }
        })
        .catch(error => {
            console.error('Error fetching transaction details:', error);
            alert('<?php echo $lang['error_loading_transaction'] ?? 'Error loading transaction details'; ?>');
        });
    }
    
    function closeTransactionModal() {
        document.getElementById('transactionDetailsModal').classList.add('hidden');
    }
    
    // Status Update Modal Functions
    function openStatusModal(transactionId, currentStatus) {
        // Set transaction ID and current status
        document.getElementById('updateStatusForm').action = `<?php echo $_SERVER['PHP_SELF']; ?>?action=update&id=${transactionId}`;
        const statusSelect = document.getElementById('update-status');
        
        // Set the current status as selected
        for (let i = 0; i < statusSelect.options.length; i++) {
            if (statusSelect.options[i].value === currentStatus) {
                statusSelect.selectedIndex = i;
                break;
            }
        }
        
        // Show modal
        document.getElementById('updateStatusModal').classList.remove('hidden');
    }
    
    function closeStatusModal() {
        document.getElementById('updateStatusModal').classList.add('hidden');
    }
    
    document.getElementById('confirmStatusUpdate').addEventListener('click', function() {
        document.getElementById('updateStatusForm').submit();
    });
    
    // Refund Modal Functions
    function confirmRefund(transactionId) {
        document.getElementById('refund-transaction-id').value = transactionId;
        document.getElementById('refundForm').action = `<?php echo $_SERVER['PHP_SELF']; ?>?action=refund&id=${transactionId}`;
        document.getElementById('refundModal').classList.remove('hidden');
    }
    
    function closeRefundModal() {
        document.getElementById('refundModal').classList.add('hidden');
    }
    
    // Print Transaction Receipt
    document.getElementById('printTransactionBtn').addEventListener('click', function() {
        const printWindow = window.open('', '_blank');
        const darkMode = <?php echo $darkMode ? 'true' : 'false'; ?>;
        
        // Get transaction details
        const id = document.getElementById('detail-id').textContent;
        const user = document.getElementById('detail-user').textContent;
        const amount = document.getElementById('detail-amount').textContent;
        const method = document.getElementById('detail-method').textContent;
        const status = document.getElementById('detail-status').textContent;
        const date = document.getElementById('detail-date').textContent;
        
        // Create print HTML
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Transaction Receipt - ${id}</title>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <style>
                    body {
                        font-family: Arial, sans-serif;
                        line-height: 1.6;
                        color: #333;
                        max-width: 800px;
                        margin: 0 auto;
                        padding: 20px;
                    }
                    .header {
                        text-align: center;
                        margin-bottom: 30px;
                        padding-bottom: 10px;
                        border-bottom: 1px solid #ccc;
                    }
                    .logo {
                        max-width: 200px;
                        height: auto;
                    }
                    .receipt-title {
                        font-size: 24px;
                        font-weight: bold;
                        margin: 10px 0;
                    }
                    .receipt-date {
                        color: #666;
                        font-style: italic;
                    }
                    .receipt-details {
                        margin: 20px 0;
                    }
                    .detail-row {
                        display: flex;
                        border-bottom: 1px solid #eee;
                        padding: 10px 0;
                    }
                    .detail-label {
                        flex: 1;
                        font-weight: bold;
                    }
                    .detail-value {
                        flex: 2;
                    }
                    .receipt-footer {
                        margin-top: 40px;
                        text-align: center;
                        font-size: 0.9em;
                        color: #666;
                    }
                    .status {
                        display: inline-block;
                        padding: 3px 8px;
                        border-radius: 12px;
                        font-size: 0.8em;
                        font-weight: bold;
                    }
                    .status-completed {
                        background-color: #d1fae5;
                        color: #065f46;
                    }
                    .status-pending {
                        background-color: #fef3c7;
                        color: #92400e;
                    }
                    .status-failed {
                        background-color: #fee2e2;
                        color: #b91c1c;
                    }
                    .status-refunded {
                        background-color: #dbeafe;
                        color: #1e40af;
                    }
                    @media print {
                        body {
                            print-color-adjust: exact;
                            -webkit-print-color-adjust: exact;
                        }
                    }
                </style>
            </head>
            <body>
                <div class="header">
                    <img src="<?php echo getBaseUrl(); ?>/assets/images/logo.png" alt="Logo" class="logo">
                    <h1 class="receipt-title"><?php echo $lang['transaction_receipt'] ?? 'Transaction Receipt'; ?></h1>
                    <p class="receipt-date">${new Date().toLocaleDateString()}</p>
                </div>
                
                <div class="receipt-details">
                    <div class="detail-row">
                        <div class="detail-label"><?php echo $lang['transaction_id'] ?? 'Transaction ID'; ?>:</div>
                        <div class="detail-value">${id}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label"><?php echo $lang['date'] ?? 'Date'; ?>:</div>
                        <div class="detail-value">${date}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label"><?php echo $lang['user'] ?? 'User'; ?>:</div>
                        <div class="detail-value">${user}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label"><?php echo $lang['payment_method'] ?? 'Payment Method'; ?>:</div>
                        <div class="detail-value">${method}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label"><?php echo $lang['amount'] ?? 'Amount'; ?>:</div>
                        <div class="detail-value">${amount}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label"><?php echo $lang['status'] ?? 'Status'; ?>:</div>
                        <div class="detail-value">
                            <span class="status status-${status.toLowerCase()}">${status}</span>
                        </div>
                    </div>
                </div>
                
                <div class="receipt-footer">
                    <p><?php echo $lang['thank_you'] ?? 'Thank you for your business!'; ?></p>
                    <p><?php echo getSystemName(); ?> &copy; ${new Date().getFullYear()}</p>
                </div>
                
                <script>
                    window.onload = function() {
                        window.print();
                        setTimeout(function() {
                            window.close();
                        }, 500);
                    };
                </script>
            </body>
            </html>
        `);
        
        printWindow.document.close();
    });
</script>

<?php
// Include footer
include '../../theme/admin/footer.php';
?>
        