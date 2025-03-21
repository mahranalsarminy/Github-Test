<?php
/**
 * Features Management - Features List
 * 
 * Allows administrators to view and manage website features.
 * 
 * @package WallPix
 * @version 1.0.0
 */

// Define the project root directory
define('ROOT_DIR', dirname(dirname(__DIR__)));

// Include necessary files
require_once ROOT_DIR . '/includes/init.php';

// Set page title
$pageTitle = 'Features Management';

// Current date and time
$currentDateTime = '2025-03-19 04:36:40';
$currentUser = 'mahranalsarminy';

// Handle status toggle action
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $feature_id = (int)$_GET['toggle_status'];
    
    try {
        // Get current status
        $stmt = $pdo->prepare("SELECT is_active FROM features WHERE id = ?");
        $stmt->execute([$feature_id]);
        $currentStatus = $stmt->fetchColumn();
        
        // Toggle status
        $newStatus = $currentStatus ? 0 : 1;
        $stmt = $pdo->prepare("UPDATE features SET is_active = ? WHERE id = ?");
        $stmt->execute([$newStatus, $feature_id]);
        
        // Log activity
        $action = $newStatus ? "activated" : "deactivated";
        $pdo->prepare("
            INSERT INTO activities (user_id, description, created_at) 
            VALUES (:user_id, :description, NOW())
        ")->execute([
            ':user_id' => $_SESSION['user_id'] ?? null,
            ':description' => "Feature #$feature_id $action"
        ]);
        
        $_SESSION['success'] = "Feature status updated successfully.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
    
    // Redirect to maintain clean URL
    header("Location: index.php");
    exit;
}

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $feature_id = (int)$_GET['delete'];
    
    try {
        // Get feature title before delete for logging
        $stmt = $pdo->prepare("SELECT title FROM features WHERE id = ?");
        $stmt->execute([$feature_id]);
        $featureTitle = $stmt->fetchColumn();
        
        // Delete the feature
        $stmt = $pdo->prepare("DELETE FROM features WHERE id = ?");
        $stmt->execute([$feature_id]);
        
        // Log activity
        $pdo->prepare("
            INSERT INTO activities (user_id, description, created_at) 
            VALUES (:user_id, :description, NOW())
        ")->execute([
            ':user_id' => $_SESSION['user_id'] ?? null,
            ':description' => "Deleted feature #$feature_id: $featureTitle"
        ]);
        
        $_SESSION['success'] = "Feature deleted successfully.";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Database error: " . $e->getMessage();
    }
    
    // Redirect to maintain clean URL
    header("Location: index.php");
    exit;
}

