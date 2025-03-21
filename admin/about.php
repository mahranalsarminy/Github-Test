<?php
// Define the project root directory
define('ROOT_DIR', dirname(__DIR__));

// Include the centralized initialization file
require_once ROOT_DIR . '/includes/init.php';

// Ensure only admins can access this page
require_admin();

// Handle section actions
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_section'])) {
        $section_id = (int)$_POST['section_id'];
        
        // Delete the associated image first
        $stmt = $pdo->prepare("SELECT image_url FROM about_content WHERE id = ?");
        $stmt->execute([$section_id]);
        $section = $stmt->fetch();
        
        if ($section && $section['image_url'] && file_exists(ROOT_DIR . $section['image_url'])) {
            unlink(ROOT_DIR . $section['image_url']);
        }
        
        // Delete the section from database
        $stmt = $pdo->prepare("DELETE FROM about_content WHERE id = ?");
        $stmt->execute([$section_id]);
        $success_message = "Section deleted successfully!";
    } elseif (isset($_POST['toggle_status'])) {
        $section_id = (int)$_POST['section_id'];
        $new_status = (int)$_POST['new_status'];
        
        $stmt = $pdo->prepare("UPDATE about_content SET is_active = ? WHERE id = ?");
        $stmt->execute([$new_status, $section_id]);
        $success_message = "Section status updated successfully!";
    }
}

// Fetch all about sections
$stmt = $pdo->query("SELECT * FROM about_content ORDER BY sort_order ASC");
$sections_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="<?php echo $lang['code']; ?>" dir="<?php echo $lang['dir']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage About Content - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <div class="main-container ml-64 p-4">
        <main class="container mx-auto mt-8">
            <h1 class="text-3xl font-bold text-center">Manage About Content</h1>

            <?php if (!empty($error_message)): ?>
                <div class="bg-red-200 text-red-800 p-4 mb-4"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="bg-green-200 text-green-800 p-4 mb-4"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <!-- Add New Section Button -->
            <div class="mb-4">
                <a href="/admin/add_about.php" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">
                    <i class="fas fa-plus mr-2"></i>Add New Section
                </a>
            </div>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-200">
                            <th class="p-2 text-left">Title</th>
                            <th class="p-2 text-left">Content Preview</th>
                            <th class="p-2 text-center">Image</th>
                            <th class="p-2 text-center">Order</th>
                            <th class="p-2 text-center">Status</th>
                            <th class="p-2 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($sections_list as $section): ?>
                            <tr class="border-b">
                                <td class="p-2"><?php echo htmlspecialchars($section['title']); ?></td>
                                <td class="p-2">
                                    <?php 
                                    $content_preview = strip_tags($section['content']);
                                    echo htmlspecialchars(substr($content_preview, 0, 100)) . '...'; 
                                    ?>
                                </td>
                                <td class="p-2 text-center">
                                    <?php if ($section['image_url']): ?>
                                        <img src="<?php echo htmlspecialchars($section['image_url']); ?>" 
                                             alt="Section image" 
                                             class="w-16 h-16 object-cover rounded inline-block">
                                    <?php else: ?>
                                        <span class="text-gray-400">No image</span>
                                    <?php endif; ?>
                                </td>
                                <td class="p-2 text-center"><?php echo htmlspecialchars($section['sort_order']); ?></td>
                                <td class="p-2 text-center">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="section_id" value="<?php echo $section['id']; ?>">
                                        <input type="hidden" name="new_status" value="<?php echo $section['is_active'] ? '0' : '1'; ?>">
                                        <button type="submit" name="toggle_status" 
                                                class="<?php echo $section['is_active'] ? 'bg-green-500 hover:bg-green-600' : 'bg-gray-500 hover:bg-gray-600'; ?> text-white px-3 py-1 rounded">
                                            <?php echo $section['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </button>
                                    </form>
                                </td>
                                <td class="p-2 text-center">
                                    <div class="flex justify-center space-x-2">
                                        <a href="/admin/edit_about.php?id=<?php echo $section['id']; ?>" 
                                           class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600">
                                            <i class="fas fa-edit"></i> Edit
                                        </a>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="section_id" value="<?php echo $section['id']; ?>">
                                            <button type="submit" name="delete_section" 
                                                    class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600"
                                                    onclick="return confirm('Are you sure you want to delete this section?');">
                                                <i class="fas fa-trash"></i> Delete
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        // Add any necessary JavaScript here
    </script>
</body>
</html>