<?php
/**
 * Admin Panel Sidebar Template
 *
 * @package WallPix
 * @version 1.0.0
 */

// Include required files
require_once __DIR__ . '/../../includes/init.php';

require_once __DIR__ . '/../../includes/auth.php';

// Verify admin login
require_admin();

?>
<!-- Sidebar -->
<aside id="sidebar" class="fixed top-0 left-0 z-20 flex flex-col flex-shrink-0 w-64 h-full pt-16 font-normal duration-75 lg:flex transition-width sidebar <?php echo $darkMode ? 'bg-gray-800 text-white' : 'bg-white text-gray-900'; ?> border-r <?php echo $darkMode ? 'border-gray-700' : 'border-gray-200'; ?>">
    <div class="relative flex flex-col flex-1 min-h-0 pt-0">
        <div class="flex flex-col flex-1 pt-5 pb-4 overflow-y-auto">
            <div class="flex-1 px-3 space-y-1 bg-white divide-y divide-gray-200 <?php echo $darkMode ? 'bg-gray-800 divide-gray-700' : ''; ?>">
                <!-- Main Navigation -->
                <ul class="pb-2 space-y-2">
                    <!-- Dashboard -->
                    <li>
                        <a href="<?php echo $adminUrl; ?>/index.php" class="flex items-center p-2 text-base rounded-lg <?php echo isMenuActive('index.php') ? ($darkMode ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-900') : ($darkMode ? 'text-gray-300 hover:bg-gray-700' : 'text-gray-900 hover:bg-gray-100'); ?>">
                            <i class="fas fa-tachometer-alt w-6 h-6 text-gray-500 transition duration-75 <?php echo $darkMode ? 'text-gray-400 group-hover:text-gray-300' : 'group-hover:text-gray-900'; ?>"></i>
                            <span class="ml-3"><?php echo $lang['dashboard'] ?? 'Dashboard'; ?></span>
                        </a>
                    </li>
                    
                    <!-- Media Management -->
                    <li>
                        <button type="button" class="flex items-center w-full p-2 text-base text-left rounded-lg transition duration-75 <?php echo isMenuActive(['media', 'categories']) ? ($darkMode ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-900') : ($darkMode ? 'text-gray-300 hover:bg-gray-700' : 'text-gray-900 hover:bg-gray-100'); ?>" aria-controls="dropdown-media" data-collapse-toggle="dropdown-media">
                            <i class="fas fa-photo-video w-6 h-6 text-gray-500 transition duration-75 <?php echo $darkMode ? 'text-gray-400 group-hover:text-gray-300' : 'group-hover:text-gray-900'; ?>"></i>
                            <span class="flex-1 ml-3 text-left whitespace-nowrap"><?php echo $lang['media_management'] ?? 'Media Management'; ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <ul id="dropdown-media" class="py-2 space-y-2 <?php echo isMenuActive(['media', 'categories']) ? '' : 'hidden'; ?>">
                            <li>
                                <a href="<?php echo $adminUrl; ?>/media/index.php" class="flex items-center p-2 pl-11 text-base rounded-lg <?php echo isMenuActive('media') ? ($darkMode ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-900') : ($darkMode ? 'text-gray-300 hover:bg-gray-700' : 'text-gray-900 hover:bg-gray-100'); ?>">
                                    <?php echo $lang['media'] ?? 'Media'; ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo $adminUrl; ?>/categories/index.php" class="flex items-center p-2 pl-11 text-base rounded-lg <?php echo isMenuActive('categories') ? ($darkMode ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-900') : ($darkMode ? 'text-gray-300 hover:bg-gray-700' : 'text-gray-900 hover:bg-gray-100'); ?>">
                                    <?php echo $lang['categories'] ?? 'Categories'; ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo $adminUrl; ?>/tags/index.php" class="flex items-center p-2 pl-11 text-base rounded-lg <?php echo isMenuActive('tags') ? ($darkMode ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-900') : ($darkMode ? 'text-gray-300 hover:bg-gray-700' : 'text-gray-900 hover:bg-gray-100'); ?>">
                                    <?php echo $lang['tags'] ?? 'Tags'; ?>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- User Management -->
                    <li>
                        <button type="button" class="flex items-center w-full p-2 text-base text-left rounded-lg transition duration-75 <?php echo isMenuActive(['users', 'subscriptions']) ? ($darkMode ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-900') : ($darkMode ? 'text-gray-300 hover:bg-gray-700' : 'text-gray-900 hover:bg-gray-100'); ?>" aria-controls="dropdown-users" data-collapse-toggle="dropdown-users">
                            <i class="fas fa-users w-6 h-6 text-gray-500 transition duration-75 <?php echo $darkMode ? 'text-gray-400 group-hover:text-gray-300' : 'group-hover:text-gray-900'; ?>"></i>
                            <span class="flex-1 ml-3 text-left whitespace-nowrap"><?php echo $lang['user_management'] ?? 'User Management'; ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <ul id="dropdown-users" class="py-2 space-y-2 <?php echo isMenuActive(['users', 'subscriptions']) ? '' : 'hidden'; ?>">
                            <li>
                                <a href="<?php echo $adminUrl; ?>/users/index.php" class="flex items-center p-2 pl-11 text-base rounded-lg <?php echo isMenuActive('users') ? ($darkMode ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-900') : ($darkMode ? 'text-gray-300 hover:bg-gray-700' : 'text-gray-900 hover:bg-gray-100'); ?>">
                                    <?php echo $lang['users'] ?? 'Users'; ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo $adminUrl; ?>/subscriptions/index.php" class="flex items-center p-2 pl-11 text-base rounded-lg <?php echo isMenuActive('subscriptions') ? ($darkMode ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-900') : ($darkMode ? 'text-gray-300 hover:bg-gray-700' : 'text-gray-900 hover:bg-gray-100'); ?>">
                                    <?php echo $lang['subscriptions'] ?? 'Subscriptions'; ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo $adminUrl; ?>/payments/index.php" class="flex items-center p-2 pl-11 text-base rounded-lg <?php echo isMenuActive('payments') ? ($darkMode ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-900') : ($darkMode ? 'text-gray-300 hover:bg-gray-700' : 'text-gray-900 hover:bg-gray-100'); ?>">
                                    <?php echo $lang['payments'] ?? 'Payments'; ?>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Content Management -->
                    <li>
                        <button type="button" class="flex items-center w-full p-2 text-base text-left rounded-lg transition duration-75 <?php echo isMenuActive(['pages', 'features']) ? ($darkMode ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-900') : ($darkMode ? 'text-gray-300 hover:bg-gray-700' : 'text-gray-900 hover:bg-gray-100'); ?>" aria-controls="dropdown-content" data-collapse-toggle="dropdown-content">
                            <i class="fas fa-file-alt w-6 h-6 text-gray-500 transition duration-75 <?php echo $darkMode ? 'text-gray-400 group-hover:text-gray-300' : 'group-hover:text-gray-900'; ?>"></i>
                            <span class="flex-1 ml-3 text-left whitespace-nowrap"><?php echo $lang['content_management'] ?? 'Content Management'; ?></span>
                            <i class="fas fa-chevron-down"></i>
                        </button>
                        <ul id="dropdown-content" class="py-2 space-y-2 <?php echo isMenuActive(['pages', 'features']) ? '' : 'hidden'; ?>">
                            <li>
                                <a href="<?php echo $adminUrl; ?>/pages/index.php" class="flex items-center p-2 pl-11 text-base rounded-lg <?php echo isMenuActive('pages') ? ($darkMode ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-900') : ($darkMode ? 'text-gray-300 hover:bg-gray-700' : 'text-gray-900 hover:bg-gray-100'); ?>">
                                    <?php echo $lang['pages'] ?? 'Pages'; ?>
                                </a>
                            </li>
                            <li>
                                <a href="<?php echo $adminUrl; ?>/features/index.php" class="flex items-center p-2 pl-11 text-base rounded-lg <?php echo isMenuActive('features') ? ($darkMode ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-900') : ($darkMode ? 'text-gray-300 hover:bg-gray-700' : 'text-gray-900 hover:bg-gray-100'); ?>">
                                    <?php echo $lang['features'] ?? 'Features'; ?>
                                </a>
                            </li>
                        </ul>
                    </li>
                    
                    <!-- Reports -->
                    <li>
                        <a href="<?php echo $adminUrl; ?>/reports/index.php" class="flex items-center p-2 text-base rounded-lg <?php echo isMenuActive('reports') ? ($darkMode ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-900') : ($darkMode ? 'text-gray-300 hover:bg-gray-700' : 'text-gray-900 hover:bg-gray-100'); ?>">
                            <i class="fas fa-chart-bar w-6 h-6 text-gray-500 transition duration-75 <?php echo $darkMode ? 'text-gray-400 group-hover:text-gray-300' : 'group-hover:text-gray-900'; ?>"></i>
                            <span class="ml-3"><?php echo $lang['reports'] ?? 'Reports'; ?></span>
                        </a>
                    </li>
                </ul>
                
                <!-- System Menu -->
                <ul class="pt-4 mt-4 space-y-2">
                    <li>
                        <a href="<?php echo $adminUrl; ?>/settings/index.php" class="flex items-center p-2 text-base rounded-lg <?php echo isMenuActive('settings') ? ($darkMode ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-900') : ($darkMode ? 'text-gray-300 hover:bg-gray-700' : 'text-gray-900 hover:bg-gray-100'); ?>">
                            <i class="fas fa-cog w-6 h-6 text-gray-500 transition duration-75 <?php echo $darkMode ? 'text-gray-400 group-hover:text-gray-300' : 'group-hover:text-gray-900'; ?>"></i>
                            <span class="ml-3"><?php echo $lang['site_settings'] ?? 'Site Settings'; ?></span>
                        </a>
                    </li>
                    
                    <li>
                        <a href="<?php echo $adminUrl; ?>/language/index.php" class="flex items-center p-2 text-base rounded-lg <?php echo isMenuActive('language') ? ($darkMode ? 'bg-gray-700 text-white' : 'bg-gray-100 text-gray-900') : ($darkMode ? 'text-gray-300 hover:bg-gray-700' : 'text-gray-900 hover:bg-gray-100'); ?>">
                            <i class="fas fa-language w-6 h-6 text-gray-500 transition duration-75 <?php echo $darkMode ? 'text-gray-400 group-hover:text-gray-300' : 'group-hover:text-gray-900'; ?>"></i>
                            <span class="ml-3"><?php echo $lang['language'] ?? 'Language'; ?></span>
                        </a>
                    </li>
                    
                    <li>
                        <a href="<?php echo $siteUrl; ?>" target="_blank" class="flex items-center p-2 text-base rounded-lg <?php echo $darkMode ? 'text-gray-300 hover:bg-gray-700' : 'text-gray-900 hover:bg-gray-100'; ?>">
                            <i class="fas fa-external-link-alt w-6 h-6 text-gray-500 transition duration-75 <?php echo $darkMode ? 'text-gray-400 group-hover:text-gray-300' : 'group-hover:text-gray-900'; ?>"></i>
                            <span class="ml-3"><?php echo $lang['visit_site'] ?? 'Visit Site'; ?></span>
                        </a>
                    </li>
                    
                    <li>
                        <a href="<?php echo $adminUrl; ?>/logout.php" class="flex items-center p-2 text-base rounded-lg <?php echo $darkMode ? 'text-red-400 hover:bg-gray-700' : 'text-red-600 hover:bg-gray-100'; ?>">
                                                        <i class="fas fa-sign-out-alt w-6 h-6 text-gray-500 transition duration-75 <?php echo $darkMode ? 'text-red-400 group-hover:text-red-300' : 'text-red-600 group-hover:text-red-700'; ?>"></i>
                            <span class="ml-3"><?php echo $lang['logout'] ?? 'Logout'; ?></span>
                        </a>
                    </li>
                </ul>
                
                <!-- User Info -->
                <div class="pt-4 mt-4">
                    <div class="p-4 rounded-lg bg-gray-100 <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-user-circle text-3xl <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>"></i>
                            </div>
                            <div class="ml-3">
                                <p class="text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                    <?php echo htmlspecialchars($currentUser); ?>
                                </p>
                                <p class="text-xs <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                                    <?php echo date('Y-m-d H:i:s', strtotime($currentDateTime)); ?>
                                </p>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="flex space-x-2">
                                <a href="<?php echo $adminUrl; ?>/profile.php" class="px-2 py-1 text-xs rounded-md <?php echo $darkMode ? 'bg-gray-600 text-gray-300 hover:bg-gray-500' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>" title="<?php echo $lang['edit_profile'] ?? 'Edit Profile'; ?>">
                                    <i class="fas fa-user-edit"></i>
                                </a>
                                <a href="<?php echo $adminUrl; ?>/notifications.php" class="px-2 py-1 text-xs rounded-md <?php echo $darkMode ? 'bg-gray-600 text-gray-300 hover:bg-gray-500' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>" title="<?php echo $lang['notifications'] ?? 'Notifications'; ?>">
                                    <i class="fas fa-bell"></i>
                                </a>
                                <a href="<?php echo $adminUrl; ?>/settings.php" class="px-2 py-1 text-xs rounded-md <?php echo $darkMode ? 'bg-gray-600 text-gray-300 hover:bg-gray-500' : 'bg-gray-200 text-gray-700 hover:bg-gray-300'; ?>" title="<?php echo $lang['settings'] ?? 'Settings'; ?>">
                                    <i class="fas fa-cog"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Version Info -->
        <div class="hidden sm:flex p-3 border-t <?php echo $darkMode ? 'border-gray-700' : 'border-gray-200'; ?>">
            <div class="w-full">
                <p class="text-xs text-center <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                    WallPix.Top v1.0.0
                </p>
            </div>
        </div>
    </div>
</aside>

<script>
    // Sidebar toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('sidebar-collapsed');
            });
        }
        
        // Dropdown menu functionality
        const dropdownToggles = document.querySelectorAll('[data-collapse-toggle]');
        
        dropdownToggles.forEach(toggle => {
            toggle.addEventListener('click', function() {
                const targetId = this.getAttribute('aria-controls');
                const target = document.getElementById(targetId);
                
                if (target) {
                    target.classList.toggle('hidden');
                    
                    // Toggle the dropdown icon
                    const icon = this.querySelector('.fa-chevron-down');
                    if (icon) {
                        icon.classList.toggle('fa-chevron-up');
                    }
                }
            });
        });
    });
</script>