<?php
/**
 * Tags Management - Tags List
 * 
 * Allows administrators to view and manage website tags.
 * 
 * @package WallPix
 * @version 1.0.0
 */

// Define the project root directory
define('ROOT_DIR', dirname(dirname(__DIR__)));

// Include necessary files
require_once ROOT_DIR . '/includes/init.php';

// Set page title
$pageTitle = 'Tags Management';

// Current date and time
$currentDateTime = '2025-03-19 05:08:32';
$currentUser = 'mahranalsarminy';

// Handle status toggle action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $tag_id = (int)$_GET['delete'];
    
    try {
        // Check if tag is in use
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM media_tags WHERE tag_id = ?");
        $stmt->execute([$tag_id]);
        $usage_count = $stmt->fetchColumn();
        
        if ($usage_count > 0) {
            $_SESSION['error'] = "This tag cannot be deleted because it is used by $usage_count media items.";
        } else {
            // Get tag name before delete for logging
            $stmt = $pdo->prepare("SELECT name FROM tags WHERE id = ?");
            $stmt->execute([$tag_id]);
            $tagName = $stmt->fetchColumn();
            
            // Delete the tag
            $stmt = $pdo->prepare("DELETE FROM tags WHERE id = ?");
            $stmt->execute([$tag_id]);
            
            // Log activity
            $pdo->prepare("
                INSERT INTO activities (user_id, description, created_at) 
                VALUES (:user_id, :description, NOW())
            ")->execute([
                ':user_id' => $_SESSION['user_id'] ?? null,
                ':description' => "Deleted tag #$tag_id: $tagName"
            ]);
            
            $_SESSION['success'] = "Tag deleted successfully.";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
    
    // Redirect to maintain clean URL
    header("Location: index.php");
    exit;
}

// Handle bulk actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && isset($_POST['tag_ids'])) {
    $action = $_POST['action'];
    $tag_ids = $_POST['tag_ids'];
    
    if (!empty($tag_ids)) {
        try {
            if ($action === 'delete') {
                // Check if any selected tag is in use
                $placeholders = implode(',', array_fill(0, count($tag_ids), '?'));
                $stmt = $pdo->prepare("SELECT tag_id, COUNT(*) as count FROM media_tags WHERE tag_id IN ($placeholders) GROUP BY tag_id");
                $stmt->execute($tag_ids);
                $used_tags = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                
                $cannot_delete = [];
                foreach ($tag_ids as $id) {
                    if (isset($used_tags[$id]) && $used_tags[$id] > 0) {
                        $cannot_delete[] = $id;
                    }
                }
                
                if (!empty($cannot_delete)) {
                    // Get tag names that cannot be deleted
                    $placeholders = implode(',', array_fill(0, count($cannot_delete), '?'));
                    $stmt = $pdo->prepare("SELECT id, name FROM tags WHERE id IN ($placeholders)");
                    $stmt->execute($cannot_delete);
                    $tag_names = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                    
                    $error_message = "The following tags cannot be deleted because they are in use: ";
                    $tag_list = [];
                    foreach ($tag_names as $id => $name) {
                        $tag_list[] = "$name (used by {$used_tags[$id]} items)";
                    }
                    $_SESSION['error'] = $error_message . implode(', ', $tag_list);
                    
                    // Remove tags that cannot be deleted
                    $tag_ids = array_diff($tag_ids, $cannot_delete);
                }
                
                if (!empty($tag_ids)) {
                    // Get tag names before delete for logging
                    $placeholders = implode(',', array_fill(0, count($tag_ids), '?'));
                    $stmt = $pdo->prepare("SELECT id, name FROM tags WHERE id IN ($placeholders)");
                    $stmt->execute($tag_ids);
                    $deleted_tags = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                    
                    // Delete the tags
                    $stmt = $pdo->prepare("DELETE FROM tags WHERE id IN ($placeholders)");
                    $stmt->execute($tag_ids);
                    
                    // Log activity
                    $tags_list = implode(', ', array_map(function($id, $name) {
                        return "$name (ID: $id)";
                    }, array_keys($deleted_tags), $deleted_tags));
                    
                    $pdo->prepare("
                        INSERT INTO activities (user_id, description, created_at) 
                        VALUES (:user_id, :description, NOW())
                    ")->execute([
                        ':user_id' => $_SESSION['user_id'] ?? null,
                        ':description' => "Bulk deleted tags: $tags_list"
                    ]);
                    
                    if (!isset($_SESSION['error'])) {
                        $_SESSION['success'] = "Selected tags deleted successfully.";
                    } else {
                        $_SESSION['success'] = "Some tags were deleted successfully.";
                    }
                }
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Database error: " . $e->getMessage();
        }
        
        // Redirect to maintain clean URL
        header("Location: index.php");
        exit;
    }
}

