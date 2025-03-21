<?php
/**
 * Media Management - Index Page
 * 
 * Lists all media with filtering and pagination options.
 * Includes functionality to toggle status and featured flags.
 * 
 * @package WallPix
 * @version 1.0.0
 */

// Define the project root directory
define('ROOT_DIR', dirname(dirname(__DIR__)));

// Include necessary files
require_once ROOT_DIR . '/includes/init.php';

// Check for toggle actions first
if (isset($_GET['action']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $action = $_GET['action'];
    
    // Verify the ID is valid
    if ($id > 0) {
        try {
            // Toggle featured status
            if ($action === 'toggle_featured') {
                $stmt = $pdo->prepare("UPDATE media SET featured = NOT featured WHERE id = ?");
                $stmt->execute([$id]);
                
                // Log activity
                $pdo->prepare("INSERT INTO activities (description, created_at) VALUES (?, NOW())")
                    ->execute(["Toggled featured status for media #$id"]);
                    
                // Redirect to remove the action from URL
                header("Location: index.php?msg=featured_toggled");
                exit;
            }
            // Toggle active status
            elseif ($action === 'toggle_status') {
                $stmt = $pdo->prepare("UPDATE media SET status = NOT status WHERE id = ?");
                $stmt->execute([$id]);
                
                // Log activity
                $pdo->prepare("INSERT INTO activities (description, created_at) VALUES (?, NOW())")
                    ->execute(["Toggled active status for media #$id"]);
                
                // Redirect to remove the action from URL
                header("Location: index.php?msg=status_toggled");
                exit;
            }
            // Toggle orientation
            elseif ($action === 'toggle_orientation') {
                // Get current orientation
                $stmt = $pdo->prepare("SELECT orientation FROM media WHERE id = ?");
                $stmt->execute([$id]);
                $currentOrientation = $stmt->fetchColumn();
                
                // Set new orientation
                $newOrientation = ($currentOrientation === 'portrait') ? 'landscape' : 'portrait';
                $stmt = $pdo->prepare("UPDATE media SET orientation = ? WHERE id = ?");
                $stmt->execute([$newOrientation, $id]);
                
                // Log activity
                $pdo->prepare("INSERT INTO activities (description, created_at) VALUES (?, NOW())")
                    ->execute(["Changed orientation for media #$id to $newOrientation"]);
                
                // Redirect to remove the action from URL
                header("Location: index.php?msg=orientation_changed");
                exit;
            }
        } catch (PDOException $e) {
            $error_message = "Database error: " . $e->getMessage();
        }
    }
}

// Set page title
$pageTitle = 'Media Management';

// Default page values
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$orientation = isset($_GET['orientation']) ? $_GET['orientation'] : '';
$tag = isset($_GET['tag']) ? (int)$_GET['tag'] : 0;
$featured = isset($_GET['featured']) ? (int)$_GET['featured'] : -1;
$status = isset($_GET['status']) ? (int)$_GET['status'] : -1;

// Build the query and parameters
$params = [];
$where_clauses = [];

// Base query
$query = "SELECT m.*, c.name as category_name 
          FROM media m 
          LEFT JOIN categories c ON m.category_id = c.id";

// Search filter
if (!empty($search)) {
    $where_clauses[] = "(m.title LIKE :search OR m.description LIKE :search)";
    $params[':search'] = "%$search%";
}

// Category filter
if ($category > 0) {
    $where_clauses[] = "m.category_id = :category";
    $params[':category'] = $category;
}

// Orientation filter
if (!empty($orientation) && in_array($orientation, ['portrait', 'landscape'])) {
    $where_clauses[] = "m.orientation = :orientation";
    $params[':orientation'] = $orientation;
}

// Tag filter
if ($tag > 0) {
    $query = "SELECT DISTINCT m.*, c.name as category_name 
              FROM media m 
              LEFT JOIN categories c ON m.category_id = c.id
              JOIN media_tags mt ON m.id = mt.media_id";
    $where_clauses[] = "mt.tag_id = :tag";
    $params[':tag'] = $tag;
}

// Featured filter
if ($featured != -1) {
    $where_clauses[] = "m.featured = :featured";
    $params[':featured'] = $featured;
}

// Status filter
if ($status != -1) {
    $where_clauses[] = "m.status = :status";
    $params[':status'] = $status;
}

// Combine where clauses
if (!empty($where_clauses)) {
    $query .= " WHERE " . implode(" AND ", $where_clauses);
}

// Add order by and limit
$query .= " ORDER BY m.created_at DESC LIMIT :limit OFFSET :offset";
$params[':limit'] = $limit;
$params[':offset'] = $offset;

// Get total count for pagination
$count_query = "SELECT COUNT(*) FROM media m";
if ($tag > 0) {
    $count_query = "SELECT COUNT(DISTINCT m.id) FROM media m 
                    JOIN media_tags mt ON m.id = mt.media_id";
}
if (!empty($where_clauses)) {
    $count_query .= " WHERE " . implode(" AND ", $where_clauses);
}

try {
    // Execute count query
    $count_stmt = $pdo->prepare($count_query);
    foreach ($params as $key => $value) {
        if ($key != ':limit' && $key != ':offset') {
            $count_stmt->bindValue($key, $value);
        }
    }
    $count_stmt->execute();
    $total_records = $count_stmt->fetchColumn();
    $total_pages = ceil($total_records / $limit);

    // Execute main query
    $stmt = $pdo->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $media_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch categories for filter
    $categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

    // Fetch tags for filter
    $tags = $pdo->query("SELECT id, name FROM tags ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

    // Fetch statistics
    $active_count = $pdo->query("SELECT COUNT(*) FROM media WHERE status = 1")->fetchColumn();
    $inactive_count = $pdo->query("SELECT COUNT(*) FROM media WHERE status = 0")->fetchColumn();
    $featured_count = $pdo->query("SELECT COUNT(*) FROM media WHERE featured = 1")->fetchColumn();

    // Log activity
    $activity_desc = "Viewed media list";
    $pdo->prepare("INSERT INTO activities (description, created_at) VALUES (?, NOW())")
        ->execute([$activity_desc]);

} catch (PDOException $e) {
    error_log("Error in media index: " . $e->getMessage());
    $error_message = "Database error: " . $e->getMessage();
}

// Current date & time (from parameter)
$currentDateTime = '2025-03-18 22:27:46';

// Current user's login (from parameter)
$currentUser = 'mahranalsarminy';

// Include admin panel header
include ROOT_DIR . '/theme/admin/header.php';
// Include sidebar
require_once '../../theme/admin/slidbar.php';
?>

<!-- Main Content -->
<div class="content-wrapper min-h-screen bg-gray-100 <?php echo $darkMode ? 'dark-mode' : ''; ?>">
    <div class="px-6 py-8">
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                    <?php echo $lang['media'] ?? 'Media Management'; ?>
                </h1>
                <p class="mt-2 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                    <?php echo $lang['manage_media'] ?? 'Media Management'; ?>
                    <span class="ml-2"><?php echo $currentDateTime; ?></span>
                </p>
            </div>
            <div class="mt-4 md:mt-0">
                    <a href="add.php" class="btn btn-primary">
                        <i class="fas fa-plus mr-2"></i> Add New Media
                    </a>
                </div>
            </div>

            <!-- Notification Messages -->
            <?php if (isset($_GET['msg'])): ?>
                <div class="mb-4">
                    <?php if ($_GET['msg'] === 'featured_toggled'): ?>
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 <?php echo $darkMode ? 'bg-green-900 text-green-300' : ''; ?>" role="alert">
                            <p>Featured status has been successfully updated.</p>
                        </div>
                    <?php elseif ($_GET['msg'] === 'status_toggled'): ?>
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 <?php echo $darkMode ? 'bg-green-900 text-green-300' : ''; ?>" role="alert">
                            <p>Media status has been successfully updated.</p>
                        </div>
                    <?php elseif ($_GET['msg'] === 'orientation_changed'): ?>
                        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 <?php echo $darkMode ? 'bg-green-900 text-green-300' : ''; ?>" role="alert">
                            <p>Media orientation has been successfully changed.</p>
                        </div>
                    <?php elseif ($_GET['msg'] === 'deleted'): ?>
                        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 <?php echo $darkMode ? 'bg-red-900 text-red-300' : ''; ?>" role="alert">
                            <p>Media has been successfully deleted.</p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (isset($error_message)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 <?php echo $darkMode ? 'bg-red-900 text-red-300' : ''; ?>" role="alert">
                    <p><?= htmlspecialchars($error_message) ?></p>
                </div>
            <?php endif; ?>

            <!-- Statistics Summary -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="bg-white <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?> rounded-lg shadow-md p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-blue-500 rounded-full p-3">
                            <i class="fas fa-images text-white text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold">Total Media</h3>
                            <p class="text-3xl font-bold text-blue-600 <?php echo $darkMode ? 'text-blue-400' : ''; ?>"><?= $total_records ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?> rounded-lg shadow-md p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-green-500 rounded-full p-3">
                            <i class="fas fa-check-circle text-white text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold">Active</h3>
                            <p class="text-3xl font-bold text-green-600 <?php echo $darkMode ? 'text-green-400' : ''; ?>"><?= $active_count ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?> rounded-lg shadow-md p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-red-500 rounded-full p-3">
                            <i class="fas fa-times-circle text-white text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold">Inactive</h3>
                            <p class="text-3xl font-bold text-red-600 <?php echo $darkMode ? 'text-red-400' : ''; ?>"><?= $inactive_count ?></p>
                        </div>
                    </div>
                </div>
                <div class="bg-white <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?> rounded-lg shadow-md p-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 bg-yellow-500 rounded-full p-3">
                            <i class="fas fa-star text-white text-xl"></i>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg font-semibold">Featured</h3>
                            <p class="text-3xl font-bold text-yellow-600 <?php echo $darkMode ? 'text-yellow-400' : ''; ?>"><?= $featured_count ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?> rounded-lg shadow-md p-4 mb-6">
                <h2 class="text-xl font-semibold mb-4">Filters</h2>
                <form action="" method="get" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="search" class="block text-sm font-medium mb-1">Search</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input type="text" id="search" name="search" value="<?= htmlspecialchars($search) ?>" 
                                class="w-full pl-10 p-2 border border-gray-300 rounded <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                placeholder="Search title or description...">
                        </div>
                    </div>

                    <div>
                        <label for="category" class="block text-sm font-medium mb-1">Category</label>
                        <select id="category" name="category" class="w-full p-2 border border-gray-300 rounded <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                            <option value="0">All Categories</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="orientation" class="block text-sm font-medium mb-1">Orientation</label>
                        <select id="orientation" name="orientation" class="w-full p-2 border border-gray-300 rounded <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                            <option value="">All Orientations</option>
                            <option value="portrait" <?= $orientation == 'portrait' ? 'selected' : '' ?>>Portrait</option>
                            <option value="landscape" <?= $orientation == 'landscape' ? 'selected' : '' ?>>Landscape</option>
                        </select>
                    </div>

                    <div>
                        <label for="tag" class="block text-sm font-medium mb-1">Tag</label>
                        <select id="tag" name="tag" class="w-full p-2 border border-gray-300 rounded <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                            <option value="0">All Tags</option>
                            <?php foreach ($tags as $t): ?>
                                <option value="<?= $t['id'] ?>" <?= $tag == $t['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="featured" class="block text-sm font-medium mb-1">Featured</label>
                        <select id="featured" name="featured" class="w-full p-2 border border-gray-300 rounded <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                            <option value="-1">All</option>
                            <option value="1" <?= $featured === 1 ? 'selected' : '' ?>>Featured</option>
                            <option value="0" <?= $featured === 0 ? 'selected' : '' ?>>Not Featured</option>
                        </select>
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-medium mb-1">Status</label>
                        <select id="status" name="status" class="w-full p-2 border border-gray-300 rounded <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                            <option value="-1">All</option>
                            <option value="1" <?= $status === 1 ? 'selected' : '' ?>>Active</option>
                            <option value="0" <?= $status === 0 ? 'selected' : '' ?>>Inactive</option>
                        </select>
                    </div>

                    <div class="md:col-span-3 flex justify-end">
                        <button type="submit" class="btn btn-primary mr-2">
                            <i class="fas fa-filter mr-2"></i> Apply Filters
                        </button>
                        <a href="index.php" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                            <i class="fas fa-sync-alt mr-2"></i> Reset
                        </a>
                    </div>
                </form>
            </div>

            <!-- Media List -->
            <div class="bg-white <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?> rounded-lg shadow-md overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-700' : ''; ?>">
                        <thead class="bg-gray-50 <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 <?php echo $darkMode ? 'text-gray-300' : ''; ?> uppercase tracking-wider">ID</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 <?php echo $darkMode ? 'text-gray-300' : ''; ?> uppercase tracking-wider">Thumbnail</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 <?php echo $darkMode ? 'text-gray-300' : ''; ?> uppercase tracking-wider">Title</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 <?php echo $darkMode ? 'text-gray-300' : ''; ?> uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 <?php echo $darkMode ? 'text-gray-300' : ''; ?> uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 <?php echo $darkMode ? 'text-gray-300' : ''; ?> uppercase tracking-wider">Featured</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 <?php echo $darkMode ? 'text-gray-300' : ''; ?> uppercase tracking-wider">Orientation</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 <?php echo $darkMode ? 'text-gray-300' : ''; ?> uppercase tracking-wider">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 <?php echo $darkMode ? 'text-gray-300' : ''; ?> uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-700' : ''; ?>">
                            <?php if (empty($media_items)): ?>
                                <tr>
                                    <td colspan="9" class="px-6 py-4 text-center text-gray-500 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">
                                        <div class="flex flex-col items-center justify-center py-8">
                                            <i class="fas fa-photo-video text-5xl mb-4 text-gray-400"></i>
                                            <p>No media found matching your criteria</p>
                                            <a href="index.php" class="mt-4 text-blue-500 hover:underline">
                                                <i class="fas fa-sync-alt mr-1"></i> Reset filters
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($media_items as $item): ?>
                                    <tr class="hover:bg-gray-50 <?php echo $darkMode ? 'hover:bg-gray-700' : ''; ?>">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 <?php echo $darkMode ? 'text-gray-200' : ''; ?>">
                                            <?= htmlspecialchars($item['id']) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if (!empty($item['thumbnail_url'])): ?>
                                                <img src="<?= htmlspecialchars($item['thumbnail_url']) ?>" alt="Thumbnail" class="w-16 h-16 object-cover rounded">
                                            <?php elseif (!empty($item['external_url'])): ?>
                                                                                                <img src="<?= htmlspecialchars($item['external_url']) ?>" alt="Thumbnail" class="w-16 h-16 object-cover rounded">
                                            <?php else: ?>
                                                <div class="w-16 h-16 bg-gray-200 <?php echo $darkMode ? 'bg-gray-600' : ''; ?> rounded flex items-center justify-center">
                                                    <i class="fas fa-image text-gray-400"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 <?php echo $darkMode ? 'text-gray-200' : ''; ?>">
                                            <a href="view.php?id=<?= $item['id'] ?>" class="hover:underline">
                                                <?= htmlspecialchars($item['title']) ?>
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 <?php echo $darkMode ? 'text-gray-200' : ''; ?>">
                                            <?= htmlspecialchars($item['category_name'] ?? 'None') ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <a href="index.php?action=toggle_status&id=<?= $item['id'] ?>" 
                                               class="status-toggle cursor-pointer" 
                                               data-id="<?= $item['id'] ?>"
                                               title="Click to toggle status">
                                                <?php if ($item['status']): ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 <?php echo $darkMode ? 'bg-green-900 text-green-200' : ''; ?>">
                                                        <i class="fas fa-check-circle mr-1"></i> Active
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 <?php echo $darkMode ? 'bg-red-900 text-red-200' : ''; ?>">
                                                        <i class="fas fa-times-circle mr-1"></i> Inactive
                                                    </span>
                                                <?php endif; ?>
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <a href="index.php?action=toggle_featured&id=<?= $item['id'] ?>" 
                                               class="featured-toggle cursor-pointer" 
                                               data-id="<?= $item['id'] ?>"
                                               title="Click to toggle featured">
                                                <?php if ($item['featured']): ?>
                                                    <span class="text-yellow-500 <?php echo $darkMode ? 'text-yellow-400' : ''; ?>">
                                                        <i class="fas fa-star"></i> Featured
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-gray-400">
                                                        <i class="far fa-star"></i> Not Featured
                                                    </span>
                                                <?php endif; ?>
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <a href="index.php?action=toggle_orientation&id=<?= $item['id'] ?>" 
                                               class="orientation-toggle cursor-pointer" 
                                               data-id="<?= $item['id'] ?>"
                                               title="Click to toggle orientation">
                                                <?php if ($item['orientation'] == 'portrait'): ?>
                                                    <span class="text-blue-500 <?php echo $darkMode ? 'text-blue-400' : ''; ?>">
                                                        <i class="fas fa-mobile-alt mr-1"></i> Portrait
                                                    </span>
                                                <?php elseif ($item['orientation'] == 'landscape'): ?>
                                                    <span class="text-green-500 <?php echo $darkMode ? 'text-green-400' : ''; ?>">
                                                        <i class="fas fa-laptop mr-1"></i> Landscape
                                                    </span>
                                                <?php else: ?>
                                                    <span>-</span>
                                                <?php endif; ?>
                                            </a>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 <?php echo $darkMode ? 'text-gray-200' : ''; ?>">
                                            <?= htmlspecialchars(date('Y-m-d', strtotime($item['created_at']))) ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <a href="/../media.php?id=<?= $item['id'] ?>" class="text-blue-500 hover:text-blue-700" title="View">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                                <a href="edit.php?id=<?= $item['id'] ?>" class="text-indigo-500 hover:text-indigo-700" title="Edit">
                                                    <i class="fas fa-edit"></i>
                                                </a>
                                                <button onclick="confirmDelete(<?= $item['id'] ?>)" class="text-red-500 hover:text-red-700" title="Delete">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="flex justify-center mt-6">
                    <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= ($page - 1) ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>&category=<?= $category ?>&orientation=<?= urlencode($orientation) ?>&tag=<?= $tag ?>&featured=<?= $featured ?>&status=<?= $status ?>" 
                               class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white <?php echo $darkMode ? 'bg-gray-800 border-gray-700 text-gray-300' : 'text-gray-500'; ?> hover:bg-gray-50 <?php echo $darkMode ? 'hover:bg-gray-700' : ''; ?>">
                                <i class="fas fa-chevron-left text-sm"></i>
                                <span class="sr-only">Previous</span>
                            </a>
                        <?php endif; ?>

                        <?php
                        $start_page = max(1, $page - 2);
                        $end_page = min($total_pages, $start_page + 4);
                        if ($end_page - $start_page < 4) {
                            $start_page = max(1, $end_page - 4);
                        }
                        for ($i = $start_page; $i <= $end_page; $i++):
                        ?>
                            <a href="?page=<?= $i ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>&category=<?= $category ?>&orientation=<?= urlencode($orientation) ?>&tag=<?= $tag ?>&featured=<?= $featured ?>&status=<?= $status ?>" 
                               class="relative inline-flex items-center px-4 py-2 border border-gray-300 <?php echo $i === $page ? ($darkMode ? 'bg-gray-700 text-white border-blue-500' : 'bg-blue-50 border-blue-500 text-blue-600') : ($darkMode ? 'bg-gray-800 border-gray-700 text-gray-300' : 'bg-white text-gray-700'); ?> hover:bg-gray-50 <?php echo $darkMode ? 'hover:bg-gray-700' : ''; ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= ($page + 1) ?>&limit=<?= $limit ?>&search=<?= urlencode($search) ?>&category=<?= $category ?>&orientation=<?= urlencode($orientation) ?>&tag=<?= $tag ?>&featured=<?= $featured ?>&status=<?= $status ?>" 
                               class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white <?php echo $darkMode ? 'bg-gray-800 border-gray-700 text-gray-300' : 'text-gray-500'; ?> hover:bg-gray-50 <?php echo $darkMode ? 'hover:bg-gray-700' : ''; ?>">
                                <i class="fas fa-chevron-right text-sm"></i>
                                <span class="sr-only">Next</span>
                            </a>
                        <?php endif; ?>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="delete-modal" class="fixed z-10 inset-0 overflow-y-auto hidden">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center">
        <div class="fixed inset-0 transition-opacity" aria-hidden="true">
            <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
        </div>
        <div class="inline-block align-bottom bg-white <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?> rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <div class="bg-white <?php echo $darkMode ? 'bg-gray-800' : ''; ?> px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="sm:flex sm:items-start">
                    <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 <?php echo $darkMode ? 'bg-red-900' : ''; ?> sm:mx-0 sm:h-10 sm:w-10">
                        <i class="fas fa-exclamation-triangle text-red-600 <?php echo $darkMode ? 'text-red-400' : ''; ?>"></i>
                    </div>
                    <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 <?php echo $darkMode ? 'text-white' : ''; ?>" id="modal-title">
                            Delete Media
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                                Are you sure you want to delete this media? This action cannot be undone.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-gray-50 <?php echo $darkMode ? 'bg-gray-700' : ''; ?> px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                <form id="delete-form" method="post" action="delete.php">
                    <input type="hidden" id="delete-id" name="id" value="">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Delete
                    </button>
                </form>
                <button type="button" onclick="closeDeleteModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white <?php echo $darkMode ? 'bg-gray-600 text-white border-gray-600' : 'text-gray-700'; ?> hover:bg-gray-50 <?php echo $darkMode ? 'hover:bg-gray-500' : ''; ?> focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                    Cancel
                </button>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for the page -->
<script>
    // Delete confirmation modal functions
    function confirmDelete(id) {
        document.getElementById('delete-id').value = id;
        document.getElementById('delete-modal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('delete-modal').classList.add('hidden');
    }

    // AJAX toggle functionality without page reload
    document.addEventListener('DOMContentLoaded', function() {
        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('delete-modal');
            if (event.target === modal) {
                closeDeleteModal();
            }
        }

        // Status toggle with AJAX
        document.querySelectorAll('.status-toggle').forEach(function(element) {
            element.addEventListener('click', function(e) {
                e.preventDefault();
                const mediaId = this.getAttribute('data-id');
                const toggleUrl = `index.php?action=toggle_status&id=${mediaId}`;
                
                // Show loading indicator
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                
                // Make the AJAX request
                fetch(toggleUrl, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (response.ok) {
                        return response.text();
                    }
                    throw new Error('Network response was not ok.');
                })
                .then(() => {
                    // Reload the page to see changes
                    window.location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to toggle status. Please try again.');
                    // Reload the page to reset UI
                    window.location.reload();
                });
            });
        });

        // Featured toggle with AJAX
        document.querySelectorAll('.featured-toggle').forEach(function(element) {
            element.addEventListener('click', function(e) {
                e.preventDefault();
                const mediaId = this.getAttribute('data-id');
                const toggleUrl = `index.php?action=toggle_featured&id=${mediaId}`;
                
                // Show loading indicator
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                
                // Make the AJAX request
                fetch(toggleUrl, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (response.ok) {
                        return response.text();
                    }
                    throw new Error('Network response was not ok.');
                })
                .then(() => {
                    // Reload the page to see changes
                    window.location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to toggle featured status. Please try again.');
                    // Reload the page to reset UI
                    window.location.reload();
                });
            });
        });

        // Orientation toggle with AJAX
        document.querySelectorAll('.orientation-toggle').forEach(function(element) {
            element.addEventListener('click', function(e) {
                e.preventDefault();
                const mediaId = this.getAttribute('data-id');
                const toggleUrl = `index.php?action=toggle_orientation&id=${mediaId}`;
                
                // Show loading indicator
                this.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
                
                // Make the AJAX request
                fetch(toggleUrl, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => {
                    if (response.ok) {
                        return response.text();
                    }
                    throw new Error('Network response was not ok.');
                })
                .then(() => {
                    // Reload the page to see changes
                    window.location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to toggle orientation. Please try again.');
                    // Reload the page to reset UI
                    window.location.reload();
                });
            });
        });
    });
</script>

<?php
// Include admin panel footer
include ROOT_DIR . '/theme/admin/footer.php';
?>