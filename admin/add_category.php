<?php
// Define the project root directory
define('ROOT_DIR', dirname(__DIR__));

// Include the centralized initialization file
require_once ROOT_DIR . '/includes/init.php';

// Ensure only admins can access this page
require_admin();


$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_name = trim($_POST['category_name']);

    if (empty($category_name)) {
        $error_message = "Please enter a valid category name.";
    } else {
        // Check if the category already exists
        $stmt_check = $pdo->prepare("SELECT id FROM categories WHERE name = ?");
        $stmt_check->execute([$category_name]);
        if ($stmt_check->fetch()) {
            $error_message = "This category already exists.";
        } else {
            // Insert the new category
            $stmt_insert = $pdo->prepare("INSERT INTO categories (name, created_at) VALUES (?, NOW())");
            $stmt_insert->execute([$category_name]);
            $success_message = "Category added successfully!";
        }
    }
}
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
    </style>
</head>
<body class="bg-gray-100">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <div class="main-container">

    <main class="container mx-auto mt-8 p-6 bg-white rounded-lg shadow-md">
        <h1 class="text-3xl font-bold text-center">Add New Category</h1>

        <?php if (!empty($error_message)): ?>
            <div class="bg-red-200 text-red-800 p-4 mb-4"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="bg-green-200 text-green-800 p-4 mb-4"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <form method="POST" action="/admin/add_category.php" class="grid grid-cols-1 gap-4">
            <div>
                <label class="block text-gray-700">Category Name</label>
                <input type="text" name="category_name" class="w-full p-2 border rounded" required>
            </div>
            <button type="submit" class="w-full bg-blue-500 text-white p-2 rounded">Add Category</button>
        </form>
    </main>

</body>
</html>