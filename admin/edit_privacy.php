<?php
define('ROOT_DIR', dirname(__DIR__));
require_once ROOT_DIR . '/includes/init.php';
require_admin();

$error_message = '';
$success_message = '';
$section = null;

// Get section ID from URL
$section_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $content = $_POST['content'];
    $sort_order = (int)$_POST['sort_order'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if (empty($title)) {
        $error_message = "Title is required.";
    } elseif (empty($content)) {
        $error_message = "Content is required.";
    } else {
        try {
            $stmt = $pdo->prepare("
                UPDATE privacy_content 
                SET title = ?, content = ?, sort_order = ?, is_active = ?
                WHERE id = ?
            ");
            
            $stmt->execute([$title, $content, $sort_order, $is_active, $section_id]);
            $success_message = "Section updated successfully!";
            
            // Refresh section data
            $stmt = $pdo->prepare("SELECT * FROM privacy_content WHERE id = ?");
            $stmt->execute([$section_id]);
            $section = $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            $error_message = "Error updating section. Please try again.";
        }
    }
}

// Fetch section details
try {
    $stmt = $pdo->prepare("SELECT * FROM privacy_content WHERE id = ?");
    $stmt->execute([$section_id]);
    $section = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$section) {
        die("Section not found.");
    }
} catch (PDOException $e) {
    error_log("Database Error: " . $e->getMessage());
    die("Error fetching section details.");
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang['code']; ?>" dir="<?php echo $lang['dir']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Privacy Section - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.tiny.cloud/1/89a26gz76i0oxsdzd5o6zakeqzyyejsuqc1ta3qip1um7icg/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <style>
        .tox-tinymce {
            border-radius: 0.375rem;
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php include 'sidebar.php'; ?>

    <div class="main-container ml-64 p-4">
        <main class="container mx-auto mt-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold">Edit Privacy Section</h1>
                <a href="/admin/privacy.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                    Back to Privacy Policy
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

            <div class="bg-white rounded-lg shadow-lg p-6">
                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Title</label>
                        <input type="text" name="title" 
                               value="<?php echo htmlspecialchars($section['title']); ?>" 
                               class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-400" 
                               required>
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Content</label>
                        <textarea name="content" id="content" 
                                  class="w-full"><?php echo $section['content']; ?></textarea>
                    </div>

                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Sort Order</label>
                        <input type="number" name="sort_order" 
                               value="<?php echo $section['sort_order']; ?>" 
                               class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-400">
                    </div>

                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" 
                               <?php echo $section['is_active'] ? 'checked' : ''; ?>
                               class="mr-2">
                        <label for="is_active" class="text-gray-700 font-semibold">Active</label>
                    </div>

                    <div class="flex space-x-4">
                        <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
                            Update Section
                        </button>
                        <a href="/admin/privacy.php" class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">
                            Cancel
                        </a>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <script>
        tinymce.init({
            selector: '#content',
            plugins: 'advlist autolink lists link charmap preview anchor searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount',
            toolbar: 'undo redo | blocks | bold italic forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
            content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; }',
            height: 400,
            directionality: '<?php echo $lang['dir']; ?>',
            setup: function(editor) {
                editor.on('change', function() {
                    editor.save();
                });
            }
        });
    </script>
</body>
</html>