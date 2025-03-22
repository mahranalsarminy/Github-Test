<?php
/**
 * Admin Menu Management Page
 * 
 * @package WallPix
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'Menu Management';

// Include header
require_once '../../theme/admin/header.php';

// Include sidebar
require_once '../../theme/admin/slidbar.php';

// Process form submission
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_menu'])) {
        $name = $_POST['name'] ?? null;

        // Validate required fields
        if (empty($name)) {
            $errorMessage = 'Please fill in the menu name.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO menus (name) VALUES (?)");
                $stmt->execute([$name]);
                
                // Log activity
                $activityQuery = $pdo->prepare(
                    "INSERT INTO activities (user_id, description, created_at)
                    VALUES (:user_id, :description, NOW())"
                );
                
                $activityQuery->execute([
                    'user_id' => $_SESSION['user_id'] ?? null,
                    'description' => 'Added new menu: ' . $name
                ]);
                
                $successMessage = 'Menu added successfully';
            } catch (PDOException $e) {
                error_log("Add menu error: " . $e->getMessage());
                $errorMessage = 'Error adding menu: ' . $e->getMessage();
            }
        }
    }

    if (isset($_POST['delete_menu'])) {
        $id = $_POST['id'];
        try {
            // Get menu name for activity log
            $stmt = $pdo->prepare("SELECT name FROM menus WHERE id = ?");
            $stmt->execute([$id]);
            $menuName = $stmt->fetchColumn();
            
            // Delete menu
            $stmt = $pdo->prepare("DELETE FROM menus WHERE id = ?");
            $stmt->execute([$id]);
            
            // Log activity
            $activityQuery = $pdo->prepare(
                "INSERT INTO activities (user_id, description, created_at)
                VALUES (:user_id, :description, NOW())"
            );
            
            $activityQuery->execute([
                'user_id' => $_SESSION['user_id'] ?? null,
                'description' => 'Deleted menu: ' . $menuName
            ]);
            
            $successMessage = 'Menu deleted successfully';
        } catch (PDOException $e) {
            error_log("Delete menu error: " . $e->getMessage());
            $errorMessage = 'Error deleting menu: ' . $e->getMessage();
        }
    }

    if (isset($_POST['add_menu_item'])) {
        $menu_id = $_POST['menu_id'] ?? null;
        $name = $_POST['name'] ?? null;
        $url = $_POST['url'] ?? null;
        $sort_order = $_POST['sort_order'] ?? 0;

        // Validate required fields
        if (empty($menu_id) || empty($name) || empty($url)) {
            $errorMessage = 'Please fill in all required fields.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO menu_items (menu_id, name, url, sort_order) VALUES (?, ?, ?, ?)");
                $stmt->execute([$menu_id, $name, $url, $sort_order]);
                
                // Log activity
                $activityQuery = $pdo->prepare(
                    "INSERT INTO activities (user_id, description, created_at)
                    VALUES (:user_id, :description, NOW())"
                );
                
                $activityQuery->execute([
                    'user_id' => $_SESSION['user_id'] ?? null,
                    'description' => 'Added menu item: ' . $name . ' to menu ID ' . $menu_id
                ]);
                
                $successMessage = 'Menu item added successfully';
            } catch (PDOException $e) {
                error_log("Add menu item error: " . $e->getMessage());
                $errorMessage = 'Error adding menu item: ' . $e->getMessage();
            }
        }
    }

    if (isset($_POST['delete_menu_item'])) {
        $id = $_POST['id'];
        try {
            // Get menu item info for activity log
            $stmt = $pdo->prepare("SELECT name, menu_id FROM menu_items WHERE id = ?");
            $stmt->execute([$id]);
            $menuItem = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Delete menu item
            $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
            $stmt->execute([$id]);
            
            // Log activity
            $activityQuery = $pdo->prepare(
                "INSERT INTO activities (user_id, description, created_at)
                VALUES (:user_id, :description, NOW())"
            );
            
            $activityQuery->execute([
                'user_id' => $_SESSION['user_id'] ?? null,
                'description' => 'Deleted menu item: ' . $menuItem['name'] . ' from menu ID ' . $menuItem['menu_id']
            ]);
            
            $successMessage = 'Menu item deleted successfully';
        } catch (PDOException $e) {
            error_log("Delete menu item error: " . $e->getMessage());
            $errorMessage = 'Error deleting menu item: ' . $e->getMessage();
        }
    }
    
    // Edit menu
    if (isset($_POST['edit_menu'])) {
        $id = $_POST['id'];
        $name = $_POST['name'] ?? null;

        if (empty($name)) {
            $errorMessage = 'Please fill in the menu name.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE menus SET name = ? WHERE id = ?");
                $stmt->execute([$name, $id]);
                
                // Log activity
                $activityQuery = $pdo->prepare(
                    "INSERT INTO activities (user_id, description, created_at)
                    VALUES (:user_id, :description, NOW())"
                );
                
                $activityQuery->execute([
                    'user_id' => $_SESSION['user_id'] ?? null,
                    'description' => 'Updated menu: ' . $name
                ]);
                
                $successMessage = 'Menu updated successfully';
            } catch (PDOException $e) {
                error_log("Update menu error: " . $e->getMessage());
                $errorMessage = 'Error updating menu: ' . $e->getMessage();
            }
        }
    }
    
    // Edit menu item
    if (isset($_POST['edit_menu_item'])) {
        $id = $_POST['id'];
        $menu_id = $_POST['menu_id'] ?? null;
        $name = $_POST['name'] ?? null;
        $url = $_POST['url'] ?? null;
        $sort_order = $_POST['sort_order'] ?? 0;

        if (empty($menu_id) || empty($name) || empty($url)) {
            $errorMessage = 'Please fill in all required fields.';
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE menu_items SET menu_id = ?, name = ?, url = ?, sort_order = ? WHERE id = ?");
                $stmt->execute([$menu_id, $name, $url, $sort_order, $id]);
                
                // Log activity
                $activityQuery = $pdo->prepare(
                    "INSERT INTO activities (user_id, description, created_at)
                    VALUES (:user_id, :description, NOW())"
                );
                
                $activityQuery->execute([
                    'user_id' => $_SESSION['user_id'] ?? null,
                    'description' => 'Updated menu item: ' . $name
                ]);
                
                $successMessage = 'Menu item updated successfully';
            } catch (PDOException $e) {
                error_log("Update menu item error: " . $e->getMessage());
                $errorMessage = 'Error updating menu item: ' . $e->getMessage();
            }
        }
    }
}

// Get all menus and menu items
$menus = $pdo->query("SELECT id, name FROM menus ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
$menu_items = $pdo->query("
    SELECT mi.id, mi.menu_id, mi.name, mi.url, mi.sort_order, m.name as menu_name 
    FROM menu_items mi
    JOIN menus m ON mi.menu_id = m.id
    ORDER BY m.name, mi.sort_order
")->fetchAll(PDO::FETCH_ASSOC);

// Get menu items grouped by menu for the display tab
$grouped_menu_items = [];
foreach ($menu_items as $item) {
    if (!isset($grouped_menu_items[$item['menu_id']])) {
        $grouped_menu_items[$item['menu_id']] = [
            'menu_name' => $item['menu_name'],
            'items' => []
        ];
    }
    $grouped_menu_items[$item['menu_id']]['items'][] = $item;
}

?>

<!-- Main content container -->
<div class="content-wrapper p-4 sm:ml-64">
    <div class="p-4 mt-14">
        <div class="bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-800' : ''; ?>">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-semibold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                    <i class="fas fa-list mr-2"></i> Menu Management
                </h1>
                <nav class="flex" aria-label="Breadcrumb">
                    <ol class="inline-flex items-center space-x-1 md:space-x-3">
                        <li class="inline-flex items-center">
                            <a href="<?php echo $adminUrl; ?>/index.php" class="inline-flex items-center text-sm font-medium <?php echo $darkMode ? 'text-gray-400 hover:text-white' : 'text-gray-700 hover:text-blue-600'; ?>">
                                <i class="fas fa-home mr-2"></i> Dashboard
                            </a>
                        </li>
                        <li aria-current="page">
                            <div class="flex items-center">
                                <i class="fas fa-chevron-right text-gray-400 mx-2"></i>
                                <span class="text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">
                                    Menu Management
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

            <!-- Tabs Navigation -->
            <div class="mb-4 border-b border-gray-200 <?php echo $darkMode ? 'border-gray-700' : ''; ?>">
                <ul class="flex flex-wrap -mb-px text-sm font-medium text-center" id="menuTabs" role="tablist">
                    <li class="mr-2" role="presentation">
                        <button type="button" class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300" id="manage-tab" data-tabs-target="#manage" role="tab" aria-controls="manage" aria-selected="false">
                            <i class="fas fa-cog mr-2"></i> Manage Menus
                        </button>
                    </li>
                    <li class="mr-2" role="presentation">
                        <button type="button" class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300" id="items-tab" data-tabs-target="#items" role="tab" aria-controls="items" aria-selected="false">
                            <i class="fas fa-list-ul mr-2"></i> Menu Items
                        </button>
                    </li>
                    <li class="mr-2" role="presentation">
                        <button type="button" class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300" id="display-tab" data-tabs-target="#display" role="tab" aria-controls="display" aria-selected="false">
                            <i class="fas fa-eye mr-2"></i> Display Preview
                        </button>
                    </li>
                </ul>
            </div>
            
            <!-- Tab Content -->
            <div id="menuTabContent">
                <!-- Manage Menus Tab -->
                <div class="hidden p-4 rounded-lg bg-white <?php echo $darkMode ? 'bg-gray-800' : ''; ?>" id="manage" role="tabpanel" aria-labelledby="manage-tab">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Add Menu Form -->
                        <div class="bg-gray-50 p-6 rounded-lg shadow-sm <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                            <h2 class="text-xl font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                                <i class="fas fa-plus-circle mr-2"></i> Add New Menu
                            </h2>
                            
                            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                                <div class="mb-4">
                                    <label for="name" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                        Menu Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="name" name="name" 
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-600 border-gray-500 text-white' : ''; ?>" 
                                        placeholder="Enter menu name" required>
                                </div>
                                
                                <button type="submit" name="add_menu" class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center <?php echo $darkMode ? 'bg-blue-500 hover:bg-blue-600' : ''; ?>">
                                    <i class="fas fa-plus mr-2"></i> Add Menu
                                </button>
                            </form>
                        </div>
                        
                        <!-- Existing Menus -->
                        <div class="bg-gray-50 p-6 rounded-lg shadow-sm <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                            <h2 class="text-xl font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                                <i class="fas fa-list mr-2"></i> Existing Menus
                            </h2>
                            
                            <?php if (empty($menus)): ?>
                                <div class="text-center py-4 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-600'; ?>">
                                    <i class="fas fa-info-circle text-2xl mb-2"></i>
                                    <p>No menus have been created yet.</p>
                                </div>
                            <?php else: ?>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm text-left <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">
                                        <thead class="text-xs uppercase <?php echo $darkMode ? 'bg-gray-600 text-gray-300' : 'bg-gray-100 text-gray-700'; ?>">
                                            <tr>
                                                <th scope="col" class="px-4 py-2 rounded-tl-lg">ID</th>
                                                <th scope="col" class="px-4 py-2">Name</th>
                                                <th scope="col" class="px-4 py-2 rounded-tr-lg text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($menus as $menu): ?>
                                                <tr class="border-b <?php echo $darkMode ? 'border-gray-600' : 'border-gray-200'; ?>">
                                                    <td class="px-4 py-2"><?php echo $menu['id']; ?></td>
                                                    <td class="px-4 py-2"><?php echo htmlspecialchars($menu['name']); ?></td>
                                                    <td class="px-4 py-2 text-right">
                                                        <button type="button" class="text-blue-500 hover:text-blue-700 edit-menu-btn" 
                                                                data-id="<?php echo $menu['id']; ?>" 
                                                                data-name="<?php echo htmlspecialchars($menu['name']); ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="inline-block ml-2">
                                                            <input type="hidden" name="id" value="<?php echo $menu['id']; ?>">
                                                            <button type="submit" name="delete_menu" class="text-red-500 hover:text-red-700" onclick="return confirm('Are you sure you want to delete this menu? This will also delete all menu items associated with it.');">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Edit Menu Modal -->
                    <div id="editMenuModal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
                        <div class="fixed inset-0 bg-black opacity-50"></div>
                        <div class="relative bg-white rounded-lg shadow-lg p-6 w-full max-w-md <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                            <button type="button" class="absolute top-3 right-3 text-gray-400 hover:text-gray-900 rounded-lg text-sm w-8 h-8 inline-flex justify-center items-center <?php echo $darkMode ? 'hover:text-white' : ''; ?>" id="closeEditMenuModal">
                                <i class="fas fa-times"></i>
                                <span class="sr-only">Close</span>
                            </button>
                            
                            <h3 class="text-xl font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                <i class="fas fa-edit mr-2"></i> Edit Menu
                            </h3>
                            
                            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                                <input type="hidden" id="edit_menu_id" name="id">
                                
                                <div class="mb-4">
                                    <label for="edit_menu_name" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                        Menu Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="edit_menu_name" name="name" 
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-600 border-gray-500 text-white' : ''; ?>" 
                                        required>
                                </div>
                                
                                <div class="flex justify-end">
                                    <button type="button" class="mr-2 px-4 py-2 text-sm font-medium text-gray-500 bg-gray-100 rounded-lg border border-gray-200 hover:bg-gray-200 focus:ring-4 focus:ring-gray-300 <?php echo $darkMode ? 'bg-gray-600 text-gray-300 border-gray-500 hover:bg-gray-500' : ''; ?>" id="cancelEditMenuModal">
                                        Cancel
                                    </button>
                                    <button type="submit" name="edit_menu" class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center <?php echo $darkMode ? 'bg-blue-500 hover:bg-blue-600' : ''; ?>">
                                        Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Menu Items Tab -->
                <div class="hidden p-4 rounded-lg bg-white <?php echo $darkMode ? 'bg-gray-800' : ''; ?>" id="items" role="tabpanel" aria-labelledby="items-tab">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <!-- Add Menu Item Form -->
                        <div class="bg-gray-50 p-6 rounded-lg shadow-sm <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                            <h2 class="text-xl font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                                <i class="fas fa-plus-circle mr-2"></i> Add Menu Item
                            </h2>
                            
                            <?php if (empty($menus)): ?>
                                <div class="text-center py-4 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-600'; ?>">
                                    <i class="fas fa-info-circle text-2xl mb-2"></i>
                                    <p>No menus have been created yet. Please create a menu first.</p>
                                </div>
                            <?php else: ?>
                                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                                    <div class="mb-4">
                                        <label for="menu_id" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                            Select Menu <span class="text-red-500">*</span>
                                        </label>
                                        <select id="menu_id" name="menu_id" 
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-600 border-gray-500 text-white' : ''; ?>" required>
                                            <option value="">Select a menu</option>
                                            <?php foreach ($menus as $menu): ?>
                                                <option value="<?php echo $menu['id']; ?>"><?php echo htmlspecialchars($menu['name']); ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="item_name" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                            Item Name <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="item_name" name="name" 
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-600 border-gray-500 text-white' : ''; ?>" 
                                            placeholder="Enter item name" required>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="item_url" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                            Item URL <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" id="item_url" name="url" 
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-600 border-gray-500 text-white' : ''; ?>" 
                                            placeholder="https://example.com/page" required>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <label for="sort_order" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                            Sort Order
                                        </label>
                                        <input type="number" id="sort_order" name="sort_order" 
                                            class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-600 border-gray-500 text-white' : ''; ?>" 
                                            placeholder="0" value="0" min="0">
                                    </div>
                                    
                                    <button type="submit" name="add_menu_item" class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center <?php echo $darkMode ? 'bg-blue-500 hover:bg-blue-600' : ''; ?>">
                                        <i class="fas fa-plus mr-2"></i> Add Menu Item
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Existing Menu Items -->
                        <div class="bg-gray-50 p-6 rounded-lg shadow-sm <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                            <h2 class="text-xl font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                                <i class="fas fa-list-ul mr-2"></i> Existing Menu Items
                            </h2>
                            
                            <?php if (empty($menu_items)): ?>
                                <div class="text-center py-4 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-600'; ?>">
                                    <i class="fas fa-info-circle text-2xl mb-2"></i>
                                    <p>No menu items have been created yet.</p>
                                </div>
                            <?php else: ?>
                                <div class="overflow-x-auto">
                                    <table class="w-full text-sm text-left <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">
                                        <thead class="text-xs uppercase <?php echo $darkMode ? 'bg-gray-600 text-gray-300' : 'bg-gray-100 text-gray-700'; ?>">
                                            <tr>
                                                <th scope="col" class="px-4 py-2 rounded-tl-lg">Menu</th>
                                                <th scope="col" class="px-4 py-2">Name</th>
                                                <th scope="col" class="px-4 py-2">Order</th>
                                                <th scope="col" class="px-4 py-2 rounded-tr-lg text-right">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($menu_items as $item): ?>
                                                <tr class="border-b <?php echo $darkMode ? 'border-gray-600' : 'border-gray-200'; ?>">
                                                    <td class="px-4 py-2"><?php echo htmlspecialchars($item['menu_name']); ?></td>
                                                    <td class="px-4 py-2"><?php echo htmlspecialchars($item['name']); ?></td>
                                                    <td class="px-4 py-2"><?php echo $item['sort_order']; ?></td>
                                                    <td class="px-4 py-2 text-right">
                                                        <button type="button" class="text-blue-500 hover:text-blue-700 edit-menu-item-btn" 
                                                                data-id="<?php echo $item['id']; ?>"
                                                                data-menu-id="<?php echo $item['menu_id']; ?>"
                                                                data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                                                data-url="<?php echo htmlspecialchars($item['url']); ?>"
                                                                data-sort-order="<?php echo $item['sort_order']; ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="inline-block ml-2">
                                                            <input type="hidden" name="id" value="<?php echo $item['id']; ?>">
                                                            <button type="submit" name="delete_menu_item" class="text-red-500 hover:text-red-700" onclick="return confirm('Are you sure you want to delete this menu item?');">
                                                                <i class="fas fa-trash-alt"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Edit Menu Item Modal -->
                    <div id="editMenuItemModal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
                        <div class="fixed inset-0 bg-black opacity-50"></div>
                        <div class="relative bg-white rounded-lg shadow-lg p-6 w-full max-w-md <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                            <button type="button" class="absolute top-3 right-3 text-gray-400 hover:text-gray-900 rounded-lg text-sm w-8 h-8 inline-flex justify-center items-center <?php echo $darkMode ? 'hover:text-white' : ''; ?>" id="closeEditMenuItemModal">
                                <i class="fas fa-times"></i>
                                <span class="sr-only">Close</span>
                            </button>
                            
                            <h3 class="text-xl font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                <i class="fas fa-edit mr-2"></i> Edit Menu Item
                            </h3>
                            
                            <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
                                <input type="hidden" id="edit_item_id" name="id">
                                
                                <div class="mb-4">
                                    <label for="edit_item_menu_id" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                        Select Menu <span class="text-red-500">*</span>
                                    </label>
                                    <select id="edit_item_menu_id" name="menu_id" 
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-600 border-gray-500 text-white' : ''; ?>" required>
                                        <?php foreach ($menus as $menu): ?>
                                            <option value="<?php echo $menu['id']; ?>"><?php echo htmlspecialchars($menu['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="edit_item_name" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                        Item Name <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="edit_item_name" name="name" 
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-600 border-gray-500 text-white' : ''; ?>" 
                                        required>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="edit_item_url" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                        Item URL <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="edit_item_url" name="url" 
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-600 border-gray-500 text-white' : ''; ?>" 
                                        required>
                                </div>
                                
                                <div class="mb-4">
                                    <label for="edit_item_sort_order" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                                        Sort Order
                                    </label>
                                    <input type="number" id="edit_item_sort_order" name="sort_order" 
                                        class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-600 border-gray-500 text-white' : ''; ?>" 
                                        min="0">
                                </div>
                                
                                <div class="flex justify-end">
                                    <button type="button" class="mr-2 px-4 py-2 text-sm font-medium text-gray-500 bg-gray-100 rounded-lg border border-gray-200 hover:bg-gray-200 focus:ring-4 focus:ring-gray-300 <?php echo $darkMode ? 'bg-gray-600 text-gray-300 border-gray-500 hover:bg-gray-500' : ''; ?>" id="cancelEditMenuItemModal">
                                        Cancel
                                    </button>
                                    <button type="submit" name="edit_menu_item" class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center <?php echo $darkMode ? 'bg-blue-500 hover:bg-blue-600' : ''; ?>">
                                        Save Changes
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Display Preview Tab -->
                <div class="hidden p-4 rounded-lg bg-white <?php echo $darkMode ? 'bg-gray-800' : ''; ?>" id="display" role="tabpanel" aria-labelledby="display-tab">
                    <?php if (empty($menus)): ?>
                        <div class="text-center py-6 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-600'; ?>">
                            <i class="fas fa-info-circle text-4xl mb-3"></i>
                            <p class="text-xl">No menus have been created yet.</p>
                            <p class="mt-2">Create a menu in the "Manage Menus" tab to see a preview.</p>
                        </div>
                    <?php else: ?>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                            <?php foreach ($menus as $menu): ?>
                                <div class="bg-gray-50 p-6 rounded-lg shadow-sm <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                                    <h2 class="text-xl font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                                        <i class="fas fa-list mr-2"></i> <?php echo htmlspecialchars($menu['name']); ?> Menu
                                    </h2>
                                    
                                    <?php if (!isset($grouped_menu_items[$menu['id']]) || empty($grouped_menu_items[$menu['id']]['items'])): ?>
                                        <div class="text-center py-4 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-600'; ?>">
                                            <p><em>This menu has no items yet.</em></p>
                                        </div>
                                    <?php else: ?>
                                        <div class="border rounded-lg overflow-hidden <?php echo $darkMode ? 'border-gray-600 bg-gray-800' : 'border-gray-200'; ?>">
                                            <div class="p-3 <?php echo $darkMode ? 'bg-gray-600 text-gray-100' : 'bg-gray-100 text-gray-700'; ?>">
                                                <strong>Preview:</strong>
                                            </div>
                                            <div class="p-4">
                                                <ul class="flex flex-wrap gap-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-800'; ?>">
                                                    <?php 
                                                    // Sort items by sort order
                                                    $items = $grouped_menu_items[$menu['id']]['items'];
                                                    usort($items, function($a, $b) {
                                                        return $a['sort_order'] - $b['sort_order'];
                                                    });
                                                    
                                                    foreach ($items as $item): 
                                                    ?>
                                                        <li>
                                                            <a href="<?php echo htmlspecialchars($item['url']); ?>" class="px-3 py-2 rounded hover:bg-gray-100 <?php echo $darkMode ? 'hover:bg-gray-600' : ''; ?>">
                                                                <?php echo htmlspecialchars($item['name']); ?>
                                                            </a>
                                                        </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-4 overflow-x-auto">
                                            <table class="w-full text-sm text-left <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">
                                                <thead class="text-xs uppercase <?php echo $darkMode ? 'bg-gray-600 text-gray-300' : 'bg-gray-100 text-gray-700'; ?>">
                                                    <tr>
                                                        <th scope="col" class="px-4 py-2 rounded-tl-lg">Name</th>
                                                        <th scope="col" class="px-4 py-2">URL</th>
                                                        <th scope="col" class="px-4 py-2 rounded-tr-lg">Order</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($items as $item): ?>
                                                        <tr class="border-b <?php echo $darkMode ? 'border-gray-600' : 'border-gray-200'; ?>">
                                                            <td class="px-4 py-2"><?php echo htmlspecialchars($item['name']); ?></td>
                                                            <td class="px-4 py-2 truncate max-w-[200px]">
                                                                <span title="<?php echo htmlspecialchars($item['url']); ?>">
                                                                    <?php echo htmlspecialchars($item['url']); ?>
                                                                </span>
                                                            </td>
                                                            <td class="px-4 py-2"><?php echo $item['sort_order']; ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Show first tab by default
        document.getElementById('manage').classList.remove('hidden');
        document.getElementById('manage-tab').classList.add('text-blue-600', 'border-blue-600');
        document.getElementById('manage-tab').classList.remove('border-transparent');
        document.getElementById('manage-tab').setAttribute('aria-selected', 'true');
        
        // Tab switching functionality
        const tabs = document.querySelectorAll('[role="tab"]');
        const tabContents = document.querySelectorAll('[role="tabpanel"]');
        
        tabs.forEach(tab => {
            tab.addEventListener('click', () => {
                // Deactivate all tabs
                tabs.forEach(t => {
                    t.classList.remove('text-blue-600', 'border-blue-600');
                    t.classList.add('text-gray-500', 'border-transparent');
                    t.setAttribute('aria-selected', 'false');
                });
                
                // Hide all tab contents
                tabContents.forEach(content => {
                    content.classList.add('hidden');
                });
                
                // Activate clicked tab
                tab.classList.remove('text-gray-500', 'border-transparent');
                tab.classList.add('text-blue-600', 'border-blue-600');
                tab.setAttribute('aria-selected', 'true');
                
                // Show corresponding tab content
                const targetId = tab.getAttribute('data-tabs-target').substring(1); // Remove the #
                const target = document.getElementById(targetId);
                target.classList.remove('hidden');
            });
        });
        
        // Edit Menu Modal functionality
        const editMenuModal = document.getElementById('editMenuModal');
        const closeEditMenuModal = document.getElementById('closeEditMenuModal');
        const cancelEditMenuModal = document.getElementById('cancelEditMenuModal');
        const editMenuBtns = document.querySelectorAll('.edit-menu-btn');
        
        editMenuBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const menuId = btn.getAttribute('data-id');
                const menuName = btn.getAttribute('data-name');
                
                document.getElementById('edit_menu_id').value = menuId;
                document.getElementById('edit_menu_name').value = menuName;
                
                editMenuModal.classList.remove('hidden');
            });
        });
        
        closeEditMenuModal.addEventListener('click', () => {
            editMenuModal.classList.add('hidden');
        });
        
        cancelEditMenuModal.addEventListener('click', () => {
            editMenuModal.classList.add('hidden');
        });
        
        // Edit Menu Item Modal functionality
        const editMenuItemModal = document.getElementById('editMenuItemModal');
        const closeEditMenuItemModal = document.getElementById('closeEditMenuItemModal');
        const cancelEditMenuItemModal = document.getElementById('cancelEditMenuItemModal');
        const editMenuItemBtns = document.querySelectorAll('.edit-menu-item-btn');
        
        editMenuItemBtns.forEach(btn => {
            btn.addEventListener('click', () => {
                const itemId = btn.getAttribute('data-id');
                const menuId = btn.getAttribute('data-menu-id');
                const itemName = btn.getAttribute('data-name');
                const itemUrl = btn.getAttribute('data-url');
                const sortOrder = btn.getAttribute('data-sort-order');
                
                document.getElementById('edit_item_id').value = itemId;
                document.getElementById('edit_item_menu_id').value = menuId;
                document.getElementById('edit_item_name').value = itemName;
                document.getElementById('edit_item_url').value = itemUrl;
                document.getElementById('edit_item_sort_order').value = sortOrder;
                
                editMenuItemModal.classList.remove('hidden');
            });
        });
        
        closeEditMenuItemModal.addEventListener('click', () => {
            editMenuItemModal.classList.add('hidden');
        });
        
        cancelEditMenuItemModal.addEventListener('click', () => {
            editMenuItemModal.classList.add('hidden');
        });
        
        // Close modals when clicking outside
        window.addEventListener('click', (e) => {
            if (e.target.classList.contains('fixed') && e.target.classList.contains('inset-0') && e.target.classList.contains('flex')) {
                e.target.classList.add('hidden');
            }
        });
    });
</script>

<?php
// Include footer
require_once '../../theme/admin/footer.php';
?>