<?php
define('ROOT_DIR', dirname(__DIR__));
require_once ROOT_DIR . '/includes/init.php';
require_admin();

// Handle section actions
$error_message = '';
$success_message = '';

// Get media ID from URL
$media_id = (int)($_GET['id'] ?? 0);

if (!$media_id) {
    header('Location: media.php');
    exit;
}

// Get categories for dropdown
$stmt = $pdo->query("
    SELECT id, name, parent_id 
    FROM categories 
    ORDER BY parent_id IS NULL DESC, name ASC
");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Define allowed dimensions
$allowed_dimensions = [
    'thumbnail' => ['width' => 300, 'height' => 300],
    'medium' => ['width' => 800, 'height' => 600],
    'large' => ['width' => 1920, 'height' => 1080],
    'custom' => [
        'width' => (int)($_POST['custom_width'] ?? 0),
        'height' => (int)($_POST['custom_height'] ?? 0)
    ]
];

// Get media data
$stmt = $pdo->prepare("
    SELECT m.*, GROUP_CONCAT(t.name) as tags
    FROM media m
    LEFT JOIN media_tags mt ON m.id = mt.media_id
    LEFT JOIN tags t ON mt.tag_id = t.id
    WHERE m.id = ?
    GROUP BY m.id
");
$stmt->execute([$media_id]);
$media = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$media) {
    header('Location: media.php');
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // Basic information
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $category_id = (int)($_POST['category_id'] ?? 0);
        $tags = $_POST['tags'] ?? '';
        $status = isset($_POST['status']) ? 1 : 0;
        $featured = isset($_POST['featured']) ? 1 : 0;
        $external_url = $_POST['external_url'] ?? '';
        
        // Image processing options
        $selected_size = $_POST['media_size'] ?? 'medium';
        $maintain_aspect_ratio = isset($_POST['maintain_aspect_ratio']) ? 1 : 0;
        $background_color = $_POST['background_color'] ?? '#FFFFFF';
        $custom_width = (int)($_POST['custom_width'] ?? 0);
        $custom_height = (int)($_POST['custom_height'] ?? 0);

        // Update basic media information
        $stmt = $pdo->prepare("
            UPDATE media SET 
                title = ?,
                description = ?,
                category_id = ?,
                status = ?,
                featured = ?,
                external_url = ?,
                size_type = ?,
                background_color = ?,
                maintain_aspect_ratio = ?,
                updated_by = ?,
                updated_at = ?
            WHERE id = ?
        ");
        
        $stmt->execute([
            $title,
            $description,
            $category_id,
            $status,
            $featured,
            $external_url,
            $selected_size,
            $background_color,
            $maintain_aspect_ratio,
            $current_user,
            $current_timestamp,
            $media_id
        ]);

        // Process tags
        // Remove existing tags
        $stmt = $pdo->prepare("DELETE FROM media_tags WHERE media_id = ?");
        $stmt->execute([$media_id]);

        // Add new tags
        if (!empty($tags)) {
            $tag_names = array_map('trim', explode(',', $tags));
            foreach ($tag_names as $tag_name) {
                if (!empty($tag_name)) {
                    $tag_slug = strtolower(
                        preg_replace('/[^a-zA-Z0-9]+/', '-', $tag_name)
                    );
                    
                    // Check if tag exists
                    $stmt = $pdo->prepare("SELECT id FROM tags WHERE slug = ?");
                    $stmt->execute([$tag_slug]);
                    $tag = $stmt->fetch();
                    
                    if ($tag) {
                        $tag_id = $tag['id'];
                    } else {
                        // Create new tag
                        $stmt = $pdo->prepare("
                            INSERT INTO tags (name, slug, created_by) 
                            VALUES (?, ?, ?)
                        ");
                        $stmt->execute([$tag_name, $tag_slug, $current_user]);
                        $tag_id = $pdo->lastInsertId();
                    }

                    // Add tag to media
                    $stmt = $pdo->prepare("
                        INSERT INTO media_tags (media_id, tag_id, created_by) 
                        VALUES (?, ?, ?)
                    ");
                    $stmt->execute([$media_id, $tag_id, $current_user]);
                }
            }
        }

        // Handle new file upload if provided
        if (!empty($_FILES['file']['name'])) {
            $file = $_FILES['file'];
            
            if ($file['error'] === 0) {
                $file_name = $file['name'];
                $tmp_name = $file['tmp_name'];
                $file_size = $file['size'];
                $file_type = $file['type'];
                
                // Generate unique filename
                $extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $unique_filename = uniqid() . '_' . time() . '.' . $extension;
                
                // Create upload directories
                $upload_path = '../uploads/media/' . date('Y/m/');
                $thumbnail_path = '../uploads/thumbnails/' . date('Y/m/');
                
                foreach ([$upload_path, $thumbnail_path] as $path) {
                    if (!file_exists($path)) {
                        mkdir($path, 0777, true);
                    }
                }
                
                $file_path = $upload_path . $unique_filename;
                $thumbnail_url = '';
                $width = 0;
                $height = 0;
                
                // Process image files
                if (strpos($file_type, 'image/') === 0) {
                    // Get original image dimensions
                    $image = imagecreatefromstring(file_get_contents($tmp_name));
                    $orig_width = imagesx($image);
                    $orig_height = imagesy($image);
                    
                    // Get target dimensions
                    $target_dimensions = $allowed_dimensions[$selected_size];
                    if ($selected_size === 'custom') {
                        $target_dimensions = [
                            'width' => $custom_width,
                            'height' => $custom_height
                        ];
                    }
                    $target_width = $target_dimensions['width'];
                    $target_height = $target_dimensions['height'];
                    
                    if ($maintain_aspect_ratio) {
                        // Calculate new dimensions maintaining aspect ratio
                        $ratio = min($target_width / $orig_width, $target_height / $orig_height);
                        $new_width = round($orig_width * $ratio);
                        $new_height = round($orig_height * $ratio);
                    } else {
                        $new_width = $target_width;
                        $new_height = $target_height;
                    }
                    
                    // Create new image with background color
                    $new_image = imagecreatetruecolor($target_width, $target_height);
                    
                    // Convert hex background color to RGB
                    $bg_color = sscanf($background_color, "#%02x%02x%02x");
                    $bg = imagecolorallocate($new_image, $bg_color[0], $bg_color[1], $bg_color[2]);
                    imagefill($new_image, 0, 0, $bg);
                    
                    // Calculate position to center image
                    $x = ($target_width - $new_width) / 2;
                    $y = ($target_height - $new_height) / 2;
                    
                    // Copy and resize image
                    imagecopyresampled(
                        $new_image, $image,
                        $x, $y, 0, 0,
                        $new_width, $new_height,
                        $orig_width, $orig_height
                    );
                    
                    // Save image
                    switch ($file_type) {
                        case 'image/jpeg':
                            imagejpeg($new_image, $file_path, 90);
                            break;
                        case 'image/png':
                            imagepng($new_image, $file_path, 9);
                            break;
                        case 'image/gif':
                            imagegif($new_image, $file_path);
                            break;
                    }
                    
                    // Create thumbnail
                    $thumb_width = 300;
                    $thumb_height = round($thumb_width * ($orig_height / $orig_width));
                    $thumbnail = imagecreatetruecolor($thumb_width, $thumb_height);
                    imagecopyresampled(
                        $thumbnail, $image,
                        0, 0, 0, 0,
                        $thumb_width, $thumb_height,
                        $orig_width, $orig_height
                    );
                    
                    $thumbnail_filename = 'thumb_' . $unique_filename;
                    $thumbnail_full_path = $thumbnail_path . $thumbnail_filename;
                    
                    switch ($file_type) {
                        case 'image/jpeg':
                            imagejpeg($thumbnail, $thumbnail_full_path, 90);
                            break;
                        case 'image/png':
                            imagepng($thumbnail, $thumbnail_full_path, 9);
                            break;
                        case 'image/gif':
                            imagegif($thumbnail, $thumbnail_full_path);
                            break;
                    }
                    
                    $thumbnail_url = str_replace('../', '', $thumbnail_full_path);
                    $width = $target_width;
                    $height = $target_height;
                    
                    imagedestroy($thumbnail);
                    imagedestroy($new_image);
                    imagedestroy($image);
                } else {
                    // For non-image files, just move the uploaded file
                    move_uploaded_file($tmp_name, $file_path);
                }
                
                // Delete old files if they exist
                if (!empty($media['file_path'])) {
                    @unlink('../' . $media['file_path']);
                }
                if (!empty($media['thumbnail_url'])) {
                    @unlink('../' . $media['thumbnail_url']);
                }
                
                // Update media record with new file information
                $stmt = $pdo->prepare("
                    UPDATE media SET 
                        file_name = ?,
                        file_path = ?,
                        file_type = ?,
                        file_size = ?,
                        thumbnail_url = ?,
                        width = ?,
                        height = ?,
                        updated_by = ?,
                        updated_at = ?
                    WHERE id = ?
                ");
                
                $stmt->execute([
                    $unique_filename,
                    str_replace('../', '', $file_path),
                    $file_type,
                    $file_size,
                    $thumbnail_url,
                    $width,
                    $height,
                    $current_user,
                    $current_timestamp,
                    $media_id
                ]);
            }
        }
        
        $pdo->commit();
        $success = "Media updated successfully!";
        
        // Refresh media data
        $stmt = $pdo->prepare("
            SELECT m.*, GROUP_CONCAT(t.name) as tags
            FROM media m
            LEFT JOIN media_tags mt ON m.id = mt.media_id
            LEFT JOIN tags t ON mt.tag_id = t.id
            WHERE m.id = ?
            GROUP BY m.id
        ");
        $stmt->execute([$media_id]);
        $media = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New Media</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/filepond@4.30.4/dist/filepond.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/filepond-plugin-image-preview@4.6.11/dist/filepond-plugin-image-preview.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>
</head>
<body class="bg-gray-100 flex">
    <?php include 'sidebar.php'; ?>

    <div class="container mx-auto px-4 py-8 ml-64">
        <div class="bg-white rounded-lg shadow-md p-6 mb-6">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Edit Media</h1>
                <a href="media.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Media
                </a>
            </div>

            <?php if ($error || $success): ?>
            <div class="rounded-md <?php echo $error ? 'bg-red-50' : 'bg-green-50'; ?> p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-<?php echo $error ? 'exclamation-circle text-red-400' : 'check-circle text-green-400'; ?>"></i>
                    </div>
                    <div class="ml-3">
                        <p class="text-sm <?php echo $error ? 'text-red-700' : 'text-green-700'; ?>">
                            <?php echo $error ?: $success; ?>
                        </p>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Current Media Preview -->
            <div class="mb-6">
                <h2 class="text-lg font-medium text-gray-900 mb-2">Current Media</h2>
                <div class="bg-gray-50 rounded-lg p-4">
                    <?php if (strpos($media['file_type'], 'image/') === 0): ?>
                        <img src="<?php echo htmlspecialchars($media['file_path']); ?>" 
                                                          alt="<?php echo htmlspecialchars($media['title']); ?>"
                             class="max-w-full h-auto rounded">
                    <?php elseif (strpos($media['file_type'], 'video/') === 0): ?>
                        <video controls class="max-w-full">
                            <source src="<?php echo htmlspecialchars($media['file_path']); ?>" 
                                    type="<?php echo htmlspecialchars($media['file_type']); ?>">
                            Your browser does not support the video tag.
                        </video>
                    <?php elseif (strpos($media['file_type'], 'audio/') === 0): ?>
                        <audio controls class="w-full">
                            <source src="<?php echo htmlspecialchars($media['file_path']); ?>" 
                                    type="<?php echo htmlspecialchars($media['file_type']); ?>">
                            Your browser does not support the audio tag.
                        </audio>
                    <?php endif; ?>
                    
                    <div class="mt-2 text-sm text-gray-500">
                        <p>File Name: <?php echo htmlspecialchars($media['file_name']); ?></p>
                        <p>Type: <?php echo htmlspecialchars($media['file_type']); ?></p>
                        <p>Size: <?php echo number_format($media['file_size'] / 1024, 2); ?> KB</p>
                        <?php if ($media['width'] && $media['height']): ?>
                        <p>Dimensions: <?php echo $media['width']; ?> × <?php echo $media['height']; ?> pixels</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <form action="" method="post" enctype="multipart/form-data" class="space-y-6">
                <!-- Basic Info Section -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-1">Title</label>
                        <input type="text" 
                               name="title" 
                               id="title" 
                               required
                               value="<?php echo htmlspecialchars($media['title']); ?>"
                               class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>

                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                        <select name="category_id" 
                                id="category_id" 
                                required
                                class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>" 
                                    <?php echo $category['id'] == $media['category_id'] ? 'selected' : ''; ?>>
                                <?php echo str_repeat('- ', $category['parent_id'] ? 1 : 0) . htmlspecialchars($category['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                    <textarea name="description" 
                              id="description" 
                              rows="4" 
                              class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"><?php echo htmlspecialchars($media['description']); ?></textarea>
                </div>

                <div>
                    <label for="tags" class="block text-sm font-medium text-gray-700 mb-1">Tags</label>
                    <input type="text" 
                           name="tags" 
                           id="tags" 
                           value="<?php echo htmlspecialchars($media['tags']); ?>"
                           class="tagify"
                           placeholder="Enter tags separated by commas">
                </div>

                <!-- External URL -->
                <div>
                    <label for="external_url" class="block text-sm font-medium text-gray-700 mb-1">External URL</label>
                    <input type="url" 
                           name="external_url" 
                           id="external_url" 
                           value="<?php echo htmlspecialchars($media['external_url']); ?>"
                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                           placeholder="https://example.com/media.jpg">
                </div>

                <!-- Media Size Options -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Media Size</label>
                        <div class="grid grid-cols-2 gap-4">
                            <select name="media_size" 
                                    id="media_size" 
                                    class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                <option value="thumbnail" <?php echo $media['size_type'] == 'thumbnail' ? 'selected' : ''; ?>>Thumbnail (300×300)</option>
                                <option value="medium" <?php echo $media['size_type'] == 'medium' ? 'selected' : ''; ?>>Medium (800×600)</option>
                                <option value="large" <?php echo $media['size_type'] == 'large' ? 'selected' : ''; ?>>Large (1920×1080)</option>
                                <option value="custom" <?php echo $media['size_type'] == 'custom' ? 'selected' : ''; ?>>Custom Size</option>
                            </select>
                            
                            <div id="custom_dimensions" class="grid grid-cols-2 gap-2" style="display: <?php echo $media['size_type'] == 'custom' ? 'grid' : 'none'; ?>">
                                <div>
                                    <label class="block text-xs text-gray-500">Width</label>
                                    <input type="number" 
                                           name="custom_width" 
                                           value="<?php echo $media['width']; ?>"
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                           placeholder="Width">
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500">Height</label>
                                    <input type="number" 
                                           name="custom_height" 
                                           value="<?php echo $media['height']; ?>"
                                           class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                           placeholder="Height">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Image Options</label>
                        <div class="space-y-2">
                            <div class="flex items-center">
                                <input type="checkbox" 
                                       name="maintain_aspect_ratio" 
                                       id="maintain_aspect_ratio" 
                                       <?php echo $media['maintain_aspect_ratio'] ? 'checked' : ''; ?>
                                       class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                                <label for="maintain_aspect_ratio" class="ml-2 block text-sm text-gray-900">
                                    Maintain Aspect Ratio
                                </label>
                            </div>
                            
                            <div>
                                <label for="background_color" class="block text-sm text-gray-700">Background Color</label>
                                <div class="mt-1 flex items-center gap-2">
                                    <input type="color" 
                                           name="background_color" 
                                           id="background_color" 
                                           value="<?php echo $media['background_color']; ?>"
                                           class="h-8 w-8 rounded-md border border-gray-300">
                                    <span class="text-sm text-gray-500" id="color_hex"><?php echo strtoupper($media['background_color']); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- New File Upload -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Replace File</label>
                    <input type="file" 
                           name="file" 
                           class="filepond">
                    <p class="mt-2 text-sm text-gray-500">
                        Upload a new file to replace the current one.
                        <br>Supported: Images (JPEG, PNG, GIF), Videos (MP4, WebM), Audio (MP3, WAV)
                        <br>Maximum size: 50MB
                    </p>
                </div>

                <!-- Status Options -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="flex items-center space-x-4">
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   name="status" 
                                   id="status" 
                                   <?php echo $media['status'] ? 'checked' : ''; ?>
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="status" class="ml-2 block text-sm text-gray-900">
                                Active
                            </label>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" 
                                   name="featured" 
                                   id="featured" 
                                   <?php echo $media['featured'] ? 'checked' : ''; ?>
                                   class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
                            <label for="featured" class="ml-2 block text-sm text-gray-900">
                                Featured
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end">
                        <button type="submit" 
                                class="inline-flex justify-center py-2 px-4 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                            <i class="fas fa-save mr-2"></i> Update Media
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/filepond-plugin-image-preview@4.6.11/dist/filepond-plugin-image-preview.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/filepond@4.30.4/dist/filepond.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>
    <script>
        // Initialize Tagify
        new Tagify(document.querySelector('.tagify'), {
            delimiters: ',',
            maxTags: 10,
            backspace: "edit",
            placeholder: "Enter tags separated by commas",
            dropdown: {
                enabled: 0
            }
        });

        // Initialize FilePond
        FilePond.registerPlugin(FilePondPluginImagePreview);

        const inputElement = document.querySelector('input[type="file"]');
        const pond = FilePond.create(inputElement, {
            allowMultiple: false,
            instantUpload: false,
            allowReorder: false,
            maxFileSize: '50MB',
            labelIdle: 'Drag & Drop your file or <span class="filepond--label-action">Browse</span>',
            acceptedFileTypes: ['image/*', 'video/*', 'audio/*']
        });

        // Handle custom dimensions visibility
        document.getElementById('media_size').addEventListener('change', function() {
            const customDimensions = document.getElementById('custom_dimensions');
            customDimensions.style.display = this.value === 'custom' ? 'grid' : 'none';
        });

        // Update color hex display
        document.getElementById('background_color').addEventListener('input', function() {
            document.getElementById('color_hex').textContent = this.value.toUpperCase();
        });

        // Form validation
        document.querySelector('form').addEventListener('submit', function(e) {
            const mediaSize = document.getElementById('media_size').value;
            
            if (mediaSize === 'custom') {
                const width = parseInt(document.querySelector('[name="custom_width"]').value);
                const height = parseInt(document.querySelector('[name="custom_height"]').value);
                
                if (!width || !height || width < 1 || height < 1) {
                    e.preventDefault();
                    alert('Please enter valid dimensions for custom size.');
                    return;
                }
                
                if (width > 4096 || height > 4096) {
                    e.preventDefault();
                    alert('Maximum allowed dimensions are 4096×4096 pixels.');
                    return;
                }
            }
        });
    </script>
</body>
</html>