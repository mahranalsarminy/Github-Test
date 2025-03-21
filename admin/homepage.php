<?php
require_once '../includes/init.php';

// Ensure only admins can access this page
require_admin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_site_settings'])) {
        $site_name = $_POST['site_name'] ?? null;
        $footer_content = $_POST['footer_content'] ?? null;
        $news_ticker_enabled = isset($_POST['news_ticker_enabled']) ? 1 : 0;
        $header_menu_id = !empty($_POST['header_menu_id']) ? $_POST['header_menu_id'] : null;
        $footer_menu_id = !empty($_POST['footer_menu_id']) ? $_POST['footer_menu_id'] : null;
        $enable_header = isset($_POST['enable_header']) ? 1 : 0;
        $enable_footer = isset($_POST['enable_footer']) ? 1 : 0;
        $enable_navbar = isset($_POST['enable_navbar']) ? 1 : 0;
        $enable_search_box = isset($_POST['enable_search_box']) ? 1 : 0;
        $enable_categories = isset($_POST['enable_categories']) ? 1 : 0;
        $dark_mode = isset($_POST['dark_mode']) ? 1 : 0;
        $language = $_POST['language'] ?? 'en';

        // التحقق من أن الحقول المطلوبة ليست فارغة
        if (empty($site_name) || empty($footer_content)) {
            $_SESSION['error'] = 'Please fill in all required fields.';
            header("Location: homepage.php");
            exit;
        }

        try {
            $stmt = $pdo->prepare("
                UPDATE site_settings 
                SET 
                    site_name = :site_name,
                    footer_content = :footer_content,
                    news_ticker_enabled = :news_ticker_enabled,
                    header_menu_id = :header_menu_id,
                    footer_menu_id = :footer_menu_id,
                    enable_header = :enable_header,
                    enable_footer = :enable_footer,
                    enable_navbar = :enable_navbar,
                    enable_search_box = :enable_search_box,
                    enable_categories = :enable_categories,
                    dark_mode = :dark_mode,
                    language = :language
                WHERE id = 1
            ");

            $stmt->execute([
                'site_name' => $site_name,
                'footer_content' => $footer_content,
                'news_ticker_enabled' => $news_ticker_enabled,
                'header_menu_id' => $header_menu_id,
                'footer_menu_id' => $footer_menu_id,
                'enable_header' => $enable_header,
                'enable_footer' => $enable_footer,
                'enable_navbar' => $enable_navbar,
                'enable_search_box' => $enable_search_box,
                'enable_categories' => $enable_categories,
                'dark_mode' => $dark_mode,
                'language' => $language
            ]);

            if (isset($_FILES['site_logo']) && $_FILES['site_logo']['error'] === UPLOAD_ERR_OK) {
                $target_dir = "../uploads/";
                $target_file = $target_dir . basename($_FILES["site_logo"]["name"]);
                if (move_uploaded_file($_FILES["site_logo"]["tmp_name"], $target_file)) {
                    $stmt = $pdo->prepare("UPDATE site_settings SET site_logo = ? WHERE id = 1");
                    $stmt->execute([$_FILES["site_logo"]["name"]]);
                }
            }

            $_SESSION['success'] = 'Settings updated successfully';
        } catch (PDOException $e) {
            error_log("Settings update error: " . $e->getMessage());
            $_SESSION['error'] = 'Error updating settings: ' . $e->getMessage();
        }
        
        header("Location: homepage.php");
        exit;
    }

    if (isset($_POST['add_navbar_item'])) {
        $name = $_POST['name'] ?? null;
        $url = $_POST['url'] ?? null;

        // التحقق من أن الحقول المطلوبة ليست فارغة
        if (empty($name) || empty($url)) {
            $_SESSION['error'] = 'Please fill in all required fields.';
            header("Location: homepage.php");
            exit;
        }

        try {
            // تعديل الاستعلام لحل المشكلة
            $stmt = $pdo->prepare("
                INSERT INTO navbar (name, url, sort_order) 
                VALUES (?, ?, (SELECT IFNULL(MAX(sort_order), 0) + 1 FROM (SELECT sort_order FROM navbar) AS subquery))
            ");
            $stmt->execute([$name, $url]);
            $_SESSION['success'] = 'Navbar item added successfully';
        } catch (PDOException $e) {
            error_log("Add navbar item error: " . $e->getMessage());
            $_SESSION['error'] = 'Error adding navbar item: ' . $e->getMessage();
        }

        header("Location: homepage.php");
        exit;
    }

    if (isset($_POST['delete_navbar_item'])) {
        $id = $_POST['id'];
        try {
            $stmt = $pdo->prepare("DELETE FROM navbar WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = 'Navbar item deleted successfully';
        } catch (PDOException $e) {
            error_log("Delete navbar item error: " . $e->getMessage());
            $_SESSION['error'] = 'Error deleting navbar item: ' . $e->getMessage();
        }

        header("Location: homepage.php");
        exit;
    }
}

$settings = get_site_settings();
$menus = $pdo->query("SELECT id, name FROM menus")->fetchAll(PDO::FETCH_ASSOC);
include 'theme/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage Settings - Admin Panel</title>
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
                <h1 class="text-2xl font-bold mb-4 text-center">Homepage Settings</h1>

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

                <form method="post" enctype="multipart/form-data">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h2 class="text-xl font-semibold mb-4">Basic Settings</h2>

                            <div class="form-group">
                                <label>Site Name</label>
                                <input type="text" name="site_name" value="<?php echo h($settings['site_name']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Site Logo</label>
                                <input type="file" name="site_logo">
                            </div>

                            <div class="form-group">
                                <label>Footer Content</label>
                                <textarea name="footer_content" rows="3" required><?php echo h($settings['footer_content']); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label>Enable News Ticker</label>
                                <input type="checkbox" name="news_ticker_enabled" <?php echo $settings['news_ticker_enabled'] ? 'checked' : ''; ?>>
                            </div>

                            <div class="form-group">
                                <label>Header Menu</label>
                                <select name="header_menu_id">
                                    <option value="">Select a menu</option>
                                    <?php foreach ($menus as $menu): ?>
                                        <option value="<?php echo $menu['id']; ?>" <?php echo $settings['header_menu_id'] == $menu['id'] ? 'selected' : ''; ?>>
                                            <?php echo h($menu['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <a href="menu.php" class="text-blue-500 hover:underline">Manage Menus</a>
                            </div>

                            <div class="form-group">
                                <label>Footer Menu</label>
                                <select name="footer_menu_id">
                                    <option value="">Select a menu</option>
                                    <?php foreach ($menus as $menu): ?>
                                        <option value="<?php echo $menu['id']; ?>" <?php echo $settings['footer_menu_id'] == $menu['id'] ? 'selected' : ''; ?>>
                                            <?php echo h($menu['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <a href="menu.php" class="text-blue-500 hover:underline">Manage Menus</a>
                            </div>

                            <div class="form-group">
                                <label>Enable Header</label>
                                <input type="checkbox" name="enable_header" <?php echo $settings['enable_header'] ? 'checked' : ''; ?>>
                            </div>

                            <div class="form-group">
                                <label>Enable Footer</label>
                                <input type="checkbox" name="enable_footer" <?php echo $settings['enable_footer'] ? 'checked' : ''; ?>>
                            </div>

                            <div class="form-group">
                                <label>Enable Navbar</label>
                                <input type="checkbox" name="enable_navbar" <?php echo $settings['enable_navbar'] ? 'checked' : ''; ?>>
                            </div>

                            <div class="form-group">
                                <label>Enable Search Box</label>
                                <input type="checkbox" name="enable_search_box" <?php echo $settings['enable_search_box'] ? 'checked' : ''; ?>>
                            </div>

                            <div class="form-group">
                                <label>Enable Categories</label>
                                <input type="checkbox" name="enable_categories" <?php echo $settings['enable_categories'] ? 'checked' : ''; ?>>
                            </div>

                            <div class="form-group">
                                <label>Dark Mode</label>
                                <input type="checkbox" name="dark_mode" <?php echo $settings['dark_mode'] ? 'checked' : ''; ?>>
                            </div>

                            <div class="form-group">
                                <label>Language</label>
                                <select name="language">
                                    <option value="en" <?php echo $settings['language'] == 'en' ? 'selected' : ''; ?>>English</option>
                                    <option value="ar" <?php echo $settings['language'] == 'ar' ? 'selected' : ''; ?>>Arabic</option>
                                    <!-- أضف لغات أخرى هنا -->
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 text-center">
                        <button type="submit" name="update_site_settings" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                            Save Changes
                        </button>
                    </div>
                </form>

                <h2 class="text-2xl font-bold mb-4 text-center mt-8">Navbar Items</h2>

                <form method="post">
                    <div class="form-group">
                        <label>Name</label>
                        <input type="text" name="name" required>
                    </div>
                    <div class="form-group">
                        <label>URL</label>
                        <input type="text" name="url" required>
                    </div>
                    <div class="text-center">
                        <button type="submit" name="add_navbar_item" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                            Add
                        </button>
                    </div>
                </form>

                               <ul class="mt-6">
                    <?php
                    $stmt = $pdo->query("SELECT id, name, url FROM navbar ORDER BY sort_order");
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                        echo '<li class="flex justify-between items-center mb-2">';
                        echo '<span>' . $row['name'] . ' (' . $row['url'] . ')</span>';
                        echo '<form action="homepage.php" method="post" style="display:inline;">';
                        echo '<input type="hidden" name="id" value="' . $row['id'] . '">';
                        echo '<button type="submit" name="delete_navbar_item" class="bg-red-500 text-white px-2 py-1 rounded hover:bg-red-600">Delete</button>';
                        echo '</form>';
                        echo '</li>';
                    }
                    ?>
                </ul>
            </div>
        </main>
    </div>
</body>
</html>
<?php include 'theme/footer.php'; ?>