// Set up search parameters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20; // Items per page
$offset = ($currentPage - 1) * $limit;

// Build the base query
$query = "SELECT t.id, t.name, t.slug, t.created_by, t.created_at, 
                 COALESCE(u.username, 'System') as creator_name,
                 COUNT(mt.media_id) as media_count
          FROM tags t
          LEFT JOIN users u ON t.created_by = u.id
          LEFT JOIN media_tags mt ON t.id = mt.tag_id";

$countQuery = "SELECT COUNT(*) FROM tags t";

// Add search condition if search is provided
if (!empty($search)) {
    $searchWhere = " WHERE t.name LIKE :search OR t.slug LIKE :search";
    $query .= $searchWhere;
    $countQuery .= $searchWhere;
}

// Add group by and order by
$query .= " GROUP BY t.id ORDER BY t.created_at DESC LIMIT :limit OFFSET :offset";

// Execute the count query to get total records
try {
    if (!empty($search)) {
        $stmt = $pdo->prepare($countQuery);
        $searchParam = "%$search%";
        $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
        $stmt->execute();
    } else {
        $stmt = $pdo->query($countQuery);
    }
    $totalTags = $stmt->fetchColumn();
} catch (PDOException $e) {
    $_SESSION['error'] = "Error counting tags: " . $e->getMessage();
    $totalTags = 0;
}

// Calculate total pages
$totalPages = ceil($totalTags / $limit);

// Make sure current page is within range
if ($currentPage < 1) $currentPage = 1;
if ($totalPages > 0 && $currentPage > $totalPages) $currentPage = $totalPages;

