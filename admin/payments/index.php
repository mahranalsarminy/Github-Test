<?php
/**
 * Payment Gateway Management
 *
 * @package WallPix
 * @version 1.0.0
 */

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

// Set page title
$pageTitle = 'Payment Gateway Settings';

// Process form submission
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['save_gateway'])) {
            $gateway_id = isset($_POST['gateway_id']) ? (int)$_POST['gateway_id'] : 0;
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $test_mode = isset($_POST['test_mode']) ? 1 : 0;
            $display_name = isset($_POST['display_name']) ? trim($_POST['display_name']) : '';
            $description = isset($_POST['description']) ? trim($_POST['description']) : '';
            
            // Get existing configuration
            $stmt = $pdo->prepare("SELECT * FROM payment_gateways WHERE id = ?");
            $stmt->execute([$gateway_id]);
            $gateway = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$gateway) {
                throw new Exception("Gateway not found");
            }
            
            // Decode existing config
            $config = json_decode($gateway['config'], true);
            
            // Update config based on gateway type
            if ($gateway['name'] === 'paypal') {
                // Test credentials
                $config['test']['client_id'] = trim($_POST['paypal_test_client_id'] ?? '');
                $config['test']['client_secret'] = trim($_POST['paypal_test_client_secret'] ?? '');
                $config['test']['webhook_id'] = trim($_POST['paypal_test_webhook_id'] ?? '');
                
                // Live credentials
                $config['live']['client_id'] = trim($_POST['paypal_live_client_id'] ?? '');
                $config['live']['client_secret'] = trim($_POST['paypal_live_client_secret'] ?? '');
                $config['live']['webhook_id'] = trim($_POST['paypal_live_webhook_id'] ?? '');
            } 
            else if ($gateway['name'] === 'stripe') {
                // Test credentials
                $config['test']['publishable_key'] = trim($_POST['stripe_test_publishable_key'] ?? '');
                $config['test']['secret_key'] = trim($_POST['stripe_test_secret_key'] ?? '');
                $config['test']['webhook_secret'] = trim($_POST['stripe_test_webhook_secret'] ?? '');
                
                // Live credentials
                $config['live']['publishable_key'] = trim($_POST['stripe_live_publishable_key'] ?? '');
                $config['live']['secret_key'] = trim($_POST['stripe_live_secret_key'] ?? '');
                $config['live']['webhook_secret'] = trim($_POST['stripe_live_webhook_secret'] ?? '');
            }
            
            // Update database
            $stmt = $pdo->prepare("
                UPDATE payment_gateways 
                SET is_active = ?, 
                    test_mode = ?, 
                    display_name = ?, 
                    description = ?, 
                    config = ?,
                    updated_by = ?,
                    updated_at = NOW()
                WHERE id = ?");
            
            $stmt->execute([
                $is_active,
                $test_mode,
                $display_name,
                $description,
                json_encode($config),
                $gateway_id
            ]);
            
            // Add to activity log
            $stmt = $pdo->prepare("INSERT INTO activities (user_id, description) VALUES ((SELECT id FROM users WHERE username = ?), ?)");
            $stmt->execute(['mahranalsarminy', "Updated {$gateway['name']} payment gateway settings"]);
            
            $message = "Payment gateway settings updated successfully!";
            $messageType = "success";
        }
    } catch (Exception $e) {
        $message = "Error: " . $e->getMessage();
        $messageType = "error";
    }
}

// Get payment gateways from database
try {
    $stmt = $pdo->query("SELECT * FROM payment_gateways ORDER BY display_name");
    $gateways = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $gateways = [];
    $message = "Error loading payment gateways: " . $e->getMessage();
    $messageType = "error";
}

// Check if payment_gateways table exists, if not suggest creating it
if (empty($gateways)) {
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'payment_gateways'");
        if ($stmt->rowCount() === 0) {
            $message = "The payment_gateways table does not exist. Please run the SQL script to create it.";
            $messageType = "error";
        }
    } catch (PDOException $e) {
        // Table check failed
    }
}

// Current date and time information
$currentDateTime = '2025-03-20 07:14:06'; // UTC time
$currentUser = 'mahranalsarminy';

