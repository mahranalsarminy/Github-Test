<?php
define('ROOT_DIR', dirname(__DIR__));
require_once ROOT_DIR . '/includes/init.php';
require_admin();

$page_title = "Manage Tags";

// Handle form submission for adding a new tag
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_tag'])) {
    $tagName = trim($_POST['tagName']);

    if (!empty($tagName)) {
        $tagSlug = strtolower(preg_replace('/[^a-zA-Z0-9]+/', '-', $tagName));
        
        // Check if tag already exists
        $stmt = $pdo->prepare("SELECT id FROM tags WHERE slug = ?");
        $stmt->execute([$tagSlug]);
        $tag = $stmt->fetch();

        if ($tag) {
            $_SESSION['error'] = 'Tag already exists.';
        } else {
            // Insert new tag
            $stmt = $pdo->prepare("INSERT INTO tags (name, slug, created_by) VALUES (?, ?, ?)");
            $stmt->execute([$tagName, $tagSlug, $current_user]);

            $_SESSION['success'] = 'Tag added successfully.';
        }
    } else {
        $_SESSION['error'] = 'Please enter a tag name.';
    }

    header("Location: tags.php");
    exit;
}

// Handle tag deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_tag'])) {
    $tagId = $_POST['tag_id'];

    // Delete tag
    $stmt = $pdo->prepare("DELETE FROM tags WHERE id = ?");
    $stmt->execute([$tagId]);

    $_SESSION['success'] = 'Tag deleted successfully.';
    header("Location: tags.php");
    exit;
}

// Fetch tags and their usage count
$stmt = $pdo->query("
    SELECT t.id, t.name, t.slug, COUNT(mt.tag_id) as usage_count
    FROM tags t
    LEFT JOIN media_tags mt ON t.id = mt.tag_id
    GROUP BY t.id, t.name, t.slug
    ORDER BY t.name ASC
");
$tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        /* Make sure the page takes full height */
        html, body {
            height: 100%;
            margin: 0;
        }

        body {
            display: flex;
            flex-direction: column;
        }

        main {
            flex: 1;
        }

        /* Tag count styling */
        .tag-count {
            background-color: #f3f4f6;
            border-radius: 9999px;
            padding: 0.25rem 0.5rem;
            font-size: 0.75rem;
            line-height: 1rem;
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 dark:text-white">
    <?php include 'theme/header.php'; ?>

    <div class="flex flex-1 flex-col md:flex-row">
        <aside class="md:w-64 w-full md:block hidden">
            <?php include 'theme/sidebar.php'; ?>
        </aside>

        <main class="flex-1 p-4 mt-16 w-full">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 w-full">
                <h1 class="text-2xl font-bold mb-4 text-center">Manage Tags</h1>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-300 px-4 py-3 rounded relative mb-4">
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-300 px-4 py-3 rounded relative mb-4">
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>

                <!-- Tag Search and Add Form -->
                <div class="mb-4">
                    <input type="text" id="tagSearch" placeholder="Search tags..." class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm mb-2">
                    <form id="addTagForm" action="tags.php" method="POST">
                        <div class="flex space-x-4">
                            <input type="text" name="tagName" placeholder="New tag name" class="block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            <button type="submit" name="add_tag" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 dark:bg-blue-700 dark:hover:bg-blue-800">Add Tag</button>
                        </div>
                    </form>
                </div>

                <table class="min-w-full bg-white dark:bg-gray-800">
                    <thead>
                        <tr>
                            <th class="py-2 px-4 border-b border-gray-300 dark:border-gray-700 text-left">Tag Name</th>
                            <th class="py-2 px-4 border-b border-gray-300 dark:border-gray-700 text-left">Usage Count</th>
                            <th class="py-2 px-4 border-b border-gray-300 dark:border-gray-700 text-left">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="tagTableBody">
                        <?php foreach ($tags as $tag): ?>
                        <tr>
                            <td class="py-2 px-4 border-b border-gray-300 dark:border-gray-700"><?php echo htmlspecialchars($tag['name']); ?></td>
                            <td class="py-2 px-4 border-b border-gray-300 dark:border-gray-700">
                                <span class="tag-count"><?php echo $tag['usage_count']; ?></span>
                            </td>
                            <td class="py-2 px-4 border-b border-gray-300 dark:border-gray-700">
                                <a href="edit_tag.php?id=<?php echo $tag['id']; ?>" class="text-blue-600 dark:text-blue-400 hover:underline"><i class="fa fa-edit"></i> Edit</a>
                                <form action="tags.php" method="POST" style="display:inline;">
                                    <input type="hidden" name="tag_id" value="<?php echo $tag['id']; ?>">
                                    <button type="submit" name="delete_tag" class="text-red-600 dark:text-red-400 hover:underline ml-4" onclick="return confirm('Are you sure you want to delete this tag?');"><i class="fa fa-trash"></i> Delete</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('tagSearch').addEventListener('input', function() {
            const searchQuery = this.value.toLowerCase();
            const tags = document.querySelectorAll('#tagTableBody tr');
            tags.forEach(tag => {
                const tagName = tag.querySelector('td').textContent.toLowerCase();
                if (tagName.includes(searchQuery)) {
                    tag.style.display = '';
                } else {
                    tag.style.display = 'none';
                }
            });
        });

        document.getElementById('addTagForm').addEventListener('submit', function(e) {
            const tagName = this.elements['tagName'].value.trim();
            if (!tagName) {
                e.preventDefault();
                alert('Please enter a tag name.');
            }
        });
    </script>
</body>
</html>

<?php include 'theme/footer.php'; ?>
