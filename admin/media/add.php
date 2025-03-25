<?php
/**
 * Media Add Page
 *
 * @package WallPix
 * @version 1.0.0
 */
// تمكين تسجيل الأخطاء (للتطوير فقط)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Define the project root directory
define('ROOT_DIR', dirname(dirname(__DIR__)));

// Include necessary files
require_once ROOT_DIR . '/includes/init.php';

// Verify admin login
require_admin();

// Current user and datetime
$current_user = $_SESSION['user_name'] ?? 'mahranalsarminy';
$current_datetime = '2025-03-24 06:30:06'; // Using the provided UTC timestamp

// Set page title
$pageTitle = 'Add New Media';

// Get categories for dropdown
$stmt = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY sort_order ASC, name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get colors for dropdown
$stmt = $pdo->query("SELECT id, color_name, hex_code FROM colors ORDER BY color_name ASC");
$colors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get all tags for autocomplete suggestions
$stmt = $pdo->query("SELECT name FROM tags ORDER BY name ASC");
$all_tags = $stmt->fetchAll(PDO::FETCH_COLUMN);
$tags_json = json_encode($all_tags);

// Get licenses for dropdown
$stmt = $pdo->query("SELECT id, name FROM media_licenses ORDER BY name ASC");
$licenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get predefined resolutions
$resolutions = [
    'HD' => '1280x720',
    'Full HD' => '1920x1080',
    'QHD' => '2560x1440',
    '4K UHD' => '3840x2160',
    '8K UHD' => '7680x4320',
    'Mobile HD' => '750x1334',
    'Mobile Full HD' => '1080x1920',
    'Mobile QHD' => '1440x2560',
    'Mobile 4K' => '2160x3840',
    'Square Small' => '600x600',
    'Square Medium' => '1080x1080',
    'Square Large' => '2000x2000'
];

// Supported file formats
$supported_types = [
    'image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml',
    'video/mp4', 'video/webm', 'video/ogg'
];
$supported_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg', 'mp4', 'webm', 'ogg'];

// Helper function to create thumbnail
function create_thumbnail($source_path, $thumb_path, $max_size = 300) {
    // Get image type
    $image_info = getimagesize($source_path);
    $image_type = $image_info[2];
    
    // Create source image based on file type
    if ($image_type == IMAGETYPE_JPEG) {
        $source_image = imagecreatefromjpeg($source_path);
    } elseif ($image_type == IMAGETYPE_PNG) {
        $source_image = imagecreatefrompng($source_path);
    } elseif ($image_type == IMAGETYPE_GIF) {
        $source_image = imagecreatefromgif($source_path);
    } elseif ($image_type == IMAGETYPE_WEBP && function_exists('imagecreatefromwebp')) {
        $source_image = imagecreatefromwebp($source_path);
    } else {
        return false;
    }
    
    // Get dimensions
    $source_width = imagesx($source_image);
    $source_height = imagesy($source_image);
    
    // Calculate thumbnail dimensions
    if ($source_width > $source_height) {
        $thumb_width = $max_size;
        $thumb_height = intval($source_height * ($max_size / $source_width));
    } else {
        $thumb_height = $max_size;
        $thumb_width = intval($source_width * ($max_size / $source_height));
    }
    
    // Create thumbnail
    $thumb_image = imagecreatetruecolor($thumb_width, $thumb_height);
    
    // Preserve transparency for PNG and GIF
    if ($image_type == IMAGETYPE_PNG || $image_type == IMAGETYPE_GIF) {
        imagealphablending($thumb_image, false);
        imagesavealpha($thumb_image, true);
        $transparent = imagecolorallocatealpha($thumb_image, 255, 255, 255, 127);
        imagefilledrectangle($thumb_image, 0, 0, $thumb_width, $thumb_height, $transparent);
    }
    
    // Resize
    imagecopyresampled(
        $thumb_image, $source_image,
        0, 0, 0, 0,
        $thumb_width, $thumb_height, $source_width, $source_height
    );
    
    // Save thumbnail
    if ($image_type == IMAGETYPE_JPEG) {
        imagejpeg($thumb_image, $thumb_path, 90);
    } elseif ($image_type == IMAGETYPE_PNG) {
        imagepng($thumb_image, $thumb_path, 9);
    } elseif ($image_type == IMAGETYPE_GIF) {
        imagegif($thumb_image, $thumb_path);
    } elseif ($image_type == IMAGETYPE_WEBP && function_exists('imagewebp')) {
        imagewebp($thumb_image, $thumb_path, 80);
    }
    
    // Free memory
    imagedestroy($source_image);
    imagedestroy($thumb_image);
    
    return true;
}

// Helper function to format file size
function format_file_size($size) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $i = 0;
    while ($size >= 1024 && $i < count($units) - 1) {
        $size /= 1024;
        $i++;
    }
    return round($size, 2) . ' ' . $units[$i];
}

