<?php
require_once '../includes/init.php';

// Get all categories with their details
$stmt = $pdo->query("
    SELECT c.*, 
           p.name as parent_name,
           (SELECT COUNT(*) FROM media WHERE category_id = c.id) as items_count
    FROM categories c
    LEFT JOIN categories p ON c.parent_id = p.id
    ORDER BY c.sort_order, c.name
");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle Activation/Deactivation
if (isset($_POST['toggle_status'])) {
    $id = (int)$_POST['id'];
    $new_status = (int)$_POST['status'];
    
    try {
        $stmt = $pdo->prepare("
            UPDATE categories 
            SET active = ?, 
                updated_at = ?, 
                updated_by = ?
            WHERE id = ?
        ");
        
        $stmt->execute([$new_status, '2025-03-14 07:19:49', 'mahranalsarminy', $id]);
        $_SESSION['success'] = 'Category status updated successfully';
    } catch (Exception $e) {
        $_SESSION['error'] = 'Error updating category status';
    }
    
    header("Location: categories.php");
    exit;
}

// Handle Delete
if (isset($_POST['delete'])) {
    $id = (int)$_POST['id'];
    
    try {
        // Check for items in category
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM media WHERE category_id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Cannot delete category that has items');
        }
        
        // Delete category
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $_SESSION['success'] = 'Category deleted successfully';
    } catch (Exception $e) {
        $_SESSION['error'] = $e->getMessage();
    }
    
    header("Location: categories.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $lang['code']; ?>" dir="<?php echo $lang['dir']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include 'sidebar.php'; ?>

    <div class="main-container ml-64 p-4">
        <main class="container mx-auto mt-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-2xl font-bold">Manage Categories</h1>
                <a href="category_edit.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    <i class="fas fa-plus mr-2"></i>Add Category
                </a>
            </div>

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

            <div class="bg-white rounded-lg shadow-md overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Icon</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Parent</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        <?php foreach ($categories as $category): ?>
                        <tr>
                            <td class="px-6 py-4">
                                <?php if ($category['icon_url']): ?>
                                    <div class="w-10 h-10 rounded-lg" style="background-color: <?php echo h($category['bg_color']); ?>">
                                        <img src="<?php echo h($category['icon_url']); ?>" 
                                             alt="" class="w-full h-full object-contain p-1">
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-gray-900"><?php echo h($category['name']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo h($category['slug']); ?></div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?php echo h($category['parent_name'] ?? '-'); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?php echo number_format($category['items_count']); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-500">
                                <?php echo $category['sort_order']; ?>
                            </td>
                            <td class="px-6 py-4">
                                <form method="post" class="inline">
                                    <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                    <input type="hidden" name="status" value="<?php echo $category['active'] ? 0 : 1; ?>">
                                    <button type="submit" name="toggle_status" class="px-3 py-1 rounded text-sm font-semibold
                                        <?php echo $category['active'] 
                                            ? 'bg-green-100 text-green-800 hover:bg-green-200' 
                                            : 'bg-red-100 text-red-800 hover:bg-red-200'; ?>">
                                        <?php echo $category['active'] ? 'Active' : 'Inactive'; ?>
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-4 text-sm font-medium">
                                <a href="category_edit.php?id=<?php echo $category['id']; ?>" 
                                   class="text-blue-600 hover:text-blue-900 mr-3">
                                    <i class="fas fa-edit"></i>
                                </a>
                                
                                <form method="post" class="inline" onsubmit="return confirm('Are you sure you want to delete this category?');">
                                    <input type="hidden" name="id" value="<?php echo $category['id']; ?>">
                                    <button type="submit" name="delete" 
                                            class="text-red-600 hover:text-red-900"
                                            <?php echo $category['items_count'] > 0 ? 'disabled' : ''; ?>>
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>