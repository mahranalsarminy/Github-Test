<?php
define('ROOT_DIR', dirname(__DIR__));
require_once ROOT_DIR . '/includes/init.php';
require_admin();

$page_title = "Manage Media";

// Fetch media records
$stmt = $pdo->query("
    SELECT media.*, categories.name as category_name 
    FROM media 
    LEFT JOIN categories ON media.category_id = categories.id
    ORDER BY media.created_at DESC
");
$media_records = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch categories for filter dropdown
$stmt = $pdo->query("
    SELECT id, name 
    FROM categories 
    WHERE is_active = 1 
    ORDER BY name ASC
");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle status update
if (isset($_POST['update_status'])) {
    $id = $_POST['id'];
    $status = $_POST['status'];
    $stmt = $pdo->prepare("UPDATE media SET status = ? WHERE id = ?");
    $stmt->execute([$status, $id]);
    header("Location: media.php");
    exit;
}

// Handle feature update
if (isset($_POST['update_feature'])) {
    $id = $_POST['id'];
    $featured = $_POST['featured'];
    $stmt = $pdo->prepare("UPDATE media SET featured = ? WHERE id = ?");
    $stmt->execute([$featured, $id]);
    header("Location: media.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        .thumbnail-container {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            background-color: #f3f4f6;
        }
        .thumbnail-container img {
            max-width: 100%;
            max-height: 100%;
            object-fit: cover;
        }
        .thumbnail-fallback {
            width: 80px;
            height: 80px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #e5e7eb;
            color: #6b7280;
            font-size: 24px;
        }
        .file-icon {
            font-size: 32px;
        }
    </style>
    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            const modeToggle = document.getElementById('mode-toggle');
            const body = document.body;

            if (localStorage.getItem('theme') === 'dark') {
                body.classList.add('dark');
                modeToggle.textContent = 'Light Mode';
            } else {
                body.classList.remove('dark');
                modeToggle.textContent = 'Dark Mode';
            }

            modeToggle.addEventListener('click', () => {
                body.classList.toggle('dark');
                if (body.classList.contains('dark')) {
                    localStorage.setItem('theme', 'dark');
                    modeToggle.textContent = 'Light Mode';
                } else {
                    localStorage.setItem('theme', 'light');
                    modeToggle.textContent = 'Dark Mode';
                }
            });
        });
    </script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 dark:text-white">
    <?php include 'theme/header.php'; ?>
    
    <div class="flex flex-1 flex-col md:flex-row">
        <aside class="md:w-64 w-full md:block hidden">
            <?php include 'theme/sidebar.php'; ?>
        </aside>

        <main class="flex-1 p-4 mt-16 w-full">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 w-full">
                <div class="flex justify-between items-center mb-4">
                    <h1 class="text-2xl font-bold">Manage Media</h1>
                    <a href="add_media.php" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                        <i class="fas fa-plus mr-2"></i> Add Media
                    </a>
                </div>

                <div class="mb-6">
                    <form action="" method="get" class="flex space-x-4">
                        <select name="category" class="block w-1/3 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white">
                            <option value="">All Categories</option>
                            <?php foreach ($categories as $category): ?>
                            <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <input type="text" name="search" placeholder="Search title..." class="block w-1/3 rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-700 dark:text-white">
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700">
                            <i class="fas fa-search mr-2"></i> Search
                        </button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full bg-white dark:bg-gray-800">
                        <thead>
                            <tr>
                                <th class="py-2 px-4 border-b-2 border-gray-200 dark:border-gray-700">Thumbnail</th>
                                <th class="py-2 px-4 border-b-2 border-gray-200 dark:border-gray-700">Title</th>
                                <th class="py-2 px-4 border-b-2 border-gray-200 dark:border-gray-700">Category</th>
                                <th class="py-2 px-4 border-b-2 border-gray-200 dark:border-gray-700">Type</th>
                                <th class="py-2 px-4 border-b-2 border-gray-200 dark:border-gray-700">Status</th>
                                <th class="py-2 px-4 border-b-2 border-gray-200 dark:border-gray-700">Featured</th>
                                <th class="py-2 px-4 border-b-2 border-gray-200 dark:border-gray-700">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($media_records as $media): ?>
                            <tr>
                                <td class="py-2 px-4 border-b border-gray-200 dark:border-gray-700">
                                    <div class="thumbnail-container">
                                        <?php if (!empty($media['file_path']) && file_exists(ROOT_DIR . '/' . $media['file_path'])): ?>
                                            <?php if (strpos($media['file_type'] ?? '', 'image/') === 0): ?>
                                                <!-- Image thumbnail -->
                                                <img src="<?php echo '/' . htmlspecialchars($media['file_path']); ?>" alt="Thumbnail">
                                            <?php elseif (strpos($media['file_type'] ?? '', 'video/') === 0): ?>
                                                <!-- Video icon -->
                                                <i class="fas fa-video file-icon text-blue-500"></i>
                                            <?php elseif (strpos($media['file_type'] ?? '', 'audio/') === 0): ?>
                                                <!-- Audio icon -->
                                                <i class="fas fa-music file-icon text-purple-500"></i>
                                            <?php else: ?>
                                                <!-- Generic file icon -->
                                                <i class="fas fa-file file-icon text-gray-500"></i>
                                            <?php endif; ?>
                                        <?php elseif (!empty($media['external_url'])): ?>
                                            <!-- External URL thumbnail or icon -->
                                            <?php if (preg_match('/\.(jpg|jpeg|png|gif)$/i', $media['external_url'])): ?>
                                                <img src="<?php echo htmlspecialchars($media['external_url']); ?>" alt="External Thumbnail">
                                            <?php else: ?>
                                                <i class="fas fa-link file-icon text-green-500"></i>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <!-- Fallback icon -->
                                            <div class="thumbnail-fallback">
                                                <i class="fas fa-file-alt"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td class="py-2 px-4 border-b border-gray-200 dark:border-gray-700"><?php echo htmlspecialchars($media['title']); ?></td>
                                <td class="py-2 px-4 border-b border-gray-200 dark:border-gray-700"><?php echo htmlspecialchars($media['category_name']); ?></td>
                                <td class="py-2 px-4 border-b border-gray-200 dark:border-gray-700">
                                    <?php 
                                    if (!empty($media['file_type'])) {
                                        echo htmlspecialchars($media['file_type']);
                                    } elseif (!empty($media['external_url'])) {
                                        echo 'External URL';
                                    } else {
                                        echo 'Unknown';
                                    }
                                    ?>
                                </td>
                                <td class="py-2 px-4 border-b border-gray-200 dark:border-gray-700">
                                    <form method="post" action="" class="inline">
                                        <input type="hidden" name="id" value="<?php echo $media['id']; ?>">
                                        <input type="hidden" name="status" value="<?php echo $media['status'] ? 0 : 1; ?>">
                                        <button type="submit" name="update_status" class="text-<?php echo $media['status'] ? 'green' : 'red'; ?>-600 hover:text-<?php echo $media['status'] ? 'green' : 'red'; ?>-900">
                                            <?php echo $media['status'] ? 'Active' : 'Inactive'; ?>
                                        </button>
                                    </form>
                                </td>
                                <td class="py-2 px-4 border-b border-gray-200 dark:border-gray-700">
                                    <form method="post" action="" class="inline">
                                        <input type="hidden" name="id" value="<?php echo $media['id']; ?>">
                                        <input type="hidden" name="featured" value="<?php echo $media['featured'] ? 0 : 1; ?>">
                                        <button type="submit" name="update_feature" class="text-<?php echo $media['featured'] ? 'yellow' : 'gray'; ?>-600 hover:text-<?php echo $media['featured'] ? 'yellow' : 'gray'; ?>-900">
                                            <?php echo $media['featured'] ? 'Yes' : 'No'; ?>
                                        </button>
                                    </form>
                                </td>
                                <td class="py-2 px-4 border-b border-gray-200 dark:border-gray-700">
                                    <a href="edit_media.php?id=<?php echo $media['id']; ?>" class="text-indigo-600 hover:text-indigo-900">
                                        <i class="fas fa-edit mr-2"></i> Edit
                                    </a>
                                    <a href="delete_media.php?id=<?php echo $media['id']; ?>" class="text-red-600 hover:text-red-900 ml-4" onclick="return confirm('Are you sure you want to delete this media?')">
                                        <i class="fas fa-trash-alt mr-2"></i> Delete
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
<?php include 'theme/footer.php'; ?>