<?php
/**
 * Pages Management - Add New Page
 * 
 * Allows administrators to add new pages to the website.
 * 
 * @package WallPix
 * @version 1.0.0
 */

// Define the project root directory
define('ROOT_DIR', dirname(dirname(__DIR__)));

// Include necessary files
require_once ROOT_DIR . '/includes/init.php';

// Default page type
$pageType = isset($_GET['type']) && in_array($_GET['type'], ['about', 'privacy', 'terms']) ? $_GET['type'] : 'about';

// Set page title based on page type
$pageTitle = 'Add New ' . ucfirst($pageType) . ' Page';

// Initialize variables
$title = '';
$content = '';
$sort_order = 0;
$is_active = 1;
$image_url = '';
$errors = [];
$success = false;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $sort_order = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
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
    $image_url = '';
    if ($pageType === 'about' && isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
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
                $image_url = '/uploads/pages/' . $new_file_name;
            } else {
                $errors[] = "Failed to upload image. Please try again.";
            }
        }
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        try {
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
                    throw new Exception("Invalid page type");
            }
            
            // Prepare SQL query based on page type
            if ($pageType === 'about') {
                $stmt = $pdo->prepare("
                    INSERT INTO $table (title, content, image_url, sort_order, is_active, created_at)
                    VALUES (:title, :content, :image_url, :sort_order, :is_active, NOW())
                ");
                $stmt->bindParam(':image_url', $image_url);
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO $table (title, content, sort_order, is_active, created_at)
                    VALUES (:title, :content, :sort_order, :is_active, NOW())
                ");
            }
            
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':content', $content);
            $stmt->bindParam(':sort_order', $sort_order);
            $stmt->bindParam(':is_active', $is_active);
            $stmt->execute();
            
            $newPageId = $pdo->lastInsertId();
            
            // Log activity
            $pdo->prepare("
                INSERT INTO activities (user_id, description, created_at) 
                VALUES (:user_id, :description, NOW())
            ")->execute([
                ':user_id' => $_SESSION['user_id'] ?? null,
                ':description' => "Added new $pageType page: $title"
            ]);
            
            // Set success message
            $success = true;
            
            // Reset form fields on success
            if (!$success) {
                $title = '';
                $content = '';
                $sort_order = 0;
                $is_active = 1;
                $image_url = '';
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
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
                        <i class="fas fa-plus-circle mr-2"></i> Add New <?php echo ucfirst($pageType); ?> Page
                    </h1>
                    <a href="index.php<?php echo $pageType !== 'about' ? '?type=' . $pageType : ''; ?>" class="btn bg-gray-500 hover:bg-gray-700 text-white">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Pages List
                    </a>
                </div>

                <?php if ($success): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 <?php echo $darkMode ? 'bg-green-900 text-green-300 border-green-500' : ''; ?>" role="alert">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-500 <?php echo $darkMode ? 'text-green-400' : ''; ?>"></i>
                            </div>
                            <div class="ml-3">
                                <p class="font-medium">Page has been created successfully!</p>
                                <div class="mt-2">
                                    <a href="index.php?type=<?php echo $pageType; ?>" class="text-sm underline">
                                        Return to pages list
                                    </a>
                                    <span class="mx-2 text-gray-500">|</span>
                                    <a href="add.php?type=<?php echo $pageType; ?>" class="text-sm underline">
                                        Add another <?php echo $pageType; ?> page
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

                <!-- Add Page Form -->
                <div class="bg-white <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?> rounded-lg shadow-md p-6">
                    <form action="add.php?type=<?php echo $pageType; ?>" method="post" enctype="multipart/form-data" class="space-y-6">
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
                                    <div id="image_preview" class="h-32 w-32 border rounded-md flex items-center justify-center <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : 'bg-gray-100 border-gray-200'; ?>">
                                        <i class="fas fa-image text-gray-400 text-3xl"></i>
                                    </div>
                                    <label for="image" class="cursor-pointer bg-blue-500 text-white py-2 px-4 rounded-md hover:bg-blue-600">
                                        <span><i class="fas fa-upload mr-2"></i> Upload Image</span>
                                        <input type="file" id="image" name="image" accept=".jpg,.jpeg,.png,.gif,.webp" class="hidden" onchange="previewImage(this)">
                                    </label>
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
                        
                        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 <?php echo $darkMode ? 'border-gray-700' : ''; ?>">
                            <a href="index.php<?php echo $pageType !== 'about' ? '?type=' . $pageType : ''; ?>" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                <i class="fas fa-times mr-2"></i> Cancel
                            </a>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                <i class="fas fa-save mr-2"></i> Create Page
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
        
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                preview.innerHTML = `<img src="${e.target.result}" class="max-w-full max-h-full object-contain">`;
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }
</script>

<?php
// Set current date and time for footer
$currentDateTime = '2025-03-19 03:52:59';
$currentUser = 'mahranalsarminy';

// Include admin panel footer
include ROOT_DIR . '/theme/admin/footer.php';
?>