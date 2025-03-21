<?php
/**
 * Media Management - Add New Media
 * 
 * Allows administrators to add new media items with metadata.
 * Supports four upload methods:
 * 1. Single file upload
 * 2. Multi-file upload
 * 3. Single external URL
 * 4. Multiple external URLs
 * 
 * @package WallPix
 * @version 1.0.0
 */

// Define the project root directory
define('ROOT_DIR', dirname(dirname(__DIR__)));

// Include necessary files
require_once ROOT_DIR . '/includes/init.php';

// Set page title
$pageTitle = 'Add New Media';

// Initialize variables
$title = '';
$description = '';
$category_id = 0;
$tags_input = '';
$color_ids = [];
$resolution_id = 0;
$orientation = '';
$status = 1;
$featured = 0;
$errors = [];
$success = false;
$success_count = 0;

// Get all categories for the dropdown
try {
    $categories = $pdo->query("SELECT id, name FROM categories ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Error loading categories: " . $e->getMessage();
}

// Get all colors for selection
try {
    $colors = $pdo->query("SELECT id, color_name, hex_code FROM colors ORDER BY color_name")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Error loading colors: " . $e->getMessage();
}

// Get all resolutions for selection
try {
    $resolutions = $pdo->query("SELECT id, resolution FROM resolutions ORDER BY resolution")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $errors[] = "Error loading resolutions: " . $e->getMessage();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $tags_input = trim($_POST['tags_input'] ?? '');
    $color_ids = isset($_POST['color_ids']) ? (array)$_POST['color_ids'] : [];
    $resolution_id = (int)($_POST['resolution_id'] ?? 0);
    $orientation = $_POST['orientation'] ?? '';
    $status = isset($_POST['status']) ? 1 : 0;
    $featured = isset($_POST['featured']) ? 1 : 0;
    $upload_type = $_POST['upload_type'] ?? 'single_file';
    
    // Basic validation
    if (empty($title) && $upload_type != 'multi_file' && $upload_type != 'multi_url') {
        $errors[] = "Title is required for single uploads";
    }
    
    if (empty($orientation) || !in_array($orientation, ['portrait', 'landscape'])) {
        $errors[] = "Please select a valid orientation";
    }

    // Process uploads based on selected method
    switch ($upload_type) {
        case 'single_file':
            if (processingSingleFileUpload()) {
                $success = true;
                $success_count = 1;
            }
            break;
        case 'multi_file':
            $success_count = processingMultiFileUpload();
            if ($success_count > 0) {
                $success = true;
            }
            break;
        case 'single_url':
            if (processingSingleUrlUpload()) {
                $success = true;
                $success_count = 1;
            }
            break;
        case 'multi_url':
            $success_count = processingMultiUrlUpload();
            if ($success_count > 0) {
                $success = true;
            }
            break;
    }
}

/**
 * Process single file upload
 * @return bool Success or failure
 */
function processingSingleFileUpload() {
    global $pdo, $title, $description, $category_id, $tags_input, $color_ids, 
           $resolution_id, $orientation, $status, $featured, $errors;

    // Check if file is uploaded
    if (!isset($_FILES['media_file']) || $_FILES['media_file']['error'] == UPLOAD_ERR_NO_FILE) {
        $errors[] = "Please select a file to upload";
        return false;
    }
    
    $file = $_FILES['media_file'];
    
    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $error_messages = [
            UPLOAD_ERR_INI_SIZE => "The uploaded file exceeds the upload_max_filesize directive",
            UPLOAD_ERR_FORM_SIZE => "The uploaded file exceeds the MAX_FILE_SIZE directive",
            UPLOAD_ERR_PARTIAL => "The uploaded file was only partially uploaded",
            UPLOAD_ERR_NO_TMP_DIR => "Missing a temporary folder",
            UPLOAD_ERR_CANT_WRITE => "Failed to write file to disk",
            UPLOAD_ERR_EXTENSION => "A PHP extension stopped the file upload"
        ];
        $errors[] = "Upload error: " . ($error_messages[$file['error']] ?? "Unknown error");
        return false;
    }
    
    // Check file type
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    if (!in_array($file['type'], $allowed_types)) {
        $errors[] = "Invalid file type. Allowed types: JPEG, PNG, GIF, WEBP";
        return false;
    }
    
    // Check file size (10MB max)
    if ($file['size'] > 10 * 1024 * 1024) {
        $errors[] = "File size exceeds the maximum limit (10MB)";
        return false;
    }
    
    // Generate unique filename
    $filename = uniqid() . '_' . basename($file['name']);
    
    // Create upload directory if it doesn't exist
    $upload_dir = ROOT_DIR . '/uploads/media/' . date('Y/m');
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Set destination path
    $destination = $upload_dir . '/' . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $destination)) {
        $file_path = '/uploads/media/' . date('Y/m') . '/' . $filename;
        
        // Create thumbnail
        $thumbnail_dir = ROOT_DIR . '/uploads/thumbnails/' . date('Y/m');
        if (!is_dir($thumbnail_dir)) {
            mkdir($thumbnail_dir, 0755, true);
        }
        
        $thumbnail_file = $thumbnail_dir . '/thumb_' . $filename;
        
        // Generate thumbnail
        if (create_thumbnail($destination, $thumbnail_file, 300, 300)) {
            $thumbnail_path = '/uploads/thumbnails/' . date('Y/m') . '/thumb_' . $filename;
        } else {
            // Use original as thumbnail if generation fails
            $thumbnail_path = $file_path;
        }
        
        // Get image dimensions
        if ($image_info = getimagesize($destination)) {
            $width = $image_info[0];
            $height = $image_info[1];
        }
        
        // Save media to database
        return saveMediaToDatabase($title, $description, $category_id, $tags_input, $color_ids, 
            $resolution_id, $orientation, $status, $featured, $filename, $file_path, 
            $thumbnail_path, $width ?? null, $height ?? null);
    } else {
        $errors[] = "Failed to move uploaded file";
        return false;
    }
}
/**
 * Process multiple file upload
 * @return int Number of successfully uploaded files
 */