// Helper function to create a slug from text
function create_slug($text) {
    // Convert to lowercase
    $slug = strtolower($text);
    // Replace non-alphanumeric characters with hyphens
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    // Remove leading/trailing hyphens
    $slug = trim($slug, '-');
    // If empty, provide a default
    if (empty($slug)) {
        $slug = 'tag-' . uniqid();
    }
    return $slug;
}
// Process form submission
$errors = [];
$success = false;
$batch_results = [];
$batch_mode = false;
$uploadMode = isset($_POST['upload_mode']) ? $_POST['upload_mode'] : 'single';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check if we're in batch mode
    $batch_mode = ($uploadMode === 'batch');
    
    // Common values for all uploads
    $category_id = intval($_POST['category_id'] ?? 0);
    $orientation = $_POST['orientation'] ?? 'portrait';
    $background_color = $_POST['background_color'] ?? '#FFFFFF';
    $resolution = $_POST['resolution'] ?? '';
    $custom_width = intval($_POST['custom_width'] ?? 0);
    $custom_height = intval($_POST['custom_height'] ?? 0);
    $owner = trim($_POST['owner'] ?? '');
    $license = trim($_POST['license'] ?? '');
    $featured = isset($_POST['featured']) ? 1 : 0;
    $status = isset($_POST['status']) ? 1 : 0;
    $paid_content = isset($_POST['paid_content']) ? 1 : 0;
    $watermark_text = trim($_POST['watermark_text'] ?? '');
    $ai_enhanced = isset($_POST['ai_enhanced']) ? 1 : 0;
    $tag_input = trim($_POST['tag_input'] ?? '');
    $selected_colors = $_POST['colors'] ?? [];
    $size_type = $_POST['size_type'] ?? '';
    
    // Set width and height based on resolution
    if ($resolution === 'custom' && ($custom_width > 0 && $custom_height > 0)) {
        $width = $custom_width;
        $height = $custom_height;
    } elseif (!empty($resolution) && isset($resolutions[$resolution])) {
        list($width, $height) = explode('x', $resolutions[$resolution]);
    } else {
        $width = '';
        $height = '';
    }
    
    // Process tags (comma-separated)
    $tags = [];
    if (!empty($tag_input)) {
        $tag_names = array_map('trim', explode(',', $tag_input));
        foreach ($tag_names as $tag_name) {
            if (!empty($tag_name)) {
                $tags[] = $tag_name;
            }
        }
    }
    
    // Validate required fields
    if (empty($category_id)) {
        $errors[] = 'Category is required';
    }
    
    if (empty($errors)) {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            if ($batch_mode) {
                // Batch upload mode processing
                if (!empty($_FILES['batch_files']['name'][0])) {
                    // Create batch record
                    $batch_name = "Batch Upload - " . date('Y-m-d H:i:s');
                    $batch_stmt = $pdo->prepare("INSERT INTO media_batches (batch_name, user_id, status, total_files, created_at) VALUES (?, ?, 'processing', ?, NOW())");
                    $batch_stmt->execute([$batch_name, $_SESSION['user_id'] ?? 1, count($_FILES['batch_files']['name'])]);
                    $batch_id = $pdo->lastInsertId();
                    
                    // Process each file in the batch
                    for ($i = 0; $i < count($_FILES['batch_files']['name']); $i++) {
                        if ($_FILES['batch_files']['error'][$i] === UPLOAD_ERR_OK) {
                            $file_name = uniqid() . '_' . basename($_FILES['batch_files']['name'][$i]);
                            $file_type = $_FILES['batch_files']['type'][$i];
                            $file_tmp = $_FILES['batch_files']['tmp_name'][$i];
                            $file_error = $_FILES['batch_files']['error'][$i];
                            $file_size = $_FILES['batch_files']['size'][$i];
                            
                            // Extract title from filename (without extension)
                            $file_title = pathinfo($_FILES['batch_files']['name'][$i], PATHINFO_FILENAME);
                            $file_title = str_replace(['_', '-'], ' ', $file_title);
                            $file_title = ucfirst($file_title);
                            
                            // Create a record in media_batch_items
                            $batch_item_stmt = $pdo->prepare("INSERT INTO media_batch_items (batch_id, file_name, status, created_at) VALUES (?, ?, 'pending', NOW())");
                            $batch_item_stmt->execute([$batch_id, $_FILES['batch_files']['name'][$i]]);
                            $batch_item_id = $pdo->lastInsertId();
                            
                            // Check if it's a supported file type
                            $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                            
                            if (!in_array($file_type, $supported_types) && !in_array($file_extension, $supported_extensions)) {
                                $update_item_stmt = $pdo->prepare("UPDATE media_batch_items SET status = 'failed', error_message = ? WHERE id = ?");
                                $update_item_stmt->execute(["Unsupported file type: {$file_type}", $batch_item_id]);
                                $batch_results[] = ["file" => $_FILES['batch_files']['name'][$i], "status" => "failed", "message" => "Unsupported file type"];
                                continue;
                            }
                            
                            // Upload directory
                            $upload_dir = '../../uploads/media/' . date('Y/m/');
                            if (!is_dir($upload_dir)) {
                                mkdir($upload_dir, 0755, true);
                            }
                            
                            $file_path = $upload_dir . $file_name;
                            $relative_path = '/uploads/media/' . date('Y/m/') . $file_name;
                            
                            // Move uploaded file
                            if (move_uploaded_file($file_tmp, $file_path)) {
                                $thumbnail_url = '';
                                
                                // Generate thumbnail if it's an image
                                if (strpos($file_type, 'image/') === 0 || in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                                    // Get image dimensions if not manually set
                                    $img_dimensions = getimagesize($file_path);
                                    $actual_width = $img_dimensions[0];
                                    $actual_height = $img_dimensions[1];
                                    
                                    // Use actual dimensions if no custom dimensions provided
                                    if (empty($width) || empty($height)) {
                                        $width = $actual_width;
                                        $height = $actual_height;
                                    }
                                    
                                    // Create thumbnail directory
                                    $thumb_dir = '../../uploads/thumbnails/' . date('Y/m/');
                                    if (!is_dir($thumb_dir)) {
                                        mkdir($thumb_dir, 0755, true);
                                    }
                                    
                                    $thumbnail_path = $thumb_dir . 'thumb_' . $file_name;
                                    $thumbnail_relative_path = '/uploads/thumbnails/' . date('Y/m/') . 'thumb_' . $file_name;
                                    
                                    // Create thumbnail
                                    create_thumbnail($file_path, $thumbnail_path, 300);
                                    $thumbnail_url = $thumbnail_relative_path;
                                    
                                    // Detect orientation if not specified
                                    if ($orientation === 'auto') {
                                        $orientation = ($actual_width > $actual_height) ? 'landscape' : 'portrait';
                                    }
                                } 
                                // For video files, generate a thumbnail using ffmpeg if available
                                elseif (strpos($file_type, 'video/') === 0 || in_array($file_extension, ['mp4', 'webm', 'ogg'])) {
                                    // Create thumbnail directory
                                    $thumb_dir = '../../uploads/thumbnails/' . date('Y/m/');
                                    if (!is_dir($thumb_dir)) {
                                        mkdir($thumb_dir, 0755, true);
                                    }
                                    
                                    $thumbnail_path = $thumb_dir . 'thumb_' . pathinfo($file_name, PATHINFO_FILENAME) . '.jpg';
                                    $thumbnail_relative_path = '/uploads/thumbnails/' . date('Y/m/') . 'thumb_' . pathinfo($file_name, PATHINFO_FILENAME) . '.jpg';
                                    
                                    // Try to generate thumbnail with ffmpeg if available
                                    if (function_exists('exec')) {
                                        exec("ffmpeg -i {$file_path} -ss 00:00:01 -vframes 1 {$thumbnail_path} 2>&1", $output, $return_var);
                                        if ($return_var === 0) {
                                            $thumbnail_url = $thumbnail_relative_path;
                                        } else {
                                            $thumbnail_url = '/assets/images/video_placeholder.jpg'; // Default video thumbnail
                                        }
                                    } else {
                                        $thumbnail_url = '/assets/images/video_placeholder.jpg'; // Default video thumbnail
                                    }
                                }
                                
                                // Get file size
                                $formatted_file_size = format_file_size($file_size);
                                
                                // Insert into media table
                                $stmt = $pdo->prepare("INSERT INTO media (
                                    title, description, category_id, file_name, file_path, file_type, file_size,
                                    thumbnail_url, status, featured, width, height, size_type, background_color,
                                    orientation, owner, license, paid_content, watermark_text, original_filename,
                                    created_by, ai_enhanced, created_at
                                ) VALUES (
                                    :title, :description, :category_id, :file_name, :file_path, :file_type, :file_size,
                                    :thumbnail_url, :status, :featured, :width, :height, :size_type, :background_color,
                                    :orientation, :owner, :license, :paid_content, :watermark_text, :original_filename,
                                    :created_by, :ai_enhanced, NOW()
                                )");
                                
                                $stmt->execute([
                                    ':title' => $file_title,
                                    ':description' => "Uploaded in batch {$batch_name}",
                                    ':category_id' => $category_id,
                                    ':file_name' => $file_name,
                                    ':file_path' => $relative_path,
                                    ':file_type' => $file_type,
                                    ':file_size' => $formatted_file_size,
                                    ':thumbnail_url' => $thumbnail_url,
                                    ':status' => $status,
                                    ':featured' => $featured,
                                    ':width' => $width,
                                    ':height' => $height,
                                    ':size_type' => $size_type,
                                    ':background_color' => $background_color,
                                    ':orientation' => $orientation,
                                    ':owner' => $owner,
                                    ':license' => $license,
                                    ':paid_content' => $paid_content,
                                    ':watermark_text' => $watermark_text,
                                    ':original_filename' => $_FILES['batch_files']['name'][$i],
                                    ':created_by' => $_SESSION['user_id'] ?? 1,
                                    ':ai_enhanced' => $ai_enhanced
                                ]);
                                
                                $media_id = $pdo->lastInsertId();
                                                                // Process and insert tags
                                if (!empty($tags)) {
                                    foreach ($tags as $tag_name) {
                                        // Check if tag already exists
                                        $check_stmt = $pdo->prepare("SELECT id FROM tags WHERE name = :name");
                                        $check_stmt->execute([':name' => $tag_name]);
                                        $tag_id = $check_stmt->fetchColumn();
                                        
                                        // If tag doesn't exist, create it with slug
                                        if (!$tag_id) {
                                            // Generate slug from tag name
                                            $slug = create_slug($tag_name);
                                            
                                            // Make sure slug is unique
                                            $slug_check = $pdo->prepare("SELECT COUNT(*) FROM tags WHERE slug = :slug");
                                            $slug_check->execute([':slug' => $slug]);
                                            $slug_exists = $slug_check->fetchColumn();
                                            
                                            // If slug exists, append a unique identifier
                                            if ($slug_exists > 0) {
                                                $slug .= '-' . uniqid();
                                            }
                                            
                                            $tag_stmt = $pdo->prepare("INSERT INTO tags (name, slug, created_at) VALUES (:name, :slug, NOW())");
                                            $tag_stmt->execute([
                                                ':name' => $tag_name,
                                                ':slug' => $slug
                                            ]);
                                            $tag_id = $pdo->lastInsertId();
                                        }
                                        
                                        // Associate tag with media
                                        $media_tag_stmt = $pdo->prepare("INSERT INTO media_tags (media_id, tag_id, created_by) VALUES (:media_id, :tag_id, :created_by)");
                                        $media_tag_stmt->execute([
                                            ':media_id' => $media_id,
                                            ':tag_id' => $tag_id,
                                            ':created_by' => $_SESSION['user_id'] ?? 1
                                        ]);
                                    }
                                }
                                
                                // Insert colors
                                if (!empty($selected_colors)) {
                                    $color_stmt = $pdo->prepare("INSERT INTO media_colors (media_id, color_id, primary_color, created_at) VALUES (:media_id, :color_id, :primary_color, NOW())");
                                    foreach ($selected_colors as $color_id) {
                                        // Get the hex code for the color
                                        $hex_stmt = $pdo->prepare("SELECT hex_code FROM colors WHERE id = :id");
                                        $hex_stmt->execute([':id' => $color_id]);
                                        $hex_code = $hex_stmt->fetchColumn();
                                        
                                        $color_stmt->execute([
                                            ':media_id' => $media_id,
                                            ':color_id' => $color_id,
                                            ':primary_color' => $hex_code ?? '#FFFFFF'
                                        ]);
                                    }
                                }
                                
                                // Update batch item status
                                $update_item_stmt = $pdo->prepare("UPDATE media_batch_items SET status = 'processed', media_id = ? WHERE id = ?");
                                $update_item_stmt->execute([$media_id, $batch_item_id]);
                                
                                $batch_results[] = ["file" => $_FILES['batch_files']['name'][$i], "status" => "success", "media_id" => $media_id];
                            } else {
                                $update_item_stmt = $pdo->prepare("UPDATE media_batch_items SET status = 'failed', error_message = ? WHERE id = ?");
                                $update_item_stmt->execute(["Failed to upload file", $batch_item_id]);
                                $batch_results[] = ["file" => $_FILES['batch_files']['name'][$i], "status" => "failed", "message" => "Failed to upload file"];
                            }
                        } else {
                            $batch_results[] = ["file" => $_FILES['batch_files']['name'][$i], "status" => "failed", "message" => "Error code: " . $_FILES['batch_files']['error'][$i]];
                        }
                    }
                    
                    // Update batch status
                    $processed_count = count(array_filter($batch_results, function($item) { return $item['status'] === 'success'; }));
                    $update_batch_stmt = $pdo->prepare("UPDATE media_batches SET status = 'completed', processed_files = ? WHERE id = ?");
                    $update_batch_stmt->execute([$processed_count, $batch_id]);
                    
                    // Log activity
                    $activity_stmt = $pdo->prepare("INSERT INTO activities (user_id, description, created_at) VALUES (:user_id, :description, NOW())");
                    $activity_stmt->execute([
                        ':user_id' => $_SESSION['user_id'] ?? 1,
                        ':description' => "Processed batch upload #{$batch_id}: {$processed_count} of " . count($_FILES['batch_files']['name']) . " files successful"
                    ]);
                    
                    $success = true;
                } else {
                    $errors[] = 'Please select files to upload';
                }
            } else {
                // Single upload mode
                $title = trim($_POST['title'] ?? '');
                $description = trim($_POST['description'] ?? '');
                $ai_description = trim($_POST['ai_description'] ?? '');
                
                // Validate required fields
                if (empty($title)) {
                    $errors[] = 'Title is required';
                    throw new Exception('Title is required');
                }
                
                // Handle single file upload
                if (!empty($_FILES['media_file']['name'])) {
                    $file = $_FILES['media_file'];
                    $file_name = uniqid() . '_' . basename($file['name']);
                    $upload_dir = '../../uploads/media/' . date('Y/m/');
                    
                    // Create directory if it doesn't exist
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0755, true);
                    }
                    
                    $file_path = $upload_dir . $file_name;
                    $relative_path = '/uploads/media/' . date('Y/m/') . $file_name;
                    
                    // Get file type
                    $file_type = $file['type'];
                    
                    // Check if it's a supported file type
                    $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
                    
                    if (!in_array($file_type, $supported_types) && !in_array($file_extension, $supported_extensions)) {
                        $errors[] = 'Unsupported file type. Please upload a supported image or video file.';
                        throw new Exception('Unsupported file type');
                    }
                    
                    // Move uploaded file
                    if (move_uploaded_file($file['tmp_name'], $file_path)) {
                        $thumbnail_url = '';
                        
                        // Generate thumbnail if it's an image
                        if (strpos($file_type, 'image/') === 0 || in_array($file_extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                            // Get image dimensions if not manually set
                            $img_dimensions = getimagesize($file_path);
                            $actual_width = $img_dimensions[0];
                            $actual_height = $img_dimensions[1];
                            
                            // Use actual dimensions if no custom dimensions provided
                            if (empty($width) || empty($height)) {
                                $width = $actual_width;
                                $height = $actual_height;
                            }
                            
                            // Create thumbnail directory
                            $thumb_dir = '../../uploads/thumbnails/' . date('Y/m/');
                            if (!is_dir($thumb_dir)) {
                                mkdir($thumb_dir, 0755, true);
                            }
                            
                            $thumbnail_path = $thumb_dir . 'thumb_' . $file_name;
                            $thumbnail_relative_path = '/uploads/thumbnails/' . date('Y/m/') . 'thumb_' . $file_name;
                            
                            // Create thumbnail
                            create_thumbnail($file_path, $thumbnail_path, 300);
                            $thumbnail_url = $thumbnail_relative_path;
                            
                            // Detect orientation if not specified
                            if ($orientation === 'auto') {
                                $orientation = ($actual_width > $actual_height) ? 'landscape' : 'portrait';
                            }
                        } 
                        // For video files, generate a thumbnail using ffmpeg if available
                        elseif (strpos($file_type, 'video/') === 0 || in_array($file_extension, ['mp4', 'webm', 'ogg'])) {
                            // Create thumbnail directory
                            $thumb_dir = '../../uploads/thumbnails/' . date('Y/m/');
                            if (!is_dir($thumb_dir)) {
                                mkdir($thumb_dir, 0755, true);
                            }
                            
                            $thumbnail_path = $thumb_dir . 'thumb_' . pathinfo($file_name, PATHINFO_FILENAME) . '.jpg';
                            $thumbnail_relative_path = '/uploads/thumbnails/' . date('Y/m/') . 'thumb_' . pathinfo($file_name, PATHINFO_FILENAME) . '.jpg';
                            
                            // Try to generate thumbnail with ffmpeg if available
                            if (function_exists('exec')) {
                                exec("ffmpeg -i {$file_path} -ss 00:00:01 -vframes 1 {$thumbnail_path} 2>&1", $output, $return_var);
                                if ($return_var === 0) {
                                    $thumbnail_url = $thumbnail_relative_path;
                                } else {
                                    $thumbnail_url = '/assets/images/video_placeholder.jpg'; // Default video thumbnail
                                }
                            } else {
                                $thumbnail_url = '/assets/images/video_placeholder.jpg'; // Default video thumbnail
                            }
                        }
                        
                        // Get file size
                        $file_size = filesize($file_path);
                        $formatted_file_size = format_file_size($file_size);
                        
                        // Insert into media table
                        $stmt = $pdo->prepare("INSERT INTO media (
                            title, description, category_id, file_name, file_path, file_type, file_size,
                            thumbnail_url, status, featured, width, height, size_type, background_color,
                            orientation, owner, license, paid_content, watermark_text, original_filename,
                            created_by, ai_enhanced, ai_description, created_at
                        ) VALUES (
                            :title, :description, :category_id, :file_name, :file_path, :file_type, :file_size,
                            :thumbnail_url, :status, :featured, :width, :height, :size_type, :background_color,
                            :orientation, :owner, :license, :paid_content, :watermark_text, :original_filename,
                            :created_by, :ai_enhanced, :ai_description, NOW()
                        )");
                        
                        $stmt->execute([
                            ':title' => $title,
                            ':description' => $description,
                            ':category_id' => $category_id,
                            ':file_name' => $file_name,
                            ':file_path' => $relative_path,
                            ':file_type' => $file_type,
                            ':file_size' => $formatted_file_size,
                            ':thumbnail_url' => $thumbnail_url,
                            ':status' => $status,
                            ':featured' => $featured,
                            ':width' => $width,
                            ':height' => $height,
                            ':size_type' => $size_type,
                            ':background_color' => $background_color,
                            ':orientation' => $orientation,
                            ':owner' => $owner,
                            ':license' => $license,
                            ':paid_content' => $paid_content,
                            ':watermark_text' => $watermark_text,
                            ':original_filename' => $file['name'],
                            ':created_by' => $_SESSION['user_id'] ?? 1,
                            ':ai_enhanced' => $ai_enhanced,
                            ':ai_description' => $ai_description
                        ]);
                        
                        $media_id = $pdo->lastInsertId();
                                                // Process and insert tags
                        if (!empty($tags)) {
                            foreach ($tags as $tag_name) {
                                // Check if tag already exists
                                $check_stmt = $pdo->prepare("SELECT id FROM tags WHERE name = :name");
                                $check_stmt->execute([':name' => $tag_name]);
                                $tag_id = $check_stmt->fetchColumn();
                                
                                // If tag doesn't exist, create it with slug
                                if (!$tag_id) {
                                    // Generate slug from tag name
                                    $slug = create_slug($tag_name);
                                    
                                    // Make sure slug is unique
                                    $slug_check = $pdo->prepare("SELECT COUNT(*) FROM tags WHERE slug = :slug");
                                    $slug_check->execute([':slug' => $slug]);
                                    $slug_exists = $slug_check->fetchColumn();
                                    
                                    // If slug exists, append a unique identifier
                                    if ($slug_exists > 0) {
                                        $slug .= '-' . uniqid();
                                    }
                                    
                                    $tag_stmt = $pdo->prepare("INSERT INTO tags (name, slug, created_at) VALUES (:name, :slug, NOW())");
                                    $tag_stmt->execute([
                                        ':name' => $tag_name,
                                        ':slug' => $slug
                                    ]);
                                    $tag_id = $pdo->lastInsertId();
                                    
                                    // Log activity for new tag
                                    $activity_stmt = $pdo->prepare("INSERT INTO activities (user_id, description, created_at) VALUES (:user_id, :description, NOW())");
                                    $activity_stmt->execute([
                                        ':user_id' => $_SESSION['user_id'] ?? 1,
                                        ':description' => "Added new tag: {$tag_name} (ID: {$tag_id})"
                                    ]);
                                }
                                
                                // Associate tag with media
                                $media_tag_stmt = $pdo->prepare("INSERT INTO media_tags (media_id, tag_id, created_by) VALUES (:media_id, :tag_id, :created_by)");
                                $media_tag_stmt->execute([
                                    ':media_id' => $media_id,
                                    ':tag_id' => $tag_id,
                                    ':created_by' => $_SESSION['user_id'] ?? 1
                                ]);
                            }
                        }
                        
                        // Insert colors
                        if (!empty($selected_colors)) {
                            $color_stmt = $pdo->prepare("INSERT INTO media_colors (media_id, color_id, primary_color, created_at) VALUES (:media_id, :color_id, :primary_color, NOW())");
                            foreach ($selected_colors as $color_id) {
                                // Get the hex code for the color
                                $hex_stmt = $pdo->prepare("SELECT hex_code FROM colors WHERE id = :id");
                                $hex_stmt->execute([':id' => $color_id]);
                                $hex_code = $hex_stmt->fetchColumn();
                                
                                $color_stmt->execute([
                                    ':media_id' => $media_id,
                                    ':color_id' => $color_id,
                                    ':primary_color' => $hex_code ?? '#FFFFFF'
                                ]);
                            }
                        }
                        
                        // Log activity
                        $activity_stmt = $pdo->prepare("INSERT INTO activities (user_id, description, created_at) VALUES (:user_id, :description, NOW())");
                        $activity_stmt->execute([
                            ':user_id' => $_SESSION['user_id'] ?? 1,
                            ':description' => "Added new media item #{$media_id}: {$title}"
                        ]);
                        
                        $success = true;
                    } else {
                        $errors[] = 'Failed to upload file';
                        throw new Exception('Failed to upload file');
                    }
                } else {
                    $errors[] = 'Please select a file to upload';
                    throw new Exception('No file selected');
                }
            }
            
            // Commit transaction
            $pdo->commit();
            
            // Redirect to media list or clear form
            if ($success && !$batch_mode) {
                if (isset($_POST['save_and_add_another'])) {
                    header("Location: add.php?success=1");
                    exit;
                } elseif (isset($_POST['save_and_return'])) {
                    header("Location: index.php?success=added");
                    exit;
                }
            }
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            if (empty($errors)) {
                $errors[] = "Error: " . $e->getMessage();
            }
        }
    }
}