// Fetch all features ordered by sort_order and id
try {
    $features = $pdo->query("
        SELECT id, title, description, image_url, icon_class, icon_color, sort_order, is_active, created_at
        FROM features
        ORDER BY sort_order, id
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $_SESSION['error'] = "Error retrieving features: " . $e->getMessage();
    $features = [];
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
                        <i class="fas fa-star mr-2"></i> Features Management
                    </h1>
                    <a href="add.php" class="btn bg-green-500 hover:bg-green-700 text-white">
                        <i class="fas fa-plus mr-2"></i> Add New Feature
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

                <!-- Features Table -->
                <div class="bg-white <?php echo $darkMode ? 'bg-gray-800' : ''; ?> rounded-lg shadow-md overflow-hidden">
                    <?php if (!empty($features)): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-700' : ''; ?>">
                                <thead class="bg-gray-50 <?php echo $darkMode ? 'bg-gray-700 text-gray-300' : 'text-gray-600'; ?>">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                            Feature
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                            Icon
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                            Description Preview
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                            Order
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200 <?php echo $darkMode ? 'bg-gray-800 text-gray-200 divide-gray-700' : ''; ?>">
                                    <?php foreach ($features as $feature): ?>
                                        <tr class="hover:bg-gray-50 <?php echo $darkMode ? 'hover:bg-gray-700' : ''; ?>">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="flex items-center">
                                                    <?php if (!empty($feature['image_url'])): ?>
                                                        <div class="flex-shrink-0 h-10 w-10 mr-3">
                                                            <img class="h-10 w-10 rounded object-cover" src="<?= htmlspecialchars($feature['image_url']) ?>" alt="<?= htmlspecialchars($feature['title']) ?>">
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="text-sm font-medium">
                                                        <a href="edit.php?id=<?= $feature['id'] ?>" class="hover:underline <?php echo $darkMode ? 'text-blue-400' : 'text-blue-600'; ?>">
                                                            <?= htmlspecialchars($feature['title']) ?>
                                                        </a>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if (!empty($feature['icon_class'])): ?>
                                                    <div class="rounded-full w-8 h-8 flex items-center justify-center text-white <?php echo 'bg-' . $feature['icon_color']; ?>">
                                                        <i class="<?= htmlspecialchars($feature['icon_class']) ?>"></i>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-gray-400">No icon</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?> max-w-xs truncate">
                                                    <?= htmlspecialchars(generateContentPreview($feature['description'], 80)) ?>
                                                </div>
                                                <div class="text-xs text-gray-400 <?php echo $darkMode ? 'text-gray-500' : ''; ?> mt-1">
                                                    <?= countWords($feature['description']) ?> words
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                                <?= $feature['sort_order'] ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <?php if ($feature['is_active']): ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 <?php echo $darkMode ? 'bg-green-900 text-green-200' : ''; ?>">
                                                        Active
                                                    </span>
                                                <?php else: ?>
                                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 <?php echo $darkMode ? 'bg-red-900 text-red-200' : ''; ?>">
                                                        Inactive
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex justify-end space-x-2">
                                                    <a href="edit.php?id=<?= $feature['id'] ?>" class="text-blue-600 hover:text-blue-900 <?php echo $darkMode ? 'text-blue-400 hover:text-blue-300' : ''; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="index.php?toggle_status=<?= $feature['id'] ?>" class="<?= $feature['is_active'] ? 'text-red-600 hover:text-red-900' : 'text-green-600 hover:text-green-900' ?> <?php echo $darkMode ? ($feature['is_active'] ? 'text-red-400 hover:text-red-300' : 'text-green-400 hover:text-green-300') : ''; ?>">
                                                        <i class="fas <?= $feature['is_active'] ? 'fa-toggle-on' : 'fa-toggle-off' ?>"></i>
                                                    </a>
                                                    <a href="#" onclick="confirmDelete(<?= $feature['id'] ?>, '<?= htmlspecialchars($feature['title']) ?>')" class="text-red-600 hover:text-red-900 <?php echo $darkMode ? 'text-red-400 hover:text-red-300' : ''; ?>">
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
                            <i class="fas fa-star text-gray-400 text-5xl mb-4"></i>
                            <p class="text-gray-600 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">No features found.</p>
                            <p class="text-gray-500 mt-2 <?php echo $darkMode ? 'text-gray-500' : ''; ?>">
                                                                Add your first feature by clicking the "Add New Feature" button.
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
                            Delete Feature
                        </h3>
                        <div class="mt-2">
                            <p class="text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>" id="deleteModalContent">
                                Are you sure you want to delete this feature? This action cannot be undone.
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
    // Delete confirmation modal functionality
    function confirmDelete(id, title) {
        const modal = document.getElementById('deleteModal');
        const overlay = document.getElementById('modalOverlay');
        const cancelButton = document.getElementById('cancelDeleteButton');
        const confirmButton = document.getElementById('confirmDeleteButton');
        const modalContent = document.getElementById('deleteModalContent');
        
        // Update modal content
        modalContent.innerHTML = `Are you sure you want to delete the feature <strong>"${title}"</strong>? This action cannot be undone.`;
        
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
</script>

<?php
// Update current date and time for footer
$currentDateTime = '2025-03-19 04:42:20';
$currentUser = 'mahranalsarminy';

// Include admin panel footer
include ROOT_DIR . '/theme/admin/footer.php';
?>