function processingMultiFileUpload() {
    global $pdo, $description, $category_id, $tags_input, $color_ids, 
           $resolution_id, $orientation, $status, $featured, $errors;
    
    $success_count = 0;
    
    // Check if files are uploaded
    if (!isset($_FILES['media_files']) || empty($_FILES['media_files']['name'][0])) {
        $errors[] = "Please select at least one file to upload";
        return 0;
    }
    
    // Create upload directories if they don't exist
    $upload_dir = ROOT_DIR . '/uploads/media/' . date('Y/m');
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $thumbnail_dir = ROOT_DIR . '/uploads/thumbnails/' . date('Y/m');
    if (!is_dir($thumbnail_dir)) {
        mkdir($thumbnail_dir, 0755, true);
    }
    
    // Allowed file types
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    // Process each file
    foreach ($_FILES['media_files']['name'] as $key => $name) {
        if (empty($name)) continue;
        
        $file_error = $_FILES['media_files']['error'][$key];
        $file_size = $_FILES['media_files']['size'][$key];
        $file_type = $_FILES['media_files']['type'][$key];
        $file_tmp = $_FILES['media_files']['tmp_name'][$key];
        
        // Skip files with errors
        if ($file_error !== UPLOAD_ERR_OK) {
            continue;
        }
        
        // Check file type
        if (!in_array($file_type, $allowed_types)) {
            continue;
        }
        
        // Check file size (10MB max)
        if ($file_size > 10 * 1024 * 1024) {
            continue;
        }
        
        // Generate unique filename
        $filename = uniqid() . '_' . basename($name);
        
        // Set destination path
        $destination = $upload_dir . '/' . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file_tmp, $destination)) {
            $file_path = '/uploads/media/' . date('Y/m') . '/' . $filename;
            
            // Generate thumbnail
            $thumbnail_file = $thumbnail_dir . '/thumb_' . $filename;
            
            if (create_thumbnail($destination, $thumbnail_file, 300, 300)) {
                $thumbnail_path = '/uploads/thumbnails/' . date('Y/m') . '/thumb_' . $filename;
            } else {
                // Use original as thumbnail if generation fails
                $thumbnail_path = $file_path;
            }
            
            // Get image dimensions
            if ($image_info = getimagesize($destination)) {
                $width = $image_info[0];
                $height = $image_info[1];
            }
            
            // Extract title from filename
            $title = pathinfo($name, PATHINFO_FILENAME);
            $title = str_replace(['_', '-'], ' ', $title);
            $title = ucwords($title);
            
            // Save media to database
            if (saveMediaToDatabase($title, $description, $category_id, $tags_input, $color_ids, 
                $resolution_id, $orientation, $status, $featured, $filename, $file_path, 
                $thumbnail_path, $width ?? null, $height ?? null)) {
                $success_count++;
            }
        }
    }
    
    return $success_count;
}

/**
 * Process single URL upload
 * @return bool Success or failure
 */
function processingSingleUrlUpload() {
    global $pdo, $title, $description, $category_id, $tags_input, $color_ids, 
           $resolution_id, $orientation, $status, $featured, $errors;
    
    // Get external URL
    $external_url = trim($_POST['external_url'] ?? '');
    
    if (empty($external_url)) {
        $errors[] = "External URL is required";
        return false;
    }
    
    if (!filter_var($external_url, FILTER_VALIDATE_URL)) {
        $errors[] = "Please enter a valid URL";
        return false;
    }
    
    // For external URLs, we use the same URL for thumbnail
    $thumbnail_path = $external_url;
    
    // Save media to database
    return saveMediaToDatabase($title, $description, $category_id, $tags_input, $color_ids, 
        $resolution_id, $orientation, $status, $featured, "", "", 
        $thumbnail_path, null, null, $external_url);
}

