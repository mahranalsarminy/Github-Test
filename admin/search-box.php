<?php
require_once '../includes/init.php';

// Ensure only admins can access this page
require_admin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $background_type = $_POST['background_type'];
    $background_value = $background_type == 'color' ? $_POST['background_color'] : $_FILES['background_image']['name'];
    $categories = implode(',', array_map('trim', explode(',', $_POST['categories'])));
    
    try {
        $stmt = $pdo->prepare("UPDATE search_box_settings SET background_type = ?, background_value = ?, categories = ? WHERE id = 1");
        $stmt->execute([$background_type, $background_value, $categories]);
        
        if ($background_type == 'image' && isset($_FILES['background_image']) && $_FILES['background_image']['error'] === UPLOAD_ERR_OK) {
            $target_dir = "../uploads/";
            $target_file = $target_dir . basename($_FILES["background_image"]["name"]);
            move_uploaded_file($_FILES["background_image"]["tmp_name"], $target_file);
        }
        
        $_SESSION['success'] = 'Settings updated successfully';
    } catch (PDOException $e) {
        error_log("Settings update error: " . $e->getMessage());
        $_SESSION['error'] = 'Error updating settings: ' . $e->getMessage();
    }
    
    header("Location: search-box.php");
    exit;
}

$stmt = $pdo->query("SELECT * FROM search_box_settings WHERE id = 1");
$settings = $stmt->fetch(PDO::FETCH_ASSOC);

$categories_stmt = $pdo->query("SELECT id, name FROM categories");
$all_categories = $categories_stmt->fetchAll(PDO::FETCH_ASSOC);

include 'theme/header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Box Settings - Admin Panel</title>
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
        .category-tag {
            display: inline-block;
            background-color: #e2e8f0;
            border-radius: 0.375rem;
            padding: 0.25rem 0.5rem;
            margin: 0.25rem;
            cursor: pointer;
        }
        .category-tag:hover {
            background-color: #cbd5e0;
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
                <h1 class="text-2xl font-bold mb-4 text-center">Search Box Settings</h1>

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
                    <div class="form-group">
                        <label for="background_type">Background Type</label>
                        <select name="background_type" id="background_type" required>
                            <option value="color" <?php echo $settings['background_type'] == 'color' ? 'selected' : ''; ?>>Color</option>
                            <option value="image" <?php echo $settings['background_type'] == 'image' ? 'selected' : ''; ?>>Image</option>
                        </select>
                    </div>

                    <div class="form-group" id="background_color_group" style="display: <?php echo $settings['background_type'] == 'color' ? 'block' : 'none'; ?>;">
                        <label for="background_color">Background Color</label>
                        <input type="color" name="background_color" id="background_color" value="<?php echo htmlspecialchars($settings['background_value']); ?>">
                    </div>

                    <div class="form-group" id="background_image_group" style="display: <?php echo $settings['background_type'] == 'image' ? 'block' : 'none'; ?>;">
                        <label for="background_image">Background Image</label>
                        <input type="file" name="background_image" id="background_image">
                    </div>

                    <div class="form-group">
                        <label for="categories">Categories</label>
                        <input type="text" name="categories" id="categories" value="<?php echo htmlspecialchars(implode(',', explode(',', $settings['categories']))); ?>" placeholder="Enter category names separated by commas">
                        <div id="categories_suggestions"></div>
                    </div>

                    <div class="mt-6 text-center">
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('background_type').addEventListener('change', function() {
            const isColor = this.value === 'color';
            document.getElementById('background_color_group').style.display = isColor ? 'block' : 'none';
            document.getElementById('background_image_group').style.display = isColor ? 'none' : 'block';
        });

        const categoriesInput = document.getElementById('categories');
        const categoriesSuggestions = document.getElementById('categories_suggestions');
        const existingCategories = <?php echo json_encode(array_column($all_categories, 'name')); ?>;

        categoriesInput.addEventListener('input', function() {
            const input = this.value.split(',').map(item => item.trim());
            const lastInput = input[input.length - 1].toLowerCase();
            categoriesSuggestions.innerHTML = '';

            if (lastInput.length > 0) {
                const suggestions = existingCategories.filter(category => category.toLowerCase().includes(lastInput));
                suggestions.forEach(suggestion => {
                    const div = document.createElement('div');
                    div.textContent = suggestion;
                    div.classList.add('category-tag');
                    div.addEventListener('click', function() {
                        input[input.length - 1] = suggestion;
                        categoriesInput.value = input.join(', ') + ', ';
                        categoriesSuggestions.innerHTML = '';
                    });
                    categoriesSuggestions.appendChild(div);
                });
            }
        });
    </script>
</body>
</html>
<?php include 'theme/footer.php'; ?>