<?php
/**
 * Categories Management - Index
 * 
 * Displays a list of all categories with options to add, edit, and delete.
 * 
 * @package WallPix
 * @version 1.0.0
 */

// Define the project root directory
define('ROOT_DIR', dirname(dirname(__DIR__)));

// Include necessary files
require_once ROOT_DIR . '/includes/init.php';

// Set page title
$pageTitle = 'Categories';

// Initialize variables
$success_message = '';
$error_message = '';

// Handle category deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $category_id = (int)$_GET['delete'];
    
    try {
        // Check if category has media items
        $media_check = $pdo->prepare("SELECT COUNT(*) FROM media WHERE category_id = ?");
        $media_check->execute([$category_id]);
        $media_count = $media_check->fetchColumn();
        
        // Check if category has child categories
        $child_check = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
        $child_check->execute([$category_id]);
        $child_count = $child_check->fetchColumn();
        
        if ($media_count > 0) {
            $error_message = "Cannot delete category: It contains $media_count media item(s). Please reassign these items before deleting.";
        } elseif ($child_count > 0) {
            $error_message = "Cannot delete category: It has $child_count child category/categories. Please delete or reassign these first.";
        } else {
            // Get category name for activity log
            $name_query = $pdo->prepare("SELECT name FROM categories WHERE id = ?");
            $name_query->execute([$category_id]);
            $category_name = $name_query->fetchColumn();
            
            // Delete the category
            $delete_stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $delete_stmt->execute([$category_id]);
            
            // Log activity
            $activity_stmt = $pdo->prepare("INSERT INTO activities (user_id, description, created_at) VALUES (?, ?, NOW())");
            $activity_stmt->execute([
                $_SESSION['user_id'] ?? null,
                "Deleted category: $category_name (ID: $category_id)"
            ]);
            
            $success_message = "Category \"$category_name\" has been deleted successfully.";
        }
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
}

// Handle category status toggle
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $category_id = (int)$_GET['toggle_status'];
    
    try {
        // Get current status
        $status_query = $pdo->prepare("SELECT is_active, name FROM categories WHERE id = ?");
        $status_query->execute([$category_id]);
        $result = $status_query->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $new_status = $result['is_active'] ? 0 : 1;
            $status_text = $new_status ? 'activated' : 'deactivated';
            
            // Update status
            $update_stmt = $pdo->prepare("UPDATE categories SET is_active = ? WHERE id = ?");
            $update_stmt->execute([$new_status, $category_id]);
            
            // Log activity
            $activity_stmt = $pdo->prepare("INSERT INTO activities (user_id, description, created_at) VALUES (?, ?, NOW())");
            $activity_stmt->execute([
                $_SESSION['user_id'] ?? null,
                "Category \"{$result['name']}\" was $status_text"
            ]);
            
            $success_message = "Category \"{$result['name']}\" has been $status_text successfully.";
        }
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
}

// Handle featured toggle
if (isset($_GET['toggle_featured']) && is_numeric($_GET['toggle_featured'])) {
    $category_id = (int)$_GET['toggle_featured'];
    
    try {
        // Get current featured status
        $featured_query = $pdo->prepare("SELECT featured, name FROM categories WHERE id = ?");
        $featured_query->execute([$category_id]);
        $result = $featured_query->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $new_status = $result['featured'] ? 0 : 1;
            $status_text = $new_status ? 'featured' : 'unfeatured';
            
            // Update featured status
            $update_stmt = $pdo->prepare("UPDATE categories SET featured = ? WHERE id = ?");
            $update_stmt->execute([$new_status, $category_id]);
            
            // Log activity
            $activity_stmt = $pdo->prepare("INSERT INTO activities (user_id, description, created_at) VALUES (?, ?, NOW())");
            $activity_stmt->execute([
                $_SESSION['user_id'] ?? null,
                "Category \"{$result['name']}\" was marked as $status_text"
            ]);
            
            $success_message = "Category \"{$result['name']}\" has been marked as $status_text successfully.";
        }
    } catch (PDOException $e) {
        $error_message = "Database error: " . $e->getMessage();
    }
}