// Include admin panel header
include ROOT_DIR . '/theme/admin/header.php';
// Include sidebar
require_once '../../theme/admin/slidbar.php';
?>

 
    <!-- Main Content -->
    <div class="content-wrapper min-h-screen bg-gray-100">
        <div class="px-6 py-8">
            <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between">
                <h1 class="text-3xl font-semibold text-gray-800 <?php echo $darkMode ? 'text-white' : ''; ?>">
                    Add New Media
                </h1>
                <a href="index.php" class="btn bg-gray-600 hover:bg-gray-700 text-white py-2 px-4 rounded">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Media List
                </a>
            </div>
            
            <!-- User and datetime info -->
            <div class="mb-6 flex flex-wrap justify-between text-sm text-gray-600 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                <div>
                    <span class="font-medium">Current User:</span> <?php echo htmlspecialchars($current_user); ?>
                </div>
                <div>
                    <span class="font-medium">Date:</span> <?php echo $current_datetime; ?>
                </div>
            </div>
            
            <?php if (!empty($errors)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <strong>Errors:</strong>
                <ul class="list-disc pl-5">
                    <?php foreach ($errors as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php if ($success && !$batch_mode): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <strong>Success!</strong> Media added successfully.
            </div>
            <?php endif; ?>
            
            <?php if ($success && $batch_mode): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <strong>Batch Upload Completed!</strong> Processed <?php echo count($batch_results); ?> files.
                <a href="#batch-results" class="underline">View results</a>
            </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['success']) && $_GET['success'] == 1): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <strong>Success!</strong> Media added successfully.
            </div>
            <?php endif; ?>
            
            <!-- Upload Mode Toggle -->
            <div class="mb-6">
                <div class="bg-white <?php echo $darkMode ? 'bg-gray-800' : ''; ?> shadow-md rounded-lg overflow-hidden">
                    <div class="px-6 py-4">
                        <h2 class="text-lg font-bold text-gray-800 <?php echo $darkMode ? 'text-white' : ''; ?> mb-4">Select Upload Mode</h2>
                        <div class="flex space-x-4">
                            <label class="inline-flex items-center">
                                <input type="radio" name="upload_mode" value="single" class="form-radio" checked onclick="switchUploadMode('single')">
                                <span class="ml-2 text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">Single File Upload</span>
                            </label>
                            <label class="inline-flex items-center">
                                <input type="radio" name="upload_mode" value="batch" class="form-radio" onclick="switchUploadMode('batch')">
                                <span class="ml-2 text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">Batch Upload</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
                        <!-- Single File Upload Form -->
            <form action="add.php" method="post" enctype="multipart/form-data" id="singleUploadForm" class="upload-form active">
                <input type="hidden" name="upload_mode" value="single">
                <div class="bg-white <?php echo $darkMode ? 'bg-gray-800' : ''; ?> shadow-md rounded-lg overflow-hidden">
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Basic Information Section -->
                            <div class="col-span-1 md:col-span-2">
                                <h2 class="text-xl font-bold mb-4 text-gray-800 <?php echo $darkMode ? 'text-white' : ''; ?>">Basic Information</h2>
                            </div>
                            
                            <!-- Title -->
                            <div class="mb-4">
                                <label for="title" class="block text-sm font-medium text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">Title *</label>
                                <input type="text" id="title" name="title" required
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                       value="<?php echo htmlspecialchars($title ?? ''); ?>">
                            </div>
                            
                            <!-- Category -->
                            <div class="mb-4">
                                <label for="category_id" class="block text-sm font-medium text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">Category *</label>
                                <select id="category_id" name="category_id" required
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo (isset($category_id) && $category_id == $category['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Description -->
                            <div class="col-span-1 md:col-span-2 mb-4">
                                <label for="description" class="block text-sm font-medium text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">Description</label>
                                <textarea id="description" name="description" rows="4"
                                          class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                            </div>
                            
                            <!-- Media Upload Section -->
                            <div class="col-span-1 md:col-span-2">
                                <h2 class="text-xl font-bold mb-4 text-gray-800 <?php echo $darkMode ? 'text-white' : ''; ?>">Media Upload</h2>
                            </div>
                            
                            <!-- File Upload Section -->
                            <div id="file_upload_section" class="col-span-1 md:col-span-2 mb-4">
                                <label for="media_file" class="block text-sm font-medium text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">Upload Media File *</label>
                                <input type="file" id="media_file" name="media_file" required
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                <p class="mt-1 text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                                    Supported formats: JPG, JPEG, PNG, GIF, WEBP, SVG, MP4, WEBM, OGG. Maximum file size: 10MB
                                </p>
                                <!-- Image Preview -->
                                <div id="image_preview" class="mt-3 hidden">
                                    <h4 class="text-sm font-medium text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?> mb-2">Preview:</h4>
                                    <div class="flex items-center">
                                        <img id="preview_image" src="#" alt="Preview" class="max-w-xs max-h-64 border rounded">
                                        <div id="image_info" class="ml-4 text-sm"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Resolution Settings -->
                            <div class="col-span-1 md:col-span-2">
                                <h2 class="text-xl font-bold mb-4 text-gray-800 <?php echo $darkMode ? 'text-white' : ''; ?>">Resolution</h2>
                                
                                <div class="mb-4">
                                    <label for="resolution" class="block text-sm font-medium text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">Select Resolution</label>
                                    <select id="resolution" name="resolution" 
                                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                            onchange="toggleCustomResolution()">
                                        <option value="">Auto-detect from file</option>
                                        <?php foreach ($resolutions as $name => $value): ?>
                                        <option value="<?php echo htmlspecialchars($name); ?>"><?php echo htmlspecialchars($name); ?> (<?php echo htmlspecialchars($value); ?>)</option>
                                        <?php endforeach; ?>
                                        <option value="custom">Custom Resolution</option>
                                    </select>
                                </div>
                                
                                <div id="custom_resolution" class="hidden">
                                    <div class="flex flex-wrap gap-4">
                                        <div class="w-1/3">
                                            <label for="custom_width" class="block text-sm font-medium text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">Width (px)</label>
                                            <input type="number" id="custom_width" name="custom_width" 
                                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                                   value="<?php echo htmlspecialchars($custom_width ?? ''); ?>" min="1">
                                        </div>
                                        <div class="w-1/3">
                                            <label for="custom_height" class="block text-sm font-medium text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">Height (px)</label>
                                            <input type="number" id="custom_height" name="custom_height" 
                                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                                   value="<?php echo htmlspecialchars($custom_height ?? ''); ?>" min="1">
                                        </div>
                                    </div>
                                </div>
                            </div>
                                                        <!-- Media Properties Section -->
                            <div class="col-span-1 md:col-span-2">
                                <h2 class="text-xl font-bold mb-4 text-gray-800 <?php echo $darkMode ? 'text-white' : ''; ?>">Media Properties</h2>
                            </div>
                            
                            <!-- Orientation -->
                            <div class="mb-4">
                                <label for="orientation" class="block text-sm font-medium text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">Orientation</label>
                                <select id="orientation" name="orientation"
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                    <option value="auto" selected>Auto-detect</option>
                                    <option value="portrait" <?php echo (isset($orientation) && $orientation == 'portrait') ? 'selected' : ''; ?>>Portrait</option>
                                    <option value="landscape" <?php echo (isset($orientation) && $orientation == 'landscape') ? 'selected' : ''; ?>>Landscape</option>
                                </select>
                            </div>
                            
                            <!-- Size Type -->
                            <div class="mb-4">
                                <label for="size_type" class="block text-sm font-medium text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">Size Type</label>
                                <select id="size_type" name="size_type"
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                    <option value="small" <?php echo (isset($size_type) && $size_type == 'small') ? 'selected' : ''; ?>>Small</option>
                                    <option value="medium" <?php echo (!isset($size_type) || $size_type == 'medium') ? 'selected' : ''; ?>>Medium</option>
                                    <option value="large" <?php echo (isset($size_type) && $size_type == 'large') ? 'selected' : ''; ?>>Large</option>
                                    <option value="xl" <?php echo (isset($size_type) && $size_type == 'xl') ? 'selected' : ''; ?>>Extra Large</option>
                                </select>
                            </div>
                            
                            <!-- Background Color -->
                            <div class="mb-4">
                                <label for="background_color" class="block text-sm font-medium text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">Background Color</label>
                                <div class="flex items-center mt-1">
                                    <input type="color" id="background_color" name="background_color"
                                           class="h-8 w-8 border border-gray-300 rounded shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                           value="<?php echo htmlspecialchars($background_color ?? '#FFFFFF'); ?>">
                                    <input type="text" id="background_color_hex" 
                                           class="ml-2 block w-24 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                           value="<?php echo htmlspecialchars($background_color ?? '#FFFFFF'); ?>" 
                                           oninput="document.getElementById('background_color').value = this.value">
                                </div>
                            </div>
                            
                            <!-- Tags with Auto Suggestion -->
                            <div class="col-span-1 md:col-span-2 mb-4">
                                <label for="tag_input" class="block text-sm font-medium text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?> mb-2">Tags (comma separated)</label>
                                <div class="relative">
                                    <input type="text" id="tag_input" name="tag_input"
                                          class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                          value="<?php echo htmlspecialchars($tag_input ?? ''); ?>"
                                          placeholder="nature, sunset, beach, water">
                                    <div id="tag_suggestions" class="absolute z-10 w-full mt-1 bg-white rounded-md shadow-lg max-h-60 overflow-auto hidden <?php echo $darkMode ? 'bg-gray-700' : ''; ?>"></div>
                                </div>
                                <p class="mt-1 text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                                    Enter tags separated by commas. New tags will be automatically created.
                                </p>
                                <div id="tag_preview" class="mt-2 flex flex-wrap gap-2"></div>
                            </div>
                            
                            <!-- Colors -->
                            <div class="col-span-1 md:col-span-2 mb-4">
                                <label class="block text-sm font-medium text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?> mb-2">Associated Colors</label>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <?php foreach ($colors as $color): ?>
                                    <div class="flex items-center">
                                        <input type="checkbox" id="color_<?php echo $color['id']; ?>" name="colors[]" value="<?php echo $color['id']; ?>"
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        <label for="color_<?php echo $color['id']; ?>" class="ml-2 block text-sm text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">
                                            <span class="inline-block w-4 h-4 border border-gray-300 mr-1" style="background-color: <?php echo $color['hex_code']; ?>;"></span>
                                            <?php echo htmlspecialchars($color['color_name']); ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Owner and License -->
                            <div class="mb-4">
                                <label for="owner" class="block text-sm font-medium text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">Owner/Creator</label>
                                <input type="text" id="owner" name="owner"
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                       value="<?php echo htmlspecialchars($owner ?? ''); ?>">
                            </div>
                            
                            <div class="mb-4">
                                <label for="license" class="block text-sm font-medium text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">License</label>
                                <select id="license" name="license"
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                    <option value="">Select License</option>
                                    <?php foreach ($licenses as $lic): ?>
                                    <option value="<?php echo $lic['name']; ?>" <?php echo (isset($license) && $license == $lic['name']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($lic['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                                                        <!-- Additional Options Section -->
                            <div class="col-span-1 md:col-span-2">
                                <h2 class="text-xl font-bold mb-4 text-gray-800 <?php echo $darkMode ? 'text-white' : ''; ?>">Additional Options</h2>
                            </div>
                            
                            <!-- Flags -->
                            <div class="col-span-1 md:col-span-2 mb-4 space-y-4">
                                <div class="flex items-center">
                                    <input type="checkbox" id="featured" name="featured" value="1" <?php echo (isset($featured) && $featured) ? 'checked' : ''; ?>
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="featured" class="ml-2 block text-sm text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">
                                        Mark as Featured
                                    </label>
                                </div>
                                
                                <div class="flex items-center">
                                    <input type="checkbox" id="status" name="status" value="1" <?php echo (!isset($status) || $status) ? 'checked' : ''; ?>
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="status" class="ml-2 block text-sm text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">
                                        Active (Visible on Site)
                                    </label>
                                </div>
                                
                                <div class="flex items-center">
                                    <input type="checkbox" id="paid_content" name="paid_content" value="1" <?php echo (isset($paid_content) && $paid_content) ? 'checked' : ''; ?>
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="paid_content" class="ml-2 block text-sm text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">
                                        Paid Content
                                    </label>
                                </div>
                                
                                <div class="flex items-center">
                                    <input type="checkbox" id="ai_enhanced" name="ai_enhanced" value="1" <?php echo (isset($ai_enhanced) && $ai_enhanced) ? 'checked' : ''; ?>
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="ai_enhanced" class="ml-2 block text-sm text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">
                                        AI Enhanced
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Watermark -->
                            <div class="col-span-1 md:col-span-2 mb-4">
                                <label for="watermark_text" class="block text-sm font-medium text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">Watermark Text</label>
                                <input type="text" id="watermark_text" name="watermark_text"
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                       value="<?php echo htmlspecialchars($watermark_text ?? ''); ?>"
                                       placeholder="e.g., © WallPix.Top">
                                <p class="mt-1 text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                                    Leave empty for no watermark
                                </p>
                            </div>
                            
                            <!-- AI Description -->
                            <div class="col-span-1 md:col-span-2 mb-4">
                                <label for="ai_description" class="block text-sm font-medium text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">AI Description</label>
                                <textarea id="ai_description" name="ai_description" rows="3"
                                          class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"><?php echo htmlspecialchars($ai_description ?? ''); ?></textarea>
                                <p class="mt-1 text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                                    Optional AI-generated description
                                </p>
                            </div>
                            
                            <!-- Live Preview -->
                            <div class="col-span-1 md:col-span-2">
                                <h2 class="text-xl font-bold mb-4 text-gray-800 <?php echo $darkMode ? 'text-white' : ''; ?>">Live Preview</h2>
                                <div class="bg-gray-100 <?php echo $darkMode ? 'bg-gray-700' : ''; ?> p-4 rounded-lg">
                                    <h3 class="text-lg font-semibold mb-2 text-gray-800 <?php echo $darkMode ? 'text-white' : ''; ?>" id="preview_title">Title Preview</h3>
                                    <div class="flex flex-col md:flex-row gap-4">
                                        <div class="w-full md:w-1/2">
                                            <div class="aspect-w-3 aspect-h-4 bg-white <?php echo $darkMode ? 'bg-gray-600' : ''; ?> rounded-lg flex items-center justify-center overflow-hidden" id="preview_container">
                                                <div class="text-center p-4 text-gray-400">
                                                    <i class="fas fa-image text-4xl mb-2"></i>
                                                    <p>Image preview will appear here</p>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="w-full md:w-1/2">
                                            <div class="text-gray-800 <?php echo $darkMode ? 'text-gray-200' : ''; ?> space-y-2">
                                                <p id="preview_description" class="text-sm">Description preview will appear here</p>
                                                <div class="flex flex-wrap gap-1 mt-2" id="preview_tags"></div>
                                                <div class="mt-2">
                                                    <span class="font-semibold">Category:</span>
                                                    <span id="preview_category">-</span>
                                                </div>
                                                <div class="mt-2">
                                                    <span class="font-semibold">Orientation:</span>
                                                    <span id="preview_orientation">-</span>
                                                </div>
                                                <div class="mt-2">
                                                    <span class="font-semibold">Resolution:</span>
                                                    <span id="preview_resolution">-</span>
                                                </div>
                                                <div class="mt-2">
                                                    <span class="font-semibold">License:</span>
                                                    <span id="preview_license">-</span>
                                                </div>
                                                <div class="mt-2">
                                                    <span class="font-semibold">Status:</span>
                                                    <span id="preview_status" class="px-2 py-1 rounded text-xs">-</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="px-6 py-4 bg-gray-50 <?php echo $darkMode ? 'bg-gray-700' : ''; ?> border-t border-gray-200 <?php echo $darkMode ? 'border-gray-600' : ''; ?> flex flex-wrap justify-end gap-3">
                        <button type="submit" name="save_and_add_another" class="btn bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded">
                            <i class="fas fa-plus mr-1"></i> Save and Add Another
                        </button>
                        <button type="submit" name="save_and_return" class="btn bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded">
                            <i class="fas fa-save mr-1"></i> Save and Return
                        </button>
                    </div>
                </div>
            </form>
                        <!-- Batch Upload Form -->
            <form action="add.php" method="post" enctype="multipart/form-data" id="batchUploadForm" class="upload-form hidden">
                <input type="hidden" name="upload_mode" value="batch">
                <div class="bg-white <?php echo $darkMode ? 'bg-gray-800' : ''; ?> shadow-md rounded-lg overflow-hidden">
                    <div class="p-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Batch Upload Section -->
                            <div class="col-span-1 md:col-span-2">
                                <h2 class="text-xl font-bold mb-4 text-gray-800 <?php echo $darkMode ? 'text-white' : ''; ?>">Batch Upload</h2>
                                <p class="text-gray-600 <?php echo $darkMode ? 'text-gray-400' : ''; ?> mb-4">
                                    Upload multiple files at once. Each file will be created as a separate media item with the common properties defined below.
                                </p>
                            </div>
                            
                            <!-- File Upload Field -->
                            <div class="col-span-1 md:col-span-2 mb-4">
                                <label for="batch_files" class="block text-sm font-medium text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">Select Multiple Files *</label>
                                <input type="file" id="batch_files" name="batch_files[]" multiple required
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                <p class="mt-1 text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                                    Hold Ctrl/Cmd to select multiple files. Supported formats: JPG, JPEG, PNG, GIF, WEBP, SVG, MP4, WEBM, OGG.
                                </p>
                                <div class="mt-3">
                                    <div id="batch_preview_count" class="text-sm font-medium text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?> mb-2">No files selected</div>
                                    <div id="batch_file_list" class="mt-2 max-h-40 overflow-y-auto"></div>
                                </div>
                            </div>
                            
                            <!-- Category -->
                            <div class="mb-4">
                                <label for="batch_category_id" class="block text-sm font-medium text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">Category *</label>
                                <select id="batch_category_id" name="category_id" required
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                    <option value="">Select Category</option>
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>">
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Orientation -->
                            <div class="mb-4">
                                <label for="batch_orientation" class="block text-sm font-medium text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">Orientation</label>
                                <select id="batch_orientation" name="orientation"
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                    <option value="auto" selected>Auto-detect from each file</option>
                                    <option value="portrait">Force Portrait for All</option>
                                    <option value="landscape">Force Landscape for All</option>
                                </select>
                            </div>
                            
                            <!-- Resolution Settings -->
                            <div class="col-span-1 md:col-span-2">
                                <h3 class="text-lg font-semibold mb-2 text-gray-800 <?php echo $darkMode ? 'text-white' : ''; ?>">Resolution</h3>
                                
                                <div class="mb-4">
                                    <label for="batch_resolution" class="block text-sm font-medium text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">Select Resolution</label>
                                    <select id="batch_resolution" name="resolution" 
                                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                            onchange="toggleBatchCustomResolution()">
                                        <option value="">Auto-detect from each file</option>
                                        <?php foreach ($resolutions as $name => $value): ?>
                                        <option value="<?php echo htmlspecialchars($name); ?>"><?php echo htmlspecialchars($name); ?> (<?php echo htmlspecialchars($value); ?>)</option>
                                        <?php endforeach; ?>
                                        <option value="custom">Custom Resolution</option>
                                    </select>
                                </div>
                                
                                <div id="batch_custom_resolution" class="hidden mb-4">
                                    <div class="flex flex-wrap gap-4">
                                        <div class="w-1/3">
                                            <label for="batch_custom_width" class="block text-sm font-medium text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">Width (px)</label>
                                            <input type="number" id="batch_custom_width" name="custom_width" 
                                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                                   min="1">
                                        </div>
                                        <div class="w-1/3">
                                            <label for="batch_custom_height" class="block text-sm font-medium text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">Height (px)</label>
                                            <input type="number" id="batch_custom_height" name="custom_height" 
                                                   class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                                   min="1">
                                        </div>
                                    </div>
                                </div>
                            </div>
                                                        <!-- Tags with Auto Suggestion -->
                            <div class="col-span-1 md:col-span-2 mb-4">
                                <label for="batch_tag_input" class="block text-sm font-medium text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?> mb-2">Tags (comma separated)</label>
                                <div class="relative">
                                    <input type="text" id="batch_tag_input" name="tag_input"
                                          class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                          placeholder="nature, sunset, beach, water">
                                    <div id="batch_tag_suggestions" class="absolute z-10 w-full mt-1 bg-white rounded-md shadow-lg max-h-60 overflow-auto hidden <?php echo $darkMode ? 'bg-gray-700' : ''; ?>"></div>
                                </div>
                                <p class="mt-1 text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                                    These tags will be applied to all uploaded files.
                                </p>
                                <div id="batch_tag_preview" class="mt-2 flex flex-wrap gap-2"></div>
                            </div>
                            
                            <!-- Associated Colors -->
                            <div class="col-span-1 md:col-span-2 mb-4">
                                <label class="block text-sm font-medium text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?> mb-2">Associated Colors</label>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <?php foreach ($colors as $color): ?>
                                    <div class="flex items-center">
                                        <input type="checkbox" id="batch_color_<?php echo $color['id']; ?>" name="colors[]" value="<?php echo $color['id']; ?>"
                                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                        <label for="batch_color_<?php echo $color['id']; ?>" class="ml-2 block text-sm text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">
                                            <span class="inline-block w-4 h-4 border border-gray-300 mr-1" style="background-color: <?php echo $color['hex_code']; ?>;"></span>
                                            <?php echo htmlspecialchars($color['color_name']); ?>
                                        </label>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                                <p class="mt-1 text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                                    These colors will be applied to all uploaded files.
                                </p>
                            </div>
                            
                            <!-- Common Properties -->
                            <div class="col-span-1 md:col-span-2">
                                <h3 class="text-lg font-semibold mb-2 text-gray-800 <?php echo $darkMode ? 'text-white' : ''; ?>">Common Properties</h3>
                                <p class="text-sm text-gray-600 <?php echo $darkMode ? 'text-gray-400' : ''; ?> mb-4">
                                    These properties will be applied to all uploaded files.
                                </p>
                            </div>
                            
                            <!-- Background Color -->
                            <div class="mb-4">
                                <label for="batch_background_color" class="block text-sm font-medium text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">Background Color</label>
                                <div class="flex items-center mt-1">
                                    <input type="color" id="batch_background_color" name="background_color"
                                           class="h-8 w-8 border border-gray-300 rounded shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500"
                                           value="#FFFFFF">
                                    <input type="text" id="batch_background_color_hex" 
                                           class="ml-2 block w-24 px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                           value="#FFFFFF" 
                                           oninput="document.getElementById('batch_background_color').value = this.value">
                                </div>
                            </div>
                            
                            <!-- Size Type -->
                            <div class="mb-4">
                                <label for="batch_size_type" class="block text-sm font-medium text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">Size Type</label>
                                <select id="batch_size_type" name="size_type"
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                    <option value="small">Small</option>
                                    <option value="medium" selected>Medium</option>
                                    <option value="large">Large</option>
                                    <option value="xl">Extra Large</option>
                                </select>
                            </div>
                                                        <!-- Owner -->
                            <div class="mb-4">
                                <label for="batch_owner" class="block text-sm font-medium text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">Owner/Creator</label>
                                <input type="text" id="batch_owner" name="owner"
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                            </div>
                            
                            <!-- License -->
                            <div class="mb-4">
                                <label for="batch_license" class="block text-sm font-medium text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">License</label>
                                <select id="batch_license" name="license"
                                        class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                    <option value="">Select License</option>
                                    <?php foreach ($licenses as $lic): ?>
                                    <option value="<?php echo $lic['name']; ?>">
                                        <?php echo htmlspecialchars($lic['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Flags -->
                            <div class="col-span-1 md:col-span-2 mb-4 space-y-4">
                                <div class="flex items-center">
                                    <input type="checkbox" id="batch_featured" name="featured" value="1"
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="batch_featured" class="ml-2 block text-sm text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">
                                        Mark All as Featured
                                    </label>
                                </div>
                                
                                <div class="flex items-center">
                                    <input type="checkbox" id="batch_status" name="status" value="1" checked
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="batch_status" class="ml-2 block text-sm text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">
                                        Active (Visible on Site)
                                    </label>
                                </div>
                                
                                <div class="flex items-center">
                                    <input type="checkbox" id="batch_paid_content" name="paid_content" value="1"
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="batch_paid_content" class="ml-2 block text-sm text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">
                                        Mark All as Paid Content
                                    </label>
                                </div>
                                
                                <div class="flex items-center">
                                    <input type="checkbox" id="batch_ai_enhanced" name="ai_enhanced" value="1"
                                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                                    <label for="batch_ai_enhanced" class="ml-2 block text-sm text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">
                                        Mark All as AI Enhanced
                                    </label>
                                </div>
                            </div>
                            
                            <!-- Watermark -->
                            <div class="col-span-1 md:col-span-2 mb-4">
                                <label for="batch_watermark_text" class="block text-sm font-medium text-gray-700 <?php echo $darkMode ? 'text-gray-300' : ''; ?>">Watermark Text</label>
                                <input type="text" id="batch_watermark_text" name="watermark_text"
                                       class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                       placeholder="e.g., © WallPix.Top">
                                <p class="mt-1 text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                                    Leave empty for no watermark
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="px-6 py-4 bg-gray-50 <?php echo $darkMode ? 'bg-gray-700' : ''; ?> border-t border-gray-200 <?php echo $darkMode ? 'border-gray-600' : ''; ?> flex flex-wrap justify-end gap-3">
                        <button type="submit" name="process_batch" class="btn bg-blue-600 hover:bg-blue-700 text-white py-2 px-4 rounded">
                            <i class="fas fa-upload mr-1"></i> Upload All Files
                        </button>
                    </div>
                </div>
            </form>
            
            <?php if ($batch_mode && $success && !empty($batch_results)): ?>
            <div id="batch-results" class="mt-8">
                <div class="bg-white <?php echo $darkMode ? 'bg-gray-800' : ''; ?> shadow-md rounded-lg overflow-hidden">
                    <div class="px-6 py-4">
                        <h2 class="text-xl font-bold mb-4 text-gray-800 <?php echo $darkMode ? 'text-white' : ''; ?>">Batch Upload Results</h2>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-700' : ''; ?>">
                                <thead class="bg-gray-50 <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                                    <tr>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 <?php echo $darkMode ? 'text-gray-300' : ''; ?> uppercase tracking-wider">
                                            File
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 <?php echo $darkMode ? 'text-gray-300' : ''; ?> uppercase tracking-wider">
                                            Status
                                        </th>
                                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 <?php echo $darkMode ? 'text-gray-300' : ''; ?> uppercase tracking-wider">
                                            Details
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white <?php echo $darkMode ? 'bg-gray-800' : ''; ?> divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-700' : ''; ?>">
                                    <?php foreach ($batch_results as $result): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 <?php echo $darkMode ? 'text-white' : ''; ?>">
                                            <?php echo htmlspecialchars($result['file']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <?php if ($result['status'] === 'success'): ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Success
                                            </span>
                                            <?php else: ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                Failed
                                            </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                                            <?php if ($result['status'] === 'success'): ?>
                                            Media ID: <?php echo $result['media_id']; ?>
                                            <?php else: ?>
                                            Error: <?php echo htmlspecialchars($result['message'] ?? 'Unknown error'); ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="px-6 py-4 bg-gray-50 <?php echo $darkMode ? 'bg-gray-700' : ''; ?> border-t border-gray-200 <?php echo $darkMode ? 'border-gray-600' : ''; ?>">
                        <div class="text-sm">
                            <span class="font-medium">Total files:</span> <?php echo count($batch_results); ?> | 
                            <span class="font-medium">Successful:</span> <?php echo count(array_filter($batch_results, function($item) { return $item['status'] === 'success'; })); ?> | 
                            <span class="font-medium">Failed:</span> <?php echo count(array_filter($batch_results, function($item) { return $item['status'] === 'failed'; })); ?>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<!-- JavaScript for dynamic features -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Store all available tags
    const availableTags = <?php echo $tags_json; ?>;
    
    // Switch between upload modes
    window.switchUploadMode = function(mode) {
        const singleForm = document.getElementById('singleUploadForm');
        const batchForm = document.getElementById('batchUploadForm');
        
        if (mode === 'single') {
            singleForm.classList.remove('hidden');
            singleForm.classList.add('active');
            batchForm.classList.add('hidden');
            batchForm.classList.remove('active');
        } else {
            singleForm.classList.add('hidden');
            singleForm.classList.remove('active');
            batchForm.classList.remove('hidden');
            batchForm.classList.add('active');
        }
    };
    
    // Set the correct mode on page load based on radio button
    const uploadModeRadios = document.querySelectorAll('input[name="upload_mode"]');
    uploadModeRadios.forEach(radio => {
        if (radio.checked) {
            switchUploadMode(radio.value);
        }
    });
    
    // Media file preview for single upload
    const mediaFileInput = document.getElementById('media_file');
    const previewContainer = document.getElementById('image_preview');
    const previewImage = document.getElementById('preview_image');
    const imageInfo = document.getElementById('image_info');
    
    if (mediaFileInput) {
        mediaFileInput.addEventListener('change', function() {
            const file = this.files[0];
            
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    if (file.type.startsWith('image/')) {
                        previewImage.src = e.target.result;
                        previewContainer.classList.remove('hidden');
                        
                        // Create a temporary image to get dimensions
                        const img = new Image();
                        img.onload = function() {
                            imageInfo.innerHTML = `<strong>Dimensions:</strong> ${img.width}x${img.height} pixels<br><strong>Size:</strong> ${formatFileSize(file.size)}`;
                            
                            // Set orientation automatically based on dimensions
                            const orientationSelect = document.getElementById('orientation');
                            if (img.width > img.height) {
                                orientationSelect.value = 'landscape';
                            } else {
                                orientationSelect.value = 'portrait';
                            }
                            
                            // Update live preview
                            updateLivePreview();
                        };
                        img.src = e.target.result;
                        
                        // Also update preview container
                        document.getElementById('preview_container').innerHTML = `<img src="${e.target.result}" alt="Preview" class="object-contain max-h-full max-w-full">`;
                    } else if (file.type.startsWith('video/')) {
                        // Handle video files
                        previewContainer.classList.remove('hidden');
                        previewImage.style.display = 'none';
                        
                        // Display video info
                        imageInfo.innerHTML = `<strong>File type:</strong> ${file.type}<br><strong>Size:</strong> ${formatFileSize(file.size)}`;
                        
                        // Create video preview
                        const video = document.createElement('video');
                        video.src = e.target.result;
                        video.controls = true;
                        video.muted = true;
                        video.className = 'max-w-xs max-h-64 border rounded';
                        
                        // Replace image with video in preview
                        const parent = previewImage.parentNode;
                        parent.insertBefore(video, previewImage);
                        
                        // Update main preview container
                        document.getElementById('preview_container').innerHTML = '';
                        const previewVideo = document.createElement('video');
                        previewVideo.src = e.target.result;
                        previewVideo.controls = true;
                        previewVideo.muted = true;
                        previewVideo.className = 'object-contain max-h-full max-w-full';
                        document.getElementById('preview_container').appendChild(previewVideo);
                    }
                };
                
                reader.readAsDataURL(file);
            } else {
                previewContainer.classList.add('hidden');
            }
        });
    }
    
    // Batch file preview
    const batchFilesInput = document.getElementById('batch_files');
    const batchPreviewCount = document.getElementById('batch_preview_count');
    const batchFileList = document.getElementById('batch_file_list');
    
    if (batchFilesInput) {
        batchFilesInput.addEventListener('change', function() {
            const files = this.files;
            
            if (files.length > 0) {
                batchPreviewCount.textContent = `${files.length} file(s) selected`;
                batchFileList.innerHTML = '';
                
                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    const listItem = document.createElement('div');
                    listItem.className = 'text-sm py-1 flex justify-between';
                    
                    const fileTypeIcon = file.type.startsWith('image/') ? 'fa-image' : (file.type.startsWith('video/') ? 'fa-video' : 'fa-file');
                    
                    listItem.innerHTML = `
                        <div>
                            <i class="fas ${fileTypeIcon} mr-1"></i>
                            ${file.name} 
                        </div>
                        <div class="text-gray-500">${formatFileSize(file.size)}</div>
                    `;
                    
                    batchFileList.appendChild(listItem);
                }
            } else {
                batchPreviewCount.textContent = 'No files selected';
                batchFileList.innerHTML = '';
            }
        });
    }
    
    // Toggle custom resolution for single upload
    window.toggleCustomResolution = function() {
        const resolutionSelect = document.getElementById('resolution');
        const customResolution = document.getElementById('custom_resolution');
        
        if (resolutionSelect.value === 'custom') {
            customResolution.classList.remove('hidden');
        } else {
            customResolution.classList.add('hidden');
        }
        
        updateLivePreview();
    };
    
    // Toggle custom resolution for batch upload
    window.toggleBatchCustomResolution = function() {
        const resolutionSelect = document.getElementById('batch_resolution');
        const customResolution = document.getElementById('batch_custom_resolution');
        
        if (resolutionSelect.value === 'custom') {
            customResolution.classList.remove('hidden');
        } else {
            customResolution.classList.add('hidden');
        }
    };
    
    // Tag input and auto-suggestion for single upload
    const tagInput = document.getElementById('tag_input');
    const tagSuggestions = document.getElementById('tag_suggestions');
    const tagPreview = document.getElementById('tag_preview');
    
    if (tagInput) {
        tagInput.addEventListener('input', function() {
            updateTagPreview();
            showTagSuggestions(this, tagSuggestions);
        });
    }
    
    // Tag input and auto-suggestion for batch upload
    const batchTagInput = document.getElementById('batch_tag_input');
    const batchTagSuggestions = document.getElementById('batch_tag_suggestions');
    const batchTagPreview = document.getElementById('batch_tag_preview');
    
    if (batchTagInput) {
        batchTagInput.addEventListener('input', function() {
            updateBatchTagPreview();
            showTagSuggestions(this, batchTagSuggestions);
        });
    }
    
    // Show tag suggestions based on current input
    function showTagSuggestions(inputElement, suggestionsElement) {
        const currentInput = inputElement.value;
        const lastTag = currentInput.split(',').pop().trim().toLowerCase();
        
        if (lastTag.length >= 1) {
            const matchingSuggestions = availableTags.filter(tag => 
                tag.toLowerCase().startsWith(lastTag)
            );
            
            if (matchingSuggestions.length > 0) {
                suggestionsElement.innerHTML = '';
                matchingSuggestions.forEach(tag => {
                    const div = document.createElement('div');
                    div.className = `px-3 py-2 cursor-pointer hover:bg-gray-100 ${!darkMode ? '' : 'hover:bg-gray-600 text-white'}`;
                    div.textContent = tag;
                    div.addEventListener('click', () => {
                        // Replace the last tag with the selected one
                        const tags = currentInput.split(',');
                        tags.pop();
                        tags.push(tag);
                        inputElement.value = tags.join(', ') + ', ';
                        suggestionsElement.classList.add('hidden');
                        inputElement.focus();
                        
                        // Update appropriate tag preview
                        if (inputElement === tagInput) {
                            updateTagPreview();
                        } else {
                            updateBatchTagPreview();
                        }
                    });
                    suggestionsElement.appendChild(div);
                });
                suggestionsElement.classList.remove('hidden');
            } else {
                suggestionsElement.classList.add('hidden');
            }
        } else {
            suggestionsElement.classList.add('hidden');
        }
    }
    
    // Hide suggestions when clicking outside
    document.addEventListener('click', function(e) {
        if (tagSuggestions && e.target !== tagInput) {
            tagSuggestions.classList.add('hidden');
        }
        if (batchTagSuggestions && e.target !== batchTagInput) {
            batchTagSuggestions.classList.add('hidden');
        }
    });
    
    // Update the visual preview of tags for single form
    function updateTagPreview() {
        if (!tagInput || !tagPreview) return;
        
        const tags = tagInput.value.split(',').map(tag => tag.trim()).filter(tag => tag !== '');
        tagPreview.innerHTML = '';
        
        tags.forEach(tag => {
            const span = document.createElement('span');
            span.className = 'inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded';
            span.textContent = tag;
            tagPreview.appendChild(span);
        });
    }
    
    // Update the visual preview of tags for batch form
    function updateBatchTagPreview() {
        if (!batchTagInput || !batchTagPreview) return;
        
        const tags = batchTagInput.value.split(',').map(tag => tag.trim()).filter(tag => tag !== '');
        batchTagPreview.innerHTML = '';
        
        tags.forEach(tag => {
            const span = document.createElement('span');
            span.className = 'inline-block bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded';
            span.textContent = tag;
            batchTagPreview.appendChild(span);
        });
    }
    
    // Live Preview Updates for single upload
    const titleInput = document.getElementById('title');
    const descriptionInput = document.getElementById('description');
    const categorySelect = document.getElementById('category_id');
    const orientationSelect = document.getElementById('orientation');
    const resolutionSelect = document.getElementById('resolution');
    const customWidthInput = document.getElementById('custom_width');
    const customHeightInput = document.getElementById('custom_height');
    const licenseSelect = document.getElementById('license');
    const statusCheckbox = document.getElementById('status');
    
    function updateLivePreview() {
        if (!document.getElementById('preview_title')) return;
        
        // Update title
        document.getElementById('preview_title').textContent = titleInput.value || 'Title Preview';
        
        // Update description
        document.getElementById('preview_description').textContent = descriptionInput.value || 'Description preview will appear here';
        
        // Update category
        const selectedCategory = categorySelect.options[categorySelect.selectedIndex];
        document.getElementById('preview_category').textContent = selectedCategory ? selectedCategory.text : '-';
        
        // Update orientation
        let orientationText = 'Auto-detect';
        if (orientationSelect.value === 'portrait') {
            orientationText = 'Portrait';
        } else if (orientationSelect.value === 'landscape') {
            orientationText = 'Landscape';
        }
        document.getElementById('preview_orientation').textContent = orientationText;
        
        // Update resolution
        const resolutionPreview = document.getElementById('preview_resolution');
        if (resolutionSelect.value === 'custom' && customWidthInput.value && customHeightInput.value) {
            resolutionPreview.textContent = `${customWidthInput.value} × ${customHeightInput.value}`;
        } else if (resolutionSelect.value && resolutionSelect.value !== 'custom') {
            const selectedOption = resolutionSelect.options[resolutionSelect.selectedIndex];
            resolutionPreview.textContent = `${selectedOption.text}`;
        } else {
            resolutionPreview.textContent = 'Auto-detected';
        }
        
        // Update license
        const selectedLicense = licenseSelect.options[licenseSelect.selectedIndex];
        document.getElementById('preview_license').textContent = selectedLicense ? selectedLicense.text : '-';
        
        // Update status
        const statusElement = document.getElementById('preview_status');
        if (statusCheckbox.checked) {
            statusElement.textContent = 'Active';
            statusElement.className = 'px-2 py-1 rounded text-xs bg-green-100 text-green-800';
        } else {
            statusElement.textContent = 'Inactive';
            statusElement.className = 'px-2 py-1 rounded text-xs bg-red-100 text-red-800';
        }
        
        // Update tag preview
        updateTagPreview();
    }
    
    // Add event listeners for live preview updates
    const previewElements = [
        titleInput, descriptionInput, categorySelect, orientationSelect,
        resolutionSelect, customWidthInput, customHeightInput,
        licenseSelect, statusCheckbox
    ];
    
    previewElements.forEach(el => {
        if(el) {
            el.addEventListener('input', updateLivePreview);
            el.addEventListener('change', updateLivePreview);
        }
    });
    
    // Initialize live preview
    if (document.getElementById('preview_title')) {
        updateLivePreview();
    }
    
    // Background color sync for single upload
    const backgroundColorInput = document.getElementById('background_color');
    const backgroundColorHex = document.getElementById('background_color_hex');
    
    if (backgroundColorInput && backgroundColorHex) {
        backgroundColorInput.addEventListener('input', function() {
            backgroundColorHex.value = this.value;
        });
        
        backgroundColorHex.addEventListener('input', function() {
            backgroundColorInput.value = this.value;
        });
    }
    
    // Background color sync for batch upload
    const batchBackgroundColorInput = document.getElementById('batch_background_color');
    const batchBackgroundColorHex = document.getElementById('batch_background_color_hex');
    
    if (batchBackgroundColorInput && batchBackgroundColorHex) {
        batchBackgroundColorInput.addEventListener('input', function() {
            batchBackgroundColorHex.value = this.value;
        });
        
        batchBackgroundColorHex.addEventListener('input', function() {
            batchBackgroundColorInput.value = this.value;
        });
    }
    
    // Helper function to format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
    
    // Check if we need to scroll to batch results
    if (window.location.hash === '#batch-results') {
        const batchResults = document.getElementById('batch-results');
        if (batchResults) {
            batchResults.scrollIntoView({ behavior: 'smooth' });
        }
    }
});
</script>
<!-- Content ends -->

<?php
// Include the footer
include_once __DIR__ . '/../../theme/admin/footer.php';
?>
                                