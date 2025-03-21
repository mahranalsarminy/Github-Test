<?php
require_once '../includes/init.php';

// Ensure only admins can access this page
require_admin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_menu'])) {
        $name = $_POST['name'] ?? null;

        // التحقق من أن الحقول المطلوبة ليست فارغة
        if (empty($name)) {
            $_SESSION['error'] = 'Please fill in the menu name.';
            header("Location: menu.php");
            exit;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO menus (name) VALUES (?)");
            $stmt->execute([$name]);
            $_SESSION['success'] = 'Menu added successfully';
        } catch (PDOException $e) {
            error_log("Add menu error: " . $e->getMessage());
            $_SESSION['error'] = 'Error adding menu: ' . $e->getMessage();
        }

        header("Location: menu.php");
        exit;
    }

    if (isset($_POST['delete_menu'])) {
        $id = $_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM menus WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = 'Menu deleted successfully';
        } catch (PDOException $e) {
            error_log("Delete menu error: " . $e->getMessage());
            $_SESSION['error'] = 'Error deleting menu: ' . $e->getMessage();
        }

        header("Location: menu.php");
        exit;
    }

    if (isset($_POST['add_menu_item'])) {
        $menu_id = $_POST['menu_id'] ?? null;
        $name = $_POST['name'] ?? null;
        $url = $_POST['url'] ?? null;
        $sort_order = $_POST['sort_order'] ?? 0;

        // التحقق من أن الحقول المطلوبة ليست فارغة
        if (empty($menu_id) || empty($name) || empty($url)) {
            $_SESSION['error'] = 'Please fill in all required fields.';
            header("Location: menu.php");
            exit;
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO menu_items (menu_id, name, url, sort_order) VALUES (?, ?, ?, ?)");
            $stmt->execute([$menu_id, $name, $url, $sort_order]);
            $_SESSION['success'] = 'Menu item added successfully';
        } catch (PDOException $e) {
            error_log("Add menu item error: " . $e->getMessage());
            $_SESSION['error'] = 'Error adding menu item: ' . $e->getMessage();
        }

        header("Location: menu.php");
        exit;
    }

    if (isset($_POST['delete_menu_item'])) {
        $id = $_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = 'Menu item deleted successfully';
        } catch (PDOException $e) {
            error_log("Delete menu item error: " . $e->getMessage());
            $_SESSION['error'] = 'Error deleting menu item: ' . $e->getMessage();
        }

        header("Location: menu.php");
        exit;
    }
}

$menus = $pdo->query("SELECT id, name FROM menus")->fetchAll(PDO::FETCH_ASSOC);
$menu_items = $pdo->query("SELECT id, menu_id, name, url, sort_order FROM menu_items")->fetchAll(PDO::FETCH_ASSOC);

include 'theme/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Menu Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        input, textarea, select {
            display: block !important;
            width: 100% !important;
            padding: 0.5rem !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 0.375rem !important;
            background-color: white !important;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
    </style>
</head>
<body class="bg-gray-100 flex flex-col min-h-screen">
    <?php include 'theme/sidebar.php'; ?>

    <div class="flex flex-1 flex-col md:flex-row">
        <aside class="md:w-64 w-full md:block hidden">
            <?php include 'theme/sidebar.php'; ?>
        </aside>

        <main class="flex-1 p-4 mt-16 w-full">
            <div class="bg-white rounded-lg shadow p-6 w-full">
                <h1 class="text-2xl font-bold mb-4 text-center">Menu Management</h1>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <div class="form-group">
                        <label>Menu Name</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="text-center">
                        <button type="submit" name="add_menu" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                            Add Menu
                        </button>
                    </div>
                </form>

                <h2 class="text-2xl font-bold mb-4 text-center mt-8">Existing Menus</h2>

                <ul class="mt-6">
                    <?php foreach ($menus as $menu): ?>
                        <li class="flex justify-between items-center mb-2">
                            <span><?php echo htmlspecialchars($menu['name']); ?></span>
                            <form action="menu.php" method="post" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo $menu['id']; ?>">
                                <button type="submit" name="delete_menu" class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600">Delete</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>

                <h2 class="text-2xl font-bold mb-4 text-center mt-8">Add Menu Items</h2>

                <form method="post">
                    <div class="form-group">
                        <label>Select Menu</label>
                        <select name="menu_id" required>
                            <option value="">Select a menu</option>
                            <?php foreach ($menus as $menu): ?>
                                <option value="<?php echo $menu['id']; ?>"><?php echo htmlspecialchars($menu['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Item Name</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>Item URL</label>
                        <input type="text" name="url" required>
                    </div>
                    <div class="form-group">
                        <label>Sort Order</label>
                        <input type="number" name="sort_order" value="0" required>
                    </div>
                    <div class="text-center">
                        <button type="submit" name="add_menu_item" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                            Add Item
                        </button>
                    </div>
                </form>

                <h2 class="text-2xl font-bold mb-4 text-center mt-8">Menu Items</h2>

                <ul class="mt-6">
                    <?php foreach ($menu_items as $menu_item): ?>
                        <li class="flex justify-between items-center mb-2">
                            <span><?php echo htmlspecialchars($menu_item['name']); ?> (<?php echo htmlspecialchars($menu_item['url']); ?>) - Menu ID: <?php echo $menu_item['menu_id']; ?></span>
                            <form action="menu.php" method="post" style="display:inline;">
                                <input type="hidden" name="id" value="<?php echo $menu_item['id']; ?>">
                                <button type="submit" name="delete_menu_item" class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600">Delete</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>

            </div>
        </main>
    </div>
</body>
</html>
<?php include 'theme/footer.php'; ?>