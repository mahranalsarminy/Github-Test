<?php
/**
 * Media Licenses Management
 * 
 * @package WallPix
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'Media Licenses - WallPix Admin';

// Include header
require_once '../../theme/admin/header.php';

// Current date and time in UTC
$currentDateTime = date('Y-m-d H:i:s');

// Initialize variables
$successMessage = '';
$errorMessage = '';
$editId = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$editMode = $editId > 0;
$editData = [];

// Form data
$name = '';
$description = '';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    // Validate form data
    $errors = [];
    
    if (empty($name)) {
        $errors[] = 'License name is required';
    } elseif (strlen($name) > 100) {
        $errors[] = 'License name must be 100 characters or less';
    }
    
    if (strlen($description) > 1000) {
        $errors[] = 'Description must be 1000 characters or less';
    }
    
    // Process based on action type
    if (empty($errors)) {
        try {
            // Add new license
            if (isset($_POST['add_license'])) {
                // Check if license with the same name already exists
                $checkStmt = $pdo->prepare("SELECT id FROM media_licenses WHERE name = ?");
                $checkStmt->execute([$name]);
                if ($checkStmt->rowCount() > 0) {
                    $errorMessage = 'A license with this name already exists';
                } else {
                    $stmt = $pdo->prepare("INSERT INTO media_licenses (name, description, created_at) VALUES (?, ?, NOW())");
                    $stmt->execute([$name, $description]);
                    
                    $successMessage = 'License added successfully';
                    
                    // Log activity
                    $activityQuery = $pdo->prepare(
                        "INSERT INTO activities (user_id, description, created_at)
                        VALUES (:user_id, :description, NOW())"
                    );
                    
                    $activityQuery->execute([
                        'user_id' => $_SESSION['user_id'] ?? null,
                        'description' => "Added new license: " . $name
                    ]);
                    
                    // Reset form fields
                    $name = '';
                    $description = '';
                }
            }
            // Update existing license
            elseif (isset($_POST['update_license'])) {
                $licenseId = intval($_POST['license_id']);
                
                // Check if license exists
                $checkStmt = $pdo->prepare("SELECT id FROM media_licenses WHERE id = ?");
                $checkStmt->execute([$licenseId]);
                if ($checkStmt->rowCount() === 0) {
                    $errorMessage = 'License not found';
                } else {
                    // Check if name is already taken by another license
                    $checkNameStmt = $pdo->prepare("SELECT id FROM media_licenses WHERE name = ? AND id != ?");
                    $checkNameStmt->execute([$name, $licenseId]);
                    if ($checkNameStmt->rowCount() > 0) {
                        $errorMessage = 'Another license with this name already exists';
                    } else {
                        $stmt = $pdo->prepare("UPDATE media_licenses SET name = ?, description = ? WHERE id = ?");
                        $stmt->execute([$name, $description, $licenseId]);
                        
                        $successMessage = 'License updated successfully';
                        
                        // Log activity
                        $activityQuery = $pdo->prepare(
                            "INSERT INTO activities (user_id, description, created_at)
                            VALUES (:user_id, :description, NOW())"
                        );
                        
                        $activityQuery->execute([
                            'user_id' => $_SESSION['user_id'] ?? null,
                            'description' => "Updated license: " . $name
                        ]);
                        
                        // Reset edit mode
                        $editMode = false;
                        $editId = 0;
                        $name = '';
                        $description = '';
                    }
                }
            }
        } catch (PDOException $e) {
            $errorMessage = 'Database error: ' . $e->getMessage();
        }
    } else {
        $errorMessage = implode('<br>', $errors);
    }
}

// Handle delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $deleteId = intval($_GET['delete']);
    
    // Check if license is in use before deleting
    try {
        // Note: Adjust this check based on how licenses are used in your system
        $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM media WHERE license = ? OR license_id = ?");
        $checkStmt->execute([$deleteId, $deleteId]);
        $licenseInUse = $checkStmt->fetchColumn() > 0;
        
        if ($licenseInUse) {
            $errorMessage = 'Cannot delete: This license is currently in use by one or more media items';
        } else {
            // Get license name for activity log
            $nameStmt = $pdo->prepare("SELECT name FROM media_licenses WHERE id = ?");
            $nameStmt->execute([$deleteId]);
            $licenseName = $nameStmt->fetchColumn();
            
            // Delete the license
            $stmt = $pdo->prepare("DELETE FROM media_licenses WHERE id = ?");
            $stmt->execute([$deleteId]);
            
            $successMessage = 'License deleted successfully';
            
            // Log activity
            $activityQuery = $pdo->prepare(
                "INSERT INTO activities (user_id, description, created_at)
                VALUES (:user_id, :description, NOW())"
            );
            
            $activityQuery->execute([
                'user_id' => $_SESSION['user_id'] ?? null,
                'description' => "Deleted license: " . $licenseName
            ]);
        }
    } catch (PDOException $e) {
        $errorMessage = 'Error deleting license: ' . $e->getMessage();
    }
}

// Load license data for editing
if ($editMode) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM media_licenses WHERE id = ?");
        $stmt->execute([$editId]);
        $editData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($editData) {
            $name = $editData['name'];
            $description = $editData['description'];
        } else {
            $errorMessage = 'License not found';
            $editMode = false;
            $editId = 0;
        }
    } catch (PDOException $e) {
        $errorMessage = 'Error loading license data: ' . $e->getMessage();
        $editMode = false;
        $editId = 0;
    }
}

// Get all licenses for listing
try {
    $stmt = $pdo->query("SELECT * FROM media_licenses ORDER BY name ASC");
    $licenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errorMessage = 'Error loading licenses: ' . $e->getMessage();
    $licenses = [];
}

// Include sidebar
require_once '../../theme/admin/slidbar.php';
?>

<!-- Main content -->
<div class="content-wrapper p-4 sm:ml-64">
    <div class="p-4 mt-14">
        <!-- Page Header -->
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold <?php echo isset($darkMode) && $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                    Media Licenses
                </h1>
                <p class="mt-2 text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                    Manage license types for media content
                </p>
            </div>
        </div>
        
        <?php if ($successMessage): ?>
            <div class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg dark:bg-green-200 dark:text-green-800" role="alert">
                <span class="font-medium"><i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($successMessage); ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($errorMessage): ?>
            <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg dark:bg-red-200 dark:text-red-800" role="alert">
                <span class="font-medium"><i class="fas fa-exclamation-circle mr-2"></i> <?php echo $errorMessage; ?></span>
            </div>
        <?php endif; ?>
        
        <div class="flex flex-col md:flex-row gap-6">
            <!-- License Form -->
            <div class="md:w-1/3 mb-6">
                <div class="bg-white rounded-lg shadow-md p-6 <?php echo isset($darkMode) && $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
                    <h2 class="text-xl font-semibold mb-4 <?php echo isset($darkMode) && $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                        <?php echo $editMode ? 'Edit License' : 'Add New License'; ?>
                    </h2>
                    
                    <form method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                        <?php if ($editMode): ?>
                            <input type="hidden" name="license_id" value="<?php echo $editId; ?>">
                        <?php endif; ?>
                        
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium mb-2 <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                License Name <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($name); ?>" required
                                class="w-full p-2.5 text-sm rounded-lg border <?php echo isset($darkMode) && $darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-gray-50 border-gray-300 text-gray-900'; ?> focus:ring-blue-500 focus:border-blue-500">
                            <p class="mt-1 text-xs <?php echo isset($darkMode) && $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                License name (max 100 characters)
                            </p>
                        </div>
                        
                        <div class="mb-4">
                            <label for="description" class="block text-sm font-medium mb-2 <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                Description
                            </label>
                            <textarea id="description" name="description" rows="4"
                                class="w-full p-2.5 text-sm rounded-lg border <?php echo isset($darkMode) && $darkMode ? 'bg-gray-700 border-gray-600 text-white' : 'bg-gray-50 border-gray-300 text-gray-900'; ?> focus:ring-blue-500 focus:border-blue-500"
                            ><?php echo htmlspecialchars($description); ?></textarea>
                            <p class="mt-1 text-xs <?php echo isset($darkMode) && $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                Detailed explanation of the license terms
                            </p>
                        </div>
                        
                        <div class="flex justify-between mt-6">
                            <?php if ($editMode): ?>
                                <a href="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                                    Cancel
                                </a>
                                <button type="submit" name="update_license" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                    Update License
                                </button>
                            <?php else: ?>
                                <button type="reset" class="px-4 py-2 bg-gray-200 text-gray-700 rounded-md hover:bg-gray-300">
                                    Clear
                                </button>
                                <button type="submit" name="add_license" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
                                    Add License
                                </button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- License List -->
            <div class="md:w-2/3">
                <div class="bg-white rounded-lg shadow-md p-6 <?php echo isset($darkMode) && $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
                    <h2 class="text-xl font-semibold mb-4 <?php echo isset($darkMode) && $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                        Existing Licenses
                    </h2>
                    
                    <?php if (count($licenses) > 0): ?>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 <?php echo isset($darkMode) && $darkMode ? 'divide-gray-700' : ''; ?>">
                                <thead class="<?php echo isset($darkMode) && $darkMode ? 'bg-gray-700' : 'bg-gray-50'; ?>">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                            License Name
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                            Description
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                            Created
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-right text-xs font-medium <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                            Actions
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="<?php echo isset($darkMode) && $darkMode ? 'bg-gray-800 divide-gray-700' : 'bg-white divide-gray-200'; ?>">
                                    <?php foreach ($licenses as $license): ?>
                                        <tr class="<?php echo $editId === $license['id'] ? ($darkMode ? 'bg-blue-900 bg-opacity-20' : 'bg-blue-50') : ''; ?>">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-medium <?php echo isset($darkMode) && $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                                    <?php echo htmlspecialchars($license['name']); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">
                                                    <?php 
                                                    $desc = htmlspecialchars($license['description'] ?: 'No description');
                                                    echo strlen($desc) > 100 ? substr($desc, 0, 100) . '...' : $desc;
                                                    ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">
                                                    <?php echo date('Y-m-d', strtotime($license['created_at'])); ?>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <a href="?edit=<?php echo $license['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3 <?php echo isset($darkMode) && $darkMode ? 'text-blue-400 hover:text-blue-300' : ''; ?>">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <a href="#" onclick="confirmDelete(<?php echo $license['id']; ?>, '<?php echo addslashes($license['name']); ?>')" class="text-red-600 hover:text-red-900 <?php echo isset($darkMode) && $darkMode ? 'text-red-400 hover:text-red-300' : ''; ?>">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">
                            <i class="fas fa-info-circle text-3xl mb-3"></i>
                            <p>No licenses have been created yet.</p>
                            <p class="text-sm mt-1">Create your first license using the form on the left.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Last Update Info -->
        <div class="mt-6 text-right text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
            Last Updated: <?php echo $currentDateTime; ?>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="fixed inset-0 z-50 hidden flex items-center justify-center">
    <div class="fixed inset-0 bg-black bg-opacity-50"></div>
    
    <div class="relative bg-white rounded-lg shadow-lg p-6 w-full max-w-md mx-4 <?php echo isset($darkMode) && $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
        <button type="button" onclick="closeModal()" class="absolute top-3 right-3 text-gray-400 hover:text-gray-900 rounded-lg p-1.5 inline-flex items-center justify-center h-8 w-8 <?php echo isset($darkMode) && $darkMode ? 'hover:text-white' : ''; ?>">
            <i class="fas fa-times"></i>
        </button>
        
        <div class="text-center">
            <i class="fas fa-exclamation-triangle text-4xl text-yellow-400 mb-4"></i>
            <h3 class="text-lg font-semibold mb-2 <?php echo isset($darkMode) && $darkMode ? 'text-white' : 'text-gray-800'; ?>">Confirm Deletion</h3>
            <p class="mb-4 <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-600'; ?>" id="deleteMessage">
                Are you sure you want to delete this license?
            </p>
            
            <div class="flex justify-center gap-4">
                <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-md hover:bg-gray-300 focus:outline-none focus:ring-2 focus:ring-gray-300 <?php echo isset($darkMode) && $darkMode ? 'bg-gray-700 text-gray-300 hover:bg-gray-600 focus:ring-gray-500' : ''; ?>">
                    Cancel
                </button>
                <a href="#" id="confirmDeleteBtn" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-red-500">
                    Delete
                </a>
            </div>
        </div>
    </div>
</div>

<script>
// Delete confirmation
function confirmDelete(id, name) {
    const modal = document.getElementById('deleteModal');
    const deleteMessage = document.getElementById('deleteMessage');
    const confirmBtn = document.getElementById('confirmDeleteBtn');
    
    deleteMessage.textContent = `Are you sure you want to delete the license "${name}"?`;
    confirmBtn.href = `<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>?delete=${id}`;
    
    modal.classList.remove('hidden');
}

// Close modal
function closeModal() {
    const modal = document.getElementById('deleteModal');
    modal.classList.add('hidden');
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('deleteModal');
    if (event.target === modal) {
        closeModal();
    }
});
</script>

<?php
// Include footer
require_once '../../theme/admin/footer.php';
?>