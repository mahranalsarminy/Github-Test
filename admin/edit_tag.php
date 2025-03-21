<?php
define('ROOT_DIR', dirname(__DIR__));
require_once ROOT_DIR . '/includes/init.php';
require_admin();

$tagId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// إعداد سجل الأخطاء
ini_set('log_errors', 1);
ini_set('error_log', ROOT_DIR . '/logs/php_errors.log');
error_reporting(E_ALL); // عرض جميع أنواع الأخطاء

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tagName = trim($_POST['tagName']);

    if (!empty($tagName)) {
        $tagSlug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $tagName));

        try {
            // Check if tag already exists with a different ID
            $stmt = $pdo->prepare("SELECT id FROM tags WHERE slug = ? AND id != ?");
            $stmt->execute([$tagSlug, $tagId]);
            $tag = $stmt->fetch();

            if ($tag) {
                $_SESSION['error'] = 'Tag already exists.';
            } else {
                // Update tag
                $stmt = $pdo->prepare("UPDATE tags SET name = ?, slug = ? WHERE id = ?");
                $stmt->execute([$tagName, $tagSlug, $tagId]);

                $_SESSION['success'] = 'Tag updated successfully.';
                header("Location: tags.php");
                exit;
            }
        } catch (Exception $e) {
            error_log("Error updating tag: " . $e->getMessage());
            $_SESSION['error'] = 'An error occurred while updating the tag.';
            header("Location: edit_tag.php?id=$tagId");
            exit;
        }
    } else {
        $_SESSION['error'] = 'Please enter a tag name.';
        header("Location: edit_tag.php?id=$tagId");
        exit;
    }
} else {
    try {
        // Fetch tag details
        $stmt = $pdo->prepare("SELECT * FROM tags WHERE id = ?");
        $stmt->execute([$tagId]);
        $tag = $stmt->fetch();

        if (!$tag) {
            $_SESSION['error'] = 'Tag not found.';
            header("Location: tags.php");
            exit;
        }
    } catch (Exception $e) {
        error_log("Error fetching tag details: " . $e->getMessage());
        $_SESSION['error'] = 'An error occurred while fetching the tag details.';
        header("Location: tags.php");
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Tag</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100 dark:bg-gray-900 dark:text-white">
    <?php include 'theme/header.php'; ?>

    <div class="flex flex-1 flex-col md:flex-row">
        <aside class="md:w-64 w-full md:block hidden">
            <?php include 'theme/sidebar.php'; ?>
        </aside>

        <main class="flex-1 p-4 mt-16 w-full">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 w-full">
                <h1 class="text-2xl font-bold mb-4 text-center">Edit Tag</h1>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-300 px-4 py-3 rounded relative mb-4">
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-300 px-4 py-3 rounded relative mb-4">
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                    </div>
                <?php endif; ?>

                <form action="" method="post" class="space-y-6">
                    <div>
                        <label for="tagName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Tag Name</label>
                        <input type="text" name="tagName" id="tagName" value="<?php echo htmlspecialchars($tag['name']); ?>" required class="block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                    </div>

                    <div class="mt-6 text-center">
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 dark:bg-blue-700 dark:hover:bg-blue-800">Update Tag</button>
                    </div>
                </form>
            </div>
        </main>
    </div>

<?php include 'theme/footer.php'; ?>
</body>
</html>