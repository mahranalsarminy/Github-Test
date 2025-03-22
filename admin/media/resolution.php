<?php
/**
 * Resolution Management
 *
 * @package WallPix
 * @version 1.0.0
 */

// Include the Composer autoload file
require_once '../../vendor/autoload.php';

// Load environment variables from the .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Set page title
$pageTitle = "Resolution Management";

// Include header and sidebar
require_once '../../theme/admin/header.php';
require_once '../../theme/admin/slidbar.php';

// Initialize message variables
$successMessage = '';
$errorMessage = '';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check action type
    if (isset($_POST['action'])) {
        // Connect to database
        $db = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
        if ($db->connect_error) {
            die("Connection failed: " . $db->connect_error);
        }

        switch ($_POST['action']) {
            case 'add':
                // Add new resolution
                if (isset($_POST['resolution']) && !empty($_POST['resolution'])) {
                    $resolution = trim($db->real_escape_string($_POST['resolution']));
                    
                    // Check if resolution already exists
                    $checkQuery = "SELECT id FROM resolutions WHERE resolution = '$resolution'";
                    $checkResult = $db->query($checkQuery);
                    
                    if ($checkResult && $checkResult->num_rows > 0) {
                        $errorMessage = "Resolution '$resolution' already exists!";
                    } else {
                        $query = "INSERT INTO resolutions (resolution) VALUES ('$resolution')";
                        
                        if ($db->query($query)) {
                            $successMessage = "Resolution added successfully!";
                        } else {
                            $errorMessage = "Failed to add resolution: " . $db->error;
                        }
                    }
                } else {
                    $errorMessage = "Resolution cannot be empty!";
                }
                break;

            case 'edit':
                // Edit existing resolution
                if (isset($_POST['id']) && is_numeric($_POST['id']) && isset($_POST['resolution']) && !empty($_POST['resolution'])) {
                    $id = (int) $_POST['id'];
                    $resolution = trim($db->real_escape_string($_POST['resolution']));
                    
                    // Check if resolution already exists for another ID
                    $checkQuery = "SELECT id FROM resolutions WHERE resolution = '$resolution' AND id != $id";
                    $checkResult = $db->query($checkQuery);
                    
                    if ($checkResult && $checkResult->num_rows > 0) {
                        $errorMessage = "Resolution '$resolution' already exists!";
                    } else {
                        $query = "UPDATE resolutions SET resolution = '$resolution' WHERE id = $id";
                        
                        if ($db->query($query)) {
                            $successMessage = "Resolution updated successfully!";
                        } else {
                            $errorMessage = "Failed to update resolution: " . $db->error;
                        }
                    }
                } else {
                    $errorMessage = "Invalid resolution data!";
                }
                break;

            case 'delete':
                // Delete resolution
                if (isset($_POST['id']) && is_numeric($_POST['id'])) {
                    $id = (int) $_POST['id'];
                    
                    $query = "DELETE FROM resolutions WHERE id = $id";
                    
                    if ($db->query($query)) {
                        $successMessage = "Resolution deleted successfully!";
                    } else {
                        $errorMessage = "Failed to delete resolution: " . $db->error;
                    }
                } else {
                    $errorMessage = "Invalid resolution ID!";
                }
                break;
        }

        $db->close();
    }
}

// Fetch all resolutions
$db = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$query = "SELECT * FROM resolutions ORDER BY id ASC";
$result = $db->query($query);
$resolutions = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $resolutions[] = $row;
    }
    $result->free();
}

$db->close();

// Get current date and time for display
$currentDateTime = date('Y-m-d H:i:s');
?>

