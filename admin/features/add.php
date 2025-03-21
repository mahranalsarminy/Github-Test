<?php
/**
 * Features Management - Add New Feature
 * 
 * Allows administrators to add new features to the website.
 * 
 * @package WallPix
 * @version 1.0.0
 */

// Define the project root directory
define('ROOT_DIR', dirname(dirname(__DIR__)));

// Include necessary files
require_once ROOT_DIR . '/includes/init.php';

// Set page title
$pageTitle = 'Add New Feature';

// Current date and time
$currentDateTime = '2025-03-19 04:42:20';
$currentUser = 'mahranalsarminy';

// Initialize variables
$title = '';
$description = '';
$icon_class = '';
$icon_color = 'blue-500';
$sort_order = 0;
$is_active = 1;
$image_url = '';
$errors = [];
$success = false;

// Available icon colors
$availableColors = [
    'blue-500' => 'Blue', 
    'green-500' => 'Green', 
    'red-500' => 'Red', 
    'yellow-500' => 'Yellow', 
    'purple-500' => 'Purple',
    'indigo-500' => 'Indigo', 
    'pink-500' => 'Pink', 
    'gray-500' => 'Gray', 
    'orange-500' => 'Orange', 
    'teal-500' => 'Teal'
];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $icon_class = trim($_POST['icon_class'] ?? '');
    $icon_color = isset($_POST['icon_color']) && array_key_exists($_POST['icon_color'], $availableColors) ? $_POST['icon_color'] : 'blue-500';
    $sort_order = isset($_POST['sort_order']) ? (int)$_POST['sort_order'] : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate title
    if (empty($title)) {
        $errors[] = "Title is required.";
    } elseif (strlen($title) > 255) {
        $errors[] = "Title cannot exceed 255 characters.";
    }
    
    // Validate description
    if (empty($description)) {
        $errors[] = "Description is required.";
    }
    
    // File upload handling
    $image_url = '';
    if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
        $upload_dir = ROOT_DIR . '/uploads/features/';
        
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
        
        // Check file size (max 2MB)
        if ($_FILES['image']['size'] > 2 * 1024 * 1024) {
            $errors[] = "Image file size should not exceed 2MB.";
        }
        
        if (empty($errors)) {
            $new_file_name = time() . '_' . bin2hex(random_bytes(8)) . '.' . $file_ext;
            $upload_path = $upload_dir . $new_file_name;
            
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $image_url = '/uploads/features/' . $new_file_name;
            } else {
                $errors[] = "Failed to upload image. Please try again.";
            }
        }
    }
    
    // If no errors, insert into database
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO features (title, description, image_url, icon_class, icon_color, sort_order, is_active, created_at)
                VALUES (:title, :description, :image_url, :icon_class, :icon_color, :sort_order, :is_active, NOW())
            ");
            
            $stmt->bindParam(':title', $title);
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':image_url', $image_url);
            $stmt->bindParam(':icon_class', $icon_class);
            $stmt->bindParam(':icon_color', $icon_color);
            $stmt->bindParam(':sort_order', $sort_order);
            $stmt->bindParam(':is_active', $is_active);
            $stmt->execute();
            
            $newFeatureId = $pdo->lastInsertId();
            
            // Log activity
            $pdo->prepare("
                INSERT INTO activities (user_id, description, created_at) 
                VALUES (:user_id, :description, NOW())
            ")->execute([
                ':user_id' => $_SESSION['user_id'] ?? null,
                ':description' => "Added new feature: $title (ID: $newFeatureId)"
            ]);
            
            // Set success message
            $success = true;
            
            // Reset form fields on success
            if ($success) {
                $title = '';
                $description = '';
                $icon_class = '';
                $icon_color = 'blue-500';
                $sort_order = 0;
                $is_active = 1;
                $image_url = '';
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
                        <i class="fas fa-plus-circle mr-2"></i> Add New Feature
                    </h1>
                    <a href="index.php" class="btn bg-gray-500 hover:bg-gray-700 text-white">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Features List
                    </a>
                </div>

                <?php if ($success): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 <?php echo $darkMode ? 'bg-green-900 text-green-300 border-green-500' : ''; ?>" role="alert">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-500 <?php echo $darkMode ? 'text-green-400' : ''; ?>"></i>
                            </div>
                            <div class="ml-3">
                                <p class="font-medium">Feature has been created successfully!</p>
                                <div class="mt-2">
                                    <a href="index.php" class="text-sm underline">
                                        Return to features list
                                    </a>
                                    <span class="mx-2 text-gray-500">|</span>
                                    <a href="add.php" class="text-sm underline">
                                        Add another feature
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

                <!-- Add Feature Form -->
                <div class="bg-white <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?> rounded-lg shadow-md p-6">
                    <form action="add.php" method="post" enctype="multipart/form-data" class="space-y-6">
                        <div>
                            <label for="title" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                Feature Title <span class="text-red-600">*</span>
                            </label>
                            <input type="text" id="title" name="title" value="<?= htmlspecialchars($title) ?>" required
                                                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                placeholder="E.g., High-Resolution Images">
                        </div>
                        
                        <div>
                            <label for="description" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                Description <span class="text-red-600">*</span>
                            </label>
                            <div class="mt-1">
                                <textarea id="description" name="description" rows="8" required
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                    placeholder="Describe this feature..."><?= htmlspecialchars($description) ?></textarea>
                            </div>
                            <p class="mt-1 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                HTML formatting is supported. Use editor buttons for formatting.
                            </p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="icon_class" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                    Icon Class
                                </label>
                                <div class="mt-1 flex rounded-md shadow-sm">
                                    <span class="inline-flex items-center px-3 rounded-l-md border border-r-0 border-gray-300 bg-gray-50 text-gray-500 <?php echo $darkMode ? 'bg-gray-600 border-gray-600 text-gray-300' : ''; ?>">
                                        <i class="fas fa-icons"></i>
                                    </span>
                                    <input type="text" id="icon_class" name="icon_class" value="<?= htmlspecialchars($icon_class) ?>"
                                        class="flex-1 min-w-0 block w-full rounded-none rounded-r-md border-gray-300 focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                        placeholder="E.g., fas fa-image or fab fa-html5">
                                </div>
                                <p class="mt-1 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    Use Font Awesome icon classes (e.g., fas fa-star). 
                                    <a href="https://fontawesome.com/icons" target="_blank" class="text-blue-600 hover:underline <?php echo $darkMode ? 'text-blue-400' : ''; ?>">
                                        Browse icons <i class="fas fa-external-link-alt text-xs"></i>
                                    </a>
                                </p>
                            </div>
                            
                            <div>
                                <label for="icon_color" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                    Icon Color
                                </label>
                                <select id="icon_color" name="icon_color" 
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                    <?php foreach ($availableColors as $value => $name): ?>
                                        <option value="<?= $value ?>" <?= $icon_color === $value ? 'selected' : '' ?>>
                                            <?= $name ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="mt-2 flex space-x-2">
                                    <?php foreach ($availableColors as $value => $name): ?>
                                        <div class="color-preview bg-<?= $value ?> h-6 w-6 rounded-full cursor-pointer" data-color="<?= $value ?>" title="<?= $name ?>"></div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                Feature Image
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
                                Optional. Recommended size: 600x400px. Max size: 2MB.
                            </p>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div>
                                <label for="sort_order" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                    Display Order
                                </label>
                                <input type="number" id="sort_order" name="sort_order" value="<?= $sort_order ?>" min="0"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                    placeholder="0">
                                <p class="mt-1 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    Features with lower numbers appear first (0 is highest priority)
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
                                        Make this feature visible on the front-end
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 <?php echo $darkMode ? 'border-gray-700' : ''; ?>">
                            <a href="index.php" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                <i class="fas fa-times mr-2"></i> Cancel
                            </a>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                <i class="fas fa-save mr-2"></i> Create Feature
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
        selector: '#description',
        height: 300,
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
    
    // Color preview functionality
    document.addEventListener('DOMContentLoaded', function() {
        const colorPreviews = document.querySelectorAll('.color-preview');
        const iconColorSelect = document.getElementById('icon_color');
        
        colorPreviews.forEach(preview => {
            preview.addEventListener('click', function() {
                const color = this.getAttribute('data-color');
                iconColorSelect.value = color;
            });
        });
    });
</script>

<?php
// Update current date and time for footer
$currentDateTime = '2025-03-19 04:48:52';
$currentUser = 'mahranalsarminy';

// Include admin panel footer
include ROOT_DIR . '/theme/admin/footer.php';
?>