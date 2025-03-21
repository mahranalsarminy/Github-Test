<?php
/**
 * Pages Management - Edit Page
 * 
 * Allows administrators to edit existing pages on the website.
 * 
 * @package WallPix
 * @version 1.0.0
 */

// Define the project root directory
define('ROOT_DIR', dirname(dirname(__DIR__)));

// Include necessary files
require_once ROOT_DIR . '/includes/init.php';

// Check if page ID and type are provided
if (!isset($_GET['id']) || !is_numeric($_GET['id']) || !isset($_GET['type'])) {
    $_SESSION['error'] = "Invalid page request.";
    header("Location: index.php");
    exit;
}

$page_id = (int)$_GET['id'];
$pageType = $_GET['type'];

// Validate page type
if (!in_array($pageType, ['about', 'privacy', 'terms'])) {
    $_SESSION['error'] = "Invalid page type.";
    header("Location: index.php");
    exit;
}

// Set page title
$pageTitle = 'Edit ' . ucfirst($pageType) . ' Page';

// Determine the table based on page type
$table = '';
switch ($pageType) {
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
        $_SESSION['error'] = "Invalid page type.";
        header("Location: index.php");
        exit;
}

// Initialize variables
$title = '';
$content = '';
$sort_order = 0;
$is_active = 1;
$image_url = '';
$created_at = '';
$errors = [];
$success = false;

