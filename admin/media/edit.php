<?php
/**
 * Media Management - Edit Media
 * 
 * Allows administrators to edit existing media items with metadata.
 * Supports updating metadata and changing media files/URLs.
 * 
 * @package WallPix
 * @version 1.0.0
 */

// Define the project root directory
define('ROOT_DIR', dirname(dirname(__DIR__)));

// Include necessary files
require_once ROOT_DIR . '/includes/init.php';

// Set page title
$pageTitle = 'Edit Media';

// Initialize variables
$errors = [];
$success = false;
$media_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if media ID is provided
if (!$media_id) {
    header('Location: index.php');
    exit;
}

// Get the media item
try {
    $stmt = $pdo->prepare("
        SELECT m.*, c.name as category_name
        FROM media m
        LEFT JOIN categories c ON m.category_id = c.id
        WHERE m.id = ?
    ");
    $stmt->execute([$media_id]);
    $media = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$media) {
        header('Location: index.php');
        exit;
    }
    
    // Get media tags
    $tags_stmt = $pdo->prepare("
        SELECT t.id, t.name 
        FROM tags t 
        JOIN media_tags mt ON t.id = mt.tag_id 
        WHERE mt.media_id = ?
    ");
    $tags_stmt->execute([$media_id]);
    $media_tags = $tags_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert tags array to comma-separated string
    $tags_input = implode(', ', array_column($media_tags, 'name'));
    
    // Get associated colors
    $colors_stmt = $pdo->prepare("
        SELECT color_id 
        FROM media_colors 
        WHERE media_id = ?
    ");
    $colors_stmt->execute([$media_id]);
    $media_colors = $colors_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Get associated resolution
    $resolution_stmt = $pdo->prepare("
        SELECT resolution_id 
        FROM media_resolutions 
        WHERE media_id = ? 
        LIMIT 1
    ");
    $resolution_stmt->execute([$media_id]);
    $resolution_id = $resolution_stmt->fetchColumn() ?: 0;
    
} catch (PDOException $e) {
    $errors[] = "Database error: " . $e->getMessage();
}

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
    $change_media = isset($_POST['change_media']) && $_POST['change_media'] === '1';
    $media_type = $_POST['media_type'] ?? 'file';
    
    // Basic validation
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    
    if (empty($orientation) || !in_array($orientation, ['portrait', 'landscape'])) {
        $errors[] = "Please select a valid orientation";
    }
    
    // Variables to store file/URL updates
    $file_path = $media['file_path'];
    $file_name = $media['file_name'];
    $thumbnail_url = $media['thumbnail_url'];
    $external_url = $media['external_url'];
    $width = $media['width'];
    $height = $media['height'];
    $file_type = $media['file_type'];
    $file_size = $media['file_size'];
    
    // Process media change if requested
    if ($change_media) {
        if ($media_type === 'file') {
            // Process file upload
            if (!isset($_FILES['media_file']) || $_FILES['media_file']['error'] == UPLOAD_ERR_NO_FILE) {
                $errors[] = "Please select a file to upload";
            } else {
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
                } else {
                    // Check file type
                    $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                    if (!in_array($file['type'], $allowed_types)) {
                        $errors[] = "Invalid file type. Allowed types: JPEG, PNG, GIF, WEBP";
                    }
                    
                    // Check file size (10MB max)
                    if ($file['size'] > 10 * 1024 * 1024) {
                        $errors[] = "File size exceeds the maximum limit (10MB)";
                    }
                    
                    // Process upload if no errors
                    if (empty($errors)) {
                        // Generate unique filename
                        $new_filename = uniqid() . '_' . basename($file['name']);
                        
                        // Create upload directory if it doesn't exist
                        $upload_dir = ROOT_DIR . '/uploads/media/' . date('Y/m');
                        if (!is_dir($upload_dir)) {
                            mkdir($upload_dir, 0755, true);
                        }
                        
                        // Set destination path
                        $destination = $upload_dir . '/' . $new_filename;
                        
                        // Move uploaded file
                        if (move_uploaded_file($file['tmp_name'], $destination)) {
                            $file_path = '/uploads/media/' . date('Y/m') . '/' . $new_filename;
                            $file_name = $new_filename;
                            $file_type = $file['type'];
                            $file_size = formatFileSize($file['size']);
                            
                            // Create thumbnail
                            $thumbnail_dir = ROOT_DIR . '/uploads/thumbnails/' . date('Y/m');
                            if (!is_dir($thumbnail_dir)) {
                                mkdir($thumbnail_dir, 0755, true);
                            }
                            
                            $thumbnail_file = $thumbnail_dir . '/thumb_' . $new_filename;
                            
                            // Generate thumbnail
                            if (create_thumbnail($destination, $thumbnail_file, 300, 300)) {
                                $thumbnail_url = '/uploads/thumbnails/' . date('Y/m') . '/thumb_' . $new_filename;
                            } else {
                                // Use original as thumbnail if generation fails
                                $thumbnail_url = $file_path;
                            }
                            
                            // Get image dimensions
                            if ($image_info = getimagesize($destination)) {
                                $width = $image_info[0];
                                $height = $image_info[1];
                            }
                            
                            // Clear external URL as we now have a file
                            $external_url = '';
                        } else {
                            $errors[] = "Failed to move uploaded file";
                        }
                    }
                }
            }
        } else if ($media_type === 'url') {
            // Process external URL
            $new_external_url = trim($_POST['external_url'] ?? '');
            
            if (empty($new_external_url)) {
                $errors[] = "External URL is required";
            } elseif (!filter_var($new_external_url, FILTER_VALIDATE_URL)) {
                $errors[] = "Please enter a valid URL";
            } else {
                // Update URL fields
                $external_url = $new_external_url;
                $thumbnail_url = $new_external_url;
                
                // Clear file data as we now have a URL
                $file_path = '';
                $file_name = '';
                $width = null;
                $height = null;
                $file_type = '';
                $file_size = '';
            }
        }
    }
    
    // Save updates to database if no errors
    if (empty($errors)) {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Update media record
            $stmt = $pdo->prepare("
                UPDATE media SET
                    title = :title,
                    description = :description,
                    category_id = :category_id,
                    orientation = :orientation,
                    file_name = :file_name,
                    file_path = :file_path,
                    file_type = :file_type,
                    file_size = :file_size,
                    thumbnail_url = :thumbnail_url,
                    external_url = :external_url,
                    status = :status,
                    featured = :featured,
                    width = :width,
                    height = :height,
                    updated_at = NOW()
                WHERE id = :id
            ");
            
            $stmt->execute([
                ':title' => $title,
                ':description' => $description,
                ':category_id' => $category_id ?: null,
                ':orientation' => $orientation,
                ':file_name' => $file_name,
                ':file_path' => $file_path,
                ':file_type' => $file_type,
                ':file_size' => $file_size,
                ':thumbnail_url' => $thumbnail_url,
                ':external_url' => $external_url,
                ':status' => $status,
                ':featured' => $featured,
                ':width' => $width,
                ':height' => $height,
                ':id' => $media_id
            ]);
            
            // Process tags - first remove existing tags
            $pdo->prepare("DELETE FROM media_tags WHERE media_id = ?")->execute([$media_id]);
            
            // Then add new tags
            if (!empty($tags_input)) {
                processMediaTags($pdo, $media_id, $tags_input);
            }
            
            // Process colors - first remove existing color associations
            $pdo->prepare("DELETE FROM media_colors WHERE media_id = ?")->execute([$media_id]);
            
            // Then add new color associations
            if (!empty($color_ids)) {
                $color_stmt = $pdo->prepare("INSERT INTO media_colors (media_id, color_id) VALUES (:media_id, :color_id)");
                foreach ($color_ids as $color_id) {
                    $color_stmt->execute([
                        ':media_id' => $media_id,
                        ':color_id' => $color_id
                    ]);
                }
            }
            
            // Process resolution - first remove existing resolution associations
            $pdo->prepare("DELETE FROM media_resolutions WHERE media_id = ?")->execute([$media_id]);
            
            // Then add new resolution if selected
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
                    ':description' => "Updated media item #$media_id: $title"
                ]);
            
            $success = true;
            
            // Refresh media data
            $stmt = $pdo->prepare("
                SELECT m.*, c.name as category_name
                FROM media m
                LEFT JOIN categories c ON m.category_id = c.id
                WHERE m.id = ?
            ");
            $stmt->execute([$media_id]);
            $media = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            // Rollback transaction
            $pdo->rollBack();
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
}
/**
 * Process tags from comma-separated string
 * Creates new tags if they don't exist
 */
function processMediaTags($pdo, $media_id, $tags_input) {
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
 * Format file size in human-readable format
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
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
                        <i class="fas fa-edit mr-2"></i> Edit Media #<?= $media_id ?>
                    </h1>
                    <div class="flex space-x-3">
                        <a href="index.php" class="btn bg-gray-500 hover:bg-gray-700 text-white">
                            <i class="fas fa-arrow-left mr-2"></i> Back to Media List
                        </a>
                        <a href="view.php?id=<?= $media_id ?>" class="btn bg-blue-500 hover:bg-blue-700 text-white">
                            <i class="fas fa-eye mr-2"></i> View Media
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
                                <p class="font-medium">Media has been updated successfully!</p>
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

                <!-- Edit Media Form -->
                <div class="bg-white <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?> rounded-lg shadow-md p-6">
                    <form action="edit.php?id=<?= $media_id ?>" method="post" enctype="multipart/form-data" class="space-y-6">
                        <!-- Current Media Preview -->
                        <div class="mb-6">
                            <h2 class="text-xl font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">Current Media</h2>
                            <div class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-6">
                                <div class="md:w-1/3">
                                    <div class="border rounded-lg overflow-hidden <?php echo $darkMode ? 'border-gray-700' : 'border-gray-200'; ?>">
                                        <?php if (!empty($media['thumbnail_url'])): ?>
                                            <img src="<?= htmlspecialchars($media['thumbnail_url']) ?>" alt="<?= htmlspecialchars($media['title']) ?>" class="w-full h-auto object-contain">
                                        <?php else: ?>
                                            <div class="bg-gray-200 <?php echo $darkMode ? 'bg-gray-700' : ''; ?> text-center p-8">
                                                <i class="fas fa-image text-4xl text-gray-400"></i>
                                                <p class="mt-2 text-sm text-gray-500">No image available</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="md:w-2/3 space-y-2">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-sm font-medium <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">Title:</p>
                                            <p class="<?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?> font-semibold"><?= htmlspecialchars($media['title']) ?></p>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">Status:</p>
                                            <p>
                                                <?php if ($media['status']): ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 <?php echo $darkMode ? 'bg-green-900 text-green-200' : ''; ?>">
                                                        <i class="fas fa-check-circle mr-1"></i> Active
                                                    </span>
                                                <?php else: ?>
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 <?php echo $darkMode ? 'bg-red-900 text-red-200' : ''; ?>">
                                                        <i class="fas fa-times-circle mr-1"></i> Inactive
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <?php if ($media['featured']): ?>
                                                    <span class="ml-2 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 <?php echo $darkMode ? 'bg-purple-900 text-purple-200' : ''; ?>">
                                                        <i class="fas fa-star mr-1"></i> Featured
                                                    </span>
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">Category:</p>
                                            <p class="<?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>"><?= htmlspecialchars($media['category_name'] ?? 'None') ?></p>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">Orientation:</p>
                                            <p class="<?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                                                <?php if ($media['orientation'] === 'portrait'): ?>
                                                    <i class="fas fa-mobile-alt mr-1"></i> Portrait
                                                <?php else: ?>
                                                    <i class="fas fa-laptop mr-1"></i> Landscape
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <?php if (!empty($media['width']) && !empty($media['height'])): ?>
                                        <div>
                                            <p class="text-sm font-medium <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">Dimensions:</p>
                                            <p class="<?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>"><?= $media['width'] ?> × <?= $media['height'] ?></p>
                                        </div>
                                        <?php endif; ?>
                                        <div>
                                            <p class="text-sm font-medium <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">Source:</p>
                                            <p class="<?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                                                <?php if (!empty($media['external_url'])): ?>
                                                    <i class="fas fa-link mr-1"></i> External URL
                                                <?php else: ?>
                                                    <i class="fas fa-file-upload mr-1"></i> Uploaded File
                                                <?php endif; ?>
                                            </p>
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">Created:</p>
                                            <p class="<?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>"><?= date('M j, Y g:i A', strtotime($media['created_at'])) ?></p>
                                        </div>
                                        <div>
                                                                                        <p class="text-sm font-medium <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">Created:</p>
                                            <p class="<?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>"><?= date('M j, Y g:i A', strtotime($media['created_at'])) ?></p>
                                        </div>
                                    </div>
                                    
                                    <!-- Description -->
                                    <div class="mt-4">
                                        <p class="text-sm font-medium <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">Description:</p>
                                        <p class="<?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>"><?= nl2br(htmlspecialchars($media['description'] ?? 'No description')) ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Media Change Option -->
                        <div class="mb-6 border-t border-b py-4 <?php echo $darkMode ? 'border-gray-700' : 'border-gray-200'; ?>">
                            <div class="flex items-start">
                                <div class="flex h-5 items-center">
                                    <input id="change_media" name="change_media" type="checkbox" value="1" 
                                        class="h-4 w-4 rounded border-gray-300 text-blue-600 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : ''; ?>"
                                        onchange="document.getElementById('media_change_options').classList.toggle('hidden')">
                                </div>
                                <div class="ml-3 text-sm">
                                    <label for="change_media" class="font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                        Change Media Content
                                    </label>
                                    <p class="text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                                        Select this option to upload a new file or change the external URL
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Media Change Options (hidden by default) -->
                            <div id="media_change_options" class="hidden mt-4">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <!-- File Upload Option -->
                                    <div class="border rounded-lg p-3 <?php echo $darkMode ? 'border-gray-700 hover:border-blue-500' : 'border-gray-200 hover:border-blue-400'; ?> cursor-pointer media-option">
                                        <input type="radio" id="media_type_file" name="media_type" value="file" checked 
                                            class="sr-only media-option-input" data-target="file_upload_section">
                                        <label for="media_type_file" class="flex items-center cursor-pointer">
                                            <span class="w-6 h-6 rounded-full border inline-flex items-center justify-center mr-3 
                                                <?php echo $darkMode ? 'border-gray-600' : 'border-gray-300'; ?>">
                                                <span class="w-3 h-3 rounded-full bg-blue-500 hidden option-selected"></span>
                                            </span>
                                            <div>
                                                <p class="font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                                                    <i class="fas fa-file-upload mr-2 text-blue-500"></i> Upload New File
                                                </p>
                                                <p class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                                                    Replace with a new image file
                                                </p>
                                            </div>
                                        </label>
                                    </div>
                                    
                                    <!-- External URL Option -->
                                    <div class="border rounded-lg p-3 <?php echo $darkMode ? 'border-gray-700 hover:border-blue-500' : 'border-gray-200 hover:border-blue-400'; ?> cursor-pointer media-option">
                                        <input type="radio" id="media_type_url" name="media_type" value="url" 
                                            class="sr-only media-option-input" data-target="external_url_section">
                                        <label for="media_type_url" class="flex items-center cursor-pointer">
                                            <span class="w-6 h-6 rounded-full border inline-flex items-center justify-center mr-3 
                                                <?php echo $darkMode ? 'border-gray-600' : 'border-gray-300'; ?>">
                                                <span class="w-3 h-3 rounded-full bg-blue-500 hidden option-selected"></span>
                                            </span>
                                            <div>
                                                <p class="font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                                                    <i class="fas fa-link mr-2 text-purple-500"></i> Use External URL
                                                </p>
                                                <p class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                                                    Link to an image on another website
                                                </p>
                                            </div>
                                        </label>
                                    </div>
                                </div>
                                
                                <!-- File Upload Section -->
                                <div id="file_upload_section" class="upload-section border border-gray-200 rounded-lg p-4 mt-4 <?php echo $darkMode ? 'border-gray-700' : ''; ?>">
                                    <div class="flex items-center justify-center w-full">
                                        <label for="media_file" class="flex flex-col items-center justify-center w-full h-48 border-2 border-gray-300 border-dashed rounded-lg cursor-pointer bg-gray-50 hover:bg-gray-100 <?php echo $darkMode ? 'bg-gray-700 hover:bg-gray-600 border-gray-600' : ''; ?>">
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
                                
                                <!-- External URL Section -->
                                <div id="external_url_section" class="upload-section border border-gray-200 rounded-lg p-4 mt-4 hidden <?php echo $darkMode ? 'border-gray-700' : ''; ?>">
                                    <div class="mb-4">
                                        <label for="external_url" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">External URL</label>
                                        <input type="url" id="external_url" name="external_url" value="<?= htmlspecialchars($media['external_url']) ?>"
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
                            </div>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Basic Information -->
                            <div class="space-y-6">
                                <h2 class="text-xl font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">Basic Information</h2>
                                
                                <div class="mb-4">
                                    <label for="title" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">Title <span class="text-red-600">*</span></label>
                                    <input type="text" id="title" name="title" value="<?= htmlspecialchars($media['title']) ?>" required
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                        placeholder="Enter media title">
                                </div>
                                
                                <div class="mb-4">
                                    <label for="description" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">Description</label>
                                    <textarea id="description" name="description" rows="4"
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                        placeholder="Enter media description"><?= htmlspecialchars($media['description']) ?></textarea>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="category_id" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">Category</label>
                                    <select id="category_id" name="category_id" 
                                        class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                        <option value="0">Select a Category</option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?= $category['id'] ?>" <?= $media['category_id'] == $category['id'] ? 'selected' : '' ?>>
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
                                                    <?= in_array($color['id'], $media_colors) ? 'checked' : '' ?>
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
                                            <input type="radio" id="orientation_portrait" name="orientation" value="portrait" <?= $media['orientation'] === 'portrait' ? 'checked' : '' ?>
                                                class="w-4 h-4 text-blue-600 border-gray-300 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : ''; ?>">
                                            <label for="orientation_portrait" class="ml-2 block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                                <i class="fas fa-mobile-alt mr-1"></i> Portrait (9:16)
                                            </label>
                                        </div>
                                        <div class="flex items-center">
                                            <input type="radio" id="orientation_landscape" name="orientation" value="landscape" <?= $media['orientation'] === 'landscape' ? 'checked' : '' ?>
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
                                            <input id="status" name="status" type="checkbox" <?= $media['status'] ? 'checked' : '' ?>
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
                                            <input id="featured" name="featured" type="checkbox" <?= $media['featured'] ? 'checked' : '' ?>
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
                            <a href="index.php" class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                <i class="fas fa-times mr-2"></i> Cancel
                            </a>
                            <button type="submit" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline">
                                <i class="fas fa-save mr-2"></i> Update Media
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
        // Media type selection
        const mediaTypeOptions = document.querySelectorAll('.media-option-input');
        const uploadSections = document.querySelectorAll('.upload-section');
        
        mediaTypeOptions.forEach(option => {
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
                
                this.closest('.media-option').querySelector('.option-selected').classList.remove('hidden');
            });
        });
        
        // Show selected state for default option
        document.querySelector('input[name="media_type"]:checked')
            .closest('.media-option')
            .querySelector('.option-selected')
            .classList.remove('hidden');
        
        // File preview functionality
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
        
        // Visual styling for media options
        document.querySelectorAll('.media-option').forEach(option => {
            option.addEventListener('click', function() {
                // First reset all options
                document.querySelectorAll('.media-option').forEach(opt => {
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
        const selectedOption = document.querySelector('input[name="media_type"]:checked');
        if (selectedOption) {
            selectedOption.closest('.media-option').classList.add('ring', 'ring-blue-500');
            if (<?php echo $darkMode ? 'true' : 'false'; ?>) {
                selectedOption.closest('.media-option').classList.remove('border-gray-700');
                selectedOption.closest('.media-option').classList.add('border-blue-500');
            } else {
                selectedOption.closest('.media-option').classList.remove('border-gray-200');
                selectedOption.closest('.media-option').classList.add('border-blue-400');
            }
        }
        
        // Trigger preview for external URL if value exists
        if (externalUrlInput && externalUrlInput.value.trim() !== '') {
            externalImagePreview.src = externalUrlInput.value;
            externalPreview.classList.remove('hidden');
        }
    });
</script>

<?php
// Set current date and time for footer
$currentDateTime = '2025-03-19 02:57:48';
$currentUser = 'mahranalsarminy';

// Include admin panel footer
include ROOT_DIR . '/theme/admin/footer.php';
?>