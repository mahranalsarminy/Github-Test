<?php
/**
 * Categories Management - Add New Category
 * 
 * Allows administrators to create new categories with metadata.
 * 
 * @package WallPix
 * @version 1.0.0
 */

// Define the project root directory
define('ROOT_DIR', dirname(dirname(__DIR__)));

// Include necessary files
require_once ROOT_DIR . '/includes/init.php';

// Set page title
$pageTitle = 'Add New Category';

// Initialize variables
$errors = [];
$success = false;
$name = '';
$parent_id = 0;
$description = '';
$description_ar = '';
$bg_color = '#FFFFFF';
$display_order = 0;
$is_active = 1;
$featured = 0;
$icon_url = '';
$image_url = '';

// Get all categories for parent dropdown
try {
    $categories = $pdo->query("
        SELECT id, name, parent_id 
        FROM categories 
        ORDER BY display_order, name
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Build a hierarchical list for dropdown
    $categories_list = [];
    $level = 0;
    
    // Function to build hierarchical list
    function buildCategoryList($categories, $parent_id = 0, $level = 0) {
        $result = [];
        
        foreach ($categories as $category) {
            if ($category['parent_id'] == $parent_id) {
                // Add spacing based on level for visual hierarchy
                $prefix = str_repeat('— ', $level);
                $category['name'] = $prefix . $category['name'];
                $category['level'] = $level;
                
                $result[] = $category;
                $children = buildCategoryList($categories, $category['id'], $level + 1);
                $result = array_merge($result, $children);
            }
        }
        
        return $result;
    }
    
    // Build the hierarchical list
    $categories_list = buildCategoryList($categories);
    
} catch (PDOException $e) {
    $errors[] = "Error loading categories: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = trim($_POST['name'] ?? '');
    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : 0;
    $description = trim($_POST['description'] ?? '');
    $description_ar = trim($_POST['description_ar'] ?? '');
    $bg_color = trim($_POST['bg_color'] ?? '#FFFFFF');
    $display_order = isset($_POST['display_order']) ? (int)$_POST['display_order'] : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $featured = isset($_POST['featured']) ? 1 : 0;
    
    // Validate form data
    if (empty($name)) {
        $errors[] = "Category name is required";
    }
    
    // Check if category name already exists
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ?");
        $stmt->execute([$name]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "A category with this name already exists";
        }
    } catch (PDOException $e) {
        $errors[] = "Database error: " . $e->getMessage();
    }
    
    // Generate slug from name
    $slug = createSlug($name);
    
    // Check if slug already exists
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE slug = ?");
        $stmt->execute([$slug]);
        if ($stmt->fetchColumn() > 0) {
            $slug = $slug . '-' . time(); // Make it unique by appending timestamp
        }
    } catch (PDOException $e) {
        $errors[] = "Database error: " . $e->getMessage();
    }
    // Process icon upload if exists
    $icon_url = '';
    if (isset($_FILES['icon']) && $_FILES['icon']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'svg', 'webp'];
        $filename = $_FILES['icon']['name'];
        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed)) {
            // Check file size (max 2MB)
            if ($_FILES['icon']['size'] <= 2097152) {
                $upload_dir = ROOT_DIR . '/uploads/categories/icons/';
                
                // Create directory if it doesn't exist
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Create a unique filename
                $new_filename = 'icon_' . uniqid() . '.' . $file_ext;
                $destination = $upload_dir . $new_filename;
                
                // Move the uploaded file
                if (move_uploaded_file($_FILES['icon']['tmp_name'], $destination)) {
                    $icon_url = '/uploads/categories/icons/' . $new_filename;
                } else {
                    $errors[] = "Failed to upload icon";
                }
            } else {
                $errors[] = "Icon file size exceeds 2MB limit";
            }
        } else {
            $errors[] = "Invalid icon file format. Allowed formats: " . implode(', ', $allowed);
        }
    }
    
    // Process image upload if exists
    $image_url = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
        $filename = $_FILES['image']['name'];
        $file_ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        
        if (in_array($file_ext, $allowed)) {
            // Check file size (max 5MB)
            if ($_FILES['image']['size'] <= 5242880) {
                $upload_dir = ROOT_DIR . '/uploads/categories/images/';
                
                // Create directory if it doesn't exist
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Create a unique filename
                $new_filename = 'image_' . uniqid() . '.' . $file_ext;
                $destination = $upload_dir . $new_filename;
                
                // Move the uploaded file
                if (move_uploaded_file($_FILES['image']['tmp_name'], $destination)) {
                    $image_url = '/uploads/categories/images/' . $new_filename;
                } else {
                    $errors[] = "Failed to upload image";
                }
            } else {
                $errors[] = "Image file size exceeds 5MB limit";
            }
        } else {
            $errors[] = "Invalid image file format. Allowed formats: " . implode(', ', $allowed);
        }
    }
    // If no errors, insert into database
    if (empty($errors)) {
        try {
            // Insert category
            $stmt = $pdo->prepare("
                INSERT INTO categories (
                    name, parent_id, icon_url, bg_color, display_order,
                    description_ar, is_active, featured, image_url,
                    slug, description, created_by, created_at
                ) VALUES (
                    :name, :parent_id, :icon_url, :bg_color, :display_order,
                    :description_ar, :is_active, :featured, :image_url,
                    :slug, :description, :created_by, NOW()
                )
            ");
            
            $stmt->execute([
                ':name' => $name,
                ':parent_id' => $parent_id ?: null,
                ':icon_url' => $icon_url,
                ':bg_color' => $bg_color,
                ':display_order' => $display_order,
                ':description_ar' => $description_ar,
                ':is_active' => $is_active,
                ':featured' => $featured,
                ':image_url' => $image_url,
                ':slug' => $slug,
                ':description' => $description,
                ':created_by' => $_SESSION['username'] ?? 'admin'
            ]);
            
            $category_id = $pdo->lastInsertId();
            
            // Log activity
            $pdo->prepare("
                INSERT INTO activities (user_id, description, created_at) 
                VALUES (:user_id, :description, NOW())
            ")->execute([
                ':user_id' => $_SESSION['user_id'] ?? null,
                ':description' => "Added new category #$category_id: $name"
            ]);
            
            $success = true;
            
            // Reset form fields after successful submission
            $name = '';
            $parent_id = 0;
            $description = '';
            $description_ar = '';
            $bg_color = '#FFFFFF';
            $display_order = 0;
            $is_active = 1;
            $featured = 0;
            $icon_url = '';
            $image_url = '';
            
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}

/**
 * Create a slug from a string
 */
function createSlug($string) {
    $slug = strtolower($string);
    $slug = preg_replace('/[^a-z0-9\s-]/', '', $slug);
    $slug = preg_replace('/[\s-]+/', '-', $slug);
    $slug = trim($slug, '-');
    return $slug;
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
                        <i class="fas fa-folder-plus mr-2"></i> Add New Category
                    </h1>
                    <a href="index.php" class="btn bg-gray-500 hover:bg-gray-700 text-white">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Categories List
                    </a>
                </div>

                <?php if ($success): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 <?php echo $darkMode ? 'bg-green-900 text-green-300 border-green-500' : ''; ?>" role="alert">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-500 <?php echo $darkMode ? 'text-green-400' : ''; ?>"></i>
                            </div>
                            <div class="ml-3">
                                <p class="font-medium">Category has been created successfully!</p>
                                <div class="mt-2">
                                    <a href="index.php" class="text-green-600 hover:underline <?php echo $darkMode ? 'text-green-400' : ''; ?>">
                                        <i class="fas fa-arrow-left mr-1"></i> Return to Categories List
                                    </a>
                                    <span class="mx-2">|</span>
                                    <a href="add.php" class="text-green-600 hover:underline <?php echo $darkMode ? 'text-green-400' : ''; ?>">
                                        <i class="fas fa-plus mr-1"></i> Add Another Category
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

                <!-- Add Category Form -->
                <div class="bg-white <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?> rounded-lg shadow-md p-6">
                    <form action="add.php" method="post" enctype="multipart/form-data" class="space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Basic Information -->
                            <div class="space-y-6">
                                <h2 class="text-xl font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">Basic Information</h2>
                                
                                <div class="mb-4">
                                    <label for="name" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        Category Name <span class="text-red-600">*</span>
                                    </label>
                                    <input type="text" id="name" name="name" value="<?= htmlspecialchars($name) ?>" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                        placeholder="Enter category name">
                                </div>
                                
                                <div class="mb-4">
                                    <label for="parent_id" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">Parent Category</label>
                                    <select id="parent_id" name="parent_id" 
                                                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                        <option value="0">None (Top Level Category)</option>
                                        <?php foreach ($categories_list as $cat): ?>
                                            <option value="<?= $cat['id'] ?>" <?= $parent_id == $cat['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($cat['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="description" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">Description</label>
                                    <textarea id="description" name="description" rows="4"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                        placeholder="Enter category description"><?= htmlspecialchars($description) ?></textarea>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="description_ar" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">Arabic Description</label>
                                    <textarea id="description_ar" name="description_ar" rows="4" dir="rtl"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                        placeholder="أدخل وصف الفئة بالعربية"><?= htmlspecialchars($description_ar) ?></textarea>
                                </div>
                            </div>
                            <!-- Additional Settings -->
                            <div class="space-y-6">
                                <h2 class="text-xl font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">Display Settings</h2>
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">Category Icon</label>
                                    <div class="mt-2 flex items-center space-x-4">
                                        <div id="icon_preview" class="w-12 h-12 border rounded-md flex items-center justify-center <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : 'bg-gray-100 border-gray-200'; ?>">
                                            <i class="fas fa-folder text-gray-400 text-2xl"></i>
                                        </div>
                                        <label for="icon" class="cursor-pointer bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600">
                                            <span><i class="fas fa-upload mr-2"></i> Upload Icon</span>
                                            <input type="file" id="icon" name="icon" accept=".jpg,.jpeg,.png,.gif,.svg,.webp" class="hidden" onchange="previewIcon(this)">
                                        </label>
                                    </div>
                                    <p class="mt-2 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                        Recommended size: 48x48px. Max size: 2MB. Supported formats: JPG, PNG, GIF, SVG, WEBP.
                                    </p>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">Category Image</label>
                                    <div class="mt-2 flex items-center space-x-4">
                                        <div id="image_preview" class="w-32 h-24 border rounded-md flex items-center justify-center <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : 'bg-gray-100 border-gray-200'; ?>">
                                            <i class="fas fa-image text-gray-400 text-2xl"></i>
                                        </div>
                                        <label for="image" class="cursor-pointer bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600">
                                            <span><i class="fas fa-upload mr-2"></i> Upload Image</span>
                                            <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.gif,.webp" class="hidden" onchange="previewImage(this)">
                                        </label>
                                    </div>
                                    <p class="mt-2 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                        Recommended size: 1200x800px. Max size: 5MB. Supported formats: JPG, PNG, GIF, WEBP.
                                    </p>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="bg_color" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">Background Color</label>
                                    <div class="mt-1 flex items-center">
                                        <input type="color" id="bg_color" name="bg_color" value="<?= htmlspecialchars($bg_color) ?>"
                                            class="h-8 w-20 rounded-md border-gray-300 <?php echo $darkMode ? 'border-gray-600' : ''; ?>">
                                        <input type="text" id="bg_color_hex" value="<?= htmlspecialchars($bg_color) ?>"
                                            class="ml-2 block rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?> w-24"
                                            placeholder="#FFFFFF" onchange="updateColorPicker(this.value)">
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="display_order" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">Display Order</label>
                                    <input type="number" id="display_order" name="display_order" value="<?= $display_order ?>" min="0"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                        placeholder="0">
                                    <p class="mt-1 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                        Categories with lower numbers appear first (0 is highest priority)
                                    </p>
                                </div>
                                
                                <div class="mb-4 space-y-4">
                                    <div class="flex items-start">
                                        <div class="flex h-5 items-center">
                                            <input id="is_active" name="is_active" type="checkbox" <?= $is_active ? 'checked' : '' ?>
                                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : ''; ?>">
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="is_active" class="font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">Active</label>
                                            <p class="text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                                                Make this category visible on the front-end
                                            </p>
                                        </div>
                                    </div>
                                    
                                    <div class="flex items-start">
                                        <div class="flex h-5 items-center">
                                            <input id="featured" name="featured" type="checkbox" <?= $featured ? 'checked' : '' ?>
                                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : ''; ?>">
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="featured" class="font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">Featured</label>
                                            <p class="text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                                                Highlight this category in featured sections
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Submit Buttons -->
                        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 <?php echo $darkMode ? 'border-gray-700' : ''; ?>">
                            <a href="index.php" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                <i class="fas fa-times mr-2"></i> Cancel
                            </a>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                <i class="fas fa-save mr-2"></i> Create Category
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- JavaScript for form functionality -->
<script>
    // Preview uploaded icon
    function previewIcon(input) {
        const preview = document.getElementById('icon_preview');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" class="max-w-full max-h-full object-contain">`;
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Preview uploaded image
    function previewImage(input) {
        const preview = document.getElementById('image_preview');
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" class="max-w-full max-h-full object-contain">`;
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Update color picker from text input
    function updateColorPicker(value) {
        document.getElementById('bg_color').value = value;
    }
    
    // Update text input from color picker
    document.getElementById('bg_color').addEventListener('input', function() {
        document.getElementById('bg_color_hex').value = this.value.toUpperCase();
    });
    
    // Generate slug as user types name
    document.getElementById('name').addEventListener('input', function() {
        const name = this.value;
        let slug = name.toLowerCase()
                      .replace(/[^\w\s-]/g, '')
                      .replace(/[\s_-]+/g, '-')
                      .replace(/^-+|-+$/g, '');
    });
</script>

<?php
// Set current date and time for footer
$currentDateTime = '2025-03-19 03:16:51';
$currentUser = 'mahranalsarminy';

// Include admin panel footer
include ROOT_DIR . '/theme/admin/footer.php';
?>
