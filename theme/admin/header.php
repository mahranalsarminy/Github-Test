<?php
/**
 * Admin Panel Header Template
 *
 * @package WallPix
 * @version 1.0.0
 */

// Include required files
require_once __DIR__ . '/../../includes/init.php';

// Verify admin login
require_admin();

// Get current language
$currentLang = $_SESSION['admin_language'] ?? 'en';
$isRTL = $currentLang === 'ar';

// Get dark mode setting
$darkMode = $_SESSION['admin_dark_mode'] ?? false;

// Load language file
$langFile = __DIR__ . '/../../lang/' . $currentLang . '.php';
if (file_exists($langFile)) {
    $lang = include $langFile;
} else {
    $lang = include __DIR__ . '/../../lang/en.php';
}

// Current user information
$currentUser = $_SESSION['user_name'] ?? 'mahranalsarminy';
$currentDateTime = '2025-03-17 23:21:04';

// Get current page filename (for highlighting active menu)
$currentPage = basename($_SERVER['PHP_SELF']);
$currentDir = basename(dirname($_SERVER['PHP_SELF']));

// Function to check if menu item is active
function isMenuActive($pageNames) {
    global $currentPage, $currentDir;
    if (is_array($pageNames)) {
        foreach ($pageNames as $pageName) {
            if ($currentPage === $pageName || $currentDir === $pageName) {
                return true;
            }
        }
        return false;
    }
    return $currentPage === $pageNames || $currentDir === $pageNames;
}

// Website URLs
$siteUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'];
$adminUrl = $siteUrl . '/admin';
?>
<!DOCTYPE html>
<html lang="<?php echo $currentLang; ?>" dir="<?php echo $isRTL ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? 'Admin Panel - WallPix'); ?></title>
    
    <!-- Tailwind CSS -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Custom Admin CSS -->
    <link href="<?php echo $siteUrl; ?>/theme/admin/css/admin.css" rel="stylesheet">
    
    <?php if ($isRTL): ?>
    <!-- RTL Support -->
    <link href="<?php echo $siteUrl; ?>/theme/admin/css/rtl.css" rel="stylesheet">
    <?php endif; ?>
    
    <?php if ($darkMode): ?>
    <!-- Dark Mode Support -->
    <style>
        .dark-mode {
            background-color: #1a202c;
            color: #f7fafc;
        }
        .dark-mode .bg-white {
            background-color: #2d3748;
        }
        .dark-mode .border-gray-200 {
            border-color: #4a5568;
        }
        .dark-mode .text-gray-500 {
            color: #a0aec0;
        }
        .dark-mode .text-gray-700 {
            color: #e2e8f0;
        }
        .dark-mode .text-gray-800 {
            color: #f7fafc;
        }
        .dark-mode .shadow-md {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.2);
        }
        .dark-mode input, 
        .dark-mode select, 
        .dark-mode textarea {
            background-color: #4a5568;
            border-color: #718096;
            color: #f7fafc;
        }
        .dark-mode input::placeholder {
            color: #a0aec0;
        }
        .dark-mode .bg-gray-100 {
            background-color: #2d3748;
        }
        .dark-mode .bg-gray-50 {
            background-color: #374151;
        }

        /* Header for Dark Mode */
        .dark-mode nav {
            background-color: #2d3748; /* Darken the header background */
            color: #f7fafc;
        }
        .dark-mode nav a {
            color: #f7fafc; /* Adjust link color */
        }
    </style>
    <?php endif; ?>
    
    <!-- Custom Styles -->
    <style>
        /* Sidebar animation */
        .sidebar {
            transition: width 0.3s ease;
        }
        
        .sidebar-collapsed {
            width: 0;
        }
        
        /* Responsive adjustments */
        @media (min-width: 768px) {
            .content-wrapper {
                margin-left: 16rem;
                transition: margin-left 0.3s ease;
            }
            
            .sidebar-collapsed + .content-wrapper {
                margin-left: 0;
            }
        }
        
        /* Custom button styling */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.5rem 1rem;
            border-radius: 0.375rem;
            font-weight: 500;
            transition: all 0.2s ease;
        }
        
        .btn-primary {
            background-color: #3b82f6;
            color: white;
        }
        
        .btn-primary:hover {
            background-color: #2563eb;
        }
        
        /* RTL adjustments */
        html[dir="rtl"] .ml-2 {
            margin-left: 0;
            margin-right: 0.5rem;
        }
        
        html[dir="rtl"] .mr-2 {
            margin-right: 0;
            margin-left: 0.5rem;
        }
        
        /* Utility classes */
        .pointer-events-none {
            pointer-events: none;
        }
        
        /* Fix for fixed header overlap issue */
        body {
            padding-top: 4rem; /* Ensure content doesn't overlap with fixed header */
        }
    </style>
    <!-- Custom JavaScript for the header -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Language Switcher
    const languageItems = document.querySelectorAll('.language-item');
    languageItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const newLang = item.getAttribute('data-lang');
            
            fetch('../../admin/ajax/update_language.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    language: newLang
                })
            }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    console.error('Error updating language:', data.message);
                }
            });
        });
    });

    // Dark Mode Toggle
    const darkModeButton = document.getElementById('darkModeToggle');
    darkModeButton.addEventListener('click', function() {
        const darkModeStatus = <?php echo $darkMode ? 'false' : 'true'; ?> ? '1' : '0';
        
        fetch('../../admin/ajax/update_theme.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                dark_mode: darkModeStatus
            })
        }).then(response => response.json())
        .then(data => {
            if (data.success) {
                window.location.reload();
            } else {
                console.error('Error updating theme:', data.message);
            }
        });
    });
});
    </script>
