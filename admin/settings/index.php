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
        
        // Update settings in database
        $updateQuery = $pdo->prepare("
            UPDATE site_settings SET 
                site_name = :site_name,
                site_title = :site_title,
                site_description = :site_description,
                site_keywords = :site_keywords,
                site_logo = :site_logo,
                site_favicon = :site_favicon,
                maintenance_mode = :maintenance_mode,
                register_enabled = :register_enabled,
                email_verification = :email_verification,
                facebook_url = :facebook_url,
                twitter_url = :twitter_url,
                instagram_url = :instagram_url,
                youtube_url = :youtube_url,
                pinterest_url = :pinterest_url,
                contact_email = :contact_email,
                contact_phone = :contact_phone,
                contact_address = :contact_address,
                copyright_text = :copyright_text,
                google_analytics = :google_analytics,
                download_limit_free = :download_limit_free,
                display_watermark = :display_watermark,
                allow_guest_download = :allow_guest_download,
                last_updated = NOW(),
                last_updated_by = :last_updated_by
            WHERE id = 1
        ");
        
        $updateQuery->execute([
            'site_name' => $_POST['site_name'],
            'site_title' => $_POST['site_title'],
            'site_description' => $_POST['site_description'],
            'site_keywords' => $_POST['site_keywords'],
            'site_logo' => $logoPath,
            'site_favicon' => $faviconPath,
            'maintenance_mode' => isset($_POST['maintenance_mode']) ? 1 : 0,
            'register_enabled' => isset($_POST['register_enabled']) ? 1 : 0,
            'email_verification' => isset($_POST['email_verification']) ? 1 : 0,
            'facebook_url' => $_POST['facebook_url'],
            'twitter_url' => $_POST['twitter_url'],
            'instagram_url' => $_POST['instagram_url'],
            'youtube_url' => $_POST['youtube_url'],
            'pinterest_url' => $_POST['pinterest_url'],
            'contact_email' => $_POST['contact_email'],
            'contact_phone' => $_POST['contact_phone'],
            'contact_address' => $_POST['contact_address'],
            'copyright_text' => $_POST['copyright_text'],
            'google_analytics' => $_POST['google_analytics'],
            'download_limit_free' => intval($_POST['download_limit_free']),
            'display_watermark' => isset($_POST['display_watermark']) ? 1 : 0,
            'allow_guest_download' => isset($_POST['allow_guest_download']) ? 1 : 0,
            'last_updated_by' => $_SESSION['user_id']
        ]);
        
        // Log activity
        $activityQuery = $pdo->prepare("
            INSERT INTO activities (user_id, activity_type, activity_description, ip_address, created_at)
            VALUES (:user_id, 'settings_update', 'Updated site settings', :ip_address, NOW())
        ");
        
        $activityQuery->execute([
            'user_id' => $_SESSION['user_id'],
            'ip_address' => $_SERVER['REMOTE_ADDR']
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
                    <i class="fas fa-cog mr-2"></i> <?php echo $lang['site_settings'] ?? 'Site Settings'; ?>
                </h1>
                <nav class="flex" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="<?php echo $adminUrl; ?>/index.php" class="inline-flex items-center text-sm font-medium <?php echo $darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-700 hover:text-blue-600'; ?>">
                                <i class="fas fa-home mr-2"></i> <?php echo $lang['dashboard'] ?? 'Dashboard'; ?>
                            </a>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                                <span class="text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">
                                    <?php echo $lang['site_settings'] ?? 'Site Settings'; ?>
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
                            <button class="inline-block p-4 border-b-2 rounded-t-lg" id="general-tab" data-tabs-target="#general" type="button" role="tab" aria-controls="general" aria-selected="true">
                                <i class="fas fa-sliders-h mr-2"></i> <?php echo $lang['general'] ?? 'General'; ?>
                            </button>
                        </li>
                        <li class="mr-2" role="presentation">
                            <button class="inline-block p-4 border-b-2 rounded-t-lg" id="appearance-tab" data-tabs-target="#appearance" type="button" role="tab" aria-controls="appearance" aria-selected="false">
                                <i class="fas fa-palette mr-2"></i> <?php echo $lang['appearance'] ?? 'Appearance'; ?>
                            </button>
                        </li>
                        <li class="mr-2" role="presentation">
                            <button class="inline-block p-4 border-b-2 rounded-t-lg" id="social-tab" data-tabs-target="#social" type="button" role="tab" aria-controls="social" aria-selected="false">
                                <i class="fas fa-share-alt mr-2"></i> <?php echo $lang['social_media'] ?? 'Social Media'; ?>
                            </button>
                        </li>
                        <li class="mr-2" role="presentation">
                            <button class="inline-block p-4 border-b-2 rounded-t-lg" id="contact-tab" data-tabs-target="#contact" type="button" role="tab" aria-controls="contact" aria-selected="false">
                                <i class="fas fa-address-card mr-2"></i> <?php echo $lang['contact_info'] ?? 'Contact Info'; ?>
                            </button>
                        </li>
                        <li role="presentation">
                            <button class="inline-block p-4 border-b-2 rounded-t-lg" id="advanced-tab" data-tabs-target="#advanced" type="button" role="tab" aria-controls="advanced" aria-selected="false">
                                <i class="fas fa-tools mr-2"></i> <?php echo $lang['advanced'] ?? 'Advanced'; ?>
                            </button>
                        </li>
                    </ul>
                </div>
                                <!-- Tab Content -->
                <div id="settingsTabContent">
                    <!-- General Settings Tab -->
                    <div class="p-4 rounded-lg bg-white <?php echo $darkMode ? 'bg-gray-800' : ''; ?>" id="general" role="tabpanel" aria-labelledby="general-tab">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="mb-4">
                                <label for="site_name" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    <?php echo $lang['site_name'] ?? 'Site Name'; ?> <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="site_name" name="site_name" 
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>" 
                                    value="<?php echo htmlspecialchars($settings['site_name'] ?? 'WallPix'); ?>" required>
                            </div>
                            <div class="mb-4">
                                <label for="site_title" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    <?php echo $lang['site_title'] ?? 'Site Title (Meta)'; ?> <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="site_title" name="site_title" 
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>" 
                                    value="<?php echo htmlspecialchars($settings['site_title'] ?? 'WallPix - Free HD Wallpapers'); ?>" required>
                            </div>
                            <div class="mb-4 col-span-1 md:col-span-2">
                                <label for="site_description" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    <?php echo $lang['site_description'] ?? 'Site Description (Meta)'; ?>
                                </label>
                                <textarea id="site_description" name="site_description" rows="3" 
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"><?php echo htmlspecialchars($settings['site_description'] ?? 'Download Free HD Wallpapers for your devices'); ?></textarea>
                            </div>
                            <div class="mb-4 col-span-1 md:col-span-2">
                                <label for="site_keywords" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    <?php echo $lang['site_keywords'] ?? 'Site Keywords (Meta)'; ?>
                                </label>
                                <input type="text" id="site_keywords" name="site_keywords" 
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>" 
                                    value="<?php echo htmlspecialchars($settings['site_keywords'] ?? 'wallpapers, backgrounds, HD, 4K, free'); ?>" 
                                    placeholder="<?php echo $lang['keywords_comma_separated'] ?? 'Keywords separated by commas'; ?>">
                            </div>
                            <div class="mb-4">
                                <label for="copyright_text" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    <?php echo $lang['copyright_text'] ?? 'Copyright Text'; ?>
                                </label>
                                <input type="text" id="copyright_text" name="copyright_text" 
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>" 
                                    value="<?php echo htmlspecialchars($settings['copyright_text'] ?? '© ' . date('Y') . ' WallPix.Top. All rights reserved.'); ?>">
                            </div>
                            <div class="mb-4">
                                <div class="flex items-center">
                                    <input id="maintenance_mode" name="maintenance_mode" type="checkbox" 
                                        class="w-4 h-4 text-blue-600 bg-gray-100 rounded border-gray-300 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : ''; ?>" 
                                        <?php echo ($settings['maintenance_mode'] ?? 0) ? 'checked' : ''; ?>>
                                    <label for="maintenance_mode" class="ml-2 text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        <?php echo $lang['maintenance_mode'] ?? 'Enable Maintenance Mode'; ?>
                                    </label>
                                </div>
                                <p class="mt-1 text-xs <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    <?php echo $lang['maintenance_mode_help'] ?? 'When enabled, only administrators can access the site.'; ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Appearance Tab -->
                    <div class="hidden p-4 rounded-lg bg-white <?php echo $darkMode ? 'bg-gray-800' : ''; ?>" id="appearance" role="tabpanel" aria-labelledby="appearance-tab">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="mb-4">
                                <label class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>" for="site_logo">
                                    <?php echo $lang['site_logo'] ?? 'Site Logo'; ?>
                                </label>
                                <input class="block w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 cursor-pointer <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>" 
                                    id="site_logo" name="site_logo" type="file" accept="image/png, image/jpeg, image/svg+xml">
                                <p class="mt-1 text-xs <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    <?php echo $lang['recommended_size'] ?? 'Recommended size'; ?>: 200x50px (PNG, JPG, or SVG)
                                </p>
                                <?php if (!empty($settings['site_logo'])): ?>
                                <div class="mt-2">
                                    <p class="text-sm mb-1 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        <?php echo $lang['current_logo'] ?? 'Current Logo'; ?>:
                                    </p>
                                    <img src="<?php echo '../../' . htmlspecialchars($settings['site_logo']); ?>" 
                                        alt="Current logo" class="h-10 border <?php echo $darkMode ? 'border-gray-600' : 'border-gray-200'; ?> rounded p-1">
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-4">
                                <label class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>" for="site_favicon">
                                    <?php echo $lang['site_favicon'] ?? 'Site Favicon'; ?>
                                </label>
                                <input class="block w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 cursor-pointer <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>" 
                                    id="site_favicon" name="site_favicon" type="file" accept="image/x-icon, image/png, image/svg+xml">
                                <p class="mt-1 text-xs <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    <?php echo $lang['favicon_info'] ?? 'Size 32x32px or 16x16px (ICO, PNG, or SVG)'; ?>
                                </p>
                                <?php if (!empty($settings['site_favicon'])): ?>
                                <div class="mt-2">
                                    <p class="text-sm mb-1 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        <?php echo $lang['current_favicon'] ?? 'Current Favicon'; ?>:
                                    </p>
                                    <img src="<?php echo '../../' . htmlspecialchars($settings['site_favicon']); ?>" 
                                        alt="Current favicon" class="h-8 border <?php echo $darkMode ? 'border-gray-600' : 'border-gray-200'; ?> rounded p-1">
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-4">
                                <div class="flex items-center">
                                    <input id="display_watermark" name="display_watermark" type="checkbox" 
                                        class="w-4 h-4 text-blue-600 bg-gray-100 rounded border-gray-300 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : ''; ?>" 
                                        <?php echo ($settings['display_watermark'] ?? 1) ? 'checked' : ''; ?>>
                                    <label for="display_watermark" class="ml-2 text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        <?php echo $lang['display_watermark'] ?? 'Display Watermark on Images'; ?>
                                    </label>
                                </div>
                                <p class="mt-1 text-xs <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    <?php echo $lang['watermark_help'] ?? 'Adds your site name as a watermark to prevent unauthorized use.'; ?>
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Social Media Tab -->
                    <div class="hidden p-4 rounded-lg bg-white <?php echo $darkMode ? 'bg-gray-800' : ''; ?>" id="social" role="tabpanel" aria-labelledby="social-tab">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="mb-4">
                                <label for="facebook_url" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    <i class="fab fa-facebook text-blue-600 mr-2"></i> <?php echo $lang['facebook_url'] ?? 'Facebook URL'; ?>
                                </label>
                                <input type="url" id="facebook_url" name="facebook_url" 
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>" 
                                    value="<?php echo htmlspecialchars($settings['facebook_url'] ?? ''); ?>" 
                                    placeholder="https://facebook.com/wallpix">
                            </div>
                            <div class="mb-4">
                                <label for="twitter_url" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    <i class="fab fa-twitter text-blue-400 mr-2"></i> <?php echo $lang['twitter_url'] ?? 'Twitter URL'; ?>
                                </label>
                                <input type="url" id="twitter_url" name="twitter_url" 
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>" 
                                    value="<?php echo htmlspecialchars($settings['twitter_url'] ?? ''); ?>" 
                                    placeholder="https://twitter.com/wallpix">
                            </div>
                            <div class="mb-4">
                                <label for="instagram_url" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    <i class="fab fa-instagram text-pink-600 mr-2"></i> <?php echo $lang['instagram_url'] ?? 'Instagram URL'; ?>
                                </label>
                                <input type="url" id="instagram_url" name="instagram_url" 
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>" 
                                    value="<?php echo htmlspecialchars($settings['instagram_url'] ?? ''); ?>" 
                                    placeholder="https://instagram.com/wallpix">
                            </div>
                            <div class="mb-4">
                                <label for="youtube_url" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    <i class="fab fa-youtube text-red-600 mr-2"></i> <?php echo $lang['youtube_url'] ?? 'YouTube URL'; ?>
                                </label>
                                <input type="url" id="youtube_url" name="youtube_url" 
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>" 
                                    value="<?php echo htmlspecialchars($settings['youtube_url'] ?? ''); ?>" 
                                    placeholder="https://youtube.com/c/wallpix">
                            </div>
                            <div class="mb-4">
                                <label for="pinterest_url" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    <i class="fab fa-pinterest text-red-700 mr-2"></i> <?php echo $lang['pinterest_url'] ?? 'Pinterest URL'; ?>
                                </label>
                                <input type="url" id="pinterest_url" name="pinterest_url" 
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>" 
                                    value="<?php echo htmlspecialchars($settings['pinterest_url'] ?? ''); ?>" 
                                    placeholder="https://pinterest.com/wallpix">
                            </div>
                        </div>
                    </div>
                                        <!-- Contact Information Tab -->
                    <div class="hidden p-4 rounded-lg bg-white <?php echo $darkMode ? 'bg-gray-800' : ''; ?>" id="contact" role="tabpanel" aria-labelledby="contact-tab">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="mb-4">
                                <label for="contact_email" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    <i class="fas fa-envelope mr-2"></i> <?php echo $lang['contact_email'] ?? 'Contact Email'; ?>
                                </label>
                                <input type="email" id="contact_email" name="contact_email" 
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>" 
                                    value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>" 
                                    placeholder="contact@wallpix.top">
                            </div>
                            <div class="mb-4">
                                <label for="contact_phone" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    <i class="fas fa-phone-alt mr-2"></i> <?php echo $lang['contact_phone'] ?? 'Contact Phone'; ?>
                                </label>
                                <input type="text" id="contact_phone" name="contact_phone" 
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>" 
                                    value="<?php echo htmlspecialchars($settings['contact_phone'] ?? ''); ?>" 
                                    placeholder="+1 (555) 123-4567">
                            </div>
                            <div class="mb-4 col-span-1 md:col-span-2">
                                <label for="contact_address" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    <i class="fas fa-map-marker-alt mr-2"></i> <?php echo $lang['contact_address'] ?? 'Contact Address'; ?>
                                </label>
                                <textarea id="contact_address" name="contact_address" rows="3" 
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                    placeholder="123 Wallpaper St, Suite 456, Pixel City, PC 12345"><?php echo htmlspecialchars($settings['contact_address'] ?? ''); ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Advanced Settings Tab -->
                    <div class="hidden p-4 rounded-lg bg-white <?php echo $darkMode ? 'bg-gray-800' : ''; ?>" id="advanced" role="tabpanel" aria-labelledby="advanced-tab">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="mb-4">
                                <label for="download_limit_free" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    <?php echo $lang['download_limit_free'] ?? 'Daily Download Limit (Free Users)'; ?>
                                </label>
                                <input type="number" id="download_limit_free" name="download_limit_free" min="0" max="100"
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>" 
                                    value="<?php echo htmlspecialchars($settings['download_limit_free'] ?? 5); ?>">
                                <p class="mt-1 text-xs <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    <?php echo $lang['download_limit_help'] ?? 'Set to 0 for unlimited downloads'; ?>
                                </p>
                            </div>
                            <div class="mb-4">
                                <div class="flex items-center">
                                    <input id="register_enabled" name="register_enabled" type="checkbox" 
                                        class="w-4 h-4 text-blue-600 bg-gray-100 rounded border-gray-300 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : ''; ?>" 
                                        <?php echo ($settings['register_enabled'] ?? 1) ? 'checked' : ''; ?>>
                                    <label for="register_enabled" class="ml-2 text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        <?php echo $lang['enable_registration'] ?? 'Enable User Registration'; ?>
                                    </label>
                                </div>
                                <p class="mt-1 text-xs <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    <?php echo $lang['registration_help'] ?? 'When disabled, new users cannot register'; ?>
                                </p>
                            </div>
                            <div class="mb-4">
                                <div class="flex items-center">
                                    <input id="email_verification" name="email_verification" type="checkbox" 
                                        class="w-4 h-4 text-blue-600 bg-gray-100 rounded border-gray-300 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : ''; ?>" 
                                        <?php echo ($settings['email_verification'] ?? 1) ? 'checked' : ''; ?>>
                                    <label for="email_verification" class="ml-2 text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        <?php echo $lang['require_email_verification'] ?? 'Require Email Verification'; ?>
                                    </label>
                                </div>
                                <p class="mt-1 text-xs <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    <?php echo $lang['verification_help'] ?? 'Users must verify their email before logging in'; ?>
                                </p>
                            </div>
                            <div class="mb-4">
                                <div class="flex items-center">
                                    <input id="allow_guest_download" name="allow_guest_download" type="checkbox" 
                                        class="w-4 h-4 text-blue-600 bg-gray-100 rounded border-gray-300 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : ''; ?>" 
                                        <?php echo ($settings['allow_guest_download'] ?? 0) ? 'checked' : ''; ?>>
                                    <label for="allow_guest_download" class="ml-2 text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        <?php echo $lang['allow_guest_download'] ?? 'Allow Guest Downloads'; ?>
                                    </label>
                                </div>
                                <p class="mt-1 text-xs <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    <?php echo $lang['guest_download_help'] ?? 'When enabled, non-registered users can download images'; ?>
                                </p>
                            </div>
                            <div class="mb-4 col-span-1 md:col-span-2">
                                <label for="google_analytics" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                    <?php echo $lang['google_analytics'] ?? 'Google Analytics Code'; ?>
                                </label>
                                <textarea id="google_analytics" name="google_analytics" rows="4" 
                                    class="bg-gray-50 border border-gray-300 text-gray-900 text-sm font-mono rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                    placeholder="<!-- Google Analytics code here -->"><?php echo htmlspecialchars($settings['google_analytics'] ?? ''); ?></textarea>
                                <p class="mt-1 text-xs <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    <?php echo $lang['analytics_help'] ?? 'Paste your Google Analytics tracking code here'; ?>
                                </p>
                            </div>
                        </div>
                        <div class="mt-4 p-4 bg-yellow-50 rounded-lg border border-yellow-200 <?php echo $darkMode ? 'bg-yellow-900 border-yellow-700 text-yellow-200' : ''; ?>">
                            <div class="flex items-center mb-2">
                                <i class="fas fa-exclamation-triangle text-yellow-500 mr-2"></i>
                                <h3 class="text-sm font-medium <?php echo $darkMode ? 'text-yellow-200' : 'text-yellow-700'; ?>">
                                    <?php echo $lang['advanced_settings_warning'] ?? 'Advanced Settings Warning'; ?>
                                </h3>
                            </div>
                            <p class="text-xs <?php echo $darkMode ? 'text-yellow-300' : 'text-yellow-600'; ?>">
                                <?php echo $lang['settings_warning_text'] ?? 'Changes to these settings may affect site performance and user experience. Please test thoroughly after making changes.'; ?>
                            </p>
                        </div>
                        <div class="mt-4 bg-gray-100 rounded-lg p-4 <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                            <h4 class="text-sm font-medium mb-2 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">
                                <i class="fas fa-history mr-2"></i> <?php echo $lang['last_updated'] ?? 'Last Updated'; ?>
                            </h4>
                            <p class="text-xs <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                <?php 
                                if (!empty($settings['last_updated'])) {
                                    echo date('F j, Y H:i:s', strtotime($settings['last_updated']));
                                    if (!empty($settings['last_updated_by'])) {
                                        // Get the username of the last updater
                                        $userQuery = $pdo->prepare("SELECT username FROM users WHERE id = :user_id");
                                        $userQuery->execute(['user_id' => $settings['last_updated_by']]);
                                        $username = $userQuery->fetchColumn();
                                        if ($username) {
                                            echo ' ' . $lang['by'] ?? 'by' . ' ' . htmlspecialchars($username);
                                        }
                                    }
                                } else {
                                    echo $lang['not_updated_yet'] ?? 'Not updated yet';
                                }
                                ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="mt-6 flex items-center justify-end">
                    <button type="submit" class="py-2 px-4 bg-blue-600 hover:bg-blue-700 focus:ring-blue-500 focus:ring-offset-blue-200 text-white w-auto transition ease-in duration-200 text-center text-base font-semibold shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 rounded-lg">
                        <i class="fas fa-save mr-2"></i> <?php echo $lang['save_settings'] ?? 'Save Settings'; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Tab switching functionality
        const tabs = document.querySelectorAll('[role="tab"]');
        const tabContents = document.querySelectorAll('[role="tabpanel"]');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Deactivate all tabs
                tabs.forEach(t => {
                    t.classList.remove('text-blue-600', 'border-blue-600', '<?php echo $darkMode ? "text-blue-300 border-blue-500" : ""; ?>');
                    t.classList.add('text-gray-500', 'border-transparent', '<?php echo $darkMode ? "text-gray-400" : ""; ?>');
                    t.setAttribute('aria-selected', 'false');
                });
                
                // Hide all tab contents
                tabContents.forEach(content => {
                    content.classList.add('hidden');
                });
                
                // Activate clicked tab
                tab.classList.remove('text-gray-500', 'border-transparent', '<?php echo $darkMode ? "text-gray-400" : ""; ?>');
                tab.classList.add('text-blue-600', 'border-blue-600', '<?php echo $darkMode ? "text-blue-300 border-blue-500" : ""; ?>');
                tab.setAttribute('aria-selected', 'true');
                
                // Show corresponding tab content
                const targetId = tab.getAttribute('data-tabs-target');
                const target = document.querySelector(targetId);
                target.classList.remove('hidden');
            });
        });
        
        // Set default active tab
        document.getElementById('general-tab').click();
        
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
                        label.textContent = type === 'logo' ? '<?php echo $lang['new_logo'] ?? 'New Logo Preview'; ?>' : '<?php echo $lang['new_favicon'] ?? 'New Favicon Preview'; ?>';
                        
                        const img = document.createElement('img');
                        img.id = `${type}-preview`;
                        img.className = type === 'logo' ? 'h-10 border rounded p-1' : 'h-8 border rounded p-1';
                        img.className += ' <?php echo $darkMode ? "border-gray-600" : "border-gray-200"; ?>';
                        img.alt = `New ${type} preview`;
                        
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