/**
 * Process multiple URL upload
 * @return int Number of successfully processed URLs
 */
function processingMultiUrlUpload() {
    global $pdo, $description, $category_id, $tags_input, $color_ids, 
           $resolution_id, $orientation, $status, $featured, $errors;
    
    $success_count = 0;
    
    // Get URLs from textarea
    $urls_text = trim($_POST['external_urls'] ?? '');
    
    if (empty($urls_text)) {
        $errors[] = "Please enter at least one URL";
        return 0;
    }
    
    // Split text by newlines
    $urls = preg_split('/\r\n|\r|\n/', $urls_text);
    
    foreach ($urls as $url) {
        $url = trim($url);
        if (empty($url)) continue;
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            continue;
        }
        
        // Extract title from URL
        $path_parts = pathinfo(parse_url($url, PHP_URL_PATH));
        $auto_title = !empty($path_parts['filename']) ? $path_parts['filename'] : 'Image ' . (count($success_count) + 1);
        $auto_title = str_replace(['_', '-'], ' ', $auto_title);
        $auto_title = ucwords($auto_title);
        
        // Save media to database
        if (saveMediaToDatabase($auto_title, $description, $category_id, $tags_input, $color_ids, 
            $resolution_id, $orientation, $status, $featured, "", "", 
            $url, null, null, $url)) {
            $success_count++;
        }
    }
    
    return $success_count;
}
/**
 * Save media information to database
 * @return bool Success or failure
 */
function saveMediaToDatabase($title, $description, $category_id, $tags_input, $color_ids, 
                             $resolution_id, $orientation, $status, $featured, 
                             $file_name, $file_path, $thumbnail_url, $width, $height, $external_url = '') {
    global $pdo, $errors;
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Insert media record
        $stmt = $pdo->prepare("
            INSERT INTO media (
                title, description, category_id, orientation, file_name, file_path, 
                thumbnail_url, external_url, status, featured, width, height, 
                created_at, created_by
            ) VALUES (
                :title, :description, :category_id, :orientation, :file_name, :file_path, 
                :thumbnail_url, :external_url, :status, :featured, :width, :height,
                NOW(), :created_by
            )
        ");
        
        $stmt->execute([
            ':title' => $title,
            ':description' => $description,
            ':category_id' => $category_id ?: null,
            ':orientation' => $orientation,
            ':file_name' => $file_name,
            ':file_path' => $file_path,
            ':thumbnail_url' => $thumbnail_url,
            ':external_url' => $external_url,
            ':status' => $status,
            ':featured' => $featured,
            ':width' => $width,
            ':height' => $height,
            ':created_by' => $_SESSION['user_id'] ?? 1
        ]);
        
        $media_id = $pdo->lastInsertId();
        
        // Process tags
        if (!empty($tags_input)) {
            processMediaTags($media_id, $tags_input);
        }
        
        // Process colors
        if (!empty($color_ids)) {
            $color_stmt = $pdo->prepare("INSERT INTO media_colors (media_id, color_id) VALUES (:media_id, :color_id)");
            foreach ($color_ids as $color_id) {
                $color_stmt->execute([
                    ':media_id' => $media_id,
                    ':color_id' => $color_id
                ]);
            }
        }
        
        // Process resolution if selected
        if (!empty($resolution_id)) {
            $resolution_stmt = $pdo->prepare("INSERT INTO media_resolutions (media_id, resolution_id) VALUES (:media_id, :resolution_id)");
            $resolution_stmt->execute([
                ':media_id' => $media_id,
                ':resolution_id' => $resolution_id
            ]);
        }
        
        // Commit transaction
        $pdo->commit();
        
        // Log activity
        $pdo->prepare("INSERT INTO activities (user_id, description, created_at) VALUES (:user_id, :description, NOW())")
            ->execute([
                ':user_id' => $_SESSION['user_id'] ?? null,
                ':description' => "Added new media item #$media_id: $title"
            ]);
        
        return true;
        
    } catch (PDOException $e) {
        // Rollback transaction
        $pdo->rollBack();
        $errors[] = "Database error: " . $e->getMessage();
        return false;
    }
}

/**
 * Process tags from comma-separated string
 * Creates new tags if they don't exist
 */
