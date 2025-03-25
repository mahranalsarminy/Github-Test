<?php
/**
 * Payment Gateway Management
 *
 * @package WallPix
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'Payment Management - WallPix Admin';

// Include header
require_once '../../theme/admin/header.php';

// Current date and time in UTC
$currentDateTime = date('Y-m-d H:i:s');

// Initialize variables
$message = '';
$messageType = '';
$activeTab = isset($_GET['tab']) ? $_GET['tab'] : 'gateways';
$validTabs = ['gateways', 'transactions'];

if (!in_array($activeTab, $validTabs)) {
    $activeTab = 'gateways';
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Update payment gateway settings
        if (isset($_POST['update_gateway'])) {
            $gateway_id = (int)$_POST['gateway_id'];
            $display_name = htmlspecialchars(trim($_POST['display_name']));
            $description = htmlspecialchars(trim($_POST['description']));
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $test_mode = isset($_POST['test_mode']) ? 1 : 0;
            
            // Get existing configuration
            $stmt = $pdo->prepare("SELECT * FROM payment_gateways WHERE id = ?");
            $stmt->execute([$gateway_id]);
            $gateway = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$gateway) {
                throw new Exception("Gateway not found");
            }
            
            // Decode existing config
            $config = json_decode($gateway['config'], true) ?: [
                'live' => [],
                'test' => []
            ];
            
            // Update config based on gateway type
            if ($gateway['name'] === 'paypal') {
                // Test credentials
                $config['test']['client_id'] = trim($_POST['test_client_id'] ?? '');
                $config['test']['client_secret'] = trim($_POST['test_client_secret'] ?? '');
                $config['test']['webhook_id'] = trim($_POST['test_webhook_id'] ?? '');
                $config['test']['webhook_secret'] = trim($_POST['test_webhook_secret'] ?? '');
                
                // Live credentials
                $config['live']['client_id'] = trim($_POST['live_client_id'] ?? '');
                $config['live']['client_secret'] = trim($_POST['live_client_secret'] ?? '');
                $config['live']['webhook_id'] = trim($_POST['live_webhook_id'] ?? '');
                $config['live']['webhook_secret'] = trim($_POST['live_webhook_secret'] ?? '');
            } 
            else if ($gateway['name'] === 'stripe') {
                // Test credentials
                $config['test']['publishable_key'] = trim($_POST['test_publishable_key'] ?? '');
                $config['test']['secret_key'] = trim($_POST['test_secret_key'] ?? '');
                $config['test']['webhook_secret'] = trim($_POST['test_webhook_secret'] ?? '');
                
                // Live credentials
                $config['live']['publishable_key'] = trim($_POST['live_publishable_key'] ?? '');
                $config['live']['secret_key'] = trim($_POST['live_secret_key'] ?? '');
                $config['live']['webhook_secret'] = trim($_POST['live_webhook_secret'] ?? '');
            }
            
            // Update the database
            $stmt = $pdo->prepare("
                UPDATE payment_gateways 
                SET display_name = ?, 
                    description = ?, 
                    is_active = ?, 
                    test_mode = ?, 
                    config = ?, 
                    updated_by = ?, 
                    updated_at = NOW() 
                WHERE id = ?
            ");
            
            $stmt->execute([
                $display_name,
                $description,
                $is_active,
                $test_mode,
                json_encode($config),
                $currentUser,
                $gateway_id
            ]);
            
            // Log activity
            $stmt = $pdo->prepare("
                INSERT INTO activities (user_id, description, created_at) 
                VALUES ((SELECT id FROM users WHERE username = ?), ?, NOW())
            ");
            $stmt->execute([
                $currentUser, 
                "Updated {$gateway['name']} payment gateway settings"
            ]);
            
            $message = "Payment gateway settings updated successfully!";
            $messageType = "success";
        }
        
        // Update transaction status
        if (isset($_POST['update_transaction'])) {
            $transaction_id = (int)$_POST['transaction_id'];
            $status = trim($_POST['status']);
            $notes = htmlspecialchars(trim($_POST['notes'] ?? ''));
            
            // Allowed statuses
            $validStatuses = ['pending', 'completed', 'failed', 'refunded', 'cancelled'];
            
            if (!in_array($status, $validStatuses)) {
                throw new Exception("Invalid status value");
            }
            
            // Update transaction
            $stmt = $pdo->prepare("
                UPDATE payments 
                SET status = ?, 
                    notes = ?, 
                    updated_at = NOW() 
                WHERE id = ?
            ");
            
            $stmt->execute([$status, $notes, $transaction_id]);
            
            // Log activity
            $stmt = $pdo->prepare("
                INSERT INTO activities (user_id, description, created_at) 
                VALUES ((SELECT id FROM users WHERE username = ?), ?, NOW())
            ");
            $stmt->execute([
                $currentUser, 
                "Updated payment transaction #$transaction_id status to $status"
            ]);
            
            $message = "Transaction status updated successfully!";
            $messageType = "success";
            
            // Set active tab to transactions
            $activeTab = 'transactions';
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "error";
    }
}

// Delete transaction (mark as cancelled and add note)
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    try {
        $transaction_id = (int)$_GET['delete'];
        
        // Get transaction information
        $stmt = $pdo->prepare("SELECT * FROM payments WHERE id = ?");
        $stmt->execute([$transaction_id]);
        $transaction = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$transaction) {
            throw new Exception("Transaction not found");
        }
        
        // Update transaction instead of deleting
        $stmt = $pdo->prepare("
            UPDATE payments 
            SET status = 'cancelled', 
                notes = CONCAT(IFNULL(notes, ''), '\nCancelled by admin: " . $currentUser . " on " . $currentDateTime . "'),
                updated_at = NOW() 
            WHERE id = ?
        ");
        
        $stmt->execute([$transaction_id]);
        
        // Log activity
        $stmt = $pdo->prepare("
            INSERT INTO activities (user_id, description, created_at) 
            VALUES ((SELECT id FROM users WHERE username = ?), ?, NOW())
        ");
        $stmt->execute([
            $currentUser, 
            "Cancelled payment transaction #$transaction_id"
        ]);
        
        $message = "Transaction cancelled successfully!";
        $messageType = "success";
        
        // Set active tab to transactions
        $activeTab = 'transactions';
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "error";
    }
}

// Fetch payment gateways
try {
    $stmt = $pdo->query("SELECT * FROM payment_gateways ORDER BY name");
    $payment_gateways = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $message = "Error loading payment gateways: " . $e->getMessage();
    $messageType = "error";
    $payment_gateways = [];
}

// Pagination settings for transactions
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Filters for transactions
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$method_filter = isset($_GET['method']) ? $_GET['method'] : '';
$date_start = isset($_GET['date_start']) ? $_GET['date_start'] : '';
$date_end = isset($_GET['date_end']) ? $_GET['date_end'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Fetch transactions with filters
try {
    // Build query parts
    $query = "SELECT p.*, u.username, u.email FROM payments p LEFT JOIN users u ON p.user_id = u.id WHERE 1=1";
    $count_query = "SELECT COUNT(*) FROM payments p LEFT JOIN users u ON p.user_id = u.id WHERE 1=1";
    $params = [];
    
    // Add filters
    if ($status_filter) {
        $query .= " AND p.status = ?";
        $count_query .= " AND p.status = ?";
        $params[] = $status_filter;
    }
    
    if ($method_filter) {
        $query .= " AND p.payment_method = ?";
        $count_query .= " AND p.payment_method = ?";
        $params[] = $method_filter;
    }
    
    if ($date_start) {
        $query .= " AND p.created_at >= ?";
        $count_query .= " AND p.created_at >= ?";
        $params[] = $date_start . ' 00:00:00';
    }
    
    if ($date_end) {
        $query .= " AND p.created_at <= ?";
        $count_query .= " AND p.created_at <= ?";
        $params[] = $date_end . ' 23:59:59';
    }
    
    if ($search) {
        $query .= " AND (p.transaction_id LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
        $count_query .= " AND (p.transaction_id LIKE ? OR u.username LIKE ? OR u.email LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }
    
    // Get total count
    $stmt = $pdo->prepare($count_query);
    $stmt->execute($params);
    $total_transactions = (int)$stmt->fetchColumn();
    $total_pages = ceil($total_transactions / $per_page);
    
    // Get transactions with pagination
    $query .= " ORDER BY p.created_at DESC LIMIT ?, ?";
    $params[] = $offset;
    $params[] = $per_page;
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get transaction stats
    $stmt = $pdo->query("
        SELECT 
            status, 
            COUNT(*) as count, 
            SUM(amount) as total 
        FROM payments 
        GROUP BY status
    ");
    $transaction_stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate totals
    $total_revenue = 0;
    $total_pending = 0;
    $completed_count = 0;
    
    foreach ($transaction_stats as $stat) {
        if ($stat['status'] === 'completed') {
            $total_revenue += (float)$stat['total'];
            $completed_count = (int)$stat['count'];
        } elseif ($stat['status'] === 'pending') {
            $total_pending += (float)$stat['total'];
        }
    }
    
} catch (PDOException $e) {
    $message = "Error loading transactions: " . $e->getMessage();
    $messageType = "error";
    $transactions = [];
    $total_transactions = 0;
    $total_pages = 0;
    $transaction_stats = [];
    $total_revenue = 0;
    $total_pending = 0;
    $completed_count = 0;
}

// Include sidebar
require_once '../../theme/admin/slidbar.php';
?>

<!-- Main Content -->
<div class="content-wrapper p-4 sm:ml-64">
    <div class="p-4 mt-14">
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                    <i class="fas fa-credit-card mr-2"></i> Payment Management
                </h1>
                <p class="mt-2 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                    Configure payment gateways and manage transactions
                </p>
            </div>
            <div class="mt-4 md:mt-0">
                <span class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                    <i class="fas fa-clock mr-2"></i> <?php echo $currentDateTime; ?> (UTC)
                </span>
            </div>
        </div>

        <?php if ($message): ?>
            <div class="mb-6 p-4 rounded-md <?php echo $messageType === 'success' ? ($darkMode ? 'bg-green-800 text-green-200' : 'bg-green-100 text-green-700') : ($darkMode ? 'bg-red-800 text-red-200' : 'bg-red-100 text-red-700'); ?>">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <?php if ($messageType === 'success'): ?>
                            <i class="fas fa-check-circle"></i>
                        <?php else: ?>
                            <i class="fas fa-exclamation-circle"></i>
                        <?php endif; ?>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm"><?php echo $message; ?></p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Payment Gateways Tab -->
        <?php if ($activeTab === 'gateways'): ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- PayPal Gateway Settings -->
                <?php 
                $paypal_gateway = null;
                $stripe_gateway = null;
                
                foreach ($payment_gateways as $gateway) {
                    if ($gateway['name'] === 'paypal') {
                        $paypal_gateway = $gateway;
                    } elseif ($gateway['name'] === 'stripe') {
                        $stripe_gateway = $gateway;
                    }
                }
                
                // If gateways not found, create default settings
                if (!$paypal_gateway) {
                    $paypal_gateway = [
                        'id' => 1,
                        'name' => 'paypal',
                        'display_name' => 'PayPal',
                        'description' => 'Accept payments via PayPal',
                        'is_active' => 0,
                        'test_mode' => 1,
                        'logo_url' => '/assets/images/paypal-logo.png',
                        'config' => json_encode([
                            'test' => [
                                'client_id' => '',
                                'client_secret' => '',
                                'webhook_id' => '',
                                'webhook_secret' => ''
                            ],
                            'live' => [
                                'client_id' => '',
                                'client_secret' => '',
                                'webhook_id' => '',
                                'webhook_secret' => ''
                            ]
                        ])
                    ];
                }
                
                if (!$stripe_gateway) {
                    $stripe_gateway = [
                        'id' => 2,
                        'name' => 'stripe',
                        'display_name' => 'Stripe',
                        'description' => 'Accept credit card payments via Stripe',
                        'is_active' => 0,
                        'test_mode' => 1,
                        'logo_url' => '/assets/images/stripe-logo.png',
                        'config' => json_encode([
                            'test' => [
                                'publishable_key' => '',
                                'secret_key' => '',
                                'webhook_secret' => ''
                            ],
                            'live' => [
                                'publishable_key' => '',
                                'secret_key' => '',
                                'webhook_secret' => ''
                            ]
                        ])
                    ];
                }
                
                // Decode config
                $paypal_config = json_decode($paypal_gateway['config'], true) ?: [
                    'test' => ['client_id' => '', 'client_secret' => '', 'webhook_id' => '', 'webhook_secret' => ''],
                    'live' => ['client_id' => '', 'client_secret' => '', 'webhook_id' => '', 'webhook_secret' => '']
                ];
                
                $stripe_config = json_decode($stripe_gateway['config'], true) ?: [
                    'test' => ['publishable_key' => '', 'secret_key' => '', 'webhook_secret' => ''],
                    'live' => ['publishable_key' => '', 'secret_key' => '', 'webhook_secret' => '']
                ];
                ?>
                
                <!-- PayPal Gateway Settings -->
                <div class="bg-white rounded-lg shadow-md <?php echo $darkMode ? 'bg-gray-800' : ''; ?>">
                    <div class="p-6 flex items-center justify-between border-b border-gray-200 <?php echo $darkMode ? 'border-gray-700' : ''; ?>">
                        <div class="flex items-center">
                            <img src="<?php echo htmlspecialchars($paypal_gateway['logo_url'] ?: '/assets/images/paypal-logo.png'); ?>" alt="PayPal Logo" class="h-8 mr-4">
                            <div>
                                <h2 class="text-lg font-semibold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                                    PayPal
                                </h2>
                                <p class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                                    Accept payments via PayPal
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <span class="px-3 py-1 text-xs rounded-full <?php echo $paypal_gateway['is_active'] ? ($darkMode ? 'bg-green-900 text-green-200' : 'bg-green-100 text-green-800') : ($darkMode ? 'bg-gray-700 text-gray-300' : 'bg-gray-100 text-gray-800'); ?>">
                                <?php echo $paypal_gateway['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                            <span class="ml-2 px-3 py-1 text-xs rounded-full <?php echo $paypal_gateway['test_mode'] ? ($darkMode ? 'bg-yellow-900 text-yellow-200' : 'bg-yellow-100 text-yellow-800') : ($darkMode ? 'bg-blue-900 text-blue-200' : 'bg-blue-100 text-blue-800'); ?>">
                                <?php echo $paypal_gateway['test_mode'] ? 'Test Mode' : 'Live Mode'; ?>
                            </span>
                        </div>
                    </div>
                    <div class="p-6">
                        <form method="post" action="?tab=gateways" class="space-y-6">
                            <input type="hidden" name="update_gateway" value="1">
                            <input type="hidden" name="gateway_id" value="<?php echo $paypal_gateway['id']; ?>">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="display_name" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        Display Name
                                    </label>
                                    <input type="text" id="display_name" name="display_name" value="<?php echo htmlspecialchars($paypal_gateway['display_name']); ?>"
                                        class="mt-1 block w-full rounded-md shadow-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'; ?>">
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="is_active" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                            Status
                                        </label>
                                        <div class="mt-1 flex items-center">
                                            <input type="checkbox" id="is_active" name="is_active" value="1" <?php echo $paypal_gateway['is_active'] ? 'checked' : ''; ?>
                                                class="h-4 w-4 rounded <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-blue-500' : 'border-gray-300 text-blue-600 focus:ring-blue-500'; ?>">
                                            <label for="is_active" class="ml-2 block text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                                Enable PayPal
                                            </label>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="test_mode" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                            Mode
                                        </label>
                                        <div class="mt-1 flex items-center">
                                            <input type="checkbox" id="test_mode" name="test_mode" value="1" <?php echo $paypal_gateway['test_mode'] ? 'checked' : ''; ?>
                                                class="h-4 w-4 rounded <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-yellow-500' : 'border-gray-300 text-yellow-600 focus:ring-yellow-500'; ?>">
                                            <label for="test_mode" class="ml-2 block text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                                Test Mode
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <label for="description" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                    Description
                                </label>
                                <textarea id="description" name="description" rows="2"
                                    class="mt-1 block w-full rounded-md shadow-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'; ?>"><?php echo htmlspecialchars($paypal_gateway['description']); ?></textarea>
                                <p class="mt-1 text-xs <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    This description will be shown to customers on the checkout page
                                </p>
                            </div>
                            
                            <!-- Tabs for Test and Live Credentials -->
                            <div class="border-b border-gray-200 <?php echo $darkMode ? 'border-gray-700' : ''; ?>">
                                <ul class="flex -mb-px" role="tablist">
                                    <li class="mr-2" role="presentation">
                                        <button type="button" id="paypal-test-tab" data-tabs-target="paypal-test-credentials" role="tab" aria-selected="true"
                                            class="inline-block py-2 px-4 text-sm font-medium text-center border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 <?php echo $darkMode ? 'text-gray-300 hover:text-gray-200 hover:border-gray-600' : ''; ?>">
                                            Test Credentials
                                        </button>
                                    </li>
                                    <li role="presentation">
                                        <button type="button" id="paypal-live-tab" data-tabs-target="paypal-live-credentials" role="tab" aria-selected="false"
                                            class="inline-block py-2 px-4 text-sm font-medium text-center border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 <?php echo $darkMode ? 'text-gray-300 hover:text-gray-200 hover:border-gray-600' : ''; ?>">
                                            Live Credentials
                                        </button>
                                    </li>
                                </ul>
                            </div>
                            
                            <!-- Test Credentials Section -->
                            <div id="paypal-test-credentials" role="tabpanel" class="space-y-4">
                                <div>
                                    <label for="test_client_id" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        Test Client ID
                                    </label>
                                    <input type="text" id="test_client_id" name="test_client_id" value="<?php echo htmlspecialchars($paypal_config['test']['client_id']); ?>"
                                        class="mt-1 block w-full rounded-md shadow-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'; ?>">
                                </div>
                                <div>
                                    <label for="test_client_secret" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        Test Client Secret
                                    </label>
                                    <input type="password" id="test_client_secret" name="test_client_secret" value="<?php echo htmlspecialchars($paypal_config['test']['client_secret']); ?>"
                                        class="mt-1 block w-full rounded-md shadow-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'; ?>">
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="test_webhook_id" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                            Test Webhook ID
                                        </label>
                                        <input type="text" id="test_webhook_id" name="test_webhook_id" value="<?php echo htmlspecialchars($paypal_config['test']['webhook_id']); ?>"
                                            class="mt-1 block w-full rounded-md shadow-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'; ?>">
                                    </div>
                                    <div>
                                        <label for="test_webhook_secret" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                            Test Webhook Secret
                                        </label>
                                        <input type="password" id="test_webhook_secret" name="test_webhook_secret" value="<?php echo htmlspecialchars($paypal_config['test']['webhook_secret']); ?>"
                                            class="mt-1 block w-full rounded-md shadow-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Live Credentials Section (Hidden Initially) -->
                            <div id="paypal-live-credentials" role="tabpanel" class="hidden space-y-4">
                                <div>
                                    <label for="live_client_id" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        Live Client ID
                                    </label>
                                    <input type="text" id="live_client_id" name="live_client_id" value="<?php echo htmlspecialchars($paypal_config['live']['client_id']); ?>"
                                        class="mt-1 block w-full rounded-md shadow-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'; ?>">
                                </div>
                                <div>
                                    <label for="live_client_secret" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        Live Client Secret
                                    </label>
                                    <input type="password" id="live_client_secret" name="live_client_secret" value="<?php echo htmlspecialchars($paypal_config['live']['client_secret']); ?>"
                                        class="mt-1 block w-full rounded-md shadow-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'; ?>">
                                </div>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label for="live_webhook_id" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                            Live Webhook ID
                                        </label>
                                        <input type="text" id="live_webhook_id" name="live_webhook_id" value="<?php echo htmlspecialchars($paypal_config['live']['webhook_id']); ?>"
                                            class="mt-1 block w-full rounded-md shadow-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'; ?>">
                                    </div>
                                    <div>
                                        <label for="live_webhook_secret" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                            Live Webhook Secret
                                        </label>
                                        <input type="password" id="live_webhook_secret" name="live_webhook_secret" value="<?php echo htmlspecialchars($paypal_config['live']['webhook_secret']); ?>"
                                            class="mt-1 block w-full rounded-md shadow-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'; ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="flex justify-end">
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 <?php echo $darkMode ? 'focus:ring-offset-gray-800' : ''; ?>">
                                    <i class="fas fa-save mr-2"></i> Save PayPal Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Stripe Gateway Settings -->
                <div class="bg-white rounded-lg shadow-md <?php echo $darkMode ? 'bg-gray-800' : ''; ?>">
                    <div class="p-6 flex items-center justify-between border-b border-gray-200 <?php echo $darkMode ? 'border-gray-700' : ''; ?>">
                        <div class="flex items-center">
                            <img src="<?php echo htmlspecialchars($stripe_gateway['logo_url'] ?: '/assets/images/stripe-logo.png'); ?>" alt="Stripe Logo" class="h-8 mr-4">
                            <div>
                                <h2 class="text-lg font-semibold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                                    Stripe
                                </h2>
                                <p class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                                    Accept credit card payments via Stripe
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center">
                            <span class="px-3 py-1 text-xs rounded-full <?php echo $stripe_gateway['is_active'] ? ($darkMode ? 'bg-green-900 text-green-200' : 'bg-green-100 text-green-800') : ($darkMode ? 'bg-gray-700 text-gray-300' : 'bg-gray-100 text-gray-800'); ?>">
                                <?php echo $stripe_gateway['is_active'] ? 'Active' : 'Inactive'; ?>
                            </span>
                            <span class="ml-2 px-3 py-1 text-xs rounded-full <?php echo $stripe_gateway['test_mode'] ? ($darkMode ? 'bg-yellow-900 text-yellow-200' : 'bg-yellow-100 text-yellow-800') : ($darkMode ? 'bg-blue-900 text-blue-200' : 'bg-blue-100 text-blue-800'); ?>">
                                <?php echo $stripe_gateway['test_mode'] ? 'Test Mode' : 'Live Mode'; ?>
                            </span>
                        </div>
                    </div>
                    <div class="p-6">
                        <form method="post" action="?tab=gateways" class="space-y-6">
                            <input type="hidden" name="update_gateway" value="1">
                            <input type="hidden" name="gateway_id" value="<?php echo $stripe_gateway['id']; ?>">
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label for="stripe_display_name" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        Display Name
                                    </label>
                                    <input type="text" id="stripe_display_name" name="display_name" value="<?php echo htmlspecialchars($stripe_gateway['display_name']); ?>"
                                        class="mt-1 block w-full rounded-md shadow-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'; ?>">
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label for="stripe_is_active" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                            Status
                                        </label>
                                        <div class="mt-1 flex items-center">
                                            <input type="checkbox" id="stripe_is_active" name="is_active" value="1" <?php echo $stripe_gateway['is_active'] ? 'checked' : ''; ?>
                                                class="h-4 w-4 rounded <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-blue-500' : 'border-gray-300 text-blue-600 focus:ring-blue-500'; ?>">
                                            <label for="stripe_is_active" class="ml-2 block text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                                Enable Stripe
                                            </label>
                                        </div>
                                    </div>
                                    <div>
                                        <label for="stripe_test_mode" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                            Mode
                                        </label>
                                        <div class="mt-1 flex items-center">
                                            <input type="checkbox" id="stripe_test_mode" name="test_mode" value="1" <?php echo $stripe_gateway['test_mode'] ? 'checked' : ''; ?>
                                                class="h-4 w-4 rounded <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-yellow-500' : 'border-gray-300 text-yellow-600 focus:ring-yellow-500'; ?>">
                                            <label for="stripe_test_mode" class="ml-2 block text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                                Test Mode
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div>
                                <label for="stripe_description" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                    Description
                                </label>
                                <textarea id="stripe_description" name="description" rows="2"
                                    class="mt-1 block w-full rounded-md shadow-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'; ?>"><?php echo htmlspecialchars($stripe_gateway['description']); ?></textarea>
                                <p class="mt-1 text-xs <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    This description will be shown to customers on the checkout page
                                </p>
                            </div>
                            
                            <!-- Tabs for Test and Live Credentials -->
                            <div class="border-b border-gray-200 <?php echo $darkMode ? 'border-gray-700' : ''; ?>">
                                <ul class="flex -mb-px" role="tablist">
                                    <li class="mr-2" role="presentation">
                                        <button type="button" id="stripe-test-tab" data-tabs-target="stripe-test-credentials" role="tab" aria-selected="true"
                                            class="inline-block py-2 px-4 text-sm font-medium text-center border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 <?php echo $darkMode ? 'text-gray-300 hover:text-gray-200 hover:border-gray-600' : ''; ?>">
                                            Test Credentials
                                        </button>
                                    </li>
                                    <li role="presentation">
                                        <button type="button" id="stripe-live-tab" data-tabs-target="stripe-live-credentials" role="tab" aria-selected="false"
                                            class="inline-block py-2 px-4 text-sm font-medium text-center border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 <?php echo $darkMode ? 'text-gray-300 hover:text-gray-200 hover:border-gray-600' : ''; ?>">
                                            Live Credentials
                                        </button>
                                    </li>
                                </ul>
                            </div>
                            
                            <!-- Test Credentials Section -->
                            <div id="stripe-test-credentials" role="tabpanel" class="space-y-4">
                                <div>
                                    <label for="test_publishable_key" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        Test Publishable Key
                                    </label>
                                    <input type="text" id="test_publishable_key" name="test_publishable_key" value="<?php echo htmlspecialchars($stripe_config['test']['publishable_key']); ?>"
                                        class="mt-1 block w-full rounded-md shadow-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'; ?>">
                                </div>
                                <div>
                                    <label for="test_secret_key" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        Test Secret Key
                                    </label>
                                    <input type="password" id="test_secret_key" name="test_secret_key" value="<?php echo htmlspecialchars($stripe_config['test']['secret_key']); ?>"
                                        class="mt-1 block w-full rounded-md shadow-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'; ?>">
                                </div>
                                <div>
                                    <label for="test_webhook_secret" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        Test Webhook Secret
                                    </label>
                                    <input type="password" id="test_webhook_secret" name="test_webhook_secret" value="<?php echo htmlspecialchars($stripe_config['test']['webhook_secret']); ?>"
                                        class="mt-1 block w-full rounded-md shadow-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'; ?>">
                                </div>
                            </div>
                            
                            <!-- Live Credentials Section (Hidden Initially) -->
                            <div id="stripe-live-credentials" role="tabpanel" class="hidden space-y-4">
                                <div>
                                    <label for="live_publishable_key" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        Live Publishable Key
                                    </label>
                                    <input type="text" id="live_publishable_key" name="live_publishable_key" value="<?php echo htmlspecialchars($stripe_config['live']['publishable_key']); ?>"
                                        class="mt-1 block w-full rounded-md shadow-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'; ?>">
                                </div>
                                <div>
                                    <label for="live_secret_key" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        Live Secret Key
                                    </label>
                                    <input type="password" id="live_secret_key" name="live_secret_key" value="<?php echo htmlspecialchars($stripe_config['live']['secret_key']); ?>"
                                        class="mt-1 block w-full rounded-md shadow-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'; ?>">
                                </div>
                                <div>
                                    <label for="live_webhook_secret" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        Live Webhook Secret
                                    </label>
                                    <input type="password" id="live_webhook_secret" name="live_webhook_secret" value="<?php echo htmlspecialchars($stripe_config['live']['webhook_secret']); ?>"
                                        class="mt-1 block w-full rounded-md shadow-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'; ?>">
                                </div>
                            </div>
                            
                            <div class="flex justify-end">
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 <?php echo $darkMode ? 'focus:ring-offset-gray-800' : ''; ?>">
                                    <i class="fas fa-save mr-2"></i> Save Stripe Settings
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Transactions Tab -->
        <?php if ($activeTab === 'transactions'): ?>
            <!-- Summary Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
                <div class="bg-white shadow-md rounded-lg p-6 <?php echo $darkMode ? 'bg-gray-800' : ''; ?>">
                    <div class="flex items-center">
                        <div class="bg-blue-100 rounded-full p-3 <?php echo $darkMode ? 'bg-blue-900' : ''; ?>">
                            <i class="fas fa-wallet text-blue-600 text-xl <?php echo $darkMode ? 'text-blue-300' : ''; ?>"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">Total Revenue</h3>
                            <p class="text-xl font-semibold <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">$<?php echo number_format($total_revenue, 2); ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white shadow-md rounded-lg p-6 <?php echo $darkMode ? 'bg-gray-800' : ''; ?>">
                    <div class="flex items-center">
                        <div class="bg-green-100 rounded-full p-3 <?php echo $darkMode ? 'bg-green-900' : ''; ?>">
                            <i class="fas fa-check-circle text-green-600 text-xl <?php echo $darkMode ? 'text-green-300' : ''; ?>"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">Completed Payments</h3>
                            <p class="text-xl font-semibold <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>"><?php echo $completed_count; ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white shadow-md rounded-lg p-6 <?php echo $darkMode ? 'bg-gray-800' : ''; ?>">
                    <div class="flex items-center">
                        <div class="bg-yellow-100 rounded-full p-3 <?php echo $darkMode ? 'bg-yellow-900' : ''; ?>">
                            <i class="fas fa-clock text-yellow-600 text-xl <?php echo $darkMode ? 'text-yellow-300' : ''; ?>"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">Pending Revenue</h3>
                            <p class="text-xl font-semibold <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">$<?php echo number_format($total_pending, 2); ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white shadow-md rounded-lg p-6 <?php echo $darkMode ? 'bg-gray-800' : ''; ?>">
                    <div class="flex items-center">
                        <div class="bg-purple-100 rounded-full p-3 <?php echo $darkMode ? 'bg-purple-900' : ''; ?>">
                            <i class="fas fa-exchange-alt text-purple-600 text-xl <?php echo $darkMode ? 'text-purple-300' : ''; ?>"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">Total Transactions</h3>
                            <p class="text-xl font-semibold <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>"><?php echo $total_transactions; ?></p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Transactions Filter and Search -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6 <?php echo $darkMode ? 'bg-gray-800' : ''; ?>">
                <form action="?tab=transactions" method="get" class="space-y-4">
                    <input type="hidden" name="tab" value="transactions">
                    
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                        <div>
                            <label for="status" class="block text-sm font-medium mb-1 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                Status
                            </label>
                            <select id="status" name="status" class="block w-full rounded-md shadow-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'; ?>">
                                <option value="">All Statuses</option>
                                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="failed" <?php echo $status_filter === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                <option value="refunded" <?php echo $status_filter === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                                <option value="cancelled" <?php echo $status_filter === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div>
                            <label for="method" class="block text-sm font-medium mb-1 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                Payment Method
                            </label>
                            <select id="method" name="method" class="block w-full rounded-md shadow-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'; ?>">
                                <option value="">All Methods</option>
                                <option value="stripe" <?php echo $method_filter === 'stripe' ? 'selected' : ''; ?>>Stripe</option>
                                <option value="paypal" <?php echo $method_filter === 'paypal' ? 'selected' : ''; ?>>PayPal</option>
                            </select>
                        </div>
                        <div>
                            <label for="date_start" class="block text-sm font-medium mb-1 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                Start Date
                            </label>
                            <input type="date" id="date_start" name="date_start" value="<?php echo htmlspecialchars($date_start); ?>"
                                class="block w-full rounded-md shadow-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'; ?>">
                        </div>
                        <div>
                            <label for="date_end" class="block text-sm font-medium mb-1 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                End Date
                            </label>
                            <input type="date" id="date_end" name="date_end" value="<?php echo htmlspecialchars($date_end); ?>"
                                class="block w-full rounded-md shadow-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'; ?>">
                        </div>
                    </div>
                    
                    <div class="flex items-end justify-between">
                        <div class="w-full md:w-1/3">
                            <label for="search" class="block text-sm font-medium mb-1 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                Search
                            </label>
                            <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Transaction ID, username or email..."
                                class="block w-full rounded-md shadow-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'; ?>">
                        </div>
                        
                        <div class="flex space-x-2">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 <?php echo $darkMode ? 'focus:ring-offset-gray-800' : ''; ?>">
                                <i class="fas fa-search mr-2"></i> Search
                            </button>
                            <a href="?tab=transactions" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-400 focus:ring-offset-2 <?php echo $darkMode ? 'focus:ring-offset-gray-800' : ''; ?>">
                                <i class="fas fa-times mr-2"></i> Reset
                            </a>
                            <a href="export.php<?php echo !empty($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : ''; ?>" class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 <?php echo $darkMode ? 'focus:ring-offset-gray-800' : ''; ?>">
                                <i class="fas fa-file-export mr-2"></i> Export
                            </a>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Transactions Table -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden <?php echo $darkMode ? 'bg-gray-800' : ''; ?>">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-700' : ''; ?>">
                        <thead class="<?php echo $darkMode ? 'bg-gray-700' : 'bg-gray-50'; ?>">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">User</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">Amount</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">Method</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">Status</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">Transaction ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">Date</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="<?php echo $darkMode ? 'bg-gray-800 divide-gray-700' : 'bg-white divide-gray-200'; ?>">
                            <?php if (empty($transactions)): ?>
                                <tr>
                                                                        <td colspan="8" class="px-6 py-4 text-center <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                        No transactions found.
                                        <?php if (!empty($status_filter) || !empty($method_filter) || !empty($date_start) || !empty($date_end) || !empty($search)): ?>
                                            <br>Try adjusting your filters.
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($transactions as $transaction): ?>
                                    <tr class="<?php echo $darkMode ? 'hover:bg-gray-700' : 'hover:bg-gray-50'; ?>">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                            <?php echo $transaction['id']; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">
                                            <div class="flex flex-col">
                                                <span class="font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>"><?php echo htmlspecialchars($transaction['username']); ?></span>
                                                <span class="text-xs <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>"><?php echo htmlspecialchars($transaction['email']); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium <?php echo $darkMode ? 'text-green-400' : 'text-green-600'; ?>">
                                            $<?php echo number_format($transaction['amount'], 2); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php 
                                                if ($transaction['payment_method'] === 'paypal') {
                                                    echo $darkMode ? 'bg-blue-900 text-blue-200' : 'bg-blue-100 text-blue-800';
                                                } else {
                                                    echo $darkMode ? 'bg-purple-900 text-purple-200' : 'bg-purple-100 text-purple-800';
                                                }
                                            ?>">
                                                <?php echo ucfirst($transaction['payment_method']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php 
                                                switch ($transaction['status']) {
                                                    case 'completed':
                                                        echo $darkMode ? 'bg-green-900 text-green-200' : 'bg-green-100 text-green-800';
                                                        break;
                                                    case 'pending':
                                                        echo $darkMode ? 'bg-yellow-900 text-yellow-200' : 'bg-yellow-100 text-yellow-800';
                                                        break;
                                                    case 'failed':
                                                        echo $darkMode ? 'bg-red-900 text-red-200' : 'bg-red-100 text-red-800';
                                                        break;
                                                    case 'refunded':
                                                        echo $darkMode ? 'bg-blue-900 text-blue-200' : 'bg-blue-100 text-blue-800';
                                                        break;
                                                    case 'cancelled':
                                                        echo $darkMode ? 'bg-gray-700 text-gray-300' : 'bg-gray-100 text-gray-800';
                                                        break;
                                                    default:
                                                        echo $darkMode ? 'bg-gray-700 text-gray-300' : 'bg-gray-100 text-gray-800';
                                                }
                                            ?>">
                                                <?php echo ucfirst($transaction['status']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">
                                            <?php echo !empty($transaction['transaction_id']) ? htmlspecialchars($transaction['transaction_id']) : '<span class="italic text-gray-400">None</span>'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">
                                            <?php echo date('Y-m-d H:i', strtotime($transaction['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button type="button" onclick="openEditModal(<?php echo $transaction['id']; ?>, '<?php echo $transaction['status']; ?>', '<?php echo htmlspecialchars($transaction['notes'] ?? ''); ?>')" class="text-blue-600 hover:text-blue-900 <?php echo $darkMode ? 'text-blue-400 hover:text-blue-300' : ''; ?> mr-3">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            <button type="button" onclick="openDeleteModal(<?php echo $transaction['id']; ?>)" class="text-red-600 hover:text-red-900 <?php echo $darkMode ? 'text-red-400 hover:text-red-300' : ''; ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                    <div class="px-6 py-4 border-t border-gray-200 <?php echo $darkMode ? 'border-gray-700' : ''; ?>">
                        <div class="flex items-center justify-between">
                            <div class="flex-1 flex justify-between sm:hidden">
                                <?php if ($page > 1): ?>
                                    <a href="?tab=transactions&page=<?php echo $page - 1; ?><?php echo (!empty($status_filter) ? '&status=' . urlencode($status_filter) : ''); ?><?php echo (!empty($method_filter) ? '&method=' . urlencode($method_filter) : ''); ?><?php echo (!empty($date_start) ? '&date_start=' . urlencode($date_start) : ''); ?><?php echo (!empty($date_end) ? '&date_end=' . urlencode($date_end) : ''); ?><?php echo (!empty($search) ? '&search=' . urlencode($search) : ''); ?>" class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md <?php echo $darkMode ? 'bg-gray-700 text-gray-300 border-gray-600' : 'bg-white text-gray-700'; ?> hover:bg-gray-50">
                                        Previous
                                    </a>
                                <?php else: ?>
                                    <span class="relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md <?php echo $darkMode ? 'bg-gray-800 text-gray-500 border-gray-700' : 'bg-gray-100 text-gray-500'; ?> cursor-not-allowed">
                                        Previous
                                    </span>
                                <?php endif; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <a href="?tab=transactions&page=<?php echo $page + 1; ?><?php echo (!empty($status_filter) ? '&status=' . urlencode($status_filter) : ''); ?><?php echo (!empty($method_filter) ? '&method=' . urlencode($method_filter) : ''); ?><?php echo (!empty($date_start) ? '&date_start=' . urlencode($date_start) : ''); ?><?php echo (!empty($date_end) ? '&date_end=' . urlencode($date_end) : ''); ?><?php echo (!empty($search) ? '&search=' . urlencode($search) : ''); ?>" class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md <?php echo $darkMode ? 'bg-gray-700 text-gray-300 border-gray-600' : 'bg-white text-gray-700'; ?> hover:bg-gray-50">
                                        Next
                                    </a>
                                <?php else: ?>
                                    <span class="ml-3 relative inline-flex items-center px-4 py-2 border border-gray-300 text-sm font-medium rounded-md <?php echo $darkMode ? 'bg-gray-800 text-gray-500 border-gray-700' : 'bg-gray-100 text-gray-500'; ?> cursor-not-allowed">
                                        Next
                                    </span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-700'; ?>">
                                        Showing <span class="font-medium"><?php echo ($page - 1) * $per_page + 1; ?></span> to <span class="font-medium"><?php echo min($page * $per_page, $total_transactions); ?></span> of <span class="font-medium"><?php echo $total_transactions; ?></span> results
                                    </p>
                                </div>
                                
                                <div>
                                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                        <?php if ($page > 1): ?>
                                            <a href="?tab=transactions&page=<?php echo $page - 1; ?><?php echo (!empty($status_filter) ? '&status=' . urlencode($status_filter) : ''); ?><?php echo (!empty($method_filter) ? '&method=' . urlencode($method_filter) : ''); ?><?php echo (!empty($date_start) ? '&date_start=' . urlencode($date_start) : ''); ?><?php echo (!empty($date_end) ? '&date_end=' . urlencode($date_end) : ''); ?><?php echo (!empty($search) ? '&search=' . urlencode($search) : ''); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 <?php echo $darkMode ? 'bg-gray-700 text-gray-300 border-gray-600' : 'bg-white text-gray-500'; ?> hover:bg-gray-50">
                                                <span class="sr-only">Previous</span>
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 <?php echo $darkMode ? 'bg-gray-800 text-gray-500 border-gray-700' : 'bg-gray-100 text-gray-400'; ?> cursor-not-allowed">
                                                <span class="sr-only">Previous</span>
                                                <i class="fas fa-chevron-left"></i>
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php
                                        $start_page = max(1, $page - 2);
                                        $end_page = min($total_pages, $page + 2);
                                        
                                        for($i = $start_page; $i <= $end_page; $i++):
                                        ?>
                                            <a href="?tab=transactions&page=<?php echo $i; ?><?php echo (!empty($status_filter) ? '&status=' . urlencode($status_filter) : ''); ?><?php echo (!empty($method_filter) ? '&method=' . urlencode($method_filter) : ''); ?><?php echo (!empty($date_start) ? '&date_start=' . urlencode($date_start) : ''); ?><?php echo (!empty($date_end) ? '&date_end=' . urlencode($date_end) : ''); ?><?php echo (!empty($search) ? '&search=' . urlencode($search) : ''); ?>" aria-current="page" class="relative inline-flex items-center px-4 py-2 border text-sm font-medium <?php echo $i === $page ? ($darkMode ? 'bg-gray-900 text-blue-400 border-blue-400' : 'bg-blue-50 text-blue-600 border-blue-500') : ($darkMode ? 'bg-gray-700 text-gray-300 border-gray-600 hover:bg-gray-600' : 'bg-white text-gray-500 border-gray-300 hover:bg-gray-50'); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        <?php endfor; ?>
                                        
                                        <?php if ($page < $total_pages): ?>
                                            <a href="?tab=transactions&page=<?php echo $page + 1; ?><?php echo (!empty($status_filter) ? '&status=' . urlencode($status_filter) : ''); ?><?php echo (!empty($method_filter) ? '&method=' . urlencode($method_filter) : ''); ?><?php echo (!empty($date_start) ? '&date_start=' . urlencode($date_start) : ''); ?><?php echo (!empty($date_end) ? '&date_end=' . urlencode($date_end) : ''); ?><?php echo (!empty($search) ? '&search=' . urlencode($search) : ''); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 <?php echo $darkMode ? 'bg-gray-700 text-gray-300 border-gray-600' : 'bg-white text-gray-500'; ?> hover:bg-gray-50">
                                                <span class="sr-only">Next</span>
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        <?php else: ?>
                                            <span class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 <?php echo $darkMode ? 'bg-gray-800 text-gray-500 border-gray-700' : 'bg-gray-100 text-gray-400'; ?> cursor-not-allowed">
                                                <span class="sr-only">Next</span>
                                                <i class="fas fa-chevron-right"></i>
                                            </span>
                                        <?php endif; ?>
                                    </nav>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <!-- Last Update Info -->
        <div class="mt-6 text-right text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
            Last Updated: <?php echo $currentDateTime; ?> (UTC)
        </div>
    </div>
</div>

<!-- Edit Transaction Modal -->
<div id="editModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-modal="true" role="dialog">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full <?php echo $darkMode ? 'bg-gray-800' : ''; ?>">
            <form id="editTransactionForm" method="post" action="?tab=transactions">
                <input type="hidden" name="update_transaction" value="1">
                <input type="hidden" name="transaction_id" id="edit_transaction_id">
                
                <div class="<?php echo $darkMode ? 'bg-gray-800' : 'bg-white'; ?> px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 sm:mx-0 sm:h-10 sm:w-10 <?php echo $darkMode ? 'bg-blue-900' : ''; ?>">
                            <i class="fas fa-edit text-blue-600 <?php echo $darkMode ? 'text-blue-300' : ''; ?>"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>" id="modal-title">
                                Edit Transaction
                            </h3>
                            <div class="mt-4 space-y-4">
                                <div>
                                    <label for="edit_status" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        Status
                                    </label>
                                    <select id="edit_status" name="status" class="mt-1 block w-full rounded-md shadow-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'; ?>">
                                        <option value="pending">Pending</option>
                                        <option value="completed">Completed</option>
                                        <option value="failed">Failed</option>
                                        <option value="refunded">Refunded</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="edit_notes" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        Notes
                                    </label>
                                    <textarea id="edit_notes" name="notes" rows="3" class="mt-1 block w-full rounded-md shadow-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'border-gray-300 focus:ring-blue-500 focus:border-blue-500'; ?>"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="<?php echo $darkMode ? 'bg-gray-800' : 'bg-gray-50'; ?> px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Update
                    </button>
                    <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 <?php echo $darkMode ? 'bg-gray-700 text-gray-300 hover:bg-gray-600' : 'bg-white text-gray-700 hover:bg-gray-50'; ?> focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeEditModal()">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Transaction Modal -->
<div id="deleteModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-modal="true" role="dialog">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full <?php echo $darkMode ? 'bg-gray-800' : ''; ?>">
            <div class="<?php echo $darkMode ? 'bg-gray-800' : 'bg-white'; ?> px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10 <?php echo $darkMode ? 'bg-red-900' : ''; ?>">
                        <i class="fas fa-exclamation-triangle text-red-600 <?php echo $darkMode ? 'text-red-300' : ''; ?>"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>" id="modal-title">
                            Cancel Transaction
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">
                                Are you sure you want to cancel this transaction? This action cannot be undone.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="<?php echo $darkMode ? 'bg-gray-800' : 'bg-gray-50'; ?> px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <a href="#" id="confirmDelete" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancel Transaction
                </a>
                <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 <?php echo $darkMode ? 'bg-gray-700 text-gray-300 hover:bg-gray-600' : 'bg-white text-gray-700 hover:bg-gray-50'; ?> focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm" onclick="closeDeleteModal()">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for the page -->
<script>
    // Tab handling for payment gateway credentials
    document.addEventListener('DOMContentLoaded', function() {
        // PayPal tabs
        const paypalTestTab = document.getElementById('paypal-test-tab');
        const paypalLiveTab = document.getElementById('paypal-live-tab');
        const paypalTestContent = document.getElementById('paypal-test-credentials');
        const paypalLiveContent = document.getElementById('paypal-live-credentials');
        
        if (paypalTestTab && paypalLiveTab) {
            paypalTestTab.addEventListener('click', function() {
                paypalTestTab.classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
                paypalLiveTab.classList.remove('text-blue-600', 'border-b-2', 'border-blue-600');
                paypalTestContent.classList.remove('hidden');
                paypalLiveContent.classList.add('hidden');
            });
            
            paypalLiveTab.addEventListener('click', function() {
                paypalLiveTab.classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
                paypalTestTab.classList.remove('text-blue-600', 'border-b-2', 'border-blue-600');
                paypalLiveContent.classList.remove('hidden');
                paypalTestContent.classList.add('hidden');
            });
        }
        
        // Stripe tabs
        const stripeTestTab = document.getElementById('stripe-test-tab');
        const stripeLiveTab = document.getElementById('stripe-live-tab');
        const stripeTestContent = document.getElementById('stripe-test-credentials');
        const stripeLiveContent = document.getElementById('stripe-live-credentials');
        
        if (stripeTestTab && stripeLiveTab) {
            stripeTestTab.addEventListener('click', function() {
                stripeTestTab.classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
                stripeLiveTab.classList.remove('text-blue-600', 'border-b-2', 'border-blue-600');
                stripeTestContent.classList.remove('hidden');
                stripeLiveContent.classList.add('hidden');
            });
            
            stripeLiveTab.addEventListener('click', function() {
                stripeLiveTab.classList.add('text-blue-600', 'border-b-2', 'border-blue-600');
                stripeTestTab.classList.remove('text-blue-600', 'border-b-2', 'border-blue-600');
                stripeLiveContent.classList.remove('hidden');
                stripeTestContent.classList.add('hidden');
            });
        }
    });
    
    // Edit transaction modal functions
    function openEditModal(id, status, notes) {
        document.getElementById('edit_transaction_id').value = id;
        document.getElementById('edit_status').value = status;
        document.getElementById('edit_notes').value = notes;
        document.getElementById('editModal').classList.remove('hidden');
    }
    
    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }
    
    // Delete transaction modal functions
    function openDeleteModal(id) {
        document.getElementById('confirmDelete').href = '?tab=transactions&delete=' + id;
        document.getElementById('deleteModal').classList.remove('hidden');
    }
    
    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }
    
    // Close modals when clicking outside
    window.addEventListener('click', function(event) {
        const editModal = document.getElementById('editModal');
        const deleteModal = document.getElementById('deleteModal');
        
        if (event.target === editModal) {
            closeEditModal();
        }
        
        if (event.target === deleteModal) {
            closeDeleteModal();
        }
    });
    
    // Handle escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeEditModal();
            closeDeleteModal();
        }
    });
</script>

<?php
// Include footer
require_once '../../theme/admin/footer.php';
?>