</head>
<body class="flex flex-col min-h-screen <?php echo $darkMode ? 'dark-mode' : ''; ?>">
    <!-- Top Navigation -->
    <nav class="bg-white border-b border-gray-200 fixed z-30 w-full <?php echo $darkMode ? 'bg-gray-800 border-gray-700' : ''; ?>">
        <div class="px-3 py-3 lg:px-5 lg:pl-3">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    <!-- Sidebar Toggle Button (mobile) -->
                    <button id="sidebarToggle" class="p-2 rounded-md lg:hidden focus:outline-none focus:ring-2 focus:ring-gray-300 <?php echo $darkMode ? 'text-gray-400 hover:bg-gray-700' : 'text-gray-600 hover:bg-gray-100'; ?>">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <!-- Logo -->
                    <a href="<?php echo $adminUrl; ?>/index.php" class="flex ml-2 md:mr-24">
                        <span class="self-center text-xl font-semibold sm:text-2xl whitespace-nowrap <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                            WallPix <span class="text-blue-500">Admin</span>
                        </span>
                    </a>
                </div>
                
                <!-- Right Navigation Items -->
                <div class="flex items-center">
                    <!-- Toggle Dark/Light Mode -->
                    <button id="darkModeToggle" class="p-2 rounded-md mr-2 focus:outline-none focus:ring-2 focus:ring-gray-300 <?php echo $darkMode ? 'text-yellow-300 hover:bg-gray-700' : 'text-gray-600 hover:bg-gray-100'; ?>">
                        <?php if ($darkMode): ?>
                            <i class="fas fa-sun"></i>
                        <?php else: ?>
                            <i class="fas fa-moon"></i>
                        <?php endif; ?>
                    </button>
                    
                    <!-- Language Switcher -->
                    <div class="relative mr-2">
                        <button id="languageDropdown" class="p-2 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-300 <?php echo $darkMode ? 'text-gray-400 hover:bg-gray-700' : 'text-gray-600 hover:bg-gray-100'; ?>">
                            <?php if ($currentLang === 'ar'): ?>
                                <span>AR</span>
                            <?php else: ?>
                                <span>EN</span>
                            <?php endif; ?>
                        </button>
                        <div id="languageDropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                            <a href="#" data-lang="en" class="language-item block px-4 py-2 text-sm <?php echo $darkMode ? 'text-gray-300 hover:bg-gray-600' : 'text-gray-700 hover:bg-gray-100'; ?> <?php echo $currentLang === 'en' ? 'bg-gray-200' : ''; ?>">English</a>
                            <a href="#" data-lang="ar" class="language-item block px-4 py-2 text-sm <?php echo $darkMode ? 'text-gray-300 hover:bg-gray-600' : 'text-gray-700 hover:bg-gray-100'; ?> <?php echo $currentLang === 'ar' ? 'bg-gray-200' : ''; ?>">العربية</a>
                        </div>
                    </div>
                    
                    <!-- User Dropdown -->
                    <div class="relative">
                        <button id="userDropdown" class="flex items-center p-2 rounded-md focus:outline-none focus:ring-2 focus:ring-gray-300 <?php echo $darkMode ? 'text-gray-400 hover:bg-gray-700' : 'text-gray-600 hover:bg-gray-100'; ?>">
                            <span class="mr-2 text-sm font-medium"><?php echo htmlspecialchars($currentUser); ?></span>
                            <i class="fas fa-user-circle text-xl"></i>
                        </button>
                        <div id="userDropdownMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                            <a href="<?php echo $adminUrl; ?>/profile.php" class="block px-4 py-2 text-sm <?php echo $darkMode ? 'text-gray-300 hover:bg-gray-600' : 'text-gray-700 hover:bg-gray-100'; ?>">
                                <i class="fas fa-user-cog mr-2"></i> <?php echo $lang['profile'] ?? 'Profile'; ?>
                            </a>
                            <a href="<?php echo $adminUrl; ?>/settings.php" class="block px-4 py-2 text-sm <?php echo $darkMode ? 'text-gray-300 hover:bg-gray-600' : 'text-gray-700 hover:bg-gray-100'; ?>">
                                <i class="fas fa-cog mr-2"></i> <?php echo $lang['settings'] ?? 'Settings'; ?>
                            </a>
                            <div class="border-t border-gray-200 <?php echo $darkMode ? 'border-gray-600' : ''; ?>"></div>
                            <a href="<?php echo $adminUrl; ?>/logout.php" class="block px-4 py-2 text-sm <?php echo $darkMode ? 'text-red-400 hover:bg-gray-600' : 'text-red-600 hover:bg-gray-100'; ?>">
                                <i class="fas fa-sign-out-alt mr-2"></i> <?php echo $lang['logout'] ?? 'Logout'; ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Current Date and Time Info -->
        <div class="px-3 py-1 bg-gray-100 text-xs text-gray-600 <?php echo $darkMode ? 'bg-gray-700 text-gray-400' : ''; ?>">
            <div class="flex justify-between items-center">
                <div>
                    <?php echo date('l, F j, Y', strtotime($currentDateTime)); ?>
                </div>
                <div>
                    <?php echo $currentDateTime; ?> (UTC)
                </div>
            </div>
        </div>
    </nav>