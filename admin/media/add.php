<?php
/**
 * Add New Media Page
 *
 * @package WallPix
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'Add New Media';

// Include header
require_once '../../theme/admin/header.php';

// Include sidebar
require_once '../../theme/admin/slidbar.php';

// Process form submission
$successMessage = '';
$errorMessage = '';
$newMediaId = null;

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

// Function to create slugs from titles
function createSlug($string) {
    // Replace non-alphanumeric characters with hyphens
    $slug = preg_replace('/[^A-Za-z0-9-]+/', '-', $string);
    // Convert to lowercase
    $slug = strtolower($slug);
    // Remove leading/trailing hyphens
    $slug = trim($slug, '-');
    // If empty, use a default
    if (empty($slug)) {
        $slug = 'media-' . time();
    }
    return $slug;
}

// Function to create nested category dropdown
function buildCategoryOptions($categories, $parent = null, $indent = '') {
    $html = '';
    foreach ($categories as $category) {
        if (($parent === null && $category['parent_id'] === null) || $category['parent_id'] === $parent) {
            $html .= '<option value="' . $category['id'] . '">' . $indent . htmlspecialchars($category['name']) . '</option>';
            
            // Find children
            $html .= buildCategoryOptions($categories, $category['id'], $indent . '&nbsp;&nbsp;&nbsp;');
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

// Process form submission
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
        $uploadType = $_POST['upload_type'] ?? 'file';
        $applyWatermark = isset($_POST['apply_watermark']) ? 1 : 0;
        $selectedCategories = $_POST['additional_categories'] ?? [];
        $resolution_id = (int)($_POST['resolution_id'] ?? 0);
        
        // Generate slug from title
        $slug = createSlug($title);
        
        // Add primary category to selected categories if not already there
        if ($categoryId && !in_array($categoryId, $selectedCategories)) {
            array_unshift($selectedCategories, $categoryId);
        }
        
        // Validate required fields
        if (empty($title)) {
            throw new Exception('Title is required.');
        }
        
        $filePath = '';
        $fileName = '';
        $fileType = '';
        $fileSize = '';
        $thumbnailUrl = '';
        $width = '';
        $height = '';
        
        // Handle file upload
        if ($uploadType === 'file' && isset($_FILES['media_file']) && $_FILES['media_file']['error'] === 0) {
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
            } else {
                throw new Exception('Failed to upload file.');
            }
        } else if ($uploadType === 'collection') {
            // For collection upload, we'll create a main record and handle attached files
            // Process collection files
            $collectionFiles = $_FILES['collection_files'] ?? [];
            
            // Validate at least one file is uploaded
            if (empty($collectionFiles['name'][0])) {
                throw new Exception('Please upload at least one file in the collection.');
            }
            
            // We'll use the first file as the main media
            $fileTmpName = $collectionFiles['tmp_name'][0];
            $originalFileName = $collectionFiles['name'][0];
            $fileSize = $collectionFiles['size'][0];
            $fileType = $collectionFiles['type'][0];
            
            $uploadDir = __DIR__ . '/../../uploads/media/' . date('Y/m') . '/';
            $thumbnailDir = __DIR__ . '/../../uploads/thumbnails/' . date('Y/m') . '/';
            
            // Create directories if they don't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            if (!is_dir($thumbnailDir)) {
                mkdir($thumbnailDir, 0755, true);
            }
            
            // Generate unique filename for the main file
            $uniqid = uniqid();
            $fileName = $uniqid . '_' . basename($originalFileName);
            $filePath = '/uploads/media/' . date('Y/m') . '/' . $fileName;
            $fullFilePath = $uploadDir . $fileName;
            
            // Move the main file
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
                }
            } else {
                throw new Exception('Failed to upload main file in collection.');
            }
            
            // Store collection data to process after main media insertion
            $collectionData = [
                'files' => $collectionFiles,
                'count' => count($collectionFiles['name'])
            ];
        } else {
            throw new Exception('Please upload a file or collection.');
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
        
        // Insert into media table
        $stmt = $pdo->prepare("
            INSERT INTO media (
                title, description, category_id, file_name, file_path, file_type, file_size, 
                thumbnail_url, status, featured, width, height, background_color, orientation, 
                owner, license, publish_date, paid_content, created_by, ai_enhanced, resolution_id,
                slug, created_at
            ) VALUES (
                :title, :description, :category_id, :file_name, :file_path, :file_type, :file_size, 
                :thumbnail_url, :status, :featured, :width, :height, :background_color, :orientation, 
                :owner, :license, :publish_date, :paid_content, :created_by, :ai_enhanced, :resolution_id,
                :slug, NOW()
            )
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
            ':created_by' => $_SESSION['user_id'] ?? 1,
            ':ai_enhanced' => $aiEnhanced,
            ':resolution_id' => $resolution_id,
            ':slug' => $slug
        ]);
        
        $newMediaId = $pdo->lastInsertId();
        
        // Insert dominant colors if available
        if (isset($dominantColors) && $newMediaId) {
            $stmt = $pdo->prepare("
                INSERT INTO media_colors (
                    media_id, primary_color, secondary_color, is_dark, created_at
                ) VALUES (
                    :media_id, :primary_color, :secondary_color, :is_dark, NOW()
                )
            ");
            
            $stmt->execute([
                ':media_id' => $newMediaId,
                ':primary_color' => $dominantColors['primary'],
                ':secondary_color' => $dominantColors['secondary'],
                ':is_dark' => $dominantColors['is_dark']
            ]);
        }
        
        // Insert tags
        if (!empty($tagIds)) {
            $insertTagStmt = $pdo->prepare("
                INSERT INTO media_tags (media_id, tag_id, created_by)
                VALUES (:media_id, :tag_id, :created_by)
            ");
            
            foreach ($tagIds as $tagId) {
                $insertTagStmt->execute([
                    ':media_id' => $newMediaId,
                    ':tag_id' => (int)$tagId,
                    ':created_by' => $_SESSION['user_id'] ?? null
                ]);
            }
        }
        
        // Process additional collection files if present
        if (isset($collectionData) && $collectionData['count'] > 1) {
            // Skip the first file as it's already processed
            for ($i = 1; $i < $collectionData['count']; $i++) {
                if ($collectionData['error'][$i] === 0) {
                    $collectionFileTmp = $collectionData['files']['tmp_name'][$i];
                    $collectionFileName = $collectionData['files']['name'][$i];
                    $collectionFileSize = $collectionData['files']['size'][$i];
                    $collectionFileType = $collectionData['files']['type'][$i];
                    
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
                                    applyWatermark($collFullFilePath, $watermarkSettings);
                                }
                            }
                        }
                        
                        $collectionTitle = $title . ' - Item ' . $i;
                        $collectionSlug = $slug . '-item-' . $i;
                        
                        $stmt = $pdo->prepare("
                            INSERT INTO media (
                                title, description, category_id, file_name, file_path, file_type, file_size,
                                thumbnail_url, status, featured, width, height, background_color, orientation,
                                owner, license, publish_date, paid_content, created_by, ai_enhanced, resolution_id,
                                parent_id, slug, created_at
                            ) VALUES (
                                :title, :description, :category_id, :file_name, :file_path, :file_type, :file_size,
                                :thumbnail_url, :status, :featured, :width, :height, :background_color, :orientation,
                                :owner, :license, :publish_date, :paid_content, :created_by, :ai_enhanced, :resolution_id,
                                :parent_id, :slug, NOW()
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
                            ':parent_id' => $newMediaId,
                            ':slug' => $collectionSlug
                        ]);
                        
                        $collItemId = $pdo->lastInsertId();
                        
                        // Add the same tags to collection items
                        if (!empty($tagIds)) {
                            foreach ($tagIds as $tagId) {
                                $insertTagStmt->execute([
                                    ':media_id' => $collItemId,
                                    ':tag_id' => (int)$tagId,
                                    ':created_by' => $_SESSION['user_id'] ?? null
                                ]);
                            }
                        }
                    }
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
            ':description' => "Added new media item #{$newMediaId}: {$title}"
        ]);
        
        // Commit transaction
        $pdo->commit();
        
        $successMessage = "Media item '{$title}' has been added successfully.";
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
?>

<!-- Main Content -->
<div class="content-wrapper px-4 py-6 lg:px-8">
    <div class="max-w-full mx-auto">
        <!-- Page Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold <?php echo $darkMode ? 'text-gray-200' : 'text-gray-800'; ?>">
                <i class="fas fa-plus-circle mr-2"></i> Add New Media
            </h1>
            <a href="<?php echo $adminUrl; ?>/media/index.php" class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition-colors">
                <i class="fas fa-arrow-left mr-2"></i> Back to Media List
            </a>
        </div>
        
        <!-- Alert Messages -->
        <?php if ($successMessage): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6" role="alert">
                <p><?php echo $successMessage; ?></p>
                <?php if ($newMediaId): ?>
                    <div class="mt-2">
                        <a href="<?php echo $adminUrl; ?>/media/edit.php?id=<?php echo $newMediaId; ?>" class="text-blue-600 hover:underline">
                            <i class="fas fa-edit mr-1"></i> Edit this media
                        </a>
                        <span class="mx-2">|</span>
                        <a href="<?php echo $adminUrl; ?>/media/add.php" class="text-blue-600 hover:underline">
                            <i class="fas fa-plus-circle mr-1"></i> Add another media
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($errorMessage): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6" role="alert">
                <p><?php echo $errorMessage; ?></p>
            </div>
        <?php endif; ?>
        
        <!-- Add Media Form -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
            <div class="p-6">
                <form id="addMediaForm" action="" method="post" enctype="multipart/form-data" class="space-y-6">
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
                                    <i class="fas fa-upload mr-2"></i> Media Upload
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
                                    <input type="text" id="title" name="title" required 
                                        class="w-full p-2 border rounded-md <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-gray-200' : 'bg-white border-gray-300'; ?>"
                                        placeholder="Enter media title">
                                </div>
                                
                                <!-- Description -->
                                <div class="col-span-2">
                                    <label for="description" class="block font-medium mb-1 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">Description</label>
                                    <textarea id="description" name="description" rows="4" 
                                        class="w-full p-2 border rounded-md <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-gray-200' : 'bg-white border-gray-300'; ?>"
                                        placeholder="Enter media description"></textarea>
                                </div>
                                
                                <!-- Status & Featured -->
                                <div>
                                    <label class="block font-medium mb-2 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">Status & Visibility</label>
                                    <div class="space-y-2">
                                        <div class="flex items-center">
                                            <input type="checkbox" id="status" name="status" class="h-5 w-5 text-blue-600" checked>
                                            <label for="status" class="ml-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                                Active (visible to users)
                                            </label>
                                        </div>
                                        <div class="flex items-center">
                                            <input type="checkbox" id="featured" name="featured" class="h-5 w-5 text-blue-600">
                                            <label for="featured" class="ml-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                                Featured (shown in featured sections)
                                            </label>
                                        </div>
                                        <div class="flex items-center">
                                            <input type="checkbox" id="paid_content" name="paid_content" class="h-5 w-5 text-blue-600">
                                            <label for="paid_content" class="ml-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                                Premium Content (for paid users only)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Publishing Date -->
                                <div>
                                    <label for="publish_date" class="block font-medium mb-1 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">Publish Date</label>
                                    <input type="date" id="publish_date" name="publish_date" value="<?php echo date('Y-m-d'); ?>" 
                                        class="w-full p-2 border rounded-md <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-gray-200' : 'bg-white border-gray-300'; ?>">
                                </div>
                            </div>
                        </div>
                        
                        <!-- Media Upload Tab -->
                        <div id="media-upload" class="tab-pane hidden">
                            <!-- Upload Type Selection -->
                            <div class="mb-6">
                                <label class="block font-medium mb-2 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">Upload Method</label>
                                <div class="flex space-x-4">
                                    <div class="flex items-center">
                                        <input type="radio" id="upload_file" name="upload_type" value="file" class="h-4 w-4 text-blue-600" checked onchange="toggleUploadMethod()">
                                        <label for="upload_file" class="ml-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                            Single File Upload
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input type="radio" id="upload_collection" name="upload_type" value="collection" class="h-4 w-4 text-blue-600" onchange="toggleUploadMethod()">
                                        <label for="upload_collection" class="ml-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                            Collection (Multiple Files)
                                        </label>
                                    </div>
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
                                        <option value="<?php echo $resolution['id']; ?>"><?php echo htmlspecialchars($resolution['resolution']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="mt-1 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    Select the resolution for this media item
                                </p>
                            </div>
                            
                            <!-- Single File Upload Section -->
                            <div id="file_upload_section">
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
                                
                                <!-- File Details -->
                                <div id="file_details" class="hidden">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <!-- File Information -->
                                        <div>
                                            <h3 class="font-semibold mb-2 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-800'; ?>">File Information</h3>
                                            <div class="space-y-2">
                                                <p><span class="<?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">File Name:</span> <span id="info-filename" class="<?php echo $darkMode ? 'text-gray-200' : 'text-gray-800'; ?>"></span></p>
                                                <p><span class="<?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">File Size:</span> <span id="info-filesize" class="<?php echo $darkMode ? 'text-gray-200' : 'text-gray-800'; ?>"></span></p>
                                                <p><span class="<?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">File Type:</span> <span id="info-filetype" class="<?php echo $darkMode ? 'text-gray-200' : 'text-gray-800'; ?>"></span></p>
                                                <p><span class="<?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">Dimensions:</span> <span id="info-dimensions" class="<?php echo $darkMode ? 'text-gray-200' : 'text-gray-800'; ?>">-</span></p>
                                            </div>
                                        </div>
                                        
                                        <!-- Watermark Option -->
                                        <div>
                                            <?php if ($watermarkEnabled): ?>
                                                <div class="bg-blue-50 border-l-4 border-blue-500 text-blue-700 p-4 mb-4 <?php echo $darkMode ? 'bg-blue-900 text-blue-200' : ''; ?>">
                                                    <div class="flex items-center">
                                                        <input type="checkbox" id="apply_watermark" name="apply_watermark" class="h-5 w-5 text-blue-600" checked>
                                                        <label for="apply_watermark" class="ml-2 <?php echo $darkMode ? 'text-blue-200' : 'text-blue-700'; ?>">
                                                            Apply watermark to this media
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
                                </div>
                            </div>
                            
                            <!-- Collection (Multiple Files) Section -->
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
                                            Create a collection of related files (images, videos, etc.)
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
                                        <h3 class="font-semibold mb-2 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-800'; ?>">Collection Information</h3>
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
                                        <?php echo buildCategoryOptions($categories); ?>
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
                                                    class="h-4 w-4 text-blue-600">
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
                                        <input type="hidden" id="tag_input" name="tag_input" value="">
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
                                            <input type="radio" id="orientation_portrait" name="orientation" value="portrait" class="h-4 w-4 text-blue-600" checked>
                                            <label for="orientation_portrait" class="ml-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                                Portrait
                                            </label>
                                        </div>
                                        <div class="flex items-center">
                                            <input type="radio" id="orientation_landscape" name="orientation" value="landscape" class="h-4 w-4 text-blue-600">
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
                                        <input type="color" id="background_color" name="background_color" value="#FFFFFF" 
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
                                    <input type="text" id="owner" name="owner" 
                                        class="w-full p-2 border rounded-md <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-gray-200' : 'bg-white border-gray-300'; ?>"
                                        placeholder="Original creator or copyright owner">
                                </div>
                                
                                <div>
                                    <label for="license" class="block font-medium mb-1 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">License</label>
                                    <input type="text" id="license" name="license" 
                                        class="w-full p-2 border rounded-md <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-gray-200' : 'bg-white border-gray-300'; ?>"
                                        placeholder="e.g., Creative Commons, Royalty Free">
                                </div>
                                
                                <!-- AI Enhanced -->
                                <div class="col-span-2">
                                    <div class="flex items-center">
                                        <input type="checkbox" id="ai_enhanced" name="ai_enhanced" class="h-5 w-5 text-blue-600">
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
                    </div>
                    
                    <!-- Live Preview Section -->
                    <div id="live-preview-section" class="mt-8 border-t pt-6 <?php echo $darkMode ? 'border-gray-600' : 'border-gray-200'; ?>">
                        <h3 class="text-lg font-semibold mb-4 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-800'; ?>">
                            <i class="fas fa-eye mr-2"></i> Live Preview
                        </h3>
                        
                        <div class="bg-white rounded-md shadow-md overflow-hidden <?php echo $darkMode ? 'bg-gray-800' : ''; ?>">
                            <div class="p-4">
                                <h4 id="preview-title" class="text-xl font-bold mb-2 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-800'; ?>">Media Title</h4>
                                <p id="preview-description" class="text-sm mb-4 <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">Media description will appear here.</p>
                                
                                <div class="flex justify-center mb-4">
                                    <div id="preview-media" class="rounded-md overflow-hidden flex items-center justify-center" style="height: 300px; background-color: #f0f0f0;">
                                        <i class="fas fa-image text-6xl <?php echo $darkMode ? 'text-gray-600' : 'text-gray-400'; ?>"></i>
                                    </div>
                                </div>
                                
                                <div class="flex flex-wrap gap-2 mb-4">
                                    <span id="preview-category" class="px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 <?php echo $darkMode ? 'bg-blue-900 text-blue-200' : ''; ?>">
                                        Category
                                    </span>
                                    <div id="preview-tags" class="flex flex-wrap gap-1">
                                        <!-- Preview tags will appear here -->
                                    </div>
                                </div>
                                
                                <div class="flex justify-between text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    <span id="preview-owner">Owner: <?php echo $currentUser; ?></span>
                                    <span id="preview-date">Date: <?php echo date('Y-m-d', strtotime($currentDateTime)); ?></span>
                                </div>
                                
                                <div class="mt-2 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    <span id="preview-resolution">Resolution: Not selected</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Form Buttons -->
                    <div class="flex justify-between pt-6 border-t <?php echo $darkMode ? 'border-gray-600' : 'border-gray-200'; ?>">
                        <button type="reset" class="px-6 py-2 bg-gray-500 text-white rounded-md hover:bg-gray-600 transition-colors">
                            <i class="fas fa-undo mr-2"></i> Reset Form
                        </button>
                        
                        <div>
                            <button type="button" id="preview_button" class="px-6 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors mr-2">
                                <i class="fas fa-eye mr-2"></i> Preview
                            </button>
                            
                            <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                                <i class="fas fa-save mr-2"></i> Save Media
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Add Media Page -->
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
    
    // Toggle between file upload and collection upload
    window.toggleUploadMethod = function() {
        const uploadType = document.querySelector('input[name="upload_type"]:checked').value;
        const fileSection = document.getElementById('file_upload_section');
        const collectionSection = document.getElementById('collection_section');
        
        if (uploadType === 'file') {
            fileSection.classList.remove('hidden');
            collectionSection.classList.add('hidden');
        } else {
            fileSection.classList.add('hidden');
            collectionSection.classList.remove('hidden');
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
    const fileDetails = document.getElementById('file_details');
    
    // File information elements
    const infoFilename = document.getElementById('info-filename');
    const infoFilesize = document.getElementById('info-filesize');
    const infoFiletype = document.getElementById('info-filetype');
    const infoDimensions = document.getElementById('info-dimensions');
    
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
                    
                    // Get image dimensions
                    const img = new Image();
                    img.onload = function() {
                        infoDimensions.textContent = this.width + ' x ' + this.height + ' pixels';
                        
                        // Auto-select orientation based on dimensions
                        if (this.width > this.height) {
                            document.getElementById('orientation_landscape').checked = true;
                        } else {
                            document.getElementById('orientation_portrait').checked = true;
                        }
                    };
                    img.src = e.target.result;
                };
                
                reader.readAsDataURL(file);
            } else if (file.type.match('video.*')) {
                // For video files, show a video player or thumbnail
                previewImage.src = '/assets/images/file-thumbnails/video-icon.png'; // Default video icon
                uploadPreview.classList.remove('hidden');
                uploadPrompt.classList.add('hidden');
            } else {
                // For non-image/video files, show generic icon
                previewImage.src = getFileTypeIcon(file.type);
                uploadPreview.classList.remove('hidden');
                uploadPrompt.classList.add('hidden');
            }
            
            // Update file information
            infoFilename.textContent = file.name;
            infoFilesize.textContent = formatFileSize(file.size);
            infoFiletype.textContent = file.type || 'Unknown';
            
            // Show file details section
            fileDetails.classList.remove('hidden');
            
            // Update live preview
            updateLivePreview();
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
            
            // If the first file is an image, use it for orientation
            if (files[0].type.match('image.*')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = new Image();
                    img.onload = function() {
                        // Auto-select orientation based on dimensions
                        if (this.width > this.height) {
                            document.getElementById('orientation_landscape').checked = true;
                        } else {
                            document.getElementById('orientation_portrait').checked = true;
                        }
                    };
                    img.src = e.target.result;
                };
                reader.readAsDataURL(files[0]);
            }
        }
    });
    
    // Drag and drop functionality for single file
    const uploadArea = document.querySelector('#file_upload_section .border-dashed');
    
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
    
    // Drag and drop functionality for collection
    const collectionArea = document.querySelector('#collection_section .border-dashed');
    
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
    
    // Tags Functionality with Auto-complete
    const tagInputField = document.getElementById('tag_input_field');
    const tagInputHidden = document.getElementById('tag_input');
    const selectedTagsContainer = document.getElementById('selected-tags');
    const tagSuggestions = document.getElementById('tag-suggestions');
    const addTagButton = document.getElementById('add_tag_btn');
    
    // All available tags from PHP
    const availableTags = <?php echo json_encode(array_map(function($tag) { return $tag['name']; }, $tags)); ?>;
    
    // Selected tags array
    let selectedTags = [];
    
    // Function to add a tag
    function addTag(tagName) {
        tagName = tagName.trim();
        
        if (tagName && !selectedTags.includes(tagName)) {
            // Add to selected tags array
            selectedTags.push(tagName);
            
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
            
            // Clear input field
            tagInputField.value = '';
            
            // Update hidden input
            updateHiddenInput();
            
            // Update live preview
            updateLivePreview();
        }
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
    
    // Click outside to hide suggestions
    document.addEventListener('click', function(e) {
        if (!tagInputField.contains(e.target) && !tagSuggestions.contains(e.target)) {
            tagSuggestions.classList.add('hidden');
        }
    });
    
    // Predefined colors dropdown
    const backgroundColorInput = document.getElementById('background_color');
    const predefinedColorsSelect = document.getElementById('predefined_colors');
    
    predefinedColorsSelect.addEventListener('change', function() {
        if (this.value) {
            backgroundColorInput.value = this.value;
            // Update preview background
            updateLivePreview();
        }
    });
    
    // Resolution selection
    const resolutionSelect = document.getElementById('resolution_id');
    
    // Live preview functionality
    const titleInput = document.getElementById('title');
    const descriptionInput = document.getElementById('description');
    const categorySelect = document.getElementById('category_id');
    const ownerInput = document.getElementById('owner');
    const publishDateInput = document.getElementById('publish_date');
    
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
        // Update title and description
        previewTitle.textContent = titleInput.value || 'Media Title';
        previewDescription.textContent = descriptionInput.value || 'Media description will appear here.';
        
        // Update media preview
        if (document.querySelector('input[name="upload_type"]:checked').value === 'file') {
            if (mediaFileInput.files && mediaFileInput.files[0]) {
                const file = mediaFileInput.files[0];
                
                if (file.type.match('image.*')) {
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        previewMedia.innerHTML = `<img src="${e.target.result}" alt="Preview" class="max-h-full max-w-full">`;
                        
                        // Set background color
                        previewMedia.style.backgroundColor = backgroundColorInput.value;
                    };
                    reader.readAsDataURL(file);
                } else if (file.type.match('video.*')) {
                    previewMedia.innerHTML = `
                        <div class="flex flex-col items-center justify-center">
                            <i class="fas fa-video text-5xl mb-2 <?php echo $darkMode ? 'text-gray-500' : 'text-gray-400'; ?>"></i>
                            <span class="<?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">${file.name}</span>
                        </div>
                    `;
                } else {
                    // Show generic icon for non-image/video files
                    previewMedia.innerHTML = `
                        <div class="flex flex-col items-center justify-center">
                            <i class="fas fa-file text-5xl mb-2 <?php echo $darkMode ? 'text-gray-500' : 'text-gray-400'; ?>"></i>
                            <span class="<?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">${file.name}</span>
                        </div>
                    `;
                }
            }
        } else if (document.querySelector('input[name="upload_type"]:checked').value === 'collection') {
            if (collectionFilesInput.files && collectionFilesInput.files.length > 0) {
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
        if (categorySelect.value) {
            const selectedOption = categorySelect.options[categorySelect.selectedIndex];
            previewCategory.textContent = selectedOption.textContent;
            previewCategory.classList.remove('hidden');
        } else {
            previewCategory.classList.add('hidden');
        }
        
        // Update tags
        previewTags.innerHTML = '';
        selectedTags.forEach(tag => {
            const tagSpan = document.createElement('span');
            tagSpan.className = 'px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 <?php echo $darkMode ? "bg-green-900 text-green-200" : ""; ?>';
            tagSpan.textContent = tag;
            previewTags.appendChild(tagSpan);
        });
        
        // Update owner and date
        previewOwner.textContent = 'Owner: ' + (ownerInput.value || '<?php echo $currentUser; ?>');
        previewDate.textContent = 'Date: ' + (publishDateInput.value || '<?php echo date('Y-m-d', strtotime($currentDateTime)); ?>');
        
        // Update resolution
        if (resolutionSelect.value) {
            const selectedResolution = resolutionSelect.options[resolutionSelect.selectedIndex].text;
            previewResolution.textContent = 'Resolution: ' + selectedResolution;
        } else {
            previewResolution.textContent = 'Resolution: Not selected';
        }
    }
    
    // Add event listeners to form elements for live preview
    const formElements = [
        titleInput, descriptionInput, categorySelect, ownerInput, publishDateInput, 
        backgroundColorInput, resolutionSelect
    ];
    
    formElements.forEach(element => {
        element.addEventListener('input', updateLivePreview);
        element.addEventListener('change', updateLivePreview);
    });
    
    // Preview button functionality
    const previewButton = document.getElementById('preview_button');
    previewButton.addEventListener('click', function() {
        const previewSection = document.getElementById('live-preview-section');
        previewSection.scrollIntoView({ behavior: 'smooth' });
        updateLivePreview();
    });
    
    // Helper function to check if a URL is an image
    function isImageUrl(url) {
        return /\.(jpg|jpeg|png|gif|webp|svg)$/i.test(url);
    }
    
    // Helper function to format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
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
    const addMediaForm = document.getElementById('addMediaForm');
    addMediaForm.addEventListener('submit', function(e) {
        let valid = true;
        
        // Basic validation
        if (!titleInput.value.trim()) {
            alert('Title is required');
            titleInput.focus();
            valid = false;
        } else if (document.querySelector('input[name="upload_type"]:checked').value === 'file' && 
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
    
    // Initialize the live preview
    updateLivePreview();
});
</script>

<?php
// Include admin footer
require_once __DIR__ . '/../../theme/admin/footer.php';
?>