<div class="content-wrapper p-4 sm:ml-64">
    <div class="p-4 border-2 border-gray-200 rounded-lg dark:border-gray-700 mt-14">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-white">
                <i class="fas fa-arrows-alt-h mr-2"></i> Resolution Management
            </h1>
            <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                <i class="fas fa-clock mr-1"></i>
                <span><?php echo htmlspecialchars($currentDateTime); ?></span>
            </div>
        </div>

        <?php if ($successMessage): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                <p><?php echo htmlspecialchars($successMessage); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                <p><?php echo htmlspecialchars($errorMessage); ?></p>
            </div>
        <?php endif; ?>

        <!-- Add Resolution Form -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Add New Resolution</h2>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="space-y-4">
                <input type="hidden" name="action" value="add">
                
                <div class="flex flex-col md:flex-row items-center space-y-2 md:space-y-0 md:space-x-4">
                    <div class="w-full md:w-1/2">
                        <label for="resolution" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Resolution (e.g. 1920x1080)</label>
                        <input type="text" id="resolution" name="resolution" required 
                               class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                               placeholder="Enter resolution (e.g. 1920x1080)">
                    </div>
                    
                    <div class="self-end">
                        <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            <i class="fas fa-plus mr-2"></i>Add Resolution
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Resolution List -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Resolution List</h2>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Resolution</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Created At</th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                        <?php if (empty($resolutions)): ?>
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No resolutions found</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($resolutions as $resolution): ?>
                                <tr id="resolution-row-<?php echo $resolution['id']; ?>" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($resolution['id']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white resolution-value"><?php echo htmlspecialchars($resolution['resolution']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($resolution['created_at']); ?></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <button type="button" onclick="editResolution(<?php echo $resolution['id']; ?>, '<?php echo addslashes($resolution['resolution']); ?>')" 
                                                class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                        <button type="button" onclick="confirmDelete(<?php echo $resolution['id']; ?>, '<?php echo addslashes($resolution['resolution']); ?>')" 
                                                class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                            <i class="fas fa-trash-alt"></i> Delete
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Edit Resolution Modal -->
<div id="editModal" class="fixed z-50 inset-0 hidden overflow-y-auto" aria-modal="true" role="dialog">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" id="modalOverlay"></div>
        
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="hidden" name="action" value="edit">
                <input type="hidden" name="id" id="editResolutionId">
                
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-edit text-blue-600 dark:text-blue-300"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                Edit Resolution
                            </h3>
                            <div class="mt-2">
                                <div class="mb-4">
                                    <label for="editResolution" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Resolution</label>
                                    <input type="text" name="resolution" id="editResolution" required 
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Save Changes
                    </button>
                    <button type="button" onclick="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed z-50 inset-0 hidden overflow-y-auto" aria-modal="true" role="dialog">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" id="deleteModalOverlay"></div>
        
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" id="deleteResolutionId">
                
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-300"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                Delete Resolution
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Are you sure you want to delete this resolution? This action cannot be undone.
                                </p>
                                <p class="text-sm font-semibold mt-2 text-gray-800 dark:text-gray-200" id="deleteResolutionName">
                                    Resolution name will appear here
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Delete
                    </button>
                    <button type="button" onclick="closeDeleteModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript for modals and interactions -->
<script>
    function editResolution(id, resolution) {
        document.getElementById('editResolutionId').value = id;
        document.getElementById('editResolution').value = resolution;
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeModal() {
        document.getElementById('editModal').classList.add('hidden');
    }

    function confirmDelete(id, resolution) {
        document.getElementById('deleteResolutionId').value = id;
        document.getElementById('deleteResolutionName').textContent = resolution;
        document.getElementById('deleteModal').classList.remove('hidden');
    }

    function closeDeleteModal() {
        document.getElementById('deleteModal').classList.add('hidden');
    }

    // Close modal when clicking on the overlay
    document.getElementById('modalOverlay').addEventListener('click', closeModal);
    document.getElementById('deleteModalOverlay').addEventListener('click', closeDeleteModal);

    // Keyboard event
     document.getElementById('deleteModalOverlay').addEventListener('click', closeDeleteModal);
 
     // Keyboard event listener for Escape key
     document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeModal();
             closeDeleteModal();
         }
    });
</script>
 
<?php require_once '../../theme/admin/footer.php'; ?>
