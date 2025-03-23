<?php
/**
 * Edit Media Page
 *
 * @package WallPix
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'Edit Media';

// Include header
require_once '../../theme/admin/header.php';

// Include sidebar
require_once '../../theme/admin/slidbar.php';

// Current UTC date/time
$currentDateTime = '2025-03-23 02:10:06';
// Current user
$currentUser = 'mahranalsarminy';

// Check if ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
            <p>Invalid media ID.</p>
            <p class="mt-2">
                <a href="' . $adminUrl . '/media/index.php" class="text-blue-600 hover:underline">
                    <i class="fas fa-arrow-left mr-1"></i> Return to media list
                </a>
            </p>
          </div>';
    require_once __DIR__ . '/../../theme/admin/footer.php';
    exit;
}

// Get media ID
$mediaId = (int)$_GET['id'];

// Process form submission
$successMessage = '';
$errorMessage = '';

// Get categories for dropdown
$stmt = $pdo->query("SELECT id, name, parent_id FROM categories WHERE is_active = 1 ORDER BY name");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get tags for dropdown
$stmt = $pdo->query("SELECT id, name FROM tags ORDER BY name");
$tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get colors for dropdown
$stmt = $pdo->query("SELECT id, color_name, hex_code FROM colors ORDER BY color_name");
$colors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get resolutions for dropdown
$stmt = $pdo->query("SELECT id, resolution FROM resolutions ORDER BY resolution");
$resolutions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get watermark settings
$stmt = $pdo->query("SELECT id, is_active FROM watermark_settings WHERE id = 1");
$watermarkSettings = $stmt->fetch(PDO::FETCH_ASSOC);
$watermarkEnabled = $watermarkSettings ? $watermarkSettings['is_active'] : 0;

// Function to create nested category dropdown
function buildCategoryOptions($categories, $selectedId = null, $parent = null, $indent = '') {
    $html = '';
    foreach ($categories as $category) {
        if (($parent === null && $category['parent_id'] === null) || $category['parent_id'] === $parent) {
            $selected = ($category['id'] == $selectedId) ? 'selected' : '';
            $html .= '<option value="' . $category['id'] . '" ' . $selected . '>' . $indent . htmlspecialchars($category['name']) . '</option>';
            
            // Find children
            $html .= buildCategoryOptions($categories, $selectedId, $category['id'], $indent . '&nbsp;&nbsp;&nbsp;');
        }
    }
    return $html;
}

// Function to generate a thumbnail from an uploaded image
function generateThumbnail($sourcePath, $targetPath, $width = 300, $height = 200) {
    list($sourceWidth, $sourceHeight, $sourceType) = getimagesize($sourcePath);
    
    // Calculate new dimensions maintaining aspect ratio
    $ratio = min($width / $sourceWidth, $height / $sourceHeight);
    $newWidth = (int)($sourceWidth * $ratio);
    $newHeight = (int)($sourceHeight * $ratio);
    
    // Create new image based on file type
    switch ($sourceType) {
        case IMAGETYPE_JPEG:
            $sourceImage = imagecreatefromjpeg($sourcePath);
            break;
        case IMAGETYPE_PNG:
            $sourceImage = imagecreatefrompng($sourcePath);
            break;
        case IMAGETYPE_GIF:
            $sourceImage = imagecreatefromgif($sourcePath);
            break;
        case IMAGETYPE_WEBP:
            $sourceImage = imagecreatefromwebp($sourcePath);
            break;
        default:
            return false;
    }
    
    // Create new true color image
    $targetImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency for PNG images
    if ($sourceType == IMAGETYPE_PNG) {
        imagealphablending($targetImage, false);
        imagesavealpha($targetImage, true);
        $transparent = imagecolorallocatealpha($targetImage, 255, 255, 255, 127);
        imagefilledrectangle($targetImage, 0, 0, $newWidth, $newHeight, $transparent);
    }
    
    // Resize the image
    imagecopyresampled($targetImage, $sourceImage, 0, 0, 0, 0, $newWidth, $newHeight, $sourceWidth, $sourceHeight);
    
    // Save the thumbnail
    switch ($sourceType) {
        case IMAGETYPE_JPEG:
            imagejpeg($targetImage, $targetPath, 90);
            break;
        case IMAGETYPE_PNG:
            imagepng($targetImage, $targetPath, 9);
            break;
        case IMAGETYPE_GIF:
            imagegif($targetImage, $targetPath);
            break;
        case IMAGETYPE_WEBP:
            imagewebp($targetImage, $targetPath, 80);
            break;
    }
    
    // Free memory
    imagedestroy($sourceImage);
    imagedestroy($targetImage);
    
    return true;
}

// Function to extract dominant colors from an image
function extractDominantColors($imagePath) {
    // Default values in case extraction fails
    $primaryColor = '#FFFFFF';
    $secondaryColor = '#000000';
    $isDark = 0;
    
    try {
        list($width, $height, $type) = getimagesize($imagePath);
        
        // Create image resource
        switch ($type) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($imagePath);
                break;
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($imagePath);
                break;
            case IMAGETYPE_WEBP:
                $image = imagecreatefromwebp($imagePath);
                break;
            default:
                return ['primary' => $primaryColor, 'secondary' => $secondaryColor, 'is_dark' => $isDark];
        }
        
        // Resize for faster processing
        $newWidth = 100;
        $newHeight = 100;
        $resized = imagecreatetruecolor($newWidth, $newHeight);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Get colors
        $colorCount = [];
        for ($x = 0; $x < $newWidth; $x++) {
            for ($y = 0; $y < $newHeight; $y++) {
                $rgb = imagecolorat($resized, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                
                // Skip very light or dark pixels
                if (($r + $g + $b) < 50 || ($r + $g + $b) > 700) {
                    continue;
                }
                
                // Convert to hex and count
                $hex = sprintf("#%02x%02x%02x", $r, $g, $b);
                if (!isset($colorCount[$hex])) {
                    $colorCount[$hex] = 1;
                } else {
                    $colorCount[$hex]++;
                }
            }
        }
        
        // Sort colors by count
        arsort($colorCount);
        
        // Get primary and secondary colors
        $colors = array_keys($colorCount);
        $primaryColor = isset($colors[0]) ? $colors[0] : $primaryColor;
        $secondaryColor = isset($colors[1]) ? $colors[1] : (isset($colors[0]) ? $colors[0] : $secondaryColor);
        
        // Calculate brightness of primary color
        $r = hexdec(substr($primaryColor, 1, 2));
        $g = hexdec(substr($primaryColor, 3, 2));
        $b = hexdec(substr($primaryColor, 5, 2));
        $brightness = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
        $isDark = ($brightness < 128) ? 1 : 0;
        
        // Free memory
        imagedestroy($image);
        imagedestroy($resized);
        
        return ['primary' => $primaryColor, 'secondary' => $secondaryColor, 'is_dark' => $isDark];
    } catch (Exception $e) {
        return ['primary' => $primaryColor, 'secondary' => $secondaryColor, 'is_dark' => $isDark];
    }
}

// Apply watermark to image
function applyWatermark($imagePath, $watermarkSettings) {
    // This would be implemented based on your watermark settings
    // Basic implementation here - full version would need to be expanded
    try {
        list($width, $height, $type) = getimagesize($imagePath);
        
        // Create image resource
        switch ($type) {
            case IMAGETYPE_JPEG:
                $image = imagecreatefromjpeg($imagePath);
                break;
            case IMAGETYPE_PNG:
                $image = imagecreatefrompng($imagePath);
                break;
            case IMAGETYPE_GIF:
                $image = imagecreatefromgif($imagePath);
                break;
            case IMAGETYPE_WEBP:
                $image = imagecreatefromwebp($imagePath);
                break;
            default:
                return false;
        }
        
        // If text watermark
        if ($watermarkSettings['type'] === 'text') {
            $text = $watermarkSettings['text_content'];
            $font = __DIR__ . '/../../assets/fonts/arial.ttf'; // Adjust path to your font
            $fontSize = $watermarkSettings['text_size'];
            
            // Convert hex color to rgb
            $color = $watermarkSettings['text_color'];
            $r = hexdec(substr($color, 1, 2));
            $g = hexdec(substr($color, 3, 2));
            $b = hexdec(substr($color, 5, 2));
            
            // Create color with opacity
            $textColor = imagecolorallocatealpha($image, $r, $g, $b, (1 - $watermarkSettings['text_opacity']) * 127);
            
            // Calculate position based on settings
            switch ($watermarkSettings['position']) {
                case 'top-left':
                    $x = $watermarkSettings['padding'];
                    $y = $fontSize + $watermarkSettings['padding'];
                    break;
                case 'top-right':
                    $box = imagettfbbox($fontSize, 0, $font, $text);
                    $textWidth = $box[2] - $box[0];
                    $x = $width - $textWidth - $watermarkSettings['padding'];
                    $y = $fontSize + $watermarkSettings['padding'];
                    break;
                case 'bottom-left':
                    $x = $watermarkSettings['padding'];
                    $y = $height - $watermarkSettings['padding'];
                    break;
                case 'bottom-right':
                    $box = imagettfbbox($fontSize, 0, $font, $text);
                    $textWidth = $box[2] - $box[0];
                    $x = $width - $textWidth - $watermarkSettings['padding'];
                    $y = $height - $watermarkSettings['padding'];
                    break;
                case 'center':
                    $box = imagettfbbox($fontSize, 0, $font, $text);
                    $textWidth = $box[2] - $box[0];
                    $x = ($width - $textWidth) / 2;
                    $y = ($height + $fontSize) / 2;
                    break;
                case 'full':
                    // For full, we would add multiple watermarks in a pattern
                    // Basic implementation would add one
                    $x = $width / 2;
                    $y = $height / 2;
                    break;
            }
            
            // Add text watermark
            imagettftext($image, $fontSize, 0, $x, $y, $textColor, $font, $text);
        }
        // If image watermark
        else if ($watermarkSettings['type'] === 'image' && !empty($watermarkSettings['image_path'])) {
            $watermarkPath = __DIR__ . '/../..' . $watermarkSettings['image_path'];
            
            if (file_exists($watermarkPath)) {
                // Get watermark image info
                $watermarkInfo = getimagesize($watermarkPath);
                $watermarkType = $watermarkInfo[2];
                
                // Create watermark image resource
                switch ($watermarkType) {
                    case IMAGETYPE_JPEG:
                        $watermark = imagecreatefromjpeg($watermarkPath);
                        break;
                    case IMAGETYPE_PNG:
                        $watermark = imagecreatefrompng($watermarkPath);
                        break;
                    case IMAGETYPE_GIF:
                        $watermark = imagecreatefromgif($watermarkPath);
                        break;
                    case IMAGETYPE_WEBP:
                        $watermark = imagecreatefromwebp($watermarkPath);
                        break;
                    default:
                        return false;
                }
                
                // Set opacity
                imagealphablending($watermark, false);
                imagesavealpha($watermark, true);
                
                // Get watermark dimensions
                $watermarkWidth = imagesx($watermark);
                $watermarkHeight = imagesy($watermark);
                
                // Calculate position based on settings
                switch ($watermarkSettings['position']) {
                    case 'top-left':
                        $x = $watermarkSettings['padding'];
                        $y = $watermarkSettings['padding'];
                        break;
                    case 'top-right':
                        $x = $width - $watermarkWidth - $watermarkSettings['padding'];
                        $y = $watermarkSettings['padding'];
                        break;
                    case 'bottom-left':
                        $x = $watermarkSettings['padding'];
                        $y = $height - $watermarkHeight - $watermarkSettings['padding'];
                        break;
                    case 'bottom-right':
                        $x = $width - $watermarkWidth - $watermarkSettings['padding'];
                        $y = $height - $watermarkHeight - $watermarkSettings['padding'];
                        break;
                    case 'center':
                        $x = ($width - $watermarkWidth) / 2;
                        $y = ($height - $watermarkHeight) / 2;
                        break;
                    case 'full':
                        // For full, add multiple watermarks in a pattern
                        // Basic implementation would add one
                        $x = $width / 2 - $watermarkWidth / 2;
                        $y = $height / 2 - $watermarkHeight / 2;
                        break;
                }
                
                // Add image watermark with opacity
                imagecopymerge($image, $watermark, $x, $y, 0, 0, $watermarkWidth, $watermarkHeight, $watermarkSettings['image_opacity'] * 100);
                
                // Free memory
                imagedestroy($watermark);
            }
        }
        
        // Save the watermarked image
        switch ($type) {
            case IMAGETYPE_JPEG:
                imagejpeg($image, $imagePath, 90);
                break;
            case IMAGETYPE_PNG:
                imagepng($image, $imagePath, 9);
                break;
            case IMAGETYPE_GIF:
                imagegif($image, $imagePath);
                break;
            case IMAGETYPE_WEBP:
                imagewebp($image, $imagePath, 80);
                break;
        }
        
        // Free memory
        imagedestroy($image);
        
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Function to add a new tag if it doesn't exist
function addNewTag($pdo, $tagName) {
    // Check if tag already exists
    $stmt = $pdo->prepare("SELECT id FROM tags WHERE name = :name");
    $stmt->execute([':name' => $tagName]);
    $existingTag = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingTag) {
        return $existingTag['id'];
    }
    
    // Create the new tag
    $stmt = $pdo->prepare("
        INSERT INTO tags (name, created_at) 
        VALUES (:name, NOW())
    ");
    $stmt->execute([':name' => $tagName]);
    
    // Log the activity
    $userId = $_SESSION['user_id'] ?? 1; // Default to admin if not set
    $newTagId = $pdo->lastInsertId();
    $stmt = $pdo->prepare("
        INSERT INTO activities (user_id, description, created_at)
        VALUES (:user_id, :description, NOW())
    ");
    $stmt->execute([
        ':user_id' => $userId,
        ':description' => "Added new tag: {$tagName} (ID: {$newTagId})"
    ]);
    
    return $newTagId;
}

// Function to get child media items (collection items)
function getChildMediaItems($pdo, $parentId) {
    $stmt = $pdo->prepare("SELECT * FROM media WHERE parent_id = :parent_id ORDER BY id");
    $stmt->execute([':parent_id' => $parentId]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get existing media data
try {
    $stmt = $pdo->prepare("SELECT * FROM media WHERE id = :id");
    $stmt->execute([':id' => $mediaId]);
    $media = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$media) {
        echo '<div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p>Media not found.</p>
                <p class="mt-2">
                    <a href="' . $adminUrl . '/media/index.php" class="text-blue-600 hover:underline">
                        <i class="fas fa-arrow-left mr-1"></i> Return to media list
                    </a>
                </p>
              </div>';
        require_once __DIR__ . '/../../theme/admin/footer.php';
        exit;
    }
    
    // Get media tags
    $stmt = $pdo->prepare("
        SELECT t.id, t.name 
        FROM tags t 
        JOIN media_tags mt ON t.id = mt.tag_id 
        WHERE mt.media_id = :media_id
    ");
    $stmt->execute([':media_id' => $mediaId]);
    $mediaTags = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get additional categories
    $stmt = $pdo->prepare("
        SELECT category_id 
        FROM media_categories 
        WHERE media_id = :media_id
    ");
    $stmt->execute([':media_id' => $mediaId]);
    $additionalCategoryIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'category_id');
    
    // Get colors
    $stmt = $pdo->prepare("
        SELECT primary_color, secondary_color, is_dark 
        FROM media_colors 
        WHERE media_id = :media_id
    ");
    $stmt->execute([':media_id' => $mediaId]);
    $mediaColors = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if this is a collection (has child items) or is part of a collection (has parent)
    $isCollection = false;
    $isCollectionItem = false;
    $collectionItems = [];
    $parentMedia = null;
    
    // Check for child items
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM media WHERE parent_id = :id");
    $stmt->execute([':id' => $mediaId]);
    $childCount = (int)$stmt->fetchColumn();
    
    if ($childCount > 0) {
        $isCollection = true;
        $collectionItems = getChildMediaItems($pdo, $mediaId);
    }
    
    // Check if it's a collection item
    if (!empty($media['parent_id'])) {
        $isCollectionItem = true;
        $stmt = $pdo->prepare("SELECT id, title FROM media WHERE id = :parent_id");
        $stmt->execute([':parent_id' => $media['parent_id']]);
        $parentMedia = $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
} catch (Exception $e) {
    $errorMessage = 'Error: ' . $e->getMessage();
}

// Process form submission for updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Get form data
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $backgroundColorHex = $_POST['background_color'] ?? '#FFFFFF';
        $featured = isset($_POST['featured']) ? 1 : 0;
        $orientation = $_POST['orientation'] ?? 'portrait';
        $owner = trim($_POST['owner'] ?? '');
        $license = trim($_POST['license'] ?? '');
        $publishDate = $_POST['publish_date'] ?? date('Y-m-d');
        $paidContent = isset($_POST['paid_content']) ? 1 : 0;
        $status = isset($_POST['status']) ? 1 : 0;
        $aiEnhanced = isset($_POST['ai_enhanced']) ? 1 : 0;
        $uploadType = $_POST['upload_type'] ?? 'keep';
        $applyWatermark = isset($_POST['apply_watermark']) ? 1 : 0;
        $selectedCategories = $_POST['additional_categories'] ?? [];
        $resolution_id = (int)($_POST['resolution_id'] ?? 0);
        
        // Add primary category to selected categories if not already there
        if ($categoryId && !in_array($categoryId, $selectedCategories)) {
            array_unshift($selectedCategories, $categoryId);
        }
        
        // Validate required fields
        if (empty($title)) {
            throw new Exception('Title is required.');
        }
        
        $filePath = $media['file_path'];
        $fileName = $media['file_name'];
        $fileType = $media['file_type'];
        $fileSize = $media['file_size'];
        $thumbnailUrl = $media['thumbnail_url'];
        $width = $media['width'];
        $height = $media['height'];
        
        // Handle file upload if replacing
        if ($uploadType === 'replace' && isset($_FILES['media_file']) && $_FILES['media_file']['error'] === 0) {
            $uploadDir = __DIR__ . '/../../uploads/media/' . date('Y/m') . '/';
            $thumbnailDir = __DIR__ . '/../../uploads/thumbnails/' . date('Y/m') . '/';
            
            // Create directories if they don't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            if (!is_dir($thumbnailDir)) {
                mkdir($thumbnailDir, 0755, true);
            }
            
            // Get file info
            $originalFileName = $_FILES['media_file']['name'];
            $fileSize = $_FILES['media_file']['size'];
            $fileTmpName = $_FILES['media_file']['tmp_name'];
            $fileType = $_FILES['media_file']['type'];
            
            // Generate unique filename
            $uniqid = uniqid();
            $fileName = $uniqid . '_' . basename($originalFileName);
            $filePath = '/uploads/media/' . date('Y/m') . '/' . $fileName;
            $fullFilePath = $uploadDir . $fileName;
            
            // Move the uploaded file
            if (move_uploaded_file($fileTmpName, $fullFilePath)) {
                // Generate thumbnail for images
                if (strpos($fileType, 'image/') === 0) {
                    $thumbnailName = 'thumb_' . $fileName;
                    $thumbnailPath = $thumbnailDir . $thumbnailName;
                    $thumbnailUrl = '/uploads/thumbnails/' . date('Y/m') . '/' . $thumbnailName;
                    
                    if (generateThumbnail($fullFilePath, $thumbnailPath)) {
                        // Get image dimensions
                        list($width, $height) = getimagesize($fullFilePath);
                        
                        // Apply watermark if enabled and requested
                        if ($watermarkEnabled && $applyWatermark) {
                            $stmt = $pdo->query("SELECT * FROM watermark_settings WHERE id = 1");
                            $watermarkSettings = $stmt->fetch(PDO::FETCH_ASSOC);
                            
                            if ($watermarkSettings && $watermarkSettings['apply_to_new']) {
                                applyWatermark($fullFilePath, $watermarkSettings);
                            }
                        }
                        
                        // Extract dominant colors
                        $dominantColors = extractDominantColors($fullFilePath);
                    }
                } else {
                    // For non-image files, use a generic thumbnail based on file type
                    $thumbnailUrl = '/assets/images/file-thumbnails/' . getFileTypeIcon($fileType);
                }
                
                // Delete old file if it exists
                if (!empty($media['file_path']) && $media['file_path'] !== $filePath) {
                    $oldFilePath = __DIR__ . '/../..' . $media['file_path'];
                    if (file_exists($oldFilePath)) {
                        @unlink($oldFilePath);
                    }
                }
                
                // Delete old thumbnail if it exists
                if (!empty($media['thumbnail_url']) && $media['thumbnail_url'] !== $thumbnailUrl) {
                    $oldThumbnailPath = __DIR__ . '/../..' . $media['thumbnail_url'];
                    if (file_exists($oldThumbnailPath)) {
                        @unlink($oldThumbnailPath);
                    }
                }
            } else {
                throw new Exception('Failed to upload file.');
            }
        } else if ($uploadType === 'collection') {
            // Process collection files
            $collectionFiles = $_FILES['collection_files'] ?? [];
            
            // Validate at least one file is uploaded
            if (empty($collectionFiles['name'][0])) {
                throw new Exception('Please upload at least one file in the collection.');
            }
            
            // We'll keep the main media record and add new collection items
            
            // Convert this media to a collection parent if it's not already one
            if (!$isCollection) {
                // No need to update the main record file, just add collection items
            }
            
            // Process collection files
            $uploadDir = __DIR__ . '/../../uploads/media/' . date('Y/m') . '/';
            $thumbnailDir = __DIR__ . '/../../uploads/thumbnails/' . date('Y/m') . '/';
            
            // Create directories if they don't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            if (!is_dir($thumbnailDir)) {
                mkdir($thumbnailDir, 0755, true);
            }
            
            // Process each collection file
            for ($i = 0; $i < count($collectionFiles['name']); $i++) {
                if ($collectionFiles['error'][$i] === 0) {
                    $collectionFileTmp = $collectionFiles['tmp_name'][$i];
                    $collectionFileName = $collectionFiles['name'][$i];
                    $collectionFileSize = $collectionFiles['size'][$i];
                    $collectionFileType = $collectionFiles['type'][$i];
                    
                    // Generate unique filename for each collection file
                    $collUniqid = uniqid();
                    $collFileName = $collUniqid . '_' . basename($collectionFileName);
                    $collFilePath = '/uploads/media/' . date('Y/m') . '/' . $collFileName;
                    $collFullFilePath = $uploadDir . $collFileName;
                    
                    // Create collection item record
                    if (move_uploaded_file($collectionFileTmp, $collFullFilePath)) {
                        // Generate thumbnail if it's an image
                        $collThumbnailUrl = '';
                        $collWidth = '';
                        $collHeight = '';
                        
                        if (strpos($collectionFileType, 'image/') === 0) {
                            $collThumbnailName = 'thumb_' . $collFileName;
                            $collThumbnailPath = $thumbnailDir . $collThumbnailName;
                            $collThumbnailUrl = '/uploads/thumbnails/' . date('Y/m') . '/' . $collThumbnailName;
                            
                            if (generateThumbnail($collFullFilePath, $collThumbnailPath)) {
                                list($collWidth, $collHeight) = getimagesize($collFullFilePath);
                                
                                // Apply watermark if needed
                                if ($watermarkEnabled && $applyWatermark) {
                                    $stmt = $pdo->query("SELECT * FROM watermark_settings WHERE id = 1");
                                    $watermarkSettings = $stmt->fetch(PDO::FETCH_ASSOC);
                                    
                                    if ($watermarkSettings && $watermarkSettings['apply_to_new']) {
                                        applyWatermark($collFullFilePath, $watermarkSettings);
                                    }
                                }
                            }
                        }
                        
                        // Insert collection item into database
                        $collectionTitle = $title . ' - Item ' . ($i + 1);
                        
                        $stmt = $pdo->prepare("
                            INSERT INTO media (
                                title, description, category_id, file_name, file_path, file_type, file_size,
                                thumbnail_url, status, featured, width, height, background_color, orientation,
                                owner, license, publish_date, paid_content, created_by, ai_enhanced, resolution_id,
                                parent_id, created_at
                            ) VALUES (
                                :title, :description, :category_id, :file_name, :file_path, :file_type, :file_size,
                                :thumbnail_url, :status, :featured, :width, :height, :background_color, :orientation,
                                :owner, :license, :publish_date, :paid_content, :created_by, :ai_enhanced, :resolution_id,
                                :parent_id, NOW()
                            )
                        ");
                        
                        $stmt->execute([
                            ':title' => $collectionTitle,
                            ':description' => $description,
                            ':category_id' => $categoryId,
                            ':file_name' => $collFileName,
                            ':file_path' => $collFilePath,
                            ':file_type' => $collectionFileType,
                            ':file_size' => $collectionFileSize,
                            ':thumbnail_url' => $collThumbnailUrl,
                            ':status' => $status,
                            ':featured' => $featured,
                            ':width' => $collWidth,
                            ':height' => $collHeight,
                            ':background_color' => $backgroundColorHex,
                            ':orientation' => $orientation,
                            ':owner' => $owner,
                            ':license' => $license,
                            ':publish_date' => $publishDate,
                            ':paid_content' => $paidContent,
                            ':created_by' => $_SESSION['user_id'] ?? 1,
                            ':ai_enhanced' => $aiEnhanced,
                            ':resolution_id' => $resolution_id,
                            ':parent_id' => $mediaId
                        ]);
                        
                        $collItemId = $pdo->lastInsertId();
                        
                        // We'll handle tags and categories after the main record is updated
                    }
                }
            }
        }
        
        // Handle input tags - convert to array of IDs (existing + new)
        $tagIds = [];
        if (!empty($_POST['tag_input'])) {
            $tagNames = explode(',', $_POST['tag_input']);
            foreach ($tagNames as $tagName) {
                $tagName = trim($tagName);
                if (!empty($tagName)) {
                    // Check if tag exists or add new
                    $tagIds[] = addNewTag($pdo, $tagName);
                }
            }
        }
        
        // Update media record
        $stmt = $pdo->prepare("
            UPDATE media SET
                title = :title,
                description = :description,
                category_id = :category_id,
                file_name = :file_name,
                file_path = :file_path,
                file_type = :file_type,
                file_size = :file_size,
                thumbnail_url = :thumbnail_url,
                status = :status,
                featured = :featured,
                width = :width,
                height = :height,
                background_color = :background_color,
                orientation = :orientation,
                owner = :owner,
                license = :license,
                publish_date = :publish_date,
                paid_content = :paid_content,
                ai_enhanced = :ai_enhanced,
                resolution_id = :resolution_id,
                updated_at = NOW()
            WHERE id = :id
        ");
        
        $stmt->execute([
            ':title' => $title,
            ':description' => $description,
            ':category_id' => $categoryId,
            ':file_name' => $fileName,
            ':file_path' => $filePath,
            ':file_type' => $fileType,
            ':file_size' => $fileSize,
            ':thumbnail_url' => $thumbnailUrl,
            ':status' => $status,
            ':featured' => $featured,
            ':width' => $width,
            ':height' => $height,
            ':background_color' => $backgroundColorHex,
            ':orientation' => $orientation,
            ':owner' => $owner,
            ':license' => $license,
            ':publish_date' => $publishDate,
            ':paid_content' => $paidContent,
            ':ai_enhanced' => $aiEnhanced,
            ':resolution_id' => $resolution_id,
            ':id' => $mediaId
        ]);
        
        // Update dominant colors if available
        if (isset($dominantColors)) {
            // Check if colors exist
            $stmt = $pdo->prepare("SELECT id FROM media_colors WHERE media_id = :media_id");
            $stmt->execute([':media_id' => $mediaId]);
            $colorExists = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($colorExists) {
                // Update existing colors
                $stmt = $pdo->prepare("
                    UPDATE media_colors SET
                        primary_color = :primary_color,
                        secondary_color = :secondary_color,
                        is_dark = :is_dark,
                        updated_at = NOW()
                    WHERE media_id = :media_id
                ");
                
                $stmt->execute([
                    ':media_id' => $mediaId,
                    ':primary_color' => $dominantColors['primary'],
                    ':secondary_color' => $dominantColors['secondary'],
                    ':is_dark' => $dominantColors['is_dark']
                ]);
            } else {
                // Insert new colors
                $stmt = $pdo->prepare("
                    INSERT INTO media_colors (
                        media_id, primary_color, secondary_color, is_dark, created_at
                    ) VALUES (
                        :media_id, :primary_color, :secondary_color, :is_dark, NOW()
                    )
                ");
                
                $stmt->execute([
                    ':media_id' => $mediaId,
                    ':primary_color' => $dominantColors['primary'],
                    ':secondary_color' => $dominantColors['secondary'],
                    ':is_dark' => $dominantColors['is_dark']
                ]);
            }
        }
        
        // Update tags - first delete existing
        $stmt = $pdo->prepare("DELETE FROM media_tags WHERE media_id = :media_id");
        $stmt->execute([':media_id' => $mediaId]);
        
        // Insert new tags
        if (!empty($tagIds)) {
            $insertTagStmt = $pdo->prepare("
                INSERT INTO media_tags (media_id, tag_id, created_by)
                VALUES (:media_id, :tag_id, :created_by)
            ");
            
            foreach ($tagIds as $tagId) {
                $insertTagStmt->execute([
                    ':media_id' => $mediaId,
                    ':tag_id' => (int)$tagId,
                    ':created_by' => $_SESSION['user_id'] ?? null
                ]);
            }
        }
        
        // Update additional categories - first delete existing
        $stmt = $pdo->prepare("DELETE FROM media_categories WHERE media_id = :media_id");
        $stmt->execute([':media_id' => $mediaId]);
        
        // Insert new categories
        if (!empty($selectedCategories)) {
            $insertCategoryStmt = $pdo->prepare("
                INSERT INTO media_categories (media_id, category_id)
                VALUES (:media_id, :category_id)
            ");
            
            foreach ($selectedCategories as $catId) {
                if ($catId != $categoryId) { // Skip primary category
                    $insertCategoryStmt->execute([
                        ':media_id' => $mediaId,
                        ':category_id' => (int)$catId
                    ]);
                }
            }
        }
        
        // Update collection items with the same tags (if this is a collection)
        if ($isCollection && !empty($tagIds)) {
            foreach ($collectionItems as $item) {
                // Delete existing tags for collection item
                $stmt = $pdo->prepare("DELETE FROM media_tags WHERE media_id = :media_id");
                $stmt->execute([':media_id' => $item['id']]);
                
                // Add new tags
                foreach ($tagIds as $tagId) {
                    $insertTagStmt->execute([
                        ':media_id' => $item['id'],
                        ':tag_id' => (int)$tagId,
                        ':created_by' => $_SESSION['user_id'] ?? null
                    ]);
                }
            }
        }
        
        // If we added collection files, apply tags to them too
        if ($uploadType === 'collection' && !empty($tagIds)) {
            // Get newly added collection items
            $stmt = $pdo->prepare("SELECT id FROM media WHERE parent_id = :parent_id AND created_at > :created_time");
            $stmt->execute([
                ':parent_id' => $mediaId,
                ':created_time' => date('Y-m-d H:i:s', strtotime('-5 minutes')) // Assume items created in the last 5 minutes are from this edit
            ]);
            $newCollectionItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($newCollectionItems as $item) {
                foreach ($tagIds as $tagId) {
                    $insertTagStmt->execute([
                        ':media_id' => $item['id'],
                        ':tag_id' => (int)$tagId,
                        ':created_by' => $_SESSION['user_id'] ?? null
                    ]);
                }
            }
        }
        
        // Log the activity
        $stmt = $pdo->prepare("
            INSERT INTO activities (user_id, description, created_at)
            VALUES (:user_id, :description, NOW())
        ");
        
        $stmt->execute([
            ':user_id' => $_SESSION['user_id'] ?? null,
            ':description' => "Updated media item #{$mediaId}: {$title}"
        ]);
        
        // Commit transaction
        $pdo->commit();
        
        $successMessage = "Media item '{$title}' has been updated successfully.";
        
        // Refresh media data after update
        $stmt = $pdo->prepare("SELECT * FROM media WHERE id = :id");
        $stmt->execute([':id' => $mediaId]);
        $media = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get media tags
        $stmt = $pdo->prepare("
            SELECT t.id, t.name 
            FROM tags t 
            JOIN media_tags mt ON t.id = mt.tag_id 
            WHERE mt.media_id = :media_id
        ");
        $stmt->execute([':media_id' => $mediaId]);
        $mediaTags = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get additional categories
        $stmt = $pdo->prepare("
            SELECT category_id 
            FROM media_categories 
            WHERE media_id = :media_id
        ");
        $stmt->execute([':media_id' => $mediaId]);
        $additionalCategoryIds = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'category_id');
        
        // Get colors
        $stmt = $pdo->prepare("
            SELECT primary_color, secondary_color, is_dark 
            FROM media_colors 
            WHERE media_id = :media_id
        ");
        $stmt->execute([':media_id' => $mediaId]);
        $mediaColors = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Refresh collection information
        if ($isCollection) {
            $collectionItems = getChildMediaItems($pdo, $mediaId);
        }
    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $errorMessage = 'Error: ' . $e->getMessage();
    }
}

// Helper function to get file type icon
function getFileTypeIcon($mimeType) {
    $icons = [
        'image/' => 'image-icon.png',
        'video/' => 'video-icon.png',
        'audio/' => 'audio-icon.png',
        'application/pdf' => 'pdf-icon.png',
        'text/' => 'text-icon.png',
        'application/zip' => 'archive-icon.png',
        'application/x-rar' => 'archive-icon.png',
    ];
    
    foreach ($icons as $type => $icon) {
        if (strpos($mimeType, $type) === 0) {
            return $icon;
        }
    }
    
    return 'file-icon.png'; // Default icon
}

// Function to check if a media item is used in a collection
function isUsedInCollection($pdo, $mediaId) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM media WHERE parent_id = :id");
    $stmt->execute([':id' => $mediaId]);
    return (int)$stmt->fetchColumn() > 0;
}
?>

<!-- Main Content -->
<div class="content-wrapper px-4 py-6 lg:px-8">
    <div class="max-w-full mx-auto">
        <!-- Page Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold <?php echo $darkMode ? 'text-gray-200' : 'text-gray-800'; ?>">
                <i class="fas fa-edit mr-2"></i> Edit Media: <?php echo htmlspecialchars($media['title']); ?>
            </h1>
            <div>
                <a href="<?php echo $adminUrl; ?>/media/view.php?id=<?php echo $mediaId; ?>" class="px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition-colors mr-2">
                    <i class="fas fa-eye mr-2"></i> View
                </a>
                <a href="<?php echo $adminUrl; ?>/media/index.php" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Media List
                </a>
            </div>
        </div>
        
        <?php if ($isCollectionItem): ?>
            <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6 <?php echo $darkMode ? 'bg-blue-900 text-blue-200 border-blue-400' : ''; ?>">
                <p>This item is part of a collection: 
                    <a href="<?php echo $adminUrl; ?>/media/edit.php?id=<?php echo $parentMedia['id']; ?>" class="font-semibold underline">
                        <?php echo htmlspecialchars($parentMedia['title']); ?>
                    </a>
                </p>
            </div>
        <?php endif; ?>
        
        <?php if ($isCollection): ?>
            <div class="bg-blue-100 border-l-4 border-blue-500 text-blue-700 p-4 mb-6 <?php echo $darkMode ? 'bg-blue-900 text-blue-200 border-blue-400' : ''; ?>">
                <p>This is a collection with <?php echo count($collectionItems); ?> items.</p>
            </div>
        <?php endif; ?>
        
        <!-- Alert Messages -->
        <?php if ($successMessage): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p><?php echo $successMessage; ?></p>
            </div>
        <?php endif; ?>
        
        <?php if ($errorMessage): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p><?php echo $errorMessage; ?></p>
            </div>
        <?php endif; ?>
        
        <!-- Edit Media Form -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
            <div class="p-6">
                <form id="editMediaForm" action="" method="post" enctype="multipart/form-data" class="space-y-6">
                    <!-- Tabs -->
                    <div class="border-b border-gray-200 <?php echo $darkMode ? 'border-gray-600' : ''; ?>">
                        <ul class="flex flex-wrap -mb-px">
                            <li class="mr-2">
                                <a href="#basic-info" class="tab-link inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:border-gray-300 <?php echo $darkMode ? 'text-gray-300 hover:border-gray-500' : 'text-gray-600 hover:text-gray-800'; ?> active" data-tab="basic-info">
                                    <i class="fas fa-info-circle mr-2"></i> Basic Info
                                </a>
                            </li>
                            <li class="mr-2">
                                <a href="#media-upload" class="tab-link inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:border-gray-300 <?php echo $darkMode ? 'text-gray-300 hover:border-gray-500' : 'text-gray-600 hover:text-gray-800'; ?>" data-tab="media-upload">
                                    <i class="fas fa-upload mr-2"></i> Media Files
                                </a>
                            </li>
                            <li class="mr-2">
                                <a href="#categories-tags" class="tab-link inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:border-gray-300 <?php echo $darkMode ? 'text-gray-300 hover:border-gray-500' : 'text-gray-600 hover:text-gray-800'; ?>" data-tab="categories-tags">
                                    <i class="fas fa-tags mr-2"></i> Categories & Tags
                                </a>
                            </li>
                            <li class="mr-2">
                                <a href="#advanced" class="tab-link inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:border-gray-300 <?php echo $darkMode ? 'text-gray-300 hover:border-gray-500' : 'text-gray-600 hover:text-gray-800'; ?>" data-tab="advanced">
                                    <i class="fas fa-cogs mr-2"></i> Advanced
                                </a>
                            </li>
                            <?php if ($isCollection): ?>
                            <li class="mr-2">
                                <a href="#collection" class="tab-link inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:border-gray-300 <?php echo $darkMode ? 'text-gray-300 hover:border-gray-500' : 'text-gray-600 hover:text-gray-800'; ?>" data-tab="collection">
                                    <i class="fas fa-layer-group mr-2"></i> Collection Items
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>
                    
                    <!-- Tab Content -->
                    <div class="tab-content">
                        <!-- Basic Info Tab -->
                        <div id="basic-info" class="tab-pane active">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Title -->
                                <div class="col-span-2">
                                    <label for="title" class="block font-medium mb-1 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">Title <span class="text-red-500">*</span></label>
                                    <input type="text" id="title" name="title" required value="<?php echo htmlspecialchars($media['title']); ?>" 
                                        class="w-full p-2 border rounded-md <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-gray-200' : 'bg-white border-gray-300'; ?>"
                                        placeholder="Enter media title">
                                </div>
                                
                                <!-- Description -->
                                <div class="col-span-2">
                                    <label for="description" class="block font-medium mb-1 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">Description</label>
                                    <textarea id="description" name="description" rows="4" 
                                        class="w-full p-2 border rounded-md <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-gray-200' : 'bg-white border-gray-300'; ?>"
                                        placeholder="Enter media description"><?php echo htmlspecialchars($media['description']); ?></textarea>
                                </div>
                                
                                <!-- Status & Featured -->
                                <div>
                                    <label class="block font-medium mb-2 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">Status & Visibility</label>
                                    <div class="space-y-2">
                                        <div class="flex items-center">
                                            <input type="checkbox" id="status" name="status" class="h-5 w-5 text-blue-600" <?php echo $media['status'] ? 'checked' : ''; ?>>
                                            <label for="status" class="ml-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                                Active (visible to users)
                                            </label>
                                        </div>
                                        <div class="flex items-center">
                                            <input type="checkbox" id="featured" name="featured" class="h-5 w-5 text-blue-600" <?php echo $media['featured'] ? 'checked' : ''; ?>>
                                            <label for="featured" class="ml-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                                Featured (shown in featured sections)
                                            </label>
                                        </div>
                                        <div class="flex items-center">
                                            <input type="checkbox" id="paid_content" name="paid_content" class="h-5 w-5 text-blue-600" <?php echo $media['paid_content'] ? 'checked' : ''; ?>>
                                            <label for="paid_content" class="ml-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                                Premium Content (for paid users only)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Publishing Date -->
                                <div>
                                    <label for="publish_date" class="block font-medium mb-1 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">Publish Date</label>
                                    <input type="date" id="publish_date" name="publish_date" value="<?php echo date('Y-m-d', strtotime($media['publish_date'])); ?>" 
                                        class="w-full p-2 border rounded-md <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-gray-200' : 'bg-white border-gray-300'; ?>">
                                </div>
                                
                                <!-- Basic File Information -->
                                <div class="col-span-2">
                                    <div class="bg-gray-50 p-4 rounded-md <?php echo $darkMode ? 'bg-gray-800' : ''; ?>">
                                        <h3 class="text-lg font-semibold mb-2 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-800'; ?>">File Information</h3>
                                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                            <div>
                                                <p class="<?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">File Name:</p>
                                                <p class="<?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>"><?php echo htmlspecialchars($media['file_name']); ?></p>
                                            </div>
                                            <div>
                                                <p class="<?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">File Type:</p>
                                                <p class="<?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>"><?php echo htmlspecialchars($media['file_type']); ?></p>
                                            </div>
                                            <div>
                                                <p class="<?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">File Size:</p>
                                                <p class="<?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>"><?php echo formatFileSize($media['file_size']); ?></p>
                                            </div>
                                            <?php if ($media['width'] && $media['height']): ?>
                                                <div>
                                                    <p class="<?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">Dimensions:</p>
                                                    <p class="<?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>"><?php echo $media['width']; ?> x <?php echo $media['height']; ?> pixels</p>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <p class="<?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">Created:</p>
                                                                                                <p class="<?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>"><?php echo date('Y-m-d H:i:s', strtotime($media['created_at'])); ?></p>
                                            </div>
                                            <div>
                                                <p class="<?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">Last Updated:</p>
                                                <p class="<?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>"><?php echo date('Y-m-d H:i:s', strtotime($media['updated_at'] ?? $media['created_at'])); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Media Files Tab -->
                        <div id="media-upload" class="tab-pane hidden">
                            <!-- Current Media Preview -->
                            <div class="mb-6">
                                <h3 class="font-semibold mb-3 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">Current Media</h3>
                                
                                <div class="bg-gray-50 p-4 rounded-md <?php echo $darkMode ? 'bg-gray-800' : ''; ?>">
                                    <div class="flex flex-col md:flex-row">
                                        <div class="md:w-1/3 flex justify-center mb-4 md:mb-0">
                                            <?php if (strpos($media['file_type'], 'image/') === 0): ?>
                                                <div class="h-64 flex items-center">
                                                    <img src="<?php echo htmlspecialchars($media['file_path']); ?>" alt="<?php echo htmlspecialchars($media['title']); ?>" class="max-h-full max-w-full">
                                                </div>
                                            <?php elseif (strpos($media['file_type'], 'video/') === 0): ?>
                                                <div class="h-64 flex items-center">
                                                    <video controls class="max-h-full max-w-full">
                                                        <source src="<?php echo htmlspecialchars($media['file_path']); ?>" type="<?php echo htmlspecialchars($media['file_type']); ?>">
                                                        Your browser does not support the video tag.
                                                    </video>
                                                </div>
                                            <?php elseif (strpos($media['file_type'], 'audio/') === 0): ?>
                                                <div class="h-64 flex items-center justify-center">
                                                    <audio controls>
                                                        <source src="<?php echo htmlspecialchars($media['file_path']); ?>" type="<?php echo htmlspecialchars($media['file_type']); ?>">
                                                        Your browser does not support the audio tag.
                                                    </audio>
                                                </div>
                                            <?php else: ?>
                                                <div class="h-64 flex flex-col items-center justify-center">
                                                    <i class="fas fa-file text-5xl mb-3 <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>"></i>
                                                    <p class="<?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>"><?php echo htmlspecialchars($media['file_name']); ?></p>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="md:w-2/3 md:pl-6">
                                            <?php if ($mediaColors): ?>
                                            <div class="mb-4">
                                                <h4 class="text-sm font-semibold mb-1 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-600'; ?>">Dominant Colors</h4>
                                                <div class="flex space-x-2">
                                                    <div class="w-8 h-8 rounded border" style="background-color: <?php echo $mediaColors['primary_color']; ?>"></div>
                                                    <div class="w-8 h-8 rounded border" style="background-color: <?php echo $mediaColors['secondary_color']; ?>"></div>
                                                </div>
                                                <p class="text-xs mt-1 <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                                    Primary: <?php echo $mediaColors['primary_color']; ?>, 
                                                    Secondary: <?php echo $mediaColors['secondary_color']; ?>
                                                </p>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <div class="mb-4">
                                                <h4 class="text-sm font-semibold mb-1 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-600'; ?>">File Details</h4>
                                                <p class="<?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                                    <?php if ($media['width'] && $media['height']): ?>
                                                        <span class="inline-block mr-3">
                                                            <i class="fas fa-ruler-combined mr-1"></i> 
                                                            <?php echo $media['width']; ?> x <?php echo $media['height']; ?> px
                                                        </span>
                                                    <?php endif; ?>
                                                    <span class="inline-block mr-3">
                                                        <i class="fas fa-weight mr-1"></i> 
                                                        <?php echo formatFileSize($media['file_size']); ?>
                                                    </span>
                                                    <span class="inline-block">
                                                        <i class="fas fa-calendar mr-1"></i> 
                                                        <?php echo date('Y-m-d', strtotime($media['created_at'])); ?>
                                                    </span>
                                                </p>
                                            </div>
                                            
                                            <?php if (!empty($media['owner'])): ?>
                                            <div class="mb-4">
                                                <h4 class="text-sm font-semibold mb-1 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-600'; ?>">Owner/Creator</h4>
                                                <p class="<?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>"><?php echo htmlspecialchars($media['owner']); ?></p>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <?php if (!empty($media['license'])): ?>
                                            <div class="mb-4">
                                                <h4 class="text-sm font-semibold mb-1 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-600'; ?>">License</h4>
                                                <p class="<?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>"><?php echo htmlspecialchars($media['license']); ?></p>
                                            </div>
                                            <?php endif; ?>
                                            
                                            <div class="mt-4 flex space-x-2">
                                                <a href="<?php echo $media['file_path']; ?>" target="_blank" class="px-3 py-1 bg-blue-600 text-white rounded-md text-sm hover:bg-blue-700">
                                                    <i class="fas fa-external-link-alt mr-1"></i> View Full Size
                                                </a>
                                                <a href="<?php echo $media['file_path']; ?>" download class="px-3 py-1 bg-green-600 text-white rounded-md text-sm hover:bg-green-700">
                                                    <i class="fas fa-download mr-1"></i> Download File
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Upload Type Selection -->
                            <div class="mb-6">
                                <label class="block font-medium mb-2 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">File Update Options</label>
                                <div class="flex space-x-4">
                                    <div class="flex items-center">
                                        <input type="radio" id="upload_keep" name="upload_type" value="keep" class="h-4 w-4 text-blue-600" checked onchange="toggleUploadMethod()">
                                        <label for="upload_keep" class="ml-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                            Keep Current File
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="radio" id="upload_replace" name="upload_type" value="replace" class="h-4 w-4 text-blue-600" onchange="toggleUploadMethod()">
                                        <label for="upload_replace" class="ml-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                            Replace File
                                        </label>
                                    </div>
                                    <?php if (!$isCollectionItem): ?>
                                    <div class="flex items-center">
                                        <input type="radio" id="upload_collection" name="upload_type" value="collection" class="h-4 w-4 text-blue-600" onchange="toggleUploadMethod()">
                                        <label for="upload_collection" class="ml-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                            <?php echo $isCollection ? 'Add to Collection' : 'Convert to Collection'; ?>
                                        </label>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Resolution Selection -->
                            <div class="mb-6">
                                <label for="resolution_id" class="block font-medium mb-1 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">
                                    Resolution
                                </label>
                                <select id="resolution_id" name="resolution_id" 
                                    class="w-full p-2 border rounded-md <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-gray-200' : 'bg-white border-gray-300'; ?>">
                                    <option value="">-- Select Resolution --</option>
                                    <?php foreach ($resolutions as $resolution): ?>
                                        <option value="<?php echo $resolution['id']; ?>" <?php echo ($media['resolution_id'] == $resolution['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($resolution['resolution']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="mt-1 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    Select the resolution for this media item
                                </p>
                            </div>
                            
                            <!-- File Upload Section -->
                            <div id="file_replace_section" class="hidden">
                                <div class="border-2 border-dashed rounded-md p-6 text-center <?php echo $darkMode ? 'border-gray-600' : 'border-gray-300'; ?> mb-4">
                                    <div id="upload-preview" class="mb-4 hidden">
                                        <img id="preview-image" src="#" alt="Preview" class="max-h-64 mx-auto mb-2">
                                        <p id="file-name" class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>"></p>
                                    </div>
                                    
                                    <div id="upload-prompt">
                                        <i class="fas fa-cloud-upload-alt text-5xl mb-2 <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>"></i>
                                        <p class="mb-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">Drag and drop your file here, or click to browse</p>
                                        <p class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                            Supports images (JPG, PNG, GIF, WEBP, SVG), videos (MP4, WebM), audio files, and more
                                        </p>
                                    </div>
                                    
                                    <input type="file" id="media_file" name="media_file" 
                                        class="hidden" 
                                        accept="image/*,video/*,audio/*,.pdf,.zip,.rar">
                                    
                                    <button type="button" id="browse_button" 
                                        class="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                                        Browse Files
                                    </button>
                                </div>
                                
                                <!-- Watermark Option -->
                                <div>
                                    <?php if ($watermarkEnabled): ?>
                                        <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4 mb-4 <?php echo $darkMode ? 'bg-blue-900 text-blue-200' : ''; ?>">
                                            <div class="flex items-center">
                                                <input type="checkbox" id="apply_watermark" name="apply_watermark" class="h-5 w-5 text-blue-600" checked>
                                                <label for="apply_watermark" class="ml-2 <?php echo $darkMode ? 'text-blue-200' : 'text-blue-700'; ?>">
                                                    Apply watermark to new file
                                                </label>
                                            </div>
                                            <p class="mt-2 text-sm">
                                                <a href="<?php echo $adminUrl; ?>/settings/watermark.php" class="underline">Configure watermark settings</a>
                                            </p>
                                        </div>
                                    <?php else: ?>
                                        <div class="bg-gray-50 border-l-4 border-gray-500 text-gray-700 p-4 <?php echo $darkMode ? 'bg-gray-800 text-gray-300 border-gray-600' : ''; ?>">
                                            <p>Watermarking is currently disabled.</p>
                                            <p class="mt-2 text-sm">
                                                <a href="<?php echo $adminUrl; ?>/settings/watermark.php" class="underline">Enable and configure watermark</a>
                                            </p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <!-- Collection Upload Section -->
                            <div id="collection_section" class="hidden">
                                <div class="border-2 border-dashed rounded-md p-6 text-center <?php echo $darkMode ? 'border-gray-600' : 'border-gray-300'; ?> mb-4">
                                    <div id="collection-preview" class="mb-4 hidden">
                                        <div id="collection-thumbnails" class="flex flex-wrap gap-2 justify-center mb-2">
                                            <!-- Thumbnails will be inserted here by JavaScript -->
                                        </div>
                                        <p id="collection-count" class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">0 files selected</p>
                                    </div>
                                    
                                    <div id="collection-prompt">
                                        <i class="fas fa-images text-5xl mb-2 <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>"></i>
                                        <p class="mb-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">Drag and drop multiple files here, or click to browse</p>
                                        <p class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                            <?php echo $isCollection ? 'Add more items to this collection' : 'Create a collection with this media and additional files'; ?>
                                        </p>
                                    </div>
                                    
                                    <input type="file" id="collection_files" name="collection_files[]" 
                                        class="hidden" 
                                        accept="image/*,video/*,audio/*,.pdf,.zip,.rar" multiple>
                                    
                                    <button type="button" id="browse_collection_button" 
                                        class="mt-4 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                                        Browse Files
                                    </button>
                                </div>
                                
                                <!-- Collection Details -->
                                <div id="collection_details" class="hidden">
                                    <div class="bg-gray-50 border rounded-md p-4 <?php echo $darkMode ? 'bg-gray-800 border-gray-600' : 'border-gray-300'; ?>">
                                        <h3 class="font-semibold mb-2 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-800'; ?>">New Collection Items</h3>
                                        <p class="mb-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                            <span id="collection-file-count">0</span> files selected for this collection
                                        </p>
                                        <ul id="collection-file-list" class="list-disc list-inside text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                                            <!-- File list will be inserted here by JavaScript -->
                                        </ul>
                                        
                                        <?php if ($watermarkEnabled): ?>
                                            <div class="mt-4 flex items-center">
                                                <input type="checkbox" id="apply_watermark_collection" name="apply_watermark" class="h-5 w-5 text-blue-600" checked>
                                                <label for="apply_watermark_collection" class="ml-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                                    Apply watermark to all collection items
                                                </label>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Categories & Tags Tab -->
                        <div id="categories-tags" class="tab-pane hidden">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Primary Category -->
                                <div>
                                    <label for="category_id" class="block font-medium mb-1 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">
                                        Primary Category
                                    </label>
                                    <select id="category_id" name="category_id" 
                                        class="w-full p-2 border rounded-md <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-gray-200' : 'bg-white border-gray-300'; ?>">
                                        <option value="">-- Select Category --</option>
                                        <?php echo buildCategoryOptions($categories, $media['category_id']); ?>
                                    </select>
                                </div>
                                
                                <!-- Additional Categories -->
                                <div>
                                    <label class="block font-medium mb-1 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">
                                        Additional Categories
                                    </label>
                                    <div class="border p-2 rounded-md max-h-60 overflow-y-auto <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : 'bg-white border-gray-300'; ?>">
                                        <?php foreach ($categories as $category): ?>
                                            <div class="flex items-center py-1">
                                                <input type="checkbox" id="category_<?php echo $category['id']; ?>" name="additional_categories[]" value="<?php echo $category['id']; ?>" 
                                                    class="h-4 w-4 text-blue-600" <?php echo in_array($category['id'], $additionalCategoryIds) ? 'checked' : ''; ?>>
                                                <label for="category_<?php echo $category['id']; ?>" class="ml-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </label>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <!-- Tags - Enhanced with Autocomplete and Add-on-fly capability -->
                                <div class="col-span-2">
                                    <label for="tag_input" class="block font-medium mb-1 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">
                                        Tags
                                    </label>
                                    <div class="tag-input-container relative">
                                        <div id="selected-tags" class="flex flex-wrap gap-2 mb-2">
                                            <!-- Selected tags will appear here -->
                                        </div>
                                        <div class="flex">
                                            <input type="text" id="tag_input_field" 
                                                class="w-full p-2 border rounded-md <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-gray-200' : 'bg-white border-gray-300'; ?>"
                                                placeholder="Type to add tags (comma or enter to separate)">
                                            <button type="button" id="add_tag_btn" 
                                                class="ml-2 px-3 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition-colors">
                                                Add
                                            </button>
                                        </div>
                                        <input type="hidden" id="tag_input" name="tag_input" value="<?php echo implode(',', array_column($mediaTags, 'name')); ?>">
                                        <div id="tag-suggestions" class="absolute z-10 w-full mt-1 bg-white border rounded-md shadow-lg max-h-60 overflow-y-auto hidden <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : ''; ?>">
                                            <!-- Tag suggestions will appear here -->
                                        </div>
                                    </div>
                                    <p class="mt-1 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                        Enter tags separated by commas or press Enter after each tag. You can also create new tags on the fly.
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Advanced Tab -->
                        <div id="advanced" class="tab-pane hidden">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Orientation -->
                                <div>
                                    <label class="block font-medium mb-1 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">Orientation</label>
                                    <div class="flex space-x-4">
                                        <div class="flex items-center">
                                            <input type="radio" id="orientation_portrait" name="orientation" value="portrait" class="h-4 w-4 text-blue-600" <?php echo $media['orientation'] === 'portrait' ? 'checked' : ''; ?>>
                                            <label for="orientation_portrait" class="ml-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                                Portrait
                                            </label>
                                        </div>
                                        <div class="flex items-center">
                                            <input type="radio" id="orientation_landscape" name="orientation" value="landscape" class="h-4 w-4 text-blue-600" <?php echo $media['orientation'] === 'landscape' ? 'checked' : ''; ?>>
                                            <label for="orientation_landscape" class="ml-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                                Landscape
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Background Color -->
                                <div>
                                    <label for="background_color" class="block font-medium mb-1 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">
                                        Background Color
                                    </label>
                                    <div class="flex">
                                        <input type="color" id="background_color" name="background_color" value="<?php echo htmlspecialchars($media['background_color']); ?>" 
                                            class="h-10 w-20 p-1 border rounded-md">
                                        <select id="predefined_colors" 
                                            class="ml-2 w-full p-2 border rounded-md <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-gray-200' : 'bg-white border-gray-300'; ?>">
                                            <option value="">-- Predefined Colors --</option>
                                            <?php foreach ($colors as $color): ?>
                                                <option value="<?php echo $color['hex_code']; ?>" data-color="<?php echo $color['hex_code']; ?>">
                                                    <?php echo htmlspecialchars($color['color_name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                
                                <!-- Owner & License -->
                                <div>
                                    <label for="owner" class="block font-medium mb-1 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">Owner/Creator</label>
                                    <input type="text" id="owner" name="owner" value="<?php echo htmlspecialchars($media['owner']); ?>" 
                                        class="w-full p-2 border rounded-md <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-gray-200' : 'bg-white border-gray-300'; ?>"
                                        placeholder="Original creator or copyright owner">
                                </div>
                                
                                <div>
                                    <label for="license" class="block font-medium mb-1 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">License</label>
                                    <input type="text" id="license" name="license" value="<?php echo htmlspecialchars($media['license']); ?>" 
                                        class="w-full p-2 border rounded-md <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-gray-200' : 'bg-white border-gray-300'; ?>"
                                        placeholder="e.g., Creative Commons, Royalty Free">
                                </div>
                                
                                <!-- AI Enhanced -->
                                <div class="col-span-2">
                                    <div class="flex items-center">
                                        <input type="checkbox" id="ai_enhanced" name="ai_enhanced" class="h-5 w-5 text-blue-600" <?php echo $media['ai_enhanced'] ? 'checked' : ''; ?>>
                                        <label for="ai_enhanced" class="ml-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                            AI Enhanced Content
                                        </label>
                                    </div>
                                    <p class="mt-1 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                        Mark if this content has been created or enhanced using AI tools
                                    </p>
                                </div>
                            </div>
                        </div>
                        
                        <?php if ($isCollection): ?>
                        <!-- Collection Items Tab -->
                        <div id="collection" class="tab-pane hidden">
                            <div class="mb-4">
                                <h3 class="text-lg font-semibold mb-2 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-800'; ?>">Collection Items</h3>
                                <p class="text-sm mb-4 <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    This collection contains <?php echo count($collectionItems); ?> items. You can edit or remove them below.
                                </p>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                    <?php foreach ($collectionItems as $item): ?>
                                        <div class="border rounded-md overflow-hidden bg-white <?php echo $darkMode ? 'border-gray-700 bg-gray-800' : 'border-gray-200'; ?>">
                                            <div class="h-48 bg-gray-100 flex items-center justify-center overflow-hidden <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                                                <?php if (strpos($item['file_type'], 'image/') === 0): ?>
                                                    <img src="<?php echo $item['thumbnail_url'] ?? $item['file_path']; ?>" alt="<?php echo htmlspecialchars($item['title']); ?>" class="w-full h-full object-contain">
                                                <?php elseif (strpos($item['file_type'], 'video/') === 0): ?>
                                                    <div class="text-center">
                                                        <i class="fas fa-video text-3xl mb-2 <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>"></i>
                                                        <p class="text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-600'; ?>">Video File</p>
                                                    </div>
                                                <?php elseif (strpos($item['file_type'], 'audio/') === 0): ?>
                                                    <div class="text-center">
                                                        <i class="fas fa-music text-3xl mb-2 <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>"></i>
                                                        <p class="text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-600'; ?>">Audio File</p>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="text-center">
                                                        <i class="fas fa-file text-3xl mb-2 <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>"></i>
                                                        <p class="text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-600'; ?>"><?php echo htmlspecialchars(pathinfo($item['file_name'], PATHINFO_EXTENSION)); ?> File</p>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <div class="p-3">
                                                <h4 class="font-medium mb-1 truncate <?php echo $darkMode ? 'text-gray-200' : 'text-gray-800'; ?>" title="<?php echo htmlspecialchars($item['title']); ?>">
                                                    <?php echo htmlspecialchars($item['title']); ?>
                                                </h4>
                                                
                                                <div class="flex items-center text-xs <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?> mb-2">
                                                    <span class="mr-2"><?php echo formatFileSize($item['file_size']); ?></span>
                                                    <?php if ($item['width'] && $item['height']): ?>
                                                        <span><?php echo $item['width']; ?>x<?php echo $item['height']; ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                
                                                <div class="flex space-x-2 mt-2">
                                                    <a href="<?php echo $adminUrl; ?>/media/edit.php?id=<?php echo $item['id']; ?>" class="px-2 py-1 text-xs bg-blue-600 text-white rounded hover:bg-blue-700">
                                                        <i class="fas fa-edit mr-1"></i> Edit
                                                    </a>
                                                    <a href="<?php echo $adminUrl; ?>/media/view.php?id=<?php echo $item['id']; ?>" class="px-2 py-1 text-xs bg-gray-500 text-white rounded hover:bg-gray-600">
                                                        <i class="fas fa-eye mr-1"></i> View
                                                    </a>
                                                    <button type="button" class="px-2 py-1 text-xs bg-red-600 text-white rounded hover:bg-red-700" 
                                                        onclick="confirmRemoveItem(<?php echo $item['id']; ?>, '<?php echo addslashes(htmlspecialchars($item['title'])); ?>')">
                                                        <i class="fas fa-trash mr-1"></i> Remove
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Live Preview Section -->
                    <div id="live-preview-section" class="mt-8 border-t pt-6 <?php echo $darkMode ? 'border-gray-600' : 'border-gray-200'; ?>">
                        <h3 class="text-lg font-semibold mb-4 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-800'; ?>">
                            <i class="fas fa-eye mr-2"></i> Live Preview
                        </h3>
                        
                        <div class="bg-white rounded-md shadow-md overflow-hidden <?php echo $darkMode ? 'bg-gray-800' : ''; ?>">
                            <div class="p-4">
                                <h4 id="preview-title" class="text-xl font-bold mb-2 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-800'; ?>"><?php echo htmlspecialchars($media['title']); ?></h4>
                                <p id="preview-description" class="text-sm mb-4 <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>"><?php echo htmlspecialchars($media['description']); ?></p>
                                
                                <div class="flex justify-center mb-4">
                                    <div id="preview-media" class="rounded-md overflow-hidden flex items-center justify-center" style="height: 300px; background-color: <?php echo $media['background_color'] ?? '#f0f0f0'; ?>">
                                        <?php if (strpos($media['file_type'], 'image/') === 0): ?>
                                            <img src="<?php echo $media['file_path']; ?>" alt="<?php echo htmlspecialchars($media['title']); ?>" class="max-h-full max-w-full">
                                        <?php elseif (strpos($media['file_type'], 'video/') === 0): ?>
                                            <div class="flex flex-col items-center justify-center">
                                                <i class="fas fa-video text-5xl mb-2 <?php echo $darkMode ? 'text-gray-500' : 'text-gray-400'; ?>"></i>
                                                <span class="<?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>"><?php echo htmlspecialchars($media['file_name']); ?></span>
                                            </div>
                                        <?php else: ?>
                                            <div class="flex flex-col items-center justify-center">
                                                <i class="fas fa-file text-5xl mb-2 <?php echo $darkMode ? 'text-gray-500' : 'text-gray-400'; ?>"></i>
                                                <span class="<?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>"><?php echo htmlspecialchars($media['file_name']); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="flex flex-wrap gap-2 mb-4">
                                    <?php if ($media['category_id']): 
                                        // Get category name
                                        foreach ($categories as $cat) {
                                            if ($cat['id'] == $media['category_id']) {
                                                $categoryName = $cat['name'];
                                                break;
                                            }
                                        }
                                    ?>
                                        <span id="preview-category" class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 <?php echo $darkMode ? 'bg-blue-900 text-blue-200' : ''; ?>">
                                            <?php echo htmlspecialchars($categoryName ?? 'Category'); ?>
                                        </span>
                                    <?php endif; ?>
                                    
                                    <div id="preview-tags" class="flex flex-wrap gap-1">
                                        <?php foreach ($mediaTags as $tag): ?>
                                            <span class="px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 <?php echo $darkMode ? 'bg-green-900 text-green-200' : ''; ?>">
                                                <?php echo htmlspecialchars($tag['name']); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                
                                <div class="flex justify-between text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    <span id="preview-owner">Owner: <?php echo htmlspecialchars($media['owner'] ?: $currentUser); ?></span>
                                    <span id="preview-date">Date: <?php echo date('Y-m-d', strtotime($media['publish_date'])); ?></span>
                                </div>
                                
                                <div class="mt-2 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    <span id="preview-resolution">
                                        Resolution: 
                                        <?php 
                                        if ($media['resolution_id']) {
                                            foreach ($resolutions as $res) {
                                                if ($res['id'] == $media['resolution_id']) {
                                                    echo htmlspecialchars($res['resolution']);
                                                    break;
                                                }
                                            }
                                        } else {
                                            echo 'Not selected';
                                        }
                                        ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Form Buttons -->
                    <div class="flex justify-between pt-6 border-t <?php echo $darkMode ? 'border-gray-600' : 'border-gray-200'; ?>">
                        <div>
                            <a href="<?php echo $adminUrl; ?>/media/index.php" class="px-6 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition-colors">
                                <i class="fas fa-arrow-left mr-2"></i> Cancel
                            </a>
                        </div>
                        
                        <div>
                            <button type="button" id="preview_button" class="px-6 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors mr-2">
                                <i class="fas fa-eye mr-2"></i> Preview
                            </button>
                            
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                <i class="fas fa-save mr-2"></i> Update Media
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal for removing collection item -->
<div id="removeItemModal" class="fixed z-50 inset-0 overflow-y-auto hidden" aria-modal="true">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <!-- Background overlay -->
        <div id="removeItemModalBg" class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"></div>
        
        <!-- Modal panel -->
        <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full <?php echo $darkMode ? 'bg-gray-800 text-gray-200' : ''; ?>">
            <div class="p-6">
                <h3 class="text-lg font-medium leading-6 mb-4 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-900'; ?>">Confirm Removal</h3>
                <p class="mb-4 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                    Are you sure you want to remove <span id="removeItemName" class="font-semibold"></span> from this collection?
                </p>
                <input type="hidden" id="removeItemId" value="">
                
                <div class="flex justify-end space-x-2">
                    <button type="button" id="cancelRemoveBtn" class="px-4 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600">
                        Cancel
                    </button>
                    <button type="button" id="confirmRemoveBtn" class="px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700">
                        Remove
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Helper function to format file size (used in the file) -->
<script>
function formatFileSize(bytes) {
    if (bytes === 0 || bytes === null || bytes === undefined) return '0 Bytes';
    
    const k = 1024;
    const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Function to remove collection item
function confirmRemoveItem(itemId, itemTitle) {
    document.getElementById('removeItemId').value = itemId;
    document.getElementById('removeItemName').textContent = itemTitle;
    document.getElementById('removeItemModal').classList.remove('hidden');
}
</script>

<!-- JavaScript for Edit Media Page -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab Navigation
    const tabLinks = document.querySelectorAll('.tab-link');
    const tabPanes = document.querySelectorAll('.tab-pane');
    
    tabLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all tabs and panes
            tabLinks.forEach(l => l.classList.remove('active', 'border-blue-500', 'text-blue-600'));
            tabPanes.forEach(p => p.classList.add('hidden'));
            
            // Add active class to current tab
            this.classList.add('active', 'border-blue-500', 'text-blue-600');
            
            // Show current tab pane
            const tabId = this.getAttribute('data-tab');
            document.getElementById(tabId).classList.remove('hidden');
        });
    });
    
    // Toggle between file upload options
    window.toggleUploadMethod = function() {
        const uploadType = document.querySelector('input[name="upload_type"]:checked').value;
        const fileReplaceSection = document.getElementById('file_replace_section');
        const collectionSection = document.getElementById('collection_section');
        
        if (uploadType === 'replace') {
            fileReplaceSection.classList.remove('hidden');
            collectionSection.classList.add('hidden');
        } else if (uploadType === 'collection') {
            fileReplaceSection.classList.add('hidden');
            collectionSection.classList.remove('hidden');
        } else { // keep
            fileReplaceSection.classList.add('hidden');
            collectionSection.classList.add('hidden');
        }
    };
    
    // Initialize upload method toggle
    toggleUploadMethod();
    
    // File Upload Functionality
    const mediaFileInput = document.getElementById('media_file');
    const browseButton = document.getElementById('browse_button');
    const uploadPreview = document.getElementById('upload-preview');
    const uploadPrompt = document.getElementById('upload-prompt');
    const previewImage = document.getElementById('preview-image');
    const fileName = document.getElementById('file-name');
    
    // Browse button click handler
    browseButton.addEventListener('click', function() {
        mediaFileInput.click();
    });
    
    // File input change handler
    mediaFileInput.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            const file = this.files[0];
            
            // Update file name display
            fileName.textContent = file.name;
            
            // Show file preview for images
            if (file.type.match('image.*')) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    previewImage.src = e.target.result;
                    uploadPreview.classList.remove('hidden');
                    uploadPrompt.classList.add('hidden');
                    
                    // Auto-select orientation based on dimensions
                    const img = new Image();
                    img.onload = function() {
                        if (this.width > this.height) {
                            document.getElementById('orientation_landscape').checked = true;
                        } else {
                            document.getElementById('orientation_portrait').checked = true;
                        }
                        
                        // Update live preview
                        updateLivePreview();
                    };
                    img.src = e.target.result;
                };
                
                reader.readAsDataURL(file);
            } else if (file.type.match('video.*')) {
                // For video files, show a video player or thumbnail
                previewImage.src = '/assets/images/file-thumbnails/video-icon.png'; // Default video icon
                uploadPreview.classList.remove('hidden');
                uploadPrompt.classList.add('hidden');
                updateLivePreview();
            } else {
                // For non-image/video files, show generic icon
                previewImage.src = getFileTypeIcon(file.type);
                uploadPreview.classList.remove('hidden');
                uploadPrompt.classList.add('hidden');
                updateLivePreview();
            }
        }
    });
    
    // Collection Upload Functionality
    const collectionFilesInput = document.getElementById('collection_files');
    const browseCollectionButton = document.getElementById('browse_collection_button');
    const collectionPreview = document.getElementById('collection-preview');
    const collectionPrompt = document.getElementById('collection-prompt');
    const collectionThumbnails = document.getElementById('collection-thumbnails');
    const collectionCount = document.getElementById('collection-count');
    const collectionDetails = document.getElementById('collection_details');
    const collectionFileCount = document.getElementById('collection-file-count');
    const collectionFileList = document.getElementById('collection-file-list');
    
    // Browse collection button click handler
    browseCollectionButton.addEventListener('click', function() {
        collectionFilesInput.click();
    });
    
    // Collection files input change handler
    collectionFilesInput.addEventListener('change', function() {
        if (this.files && this.files.length > 0) {
            const files = this.files;
            
            // Clear previous thumbnails and file list
            collectionThumbnails.innerHTML = '';
            collectionFileList.innerHTML = '';
            
            // Update file count
            collectionCount.textContent = `${files.length} files selected`;
            collectionFileCount.textContent = files.length;
            
            // Process each file
            for (let i = 0; i < files.length; i++) {
                const file = files[i];
                
                // Create thumbnail if it's an image
                if (file.type.match('image.*')) {
                    const reader = new FileReader();
                    
                    reader.onload = function(e) {
                        const thumbnail = document.createElement('div');
                        thumbnail.classList.add('w-16', 'h-16', 'overflow-hidden', 'rounded');
                        
                        const img = document.createElement('img');
                        img.src = e.target.result;
                        img.classList.add('w-full', 'h-full', 'object-cover');
                        
                        thumbnail.appendChild(img);
                        collectionThumbnails.appendChild(thumbnail);
                    };
                    
                    reader.readAsDataURL(file);
                } else {
                    // For non-image files, show type icon
                    const thumbnail = document.createElement('div');
                    thumbnail.classList.add('w-16', 'h-16', 'overflow-hidden', 'rounded', 'flex', 'items-center', 'justify-center', 'bg-gray-200');
                    
                    const icon = document.createElement('i');
                    if (file.type.match('video.*')) {
                        icon.classList.add('fas', 'fa-video');
                    } else if (file.type.match('audio.*')) {
                        icon.classList.add('fas', 'fa-music');
                    } else {
                        icon.classList.add('fas', 'fa-file');
                    }
                    
                    thumbnail.appendChild(icon);
                    collectionThumbnails.appendChild(thumbnail);
                }
                
                // Add to file list
                const listItem = document.createElement('li');
                listItem.textContent = `${file.name} (${formatFileSize(file.size)})`;
                collectionFileList.appendChild(listItem);
            }
            
            // Show collection preview and details
            collectionPreview.classList.remove('hidden');
            collectionPrompt.classList.add('hidden');
            collectionDetails.classList.remove('hidden');
            
            // Update live preview
            updateLivePreview();
        }
    });
    
    // Drag and drop functionality for single file
    const uploadArea = document.querySelector('#file_replace_section .border-dashed');
    
    if (uploadArea) {
        uploadArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('border-blue-500');
        });
        
        uploadArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('border-blue-500');
        });
        
        uploadArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('border-blue-500');
            
            if (e.dataTransfer.files && e.dataTransfer.files[0]) {
                mediaFileInput.files = e.dataTransfer.files;
                
                // Trigger change event
                const event = new Event('change');
                mediaFileInput.dispatchEvent(event);
            }
        });
    }
    
    // Drag and drop functionality for collection
    const collectionArea = document.querySelector('#collection_section .border-dashed');
    
    if (collectionArea) {
        collectionArea.addEventListener('dragover', function(e) {
            e.preventDefault();
            this.classList.add('border-blue-500');
        });
        
        collectionArea.addEventListener('dragleave', function(e) {
            e.preventDefault();
            this.classList.remove('border-blue-500');
        });
        
        collectionArea.addEventListener('drop', function(e) {
            e.preventDefault();
            this.classList.remove('border-blue-500');
            
            if (e.dataTransfer.files && e.dataTransfer.files.length > 0) {
                collectionFilesInput.files = e.dataTransfer.files;
                
                // Trigger change event
                const event = new Event('change');
                collectionFilesInput.dispatchEvent(event);
            }
        });
    }
    
    // Tags Functionality with Auto-complete
    const tagInputField = document.getElementById('tag_input_field');
    const tagInputHidden = document.getElementById('tag_input');
    const selectedTagsContainer = document.getElementById('selected-tags');
    const tagSuggestions = document.getElementById('tag-suggestions');
    const addTagButton = document.getElementById('add_tag_btn');
    
    // All available tags from PHP
    const availableTags = <?php echo json_encode(array_map(function($tag) { return $tag['name']; }, $tags)); ?>;
    
    // Initialize selected tags from hidden input
    let selectedTags = [];
    if (tagInputHidden.value) {
        selectedTags = tagInputHidden.value.split(',');
        selectedTags.forEach(tag => addTagElement(tag));
    }
    
    // Function to add a tag
    function addTag(tagName) {
        tagName = tagName.trim();
        
        if (tagName && !selectedTags.includes(tagName)) {
            // Add to selected tags array
            selectedTags.push(tagName);
            addTagElement(tagName);
            
            // Clear input field
            tagInputField.value = '';
            
            // Update hidden input
            updateHiddenInput();
            
            // Update live preview
            updateLivePreview();
        }
    }
    
    // Function to add tag element to the UI
    function addTagElement(tagName) {
        // Create tag element
        const tagElement = document.createElement('div');
        tagElement.className = 'flex items-center bg-blue-100 text-blue-800 rounded-full px-3 py-1 text-sm <?php echo $darkMode ? "bg-blue-900 text-blue-200" : ""; ?>';
        tagElement.innerHTML = `
            <span>${tagName}</span>
            <button type="button" class="ml-1 text-xs" data-tag="${tagName}">×</button>
        `;
        
        // Add remove button functionality
        tagElement.querySelector('button').addEventListener('click', function() {
            const tagToRemove = this.getAttribute('data-tag');
            selectedTags = selectedTags.filter(tag => tag !== tagToRemove);
            this.parentElement.remove();
            updateHiddenInput();
            updateLivePreview();
        });
        
        // Add to container
        selectedTagsContainer.appendChild(tagElement);
    }
    
    // Function to update hidden input
    function updateHiddenInput() {
        tagInputHidden.value = selectedTags.join(',');
    }
    
    // Function to show tag suggestions
    function showTagSuggestions(query) {
        query = query.toLowerCase();
        
        // Filter tags that start with the query
        const filteredTags = availableTags.filter(tag => 
            tag.toLowerCase().includes(query) && !selectedTags.includes(tag)
        );
        
        // Clear suggestions
        tagSuggestions.innerHTML = '';
        
        if (filteredTags.length > 0 && query.length >= 2) {
            tagSuggestions.classList.remove('hidden');
            
            // Add filtered tags to suggestions
            filteredTags.forEach(tag => {
                const suggestionItem = document.createElement('div');
                suggestionItem.className = 'p-2 cursor-pointer hover:bg-gray-100 <?php echo $darkMode ? "hover:bg-gray-600 text-gray-300" : "text-gray-700"; ?>';
                suggestionItem.textContent = tag;
                
                suggestionItem.addEventListener('click', function() {
                    addTag(tag);
                    tagSuggestions.classList.add('hidden');
                });
                
                tagSuggestions.appendChild(suggestionItem);
            });
        } else {
            tagSuggestions.classList.add('hidden');
        }
    }
    
    // Tag input keyup event for suggestions
    if (tagInputField) {
        tagInputField.addEventListener('input', function() {
            showTagSuggestions(this.value);
        });
        
        // Tag input keydown event for comma and enter
        tagInputField.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.key === ',') {
                e.preventDefault();
                
                // Get tags from input (comma separated)
                const inputTags = this.value.split(',');
                
                // Add each tag
                inputTags.forEach(tag => {
                    if (tag.trim()) {
                        addTag(tag);
                    }
                });
                
                // Hide suggestions
                tagSuggestions.classList.add('hidden');
            }
        });
        
        // Add tag button click
        if (addTagButton) {
            addTagButton.addEventListener('click', function() {
                // Get tags from input (comma separated)
                const inputTags = tagInputField.value.split(',');
                
                // Add each tag
                inputTags.forEach(tag => {
                    if (tag.trim()) {
                        addTag(tag);
                    }
                });
                
                // Hide suggestions
                tagSuggestions.classList.add('hidden');
            });
        }
        
        // Click outside to hide suggestions
        document.addEventListener('click', function(e) {
            if (!tagInputField.contains(e.target) && !tagSuggestions.contains(e.target)) {
                tagSuggestions.classList.add('hidden');
            }
        });
    }
    
    // Predefined colors dropdown
    const backgroundColorInput = document.getElementById('background_color');
    const predefinedColorsSelect = document.getElementById('predefined_colors');
    
    if (predefinedColorsSelect) {
        predefinedColorsSelect.addEventListener('change', function() {
            if (this.value) {
                backgroundColorInput.value = this.value;
                // Update preview background
                updateLivePreview();
            }
        });
    }
    
    // Live preview functionality
    const titleInput = document.getElementById('title');
    const descriptionInput = document.getElementById('description');
    const categorySelect = document.getElementById('category_id');
    const ownerInput = document.getElementById('owner');
    const publishDateInput = document.getElementById('publish_date');
    const resolutionSelect = document.getElementById('resolution_id');
    
    // Preview elements
    const previewTitle = document.getElementById('preview-title');
    const previewDescription = document.getElementById('preview-description');
    const previewMedia = document.getElementById('preview-media');
    const previewCategory = document.getElementById('preview-category');
    const previewTags = document.getElementById('preview-tags');
    const previewOwner = document.getElementById('preview-owner');
    const previewDate = document.getElementById('preview-date');
    const previewResolution = document.getElementById('preview-resolution');
    
    // Function to update live preview
    function updateLivePreview() {
        if (previewTitle) previewTitle.textContent = titleInput.value || 'Media Title';
        if (previewDescription) previewDescription.textContent = descriptionInput.value || 'Media description will appear here.';
        
        // Update media preview for replaced file
        if (document.querySelector('input[name="upload_type"]:checked').value === 'replace') {
            if (mediaFileInput.files && mediaFileInput.files[0]) {
                const file = mediaFileInput.files[0];
                
                if (file.type.match('image.*')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        if (previewMedia) {
                            previewMedia.innerHTML = `<img src="${e.target.result}" alt="Preview" class="max-h-full max-w-full">`;
                        }
                    };
                    reader.readAsDataURL(file);
                } else if (file.type.match('video.*')) {
                    if (previewMedia) {
                        previewMedia.innerHTML = `
                            <div class="flex flex-col items-center justify-center">
                                <i class="fas fa-video text-5xl mb-2 <?php echo $darkMode ? 'text-gray-500' : 'text-gray-400'; ?>"></i>
                                <span class="<?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">${file.name}</span>
                            </div>
                        `;
                    }
                } else {
                    if (previewMedia) {
                                                previewMedia.innerHTML = `
                            <div class="flex flex-col items-center justify-center">
                                <i class="fas fa-file text-5xl mb-2 <?php echo $darkMode ? 'text-gray-500' : 'text-gray-400'; ?>"></i>
                                <span class="<?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">${file.name}</span>
                            </div>
                        `;
                    }
                }
            }
        } else if (document.querySelector('input[name="upload_type"]:checked').value === 'collection') {
            // For collection preview, show a grid of thumbnails
            if (collectionFilesInput.files && collectionFilesInput.files.length > 0 && previewMedia) {
                const files = collectionFilesInput.files;
                
                // Create a grid preview for collection
                let previewHTML = '<div class="grid grid-cols-2 md:grid-cols-3 gap-2">';
                
                // Add up to 6 thumbnails
                const maxPreviews = Math.min(files.length, 6);
                for (let i = 0; i < maxPreviews; i++) {
                    const file = files[i];
                    
                    if (file.type.match('image.*')) {
                        // For images, we need to read the file
                        const reader = new FileReader();
                        reader.onload = (function(idx) {
                            return function(e) {
                                document.getElementById(`collection-preview-${idx}`).innerHTML = `
                                    <img src="${e.target.result}" alt="Preview" class="w-full h-full object-cover">
                                `;
                            };
                        })(i);
                        reader.readAsDataURL(file);
                        
                        previewHTML += `
                            <div id="collection-preview-${i}" class="h-24 bg-gray-100 <?php echo $darkMode ? 'bg-gray-700' : ''; ?> flex items-center justify-center overflow-hidden">
                                <i class="fas fa-spinner fa-spin"></i>
                            </div>
                        `;
                    } else if (file.type.match('video.*')) {
                        previewHTML += `
                            <div class="h-24 bg-gray-100 <?php echo $darkMode ? 'bg-gray-700' : ''; ?> flex items-center justify-center">
                                <i class="fas fa-video"></i>
                            </div>
                        `;
                    } else {
                        previewHTML += `
                            <div class="h-24 bg-gray-100 <?php echo $darkMode ? 'bg-gray-700' : ''; ?> flex items-center justify-center">
                                <i class="fas fa-file"></i>
                            </div>
                        `;
                    }
                }
                
                // If there are more files than we showed
                if (files.length > 6) {
                    previewHTML += `
                        <div class="h-24 bg-gray-100 <?php echo $darkMode ? 'bg-gray-700' : ''; ?> flex items-center justify-center">
                            <span class="<?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">+${files.length - 6} more</span>
                        </div>
                    `;
                }
                
                previewHTML += '</div>';
                previewMedia.innerHTML = previewHTML;
            }
        }
        
        // Update category
        if (categorySelect && previewCategory) {
            if (categorySelect.value) {
                const selectedOption = categorySelect.options[categorySelect.selectedIndex];
                previewCategory.textContent = selectedOption.textContent;
                previewCategory.classList.remove('hidden');
            } else if (previewCategory) {
                previewCategory.classList.add('hidden');
            }
        }
        
        // Update tags
        if (previewTags) {
            previewTags.innerHTML = '';
            selectedTags.forEach(tag => {
                const tagSpan = document.createElement('span');
                tagSpan.className = 'px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 <?php echo $darkMode ? "bg-green-900 text-green-200" : ""; ?>';
                tagSpan.textContent = tag;
                previewTags.appendChild(tagSpan);
            });
        }
        
        // Update owner
        if (previewOwner) {
            previewOwner.textContent = 'Owner: ' + (ownerInput.value || '<?php echo $currentUser; ?>');
        }
        
        // Update date
        if (previewDate && publishDateInput) {
            previewDate.textContent = 'Date: ' + (publishDateInput.value || '<?php echo date('Y-m-d', strtotime($currentDateTime)); ?>');
        }
        
        // Update resolution
        if (previewResolution && resolutionSelect) {
            if (resolutionSelect.value) {
                const selectedResolution = resolutionSelect.options[resolutionSelect.selectedIndex].text;
                previewResolution.textContent = 'Resolution: ' + selectedResolution;
            } else {
                previewResolution.textContent = 'Resolution: Not selected';
            }
        }
        
        // Update background color
        if (previewMedia && backgroundColorInput) {
            previewMedia.style.backgroundColor = backgroundColorInput.value;
        }
    }
    
    // Add event listeners to form elements for live preview
    const formElements = [
        titleInput, descriptionInput, categorySelect, ownerInput, publishDateInput, 
        backgroundColorInput, resolutionSelect
    ];
    
    formElements.forEach(element => {
        if (element) {
            element.addEventListener('input', updateLivePreview);
            element.addEventListener('change', updateLivePreview);
        }
    });
    
    // Preview button functionality
    const previewButton = document.getElementById('preview_button');
    if (previewButton) {
        previewButton.addEventListener('click', function() {
            const previewSection = document.getElementById('live-preview-section');
            if (previewSection) {
                previewSection.scrollIntoView({ behavior: 'smooth' });
                updateLivePreview();
            }
        });
    }
    
    // Collection item removal modal functionality
    const removeItemModal = document.getElementById('removeItemModal');
    const removeItemModalBg = document.getElementById('removeItemModalBg');
    const cancelRemoveBtn = document.getElementById('cancelRemoveBtn');
    const confirmRemoveBtn = document.getElementById('confirmRemoveBtn');
    
    if (cancelRemoveBtn) {
        cancelRemoveBtn.addEventListener('click', function() {
            removeItemModal.classList.add('hidden');
        });
    }
    
    if (removeItemModalBg) {
        removeItemModalBg.addEventListener('click', function() {
            removeItemModal.classList.add('hidden');
        });
    }
    
    if (confirmRemoveBtn) {
        confirmRemoveBtn.addEventListener('click', function() {
            const itemId = document.getElementById('removeItemId').value;
            
            // Send AJAX request to remove item
            fetch('<?php echo $adminUrl; ?>/media/ajax/remove_collection_item.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: itemId, parent_id: <?php echo $mediaId; ?> }),
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Reload the page to reflect changes
                    window.location.reload();
                } else {
                    alert('Error: ' + data.message);
                    removeItemModal.classList.add('hidden');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred. Please try again.');
                removeItemModal.classList.add('hidden');
            });
        });
    }
    
    // Helper function to get file type icon
    function getFileTypeIcon(mimeType) {
        // This would be expanded in a real application to return appropriate icons
        if (mimeType.startsWith('image/')) {
            return '/assets/images/file-thumbnails/image-icon.png';
        } else if (mimeType.startsWith('video/')) {
            return '/assets/images/file-thumbnails/video-icon.png';
        } else if (mimeType.startsWith('audio/')) {
            return '/assets/images/file-thumbnails/audio-icon.png';
        } else if (mimeType === 'application/pdf') {
            return '/assets/images/file-thumbnails/pdf-icon.png';
        } else {
            return '/assets/images/file-thumbnails/file-icon.png';
        }
    }
    
    // Settings submenu toggle
    const settingsToggle = document.querySelector('.settings-toggle');
    const settingsSubmenu = document.querySelector('.settings-submenu');
    
    if (settingsToggle) {
        settingsToggle.addEventListener('click', function() {
            settingsSubmenu.classList.toggle('hidden');
        });
    }
    
    // Mobile sidebar toggle
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.sidebar');
    
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function() {
            sidebar.classList.toggle('hidden');
        });
    }
    
    // Form validation
    const editMediaForm = document.getElementById('editMediaForm');
    if (editMediaForm) {
        editMediaForm.addEventListener('submit', function(e) {
            let valid = true;
            
            // Basic validation
            if (!titleInput.value.trim()) {
                alert('Title is required');
                titleInput.focus();
                valid = false;
            } else if (document.querySelector('input[name="upload_type"]:checked').value === 'replace' && 
                      (!mediaFileInput.files || mediaFileInput.files.length === 0)) {
                alert('Please select a file to upload');
                valid = false;
            } else if (document.querySelector('input[name="upload_type"]:checked').value === 'collection' && 
                      (!collectionFilesInput.files || collectionFilesInput.files.length === 0)) {
                alert('Please select at least one file for the collection');
                valid = false;
            }
            
            if (!valid) {
                e.preventDefault();
            }
        });
    }
    
    // Initialize the live preview
    updateLivePreview();
});
</script>

<!-- AJAX handler for collection item removal (note: create this file separately) -->
<script>
// This would be implemented in a separate PHP file: /admin/media/ajax/remove_collection_item.php
/*
<?php
// Ajax handler to remove collection item
header('Content-Type: application/json');

// Check if request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Get request body
$data = json_decode(file_get_contents('php://input'), true);
$itemId = (int)($data['id'] ?? 0);
$parentId = (int)($data['parent_id'] ?? 0);

if (!$itemId) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
    exit;
}

// Include database connection
require_once __DIR__ . '/../../../includes/init.php';
require_admin();

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // Get item details
    $stmt = $pdo->prepare("SELECT * FROM media WHERE id = :id");
    $stmt->execute([':id' => $itemId]);
    $item = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$item) {
        throw new Exception('Item not found');
    }
    
    // Verify it's part of the collection
    if ($item['parent_id'] != $parentId) {
        throw new Exception('Item is not part of this collection');
    }
    
    // Delete the item from database
    $stmt = $pdo->prepare("DELETE FROM media WHERE id = :id");
    $stmt->execute([':id' => $itemId]);
    
    // Clean up related records
    $stmt = $pdo->prepare("DELETE FROM media_tags WHERE media_id = :media_id");
    $stmt->execute([':media_id' => $itemId]);
    
    $stmt = $pdo->prepare("DELETE FROM media_categories WHERE media_id = :media_id");
    $stmt->execute([':media_id' => $itemId]);
    
    $stmt = $pdo->prepare("DELETE FROM media_colors WHERE media_id = :media_id");
    $stmt->execute([':media_id' => $itemId]);
    
    // Delete files
    if (!empty($item['file_path'])) {
        $filePath = __DIR__ . '/../../../' . ltrim($item['file_path'], '/');
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }
    
    if (!empty($item['thumbnail_url'])) {
        $thumbnailPath = __DIR__ . '/../../../' . ltrim($item['thumbnail_url'], '/');
        if (file_exists($thumbnailPath)) {
            unlink($thumbnailPath);
        }
    }
    
    // Log activity
    $stmt = $pdo->prepare("
        INSERT INTO activities (user_id, description, created_at)
        VALUES (:user_id, :description, NOW())
    ");
    
    $stmt->execute([
        ':user_id' => $_SESSION['user_id'] ?? null,
        ':description' => "Removed collection item #{$itemId} from collection #{$parentId}"
    ]);
    
    // Commit transaction
    $pdo->commit();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    // Rollback transaction on error
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
*/
</script>

<?php
// Include admin footer
require_once __DIR__ . '/../../theme/admin/footer.php';
?>
                                