// Execute the main query to get tags for current page
try {
    $stmt = $pdo->prepare($query);
    if (!empty($search)) {
        $searchParam = "%$search%";
        $stmt->bindParam(':search', $searchParam, PDO::PARAM_STR);
    }
    $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error retrieving tags: " . $e->getMessage();
    $tags = [];
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
                        <i class="fas fa-tags mr-2"></i> Tags Management
                    </h1>
                    <a href="add.php" class="btn bg-green-500 hover:bg-green-700 text-white">
                        <i class="fas fa-plus mr-2"></i> Add New Tag
                    </a>
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

                <!-- Search and Filters -->
                <div class="bg-white <?php echo $darkMode ? 'bg-gray-800' : ''; ?> rounded-lg shadow-md p-4 mb-6">
                    <form action="index.php" method="get" class="flex flex-col md:flex-row gap-4 items-end">
                        <div class="flex-grow">
                            <label for="search" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                Search Tags
                            </label>
                            <div class="mt-1 relative rounded-md shadow-sm">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                    <i class="fas fa-search text-gray-400"></i>
                                </div>
                                <input type="text" name="search" id="search" value="<?= htmlspecialchars($search) ?>"
                                    class="pl-10 focus:ring-blue-500 focus:border-blue-500 block w-full rounded-md border-gray-300 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white placeholder-gray-400' : ''; ?>"
                                    placeholder="Search by tag name or slug">
                            </div>
                        </div>
                        <div>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white py-2 px-4 rounded">
                                <i class="fas fa-search mr-2"></i> Search
                            </button>
                            <?php if (!empty($search)): ?>
                                <a href="index.php" class="ml-2 bg-gray-500 hover:bg-gray-700 text-white py-2 px-4 rounded">
                                    Clear
                                </a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- Bulk Actions Form -->
                <form action="index.php" method="post" id="bulkActionForm">
                    <!-- Tags Table -->
                    <div class="bg-white <?php echo $darkMode ? 'bg-gray-800' : ''; ?> rounded-lg shadow-md overflow-hidden mb-6">
                        <?php if (!empty($tags)): ?>
                            <div class="overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-700' : ''; ?>">
                                    <thead class="bg-gray-50 <?php echo $darkMode ? 'bg-gray-700 text-gray-300' : 'text-gray-600'; ?>">
                                        <tr>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider w-10">
                                                <input type="checkbox" id="selectAll" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : ''; ?>">
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                                Tag Name
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                                Slug
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                                Usage
                                            </th>
                                                                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                                Created By
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                                Created Date
                                            </th>
                                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider">
                                                Actions
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white divide-y divide-gray-200 <?php echo $darkMode ? 'bg-gray-800 text-gray-200 divide-gray-700' : ''; ?>">
                                        <?php foreach ($tags as $tag): ?>
                                            <tr class="hover:bg-gray-50 <?php echo $darkMode ? 'hover:bg-gray-700' : ''; ?>">
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <input type="checkbox" name="tag_ids[]" value="<?= $tag['id'] ?>" class="tag-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : ''; ?>">
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-medium">
                                                        <a href="edit.php?id=<?= $tag['id'] ?>" class="hover:underline <?php echo $darkMode ? 'text-blue-400' : 'text-blue-600'; ?>">
                                                            <?= htmlspecialchars($tag['name']) ?>
                                                        </a>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                                                        <?= htmlspecialchars($tag['slug']) ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <?php if ($tag['media_count'] > 0): ?>
                                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800 <?php echo $darkMode ? 'bg-blue-900 text-blue-200' : ''; ?>">
                                                            <?= $tag['media_count'] ?> media
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="text-gray-400 text-xs">Not used</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                                                        <?= htmlspecialchars($tag['creator_name']) ?>
                                                    </div>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                                                    <?= date('Y-m-d H:i', strtotime($tag['created_at'])) ?>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <div class="flex justify-end space-x-2">
                                                        <a href="edit.php?id=<?= $tag['id'] ?>" class="text-blue-600 hover:text-blue-900 <?php echo $darkMode ? 'text-blue-400 hover:text-blue-300' : ''; ?>" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <?php if ($tag['media_count'] == 0): ?>
                                                            <a href="#" onclick="confirmDelete(<?= $tag['id'] ?>, '<?= htmlspecialchars(addslashes($tag['name'])) ?>')" class="text-red-600 hover:text-red-900 <?php echo $darkMode ? 'text-red-400 hover:text-red-300' : ''; ?>" title="Delete">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </a>
                                                        <?php else: ?>
                                                            <span class="text-gray-400 cursor-not-allowed" title="Cannot delete tag in use">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            
                            <!-- Bulk Actions -->
                            <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 <?php echo $darkMode ? 'bg-gray-700 text-gray-200 border-gray-700' : ''; ?>">
                                <div class="flex items-center gap-2">
                                    <select name="action" id="bulkAction" class="block w-40 rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                        <option value="">Bulk Actions</option>
                                        <option value="delete">Delete Selected</option>
                                    </select>
                                    <button type="button" id="applyBulkAction" class="bg-blue-500 hover:bg-blue-700 text-white py-2 px-4 rounded">
                                        Apply
                                    </button>
                                    <span id="selectedCount" class="ml-3 text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">0 items selected</span>
                                </div>
                            </div>
                            
                            <!-- Pagination -->
                            <?php if ($totalPages > 1): ?>
                                <div class="px-6 py-3 bg-gray-50 border-t border-gray-200 <?php echo $darkMode ? 'bg-gray-700 text-gray-200 border-gray-700' : ''; ?>">
                                    <div class="flex items-center justify-between">
                                        <div class="text-sm text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">
                                            Showing <span class="font-medium"><?= $offset + 1 ?></span> to 
                                            <span class="font-medium"><?= min($offset + $limit, $totalTags) ?></span> of 
                                            <span class="font-medium"><?= $totalTags ?></span> tags
                                        </div>
                                        <div class="flex space-x-1">
                                            <?php if ($currentPage > 1): ?>
                                                <a href="?page=1<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="px-3 py-1 rounded-md bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 <?php echo $darkMode ? 'bg-gray-600 border-gray-600 text-gray-200 hover:bg-gray-500' : ''; ?>">
                                                    <i class="fas fa-angle-double-left"></i>
                                                </a>
                                                <a href="?page=<?= $currentPage - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="px-3 py-1 rounded-md bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 <?php echo $darkMode ? 'bg-gray-600 border-gray-600 text-gray-200 hover:bg-gray-500' : ''; ?>">
                                                    <i class="fas fa-angle-left"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <?php
                                            // Calculate range of page numbers to show
                                            $range = 2;
                                            $startPage = max(1, $currentPage - $range);
                                            $endPage = min($totalPages, $currentPage + $range);
                                            
                                            // Show first page if not in range
                                            if ($startPage > 1) {
                                                echo '<a href="?page=1' . (!empty($search) ? '&search=' . urlencode($search) : '') . '" class="px-3 py-1 rounded-md bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 ' . ($darkMode ? 'bg-gray-600 border-gray-600 text-gray-200 hover:bg-gray-500' : '') . '">1</a>';
                                                if ($startPage > 2) {
                                                    echo '<span class="px-3 py-1 text-gray-500 ' . ($darkMode ? 'text-gray-400' : '') . '">...</span>';
                                                }
                                            }
                                            
                                            // Show page numbers
                                            for ($i = $startPage; $i <= $endPage; $i++) {
                                                $isCurrentPage = $i == $currentPage;
                                                echo '<a href="?page=' . $i . (!empty($search) ? '&search=' . urlencode($search) : '') . '" class="px-3 py-1 rounded-md ' . 
                                                    ($isCurrentPage 
                                                        ? 'bg-blue-600 border border-blue-600 text-white ' 
                                                        : 'bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 ' . ($darkMode ? 'bg-gray-600 border-gray-600 text-gray-200 hover:bg-gray-500' : '')
                                                    ) . '">' . $i . '</a>';
                                            }
                                            
                                            // Show last page if not in range
                                            if ($endPage < $totalPages) {
                                                if ($endPage < $totalPages - 1) {
                                                    echo '<span class="px-3 py-1 text-gray-500 ' . ($darkMode ? 'text-gray-400' : '') . '">...</span>';
                                                }
                                                echo '<a href="?page=' . $totalPages . (!empty($search) ? '&search=' . urlencode($search) : '') . '" class="px-3 py-1 rounded-md bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 ' . ($darkMode ? 'bg-gray-600 border-gray-600 text-gray-200 hover:bg-gray-500' : '') . '">' . $totalPages . '</a>';
                                            }
                                            ?>
                                            
                                            <?php if ($currentPage < $totalPages): ?>
                                                <a href="?page=<?= $currentPage + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="px-3 py-1 rounded-md bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 <?php echo $darkMode ? 'bg-gray-600 border-gray-600 text-gray-200 hover:bg-gray-500' : ''; ?>">
                                                    <i class="fas fa-angle-right"></i>
                                                </a>
                                                <a href="?page=<?= $totalPages ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>" class="px-3 py-1 rounded-md bg-white border border-gray-300 text-gray-700 hover:bg-gray-100 <?php echo $darkMode ? 'bg-gray-600 border-gray-600 text-gray-200 hover:bg-gray-500' : ''; ?>">
                                                    <i class="fas fa-angle-double-right"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                        <?php else: ?>
                            <div class="text-center py-8">
                                <i class="fas fa-tags text-gray-400 text-5xl mb-4"></i>
                                <?php if (!empty($search)): ?>
                                    <p class="text-gray-600 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">No tags found matching your search.</p>
                                    <p class="text-gray-500 mt-2 <?php echo $darkMode ? 'text-gray-500' : ''; ?>">
                                        <a href="index.php" class="text-blue-600 hover:underline <?php echo $darkMode ? 'text-blue-400' : ''; ?>">Clear search</a> to see all tags.
                                    </p>
                                <?php else: ?>
                                    <p class="text-gray-600 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">No tags found.</p>
                                    <p class="text-gray-500 mt-2 <?php echo $darkMode ? 'text-gray-500' : ''; ?>">
                                        Add your first tag by clicking the "Add New Tag" button.
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </form>
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
                            Delete Tag
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>" id="deleteModalContent">
                                Are you sure you want to delete this tag? This action cannot be undone.
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

<!-- Bulk Delete Confirmation Modal -->
<div id="bulkDeleteModal" class="fixed inset-0 hidden z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div id="bulkModalOverlay" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>

        <!-- Modal panel -->
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4 <?php echo $darkMode ? 'bg-gray-800' : ''; ?>">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10 <?php echo $darkMode ? 'bg-red-900' : ''; ?>">
                        <i class="fas fa-exclamation-triangle text-red-600 <?php echo $darkMode ? 'text-red-400' : ''; ?>"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 <?php echo $darkMode ? 'text-white' : ''; ?>" id="bulk-modal-title">
                            Delete Selected Tags
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>" id="bulkDeleteModalContent">
                                Are you sure you want to delete the selected tags? This action cannot be undone.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                <button id="confirmBulkDeleteButton" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                    Delete
                </button>
                <button type="button" id="cancelBulkDeleteButton" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm <?php echo $darkMode ? 'bg-gray-600 text-white border-gray-600 hover:bg-gray-500' : ''; ?>">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Single delete confirmation modal functionality
    function confirmDelete(id, tagName) {
        const modal = document.getElementById('deleteModal');
        const overlay = document.getElementById('modalOverlay');
        const cancelButton = document.getElementById('cancelDeleteButton');
        const confirmButton = document.getElementById('confirmDeleteButton');
        const modalContent = document.getElementById('deleteModalContent');
        
        // Update modal content
        modalContent.innerHTML = `Are you sure you want to delete the tag <strong>"${tagName}"</strong>? This action cannot be undone.`;
        
        // Set the confirm button URL
        confirmButton.href = `index.php?delete=${id}`;
        
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
    
    // Bulk actions functionality
    document.addEventListener('DOMContentLoaded', function() {
        const selectAllCheckbox = document.getElementById('selectAll');
        const tagCheckboxes = document.querySelectorAll('.tag-checkbox');
        const selectedCountElement = document.getElementById('selectedCount');
        const bulkActionSelect = document.getElementById('bulkAction');
        const applyBulkActionButton = document.getElementById('applyBulkAction');
        const bulkActionForm = document.getElementById('bulkActionForm');
        
        // Bulk delete confirmation modal
        const bulkDeleteModal = document.getElementById('bulkDeleteModal');
        const bulkModalOverlay = document.getElementById('bulkModalOverlay');
        const confirmBulkDeleteButton = document.getElementById('confirmBulkDeleteButton');
        const cancelBulkDeleteButton = document.getElementById('cancelBulkDeleteButton');
        
        // Function to update the selected count
        function updateSelectedCount() {
            const checkedCheckboxes = document.querySelectorAll('.tag-checkbox:checked');
            selectedCountElement.textContent = `${checkedCheckboxes.length} items selected`;
        }
        
        // Select all checkbox functionality
        selectAllCheckbox.addEventListener('change', function() {
            tagCheckboxes.forEach(checkbox => {
                checkbox.checked = selectAllCheckbox.checked;
            });
            updateSelectedCount();
        });
        
        // Individual checkbox functionality
        tagCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateSelectedCount();
                
                // Check if all checkboxes are checked
                const allChecked = document.querySelectorAll('.tag-checkbox:checked').length === tagCheckboxes.length;
                selectAllCheckbox.checked = allChecked;
            });
        });
        
        // Apply bulk action button functionality
        applyBulkActionButton.addEventListener('click', function() {
            const selectedAction = bulkActionSelect.value;
            const checkedCheckboxes = document.querySelectorAll('.tag-checkbox:checked');
            
            if (selectedAction === '') {
                alert('Please select an action to perform.');
                return;
            }
            
            if (checkedCheckboxes.length === 0) {
                alert('Please select at least one tag to perform this action.');
                return;
            }
            
            if (selectedAction === 'delete') {
                // Show bulk delete confirmation modal
                bulkDeleteModal.classList.remove('hidden');
                
                // Handle close modal events
                const closeBulkModal = () => {
                    bulkDeleteModal.classList.add('hidden');
                };
                
                bulkModalOverlay.addEventListener('click', closeBulkModal);
                cancelBulkDeleteButton.addEventListener('click', closeBulkModal);
                
                // Submit form on confirm
                confirmBulkDeleteButton.addEventListener('click', function() {
                    bulkActionForm.submit();
                });
            }
        });
        
        // Initialize the selected count
        updateSelectedCount();
    });
</script>

<?php
// Update current date and time for footer
$currentDateTime = '2025-03-19 05:14:19';
$currentUser = 'mahranalsarminy';

// Include admin panel footer
include ROOT_DIR . '/theme/admin/footer.php';
?>