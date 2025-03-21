<?php
// Define the project root directory
define('ROOT_DIR', dirname(__DIR__));

// Include the centralized initialization file
require_once ROOT_DIR . '/includes/init.php';

// Ensure only admins can access this page
require_admin();

// Fetch all users
$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$user_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - WallPix Admin</title>
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
            text-align: center; /* Center table content */
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
    <!-- Main Content -->
    <div class="flex-1 p-10 ml-64">
        <h2 class="text-3xl font-bold mb-4">Manage Users</h2>
        <div class="bg-white p-6 rounded-lg shadow-md">
            <!-- Add New User Button -->
                    <!-- Add New User Button -->
        <div class="mb-4">
            <a href="/admin/add_user.php" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">
                Add New User
            </a>
        </div>
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="p-2">ID</th>
                        <th class="p-2">Email</th>
                        <th class="p-2">Role</th>
                        <th class="p-2">Created At</th>
                        <th class="p-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($user_list as $user): ?>
                        <tr class="border-b">
                            <td class="p-2"><?php echo htmlspecialchars($user['id']); ?></td>
                            <td class="p-2"><?php echo htmlspecialchars($user['email']); ?></td>
                            <td class="p-2"><?php echo htmlspecialchars($user['role']); ?></td>
                            <td class="p-2"><?php echo htmlspecialchars($user['created_at']); ?></td>
                            <td class="p-2">
                                <a href="/admin/edit_user.php?id=<?php echo $user['id']; ?>" class="bg-blue-500 text-white px-4 py-2 rounded">Edit</a>
                                <a href="/admin/delete_user.php?id=<?php echo $user['id']; ?>" class="bg-red-500 text-white px-4 py-2 rounded">Delete</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>