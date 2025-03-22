<?php
/**
 * Media Colors Management
 *
 * @package WallPix
 * @version 1.0.0
 */

// Include the Composer autoload file
require_once '../../vendor/autoload.php';

// Load environment variables from the .env file
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../../');
$dotenv->load();

// Set page title
$pageTitle = "Media Colors Management";

// Include header and sidebar
require_once '../../theme/admin/header.php';
require_once '../../theme/admin/slidbar.php';

// Initialize message variables
$successMessage = '';
$errorMessage = '';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check action type
    if (isset($_POST['action'])) {
        // Connect to database
        $db = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
        if ($db->connect_error) {
            die("Connection failed: " . $db->connect_error);
        }

        switch ($_POST['action']) {
            case 'add_color':
                // Add new color
                if (isset($_POST['color_name']) && !empty($_POST['color_name']) && 
                    isset($_POST['hex_code']) && !empty($_POST['hex_code'])) {
                    
                    $color_name = trim($db->real_escape_string($_POST['color_name']));
                    $hex_code = trim($db->real_escape_string($_ENV['hex_code']));
                    
                    // Validate hex code format
                    if (!preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $hex_code)) {
                        $errorMessage = "Invalid hex code format! Use format #RRGGBB";
                    } else {
                        // Check if color name already exists
                        $checkQuery = "SELECT id FROM colors WHERE color_name = '$color_name'";
                        $checkResult = $db->query($checkQuery);
                        
                        if ($checkResult && $checkResult->num_rows > 0) {
                            $errorMessage = "Color name '$color_name' already exists!";
                        } else {
                            $query = "INSERT INTO colors (color_name, hex_code) VALUES ('$color_name', '$hex_code')";
                            
                            if ($db->query($query)) {
                                $successMessage = "Color added successfully!";
                            } else {
                                $errorMessage = "Failed to add color: " . $db->error;
                            }
                        }
                    }
                } else {
                    $errorMessage = "Color name and hex code are required!";
                }
                break;

            case 'edit_color':
                // Edit existing color
                if (isset($_POST['id']) && is_numeric($_POST['id']) && 
                    isset($_POST['color_name']) && !empty($_POST['color_name']) && 
                    isset($_POST['hex_code']) && !empty($_POST['hex_code'])) {
                    
                    $id = (int) $_POST['id'];
                    $color_name = trim($db->real_escape_string($_POST['color_name']));
                    $hex_code = trim($db->real_escape_string($_POST['hex_code']));
                    
                    // Validate hex code format
                    if (!preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $hex_code)) {
                        $errorMessage = "Invalid hex code format! Use format #RRGGBB";
                    } else {
                        // Check if color name already exists for another ID
                        $checkQuery = "SELECT id FROM colors WHERE color_name = '$color_name' AND id != $id";
                        $checkResult = $db->query($checkQuery);
                        
                        if ($checkResult && $checkResult->num_rows > 0) {
                            $errorMessage = "Color name '$color_name' already exists!";
                        } else {
                            $query = "UPDATE colors SET color_name = '$color_name', hex_code = '$hex_code' WHERE id = $id";
                            
                            if ($db->query($query)) {
                                $successMessage = "Color updated successfully!";
                            } else {
                                $errorMessage = "Failed to update color: " . $db->error;
                            }
                        }
                    }
                } else {
                    $errorMessage = "Invalid color data!";
                }
                break;

            case 'delete_color':
                // Delete color
                if (isset($_POST['id']) && is_numeric($_POST['id'])) {
                    $id = (int) $_POST['id'];
                    
                    // Check if the color is being used by media_colors
                    $checkQuery = "SELECT COUNT(*) as count FROM media_colors WHERE color_id = $id";
                    $checkResult = $db->query($checkQuery);
                    
                    if ($checkResult) {
                        $row = $checkResult->fetch_assoc();
                        if ($row['count'] > 0) {
                            $errorMessage = "This color is being used by " . $row['count'] . " media items. Please reassign them before deleting.";
                            break;
                        }
                    }
                    
                    $query = "DELETE FROM colors WHERE id = $id";
                    
                    if ($db->query($query)) {
                        $successMessage = "Color deleted successfully!";
                    } else {
                        $errorMessage = "Failed to delete color: " . $db->error;
                    }
                } else {
                    $errorMessage = "Invalid color ID!";
                }
                break;
                
            case 'update_media_color':
                // Update media color
                if (isset($_POST['media_id']) && is_numeric($_POST['media_id']) && 
                    isset($_POST['primary_color']) && !empty($_POST['primary_color']) && 
                    isset($_POST['secondary_color']) && !empty($_POST['secondary_color'])) {
                    
                    $media_id = (int) $_POST['media_id'];
                    $primary_color = trim($db->real_escape_string($_POST['primary_color']));
                    $secondary_color = trim($db->real_escape_string($_POST['secondary_color']));
                    $is_dark = isset($_POST['is_dark']) ? 1 : 0;
                    $color_id = isset($_POST['color_id']) && !empty($_POST['color_id']) ? (int) $_POST['color_id'] : null;
                    
                    // Validate hex code format
                    if (!preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $primary_color) || 
                        !preg_match('/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', $secondary_color)) {
                        $errorMessage = "Invalid hex code format! Use format #RRGGBB";
                    } else {
                        // Check if media_color record exists
                        $checkQuery = "SELECT id FROM media_colors WHERE media_id = $media_id";
                        $checkResult = $db->query($checkQuery);
                        
                        if ($checkResult && $checkResult->num_rows > 0) {
                            // Update existing record
                            $row = $checkResult->fetch_assoc();
                            $colorId = $row['id'];
                            
                            $updateQuery = "UPDATE media_colors SET 
                                primary_color = '$primary_color',
                                secondary_color = '$secondary_color',
                                is_dark = $is_dark,
                                color_id = " . ($color_id ? $color_id : "NULL") . ",
                                updated_at = NOW()
                                WHERE id = $colorId";
                                
                            if ($db->query($updateQuery)) {
                                $successMessage = "Media color updated successfully!";
                            } else {
                                $errorMessage = "Failed to update media color: " . $db->error;
                            }
                        } else {
                            // Insert new record
                            $insertQuery = "INSERT INTO media_colors (media_id, primary_color, secondary_color, is_dark, color_id)
                                VALUES ($media_id, '$primary_color', '$secondary_color', $is_dark, " . ($color_id ? $color_id : "NULL") . ")";
                                
                            if ($db->query($insertQuery)) {
                                $successMessage = "Media color added successfully!";
                            } else {
                                $errorMessage = "Failed to add media color: " . $db->error;
                            }
                        }
                    }
                } else {
                    $errorMessage = "Media ID, primary color and secondary color are required!";
                }
                break;
                
            case 'delete_media_color':
                // Delete media color association
                if (isset($_POST['id']) && is_numeric($_POST['id'])) {
                    $id = (int) $_POST['id'];
                    
                    $query = "DELETE FROM media_colors WHERE id = $id";
                    
                    if ($db->query($query)) {
                        $successMessage = "Media color association deleted successfully!";
                    } else {
                        $errorMessage = "Failed to delete media color association: " . $db->error;
                    }
                } else {
                    $errorMessage = "Invalid media color ID!";
                }
                break;
        }

        $db->close();
    }
}

