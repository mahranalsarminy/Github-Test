<?php
/**
 * Watermark Settings Page
 *
 * @package WallPix
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'Watermark Settings';

// Include header
require_once '../../theme/admin/header.php';

// Include sidebar
require_once '../../theme/admin/slidbar.php';

// Process form submission
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get form data
        $type = $_POST['watermark_type'] ?? 'text';
        $textContent = $_POST['text_content'] ?? '';
        $textFont = $_POST['text_font'] ?? 'Arial';
        $textSize = (int)($_POST['text_size'] ?? 24);
        $textColor = $_POST['text_color'] ?? '#FFFFFF';
        $textOpacity = (float)($_POST['text_opacity'] ?? 0.70);
        $position = $_POST['position'] ?? 'bottom-right';
        $padding = (int)($_POST['padding'] ?? 10);
        $isActive = isset($_POST['is_active']) ? 1 : 0;
        $applyToNew = isset($_POST['apply_to_new']) ? 1 : 0;
        $applyToDownloads = isset($_POST['apply_to_downloads']) ? 1 : 0;
        
        // Handle image upload if image type is selected
        $imagePath = null;
        if ($type === 'image' && isset($_FILES['watermark_image']) && $_FILES['watermark_image']['error'] === 0) {
            $uploadDir = __DIR__ . '/../../uploads/watermarks/';
            
            // Create directory if it doesn't exist
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            // Get file info
            $fileName = $_FILES['watermark_image']['name'];
            $fileSize = $_FILES['watermark_image']['size'];
            $fileTmpName = $_FILES['watermark_image']['tmp_name'];
            $fileType = $_FILES['watermark_image']['type'];
            $fileError = $_FILES['watermark_image']['error'];
            
            // Generate a unique filename
            $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $uniqueName = uniqid('watermark_') . '.' . $fileExtension;
            $targetFilePath = $uploadDir . $uniqueName;
            
            // Check if image file is a valid image
            $allowedTypes = ['image/png', 'image/jpeg', 'image/gif'];
            if (!in_array($fileType, $allowedTypes)) {
                throw new Exception('Invalid file type. Only PNG, JPEG, and GIF are allowed.');
            }
            
            // Check file size (max 2MB)
            if ($fileSize > 2097152) {
                throw new Exception('File size exceeds 2MB limit.');
            }
            
            // Move the uploaded file
            if (move_uploaded_file($fileTmpName, $targetFilePath)) {
                $imagePath = '/uploads/watermarks/' . $uniqueName;
            } else {
                throw new Exception('Failed to upload image.');
            }
        } elseif ($type === 'image') {
            // Keep existing image path if no new image is uploaded
            $stmt = $pdo->prepare("SELECT image_path FROM watermark_settings WHERE id = 1");
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $imagePath = $row['image_path'] ?? null;
        }
        
        // Update database
        $stmt = $pdo->prepare("
            UPDATE watermark_settings
            SET 
                type = :type,
                text_content = :text_content,
                text_font = :text_font,
                text_size = :text_size,
                text_color = :text_color,
                text_opacity = :text_opacity,
                image_path = :image_path,
                position = :position,
                padding = :padding,
                is_active = :is_active,
                apply_to_new = :apply_to_new,
                apply_to_downloads = :apply_to_downloads,
                updated_at = NOW()
            WHERE id = 1
        ");
        
        $stmt->execute([
            ':type' => $type,
            ':text_content' => $textContent,
            ':text_font' => $textFont,
            ':text_size' => $textSize,
            ':text_color' => $textColor,
            ':text_opacity' => $textOpacity,
            ':image_path' => $imagePath,
            ':position' => $position,
            ':padding' => $padding,
            ':is_active' => $isActive,
            ':apply_to_new' => $applyToNew,
            ':apply_to_downloads' => $applyToDownloads
        ]);
        
        // Log the activity
        logAdminActivity("Updated watermark settings");
        
        $successMessage = 'Watermark settings updated successfully.';
    } catch (Exception $e) {
        $errorMessage = 'Error: ' . $e->getMessage();
    }
}

// Get current watermark settings
$stmt = $pdo->prepare("SELECT * FROM watermark_settings WHERE id = 1");
$stmt->execute();
$watermarkSettings = $stmt->fetch(PDO::FETCH_ASSOC);

// If no settings exist, create default
if (!$watermarkSettings) {
    $stmt = $pdo->prepare("
        INSERT INTO watermark_settings 
        (type, text_content, position, text_opacity, is_active) 
        VALUES 
        ('text', 'WallPix.Top', 'bottom-right', 0.70, 1)
    ");
    $stmt->execute();
    
    $stmt = $pdo->prepare("SELECT * FROM watermark_settings WHERE id = 1");
    $stmt->execute();
    $watermarkSettings = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Available fonts
$availableFonts = [
    'Arial' => 'Arial',
    'Verdana' => 'Verdana',
    'Tahoma' => 'Tahoma',
    'Georgia' => 'Georgia',
    'Times New Roman' => 'Times New Roman',
    'Courier New' => 'Courier New',
    'Impact' => 'Impact',
    'Comic Sans MS' => 'Comic Sans MS',
    'Trebuchet MS' => 'Trebuchet MS',
    'Palatino Linotype' => 'Palatino Linotype',
];

// Current date/time
$currentDateTime = '2025-03-22 19:54:36';
?>

<!-- Main Content -->
<div class="content-wrapper px-4 py-6 lg:px-8">
    <div class="max-w-full mx-auto">
        <!-- Page Header -->
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold <?php echo $darkMode ? 'text-gray-200' : 'text-gray-800'; ?>">
                <i class="fas fa-copyright mr-2"></i> Watermark Settings
            </h1>
            <span class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                Last updated: <?php echo date('F j, Y, g:i a', strtotime($watermarkSettings['updated_at'])); ?>
            </span>
        </div>
        
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
        
        <!-- Watermark Settings Form -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
            <div class="p-6">
                <form action="" method="post" enctype="multipart/form-data">
                    <!-- Watermark Enable/Disable -->
                    <div class="mb-6">
                        <div class="flex items-center">
                            <input type="checkbox" id="is_active" name="is_active" class="h-5 w-5 text-blue-600" <?php echo $watermarkSettings['is_active'] ? 'checked' : ''; ?>>
                            <label for="is_active" class="ml-2 text-lg font-semibold <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">
                                Enable Watermark
                            </label>
                        </div>
                        <p class="mt-1 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                            Turn the watermark feature on or off globally.
                        </p>
                    </div>
                    
                    <!-- Watermark Type Selection -->
                    <div class="mb-6">
                        <label class="block font-medium <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">Watermark Type</label>
                        <div class="mt-2 space-y-2">
                            <div class="flex items-center">
                                <input type="radio" id="type_text" name="watermark_type" value="text" class="h-4 w-4 text-blue-600" 
                                    <?php echo $watermarkSettings['type'] === 'text' ? 'checked' : ''; ?> 
                                    onchange="toggleWatermarkType()">
                                <label for="type_text" class="ml-2 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">
                                    Text Watermark
                                </label>
                            </div>
                            <div class="flex items-center">
                                <input type="radio" id="type_image" name="watermark_type" value="image" class="h-4 w-4 text-blue-600" 
                                    <?php echo $watermarkSettings['type'] === 'image' ? 'checked' : ''; ?> 
                                    onchange="toggleWatermarkType()">
                                <label for="type_image" class="ml-2 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">
                                    Image Watermark
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Text Watermark Settings -->
                    <div id="text_watermark_settings" class="mb-6 <?php echo $watermarkSettings['type'] === 'image' ? 'hidden' : ''; ?>">
                        <div class="border p-4 rounded-md <?php echo $darkMode ? 'border-gray-600 bg-gray-800' : 'border-gray-300'; ?>">
                            <h3 class="font-semibold mb-4 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">Text Watermark Settings</h3>
                            
                            <!-- Text Content -->
                            <div class="mb-4">
                                <label for="text_content" class="block font-medium mb-1 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">
                                    Watermark Text
                                </label>
                                <input type="text" id="text_content" name="text_content" value="<?php echo htmlspecialchars($watermarkSettings['text_content']); ?>" 
                                    class="w-full p-2 border rounded-md <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-gray-200' : 'bg-white border-gray-300'; ?>">
                                <p class="mt-1 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    The text to display as watermark.
                                </p>
                            </div>
                            
                            <!-- Font Selection -->
                            <div class="mb-4">
                                <label for="text_font" class="block font-medium mb-1 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">
                                    Font
                                </label>
                                <select id="text_font" name="text_font" 
                                    class="w-full p-2 border rounded-md <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-gray-200' : 'bg-white border-gray-300'; ?>">
                                    <?php foreach ($availableFonts as $value => $label): ?>
                                        <option value="<?php echo $value; ?>" <?php echo $watermarkSettings['text_font'] === $value ? 'selected' : ''; ?>>
                                            <?php echo $label; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Font Size -->
                            <div class="mb-4">
                                <label for="text_size" class="block font-medium mb-1 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">
                                    Font Size (px)
                                </label>
                                <input type="number" id="text_size" name="text_size" value="<?php echo $watermarkSettings['text_size']; ?>" min="8" max="72" 
                                    class="w-full p-2 border rounded-md <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-gray-200' : 'bg-white border-gray-300'; ?>">
                            </div>
                            
                            <!-- Text Color -->
                            <div class="mb-4">
                                <label for="text_color" class="block font-medium mb-1 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">
                                    Text Color
                                </label>
                                <div class="flex">
                                    <input type="color" id="text_color" name="text_color" value="<?php echo $watermarkSettings['text_color']; ?>" 
                                        class="h-10 w-20 p-1 border rounded-md">
                                    <input type="text" id="text_color_hex" value="<?php echo $watermarkSettings['text_color']; ?>" 
                                        class="ml-2 w-32 p-2 border rounded-md <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-gray-200' : 'bg-white border-gray-300'; ?>" readonly>
                                </div>
                            </div>
                            
                            <!-- Text Opacity -->
                            <div class="mb-4">
                                <label for="text_opacity" class="block font-medium mb-1 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">
                                    Opacity: <span id="text_opacity_value"><?php echo $watermarkSettings['text_opacity'] * 100; ?>%</span>
                                </label>
                                <input type="range" id="text_opacity" name="text_opacity" min="0.1" max="1" step="0.05" 
                                    value="<?php echo $watermarkSettings['text_opacity']; ?>" 
                                    class="w-full h-2 bg-gray-300 rounded-lg appearance-none cursor-pointer">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Image Watermark Settings -->
                    <div id="image_watermark_settings" class="mb-6 <?php echo $watermarkSettings['type'] === 'text' ? 'hidden' : ''; ?>">
                        <div class="border p-4 rounded-md <?php echo $darkMode ? 'border-gray-600 bg-gray-800' : 'border-gray-300'; ?>">
                            <h3 class="font-semibold mb-4 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">Image Watermark Settings</h3>
                            
                            <!-- Current Image Preview -->
                            <?php if ($watermarkSettings['image_path']): ?>
                                <div class="mb-4">
                                    <label class="block font-medium mb-2 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">
                                        Current Watermark Image
                                    </label>
                                    <div class="border p-2 rounded-md <?php echo $darkMode ? 'border-gray-600 bg-gray-900' : 'border-gray-300 bg-gray-100'; ?>">
                                        <img src="<?php echo $watermarkSettings['image_path']; ?>" alt="Watermark" class="max-h-32">
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Image Upload -->
                            <div class="mb-4">
                                <label for="watermark_image" class="block font-medium mb-1 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">
                                    Upload New Watermark Image
                                </label>
                                <input type="file" id="watermark_image" name="watermark_image" accept="image/png,image/jpeg,image/gif" 
                                    class="w-full p-2 border rounded-md <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-gray-200' : 'bg-white border-gray-300'; ?>">
                                <p class="mt-1 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    Recommended: transparent PNG file (max 2MB).
                                </p>
                            </div>
                            
                            <!-- Image Opacity -->
                            <div class="mb-4">
                                <label for="image_opacity" class="block font-medium mb-1 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">
                                    Opacity: <span id="image_opacity_value"><?php echo $watermarkSettings['image_opacity'] * 100; ?>%</span>
                                </label>
                                <input type="range" id="image_opacity" name="image_opacity" min="0.1" max="1" step="0.05" 
                                    value="<?php echo $watermarkSettings['image_opacity']; ?>" 
                                    class="w-full h-2 bg-gray-300 rounded-lg appearance-none cursor-pointer">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Common Watermark Settings -->
                    <div class="mb-6">
                        <div class="border p-4 rounded-md <?php echo $darkMode ? 'border-gray-600 bg-gray-800' : 'border-gray-300'; ?>">
                            <h3 class="font-semibold mb-4 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">Watermark Positioning</h3>
                            
                            <!-- Position Selection -->
                            <div class="mb-4">
                                <label for="position" class="block font-medium mb-1 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">
                                    Position
                                </label>
                                <select id="position" name="position" 
                                    class="w-full p-2 border rounded-md <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-gray-200' : 'bg-white border-gray-300'; ?>">
                                    <option value="top-left" <?php echo $watermarkSettings['position'] === 'top-left' ? 'selected' : ''; ?>>Top Left</option>
                                    <option value="top-right" <?php echo $watermarkSettings['position'] === 'top-right' ? 'selected' : ''; ?>>Top Right</option>
                                    <option value="bottom-left" <?php echo $watermarkSettings['position'] === 'bottom-left' ? 'selected' : ''; ?>>Bottom Left</option>
                                    <option value="bottom-right" <?php echo $watermarkSettings['position'] === 'bottom-right' ? 'selected' : ''; ?>>Bottom Right</option>
                                    <option value="center" <?php echo $watermarkSettings['position'] === 'center' ? 'selected' : ''; ?>>Center</option>
                                    <option value="full" <?php echo $watermarkSettings['position'] === 'full' ? 'selected' : ''; ?>>Full (Tiled across image)</option>
                                </select>
                            </div>
                            
                            <!-- Padding -->
                            <div class="mb-4">
                                <label for="padding" class="block font-medium mb-1 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">
                                    Padding (px)
                                </label>
                                <input type="number" id="padding" name="padding" value="<?php echo $watermarkSettings['padding']; ?>" min="0" max="50" 
                                    class="w-full p-2 border rounded-md <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-gray-200' : 'bg-white border-gray-300'; ?>">
                                <p class="mt-1 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    Distance from edge of image (not applicable for center or full positions).
                                </p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Application Options -->
                    <div class="mb-6">
                        <div class="border p-4 rounded-md <?php echo $darkMode ? 'border-gray-600 bg-gray-800' : 'border-gray-300'; ?>">
                            <h3 class="font-semibold mb-4 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">Application Settings</h3>
                            
                            <div class="space-y-3">
                                <div class="flex items-center">
                                    <input type="checkbox" id="apply_to_new" name="apply_to_new" class="h-5 w-5 text-blue-600" 
                                        <?php echo $watermarkSettings['apply_to_new'] ? 'checked' : ''; ?>>
                                    <label for="apply_to_new" class="ml-2 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">
                                        Apply watermark to newly uploaded media
                                    </label>
                                </div>
                                
                                <div class="flex items-center">
                                    <input type="checkbox" id="apply_to_downloads" name="apply_to_downloads" class="h-5 w-5 text-blue-600" 
                                        <?php echo $watermarkSettings['apply_to_downloads'] ? 'checked' : ''; ?>>
                                    <label for="apply_to_downloads" class="ml-2 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">
                                        Apply watermark to downloaded media
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Preview Section -->
                    <div class="mb-6">
                        <div class="border p-4 rounded-md <?php echo $darkMode ? 'border-gray-600 bg-gray-800' : 'border-gray-300'; ?>">
                            <h3 class="font-semibold mb-4 <?php echo $darkMode ? 'text-gray-200' : 'text-gray-700'; ?>">Preview</h3>
                            
                            <div class="bg-gray-200 relative overflow-hidden h-64 rounded-md <?php echo $darkMode ? 'bg-gray-900' : ''; ?>">
                                <!-- Sample image for preview -->
                                <div class="h-full w-full bg-gradient-to-br from-blue-500 to-purple-600"></div>
                                
                                <!-- Watermark preview will be rendered by JavaScript -->
                                <div id="watermark_preview" class="absolute p-2"></div>
                            </div>
                            
                            <p class="mt-2 text-sm text-center italic <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                Preview shows approximate watermark appearance
                            </p>
                        </div>
                    </div>
                    
                    <!-- Submit Buttons -->
                    <div class="flex justify-between">
                        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                            <i class="fas fa-save mr-2"></i> Save Settings
                        </button>
                        
                        <button type="button" id="testWatermarkBtn" class="px-6 py-2 bg-gray-600 text-white rounded-md hover:bg-gray-700 transition-colors">
                            <i class="fas fa-flask mr-2"></i> Test on Image
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript for Watermark Settings Page -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle between text and image watermark settings
    function toggleWatermarkType() {
        const selectedType = document.querySelector('input[name="watermark_type"]:checked').value;
        const textSettings = document.getElementById('text_watermark_settings');
        const imageSettings = document.getElementById('image_watermark_settings');
        
        if (selectedType === 'text') {
            textSettings.classList.remove('hidden');
            imageSettings.classList.add('hidden');
        } else {
            textSettings.classList.add('hidden');
            imageSettings.classList.remove('hidden');
        }
        
        updateWatermarkPreview();
    }
    
    // Assign the function to window for access from inline event handlers
    window.toggleWatermarkType = toggleWatermarkType;
    
    // Update opacity display values
    const textOpacitySlider = document.getElementById('text_opacity');
    const textOpacityValue = document.getElementById('text_opacity_value');
    
    textOpacitySlider.addEventListener('input', function() {
        textOpacityValue.textContent = Math.round(this.value * 100) + '%';
        updateWatermarkPreview();
    });
    
    const imageOpacitySlider = document.getElementById('image_opacity');
    const imageOpacityValue = document.getElementById('image_opacity_value');
    
    imageOpacitySlider.addEventListener('input', function() {
        imageOpacityValue.textContent = Math.round(this.value * 100) + '%';
        updateWatermarkPreview();
    });
    
    // Update color hex input when color picker changes
    const textColorPicker = document.getElementById('text_color');
    const textColorHex = document.getElementById('text_color_hex');
    
    textColorPicker.addEventListener('input', function() {
        textColorHex.value = this.value;
        updateWatermarkPreview();
    });
    
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
    
    // Watermark Preview Function
    function updateWatermarkPreview() {
        const preview = document.getElementById('watermark_preview');
        const previewContainer = preview.parentElement;
        const type = document.querySelector('input[name="watermark_type"]:checked').value;
        const position = document.getElementById('position').value;
        const padding = document.getElementById('padding').value + 'px';
        
        // Reset preview
        preview.innerHTML = '';
        preview.style.top = '';
        preview.style.right = '';
        preview.style.bottom = '';
        preview.style.left = '';
        preview.style.transform = '';
        preview.style.padding = padding;
        
        // Position the watermark
        switch (position) {
            case 'top-left':
                preview.style.top = '0';
                preview.style.left = '0';
                break;
            case 'top-right':
                preview.style.top = '0';
                preview.style.right = '0';
                break;
            case 'bottom-left':
                preview.style.bottom = '0';
                preview.style.left = '0';
                break;
            case 'bottom-right':
                preview.style.bottom = '0';
                preview.style.right = '0';
                break;
            case 'center':
                preview.style.top = '50%';
                preview.style.left = '50%';
                preview.style.transform = 'translate(-50%, -50%)';
                preview.style.padding = '0';
                break;
            case 'full':
                preview.style.top = '0';
                preview.style.left = '0';
                preview.style.right = '0';
                preview.style.bottom = '0';
                preview.style.display = 'flex';
                preview.style.flexWrap = 'wrap';
                preview.style.alignItems = 'center';
                preview.style.justifyContent = 'center';
                break;
        }
        
        if (type === 'text') {
            const text = document.getElementById('text_content').value || 'WallPix.Top';
            const font = document.getElementById('text_font').value;
            const size = document.getElementById('text_size').value + 'px';
            const color = document.getElementById('text_color').value;
            const opacity = document.getElementById('text_opacity').value;
            
            const textElement = document.createElement('span');
            textElement.textContent = text;
            textElement.style.fontFamily = font;
            textElement.style.fontSize = size;
            textElement.style.color = color;
            textElement.style.opacity = opacity;
            
            if (position === 'full') {
                // Create multiple text elements for tiled effect
                for (let i = 0; i < 9; i++) {
                    const clone = textElement.cloneNode(true);
                    clone.style.margin = '10px';
                    clone.style.transform = 'rotate(-30deg)';
                    preview.appendChild(clone);
                }
            } else {
                preview.appendChild(textElement);
            }
            
        } else if (type === 'image') {
            <?php if ($watermarkSettings['image_path']): ?>
                const opacity = document.getElementById('image_opacity').value;
                
                const imgElement = document.createElement('img');
                imgElement.src = '<?php echo $watermarkSettings['image_path']; ?>';
                imgElement.style.opacity = opacity;
                imgElement.style.maxHeight = position === 'full' ? '40px' : '60px';
                
                if (position === 'full') {
                    // Create multiple image elements for tiled effect
                    for (let i = 0; i < 9; i++) {
                        const clone = imgElement.cloneNode(true);
                        clone.style.margin = '10px';
                        preview.appendChild(clone);
                    }
                } else {
                    preview.appendChild(imgElement);
                }
            <?php else: ?>
                const placeholderText = document.createElement('span');
                placeholderText.textContent = 'No image uploaded';
                placeholderText.style.color = '#ffffff';
                preview.appendChild(placeholderText);
            <?php endif; ?>
        }
    }
    
    // Initialize preview on page load
    updateWatermarkPreview();
    
    // Update preview when settings change
    document.querySelectorAll('input, select').forEach(input => {
        input.addEventListener('change', updateWatermarkPreview);
        input.addEventListener('input', updateWatermarkPreview);
    });
    
    // Test watermark button
    const testWatermarkBtn = document.getElementById('testWatermarkBtn');
    if (testWatermarkBtn) {
        testWatermarkBtn.addEventListener('click', function() {
            // In a production system, this would open a modal with image selection
            // and preview with applied watermark
            alert('This feature would allow testing the watermark on sample images.\nImplementation would require server-side processing.');
        });
    }
});
</script>

<?php
// Include admin footer
require_once __DIR__ . '/../../theme/admin/footer.php';
?>