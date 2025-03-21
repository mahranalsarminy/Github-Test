<?php
define('ROOT_DIR', dirname(__DIR__));
require_once ROOT_DIR . '/includes/init.php';
require_admin();

// Handle section actions
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_section'])) {
        $section_id = (int)$_POST['section_id'];
        
        // Delete section from database
        $stmt = $pdo->prepare("DELETE FROM privacy_content WHERE id = ?");
        $stmt->execute([$section_id]);
        $success_message = "Section deleted successfully!";
    } elseif (isset($_POST['toggle_status'])) {
        $section_id = (int)$_POST['section_id'];
        $new_status = (int)$_POST['new_status'];
        
        $stmt = $pdo->prepare("UPDATE privacy_content SET is_active = ? WHERE id = ?");
        $stmt->execute([$new_status, $section_id]);
        $success_message = "Section status updated successfully!";
    }
}

// Fetch all sections
$stmt = $pdo->query("SELECT * FROM privacy_content ORDER BY sort_order ASC");
$privacy_sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="<?php echo $lang['code']; ?>" dir="<?php echo $lang['dir']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Privacy Content - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include 'sidebar.php'; ?>

    <div class="main-container ml-64 p-4">
        <main class="container mx-auto mt-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold">Manage Privacy Policy</h1>
                <a href="/admin/add_privacy.php" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition duration-200">
                    <i class="fas fa-plus mr-2"></i>Add New Section
                </a>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                    <p class="font-bold">Error</p>
                    <p><?php echo htmlspecialchars($error_message); ?></p>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                    <p class="font-bold">Success</p>
                    <p><?php echo htmlspecialchars($success_message); ?></p>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600 uppercase text-sm leading-normal">
                            <th class="py-3 px-6 text-left">Title</th>
                            <th class="py-3 px-6 text-left">Content Preview</th>
                            <th class="py-3 px-6 text-center">Order</th>
                            <th class="py-3 px-6 text-center">Status</th>
                            <th class="py-3 px-6 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="text-gray-600">
                        <?php foreach ($privacy_sections as $section): ?>
                            <tr class="border-b border-gray-200 hover:bg-gray-50">
                                <td class="py-4 px-6">
                                    <div class="font-medium">
                                        <?php echo htmlspecialchars($section['title']); ?>
                                    </div>
                                </td>
                                <td class="py-4 px-6">
                                    <?php 
                                    $content_preview = strip_tags($section['content']);
                                    echo htmlspecialchars(mb_substr($content_preview, 0, 100)) . 
                                         (mb_strlen($content_preview) > 100 ? '...' : ''); 
                                    ?>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <?php echo htmlspecialchars($section['sort_order']); ?>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="section_id" value="<?php echo $section['id']; ?>">
                                        <input type="hidden" name="new_status" value="<?php echo $section['is_active'] ? '0' : '1'; ?>">
                                        <button type="submit" name="toggle_status" 
                                                class="<?php echo $section['is_active'] ? 'bg-green-500 hover:bg-green-600' : 'bg-gray-500 hover:bg-gray-600'; ?> 
                                                       text-white px-3 py-1 rounded-full text-sm transition duration-200">
                                            <i class="fas <?php echo $section['is_active'] ? 'fa-check-circle' : 'fa-times-circle'; ?> mr-1"></i>
                                            <?php echo $section['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </button>
                                    </form>
                                </td>
                                <td class="py-4 px-6 text-center">
                                    <div class="flex justify-center space-x-2">
                                        <a href="/admin/edit_privacy.php?id=<?php echo $section['id']; ?>" 
                                           class="bg-yellow-500 text-white px-3 py-1 rounded hover:bg-yellow-600 transition duration-200">
                                            <i class="fas fa-edit mr-1"></i> Edit
                                        </a>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="section_id" value="<?php echo $section['id']; ?>">
                                            <button type="submit" name="delete_section" 
                                                    class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600 transition duration-200"
                                                    onclick="return confirm('Are you sure you want to delete this section?');">
                                                <i class="fas fa-trash-alt mr-1"></i> Delete
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
</body>
</html>