// Fetch all colors
$db = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$query = "SELECT * FROM colors ORDER BY id ASC";
$result = $db->query($query);
$colors = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $colors[] = $row;
    }
    $result->free();
}

// Fetch all media with colors
$query = "SELECT mc.id, mc.media_id, m.title, mc.primary_color, mc.secondary_color, mc.is_dark, c.color_name, 
          mc.created_at, mc.updated_at, c.id as color_id
          FROM media_colors mc
          LEFT JOIN media m ON mc.media_id = m.id
          LEFT JOIN colors c ON mc.color_id = c.id
          ORDER BY mc.id DESC";
$result = $db->query($query);
$media_colors = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $media_colors[] = $row;
    }
    $result->free();
}

// Fetch all media for dropdown
$query = "SELECT id, title FROM media ORDER BY id DESC";
$result = $db->query($query);
$all_media = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $all_media[] = $row;
    }
    $result->free();
}

// Get color types for dropdown
$query = "SELECT id, name, description FROM media_color_types ORDER BY id ASC";
$result = $db->query($query);
$color_types = [];

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $color_types[] = $row;
    }
    $result->free();
}

$db->close();

// Current date and time info as provided
$currentDateTime = '2025-03-21 17:45:01'; // UTC
$currentUser = 'mahranalsarminy';
?>