function processMediaTags($media_id, $tags_input) {
    global $pdo;
    
    // Split the input by commas
    $tag_names = array_map('trim', explode(',', $tags_input));
    
    foreach ($tag_names as $tag_name) {
        if (empty($tag_name)) continue;
        
        // Create slug
        $slug = createSlug($tag_name);
        
        try {
            // Check if tag exists
            $tag_stmt = $pdo->prepare("SELECT id FROM tags WHERE slug = :slug LIMIT 1");
            $tag_stmt->execute([':slug' => $slug]);
            $tag = $tag_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($tag) {
                // Use existing tag
                $tag_id = $tag['id'];
            } else {
                // Create new tag
                $new_tag_stmt = $pdo->prepare("INSERT INTO tags (name, slug, created_at) VALUES (:name, :slug, NOW())");
                $new_tag_stmt->execute([':name' => $tag_name, ':slug' => $slug]);
                $tag_id = $pdo->lastInsertId();
            }
            
            // Associate tag with media
            $media_tag_stmt = $pdo->prepare("INSERT IGNORE INTO media_tags (media_id, tag_id) VALUES (:media_id, :tag_id)");
            $media_tag_stmt->execute([':media_id' => $media_id, ':tag_id' => $tag_id]);
        } catch (PDOException $e) {
            // Log error but continue processing other tags
            error_log("Error processing tag '$tag_name': " . $e->getMessage());
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

/**
 * Create a thumbnail from an image
 */
function create_thumbnail($source_path, $thumb_path, $max_width, $max_height) {
    // Get image info
    list($width, $height, $type) = getimagesize($source_path);
    
    // Calculate new dimensions
    $ratio = min($max_width / $width, $max_height / $height);
    $new_width = $width * $ratio;
    $new_height = $height * $ratio;
    
    // Create new image based on type
    switch ($type) {
        case IMAGETYPE_JPEG:
            $source = imagecreatefromjpeg($source_path);
            break;
        case IMAGETYPE_PNG:
            $source = imagecreatefrompng($source_path);
            break;
        case IMAGETYPE_GIF:
            $source = imagecreatefromgif($source_path);
            break;
        case IMAGETYPE_WEBP:
            $source = imagecreatefromwebp($source_path);
            break;
        default:
            return false;
    }
    
    // Create blank thumbnail image
    $thumbnail = imagecreatetruecolor($new_width, $new_height);
    
    // Preserve transparency for PNG and GIF
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($thumbnail, false);
        imagesavealpha($thumbnail, true);
        $transparent = imagecolorallocatealpha($thumbnail, 255, 255, 255, 127);
        imagefilledrectangle($thumbnail, 0, 0, $new_width, $new_height, $transparent);
    }
    
    // Resize image
    imagecopyresampled($thumbnail, $source, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
    
    // Save thumbnail
    $result = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($thumbnail, $thumb_path, 85);
            break;
        case IMAGETYPE_PNG:
            $result = imagepng($thumbnail, $thumb_path, 8);
            break;
        case IMAGETYPE_GIF:
            $result = imagegif($thumbnail, $thumb_path);
            break;
        case IMAGETYPE_WEBP:
            $result = imagewebp($thumbnail, $thumb_path, 85);
            break;
    }
    
    // Free memory
    imagedestroy($source);
    imagedestroy($thumbnail);
    
    return $result;
}

// Database schema notes - ensure these tables exist:
// 1. media_colors - to associate colors with media
// 2. media_resolutions - to associate resolutions with media

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
                        <i class="fas fa-plus-circle mr-2"></i> Add New Media
                    </h1>
                    <a href="index.php" class="btn bg-gray-500 hover:bg-gray-700 text-white">
                        <i class="fas fa-arrow-left mr-2"></i> Back to Media List
                    </a>
                </div>

                <?php if ($success): ?>
                    <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 <?php echo $darkMode ? 'bg-green-900 text-green-300 border-green-500' : ''; ?>" role="alert">
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <i class="fas fa-check-circle text-green-500 <?php echo $darkMode ? 'text-green-400' : ''; ?>"></i>
                            </div>
                            <div class="ml-3">
                                <p class="font-medium">Success! <?php echo $success_count; ?> media item(s) have been added.</p>
                                <div class="mt-2">
                                    <a href="index.php" class="text-green-600 hover:underline <?php echo $darkMode ? 'text-green-400' : ''; ?>">
                                        <i class="fas fa-arrow-left mr-1"></i> Return to Media List
                                    </a>
                                    <span class="mx-2">|</span>
                                    <a href="add.php" class="text-green-600 hover:underline <?php echo $darkMode ? 'text-green-400' : ''; ?>">
                                        <i class="fas fa-plus mr-1"></i> Add More Media
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

                <!-- Add Media Form -->
                <div class="bg-white <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?> rounded-lg shadow-md p-6">
                    <form action="add.php" method="post" enctype="multipart/form-data" class="space-y-6">
                        <!-- Upload Type Selection -->
                        <div class="mb-6">
                            <h2 class="text-xl font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">Upload Method</h2>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                                <!-- Single File Upload -->
                                <div class="border rounded-lg p-3 <?php echo $darkMode ? 'border-gray-700 hover:border-blue-500' : 'border-gray-200 hover:border-blue-400'; ?> cursor-pointer upload-option">
                                    <input type="radio" id="single_file" name="upload_type" value="single_file" checked 
                                        class="sr-only upload-option-input" data-target="single_file_section">
                                    <label for="single_file" class="flex items-center cursor-pointer">
                                        <span class="w-6 h-6 rounded-full border inline-flex items-center justify-center mr-3 
                                            <?php echo $darkMode ? 'border-gray-600' : 'border-gray-300'; ?>">
                                            <span class="w-3 h-3 rounded-full bg-blue-500 hidden option-selected"></span>
                                        </span>
                                        <div>
                                            <p class="font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                                                <i class="fas fa-file-upload mr-2 text-blue-500"></i> Single File
                                            </p>
                                            <p class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                                                Upload one image
                                            </p>
                                        </div>
                                    </label>
                                </div>
                                
                                <!-- Multi-File Upload -->
                                <div class="border rounded-lg p-3 <?php echo $darkMode ? 'border-gray-700 hover:border-blue-500' : 'border-gray-200 hover:border-blue-400'; ?> cursor-pointer upload-option">
                                    <input type="radio" id="multi_file" name="upload_type" value="multi_file" 
                                        class="sr-only upload-option-input" data-target="multi_file_section">
                                    <label for="multi_file" class="flex items-center cursor-pointer">
                                        <span class="w-6 h-6 rounded-full border inline-flex items-center justify-center mr-3 
                                            <?php echo $darkMode ? 'border-gray-600' : 'border-gray-300'; ?>">
                                            <span class="w-3 h-3 rounded-full bg-blue-500 hidden option-selected"></span>
                                        </span>
                                        <div>
                                            <p class="font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                                                <i class="fas fa-copy mr-2 text-green-500"></i> Multiple Files
                                            </p>
                                            <p class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                                                Upload multiple images
                                            </p>
                                        </div>
                                    </label>
                                </div>
                                
                                <!-- Single URL -->
                                <div class="border rounded-lg p-3 <?php echo $darkMode ? 'border-gray-700 hover:border-blue-500' : 'border-gray-200 hover:border-blue-400'; ?> cursor-pointer upload-option">
                                    <input type="radio" id="single_url" name="upload_type" value="single_url" 
                                        class="sr-only upload-option-input" data-target="single_url_section">
                                    <label for="single_url" class="flex items-center cursor-pointer">
                                        <span class="w-6 h-6 rounded-full border inline-flex items-center justify-center mr-3 
                                            <?php echo $darkMode ? 'border-gray-600' : 'border-gray-300'; ?>">
                                            <span class="w-3 h-3 rounded-full bg-blue-500 hidden option-selected"></span>
                                        </span>
                                        <div>
                                            <p class="font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                                                <i class="fas fa-link mr-2 text-purple-500"></i> External URL
                                            </p>
                                            <p class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                                                Use an external link
                                            </p>
                                        </div>
                                    </label>
                                </div>
                                
                                <!-- Multi URL -->
                                <div class="border rounded-lg p-3 <?php echo $darkMode ? 'border-gray-700 hover:border-blue-500' : 'border-gray-200 hover:border-blue-400'; ?> cursor-pointer upload-option">
                                    <input type="radio" id="multi_url" name="upload_type" value="multi_url" 
                                        class="sr-only upload-option-input" data-target="multi_url_section">
                                    <label for="multi_url" class="flex items-center cursor-pointer">
                                        <span class="w-6 h-6 rounded-full border inline-flex items-center justify-center mr-3 
                                            <?php echo $darkMode ? 'border-gray-600' : 'border-gray-300'; ?>">
                                            <span class="w-3 h-3 rounded-full bg-blue-500 hidden option-selected"></span>
                                        </span>
                                        <div>
                                            <p class="font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                                                <i class="fas fa-link mr-2 text-orange-500"></i> Multiple URLs
                                            </p>
                                            <p class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                                                Use multiple external links
                                            </p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>

                        <!-- Single File Upload Section -->
                        <div id="single_file_section" class="upload-section border border-gray-200 rounded-lg p-4 <?php echo $darkMode ? 'border-gray-700' : ''; ?>">
                            <div class="flex items-center justify-center w-full">
                                <label for="media_file" class="flex flex-col items-center justify-center w-full h-64 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 <?php echo $darkMode ? 'bg-gray-700 hover:bg-gray-600 border-gray-600' : ''; ?>">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                        <i class="fas fa-cloud-upload-alt mb-3 text-4xl text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>"></i>
                                        <p class="mb-2 text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                                            <span class="font-semibold">Click to upload</span> or drag and drop
                                        </p>
                                        <p class="text-xs text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                                            Supported formats: JPEG, PNG, GIF, WEBP (Max. 10MB)
                                        </p>
                                    </div>
                                    <input id="media_file" name="media_file" type="file" class="hidden" accept="image/jpeg,image/png,image/gif,image/webp" />
                                </label>
                            </div>
                            <div id="file_preview" class="hidden mt-4">
                                <div class="text-center">
                                    <p class="text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">Selected file:</p>
                                    <p id="file_name" class="font-medium"></p>
                                    <img id="image_preview" class="max-h-48 mx-auto mt-2 rounded" alt="Image Preview" />
                                </div>
                            </div>
                        </div>

                        <!-- Multi-File Upload Section -->
                        <div id="multi_file_section" class="upload-section border border-gray-200 rounded-lg p-4 hidden <?php echo $darkMode ? 'border-gray-700' : ''; ?>">
                            <div class="flex items-center justify-center w-full">
                                <label for="media_files" class="flex flex-col items-center justify-center w-full h-64 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 <?php echo $darkMode ? 'bg-gray-700 hover:bg-gray-600 border-gray-600' : ''; ?>">
                                    <div class="flex flex-col items-center justify-center pt-5 pb-6">
                                        <i class="fas fa-images mb-3 text-4xl text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>"></i>
                                        <p class="mb-2 text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                                            <span class="font-semibold">Click to upload multiple files</span> or drag and drop
                                        </p>
                                        <p class="text-xs text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                                            Select multiple files (Max. 10MB each)
                                        </p>
                                    </div>
                                    <input id="media_files" name="media_files[]" type="file" class="hidden" accept="image/jpeg,image/png,image/gif,image/webp" multiple />
                                </label>
                            </div>
                            <div id="multi_file_preview" class="hidden mt-4">
                                <p class="text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">Selected files:</p>
                                <div id="multi_file_list" class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-2"></div>
                            </div>
                        </div>

                        <!-- Single URL Section -->
                        <div id="single_url_section" class="upload-section border border-gray-200 rounded-lg p-4 hidden <?php echo $darkMode ? 'border-gray-700' : ''; ?>">
                            <div class="mb-4">
                                <label for="external_url" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">External URL</label>
                                <input type="url" id="external_url" name="external_url" value="<?= htmlspecialchars($_POST['external_url'] ?? '') ?>"
                                                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                    placeholder="https://example.com/image.jpg">
                                <p class="mt-2 text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                                    Enter the direct URL to the image. Make sure you have permission to use this image.
                                </p>
                            </div>
                            <div id="external_preview" class="hidden mt-4">
                                <div class="text-center">
                                    <p class="text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">URL preview:</p>
                                    <img id="external_image_preview" class="max-h-48 mx-auto mt-2 rounded" alt="External URL Preview" />
                                </div>
                            </div>
                        </div>

                        <!-- Multi URL Section -->
                        <div id="multi_url_section" class="upload-section border border-gray-200 rounded-lg p-4 hidden <?php echo $darkMode ? 'border-gray-700' : ''; ?>">
                            <div class="mb-4">
                                <label for="external_urls" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">External URLs</label>
                                <textarea id="external_urls" name="external_urls" rows="5"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                    placeholder="https://example.com/image1.jpg&#10;https://example.com/image2.jpg&#10;https://example.com/image3.jpg"><?= htmlspecialchars($_POST['external_urls'] ?? '') ?></textarea>
                                <p class="mt-2 text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                                    Enter one URL per line. Each URL should point directly to an image.
                                </p>
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Basic Information -->
                            <div class="space-y-6">
                                <h2 class="text-xl font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">Basic Information</h2>
                                
                                <div class="mb-4">
                                    <label for="title" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">Title <span id="title_required" class="text-red-600">*</span></label>
                                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($title) ?>"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                        placeholder="Enter media title">
                                    <p id="title_note" class="mt-1 text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                                        Title is not required for multiple uploads. Auto-generated from filenames.
                                    </p>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="description" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">Description</label>
                                    <textarea id="description" name="description" rows="4"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                        placeholder="Enter media description"><?= htmlspecialchars($description) ?></textarea>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="category_id" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">Category</label>
                                    <select id="category_id" name="category_id" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                        <option value="0">Select a Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category['id'] ?>" <?= $category_id == $category['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($category['name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="tags_input" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">Tags</label>
                                    <input type="text" id="tags_input" name="tags_input" value="<?= htmlspecialchars($tags_input) ?>"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                        placeholder="wallpaper,nature,abstract,etc...">
                                    <p class="mt-2 text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                                        Enter tags separated by commas. These will be searchable in the front-end.
                                    </p>
                                </div>
                            </div>
                            <!-- Additional Settings -->
                            <div class="space-y-6">
                                <h2 class="text-xl font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">Additional Settings</h2>
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">Resolution</label>
                                    <select id="resolution_id" name="resolution_id" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                        <option value="0">Select Resolution</option>
                                        <?php foreach ($resolutions as $resolution): ?>
                                            <option value="<?= $resolution['id'] ?>" <?= $resolution_id == $resolution['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($resolution['resolution']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium mb-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">Colors</label>
                                    <div class="grid grid-cols-4 gap-2">
                                        <?php foreach ($colors as $color): ?>
                                            <div class="flex items-center">
                                                <input type="checkbox" id="color_<?= $color['id'] ?>" name="color_ids[]" value="<?= $color['id'] ?>"
                                                    <?= in_array($color['id'], $color_ids) ? 'checked' : '' ?>
                                                    class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : ''; ?>">
                                                <label for="color_<?= $color['id'] ?>" class="ml-2 flex items-center">
                                                    <span class="inline-block w-4 h-4 rounded-full mr-1" style="background-color: <?= $color['hex_code'] ?>"></span>
                                                    <span class="text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>"><?= $color['color_name'] ?></span>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <label class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">Orientation <span class="text-red-600">*</span></label>
                                    <div class="mt-2 space-y-3">
                                        <div class="flex items-center">
                                            <input type="radio" id="orientation_portrait" name="orientation" value="portrait" <?= $orientation === 'portrait' ? 'checked' : '' ?>
                                                class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : ''; ?>">
                                            <label for="orientation_portrait" class="ml-2 block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                                <i class="fas fa-mobile-alt mr-1"></i> Portrait (9:16)
                                            </label>
                                        </div>
                                        <div class="flex items-center">
                                            <input type="radio" id="orientation_landscape" name="orientation" value="landscape" <?= $orientation === 'landscape' ? 'checked' : '' ?>
                                                class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : ''; ?>">
                                            <label for="orientation_landscape" class="ml-2 block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                                <i class="fas fa-laptop mr-1"></i> Landscape (16:9)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="mb-4">
                                    <div class="flex items-start">
                                        <div class="flex h-5 items-center">
                                            <input id="status" name="status" type="checkbox" <?= $status ? 'checked' : '' ?>
                                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : ''; ?>">
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="status" class="font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">Active</label>
                                            <p class="text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                                                Make this media available on the front-end
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mb-4">
                                    <div class="flex items-start">
                                        <div class="flex h-5 items-center">
                                            <input id="featured" name="featured" type="checkbox" <?= $featured ? 'checked' : '' ?>
                                                class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : ''; ?>">
                                        </div>
                                        <div class="ml-3 text-sm">
                                            <label for="featured" class="font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">Featured</label>
                                            <p class="text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                                                Highlight this media in featured sections
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Submit Buttons -->
                        <div class="flex justify-end space-x-3 pt-6 border-t border-gray-200 <?php echo $darkMode ? 'border-gray-700' : ''; ?>">
                            <button type="button" onclick="window.location.href='index.php'" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                Cancel
                            </button>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                <i class="fas fa-save mr-2"></i> Save Media
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
    document.addEventListener('DOMContentLoaded', function() {
        // Upload option selection
        const uploadOptions = document.querySelectorAll('.upload-option-input');
        const uploadSections = document.querySelectorAll('.upload-section');
        const titleField = document.getElementById('title');
        const titleRequired = document.getElementById('title_required');
        const titleNote = document.getElementById('title_note');
        
        // Initialize form state
        updateFormForUploadType('single_file');
        
        uploadOptions.forEach(option => {
            option.addEventListener('change', function() {
                const targetSection = this.dataset.target;
                
                // Hide all upload sections
                uploadSections.forEach(section => {
                    section.classList.add('hidden');
                });
                
                // Show selected section
                document.getElementById(targetSection).classList.remove('hidden');
                
                // Update selected state for radio buttons
                document.querySelectorAll('.option-selected').forEach(el => {
                    el.classList.add('hidden');
                });
                
                this.closest('.upload-option').querySelector('.option-selected').classList.remove('hidden');
                
                // Update form fields based on upload type
                updateFormForUploadType(this.value);
            });
        });
        
        // Show selected state for default option
        document.querySelector('input[name="upload_type"]:checked')
            .closest('.upload-option')
            .querySelector('.option-selected')
            .classList.remove('hidden');
        
        function updateFormForUploadType(type) {
            if (type === 'multi_file' || type === 'multi_url') {
                titleField.required = false;
                titleRequired.classList.add('hidden');
                titleNote.classList.remove('hidden');
            } else {
                titleField.required = true;
                titleRequired.classList.remove('hidden');
                titleNote.classList.add('hidden');
            }
        }
        
        // Single file preview functionality
        const mediaFileInput = document.getElementById('media_file');
        const filePreview = document.getElementById('file_preview');
        const fileName = document.getElementById('file_name');
        const imagePreview = document.getElementById('image_preview');
        
        mediaFileInput.addEventListener('change', function() {
            if (this.files && this.files[0]) {
                const file = this.files[0];
                fileName.textContent = file.name;
                
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    filePreview.classList.remove('hidden');
                }
                reader.readAsDataURL(file);
            } else {
                filePreview.classList.add('hidden');
            }
        });
        
        // Multi file preview functionality
        const mediaFilesInput = document.getElementById('media_files');
        const multiFilePreview = document.getElementById('multi_file_preview');
        const multiFileList = document.getElementById('multi_file_list');
        
        mediaFilesInput.addEventListener('change', function() {
            if (this.files && this.files.length > 0) {
                multiFileList.innerHTML = '';
                
                for (let i = 0; i < this.files.length; i++) {
                    const file = this.files[i];
                    const fileDiv = document.createElement('div');
                    fileDiv.className = 'border rounded-md p-2 <?php echo $darkMode ? "border-gray-700 bg-gray-800" : "border-gray-200"; ?>';
                    
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        fileDiv.innerHTML = `
                            <img src="${e.target.result}" class="w-full h-24 object-contain mb-2" alt="${file.name}" />
                            <p class="text-xs truncate <?php echo $darkMode ? "text-gray-300" : "text-gray-700"; ?>">${file.name}</p>
                        `;
                    }
                    reader.readAsDataURL(file);
                    
                    multiFileList.appendChild(fileDiv);
                }
                
                multiFilePreview.classList.remove('hidden');
            } else {
                multiFilePreview.classList.add('hidden');
            }
        });
        
        // External URL preview
        const externalUrlInput = document.getElementById('external_url');
        const externalPreview = document.getElementById('external_preview');
        const externalImagePreview = document.getElementById('external_image_preview');
        
        externalUrlInput.addEventListener('input', debounce(function() {
            if (this.value.trim() !== '') {
                externalImagePreview.src = this.value;
                externalPreview.classList.remove('hidden');
                
                // Handle load errors
                externalImagePreview.onerror = function() {
                    externalPreview.classList.add('hidden');
                };
                
                externalImagePreview.onload = function() {
                    externalPreview.classList.remove('hidden');
                };
            } else {
                externalPreview.classList.add('hidden');
            }
        }, 500));
        
        // Trigger preview for external URL if value exists
        if (externalUrlInput && externalUrlInput.value.trim() !== '') {
            externalImagePreview.src = externalUrlInput.value;
            externalPreview.classList.remove('hidden');
        }
        
        // Tag input functionality with autocomplete
        const tagsInput = document.getElementById('tags_input');
        const tagSuggestions = document.createElement('div');
        tagSuggestions.className = 'tag-suggestions hidden absolute z-10 mt-1 w-full rounded-md bg-white shadow-lg max-h-60 overflow-auto focus:outline-none <?php echo $darkMode ? "bg-gray-700" : ""; ?>';
        
        tagsInput.parentNode.style.position = 'relative';
        tagsInput.parentNode.appendChild(tagSuggestions);
        
        // Debounce function to limit how often a function can run
        function debounce(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        }
        
        // Visual styling for upload options
        document.querySelectorAll('.upload-option').forEach(option => {
            option.addEventListener('click', function() {
                // First reset all options
                document.querySelectorAll('.upload-option').forEach(opt => {
                    opt.classList.remove('ring', 'ring-blue-500');
                    if (<?php echo $darkMode ? 'true' : 'false'; ?>) {
                        opt.classList.remove('border-blue-500');
                        opt.classList.add('border-gray-700');
                    } else {
                        opt.classList.remove('border-blue-400');
                        opt.classList.add('border-gray-200');
                    }
                });
                
                // Then highlight the clicked one
                this.classList.add('ring', 'ring-blue-500');
                if (<?php echo $darkMode ? 'true' : 'false'; ?>) {
                    this.classList.remove('border-gray-700');
                    this.classList.add('border-blue-500');
                } else {
                    this.classList.remove('border-gray-200');
                    this.classList.add('border-blue-400');
                }
                
                // Select the radio button
                this.querySelector('input[type="radio"]').checked = true;
                this.querySelector('input[type="radio"]').dispatchEvent(new Event('change'));
            });
        });
        
        // Initially highlight the selected option
        const selectedOption = document.querySelector('input[name="upload_type"]:checked').closest('.upload-option');
        selectedOption.classList.add('ring', 'ring-blue-500');
        if (<?php echo $darkMode ? 'true' : 'false'; ?>) {
            selectedOption.classList.remove('border-gray-700');
            selectedOption.classList.add('border-blue-500');
        } else {
            selectedOption.classList.remove('border-gray-200');
            selectedOption.classList.add('border-blue-400');
        }
    });
</script>

<?php
// Set current date and time for footer
$currentDateTime = '2025-03-19 02:40:37';
$currentUser = 'mahranalsarminy';

// Include admin panel footer
include ROOT_DIR . '/theme/admin/footer.php';
?>
