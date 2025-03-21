<?php
/**
 * Tags Management - Add New Tag
 * 
 * Allows administrators to add new tags to the website.
 * 
 * @package WallPix
 * @version 1.0.0
 */

// Define the project root directory
define('ROOT_DIR', dirname(dirname(__DIR__)));

// Include necessary files
require_once ROOT_DIR . '/includes/init.php';

// Set page title
$pageTitle = 'Add New Tag';

// Current date and time
$currentDateTime = '2025-03-19 05:14:19';
$currentUser = 'mahranalsarminy';

// Initialize variables
$name = '';
$slug = '';
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $slug = trim($_POST['slug'] ?? '');
    
    // Auto-generate slug if empty
    if (empty($slug) && !empty($name)) {
        // Convert to lowercase, replace spaces with dashes, remove special characters
        $slug = strtolower($name);
        $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');
    }
    
    // Validate name
    if (empty($name)) {
        $errors[] = "Tag name is required.";
    } elseif (strlen($name) > 50) {
        $errors[] = "Tag name cannot exceed 50 characters.";
    }
    
    // Validate slug
    if (empty($slug)) {
        $errors[] = "Slug is required.";
    } elseif (strlen($slug) > 60) {
        $errors[] = "Slug cannot exceed 60 characters.";
    } elseif (!preg_match('/^[a-z0-9-]+$/', $slug)) {
        $errors[] = "Slug can only contain lowercase letters, numbers, and hyphens.";
    }
    
    // Check if tag with same name or slug already exists
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tags WHERE name = :name");
            $stmt->bindParam(':name', $name);
            $stmt->execute();
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "A tag with this name already exists.";
            }
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM tags WHERE slug = :slug");
            $stmt->bindParam(':slug', $slug);
            $stmt->execute();
            if ($stmt->fetchColumn() > 0) {
                $errors[] = "A tag with this slug already exists.";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
    
    // If no errors, save to database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO tags (name, slug, created_by, created_at)
                VALUES (:name, :slug, :created_by, NOW())
            ");
            
            $stmt->execute([
                ':name' => $name,
                ':slug' => $slug,
                ':created_by' => $_SESSION['user_id'] ?? null
            ]);
            
            // Get the new tag ID
            $newTagId = $pdo->lastInsertId();
            
            // Log activity
            $pdo->prepare("
                INSERT INTO activities (user_id, description, created_at) 
                VALUES (:user_id, :description, NOW())
            ")->execute([
                ':user_id' => $_SESSION['user_id'] ?? null,
                ':description' => "Added new tag: $name (ID: $newTagId)"
            ]);
            
            // Set success
            $success = true;
            
            // Reset form fields on success
            if ($success) {
                $name = '';
                $slug = '';
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

// Include admin panel header
include ROOT_DIR . '/theme/admin/header.php';
?>

<!-- Main Content Container -->
<div class="flex flex-col md:flex-row min-h-screen">
    <!-- Sidebar -->
    <?php include ROOT_DIR . '/theme/admin/slidbar.php'; ?>
    
    <!-- Main Content Area -->
    <div class="w-full md:pl-64">
                <div class="p-6 <?php echo $darkMode ? 'bg-gray-900' : 'bg-gray-100'; ?>">
            <div class="mb-6">
                <div class="flex flex-col md:flex-row justify-between items-center mb-6">
                    <h1 class="text-2xl font-semibold mb-4 md:mb-0 <?php echo $darkMode ? 'text-white' : ''; ?>">
                        <i class="fas fa-plus-circle mr-2"></i> Add New Tag
                    </h1>
                    <a href="index.php" class="btn bg-gray-500 hover:bg-gray-700 text-white">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Tags List
                    </a>
                </div>

                <?php if ($success): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 <?php echo $darkMode ? 'bg-green-900 text-green-300 border-green-500' : ''; ?>" role="alert">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-500 <?php echo $darkMode ? 'text-green-400' : ''; ?>"></i>
                            </div>
                            <div class="ml-3">
                                <p class="font-medium">Tag has been created successfully!</p>
                                <div class="mt-2">
                                    <a href="index.php" class="text-sm underline">
                                        Return to tags list
                                    </a>
                                    <span class="mx-2 text-gray-500">|</span>
                                    <a href="add.php" class="text-sm underline">
                                        Add another tag
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($errors)): ?>
                    <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 <?php echo $darkMode ? 'bg-red-900 text-red-300 border-red-500' : ''; ?>" role="alert">
                        <p class="font-bold">Please fix the following errors:</p>
                        <ul class="mt-2 ml-4 list-disc">
                            <?php foreach ($errors as $error): ?>
                                <li><?= htmlspecialchars($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Add Tag Form -->
                <div class="bg-white <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?> rounded-lg shadow-md p-6">
                    <form action="add.php" method="post" class="space-y-6">
                        <div>
                            <label for="name" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                Tag Name <span class="text-red-600">*</span>
                            </label>
                            <input type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                placeholder="E.g., Nature, Abstract, Minimalist">
                            <p class="mt-1 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                The name is how the tag appears on the site.
                            </p>
                        </div>
                        
                        <div>
                            <label for="slug" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                Slug
                            </label>
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 <?php echo $darkMode ? 'bg-gray-600 border-gray-600 text-gray-300' : ''; ?>">
                                    /tag/
                                </span>
                                <input type="text" id="slug" name="slug" value="<?= htmlspecialchars($slug) ?>"
                                    class="flex-1 min-w-0 block w-full rounded-none rounded-r-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                    placeholder="e.g., nature-photography">
                            </div>
                            <p class="mt-1 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                The slug is the URL-friendly version of the name. It is usually all lowercase and contains only letters, numbers, and hyphens. Leave empty to auto-generate from the tag name.
                            </p>
                        </div>
                        
                        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 <?php echo $darkMode ? 'border-gray-700' : ''; ?>">
                            <a href="index.php" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                <i class="fas fa-times mr-2"></i> Cancel
                            </a>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                <i class="fas fa-save mr-2"></i> Create Tag
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Auto-generate slug from name
        const nameInput = document.getElementById('name');
        const slugInput = document.getElementById('slug');
        
        nameInput.addEventListener('keyup', function() {
            // Only auto-generate slug if slug field is empty
            if (slugInput.value === '') {
                const name = nameInput.value;
                // Convert to lowercase, replace spaces and special chars with hyphens
                let slug = name.toLowerCase()
                    .replace(/[^\w\s-]/g, '') // Remove non-word chars
                    .replace(/[\s_-]+/g, '-') // Replace spaces and underscores with hyphens
                    .replace(/^-+|-+$/g, ''); // Trim hyphens from start and end
                
                slugInput.value = slug;
            }
        });
    });
</script>

<?php
// Update current date and time for footer
$currentDateTime = '2025-03-19 05:20:07';
$currentUser = 'mahranalsarminy';

// Include admin panel footer
include ROOT_DIR . '/theme/admin/footer.php';
?>
            