<?php
require_once '../includes/init.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$category = [];

if ($id) {
    // Get category for editing
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$id]);
    $category = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$category) {
        $_SESSION['error'] = 'Category not found';
        header("Location: categories.php");
        exit;
    }
}

// Get parent categories for dropdown
$stmt = $pdo->prepare("
    SELECT id, name 
    FROM categories 
    WHERE parent_id IS NULL 
    AND id != ? 
    ORDER BY name
");
$stmt->execute([$id]);
$parent_categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $data = [
            'name' => trim($_POST['name']),
            'slug' => trim($_POST['slug'] ?: create_slug($_POST['name'])),
            'description' => trim($_POST['description']),
            'icon_url' => trim($_POST['icon_url']),
            'bg_color' => trim($_POST['bg_color']),
            'parent_id' => !empty($_POST['parent_id']) ? (int)$_POST['parent_id'] : null,
            'sort_order' => (int)$_POST['sort_order'],
            'active' => isset($_POST['active']) ? 1 : 0,
            'updated_at' => '2025-03-14 07:19:49',
            'updated_by' => 'mahranalsarminy'
        ];

        if ($id) {
            // Update existing category
            $stmt = $pdo->prepare("
                UPDATE categories 
                SET name = :name,
                    slug = :slug,
                    description = :description,
                    icon_url = :icon_url,
                    bg_color = :bg_color,
                    parent_id = :parent_id,
                    sort_order = :sort_order,
                    active = :active,
                    updated_at = :updated_at,
                    updated_by = :updated_by
                WHERE id = :id
            ");
            $data['id'] = $id;
        } else {
            // Insert new category
            $stmt = $pdo->prepare("
                INSERT INTO categories 
                (name, slug, description, icon_url, bg_color, parent_id, sort_order, active,
                 created_at, updated_at, created_by, updated_by)
                VALUES 
                (:name, :slug, :description, :icon_url, :bg_color, :parent_id, :sort_order, :active,
                 :created_at, :updated_at, :created_by, :updated_by)
            ");
            $data['created_at'] = '2025-03-14 07:19:49';
            $data['created_by'] = 'mahranalsarminy';
        }

        $stmt->execute($data);
        $_SESSION['success'] = $id ? 'Category updated successfully' : 'Category added successfully';
        header("Location: categories.php");
        exit;

    } catch (Exception $e) {
        $_SESSION['error'] = 'Error saving category: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang['code']; ?>" dir="<?php echo $lang['dir']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $id ? 'Edit' : 'Add'; ?> Category - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include 'sidebar.php'; ?>

    <div class="main-container ml-64 p-4">
        <main class="container mx-auto mt-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold"><?php echo $id ? 'Edit' : 'Add'; ?> Category</h1>
                <a href="categories.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                    <i class="fas fa-arrow-left mr-2"></i>Back
                </a>
            </div>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <?php 
                    echo $_SESSION['error'];
                    unset($_SESSION['error']);
                    ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow-md p-6">
                <form method="post">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Name</label>
                            <input type="text" name="name" required
                                   value="<?php echo h($category['name'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Slug</label>
                            <input type="text" name="slug"
                                   value="<?php echo h($category['slug'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div class="md:col-span-2">
                                                        <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea name="description" rows="3"
                                      class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo h($category['description'] ?? ''); ?></textarea>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Icon URL</label>
                            <input type="url" name="icon_url"
                                   value="<?php echo h($category['icon_url'] ?? ''); ?>"
                                   class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Background Color</label>
                            <input type="color" name="bg_color"
                                   value="<?php echo h($category['bg_color'] ?? '#ffffff'); ?>"
                                   class="w-full h-10 px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Parent Category</label>
                            <select name="parent_id"
                                    class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">None</option>
                                <?php foreach ($parent_categories as $parent): ?>
                                <option value="<?php echo $parent['id']; ?>"
                                        <?php echo ($category['parent_id'] ?? '') == $parent['id'] ? 'selected' : ''; ?>>
                                    <?php echo h($parent['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Sort Order</label>
                            <input type="number" name="sort_order"
                                   value="<?php echo h($category['sort_order'] ?? '0'); ?>"
                                   class="w-full px-3 py-2 border rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>

                        <div class="md:col-span-2">
                            <label class="flex items-center">
                                <input type="checkbox" name="active" value="1"
                                       <?php echo (!isset($category['active']) || $category['active']) ? 'checked' : ''; ?>
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2">Active</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end mt-6 space-x-3">
                        <a href="categories.php" 
                           class="px-4 py-2 bg-gray-300 text-gray-700 rounded-md hover:bg-gray-400">
                            Cancel
                        </a>
                        <button type="submit" 
                                class="px-4 py-2 bg-blue-500 text-white rounded-md hover:bg-blue-600">
                            <?php echo $id ? 'Update' : 'Add'; ?> Category
                        </button>
                    </div>
                </form>
            </div>

            <?php if ($id): ?>
            <div class="mt-6 bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold mb-4">Preview</h2>
                <div class="flex items-center space-x-4">
                    <?php if ($category['icon_url']): ?>
                    <div class="w-16 h-16 rounded-lg" style="background-color: <?php echo h($category['bg_color']); ?>">
                        <img src="<?php echo h($category['icon_url']); ?>" 
                             alt="" 
                             class="w-full h-full object-contain p-2">
                    </div>
                    <?php endif; ?>
                    <div>
                        <h3 class="font-semibold"><?php echo h($category['name']); ?></h3>
                        <p class="text-sm text-gray-500"><?php echo h($category['description']); ?></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <script>
        // Auto-generate slug from name
        document.querySelector('input[name="name"]').addEventListener('input', function() {
            if (!document.querySelector('input[name="slug"]').value) {
                document.querySelector('input[name="slug"]').value = this.value
                    .toLowerCase()
                    .replace(/[^a-z0-9-]/g, '-')
                    .replace(/-+/g, '-')
                    .replace(/^-|-$/g, '');
            }
        });

        // Preview icon URL
        document.querySelector('input[name="icon_url"]').addEventListener('change', function() {
            const previewSection = document.querySelector('.preview-section');
            if (previewSection && this.value) {
                const img = previewSection.querySelector('img');
                if (img) {
                    img.src = this.value;
                }
            }
        });
    </script>
</body>
</html>