// Fetch existing page data
try {
    // Prepare SQL query based on page type
    if ($pageType === 'about') {
        $stmt = $pdo->prepare("SELECT title, content, image_url, sort_order, is_active, created_at FROM $table WHERE id = ?");
    } else {
        $stmt = $pdo->prepare("SELECT title, content, sort_order, is_active, created_at FROM $table WHERE id = ?");
    }
    
    $stmt->execute([$page_id]);
    $page = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$page) {
        $_SESSION['error'] = "Page not found.";
        header("Location: index.php");
        exit;
    }
    
    // Populate variables with existing data
    $title = $page['title'];
    $content = $page['content'];
    $sort_order = $page['sort_order'];
    $is_active = $page['is_active'];
    $created_at = $page['created_at'];
    
    if ($pageType === 'about' && isset($page['image_url'])) {
        $image_url = $page['image_url'];
    }
} catch (PDOException $e) {
    $_SESSION['error'] = "Database error: " . $e->getMessage();
    header("Location: index.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $sort_order = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $clear_image = isset($_POST['clear_image']) ? (bool)$_POST['clear_image'] : false;
    
    // Validate title
    if (empty($title)) {
        $errors[] = "Title is required.";
    } elseif (strlen($title) > 255) {
        $errors[] = "Title cannot exceed 255 characters.";
    }
    
    // Validate content
    if (empty($content)) {
        $errors[] = "Content is required.";
    }
    
    // File upload handling for About pages
    $new_image_url = $image_url;
    if ($pageType === 'about') {
        // Handle image clearing
        if ($clear_image) {
            // Delete the existing image file if it exists
            if (!empty($image_url) && file_exists(ROOT_DIR . $image_url)) {
                unlink(ROOT_DIR . $image_url);
            }
            $new_image_url = '';
        }
        // Handle new image upload
        else if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
            $upload_dir = ROOT_DIR . '/uploads/pages/';
            
            // Create directory if it doesn't exist
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            $file_name = $_FILES['image']['name'];
            $file_tmp = $_FILES['image']['tmp_name'];
            $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
            
            // Check file extension
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($file_ext, $allowed_extensions)) {
                $errors[] = "Only JPG, JPEG, PNG, GIF, and WEBP files are allowed for images.";
            }
            
            // Check file size (max 5MB)
            if ($_FILES['image']['size'] > 5 * 1024 * 1024) {
                $errors[] = "Image file size should not exceed 5MB.";
            }
            
            if (empty($errors)) {
                $new_file_name = time() . '_' . bin2hex(random_bytes(8)) . '.' . $file_ext;
                $upload_path = $upload_dir . $new_file_name;
                
                if (move_uploaded_file($file_tmp, $upload_path)) {
                    // Delete the old image if it exists
                    if (!empty($image_url) && file_exists(ROOT_DIR . $image_url)) {
                        unlink(ROOT_DIR . $image_url);
                    }
                    $new_image_url = '/uploads/pages/' . $new_file_name;
                } else {
                    $errors[] = "Failed to upload image. Please try again.";
                }
            }
        }
    }
    
    // If no errors, update database
    if (empty($errors)) {
        try {
            // Prepare SQL query based on page type
            if ($pageType === 'about') {
                $stmt = $pdo->prepare("
                    UPDATE $table 
                    SET title = :title, content = :content, image_url = :image_url, sort_order = :sort_order, is_active = :is_active
                    WHERE id = :id
                ");
                $stmt->bindParam(':image_url', $new_image_url);
            } else {
                $stmt = $pdo->prepare("
                    UPDATE $table 
                    SET title = :title, content = :content, sort_order = :sort_order, is_active = :is_active
                    WHERE id = :id
                ");
            }
            
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':sort_order', $sort_order);
            $stmt->bindParam(':is_active', $is_active);
            $stmt->bindParam(':id', $page_id);
            $stmt->execute();
            
            // Log activity
            $pdo->prepare("
                INSERT INTO activities (user_id, description, created_at) 
                VALUES (:user_id, :description, NOW())
            ")->execute([
                ':user_id' => $_SESSION['user_id'] ?? null,
                ':description' => "Updated $pageType page: $title (ID: $page_id)"
            ]);
            
            // Update image_url with new value for display
            $image_url = $new_image_url;
            
            // Set success message
            $success = true;
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
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
                        <i class="fas fa-edit mr-2"></i> Edit <?php echo ucfirst($pageType); ?> Page
                    </h1>
                    <div class="flex space-x-3">
                        <a href="index.php<?php echo $pageType !== 'about' ? '?type=' . $pageType : ''; ?>" class="btn bg-gray-500 hover:bg-gray-700 text-white">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Pages List
                        </a>
                        <?php if ($pageType === 'about'): ?>
                            <a href="<?= htmlspecialchars($siteUrl . '/about') ?>" target="_blank" class="btn bg-blue-500 hover:bg-blue-700 text-white">
                                <i class="fas fa-external-link-alt mr-2"></i> View Page
                            </a>
                        <?php elseif ($pageType === 'privacy'): ?>
                            <a href="<?= htmlspecialchars($siteUrl . '/privacy-policy') ?>" target="_blank" class="btn bg-blue-500 hover:bg-blue-700 text-white">
                                <i class="fas fa-external-link-alt mr-2"></i> View Page
                            </a>
                        <?php elseif ($pageType === 'terms'): ?>
                            <a href="<?= htmlspecialchars($siteUrl . '/terms-of-service') ?>" target="_blank" class="btn bg-blue-500 hover:bg-blue-700 text-white">
                                <i class="fas fa-external-link-alt mr-2"></i> View Page
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($success): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 <?php echo $darkMode ? 'bg-green-900 text-green-300 border-green-500' : ''; ?>" role="alert">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-500 <?php echo $darkMode ? 'text-green-400' : ''; ?>"></i>
                            </div>
                            <div class="ml-3">
                                <p class="font-medium">Page has been updated successfully!</p>
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

                <!-- Edit Page Form -->
                <div class="bg-white <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?> rounded-lg shadow-md p-6">
                    <form action="edit.php?id=<?= $page_id ?>&type=<?= $pageType ?>" method="post" enctype="multipart/form-data" class="space-y-6">
                        <div>
                            <label for="title" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                Title <span class="text-red-600">*</span>
                            </label>
                            <input type="text" id="title" name="title" value="<?= htmlspecialchars($title) ?>" required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                placeholder="Enter page title">
                        </div>
                        
                        <div>
                            <label for="content" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                Content <span class="text-red-600">*</span>
                            </label>
                            <div class="mt-1">
                                <textarea id="content" name="content" rows="15" required
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                    placeholder="Enter page content"><?= htmlspecialchars($content) ?></textarea>
                            </div>
                            <p class="mt-1 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                HTML formatting is supported. Use editor buttons for formatting.
                            </p>
                        </div>
                        
                        <?php if ($pageType === 'about'): ?>
                            <div>
                                <label class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                    Featured Image
                                </label>
                                <div class="mt-2 flex items-center space-x-4">
                                    <div id="image_preview" class="h-32 w-32 border rounded-md flex items-center justify-center overflow-hidden <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : 'bg-gray-100 border-gray-200'; ?>">
                                        <?php if (!empty($image_url)): ?>
                                            <img src="<?= htmlspecialchars($image_url) ?>" class="max-w-full max-h-full object-contain">
                                        <?php else: ?>
                                            <i class="fas fa-image text-gray-400 text-3xl"></i>
                                        <?php endif; ?>
                                    </div>
                                    <div class="flex flex-col space-y-2">
                                        <label for="image" class="cursor-pointer bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600 text-center">
                                            <span><i class="fas fa-upload mr-2"></i> <?= !empty($image_url) ? 'Change Image' : 'Upload Image' ?></span>
                                            <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.gif,.webp" class="hidden" onchange="previewImage(this)">
                                        </label>
                                        
                                        <?php if (!empty($image_url)): ?>
                                            <div class="flex items-center">
                                                <input type="checkbox" id="clear_image" name="clear_image" class="h-4 w-4 rounded border-gray-300 text-red-600 focus:ring-red-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : ''; ?>">
                                                <label for="clear_image" class="ml-2 text-sm text-red-600 <?php echo $darkMode ? 'text-red-400' : ''; ?>">Remove image</label>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <p class="mt-2 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    Recommended size: 800x600px. Max size: 5MB. Supported formats: JPG, PNG, GIF, WEBP.
                                </p>
                            </div>
                        <?php endif; ?>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="sort_order" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                    Display Order
                                </label>
                                <input type="number" id="sort_order" name="sort_order" value="<?= $sort_order ?>" min="0"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                    placeholder="0">
                                <p class="mt-1 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    Pages with lower numbers appear first (0 is highest priority)
                                </p>
                            </div>
                            
                            <div>
                                <div class="flex items-start mt-5">
                                    <div class="flex h-5 items-center">
                                        <input id="is_active" name="is_active" type="checkbox" <?= $is_active ? 'checked' : '' ?>
                                            class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : ''; ?>">
                                    </div>
                                    <div class="ml-3 text-sm">
                                        <label for="is_active" class="font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">Active</label>
                                        <p class="<?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                            Make this page visible on the front-end
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4 text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                            <span>Created: <?= date('F j, Y \a\t g:i a', strtotime($created_at)) ?></span>
                            <span class="mx-2">|</span>
                            <span>ID: <?= $page_id ?></span>
                        </div>
                        
                        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 <?php echo $darkMode ? 'border-gray-700' : ''; ?>">
                            <a href="index.php<?php echo $pageType !== 'about' ? '?type=' . $pageType : ''; ?>" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                <i class="fas fa-times mr-2"></i> Cancel
                            </a>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                <i class="fas fa-save mr-2"></i> Update Page
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for form functionality -->
<script src="https://cdn.tiny.cloud/1/89a26gz76i0oxsdzd5o6zakeqzyyejsuqc1ta3qip1um7icg/tinymce/5/tinymce.min.js" referrerpolicy="origin"></script>
<script>
    // Initialize TinyMCE for rich text editor
    tinymce.init({
        selector: '#content',
        height: 400,
        menubar: false,
        plugins: [
            'advlist autolink lists link image charmap print preview anchor',
            'searchreplace visualblocks code fullscreen',
            'insertdatetime media table paste code help wordcount'
        ],
        toolbar: 'undo redo | formatselect | ' +
                'bold italic backcolor | alignleft aligncenter ' +
                'alignright alignjustify | bullist numlist outdent indent | ' +
                'removeformat | help',
        content_style: 'body { font-family:Helvetica,Arial,sans-serif; font-size:14px }',
        <?php if ($darkMode): ?>
        skin: 'oxide-dark',
        content_css: 'dark',
        <?php endif; ?>
    });
    
    // Preview uploaded image
    function previewImage(input) {
        const preview = document.getElementById('image_preview');
        const clearImageCheckbox = document.getElementById('clear_image');
        
        if (clearImageCheckbox) {
            clearImageCheckbox.checked = false;
        }
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" class="max-w-full max-h-full object-contain">`;
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Handle clear image checkbox
    document.addEventListener('DOMContentLoaded', function() {
        const clearImageCheckbox = document.getElementById('clear_image');
        const imageInput = document.getElementById('image');
        const preview = document.getElementById('image_preview');
        
        if (clearImageCheckbox) {
            clearImageCheckbox.addEventListener('change', function() {
                if (this.checked) {
                    // Clear the file input
                    imageInput.value = '';
                    // Show placeholder
                    preview.innerHTML = '<i class="fas fa-image text-gray-400 text-3xl"></i>';
                }
            });
        }
    });
</script>

<?php
// Set current date and time for footer
$currentDateTime = '2025-03-19 03:57:46';
$currentUser = 'mahranalsarminy';

// Include admin panel footer
include ROOT_DIR . '/theme/admin/footer.php';
?>