// Include header and sidebar
include '../../theme/admin/header.php';
// Include sidebar
require_once '../../theme/admin/slidbar.php';
?>
<!-- Main Content -->

    <!-- Main Content Area -->
    <div class="content-wrapper min-h-screen bg-gray-100 <?php echo $darkMode ? 'dark-mode bg-gray-900' : ''; ?> ml-0 md:ml-64 transition-all duration-300">
        <div class="px-6 py-8">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                <div>
                    <h1 class="text-3xl font-bold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                        <i class="fas fa-credit-card"></i> <?php echo $lang['payment_gateways'] ?? 'Payment Gateways'; ?>
                    </h1>
                    <p class="mt-1 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                        <?php echo $lang['payment_gateways_desc'] ?? 'Configure payment methods for your site'; ?>
                    </p>
                </div>
                <div class="mt-4 md:mt-0">
                    <span class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                        <i class="fas fa-clock"></i> <?php echo $currentDateTime; ?>
                    </span>
                </div>
            </div>

            <?php if ($message): ?>
            <div class="mb-6 px-4 py-3 rounded-md <?php echo $messageType === 'success' ? ($darkMode ? 'bg-green-800 text-green-200' : 'bg-green-100 text-green-800') : ($darkMode ? 'bg-red-800 text-red-200' : 'bg-red-100 text-red-800'); ?>">
                <?php echo $message; ?>
            </div>
            <?php endif; ?>

            <!-- Payment Gateways Navigation -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden <?php echo $darkMode ? 'bg-gray-800' : ''; ?> mb-6">
                <div class="px-6 py-4 border-b <?php echo $darkMode ? 'border-gray-700' : 'border-gray-200'; ?>">
                    <h2 class="text-lg font-semibold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                        <?php echo $lang['available_payment_methods'] ?? 'Available Payment Methods'; ?>
                    </h2>
                    <p class="mt-1 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                        <?php echo $lang['payment_methods_desc'] ?? 'Enable and configure payment methods for your website'; ?>
                    </p>
                </div>
                
                <div class="px-6 py-4">
                    <div class="mb-4">
                        <div role="tablist" class="flex flex-wrap border-b <?php echo $darkMode ? 'border-gray-700' : 'border-gray-200'; ?>">
                            <?php foreach ($gateways as $index => $gateway): ?>
                            <button id="tab-<?php echo $gateway['name']; ?>" 
                                    role="tab" 
                                    aria-selected="<?php echo $index === 0 ? 'true' : 'false'; ?>" 
                                    aria-controls="panel-<?php echo $gateway['name']; ?>"
                                    class="py-2 px-4 font-medium text-sm focus:outline-none <?php echo $index === 0 ? ($darkMode ? 'text-blue-400 border-b-2 border-blue-400' : 'text-blue-600 border-b-2 border-blue-600') : ($darkMode ? 'text-gray-400 hover:text-blue-400' : 'text-gray-600 hover:text-blue-600'); ?>">
                                <?php echo htmlspecialchars($gateway['display_name']); ?>
                                <?php if ($gateway['is_active']): ?>
                                    <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-green-100 text-green-800 <?php echo $darkMode ? 'bg-green-900 text-green-200' : ''; ?>">
                                        <?php echo $lang['active'] ?? 'Active'; ?>
                                    </span>
                                <?php endif; ?>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <?php foreach ($gateways as $index => $gateway): ?>
                    <div id="panel-<?php echo $gateway['name']; ?>" 
                         role="tabpanel" 
                         aria-labelledby="tab-<?php echo $gateway['name']; ?>"
                         class="gateway-panel <?php echo $index === 0 ? 'block' : 'hidden'; ?>">
                         
                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="space-y-6">
                            <input type="hidden" name="gateway_id" value="<?php echo $gateway['id']; ?>">
                            
                            <div class="flex items-center justify-between pb-4 mb-4 border-b <?php echo $darkMode ? 'border-gray-700' : 'border-gray-200'; ?>">
                                <div class="flex items-center">
                                    <?php if (!empty($gateway['logo_url'])): ?>
                                        <img src="<?php echo htmlspecialchars($gateway['logo_url']); ?>" alt="<?php echo htmlspecialchars($gateway['display_name']); ?> Logo" class="h-10 mr-3">
                                    <?php else: ?>
                                        <div class="h-10 w-10 rounded bg-gray-200 flex items-center justify-center mr-3 <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                                            <i class="fas fa-credit-card text-gray-500"></i>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <div>
                                        <h3 class="font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                            <?php echo htmlspecialchars($gateway['display_name']); ?>
                                        </h3>
                                        <p class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                                            <?php echo $gateway['name'] === 'paypal' ? 'Accept PayPal and credit card payments' : 'Accept credit card payments securely'; ?>
                                        </p>
                                    </div>
                                </div>
                                
                                <div class="flex items-center">
                                    <label class="inline-flex items-center cursor-pointer mr-4">
                                        <input type="checkbox" name="is_active" class="sr-only peer" <?php echo $gateway['is_active'] ? 'checked' : ''; ?>>
                                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none rounded-full peer <?php echo $darkMode ? 'bg-gray-700' : ''; ?> peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                        <span class="ml-2 text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                            <?php echo $lang['enabled'] ?? 'Enabled'; ?>
                                        </span>
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Gateway Settings -->
                            <div class="space-y-4">
                                <!-- Display Name -->
                                <div>
                                    <label for="<?php echo $gateway['name']; ?>-display-name" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        <?php echo $lang['display_name'] ?? 'Display Name'; ?>
                                    </label>
                                    <input type="text" 
                                           id="<?php echo $gateway['name']; ?>-display-name" 
                                           name="display_name" 
                                           value="<?php echo htmlspecialchars($gateway['display_name']); ?>" 
                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                           required>
                                </div>
                                
                                <!-- Description -->
                                <div>
                                    <label for="<?php echo $gateway['name']; ?>-description" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        <?php echo $lang['description'] ?? 'Description'; ?>
                                    </label>
                                    <textarea id="<?php echo $gateway['name']; ?>-description"
                                              name="description"
                                              rows="2"
                                              class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"><?php echo htmlspecialchars($gateway['description'] ?? ''); ?></textarea>
                                </div>
                                                                <!-- Test Mode Toggle -->
                                <div class="mt-4">
                                    <div class="flex items-center">
                                        <input type="checkbox" 
                                               id="<?php echo $gateway['name']; ?>-test-mode" 
                                               name="test_mode" 
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : ''; ?>"
                                               <?php echo $gateway['test_mode'] ? 'checked' : ''; ?>>
                                        <label for="<?php echo $gateway['name']; ?>-test-mode" class="ml-2 block text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                            <?php echo $lang['test_mode'] ?? 'Enable Test/Sandbox Mode'; ?>
                                        </label>
                                    </div>
                                    <p class="mt-1 text-xs <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                        <?php echo $lang['test_mode_desc'] ?? 'Use test credentials for development and testing. No real transactions will be made.'; ?>
                                    </p>
                                </div>
                                
                                <div class="border-t <?php echo $darkMode ? 'border-gray-700' : 'border-gray-200'; ?> pt-4 mt-4">
                                    <!-- API Credentials -->
                                    <?php if ($gateway['name'] === 'paypal'): ?>
                                        <?php 
                                            $config = json_decode($gateway['config'], true);
                                            $test_client_id = $config['test']['client_id'] ?? '';
                                            $test_client_secret = $config['test']['client_secret'] ?? '';
                                            $test_webhook_id = $config['test']['webhook_id'] ?? '';
                                            $live_client_id = $config['live']['client_id'] ?? '';
                                            $live_client_secret = $config['live']['client_secret'] ?? '';
                                            $live_webhook_id = $config['live']['webhook_id'] ?? '';
                                        ?>
                                        
                                        <!-- Test Mode Credentials -->
                                        <div class="mb-6">
                                            <h4 class="text-md font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?> mb-3">
                                                <?php echo $lang['test_credentials'] ?? 'Test/Sandbox Credentials'; ?>
                                            </h4>
                                            
                                            <div class="space-y-3">
                                                <div>
                                                    <label for="paypal_test_client_id" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                                        <?php echo $lang['client_id'] ?? 'Client ID'; ?>
                                                    </label>
                                                    <input type="text" 
                                                           id="paypal_test_client_id" 
                                                           name="paypal_test_client_id" 
                                                           value="<?php echo htmlspecialchars($test_client_id); ?>" 
                                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                                </div>
                                                
                                                <div>
                                                    <label for="paypal_test_client_secret" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                                        <?php echo $lang['client_secret'] ?? 'Client Secret'; ?>
                                                    </label>
                                                    <div class="relative mt-1">
                                                        <input type="password"
                                                               id="paypal_test_client_secret"
                                                               name="paypal_test_client_secret"
                                                               value="<?php echo htmlspecialchars($test_client_secret); ?>"
                                                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                                        <button type="button" 
                                                                class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center"
                                                                data-target="paypal_test_client_secret">
                                                            <i class="fas fa-eye text-gray-400"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                
                                                <div>
                                                    <label for="paypal_test_webhook_id" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                                        <?php echo $lang['webhook_id'] ?? 'Webhook ID'; ?>
                                                    </label>
                                                    <input type="text"
                                                           id="paypal_test_webhook_id"
                                                           name="paypal_test_webhook_id"
                                                           value="<?php echo htmlspecialchars($test_webhook_id); ?>"
                                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Live Mode Credentials -->
                                        <div>
                                            <h4 class="text-md font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?> mb-3">
                                                <?php echo $lang['live_credentials'] ?? 'Live Credentials'; ?>
                                            </h4>
                                            
                                            <div class="space-y-3">
                                                <div>
                                                    <label for="paypal_live_client_id" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                                        <?php echo $lang['client_id'] ?? 'Client ID'; ?>
                                                    </label>
                                                    <input type="text"
                                                           id="paypal_live_client_id"
                                                           name="paypal_live_client_id"
                                                           value="<?php echo htmlspecialchars($live_client_id); ?>"
                                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                                </div>
                                                
                                                <div>
                                                    <label for="paypal_live_client_secret" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                                        <?php echo $lang['client_secret'] ?? 'Client Secret'; ?>
                                                    </label>
                                                    <div class="relative mt-1">
                                                        <input type="password"
                                                               id="paypal_live_client_secret"
                                                               name="paypal_live_client_secret"
                                                               value="<?php echo htmlspecialchars($live_client_secret); ?>"
                                                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                                        <button type="button"
                                                                class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center"
                                                                data-target="paypal_live_client_secret">
                                                            <i class="fas fa-eye text-gray-400"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                                                                <div>
                                                    <label for="paypal_live_webhook_id" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                                        <?php echo $lang['webhook_id'] ?? 'Webhook ID'; ?>
                                                    </label>
                                                    <input type="text"
                                                           id="paypal_live_webhook_id"
                                                           name="paypal_live_webhook_id"
                                                           value="<?php echo htmlspecialchars($live_webhook_id); ?>"
                                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                                </div>
                                            </div>
                                        </div>
                                    <?php elseif ($gateway['name'] === 'stripe'): ?>
                                        <?php 
                                            $config = json_decode($gateway['config'], true);
                                            $test_publishable_key = $config['test']['publishable_key'] ?? '';
                                            $test_secret_key = $config['test']['secret_key'] ?? '';
                                            $test_webhook_secret = $config['test']['webhook_secret'] ?? '';
                                            $live_publishable_key = $config['live']['publishable_key'] ?? '';
                                            $live_secret_key = $config['live']['secret_key'] ?? '';
                                            $live_webhook_secret = $config['live']['webhook_secret'] ?? '';
                                        ?>
                                        
                                        <!-- Test Mode Credentials -->
                                        <div class="mb-6">
                                            <h4 class="text-md font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?> mb-3">
                                                <?php echo $lang['test_credentials'] ?? 'Test Credentials'; ?>
                                            </h4>
                                            
                                            <div class="space-y-3">
                                                <div>
                                                    <label for="stripe_test_publishable_key" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                                        <?php echo $lang['publishable_key'] ?? 'Publishable Key'; ?>
                                                    </label>
                                                    <input type="text" 
                                                           id="stripe_test_publishable_key" 
                                                           name="stripe_test_publishable_key" 
                                                           value="<?php echo htmlspecialchars($test_publishable_key); ?>" 
                                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                                </div>
                                                
                                                <div>
                                                    <label for="stripe_test_secret_key" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                                        <?php echo $lang['secret_key'] ?? 'Secret Key'; ?>
                                                    </label>
                                                    <div class="relative mt-1">
                                                        <input type="password"
                                                               id="stripe_test_secret_key"
                                                               name="stripe_test_secret_key"
                                                               value="<?php echo htmlspecialchars($test_secret_key); ?>"
                                                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                                        <button type="button" 
                                                                class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center"
                                                                data-target="stripe_test_secret_key">
                                                            <i class="fas fa-eye text-gray-400"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                
                                                <div>
                                                    <label for="stripe_test_webhook_secret" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                                        <?php echo $lang['webhook_secret'] ?? 'Webhook Secret'; ?>
                                                    </label>
                                                    <div class="relative mt-1">
                                                        <input type="password"
                                                               id="stripe_test_webhook_secret"
                                                               name="stripe_test_webhook_secret"
                                                               value="<?php echo htmlspecialchars($test_webhook_secret); ?>"
                                                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                                        <button type="button"
                                                                class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center"
                                                                data-target="stripe_test_webhook_secret">
                                                            <i class="fas fa-eye text-gray-400"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <!-- Live Mode Credentials -->
                                        <div>
                                            <h4 class="text-md font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?> mb-3">
                                                <?php echo $lang['live_credentials'] ?? 'Live Credentials'; ?>
                                            </h4>
                                            
                                            <div class="space-y-3">
                                                <div>
                                                    <label for="stripe_live_publishable_key" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                                        <?php echo $lang['publishable_key'] ?? 'Publishable Key'; ?>
                                                    </label>
                                                    <input type="text"
                                                           id="stripe_live_publishable_key"
                                                           name="stripe_live_publishable_key"
                                                           value="<?php echo htmlspecialchars($live_publishable_key); ?>"
                                                           class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                                </div>
                                                
                                                <div>
                                                    <label for="stripe_live_secret_key" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                                        <?php echo $lang['secret_key'] ?? 'Secret Key'; ?>
                                                    </label>
                                                    <div class="relative mt-1">
                                                        <input type="password"
                                                               id="stripe_live_secret_key"
                                                               name="stripe_live_secret_key"
                                                               value="<?php echo htmlspecialchars($live_secret_key); ?>"
                                                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                                        <button type="button"
                                                                class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center"
                                                                data-target="stripe_live_secret_key">
                                                            <i class="fas fa-eye text-gray-400"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                                                                                <div>
                                                    <label for="stripe_live_webhook_secret" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                                        <?php echo $lang['webhook_secret'] ?? 'Webhook Secret'; ?>
                                                    </label>
                                                    <div class="relative mt-1">
                                                        <input type="password"
                                                               id="stripe_live_webhook_secret"
                                                               name="stripe_live_webhook_secret"
                                                               value="<?php echo htmlspecialchars($live_webhook_secret); ?>"
                                                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                                        <button type="button"
                                                                class="toggle-password absolute inset-y-0 right-0 pr-3 flex items-center"
                                                                data-target="stripe_live_webhook_secret">
                                                            <i class="fas fa-eye text-gray-400"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="mt-6 pt-4 border-t <?php echo $darkMode ? 'border-gray-700' : 'border-gray-200'; ?>">
                                    <div class="flex justify-end">
                                        <button type="submit" 
                                                name="save_gateway" 
                                                class="inline-flex justify-center px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-transparent rounded-md shadow-sm hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 <?php echo $darkMode ? 'focus:ring-offset-gray-900' : ''; ?>">
                                            <?php echo $lang['save_changes'] ?? 'Save Changes'; ?>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            
            <!-- Payment Transactions Section -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden <?php echo $darkMode ? 'bg-gray-800' : ''; ?> mb-6">
                <div class="px-6 py-4 border-b <?php echo $darkMode ? 'border-gray-700' : 'border-gray-200'; ?>">
                    <h2 class="text-lg font-semibold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                        <?php echo $lang['payment_transactions'] ?? 'Payment Transactions'; ?>
                    </h2>
                    <p class="mt-1 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                        <?php echo $lang['transaction_history'] ?? 'View and manage payment transaction history'; ?>
                    </p>
                </div>
                
                <div class="px-6 py-4">
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-700' : ''; ?>">
                            <thead>
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                        <?php echo $lang['id'] ?? 'ID'; ?>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                        <?php echo $lang['user'] ?? 'User'; ?>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                        <?php echo $lang['amount'] ?? 'Amount'; ?>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                        <?php echo $lang['payment_method'] ?? 'Payment Method'; ?>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                        <?php echo $lang['status'] ?? 'Status'; ?>
                                    </th>
                                    <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                        <?php echo $lang['date'] ?? 'Date'; ?>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-700' : ''; ?>">
                                <?php
                                try {
                                    $stmt = $pdo->query("SELECT p.id, p.amount, p.payment_method, p.status, p.created_at, p.transaction_id, u.username, u.email 
                                                        FROM payments p 
                                                        LEFT JOIN users u ON p.user_id = u.id 
                                                        ORDER BY p.created_at DESC 
                                                        LIMIT 10");
                                    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                    if (count($transactions) > 0) {
                                        foreach ($transactions as $transaction) {
                                            echo '<tr>';
                                                                                        echo '<td class="px-6 py-4 whitespace-nowrap text-sm ' . ($darkMode ? 'text-gray-300' : 'text-gray-800') . '">' . $transaction['id'] . '</td>';
                                                           echo '<td class="px-6 py-4 whitespace-nowrap text-sm ' . ($darkMode ? 'text-gray-300' : 'text-gray-800') . '">' . $transaction['id'] . '</td>';
                                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm ' . ($darkMode ? 'text-gray-300' : 'text-gray-800') . '">' . htmlspecialchars($transaction['username']) . '</td>';
                                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm ' . ($darkMode ? 'text-gray-300' : 'text-gray-800') . '">$' . number_format($transaction['amount'], 2) . '</td>';
                                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm ' . ($darkMode ? 'text-gray-300' : 'text-gray-800') . '">' . ucfirst(htmlspecialchars($transaction['payment_method'])) . '</td>';
                                            
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
                                                default:
                                                    $statusClass = $darkMode ? 'bg-gray-700 text-gray-300' : 'bg-gray-100 text-gray-800';
                                            }
                                            
                                            echo '<td class="px-6 py-4 whitespace-nowrap">';
                                            echo '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full ' . $statusClass . '">';
                                            echo ucfirst(htmlspecialchars($transaction['status']));
                                            echo '</span>';
                                            echo '</td>';
                                            
                                            echo '<td class="px-6 py-4 whitespace-nowrap text-sm ' . ($darkMode ? 'text-gray-300' : 'text-gray-800') . '">' . date('M j, Y H:i', strtotime($transaction['created_at'])) . '</td>';
                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="6" class="px-6 py-4 text-center text-sm ' . ($darkMode ? 'text-gray-400' : 'text-gray-500') . '">' . ($lang['no_transactions'] ?? 'No transactions found') . '</td></tr>';
                                    }
                                } catch (PDOException $e) {
                                    echo '<tr><td colspan="6" class="px-6 py-4 text-center text-sm ' . ($darkMode ? 'text-red-400' : 'text-red-500') . '">Error loading transactions</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-4 flex justify-center">
                        <a href="<?php echo $adminUrl; ?>/payments/transactions.php" class="text-sm text-blue-500 hover:text-blue-600">
                            <?php echo $lang['view_all_transactions'] ?? 'View All Transactions'; ?> <i class="fas fa-arrow-right ml-1"></i>
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Documentation Section -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden <?php echo $darkMode ? 'bg-gray-800' : ''; ?>">
                <div class="px-6 py-4 border-b <?php echo $darkMode ? 'border-gray-700' : 'border-gray-200'; ?>">
                    <h2 class="text-lg font-semibold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                        <?php echo $lang['payment_documentation'] ?? 'Documentation & Resources'; ?>
                    </h2>
                </div>
                
                <div class="px-6 py-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="p-4 border rounded-lg <?php echo $darkMode ? 'border-gray-700 bg-gray-700' : 'border-gray-200 bg-gray-50'; ?>">
                            <h3 class="font-medium text-lg mb-2 <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                <i class="fab fa-paypal text-blue-500 mr-2"></i> PayPal
                            </h3>
                            <ul class="space-y-2 text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-600'; ?>">
                                <li><a href="https://developer.paypal.com/docs/business/" class="text-blue-500 hover:underline" target="_blank" rel="noopener">
                                    <?php echo $lang['paypal_docs'] ?? 'PayPal Developer Documentation'; ?> <i class="fas fa-external-link-alt text-xs"></i>
                                </a></li>
                                <li><a href="https://developer.paypal.com/dashboard/applications/sandbox" class="text-blue-500 hover:underline" target="_blank" rel="noopener">
                                    <?php echo $lang['paypal_sandbox'] ?? 'PayPal Sandbox Dashboard'; ?> <i class="fas fa-external-link-alt text-xs"></i>
                                </a></li>
                                <li><a href="https://developer.paypal.com/docs/api/reference/webhook-events/" class="text-blue-500 hover:underline" target="_blank" rel="noopener">
                                    <?php echo $lang['paypal_webhooks'] ?? 'PayPal Webhook Guide'; ?> <i class="fas fa-external-link-alt text-xs"></i>
                                </a></li>
                            </ul>
                        </div>
                        
                        <div class="p-4 border rounded-lg <?php echo $darkMode ? 'border-gray-700 bg-gray-700' : 'border-gray-200 bg-gray-50'; ?>">
                            <h3 class="font-medium text-lg mb-2 <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                <i class="fab fa-stripe-s text-purple-500 mr-2"></i> Stripe
                            </h3>
                            <ul class="space-y-2 text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-600'; ?>">
                                <li><a href="https://stripe.com/docs" class="text-blue-500 hover:underline" target="_blank" rel="noopener">
                                    <?php echo $lang['stripe_docs'] ?? 'Stripe Documentation'; ?> <i class="fas fa-external-link-alt text-xs"></i>
                                </a></li>
                                <li><a href="https://dashboard.stripe.com/test/developers" class="text-blue-500 hover:underline" target="_blank" rel="noopener">
                                    <?php echo $lang['stripe_dashboard'] ?? 'Stripe Dashboard'; ?> <i class="fas fa-external-link-alt text-xs"></i>
                                </a></li>
                                <li><a href="https://stripe.com/docs/webhooks" class="text-blue-500 hover:underline" target="_blank" rel="noopener">
                                    <?php echo $lang['stripe_webhooks'] ?? 'Stripe Webhook Guide'; ?> <i class="fas fa-external-link-alt text-xs"></i>
                                </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for tab functionality -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab functionality
    const tabButtons = document.querySelectorAll('[role="tab"]');
    const tabPanels = document.querySelectorAll('[role="tabpanel"]');
    
    tabButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Deactivate all tabs
            tabButtons.forEach(btn => {
                btn.setAttribute('aria-selected', 'false');
                btn.classList.remove(<?php echo $darkMode ? "'text-blue-400', 'border-blue-400'" : "'text-blue-600', 'border-blue-600'"; ?>);
                btn.classList.add(<?php echo $darkMode ? "'text-gray-400'" : "'text-gray-600'"; ?>);
                btn.classList.remove('border-b-2');
            });
            
            // Hide all panels
            tabPanels.forEach(panel => {
                panel.classList.add('hidden');
            });
            
            // Activate current tab
            button.setAttribute('aria-selected', 'true');
            button.classList.remove(<?php echo $darkMode ? "'text-gray-400'" : "'text-gray-600'"; ?>);
            button.classList.add(<?php echo $darkMode ? "'text-blue-400', 'border-blue-400'" : "'text-blue-600', 'border-blue-600'"; ?>);
            button.classList.add('border-b-2');
            
            // Show current panel
            const panelId = button.getAttribute('aria-controls');
            document.getElementById(panelId).classList.remove('hidden');
        });
    });
    
    // Toggle password visibility
    const toggleButtons = document.querySelectorAll('.toggle-password');
    toggleButtons.forEach(button => {
        button.addEventListener('click', function() {
            const targetId = this.getAttribute('data-target');
            const targetInput = document.getElementById(targetId);
            const icon = this.querySelector('i');
            
            if (targetInput.type === 'password') {
                targetInput.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                targetInput.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        });
    });
});
</script>

<?php
// Include footer
include '../../theme/admin/footer.php';
?>
                