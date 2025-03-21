<?php
/**
 * Pages Management - Pages List
 * 
 * Allows administrators to view and manage website pages.
 * 
 * @package WallPix
 * @version 1.0.0
 */

// Define the project root directory
define('ROOT_DIR', dirname(dirname(__DIR__)));

// Include necessary files
require_once ROOT_DIR . '/includes/init.php';

// Set page title
$pageTitle = 'Pages Management';

// Handle page type filtering
$pageType = isset($_GET['type']) ? $_GET['type'] : 'all';

// Handle status toggle action
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $page_id = (int)$_GET['toggle_status'];
    $page_type = isset($_GET['page_type']) ? $_GET['page_type'] : '';
    
    try {
        // Determine the table based on page type
        $table = '';
        switch ($page_type) {
            case 'about':
                $table = 'about_content';
                break;
            case 'privacy':
                $table = 'privacy_content';
                break;
            case 'terms':
                $table = 'terms_content';
                break;
            default:
                throw new Exception("Invalid page type");
        }
        
        // Get current status
        $stmt = $pdo->prepare("SELECT is_active FROM $table WHERE id = ?");
        $stmt->execute([$page_id]);
        $currentStatus = $stmt->fetchColumn();
        
        // Toggle status
        $newStatus = $currentStatus ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE $table SET is_active = ? WHERE id = ?");
        $stmt->execute([$newStatus, $page_id]);
        
        // Log activity
        $action = $newStatus ? "activated" : "deactivated";
        $pdo->prepare("
            INSERT INTO activities (user_id, description, created_at) 
            VALUES (:user_id, :description, NOW())
        ")->execute([
            ':user_id' => $_SESSION['user_id'] ?? null,
            ':description' => "Page content #$page_id ($page_type) $action"
        ]);
        
        $_SESSION['success'] = "Page status updated successfully.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    // Redirect to maintain clean URL
    header("Location: index.php" . ($pageType != 'all' ? "?type=$pageType" : ""));
    exit;
}

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $page_id = (int)$_GET['delete'];
    $page_type = isset($_GET['page_type']) ? $_GET['page_type'] : '';
    
    try {
        // Determine the table based on page type
        $table = '';
        switch ($page_type) {
            case 'about':
                $table = 'about_content';
                break;
            case 'privacy':
                $table = 'privacy_content';
                break;
            case 'terms':
                $table = 'terms_content';
                break;
            default:
                throw new Exception("Invalid page type");
        }
        
        // Get page title before delete
        $stmt = $pdo->prepare("SELECT title FROM $table WHERE id = ?");
        $stmt->execute([$page_id]);
        $pageTitle = $stmt->fetchColumn();
        
        // Delete the page
        $stmt = $pdo->prepare("DELETE FROM $table WHERE id = ?");
        $stmt->execute([$page_id]);
        
        // Log activity
        $pdo->prepare("
            INSERT INTO activities (user_id, description, created_at) 
            VALUES (:user_id, :description, NOW())
        ")->execute([
            ':user_id' => $_SESSION['user_id'] ?? null,
            ':description' => "Deleted page content #$page_id: $pageTitle ($page_type)"
        ]);
        
        $_SESSION['success'] = "Page content deleted successfully.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    // Redirect to maintain clean URL
    header("Location: index.php" . ($pageType != 'all' ? "?type=$pageType" : ""));
    exit;
}
// Fetch pages based on type
$pages = [];
try {
    // Fetch About pages
    if ($pageType == 'all' || $pageType == 'about') {
        $aboutPages = $pdo->query("
            SELECT id, title, content, image_url, sort_order, is_active, created_at, 'about' as page_type 
            FROM about_content 
            ORDER BY sort_order, title
        ")->fetchAll(PDO::FETCH_ASSOC);
        $pages = array_merge($pages, $aboutPages);
    }
    
    // Fetch Privacy pages
    if ($pageType == 'all' || $pageType == 'privacy') {
        $privacyPages = $pdo->query("
            SELECT id, title, content, NULL as image_url, sort_order, is_active, created_at, 'privacy' as page_type 
            FROM privacy_content 
            ORDER BY sort_order, title
        ")->fetchAll(PDO::FETCH_ASSOC);
        $pages = array_merge($pages, $privacyPages);
    }
    
    // Fetch Terms pages
    if ($pageType == 'all' || $pageType == 'terms') {
        $termsPages = $pdo->query("
            SELECT id, title, content, NULL as image_url, sort_order, is_active, created_at, 'terms' as page_type 
            FROM terms_content 
            ORDER BY sort_order, title
        ")->fetchAll(PDO::FETCH_ASSOC);
        $pages = array_merge($pages, $termsPages);
    }
    
    // Sort all pages by sort_order and title
    usort($pages, function($a, $b) {
        if ($a['sort_order'] == $b['sort_order']) {
            return strcmp($a['title'], $b['title']);
        }
        return $a['sort_order'] - $b['sort_order'];
    });
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Error retrieving pages: " . $e->getMessage();
}

// Function to count words in HTML content
function countWords($html) {
    // Remove HTML tags
    $text = strip_tags($html);
    // Count words
    return str_word_count($text);
}

// Function to generate a truncated preview of HTML content
function generateContentPreview($html, $maxLength = 100) {
    // Remove HTML tags
    $text = strip_tags($html);
    // Truncate to specified length
    if (strlen($text) > $maxLength) {
        $text = substr($text, 0, $maxLength) . '...';
    }
    return $text;
}

// Include admin panel header
include ROOT_DIR . '/theme/admin/header.php';
?>
<!-- Main Content Container with Improved Layout -->
<div class="flex flex-col md:flex-row min-h-screen">
    <!-- Sidebar -->
    <?php include ROOT_DIR . '/theme/admin/slidbar.php'; ?>
    
    <!-- Main Content Area -->
    <div class="w-full md:pl-64">
        <div class="p-6 <?php echo $darkMode ? 'bg-gray-900' : 'bg-gray-100'; ?>">
            <div class="mb-6">
                <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                    <h1 class="text-2xl font-semibold mb-4 md:mb-0 <?php echo $darkMode ? 'text-white' : ''; ?>">
                        <i class="fas fa-file-alt mr-2"></i> Pages Management
                    </h1>
                    <div class="flex space-x-3">
                        <div class="relative">
                            <button id="pageTypeDropdown" class="btn bg-blue-500 hover:bg-blue-700 text-white flex items-center">
                                <i class="fas fa-filter mr-2"></i>
                                <?php 
                                switch ($pageType) {
                                    case 'about':
                                        echo 'About Pages';
                                        break;
                                    case 'privacy':
                                        echo 'Privacy Pages';
                                        break;
                                    case 'terms':
                                        echo 'Terms Pages';
                                        break;
                                    default:
                                        echo 'All Pages';
                                }
                                ?>
                                <i class="fas fa-chevron-down ml-2"></i>
                            </button>
                            <div id="pageTypeMenu" class="hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                                <a href="index.php" class="block px-4 py-2 text-sm <?php echo $pageType == 'all' ? ($darkMode ? 'bg-gray-600 text-white' : 'bg-gray-200 text-gray-900') : ($darkMode ? 'text-gray-300 hover:bg-gray-600' : 'text-gray-700 hover:bg-gray-100'); ?>">
                                    All Pages
                                </a>
                                <a href="index.php?type=about" class="block px-4 py-2 text-sm <?php echo $pageType == 'about' ? ($darkMode ? 'bg-gray-600 text-white' : 'bg-gray-200 text-gray-900') : ($darkMode ? 'text-gray-300 hover:bg-gray-600' : 'text-gray-700 hover:bg-gray-100'); ?>">
                                    About Pages
                                </a>
                                <a href="index.php?type=privacy" class="block px-4 py-2 text-sm <?php echo $pageType == 'privacy' ? ($darkMode ? 'bg-gray-600 text-white' : 'bg-gray-200 text-gray-900') : ($darkMode ? 'text-gray-300 hover:bg-gray-600' : 'text-gray-700 hover:bg-gray-100'); ?>">
                                    Privacy Pages
                                </a>
                                <a href="index.php?type=terms" class="block px-4 py-2 text-sm <?php echo $pageType == 'terms' ? ($darkMode ? 'bg-gray-600 text-white' : 'bg-gray-200 text-gray-900') : ($darkMode ? 'text-gray-300 hover:bg-gray-600' : 'text-gray-700 hover:bg-gray-100'); ?>">
                                    Terms Pages
                                </a>
                            </div>
                        </div>
                        
                        <div class="dropdown">
                            <button class="btn bg-green-500 hover:bg-green-700 text-white dropdown-toggle">
                                <i class="fas fa-plus mr-2"></i> Add New
                                <i class="fas fa-chevron-down ml-2"></i>
                            </button>
                            <div class="dropdown-menu hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg py-1 z-10 <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                                <a href="add.php?type=about" class="block px-4 py-2 text-sm <?php echo $darkMode ? 'text-gray-300 hover:bg-gray-600' : 'text-gray-700 hover:bg-gray-100'; ?>">
                                    About Page
                                </a>
                                <a href="add.php?type=privacy" class="block px-4 py-2 text-sm <?php echo $darkMode ? 'text-gray-300 hover:bg-gray-600' : 'text-gray-700 hover:bg-gray-100'; ?>">
                                    Privacy Page
                                </a>
                                <a href="add.php?type=terms" class="block px-4 py-2 text-sm <?php echo $darkMode ? 'text-gray-300 hover:bg-gray-600' : 'text-gray-700 hover:bg-gray-100'; ?>">
                                    Terms Page
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 <?php echo $darkMode ? 'bg-green-900 text-green-300 border-green-500' : ''; ?>" role="alert">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-500 <?php echo $darkMode ? 'text-green-400' : ''; ?>"></i>
                            </div>
                            <div class="ml-3">
                                <p class="font-medium"><?= htmlspecialchars($_SESSION['success']) ?></p>
                            </div>
                        </div>
                    </div>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 <?php echo $darkMode ? 'bg-red-900 text-red-300 border-red-500' : ''; ?>" role="alert">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-circle text-red-500 <?php echo $darkMode ? 'text-red-400' : ''; ?>"></i>
                            </div>
                            <div class="ml-3">
                                <p class="font-medium"><?= htmlspecialchars($_SESSION['error']) ?></p>
                            </div>
                        </div>
                    </div>
                    <?php unset($_SESSION['error']); ?>
                <?php endif; ?>
                 <!-- Pages Table -->
                <div class="bg-white <?php echo $darkMode ? 'bg-gray-800' : ''; ?> rounded-lg shadow-md overflow-hidden">
                    <?php if (!empty($pages)): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-700' : ''; ?>">
                                <thead class="bg-gray-50 <?php echo $darkMode ? 'bg-gray-700 text-gray-300' : 'text-gray-600'; ?>">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                            Title
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                            Type
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                            Content Preview
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                            Order
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                            Created
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200 <?php echo $darkMode ? 'bg-gray-800 text-gray-200 divide-gray-700' : ''; ?>">
                                    <?php foreach ($pages as $page): ?>
                                        <tr class="hover:bg-gray-50 <?php echo $darkMode ? 'hover:bg-gray-700' : ''; ?>">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <?php if ($page['page_type'] === 'about' && !empty($page['image_url'])): ?>
                                                        <div class="flex-shrink-0 h-10 w-10 mr-3">
                                                            <img class="h-10 w-10 rounded-full object-cover" src="<?= htmlspecialchars($page['image_url']) ?>" alt="<?= htmlspecialchars($page['title']) ?>">
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="flex-shrink-0 h-10 w-10 mr-3 bg-gray-200 rounded-full flex items-center justify-center <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                                                            <?php if ($page['page_type'] === 'about'): ?>
                                                                <i class="fas fa-info-circle text-blue-500"></i>
                                                            <?php elseif ($page['page_type'] === 'privacy'): ?>
                                                                <i class="fas fa-shield-alt text-green-500"></i>
                                                            <?php else: ?>
                                                                <i class="fas fa-file-contract text-purple-500"></i>
                                                            <?php endif; ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="text-sm font-medium">
                                                        <a href="edit.php?id=<?= $page['id'] ?>&type=<?= $page['page_type'] ?>" class="hover:underline <?php echo $darkMode ? 'text-blue-400' : 'text-blue-600'; ?>">
                                                            <?= htmlspecialchars($page['title']) ?>
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php
                                                $badgeClass = '';
                                                switch ($page['page_type']) {
                                                    case 'about':
                                                        $badgeClass = $darkMode ? 'bg-blue-800 text-blue-200' : 'bg-blue-100 text-blue-800';
                                                        break;
                                                    case 'privacy':
                                                        $badgeClass = $darkMode ? 'bg-green-800 text-green-200' : 'bg-green-100 text-green-800';
                                                        break;
                                                    case 'terms':
                                                        $badgeClass = $darkMode ? 'bg-purple-800 text-purple-200' : 'bg-purple-100 text-purple-800';
                                                        break;
                                                }
                                                ?>
                                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= $badgeClass ?>">
                                                    <?= ucfirst($page['page_type']) ?>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?> max-w-xs truncate">
                                                    <?= htmlspecialchars(generateContentPreview($page['content'], 80)) ?>
                                                </div>
                                                <div class="text-xs text-gray-400 <?php echo $darkMode ? 'text-gray-500' : ''; ?> mt-1">
                                                    <?= countWords($page['content']) ?> words
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <?= $page['sort_order'] ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($page['is_active']): ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 <?php echo $darkMode ? 'bg-green-900 text-green-200' : ''; ?>">
                                                        Active
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 <?php echo $darkMode ? 'bg-red-900 text-red-200' : ''; ?>">
                                                        Inactive
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                                                <?= date('M j, Y', strtotime($page['created_at'])) ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex justify-end space-x-2">
                                                    <a href="edit.php?id=<?= $page['id'] ?>&type=<?= $page['page_type'] ?>" class="text-blue-600 hover:text-blue-900 <?php echo $darkMode ? 'text-blue-400 hover:text-blue-300' : ''; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="index.php?toggle_status=<?= $page['id'] ?>&page_type=<?= $page['page_type'] ?>" class="<?= $page['is_active'] ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900' ?> <?php echo $darkMode ? ($page['is_active'] ? 'text-red-400 hover:text-red-300' : 'text-green-400 hover:text-green-300') : ''; ?>">
                                                        <i class="fas <?= $page['is_active'] ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                                                    </a>
                                                    <a href="#" onclick="confirmDelete(<?= $page['id'] ?>, '<?= htmlspecialchars($page['title']) ?>', '<?= $page['page_type'] ?>')" class="text-red-600 hover:text-red-900 <?php echo $darkMode ? 'text-red-400 hover:text-red-300' : ''; ?>">
                                                        <i class="fas fa-trash-alt"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8">
                            <i class="fas fa-file-alt text-gray-400 text-5xl mb-4"></i>
                            <p class="text-gray-600 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">No pages found.</p>
                            <p class="text-gray-500 mt-2 <?php echo $darkMode ? 'text-gray-500' : ''; ?>">
                                <?php if ($pageType != 'all'): ?>
                                    No <?= $pageType ?> pages found. <a href="add.php?type=<?= $pageType ?>" class="text-blue-500 hover:underline">Create a new <?= $pageType ?> page</a>.
                                <?php else: ?>
                                    No pages found. Click "Add New" to create your first page.
                                <?php endif; ?>
                            </p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 hidden z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div id="modalOverlay" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

        <!-- Modal panel -->
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 <?php echo $darkMode ? 'bg-gray-800' : ''; ?>">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10 <?php echo $darkMode ? 'bg-red-900' : ''; ?>">
                        <i class="fas fa-exclamation-triangle text-red-600 <?php echo $darkMode ? 'text-red-400' : ''; ?>"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 <?php echo $darkMode ? 'text-white' : ''; ?>" id="modal-title">
                            Delete Page?
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>" id="deleteModalContent">
                                Are you sure you want to delete this page? This action cannot be undone.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                <a id="confirmDeleteButton" href="#" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Delete
                </a>
                <button type="button" id="cancelDeleteButton" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm <?php echo $darkMode ? 'bg-gray-600 text-white border-gray-600 hover:bg-gray-500' : ''; ?>">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Dropdown functionality for page type filter
    document.addEventListener('DOMContentLoaded', function() {
        const pageTypeDropdown = document.getElementById('pageTypeDropdown');
        const pageTypeMenu = document.getElementById('pageTypeMenu');
        
        if (pageTypeDropdown && pageTypeMenu) {
            pageTypeDropdown.addEventListener('click', function() {
                pageTypeMenu.classList.toggle('hidden');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!pageTypeDropdown.contains(e.target)) {
                    pageTypeMenu.classList.add('hidden');
                }
            });
        }
        
        // Add New dropdown functionality
        const dropdownToggle = document.querySelector('.dropdown-toggle');
        const dropdownMenu = document.querySelector('.dropdown-menu');
        
        if (dropdownToggle && dropdownMenu) {
            dropdownToggle.addEventListener('click', function() {
                                dropdownMenu.classList.toggle('hidden');
            });
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!dropdownToggle.contains(e.target)) {
                    dropdownMenu.classList.add('hidden');
                }
            });
        }
    });
    
    // Delete confirmation modal functionality
    function confirmDelete(id, title, type) {
        const modal = document.getElementById('deleteModal');
        const overlay = document.getElementById('modalOverlay');
        const cancelButton = document.getElementById('cancelDeleteButton');
        const confirmButton = document.getElementById('confirmDeleteButton');
        const modalContent = document.getElementById('deleteModalContent');
        
        // Update modal content
        modalContent.innerHTML = `Are you sure you want to delete the page <strong>"${title}"</strong>? This action cannot be undone.`;
        
        // Set the confirm button URL
        confirmButton.href = `index.php?delete=${id}&page_type=${type}`;
        
        // Show the modal
        modal.classList.remove('hidden');
        
        // Handle close modal events
        const closeModal = () => {
            modal.classList.add('hidden');
        };
        
        overlay.addEventListener('click', closeModal);
        cancelButton.addEventListener('click', closeModal);
        
        // Clean up event listeners to prevent memory leaks
        const cleanup = () => {
            overlay.removeEventListener('click', closeModal);
            cancelButton.removeEventListener('click', closeModal);
            confirmButton.removeEventListener('click', cleanup);
        };
        
        confirmButton.addEventListener('click', cleanup);
    }
</script>

<?php
// Set current date and time for footer
$currentDateTime = '2025-03-19 03:50:01';
$currentUser = 'mahranalsarminy';

// Include admin panel footer
include ROOT_DIR . '/theme/admin/footer.php';
?>