// Get all categories with parent category names
try {
    $categories_query = $pdo->query("
        SELECT c.*, 
               p.name AS parent_name,
               (SELECT COUNT(*) FROM media WHERE category_id = c.id) AS media_count
        FROM categories c
        LEFT JOIN categories p ON c.parent_id = p.id
        ORDER BY c.display_order, c.name
    ");
    $categories = $categories_query->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Error loading categories: " . $e->getMessage();
    $categories = [];
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
                        <i class="fas fa-folder mr-2"></i> Category Management
                    </h1>
                    <a href="add.php" class="btn bg-blue-500 hover:bg-blue-700 text-white">
                        <i class="fas fa-plus mr-2"></i> Add New Category
                    </a>
                </div>

                <?php if (!empty($success_message)): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 <?php echo $darkMode ? 'bg-green-900 text-green-300 border-green-500' : ''; ?>" role="alert">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-500 <?php echo $darkMode ? 'text-green-400' : ''; ?>"></i>
                            </div>
                            <div class="ml-3">
                                <p class="font-medium"><?= $success_message ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($error_message)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 <?php echo $darkMode ? 'bg-red-900 text-red-300 border-red-500' : ''; ?>" role="alert">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-exclamation-triangle text-red-500 <?php echo $darkMode ? 'text-red-400' : ''; ?>"></i>
                            </div>
                            <div class="ml-3">
                                <p class="font-medium"><?= $error_message ?></p>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Categories Table -->
                <div class="bg-white <?php echo $darkMode ? 'bg-gray-800' : ''; ?> rounded-lg shadow-md overflow-hidden">
                    <?php if (count($categories) > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-700' : ''; ?>">
                                <thead class="<?php echo $darkMode ? 'bg-gray-700' : 'bg-gray-50'; ?>">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">ID</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">Icon</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">Name</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">Parent</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">Order</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">Status</th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">Media</th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">Actions</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-700' : ''; ?>">
                                    <?php foreach ($categories as $category): ?>
                                        <tr class="<?php echo $darkMode ? 'bg-gray-800 hover:bg-gray-700' : 'bg-white hover:bg-gray-50'; ?>">
                                            <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>"><?= $category['id'] ?></td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($category['icon_url']): ?>
                                                    <img src="<?= htmlspecialchars($category['icon_url']) ?>" alt="Icon" class="h-6 w-6">
                                                <?php else: ?>
                                                    <i class="fas fa-folder text-gray-400"></i>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <div class="ml-0">
                                                        <div class="text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                                            <?= htmlspecialchars($category['name']) ?>
                                                            <?php if ($category['featured']): ?>
                                                                <span class="ml-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 <?php echo $darkMode ? 'bg-purple-900 text-purple-200' : ''; ?>">
                                                                    Featured
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                        <div class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>"><?= htmlspecialchars($category['slug']) ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">
                                                <?= $category['parent_name'] ? htmlspecialchars($category['parent_name']) : '<span class="text-gray-400">None</span>' ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">
                                                <?= $category['display_order'] ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($category['is_active']): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 <?php echo $darkMode ? 'bg-green-900 text-green-200' : ''; ?>">
                                                        Active
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 <?php echo $darkMode ? 'bg-red-900 text-red-200' : ''; ?>">
                                                        Inactive
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                                                                        <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">
                                                <a href="../media/index.php?category=<?= $category['id'] ?>" class="hover:underline">
                                                    <?= $category['media_count'] ?> item<?= $category['media_count'] != 1 ? 's' : '' ?>
                                                </a>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex justify-end space-x-2">
                                                    <a href="edit.php?id=<?= $category['id'] ?>" class="text-blue-600 hover:text-blue-900 <?php echo $darkMode ? 'text-blue-400 hover:text-blue-300' : ''; ?>" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    
                                                    <a href="index.php?toggle_status=<?= $category['id'] ?>" class="<?php echo $category['is_active'] ? 'text-green-600 hover:text-green-900' : 'text-red-600 hover:text-red-900'; ?> <?php echo $darkMode ? ($category['is_active'] ? 'text-green-400 hover:text-green-300' : 'text-red-400 hover:text-red-300') : ''; ?>" title="<?php echo $category['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                        <i class="fas <?php echo $category['is_active'] ? 'fa-toggle-on' : 'fa-toggle-off'; ?>"></i>
                                                    </a>
                                                    
                                                    <a href="index.php?toggle_featured=<?= $category['id'] ?>" class="<?php echo $category['featured'] ? 'text-purple-600 hover:text-purple-900' : 'text-gray-600 hover:text-gray-900'; ?> <?php echo $darkMode ? ($category['featured'] ? 'text-purple-400 hover:text-purple-300' : 'text-gray-400 hover:text-gray-300') : ''; ?>" title="<?php echo $category['featured'] ? 'Remove from featured' : 'Add to featured'; ?>">
                                                        <i class="fas fa-star"></i>
                                                    </a>
                                                    
                                                    <a href="#" onclick="confirmDelete(<?= $category['id'] ?>, '<?= htmlspecialchars(addslashes($category['name'])) ?>', <?= $category['media_count'] ?>); return false;" class="text-red-600 hover:text-red-900 <?php echo $darkMode ? 'text-red-400 hover:text-red-300' : ''; ?>" title="Delete">
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
                        <div class="p-6 text-center <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">
                            <i class="fas fa-folder-open text-5xl mb-4 <?php echo $darkMode ? 'text-gray-400' : 'text-gray-300'; ?>"></i>
                            <p>No categories found. Get started by adding your first category.</p>
                            <a href="add.php" class="inline-block mt-4 px-4 py-2 bg-blue-500 hover:bg-blue-700 text-white rounded-md">
                                <i class="fas fa-plus mr-2"></i> Add New Category
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed z-10 inset-0 overflow-y-auto hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        <div class="inline-block align-bottom bg-white <?php echo $darkMode ? 'bg-gray-800' : ''; ?> rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white <?php echo $darkMode ? 'bg-gray-800' : ''; ?> px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 <?php echo $darkMode ? 'bg-red-900' : ''; ?> sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-exclamation-triangle text-red-600 <?php echo $darkMode ? 'text-red-400' : ''; ?>"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>" id="modal-title">
                            Delete Category
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>" id="deleteModalContent">
                                Are you sure you want to delete this category?
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 <?php echo $darkMode ? 'bg-gray-700' : ''; ?> px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <a href="#" id="confirmDeleteBtn" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Delete
                </a>
                <button type="button" onclick="closeDeleteModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 <?php echo $darkMode ? 'border-gray-500' : ''; ?> shadow-sm px-4 py-2 <?php echo $darkMode ? 'bg-gray-800 text-gray-300' : 'bg-white text-gray-700'; ?> hover:bg-gray-50 <?php echo $darkMode ? 'hover:bg-gray-700' : ''; ?> focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for handling the delete confirmation modal -->
<script>
    function confirmDelete(id, name, mediaCount) {
        const modal = document.getElementById('deleteModal');
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        const modalContent = document.getElementById('deleteModalContent');
        
        if (mediaCount > 0) {
            modalContent.innerHTML = `<strong>${name}</strong> contains ${mediaCount} media item${mediaCount !== 1 ? 's' : ''}.<br>Please reassign or delete these items before deleting the category.`;
            confirmBtn.style.display = 'none';
        } else {
            modalContent.innerHTML = `Are you sure you want to delete <strong>${name}</strong>?<br>This action cannot be undone.`;
            confirmBtn.style.display = 'inline-flex';
            confirmBtn.href = `index.php?delete=${id}`;
        }
        
        modal.classList.remove('hidden');
    }
    
    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }
    
    // Close modal when clicking outside
    window.onclick = function(event) {
        const modal = document.getElementById('deleteModal');
        if (event.target === modal) {
            closeDeleteModal();
        }
    }
</script>

<?php
// Set current date and time for footer
$currentDateTime = '2025-03-19 03:09:42';
$currentUser = 'mahranalsarminy';

// Include admin panel footer
include ROOT_DIR . '/theme/admin/footer.php';
?>