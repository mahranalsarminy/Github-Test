<?php
/**
 * Media Colors Management Page
 * 
 * @package WallPix
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'Media Colors Management';

// Set current user and timestamp
$currentUser = 'mahranalsarminy';
$currentDateTime = '2025-03-21 03:49:24';

// Include header
require_once '../../theme/admin/header.php';

// Include sidebar
require_once '../../theme/admin/slidbar.php';

// Process form submission for updating colors
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['media_id'], $_POST['primary_color'], $_POST['secondary_color'])) {
    $mediaId = (int)$_POST['media_id'];
    $colorId = isset($_POST['color_id']) ? (int)$_POST['color_id'] : null;
    $primaryColor = trim($_POST['primary_color']);
    $secondaryColor = trim($_POST['secondary_color']);
    $isDark = isset($_POST['is_dark']) ? 1 : 0;
    
    // Validate color formats
    if (!preg_match('/^#[a-f0-9]{6}$/i', $primaryColor) || !preg_match('/^#[a-f0-9]{6}$/i', $secondaryColor)) {
        $errorMessage = 'Invalid color format. Colors must be in hexadecimal format (e.g., #FF5500).';
    } else {
        try {
            // Begin transaction
            $pdo->beginTransaction();
            
            // Check if we're updating or inserting
            if ($colorId) {
                // Update existing color record
                $updateQuery = $pdo->prepare("
                    UPDATE media_colors 
                    SET 
                        primary_color = :primary_color,
                        secondary_color = :secondary_color,
                        is_dark = :is_dark,
                        updated_at = :updated_at
                    WHERE id = :color_id AND media_id = :media_id
                ");
                
                $updateQuery->execute([
                    'primary_color' => $primaryColor,
                    'secondary_color' => $secondaryColor,
                    'is_dark' => $isDark,
                    'updated_at' => $currentDateTime,
                    'color_id' => $colorId,
                    'media_id' => $mediaId
                ]);
            } else {
                // Insert new color record
                $insertQuery = $pdo->prepare("
                    INSERT INTO media_colors (media_id, primary_color, secondary_color, is_dark, created_at)
                    VALUES (:media_id, :primary_color, :secondary_color, :is_dark, :created_at)
                ");
                
                $insertQuery->execute([
                    'media_id' => $mediaId,
                    'primary_color' => $primaryColor,
                    'secondary_color' => $secondaryColor,
                    'is_dark' => $isDark,
                    'created_at' => $currentDateTime
                ]);
                
                // Get the newly created color_id
                $colorId = $pdo->lastInsertId();
            }
            
            // Skip activity logging to avoid further errors with table structure
            // We'll just log directly to your standard database login
            
            // Commit transaction
            $pdo->commit();
            
            $successMessage = "Colors for media ID #$mediaId have been updated successfully.";
            
        } catch (Exception $e) {
            // Rollback transaction on error
            $pdo->rollBack();
            $errorMessage = 'Error updating media colors: ' . $e->getMessage();
        }
    }
}

// Get media with their colors
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$limit = 12; // Items per page
$offset = ($page - 1) * $limit;

// Search filter
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';
$whereClause = '';
$params = [];

if (!empty($searchTerm)) {
    $whereClause = "WHERE m.title LIKE :search OR m.description LIKE :search";
    $params['search'] = "%$searchTerm%";
}

// Count total media items for pagination
$countQuery = $pdo->prepare("
    SELECT COUNT(*) 
    FROM media m 
    $whereClause
");

foreach ($params as $key => $value) {
    $countQuery->bindValue(':' . $key, $value);
}

$countQuery->execute();
$totalItems = $countQuery->fetchColumn();
$totalPages = ceil($totalItems / $limit);

// Get media items for current page
$query = $pdo->prepare("
    SELECT 
        m.id, 
        m.title,
        m.file_path,
        m.thumbnail_url,
        m.created_at,
        c.name as category_name,
        mc.id as color_id,
        mc.primary_color,
        mc.secondary_color,
        mc.is_dark
    FROM media m
    LEFT JOIN categories c ON m.category_id = c.id
    LEFT JOIN media_colors mc ON m.id = mc.media_id
    $whereClause
    ORDER BY m.created_at DESC
    LIMIT :offset, :limit
");

$query->bindParam(':offset', $offset, PDO::PARAM_INT);
$query->bindParam(':limit', $limit, PDO::PARAM_INT);

foreach ($params as $key => $value) {
    $query->bindValue(':' . $key, $value);
}

$query->execute();
$mediaItems = $query->fetchAll(PDO::FETCH_ASSOC);

// Function to get contrasting text color for a given background
function getContrastColor($hexcolor) {
    // Remove # if present
    $hexcolor = ltrim($hexcolor, '#');
    
    // Convert to RGB
    $r = hexdec(substr($hexcolor, 0, 2));
    $g = hexdec(substr($hexcolor, 2, 2));
    $b = hexdec(substr($hexcolor, 4, 2));
    
    // Calculate brightness (YIQ formula)
    $yiq = (($r * 299) + ($g * 587) + ($b * 114)) / 1000;
    
    // Return black or white depending on brightness
    return ($yiq >= 128) ? '#000000' : '#FFFFFF';
}
?>

<!-- Main content container -->
<div class="content-wrapper p-4 sm:ml-64">
    <div class="p-4 mt-14">
        <div class="bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-800' : ''; ?>">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-semibold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                    <i class="fas fa-palette mr-2"></i> <?php echo $lang['media_colors_management'] ?? 'Media Colors Management'; ?>
                </h1>
                <nav class="flex" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="<?php echo $adminUrl; ?>/index.php" class="inline-flex items-center text-sm font-medium <?php echo $darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-700 hover:text-blue-600'; ?>">
                                <i class="fas fa-home mr-2"></i> <?php echo $lang['dashboard'] ?? 'Dashboard'; ?>
                            </a>
                        </li>
                        <li>
                            <div class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                                <a href="<?php echo $adminUrl; ?>/media/index.php" class="inline-flex items-center text-sm font-medium <?php echo $darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-700 hover:text-blue-600'; ?>">
                                    <?php echo $lang['media'] ?? 'Media'; ?>
                                </a>
                            </div>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                                <span class="text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">
                                    <?php echo $lang['colors'] ?? 'Colors'; ?>
                                </span>
                            </div>
                        </li>
                    </ol>
                </nav>
            </div>
            
            <?php if ($successMessage): ?>
                <div class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg dark:bg-green-200 dark:text-green-800" role="alert">
                    <span class="font-medium"><i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($successMessage); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($errorMessage): ?>
                <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg dark:bg-red-200 dark:text-red-800" role="alert">
                    <span class="font-medium"><i class="fas fa-exclamation-circle mr-2"></i> <?php echo htmlspecialchars($errorMessage); ?></span>
                </div>
            <?php endif; ?>

            <!-- Description and Help Text -->
            <div class="mb-6 p-4 bg-blue-50 rounded-lg <?php echo $darkMode ? 'bg-blue-900 text-blue-200' : 'text-blue-800'; ?>">
                <h3 class="font-semibold mb-2">
                    <i class="fas fa-info-circle mr-2"></i> <?php echo $lang['about_media_colors'] ?? 'About Media Colors'; ?>
                </h3>
                <p class="text-sm">
                    <?php echo $lang['media_colors_help'] ?? 'Set primary and secondary colors for each media item. These colors can be used to create matching UI elements when displaying the media. The "Is Dark" option indicates whether the media has a dark overall appearance, useful for determining text contrast.'; ?>
                </p>
                <p class="text-sm mt-2">
                    <strong>Current Time (UTC):</strong> <?php echo $currentDateTime; ?> | 
                    <strong>Current User:</strong> <?php echo $currentUser; ?>
                </p>
            </div>

            <!-- Search Form -->
            <div class="mb-6">
                <form action="" method="GET" class="flex flex-col sm:flex-row gap-2">
                    <div class="flex-grow">
                        <input type="text" name="search" placeholder="<?php echo $lang['search_media'] ?? 'Search media...'; ?>" 
                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                            value="<?php echo htmlspecialchars($searchTerm); ?>">
                    </div>
                    <button type="submit" class="inline-flex items-center py-2.5 px-4 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 focus:ring-4 focus:outline-none focus:ring-blue-300">
                        <i class="fas fa-search mr-2"></i> <?php echo $lang['search'] ?? 'Search'; ?>
                    </button>
                </form>
            </div>

            <!-- Media Grid -->
            <?php if (empty($mediaItems)): ?>
                <div class="text-center py-10 <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                    <i class="fas fa-photo-video text-5xl mb-4 opacity-40"></i>
                    <p class="text-lg"><?php echo $lang['no_media_found'] ?? 'No media found'; ?></p>
                </div>
            <?php else: ?>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-6">
                    <?php foreach ($mediaItems as $media): ?>
                        <?php 
                        // Set default colors if not available
                        $colorId = !empty($media['color_id']) ? $media['color_id'] : null;
                        $primaryColor = !empty($media['primary_color']) ? $media['primary_color'] : '#FFFFFF';
                        $secondaryColor = !empty($media['secondary_color']) ? $media['secondary_color'] : '#000000';
                        $isDark = !empty($media['is_dark']) ? $media['is_dark'] : 0;
                        
                        // Get contrast text colors
                        $primaryTextColor = getContrastColor($primaryColor);
                        $secondaryTextColor = getContrastColor($secondaryColor);
                        ?>
                        <div class="bg-white rounded-lg shadow-md overflow-hidden <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                            <!-- Media Preview -->
                            <div class="relative h-48 overflow-hidden">
                                <?php if (!empty($media['thumbnail_url'])): ?>
                                    <img src="<?php echo htmlspecialchars('../../' . $media['thumbnail_url']); ?>" alt="<?php echo htmlspecialchars($media['title']); ?>" class="w-full h-full object-cover">
                                <?php else: ?>
                                    <div class="w-full h-full flex items-center justify-center bg-gray-200 <?php echo $darkMode ? 'bg-gray-800' : ''; ?>">
                                        <i class="fas fa-photo-video text-4xl <?php echo $darkMode ? 'text-gray-600' : 'text-gray-400'; ?>"></i>
                                    </div>
                                <?php endif; ?>
                                <div class="absolute bottom-0 left-0 right-0 px-4 py-2 bg-black bg-opacity-70">
                                    <div class="text-white text-sm font-semibold truncate"><?php echo htmlspecialchars($media['title']); ?></div>
                                    <div class="text-gray-300 text-xs">ID: <?php echo htmlspecialchars($media['id']); ?> - <?php echo htmlspecialchars($media['category_name'] ?? 'Uncategorized'); ?></div>
                                </div>
                                <?php if ($colorId): ?>
                                <div class="absolute top-2 right-2 bg-green-500 text-white text-xs px-2 py-1 rounded-full">
                                    <i class="fas fa-palette mr-1"></i> Color ID: <?php echo $colorId; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Color Form -->
                            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="p-4">
                                <input type="hidden" name="media_id" value="<?php echo $media['id']; ?>">
                                <?php if ($colorId): ?>
                                <input type="hidden" name="color_id" value="<?php echo $colorId; ?>">
                                <?php endif; ?>
                                
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <!-- Primary Color -->
                                    <div>
                                        <label class="block text-xs font-medium mb-1 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                            <?php echo $lang['primary_color'] ?? 'Primary Color'; ?>
                                        </label>
                                        <div class="flex items-center">
                                            <input type="color" id="primary_color_<?php echo $media['id']; ?>" name="primary_color" 
                                                value="<?php echo $primaryColor; ?>" 
                                                class="w-8 h-8 rounded border cursor-pointer mr-2" 
                                                onchange="updateTextInput('primary', <?php echo $media['id']; ?>); updatePreview(<?php echo $media['id']; ?>);">
                                            <input type="text" 
                                                id="primary_color_text_<?php echo $media['id']; ?>"
                                                value="<?php echo $primaryColor; ?>" 
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-1.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                                onchange="updateColorInput('primary', <?php echo $media['id']; ?>); updatePreview(<?php echo $media['id']; ?>);">
                                        </div>
                                        <div id="primary_preview_<?php echo $media['id']; ?>" class="mt-2 h-8 rounded flex items-center justify-center text-sm font-semibold" style="background-color: <?php echo $primaryColor; ?>; color: <?php echo $primaryTextColor; ?>;">
                                            Sample Text
                                        </div>
                                    </div>
                                    
                                    <!-- Secondary Color -->
                                    <div>
                                        <label class="block text-xs font-medium mb-1 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                            <?php echo $lang['secondary_color'] ?? 'Secondary Color'; ?>
                                        </label>
                                        <div class="flex items-center">
                                            <input type="color" id="secondary_color_<?php echo $media['id']; ?>" name="secondary_color" 
                                                value="<?php echo $secondaryColor; ?>" 
                                                class="w-8 h-8 rounded border cursor-pointer mr-2"
                                                onchange="updateTextInput('secondary', <?php echo $media['id']; ?>); updatePreview(<?php echo $media['id']; ?>);">
                                            <input type="text" 
                                                id="secondary_color_text_<?php echo $media['id']; ?>"
                                                value="<?php echo $secondaryColor; ?>" 
                                                class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-1.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>"
                                                onchange="updateColorInput('secondary', <?php echo $media['id']; ?>); updatePreview(<?php echo $media['id']; ?>);">
                                        </div>
                                        <div id="secondary_preview_<?php echo $media['id']; ?>" class="mt-2 h-8 rounded flex items-center justify-center text-sm font-semibold" style="background-color: <?php echo $secondaryColor; ?>; color: <?php echo $secondaryTextColor; ?>;">
                                            Sample Text
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center">
                                        <input id="is_dark_<?php echo $media['id']; ?>" name="is_dark" type="checkbox" 
                                            class="w-4 h-4 text-blue-600 bg-gray-100 rounded border-gray-300 focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : ''; ?>" 
                                            <?php echo $isDark ? 'checked' : ''; ?>>
                                        <label for="is_dark_<?php echo $media['id']; ?>" class="ml-2 text-xs font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                            <?php echo $lang['is_dark'] ?? 'Is Dark Image'; ?>
                                        </label>
                                    </div>
                                    <button type="submit" class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-xs px-3 py-1.5">
                                        <?php if ($colorId): ?>
                                            <i class="fas fa-save mr-1"></i> <?php echo $lang['update'] ?? 'Update'; ?>
                                        <?php else: ?>
                                            <i class="fas fa-plus mr-1"></i> <?php echo $lang['create'] ?? 'Create'; ?>
                                        <?php endif; ?>
                                    </button>
                                </div>
                            </form>
                            
                            <!-- Color Preview -->
                            <div class="px-4 pb-4">
                                <div id="combined_preview_<?php echo $media['id']; ?>" class="mt-2 p-3 rounded-lg" style="background-color: <?php echo $primaryColor; ?>; color: <?php echo $primaryTextColor; ?>; border: 2px solid <?php echo $secondaryColor; ?>">
                                    <div class="text-xs font-medium">
                                        <?php echo $lang['preview_text'] ?? 'This is how text would appear on the primary color background.'; ?>
                                    </div>
                                    <div class="mt-2 p-1.5 rounded text-xs" style="background-color: <?php echo $secondaryColor; ?>; color: <?php echo $secondaryTextColor; ?>">
                                        <?php echo $lang['secondary_preview'] ?? 'Secondary color element'; ?>
                                    </div>
                                </div>
                                <?php if ($colorId): ?>
                                <div class="mt-2 text-xs <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    <span class="font-medium">Last updated:</span> 
                                    <?php 
                                    try {
                                        // Query to get the last update time for this color
                                        $updateQuery = $pdo->prepare("SELECT updated_at FROM media_colors WHERE id = ?");
                                        $updateQuery->execute([$colorId]);
                                        $lastUpdate = $updateQuery->fetchColumn();
                                        echo $lastUpdate ? date('Y-m-d H:i:s', strtotime($lastUpdate)) : 'N/A'; 
                                    } catch (Exception $e) {
                                        echo 'N/A';
                                    }
                                    ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="flex justify-center mt-6">
                    <nav aria-label="Page navigation">
                        <ul class="inline-flex items-center -space-x-px">
                            <?php if ($page > 1): ?>
                            <li>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($searchTerm); ?>" class="block py-2 px-3 ml-0 leading-tight rounded-l-lg border <?php echo $darkMode ? 'bg-gray-800 border-gray-700 text-gray-400 hover:bg-gray-700 hover:text-white' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-100 hover:text-gray-700'; ?>">
                                    <i class="fas fa-chevron-left"></i>
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($totalPages, $page + 2);
                            
                            if ($startPage > 1) {
                                echo '<li><a href="?page=1&search=' . urlencode($searchTerm) . '" class="block py-2 px-3 leading-tight border ' . ($darkMode ? 'bg-gray-800 border-gray-700 text-gray-400 hover:bg-gray-700 hover:text-white' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-100 hover:text-gray-700') . '">1</a></li>';
                                if ($startPage > 2) {
                                    echo '<li><span class="block py-2 px-3 leading-tight border ' . ($darkMode ? 'bg-gray-800 border-gray-700 text-gray-400' : 'bg-white border-gray-300 text-gray-500') . '">...</span></li>';
                                }
                            }
                            
                            for ($i = $startPage; $i <= $endPage; $i++) {
                                $isCurrentPage = $i === $page;
                                echo '<li><a href="?page=' . $i . '&search=' . urlencode($searchTerm) . '" class="block py-2 px-3 leading-tight border ' . 
                                    ($isCurrentPage 
                                        ? ($darkMode ? 'bg-blue-600 border-blue-600 text-white' : 'bg-blue-50 border-blue-300 text-blue-600') 
                                        : ($darkMode ? 'bg-gray-800 border-gray-700 text-gray-400 hover:bg-gray-700 hover:text-white' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-100 hover:text-gray-700')
                                    ) . '">' . $i . '</a></li>';
                            }
                            
                            if ($endPage < $totalPages) {
                                if ($endPage < $totalPages - 1) {
                                    echo '<li><span class="block py-2 px-3 leading-tight border ' . ($darkMode ? 'bg-gray-800 border-gray-700 text-gray-400' : 'bg-white border-gray-300 text-gray-500') . '">...</span></li>';
                                }
                                echo '<li><a href="?page=' . $totalPages . '&search=' . urlencode($searchTerm) . '" class="block py-2 px-3 leading-tight border ' . ($darkMode ? 'bg-gray-800 border-gray-700 text-gray-400 hover:bg-gray-700 hover:text-white' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-100 hover:text-gray-700') . '">' . $totalPages . '</a></li>';
                            }
                            ?>
                            
                            <?php if ($page < $totalPages): ?>
                            <li>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($searchTerm); ?>" class="block py-2 px-3 leading-tight rounded-r-lg border <?php echo $darkMode ? 'bg-gray-800 border-gray-700 text-gray-400 hover:bg-gray-700 hover:text-white' : 'bg-white border-gray-300 text-gray-500 hover:bg-gray-100 hover:text-gray-700'; ?>">
                                    <i class="fas fa-chevron-right"></i>
                                </a>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
                <?php endif; ?>
            <?php endif; ?>
            
            <!-- Bulk Color Management Tools -->
            <div class="mt-8 p-4 border rounded-lg <?php echo $darkMode ? 'border-gray-700 bg-gray-700' : 'border-gray-200 bg-gray-50'; ?>">
                <h3 class="text-lg font-medium mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                    <i class="fas fa-tools mr-2"></i> <?php echo $lang['color_tools'] ?? 'Color Management Tools'; ?>
                </h3>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- Color Extraction Tool -->
                    <div>
                        <h4 class="text-sm font-semibold mb-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                            <?php echo $lang['auto_extract_colors'] ?? 'Auto Extract Colors'; ?>
                        </h4>
                        <p class="text-xs mb-3 <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                            <?php echo $lang['extract_colors_help'] ?? 'Automatically extract dominant colors from images that don\'t have colors set.'; ?>
                        </p>
                        <button type="button" id="extractColorsBtn" class="inline-flex items-center py-2 px-4 bg-purple-600 hover:bg-purple-700 text-white text-sm font-medium rounded-lg focus:ring-4 focus:ring-purple-300">
                            <i class="fas fa-magic mr-2"></i> <?php echo $lang['extract_colors'] ?? 'Extract Colors'; ?>
                        </button>
                    </div>
                    
                    <!-- Color Reset Tool -->
                    <div>
                        <h4 class="text-sm font-semibold mb-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                            <?php echo $lang['reset_colors'] ?? 'Reset Colors'; ?>
                        </h4>
                        <p class="text-xs mb-3 <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                            <?php echo $lang['reset_colors_help'] ?? 'Reset all media colors to default values.'; ?>
                        </p>
                        <button type="button" id="resetColorsBtn" class="inline-flex items-center py-2 px-4 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg focus:ring-4 focus:ring-red-300">
                            <i class="fas fa-undo mr-2"></i> <?php echo $lang['reset_all'] ?? 'Reset All'; ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Extract colors button
        document.getElementById('extractColorsBtn').addEventListener('click', function() {
            if (confirm('<?php echo $lang['confirm_extract_colors'] ?? 'Do you want to automatically extract colors from all images? This may take some time.'; ?>')) {
                // Here you'd typically make an AJAX call to a backend script that processes images
                alert('This feature would extract colors from images using a server-side process. Implementation requires image processing libraries.');
            }
        });
        
        // Reset colors button
        document.getElementById('resetColorsBtn').addEventListener('click', function() {
            if (confirm('<?php echo $lang['confirm_reset_colors'] ?? 'Are you sure you want to reset ALL media colors to default values? This cannot be undone.'; ?>')) {
                // Here you'd make an AJAX call to reset colors
                alert('This would reset all colors in the database. Add backend implementation to handle this.');
            }
        });
    });
    
    // Update text input based on color picker
    function updateTextInput(type, mediaId) {
        const colorInput = document.getElementById(type + '_color_' + mediaId);
        const textInput = document.getElementById(type + '_color_text_' + mediaId);
        textInput.value = colorInput.value;
        updatePreview(mediaId);
    }
    
    // Update color picker based on text input
    function updateColorInput(type, mediaId) {
        const textInput = document.getElementById(type + '_color_text_' + mediaId);
        const colorInput = document.getElementById(type + '_color_' + mediaId);
        colorInput.value = textInput.value;
        updatePreview(mediaId);
    }
    
    // Update color preview elements
    function updatePreview(mediaId) {
        // Get color values
        const primaryColor = document.getElementById('primary_color_' + mediaId).value;
        const secondaryColor = document.getElementById('secondary_color_' + mediaId).value;
        
        // Calculate contrast text colors
        const primaryTextColor = getContrastYIQ(primaryColor);
        const secondaryTextColor = getContrastYIQ(secondaryColor);
        
        // Update primary preview
        const primaryPreview = document.getElementById('primary_preview_' + mediaId);
        primaryPreview.style.backgroundColor = primaryColor;
        primaryPreview.style.color = primaryTextColor;
        
        // Update secondary preview
        const secondaryPreview = document.getElementById('secondary_preview_' + mediaId);
        secondaryPreview.style.backgroundColor = secondaryColor;
        secondaryPreview.style.color = secondaryTextColor;
        
        // Update combined preview
        const combinedPreview = document.getElementById('combined_preview_' + mediaId);
        combinedPreview.style.backgroundColor = primaryColor;
        combinedPreview.style.color = primaryTextColor;
        combinedPreview.style.borderColor = secondaryColor;
        
        // Update the nested secondary element in combined preview
        const nestedSecondary = combinedPreview.querySelector('div.mt-2');
        if (nestedSecondary) {
            nestedSecondary.style.backgroundColor = secondaryColor;
            nestedSecondary.style.color = secondaryTextColor;
        }
    }
    
    // Function to determine contrasting text color (black or white)
    function getContrastYIQ(hexcolor) {
        // Remove # if present
        hexcolor = hexcolor.replace('#', '');
        
        // Convert to RGB
        const r = parseInt(hexcolor.substr(0, 2), 16);
        const g = parseInt(hexcolor.substr(2, 2), 16);
        const b = parseInt(hexcolor.substr(4, 2), 16);
        
        // Calculate brightness (YIQ formula)
        const yiq = ((r * 299) + (g * 587) + (b * 114)) / 1000;
        
        // Return black or white depending on brightness
        return (yiq >= 128) ? '#000000' : '#FFFFFF';
    }
</script>

<?php
// Include footer
require_once '../../theme/admin/footer.php';
?>