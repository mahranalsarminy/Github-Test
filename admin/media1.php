<?php
// Define the project root directory
define('ROOT_DIR', dirname(__DIR__));

// Include the centralized initialization file
require_once ROOT_DIR . '/includes/init.php';

// Ensure only admins can access this page
require_admin();

// Handle media actions
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_media'])) {
        $media_id = $_POST['media_id'];
        $stmt = $pdo->prepare("DELETE FROM media WHERE id = ?");
        $stmt->execute([$media_id]);
        $success_message = "Media deleted successfully!";
    }
}

// Fetch all media
$stmt = $pdo->query("SELECT * FROM media ORDER BY created_at DESC");
$media_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Media - WallPix Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        .main-container {
            margin-left: 250px; /* Adjust this value based on the width of your sidebar */
            padding: 16px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            text-align: center;
            padding: 8px;
        }
        th {
            background-color: #f2f2f2;
        }
        tr:nth-child(even) {
            background-color: #f9f9f9;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <div class="main-container">

    <main class="container mx-auto mt-8 p-6 bg-white rounded-lg shadow-md">
        <h1 class="text-3xl font-bold text-center">Manage Media</h1>

        <?php if (!empty($error_message)): ?>
            <div class="bg-red-200 text-red-800 p-4 mb-4"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="bg-green-200 text-green-800 p-4 mb-4"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <!-- Add New Media Button -->
        <div class="mb-4">
            <a href="/admin/add_media.php" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">
                Add New Media
            </a>
        </div>
        
        <table class="w-full border-collapse">
            <thead>
                <tr class="bg-gray-200">
                    <th class="p-2">Title</th>
                    <th class="p-2">Type</th>
                    <th class="p-2">Views</th>
                    <th class="p-2">Downloads</th>
                    <th class="p-2">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($media_list as $media): ?>
                    <tr class="border-b">
                        <td class="p-2"><?php echo htmlspecialchars($media['title']); ?></td>
                        <td class="p-2"><?php echo ucfirst(htmlspecialchars($media['type'])); ?></td>
                        <td class="p-2"><?php echo htmlspecialchars($media['views']); ?></td>
                        <td class="p-2"><?php echo htmlspecialchars($media['downloads']); ?></td>
                        <td class="p-2 flex justify-center gap-2">
                            <!-- View Button -->
                            <a href="/media.php?id=<?php echo htmlspecialchars($media['id']); ?>" 
                               class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">
                                View
                            </a>

                            <!-- Edit Button -->
                            <a href="/admin/edit_media.php?id=<?php echo htmlspecialchars($media['id']); ?>" 
                               class="bg-yellow-500 text-white p-2 rounded hover:bg-yellow-600">
                                Edit
                            </a>

                            <!-- Delete Button -->
                            <form method="POST" action="/admin/media.php" class="inline">
                                <input type="hidden" name="media_id" value="<?php echo htmlspecialchars($media['id']); ?>">
                                <button type="submit" name="delete_media" 
                                        class="bg-red-500 text-white p-2 rounded hover:bg-red-600"
                                        onclick="return confirm('Are you sure you want to delete this media?');">
                                    Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>

</html>