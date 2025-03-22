<?php
/**
 * Admin Settings Page
 * 
 * @package WallPix
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'Site Settings';

// Include header
require_once '../../theme/admin/header.php';

// Include sidebar
require_once '../../theme/admin/slidbar.php';

// Get settings from database
$settingsQuery = $pdo->prepare("SELECT * FROM site_settings WHERE id = 1");
$settingsQuery->execute();
$settings = $settingsQuery->fetch(PDO::FETCH_ASSOC);

// Process form submission
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Begin transaction
        $pdo->beginTransaction();
        
        // Handle file uploads for logo and favicon
        $logoPath = $settings['site_logo'];
        $faviconPath = $settings['site_favicon'];
        
        // Handle logo upload
        if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === 0) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/svg+xml'];
            if (in_array($_FILES['site_logo']['type'], $allowedTypes)) {
                $uploadDir = '../../uploads/settings/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $logoFilename = 'logo_' . time() . '_' . basename($_FILES['site_logo']['name']);
                $logoPath = 'uploads/settings/' . $logoFilename;
                
                if (move_uploaded_file($_FILES['site_logo']['tmp_name'], $uploadDir . $logoFilename)) {
                    // Delete old logo if it exists and is not the default
                    if ($settings['site_logo'] && $settings['site_logo'] !== 'assets/images/default-logo.png' && file_exists('../../' . $settings['site_logo'])) {
                        unlink('../../' . $settings['site_logo']);
                    }
                }
            }
        }
        
        // Handle favicon upload
        if (isset($_FILES['site_favicon']) && $_FILES['site_favicon']['error'] === 0) {
            $allowedTypes = ['image/x-icon', 'image/png', 'image/svg+xml'];
            if (in_array($_FILES['site_favicon']['type'], $allowedTypes)) {
                $uploadDir = '../../uploads/settings/';
                if (!file_exists($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $faviconFilename = 'favicon_' . time() . '_' . basename($_FILES['site_favicon']['name']);
                $faviconPath = 'uploads/settings/' . $faviconFilename;
                
                if (move_uploaded_file($_FILES['site_favicon']['tmp_name'], $uploadDir . $faviconFilename)) {
                    // Delete old favicon if it exists and is not the default
                    if ($settings['site_favicon'] && $settings['site_favicon'] !== 'assets/images/default-favicon.ico' && file_exists('../../' . $settings['site_favicon'])) {
                        unlink('../../' . $settings['site_favicon']);
                    }
                }
            }
        }
        
        // Prepare update fields based on site_settings table columns
        $updateFields = [];
        $updateParams = [];
        
        // Get all columns from site_settings table
        $columnsQuery = $pdo->prepare("DESCRIBE site_settings");
        $columnsQuery->execute();
        $columns = $columnsQuery->fetchAll(PDO::FETCH_COLUMN);
        
        // Build update query dynamically based on table columns and posted data
        foreach ($columns as $column) {
            // Skip id, created_at columns, and any other columns we don't want to update
            if (in_array($column, ['id', 'created_at'])) {
                continue;
            }
            
            // Handle special cases
            if ($column === 'site_logo') {
                $updateFields[] = "$column = :$column";
                $updateParams[$column] = $logoPath;
            } 
            elseif ($column === 'site_favicon') {
                $updateFields[] = "$column = :$column";
                $updateParams[$column] = $faviconPath;
            } 
            elseif ($column === 'updated_at') {
                $updateFields[] = "$column = NOW()";
            } 
            elseif ($column === 'updated_by') {
                $updateFields[] = "$column = :updated_by";
                $updateParams['updated_by'] = $_SESSION['user_id'] ?? 'system';
            }
            // Handle Boolean fields
            elseif (isset($_POST[$column]) && is_string($_POST[$column]) && in_array($_POST[$column], ['0', '1'])) {
                $updateFields[] = "$column = :$column";
                $updateParams[$column] = $_POST[$column];
            }
            // Handle Boolean fields that come from checkboxes
            elseif (in_array($column, [
                'maintenance_mode', 'dark_mode', 'enable_header', 'enable_footer',
                'enable_navbar', 'enable_search_box', 'enable_categories',
                'news_ticker_enabled'
            ])) {
                $updateFields[] = "$column = :$column";
                $updateParams[$column] = isset($_POST[$column]) ? 1 : 0;
            }
            // Handle fields that can be NULL (like header_menu_id and footer_menu_id)
            elseif (in_array($column, ['header_menu_id', 'footer_menu_id'])) {
                $updateFields[] = "$column = :$column";
                // If value is empty, set to NULL
                $updateParams[$column] = !empty($_POST[$column]) ? $_POST[$column] : null;
            }
            // Handle all other fields that were posted
            elseif (isset($_POST[$column])) {
                $updateFields[] = "$column = :$column";
                $updateParams[$column] = $_POST[$column];
            }
        }
        
        // Build and execute final update query
        $updateQuery = $pdo->prepare(
            "UPDATE site_settings SET " . 
            implode(", ", $updateFields) . 
            " WHERE id = 1"
        );
        
        $updateQuery->execute($updateParams);
        
        // Log activity
        $activityQuery = $pdo->prepare(
            "INSERT INTO activities (user_id, description, created_at)
            VALUES (:user_id, :description, NOW())"
        );
        
        $activityQuery->execute([
            'user_id' => $_SESSION['user_id'] ?? null,
            'description' => 'Site settings updated'
        ]);
        
        // Commit transaction
        $pdo->commit();
        
        // Set success message
        $successMessage = 'Settings updated successfully!';
        
        // Refresh settings data
        $settingsQuery->execute();
        $settings = $settingsQuery->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $errorMessage = 'Error updating settings: ' . $e->getMessage();
    }
}
?>

<!-- Main content container -->
<div class="content-wrapper p-4 sm:ml-64">
    <div class="p-4 mt-14">
        <div class="bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-800' : ''; ?>">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-semibold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                    <i class="fas fa-cog mr-2"></i> Site Settings
                </h1>
                <nav class="flex" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="<?php echo $adminUrl; ?>/index.php" class="inline-flex items-center text-sm font-medium <?php echo $darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-700 hover:text-blue-600'; ?>">
                                <i class="fas fa-home mr-2"></i> Dashboard
                            </a>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                                <span class="text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">
                                    Site Settings
                                </span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>
            
            <?php if ($successMessage): ?>
                <div class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg dark:bg-green-200 dark:text-green-800" role="alert">
                    <span class="font-medium"><i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($successMessage); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($errorMessage): ?>
                <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg dark:bg-red-200 dark:text-red-800" role="alert">
                    <span class="font-medium"><i class="fas fa-exclamation-circle mr-2"></i> <?php echo htmlspecialchars($errorMessage); ?></span>
                </div>
            <?php endif; ?>

            <!-- Settings Form -->
            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" enctype="multipart/form-data">
                <!-- Tabs Navigation -->
                <div class="mb-4 border-b border-gray-200 <?php echo $darkMode ? 'border-gray-700' : ''; ?>">
                    <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="settingsTabs" role="tablist">
                        <li class="mr-2" role="presentation">
                            <button type="button" class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300" id="general-tab" data-tabs-target="#general" role="tab" aria-controls="general" aria-selected="false">
                                <i class="fas fa-sliders-h mr-2"></i> General Settings
                            </button>
                        </li>
                        <li class="mr-2" role="presentation">
                            <button type="button" class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300" id="appearance-tab" data-tabs-target="#appearance" role="tab" aria-controls="appearance" aria-selected="false">
                                <i class="fas fa-palette mr-2"></i> Appearance
                            </button>
                        </li>
                        <li class="mr-2" role="presentation">
                            <button type="button" class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300" id="social-tab" data-tabs-target="#social" role="tab" aria-controls="social" aria-selected="false">
                                <i class="fas fa-share-alt mr-2"></i> Social Media
                            </button>
                        </li>
                        <li class="mr-2" role="presentation">
                            <button type="button" class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300" id="contact-tab" data-tabs-target="#contact" role="tab" aria-controls="contact" aria-selected="false">
                                <i class="fas fa-address-card mr-2"></i> Contact Info
                            </button>
                        </li>
                        <li role="presentation">
                            <button type="button" class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300" id="advanced-tab" data-tabs-target="#advanced" role="tab" aria-controls="advanced" aria-selected="false">
                                <i class="fas fa-tools mr-2"></i> Advanced Settings
                            </button>
                        </li>
                    </ul>
                </div>
                
                <!-- Tab Content -->
                <div id="settingsTabContent">
                    <!-- General Settings Tab -->
                    <div class="hidden p-4 rounded-lg bg-white <?php echo $darkMode ? 'bg-gray-800' : ''; ?>" id="general" role="tabpanel" aria-labelledby="general-tab">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="mb-4">
                                <label for="site_name" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    Site Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="site_name" name="site_name" 
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>" 
                                    value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-4">
                                <label for="site_title" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    Site Title <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="site_title" name="site_title" 
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>" 
                                    value="<?php echo htmlspecialchars($settings['site_title'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-4 col-span-1 md:col-span-2">
                                <label for="site_description" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    Site Description
                                </label>
                                <textarea id="site_description" name="site_description" rows="3" 
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"><?php echo htmlspecialchars($settings['site_description'] ?? ''); ?></textarea>
                            </div>
                            <div class="mb-4 col-span-1 md:col-span-2">
                                <label for="site_keywords" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    Site Keywords
                                </label>
                                <input type="text" id="site_keywords" name="site_keywords" 
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>" 
                                    value="<?php echo htmlspecialchars($settings['site_keywords'] ?? ''); ?>" 
                                    placeholder="Keywords separated by commas">
                            </div>
                            <div class="mb-4">
                                <label for="site_url" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    Site URL <span class="text-red-500">*</span>
                                </label>
                                <input type="url" id="site_url" name="site_url" 
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>" 
                                    value="<?php echo htmlspecialchars($settings['site_url'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-4">
                                <label for="site_email" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    Site Email <span class="text-red-500">*</span>
                                </label>
                                <input type="email" id="site_email" name="site_email" 
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>" 
                                    value="<?php echo htmlspecialchars($settings['site_email'] ?? ''); ?>" required>
                            </div>
                            <div class="mb-4">
                                <label for="footer_text" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    Footer Text
                                </label>
                                <input type="text" id="footer_text" name="footer_text" 
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>" 
                                    value="<?php echo htmlspecialchars($settings['footer_text'] ?? '© ' . date('Y') . ' All Rights Reserved'); ?>">
                            </div>
                            <div class="mb-4">
                                <label for="language" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    Language
                                </label>
                                <select id="language" name="language" 
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                    <option value="ar" <?php echo ($settings['language'] ?? 'ar') == 'ar' ? 'selected' : ''; ?>>Arabic</option>
                                    <option value="en" <?php echo ($settings['language'] ?? 'ar') == 'en' ? 'selected' : ''; ?>>English</option>
                                </select>
                            </div>
                            <div class="mb-4">
                                <div class="flex items-center">
                                    <input id="maintenance_mode" name="maintenance_mode" type="checkbox" 
                                        class="w-4 h-4 text-blue-600 bg-gray-100 rounded border-gray-300 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : ''; ?>" 
                                        <?php echo ($settings['maintenance_mode'] ?? 0) ? 'checked' : ''; ?>>
                                    <label for="maintenance_mode" class="ml-2 text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        Maintenance Mode
                                    </label>
                                </div>
                                <p class="mt-1 text-xs <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    When enabled, only administrators can access the site
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Appearance Tab -->
                    <div class="hidden p-4 rounded-lg bg-white <?php echo $darkMode ? 'bg-gray-800' : ''; ?>" id="appearance" role="tabpanel" aria-labelledby="appearance-tab">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="mb-4">
                                <label class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>" for="site_logo">
                                    Site Logo
                                </label>
                                <input class="block w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 cursor-pointer <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>" 
                                    id="site_logo" name="site_logo" type="file" accept="image/png, image/jpeg, image/svg+xml">
                                <p class="mt-1 text-xs <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    Recommended size: 200x50px (PNG, JPG, or SVG)
                                </p>
                                <?php if (!empty($settings['site_logo'])): ?>
                                <div class="mt-2">
                                    <p class="text-sm mb-1 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        Current Logo:
                                    </p>
                                    <img src="<?php echo '../../uploads/' . htmlspecialchars($settings['site_logo']); ?>" 
                                        alt="Current logo" class="h-10 border <?php echo $darkMode ? 'border-gray-600' : 'border-gray-200'; ?> rounded p-1">
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-4">
                                <label class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>" for="site_favicon">
                                    Site Favicon
                                </label>
                                <input class="block w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 cursor-pointer <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>" 
                                    id="site_favicon" name="site_favicon" type="file" accept="image/x-icon, image/png, image/svg+xml">
                                <p class="mt-1 text-xs <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    Size: 32x32px or 16x16px (ICO, PNG, or SVG)
                                </p>
                                <?php if (!empty($settings['site_favicon'])): ?>
                                <div class="mt-2">
                                    <p class="text-sm mb-1 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        Current Favicon:
                                    </p>
                                    <img src="<?php echo '../../' . htmlspecialchars($settings['site_favicon']); ?>" 
                                        alt="Current favicon" class="h-8 border <?php echo $darkMode ? 'border-gray-600' : 'border-gray-200'; ?> rounded p-1">
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-4">
                                <div class="flex items-center">
                                    <input id="dark_mode" name="dark_mode" type="checkbox" 
                                        class="w-4 h-4 text-blue-600 bg-gray-100 rounded border-gray-300 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : ''; ?>" 
                                        <?php echo ($settings['dark_mode'] ?? 0) ? 'checked' : ''; ?>>
                                    <label for="dark_mode" class="ml-2 text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        Dark Mode
                                    </label>
                                </div>
                                <p class="mt-1 text-xs <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    Enable dark mode by default
                                </p>
                            </div>
                            <div class="mb-4">
                                <div class="flex items-center">
                                    <input id="enable_header" name="enable_header" type="checkbox" 
                                        class="w-4 h-4 text-blue-600 bg-gray-100 rounded border-gray-300 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : ''; ?>" 
                                        <?php echo ($settings['enable_header'] ?? 1) ? 'checked' : ''; ?>>
                                    <label for="enable_header" class="ml-2 text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        Enable Header
                                    </label>
                                </div>
                            </div>
                            <div class="mb-4">
                                <div class="flex items-center">
                                    <input id="enable_footer" name="enable_footer" type="checkbox" 
                                        class="w-4 h-4 text-blue-600 bg-gray-100 rounded border-gray-300 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : ''; ?>" 
                                        <?php echo ($settings['enable_footer'] ?? 1) ? 'checked' : ''; ?>>
                                    <label for="enable_footer" class="ml-2 text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        Enable Footer
                                    </label>
                                </div>
                            </div>
                            <div class="mb-4">
                                <div class="flex items-center">
                                    <input id="enable_navbar" name="enable_navbar" type="checkbox" 
                                        class="w-4 h-4 text-blue-600 bg-gray-100 rounded border-gray-300 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : ''; ?>" 
                                        <?php echo ($settings['enable_navbar'] ?? 1) ? 'checked' : ''; ?>>
                                    <label for="enable_navbar" class="ml-2 text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        Enable Navigation Bar
                                    </label>
                                </div>
                            </div>
                            <div class="mb-4">
                                <div class="flex items-center">
                                    <input id="enable_search_box" name="enable_search_box" type="checkbox" 
                                        class="w-4 h-4 text-blue-600 bg-gray-100 rounded border-gray-300 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : ''; ?>" 
                                        <?php echo ($settings['enable_search_box'] ?? 1) ? 'checked' : ''; ?>>
                                    <label for="enable_search_box" class="ml-2 text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        Enable Search Box
                                    </label>
                                </div>
                            </div>
                            <div class="mb-4">
                                <div class="flex items-center">
                                    <input id="enable_categories" name="enable_categories" type="checkbox" 
                                        class="w-4 h-4 text-blue-600 bg-gray-100 rounded border-gray-300 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : ''; ?>" 
                                        <?php echo ($settings['enable_categories'] ?? 1) ? 'checked' : ''; ?>>
                                    <label for="enable_categories" class="ml-2 text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        Enable Categories
                                    </label>
                                </div>
                            </div>
                            <div class="mb-4">
                                <div class="flex items-center">
                                    <input id="news_ticker_enabled" name="news_ticker_enabled" type="checkbox" 
                                        class="w-4 h-4 text-blue-600 bg-gray-100 rounded border-gray-300 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : ''; ?>" 
                                        <?php echo ($settings['news_ticker_enabled'] ?? 0) ? 'checked' : ''; ?>>
                                    <label for="news_ticker_enabled" class="ml-2 text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        Enable News Ticker
                                    </label>
                                </div>
                            </div>
                        </div>
                        <div class="mb-4 col-span-1 md:col-span-2">
                            <label for="footer_content" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                Footer Content
                            </label>
                            <textarea id="footer_content" name="footer_content" rows="4" 
                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"><?php echo htmlspecialchars($settings['footer_content'] ?? ''); ?></textarea>
                        </div>
                    </div>

                    <!-- Social Media Tab -->
                    <div class="hidden p-4 rounded-lg bg-white <?php echo $darkMode ? 'bg-gray-800' : ''; ?>" id="social" role="tabpanel" aria-labelledby="social-tab">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="mb-4">
                                <label for="facebook_url" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    <i class="fab fa-facebook text-blue-600 mr-2"></i> Facebook
                                </label>
                                <input type="url" id="facebook_url" name="facebook_url" 
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>" 
                                    value="<?php echo htmlspecialchars($settings['facebook_url'] ?? ''); ?>" 
                                    placeholder="https://facebook.com/yourpage">
                            </div>
                            <div class="mb-4">
                                <label for="twitter_url" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    <i class="fab fa-twitter text-blue-400 mr-2"></i> Twitter
                                </label>
                                <input type="url" id="twitter_url" name="twitter_url" 
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>" 
                                    value="<?php echo htmlspecialchars($settings['twitter_url'] ?? ''); ?>" 
                                    placeholder="https://twitter.com/yourpage">
                            </div>
                            <div class="mb-4">
                                <label for="instagram_url" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    <i class="fab fa-instagram text-pink-600 mr-2"></i> Instagram
                                </label>
                                <input type="url" id="instagram_url" name="instagram_url" 
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>" 
                                    value="<?php echo htmlspecialchars($settings['instagram_url'] ?? ''); ?>" 
                                    placeholder="https://instagram.com/yourpage">
                            </div>
                            <div class="mb-4">
                                <label for="youtube_url" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    <i class="fab fa-youtube text-red-600 mr-2"></i> YouTube
                                </label>
                                <input type="url" id="youtube_url" name="youtube_url" 
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>" 
                                    value="<?php echo htmlspecialchars($settings['youtube_url'] ?? ''); ?>" 
                                    placeholder="https://youtube.com/c/yourchannel">
                            </div>
                            <div class="mb-4">
                                <label for="header_menu_id" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    Header Menu
                                </label>
                                <select id="header_menu_id" name="header_menu_id" 
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                    <option value="">-- No Menu --</option>
                                    <?php 
                                    // Get all menus from the database
                                    $menuQuery = $pdo->prepare("SELECT id, name FROM menus ORDER BY name");
                                    $menuQuery->execute();
                                    $menus = $menuQuery->fetchAll(PDO::FETCH_ASSOC);
                                    
                                    foreach ($menus as $menu) {
                                        $selected = ($settings['header_menu_id'] ?? '') == $menu['id'] ? 'selected' : '';
                                        echo '<option value="' . $menu['id'] . '" ' . $selected . '>' . htmlspecialchars($menu['name']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="mb-4">
                                <label for="footer_menu_id" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    Footer Menu
                                </label>
                                <select id="footer_menu_id" name="footer_menu_id" 
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                    <option value="">-- No Menu --</option>
                                    <?php 
                                    foreach ($menus as $menu) {
                                        $selected = ($settings['footer_menu_id'] ?? '') == $menu['id'] ? 'selected' : '';
                                        echo '<option value="' . $menu['id'] . '" ' . $selected . '>' . htmlspecialchars($menu['name']) . '</option>';
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Contact Information Tab -->
                    <div class="hidden p-4 rounded-lg bg-white <?php echo $darkMode ? 'bg-gray-800' : ''; ?>" id="contact" role="tabpanel" aria-labelledby="contact-tab">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="mb-4">
                                <label for="contact_email" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    <i class="fas fa-envelope mr-2"></i> Contact Email
                                </label>
                                <input type="email" id="contact_email" name="contact_email" 
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>" 
                                    value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>" 
                                    placeholder="contact@example.com">
                            </div>

                            <div class="mb-4">
                                <label for="latest_items_count" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    Latest Items Count
                                </label>
                                <input type="number" id="latest_items_count" name="latest_items_count" min="1" max="50"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                    value="<?php echo htmlspecialchars($settings['latest_items_count'] ?? 10); ?>">
                            </div>

                            <div class="mb-4">
                                <label for="featured_wallpapers_count" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    Featured Wallpapers Count
                                </label>
                                <input type="number" id="featured_wallpapers_count" name="featured_wallpapers_count" min="1" max="50"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                    value="<?php echo htmlspecialchars($settings['featured_wallpapers_count'] ?? 10); ?>">
                            </div>
                            
                            <div class="mb-4">
                                <label for="featured_media_count" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    Featured Media Count
                                </label>
                                <input type="number" id="featured_media_count" name="featured_media_count" min="1" max="50"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                    value="<?php echo htmlspecialchars($settings['featured_media_count'] ?? 10); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Settings Tab -->
                    <div class="hidden p-4 rounded-lg bg-white <?php echo $darkMode ? 'bg-gray-800' : ''; ?>" id="advanced" role="tabpanel" aria-labelledby="advanced-tab">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="mb-4 col-span-1 md:col-span-2">
                                <label for="google_analytics" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    Google Analytics Code
                                </label>
                                <textarea id="google_analytics" name="google_analytics" rows="4" 
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm font-mono rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>" 
                                    placeholder="<!-- Google Analytics code here -->"><?php echo htmlspecialchars($settings['google_analytics'] ?? ''); ?></textarea>
                                <p class="mt-1 text-xs <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    Paste your Google Analytics tracking code here
                                </p>
                            </div>
                        </div>
                        <div class="mt-4 bg-gray-100 rounded-lg p-4 <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                            <h4 class="text-sm font-medium mb-2 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">
                                <i class="fas fa-history mr-2"></i> Last Updated
                            </h4>
                            <p class="text-xs <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                <?php 
                                if (!empty($settings['updated_at'])) {
                                    echo date('Y-m-d H:i:s', strtotime($settings['updated_at']));
                                    if (!empty($settings['updated_by'])) {
                                        // Get the username of the last updater
                                        $userQuery = $pdo->prepare("SELECT username FROM users WHERE id = :user_id");
                                        $userQuery->execute(['user_id' => $settings['updated_by']]);
                                        $username = $userQuery->fetchColumn();
                                        if ($username) {
                                            echo ' by ' . htmlspecialchars($username);
                                        }
                                    }
                                } else {
                                    echo 'Not updated yet';
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="mt-6 flex items-center justify-end">
                    <button type="submit" class="py-2 px-4 bg-blue-600 hover:bg-blue-700 focus:ring-blue-500 focus:ring-offset-blue-200 text-white w-auto transition ease-in duration-200 text-center text-base font-semibold shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 rounded-lg">
                        <i class="fas fa-save mr-2"></i> Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Show first tab by default
        document.getElementById('general').classList.remove('hidden');
        document.getElementById('general-tab').classList.add('text-blue-600', 'border-blue-600');
        document.getElementById('general-tab').classList.remove('border-transparent');
        document.getElementById('general-tab').setAttribute('aria-selected', 'true');
        
        // Tab switching functionality
        const tabs = document.querySelectorAll('[role="tab"]');
        const tabContents = document.querySelectorAll('[role="tabpanel"]');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Deactivate all tabs
                tabs.forEach(t => {
                    t.classList.remove('text-blue-600', 'border-blue-600');
                    t.classList.add('text-gray-500', 'border-transparent');
                    t.setAttribute('aria-selected', 'false');
                });
                
                // Hide all tab contents
                tabContents.forEach(content => {
                    content.classList.add('hidden');
                });
                
                // Activate clicked tab
                tab.classList.remove('text-gray-500', 'border-transparent');
                tab.classList.add('text-blue-600', 'border-blue-600');
                tab.setAttribute('aria-selected', 'true');
                
                // Show corresponding tab content
                const targetId = tab.getAttribute('data-tabs-target').substring(1); // Remove the #
                const target = document.getElementById(targetId);
                target.classList.remove('hidden');
            });
        });
        
        // Preview uploaded images
        const logoInput = document.getElementById('site_logo');
        const faviconInput = document.getElementById('site_favicon');
        
        logoInput.addEventListener('change', function(event) {
            previewImage(event, 'logo');
        });
        
        faviconInput.addEventListener('change', function(event) {
            previewImage(event, 'favicon');
        });
        
        function previewImage(event, type) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    // Create or update preview
                    let previewContainer = document.querySelector(`#${type}-preview-container`);
                    if (!previewContainer) {
                        previewContainer = document.createElement('div');
                        previewContainer.id = `${type}-preview-container`;
                        previewContainer.className = 'mt-2';
                        
                        const label = document.createElement('p');
                        label.className = 'text-sm mb-1 <?php echo $darkMode ? "text-gray-300" : "text-gray-700"; ?>';
                        label.textContent = type === 'logo' ? 'New Logo Preview' : 'New Favicon Preview';
                        
                        const img = document.createElement('img');
                        img.id = `${type}-preview`;
                        img.className = type === 'logo' ? 'h-10 border rounded p-1' : 'h-8 border rounded p-1';
                        img.className += ' <?php echo $darkMode ? "border-gray-600" : "border-gray-200"; ?>';
                        img.alt = type === 'logo' ? 'New logo preview' : 'New favicon preview';
                        
                        previewContainer.appendChild(label);
                        previewContainer.appendChild(img);
                        
                        // Add after the file input
                        event.target.parentNode.appendChild(previewContainer);
                    }
                    
                    // Update preview image
                    document.getElementById(`${type}-preview`).src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }
    });
</script>

<?php
// Include footer
require_once '../../theme/admin/footer.php';
?>