<div class="content-wrapper p-4 sm:ml-64">
    <div class="p-4 border-2 border-gray-200 rounded-lg dark:border-gray-700 mt-14">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-white">
                <i class="fas fa-palette mr-2"></i> Media Colors Management
            </h1>
            <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                <i class="fas fa-clock mr-1"></i>
                <span><?php echo htmlspecialchars($currentDateTime); ?> (UTC)</span>
            </div>
        </div>

        <?php if ($successMessage): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                <p><?php echo htmlspecialchars($successMessage); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                <p><?php echo htmlspecialchars($errorMessage); ?></p>
            </div>
        <?php endif; ?>

        <!-- Tabs -->
        <div class="mb-4 border-b border-gray-200 dark:border-gray-700">
            <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="colorTabs" role="tablist">
                <li class="mr-2" role="presentation">
                    <button class="inline-block p-4 border-b-2 rounded-t-lg active" id="colors-tab" data-tab="colors-content" type="button" role="tab" aria-selected="true">
                        Color Management
                    </button>
                </li>
                <li class="mr-2" role="presentation">
                    <button class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300 dark:hover:text-gray-300" id="media-colors-tab" data-tab="media-colors-content" type="button" role="tab" aria-selected="false">
                        Media Color Assignments
                    </button>
                </li>
            </ul>
        </div>
        
        <!-- Colors Management Tab Content -->
        <div id="colors-content" class="tab-content">
            <!-- Add Color Form -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Add New Color</h2>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="space-y-4">
                    <input type="hidden" name="action" value="add_color">
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="color_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Color Name</label>
                            <input type="text" id="color_name" name="color_name" required 
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                placeholder="e.g. Royal Blue">
                        </div>
                        
                        <div>
                            <label for="hex_code" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hex Code</label>
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <input type="text" id="hex_code" name="hex_code" required
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    placeholder="#4169E1" pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$">
                                <input type="color" id="color_picker" 
                                    class="ml-2 h-full px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500" 
                                    onchange="document.getElementById('hex_code').value = this.value.toUpperCase();">
                            </div>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Format: #RRGGBB (e.g. #FF0000 for red)</p>
                        </div>
                        
                        <div class="flex items-end">
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <i class="fas fa-plus mr-2"></i>Add Color
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Color List -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Color List</h2>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Color Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Hex Code</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Color Preview</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Created At</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            <?php if (empty($colors)): ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No colors found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($colors as $color): ?>
                                    <tr id="color-row-<?php echo $color['id']; ?>" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($color['id']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($color['color_name']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white font-mono"><?php echo htmlspecialchars($color['hex_code']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="w-12 h-6 rounded" style="background-color: <?php echo htmlspecialchars($color['hex_code']); ?>"></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400"><?php echo htmlspecialchars($color['created_at']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button type="button" onclick="editColor(<?php echo $color['id']; ?>, '<?php echo addslashes($color['color_name']); ?>', '<?php echo addslashes($color['hex_code']); ?>')" 
                                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button type="button" onclick="confirmDeleteColor(<?php echo $color['id']; ?>, '<?php echo addslashes($color['color_name']); ?>')" 
                                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Media Colors Tab Content -->
        <div id="media-colors-content" class="tab-content hidden">
            <!-- Assign Color to Media Form -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 mb-6">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Assign/Edit Media Colors</h2>
                
                <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="space-y-4">
                    <input type="hidden" name="action" value="update_media_color">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label for="media_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Select Media</label>
                            <select id="media_id" name="media_id" required 
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">-- Select Media --</option>
                                <?php foreach ($all_media as $media): ?>
                                    <option value="<?php echo $media['id']; ?>"><?php echo htmlspecialchars($media['id'] . ' - ' . $media['title']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="color_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Select Predefined Color (Optional)</label>
                            <select id="color_id" name="color_id" 
                                class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                <option value="">-- None --</option>
                                <?php foreach ($colors as $color): ?>
                                    <option value="<?php echo $color['id']; ?>" data-hex="<?php echo $color['hex_code']; ?>"><?php echo htmlspecialchars($color['color_name'] . ' (' . $color['hex_code'] . ')'); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label for="primary_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Primary Color</label>
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <input type="text" id="primary_color" name="primary_color" required
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    placeholder="#4169E1" pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$" value="#FFFFFF">
                                <input type="color" id="primary_color_picker" value="#FFFFFF"
                                    class="ml-2 h-full px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500" 
                                    onchange="document.getElementById('primary_color').value = this.value.toUpperCase();">
                            </div>
                        </div>
                        
                        <div>
                            <label for="secondary_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Secondary Color</label>
                            <div class="mt-1 flex rounded-md shadow-sm">
                                <input type="text" id="secondary_color" name="secondary_color" required
                                    class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                    placeholder="#000000" pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$" value="#000000">
                                <input type="color" id="secondary_color_picker" value="#000000"
                                    class="ml-2 h-full px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500" 
                                    onchange="document.getElementById('secondary_color').value = this.value.toUpperCase();">
                            </div>
                        </div>
                        
                        <div class="flex flex-col">
                            <div class="flex items-center h-full">
                                <label class="inline-flex items-center mt-6">
                                    <input type="checkbox" name="is_dark" class="form-checkbox h-5 w-5 text-blue-600 dark:text-blue-500">
                                    <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Is Dark Theme</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div>
                        <div class="flex items-center justify-between">
                            <div class="color-preview flex items-center space-x-2">
                                <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Preview: </span>
                                <div id="preview-box" class="w-20 h-10 rounded flex items-center justify-center text-xs font-semibold" style="background-color: #FFFFFF; color: #000000;">
                                    <span>Sample</span>
                                </div>
                            </div>
                            
                            <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                                <i class="fas fa-save mr-2"></i>Save Media Colors
                            </button>
                        </div>
                    </div>
                </form>
            </div>
            
            <!-- Media Colors List -->
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4">
                <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Media Colors List</h2>
                
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">ID</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Media</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Primary Color</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Secondary Color</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Preview</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Color Name</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Is Dark</th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Updated At</th>
                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 dark:bg-gray-800 dark:divide-gray-700">
                            <?php if (empty($media_colors)): ?>
                                <tr>
                                    <td colspan="9" class="px-6 py-4 text-center text-sm text-gray-500 dark:text-gray-400">No media colors found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($media_colors as $mc): ?>
                                    <tr id="media-color-row-<?php echo $mc['id']; ?>" class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white"><?php echo htmlspecialchars($mc['id']); ?></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            <?php echo htmlspecialchars($mc['media_id'] . ' - ' . $mc['title']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="w-6 h-6 rounded mr-2" style="background-color: <?php echo htmlspecialchars($mc['primary_color']); ?>"></div>
                                                <span class="text-sm font-mono text-gray-900 dark:text-white"><?php echo htmlspecialchars($mc['primary_color']); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <div class="w-6 h-6 rounded mr-2" style="background-color: <?php echo htmlspecialchars($mc['secondary_color']); ?>"></div>
                                                <span class="text-sm font-mono text-gray-900 dark:text-white"><?php echo htmlspecialchars($mc['secondary_color']); ?></span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="w-12 h-8 rounded flex items-center justify-center text-xs" 
                                                 style="background-color: <?php echo htmlspecialchars($mc['primary_color']); ?>; color: <?php echo htmlspecialchars($mc['secondary_color']); ?>">
                                                <span>Aa</span>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">
                                            <?php echo $mc['color_name'] ? htmlspecialchars($mc['color_name']) : '<span class="text-gray-500 dark:text-gray-400">None</span>'; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <?php if ($mc['is_dark']): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-800 text-white">
                                                    <i class="fas fa-moon mr-1"></i> Dark
                                                </span>
                                            <?php else: ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                    <i class="fas fa-sun mr-1"></i> Light
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                            <?php echo $mc['updated_at'] ? htmlspecialchars($mc['updated_at']) : htmlspecialchars($mc['created_at']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <button type="button" 
                                                    onclick="editMediaColor(<?php echo $mc['id']; ?>, <?php echo $mc['media_id']; ?>, '<?php echo addslashes($mc['primary_color']); ?>', '<?php echo addslashes($mc['secondary_color']); ?>', <?php echo $mc['is_dark']; ?>, <?php echo $mc['color_id'] ? $mc['color_id'] : 'null'; ?>)" 
                                                    class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300 mr-3">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <button type="button" 
                                                    onclick="confirmDeleteMediaColor(<?php echo $mc['id']; ?>, '<?php echo addslashes($mc['title']); ?>')" 
                                                    class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300">
                                                <i class="fas fa-trash-alt"></i> Delete
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Color Modal -->
<div id="editColorModal" class="fixed z-50 inset-0 hidden overflow-y-auto" aria-modal="true" role="dialog">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" id="colorModalOverlay"></div>
        
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="hidden" name="action" value="edit_color">
                <input type="hidden" name="id" id="editColorId">
                
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-blue-100 dark:bg-blue-900 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-palette text-blue-600 dark:text-blue-300"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                Edit Color
                            </h3>
                            <div class="mt-2 space-y-4">
                                <div>
                                    <label for="editColorName" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Color Name</label>
                                    <input type="text" name="color_name" id="editColorName" required 
                                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white">
                                </div>
                                <div>
                                    <label for="editHexCode" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Hex Code</label>
                                    <div class="mt-1 flex rounded-md shadow-sm">
                                        <input type="text" name="hex_code" id="editHexCode" required 
                                               class="block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                                               pattern="^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$">
                                        <input type="color" id="editColorPicker" 
                                               class="ml-2 h-full px-2 py-1 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500" 
                                               onchange="document.getElementById('editHexCode').value = this.value.toUpperCase();">
                                    </div>
                                </div>
                                <div class="mt-4">
                                    <div class="flex items-center space-x-2">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Preview: </span>
                                        <div id="edit-preview-box" class="w-20 h-10 rounded"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Save Changes
                    </button>
                    <button type="button" onclick="closeColorModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Color Confirmation Modal -->
<div id="deleteColorModal" class="fixed z-50 inset-0 hidden overflow-y-auto" aria-modal="true" role="dialog">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" id="deleteColorModalOverlay"></div>
        
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="hidden" name="action" value="delete_color">
                <input type="hidden" name="id" id="deleteColorId">
                
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-300"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                Delete Color
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Are you sure you want to delete this color? This action cannot be undone.
                                </p>
                                <p class="text-sm font-semibold mt-2 text-gray-800 dark:text-gray-200" id="deleteColorName">
                                    Color name will appear here
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Delete
                    </button>
                    <button type="button" onclick="closeDeleteColorModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Media Color Confirmation Modal -->
<div id="deleteMediaColorModal" class="fixed z-50 inset-0 hidden overflow-y-auto" aria-modal="true" role="dialog">
    <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" id="deleteMediaColorModalOverlay"></div>
        
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>">
                <input type="hidden" name="action" value="delete_media_color">
                <input type="hidden" name="id" id="deleteMediaColorId">
                
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 dark:bg-red-900 sm:mx-0 sm:h-10 sm:w-10">
                            <i class="fas fa-exclamation-triangle text-red-600 dark:text-red-300"></i>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white" id="modal-title">
                                Delete Media Color
                            </h3>
                            <div class="mt-2">
                                <p class="text-sm text-gray-500 dark:text-gray-400">
                                    Are you sure you want to delete the color settings for this media? This action cannot be undone.
                                </p>
                                <p class="text-sm font-semibold mt-2 text-gray-800 dark:text-gray-200" id="deleteMediaColorName">
                                    Media title will appear here
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Delete
                    </button>
                    <button type="button" onclick="closeDeleteMediaColorModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm dark:bg-gray-600 dark:text-white dark:border-gray-600 dark:hover:bg-gray-700">
                        Cancel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- JavaScript for modals and interactions -->
<script>
    // Tab Functionality
    document.addEventListener('DOMContentLoaded', function() {
        const tabs = document.querySelectorAll('[data-tab]');
        const tabContents = document.querySelectorAll('.tab-content');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', function() {
                // Remove active class from all tabs
                tabs.forEach(t => {
                    t.classList.remove('text-blue-600', 'dark:text-blue-500', 'border-blue-600', 'dark:border-blue-500');
                    t.classList.add('border-transparent');
                    t.setAttribute('aria-selected', 'false');
                });
                
                // Add active class to current tab
                tab.classList.add('text-blue-600', 'dark:text-blue-500', 'border-blue-600', 'dark:border-blue-500');
                tab.classList.remove('border-transparent');
                tab.setAttribute('aria-selected', 'true');
                
                // Hide all tab contents
                tabContents.forEach(content => {
                    content.classList.add('hidden');
                });
                
                // Show selected tab content
                const tabContentId = tab.getAttribute('data-tab');
                document.getElementById(tabContentId).classList.remove('hidden');
            });
        });
        
        // Color preview functionality
        const primaryColorInput = document.getElementById('primary_color');
        const secondaryColorInput = document.getElementById('secondary_color');
        const primaryColorPicker = document.getElementById('primary_color_picker');
        const secondaryColorPicker = document.getElementById('secondary_color_picker');
        const previewBox = document.getElementById('preview-box');
        
        function updatePreview() {
            const primaryColor = primaryColorInput.value;
            const secondaryColor = secondaryColorInput.value;
            
            previewBox.style.backgroundColor = primaryColor;
            previewBox.style.color = secondaryColor;
        }
        
        primaryColorInput.addEventListener('input', updatePreview);
        secondaryColorInput.addEventListener('input', updatePreview);
        primaryColorPicker.addEventListener('input', function() {
            primaryColorInput.value = this.value.toUpperCase();
            updatePreview();
        });
        secondaryColorPicker.addEventListener('input', function() {
            secondaryColorInput.value = this.value.toUpperCase();
            updatePreview();
        });
        
        // Color ID dropdown functionality
        const colorIdSelect = document.getElementById('color_id');
        colorIdSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            if (selectedOption.value) {
                const hexCode = selectedOption.getAttribute('data-hex');
                if (hexCode) {
                    primaryColorInput.value = hexCode;
                    primaryColorPicker.value = hexCode;
                    updatePreview();
                }
            }
        });
    });
    
    // Color Management Functions
    function editColor(id, colorName, hexCode) {
        document.getElementById('editColorId').value = id;
        document.getElementById('editColorName').value = colorName;
        document.getElementById('editHexCode').value = hexCode;
        document.getElementById('editColorPicker').value = hexCode;
        document.getElementById('edit-preview-box').style.backgroundColor = hexCode;
        document.getElementById('editColorModal').classList.remove('hidden');
    }

    function closeColorModal() {
        document.getElementById('editColorModal').classList.add('hidden');
    }

    function confirmDeleteColor(id, colorName) {
        document.getElementById('deleteColorId').value = id;
        document.getElementById('deleteColorName').textContent = colorName;
        document.getElementById('deleteColorModal').classList.remove('hidden');
    }

    function closeDeleteColorModal() {
        document.getElementById('deleteColorModal').classList.add('hidden');
    }
    
    // Media Color Management Functions
    function editMediaColor(id, mediaId, primaryColor, secondaryColor, isDark, colorId) {
        // Set form values for edit mode
        document.getElementById('media_id').value = mediaId;
        document.getElementById('primary_color').value = primaryColor;
        document.getElementById('secondary_color').value = secondaryColor;
        document.getElementById('primary_color_picker').value = primaryColor;
        document.getElementById('secondary_color_picker').value = secondaryColor;
        
        if (colorId) {
            document.getElementById('color_id').value = colorId;
        } else {
            document.getElementById('color_id').value = '';
        }
        
        document.querySelector('input[name="is_dark"]').checked = isDark === 1;
        
        // Update preview
        const previewBox = document.getElementById('preview-box');
        previewBox.style.backgroundColor = primaryColor;
        previewBox.style.color = secondaryColor;
        
        // Scroll to the form
        document.querySelector('[data-tab="media-colors-content"]').click();
        document.querySelector('.bg-white.dark\\:bg-gray-800.rounded-lg.shadow-md.p-4.mb-6').scrollIntoView({ behavior: 'smooth' });
    }

    function confirmDeleteMediaColor(id, mediaTitle) {
        document.getElementById('deleteMediaColorId').value = id;
        document.getElementById('deleteMediaColorName').textContent = mediaTitle;
        document.getElementById('deleteMediaColorModal').classList.remove('hidden');
    }

    function closeDeleteMediaColorModal() {
        document.getElementById('deleteMediaColorModal').classList.add('hidden');
    }

    // Close modals when clicking on overlay
    document.getElementById('colorModalOverlay').addEventListener('click', closeColorModal);
    document.getElementById('deleteColorModalOverlay').addEventListener('click', closeDeleteColorModal);
    document.getElementById('deleteMediaColorModalOverlay').addEventListener('click', closeDeleteMediaColorModal);

    // Keyboard event listener for Escape key
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeColorModal();
            closeDeleteColorModal();
            closeDeleteMediaColorModal();
        }
    });
    
    // Initialize color picker for edit hex code input
    document.getElementById('editHexCode').addEventListener('input', function() {
        document.getElementById('editColorPicker').value = this.value;
        document.getElementById('edit-preview-box').style.backgroundColor = this.value;
    });
    
    document.getElementById('editColorPicker').addEventListener('input', function() {
        document.getElementById('editHexCode').value = this.value.toUpperCase();
        document.getElementById('edit-preview-box').style.backgroundColor = this.value;
    });
</script>

<!-- Include footer -->
<?php require_once '../../theme/admin/footer.php'; ?>