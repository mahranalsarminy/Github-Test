<?php
/**
 * Categories Management - Edit Category
 * 
 * Allows administrators to edit existing categories with metadata.
 * 
 * @package WallPix
 * @version 1.0.0
 */

// Define the project root directory
define('ROOT_DIR', dirname(dirname(__DIR__)));

// Include necessary files
require_once ROOT_DIR . '/includes/init.php';

// Set page title
$pageTitle = 'Edit Category';

// Initialize variables
$errors = [];
$success = false;
$category_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if category ID is provided
if (!$category_id) {
    header('Location: index.php');
    exit;
}

// Get category data
try {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$category_id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$category) {
        header('Location: index.php');
        exit;
    }
    
    // Extract category data
    $name = $category['name'];
    $parent_id = $category['parent_id'] ?? 0;
    $description = $category['description'];
    $description_ar = $category['description_ar'];
    $bg_color = $category['bg_color'] ?? '#FFFFFF';
    $display_order = $category['display_order'] ?? 0;
    $is_active = $category['is_active'] ?? 1;
    $featured = $category['featured'] ?? 0;
    $icon_url = $category['icon_url'];
    $image_url = $category['image_url'];
    $slug = $category['slug'];
    
} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
    // Redirect if we can't load the category
    header('Location: index.php');
    exit;
}

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
    function buildCategoryList($categories, $parent_id = 0, $level = 0, $exclude_id = null) {
        $result = [];
        
        foreach ($categories as $category) {
            if ($category['id'] == $exclude_id) {
                continue; // Skip the current category (can't be its own parent)
            }
            
            // Skip children of the current category (can't create recursive parent relationships)
            if (isChildOf($categories, $category['id'], $exclude_id)) {
                continue;
            }
            
            if ($category['parent_id'] == $parent_id) {
                // Add spacing based on level for visual hierarchy
                $prefix = str_repeat('— ', $level);
                $category['name'] = $prefix . $category['name'];
                $category['level'] = $level;
                
                $result[] = $category;
                $children = buildCategoryList($categories, $category['id'], $level + 1, $exclude_id);
                $result = array_merge($result, $children);
            }
        }
        
        return $result;
    }
    
    // Check if a category is a child of another category
    function isChildOf($categories, $category_id, $potential_parent_id) {
        foreach ($categories as $category) {
            if ($category['id'] == $category_id) {
                if ($category['parent_id'] == $potential_parent_id) {
                    return true;
                } else if ($category['parent_id']) {
                    return isChildOf($categories, $category['parent_id'], $potential_parent_id);
                }
            }
        }
        return false;
    }
    
    // Build the hierarchical list excluding the current category to avoid recursion
    $categories_list = buildCategoryList($categories, 0, 0, $category_id);
    
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
    
    // Check if category name already exists for other categories
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE name = ? AND id != ?");
        $stmt->execute([$name, $category_id]);
        if ($stmt->fetchColumn() > 0) {
            $errors[] = "A category with this name already exists";
        }
    } catch (PDOException $e) {
        $errors[] = "Database error: " . $e->getMessage();
    }
    
    // Generate new slug if name changed
    if ($name !== $category['name']) {
        $new_slug = createSlug($name);
        
        // Check if slug already exists for other categories
        try {
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE slug = ? AND id != ?");
            $stmt->execute([$new_slug, $category_id]);
            if ($stmt->fetchColumn() > 0) {
                $new_slug = $new_slug . '-' . time(); // Make it unique by appending timestamp
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
        
        $slug = $new_slug;
    }
    
    // Process icon upload if exists
    $new_icon_url = $icon_url;
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
                    $new_icon_url = '/uploads/categories/icons/' . $new_filename;
                    
                    // Delete old icon file if exists and not the default
                    if (!empty($icon_url) && file_exists(ROOT_DIR . $icon_url) && strpos($icon_url, '/uploads/categories/icons/') === 0) {
                        unlink(ROOT_DIR . $icon_url);
                    }
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
    $new_image_url = $image_url;
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
                    $new_image_url = '/uploads/categories/images/' . $new_filename;
                    
                    // Delete old image file if exists and not the default
                    if (!empty($image_url) && file_exists(ROOT_DIR . $image_url) && strpos($image_url, '/uploads/categories/images/') === 0) {
                        unlink(ROOT_DIR . $image_url);
                    }
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
    // If no errors, update the database
    if (empty($errors)) {
        try {
            // Update category
            $stmt = $pdo->prepare("
                UPDATE categories SET
                    name = :name,
                    parent_id = :parent_id,
                    icon_url = :icon_url,
                    bg_color = :bg_color,
                    display_order = :display_order,
                    description_ar = :description_ar,
                    is_active = :is_active,
                    featured = :featured,
                    image_url = :image_url,
                    slug = :slug,
                    description = :description,
                    updated_by = :updated_by,
                    updated_at = NOW()
                WHERE id = :id
            ");
            
            $stmt->execute([
                ':name' => $name,
                ':parent_id' => $parent_id ?: null,
                ':icon_url' => $new_icon_url,
                ':bg_color' => $bg_color,
                ':display_order' => $display_order,
                ':description_ar' => $description_ar,
                ':is_active' => $is_active,
                ':featured' => $featured,
                ':image_url' => $new_image_url,
                ':slug' => $slug,
                ':description' => $description,
                ':updated_by' => $_SESSION['username'] ?? 'admin',
                ':id' => $category_id
            ]);
            
            // Log activity
            $pdo->prepare("
                INSERT INTO activities (user_id, description, created_at) 
                VALUES (:user_id, :description, NOW())
            ")->execute([
                ':user_id' => $_SESSION['user_id'] ?? null,
                ':description' => "Updated category #$category_id: $name"
            ]);
            
            $success = true;
            
            // Refresh category data
            $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
            $stmt->execute([$category_id]);
            $category = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Update local variables with refreshed data
            $icon_url = $category['icon_url'];
            $image_url = $category['image_url'];
            
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
                        <i class="fas fa-edit mr-2"></i> Edit Category: <?= htmlspecialchars($name) ?>
                    </h1>
                    <div class="flex space-x-3">
                        <a href="index.php" class="btn bg-gray-500 hover:bg-gray-700 text-white">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Categories List
                        </a>
                        <a href="../media/index.php?category=<?= $category_id ?>" class="btn bg-blue-500 hover:bg-blue-700 text-white">
                            <i class="fas fa-image mr-2"></i> View Media
                        </a>
                    </div>
                </div>

                <?php if ($success): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 <?php echo $darkMode ? 'bg-green-900 text-green-300 border-green-500' : ''; ?>" role="alert">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-500 <?php echo $darkMode ? 'text-green-400' : ''; ?>"></i>
                            </div>
                            <div class="ml-3">
                                <p class="font-medium">Category has been updated successfully!</p>
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

                <!-- Edit Category Form -->
                <div class="bg-white <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?> rounded-lg shadow-md p-6">
                    <form action="edit.php?id=<?= $category_id ?>" method="post" enctype="multipart/form-data" class="space-y-6">
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
                                    <p class="mt-1 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                        Note: You cannot select this category or its children as a parent.
                                    </p>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="slug" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        Slug
                                    </label>
                                    <div class="mt-1 flex rounded-md shadow-sm">
                                        <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 <?php echo $darkMode ? 'bg-gray-600 text-gray-300 border-gray-600' : 'bg-gray-50 text-gray-500'; ?>">
                                            /category/
                                        </span>
                                        <input type="text" id="slug" name="slug" value="<?= htmlspecialchars($slug) ?>" readonly
                                            class="focus:ring-blue-500 focus:border-blue-500 flex-1 block w-full rounded-none rounded-r-md border-gray-300 <?php echo $darkMode ? 'bg-gray-600 text-gray-200 border-gray-600' : 'bg-gray-50 text-gray-500'; ?>">
                                    </div>
                                    <p class="mt-1 text-xs <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                        The slug is auto-generated from the name and will update if you change the name.
                                    </p>
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
                                            <?php if (!empty($icon_url)): ?>
                                                <img src="<?= htmlspecialchars($icon_url) ?>" alt="Icon" class="max-w-full max-h-full object-contain">
                                            <?php else: ?>
                                                <i class="fas fa-folder text-gray-400 text-2xl"></i>
                                            <?php endif; ?>
                                        </div>
                                        <label for="icon" class="cursor-pointer bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600">
                                            <span><i class="fas fa-upload mr-2"></i> <?= empty($icon_url) ? 'Upload Icon' : 'Change Icon' ?></span>
                                            <input type="file" id="icon" name="icon" accept=".jpg,.jpeg,.png,.gif,.svg,.webp" class="hidden" onchange="previewIcon(this)">
                                        </label>
                                        <?php if (!empty($icon_url)): ?>
                                            <button type="button" class="text-red-600 hover:text-red-800 <?php echo $darkMode ? 'text-red-400 hover:text-red-300' : ''; ?>" onclick="clearIcon()">
                                                <i class="fas fa-trash-alt"></i> Remove
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                    <p class="mt-2 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                        Recommended size: 48x48px. Max size: 2MB. Supported formats: JPG, PNG, GIF, SVG, WEBP.
                                    </p>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">Category Image</label>
                                    <div class="mt-2 flex items-center space-x-4">
                                        <div id="image_preview" class="w-32 h-24 border rounded-md flex items-center justify-center <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : 'bg-gray-100 border-gray-200'; ?>">
                                            <?php if (!empty($image_url)): ?>
                                                <img src="<?= htmlspecialchars($image_url) ?>" alt="Image" class="max-w-full max-h-full object-contain">
                                            <?php else: ?>
                                                <i class="fas fa-image text-gray-400 text-2xl"></i>
                                            <?php endif; ?>
                                        </div>
                                        <label for="image" class="cursor-pointer bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600">
                                            <span><i class="fas fa-upload mr-2"></i> <?= empty($image_url) ? 'Upload Image' : 'Change Image' ?></span>
                                            <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.gif,.webp" class="hidden" onchange="previewImage(this)">
                                        </label>
                                        <?php if (!empty($image_url)): ?>
                                            <button type="button" class="text-red-600 hover:text-red-800 <?php echo $darkMode ? 'text-red-400 hover:text-red-300' : ''; ?>" onclick="clearImage()">
                                                <i class="fas fa-trash-alt"></i> Remove
                                            </button>
                                        <?php endif; ?>
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
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">Category Info</label>
                                    <div class="mt-2 grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <span class="block <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">Created By:</span>
                                            <span class="font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                                                <?= htmlspecialchars($category['created_by'] ?? 'Unknown') ?>
                                            </span>
                                        </div>
                                        <div>
                                            <span class="block <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">Created At:</span>
                                            <span class="font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                                                <?= date('M j, Y g:i A', strtotime($category['created_at'])) ?>
                                            </span>
                                        </div>
                                        <?php if (!empty($category['updated_by'])): ?>
                                        <div>
                                            <span class="block <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">Updated By:</span>
                                            <span class="font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                                                <?= htmlspecialchars($category['updated_by']) ?>
                                            </span>
                                        </div>
                                        <div>
                                            <span class="block <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">Updated At:</span>
                                            <span class="font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                                                <?= date('M j, Y g:i A', strtotime($category['updated_at'])) ?>
                                            </span>
                                        </div>
                                        <?php endif; ?>
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
                                <i class="fas fa-save mr-2"></i> Update Category
                            </button>
                        </div>
                        
                        <!-- Hidden fields for icon/image clearing -->
                        <input type="hidden" id="clear_icon" name="clear_icon" value="0">
                        <input type="hidden" id="clear_image" name="clear_image" value="0">
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
            
            // Reset the clear flag if a new file is selected
            document.getElementById('clear_icon').value = '0';
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
            
            // Reset the clear flag if a new file is selected
            document.getElementById('clear_image').value = '0';
        }
    }
    
    // Clear icon
    function clearIcon() {
        const preview = document.getElementById('icon_preview');
        preview.innerHTML = `<i class="fas fa-folder text-gray-400 text-2xl"></i>`;
        document.getElementById('icon').value = '';
        document.getElementById('clear_icon').value = '1';
    }
    
    // Clear image
    function clearImage() {
        const preview = document.getElementById('image_preview');
        preview.innerHTML = `<i class="fas fa-image text-gray-400 text-2xl"></i>`;
        document.getElementById('image').value = '';
        document.getElementById('clear_image').value = '1';
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
                      
        document.getElementById('slug').value = slug;
    });
</script>

<?php
// Set current date and time for footer
$currentDateTime = '2025-03-19 03:26:43';
$currentUser = 'mahranalsarminy';

// Include admin panel footer
include ROOT_DIR . '/theme/admin/footer.php';
?>
<?php
// This section should be added to the form processing section before database update
// Handle icon and image removal
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Additional processing for icon removal
    if (isset($_POST['clear_icon']) && $_POST['clear_icon'] == '1') {
        // Delete the icon file if it exists and not a default icon
        if (!empty($icon_url) && file_exists(ROOT_DIR . $icon_url) && strpos($icon_url, '/uploads/categories/icons/') === 0) {
            unlink(ROOT_DIR . $icon_url);
        }
        $new_icon_url = ''; // Clear the icon URL in database
    }
    
    // Additional processing for image removal
    if (isset($_POST['clear_image']) && $_POST['clear_image'] == '1') {
        // Delete the image file if it exists and not a default image
        if (!empty($image_url) && file_exists(ROOT_DIR . $image_url) && strpos($image_url, '/uploads/categories/images/') === 0) {
            unlink(ROOT_DIR . $image_url);
        }
        $new_image_url = ''; // Clear the image URL in database
    }
}
?>