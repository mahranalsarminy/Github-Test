<?php
// Define the project root directory
define('ROOT_DIR', dirname(__DIR__));

// Include the centralized initialization file
require_once ROOT_DIR . '/includes/init.php';

// Ensure only admins can access this page
require_admin();

$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch the user details
$stmt_user = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt_user->execute([$user_id]);
$user = $stmt_user->fetch();

if (!$user) {
    die("User not found.");
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $role = $_POST['role'];

    if (empty($email)) {
        $error_message = "Please enter a valid email.";
    } else {
        // Update the user's email and role
        $stmt_update = $pdo->prepare("UPDATE users SET email = ?, role = ? WHERE id = ?");
        $stmt_update->execute([$email, $role, $user_id]);

        // If a new password is provided, update it
        if (!empty($password)) {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $stmt_password = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt_password->execute([$hashed_password, $user_id]);
        }

        $success_message = "User updated successfully!";
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
        <h1 class="text-3xl font-bold text-center">Edit User</h1>

        <?php if (!empty($error_message)): ?>
            <div class="bg-red-200 text-red-800 p-4 mb-4"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <?php if (!empty($success_message)): ?>
            <div class="bg-green-200 text-green-800 p-4 mb-4"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <form method="POST" action="/admin/edit_user.php?id=<?php echo $user_id; ?>" class="grid grid-cols-1 gap-4">
            <div>
                <label class="block text-gray-700">Email</label>
                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="w-full p-2 border rounded" required>
            </div>
            <div>
                <label class="block text-gray-700">New Password (Leave blank to keep current)</label>
                <input type="password" name="password" class="w-full p-2 border rounded">
            </div>
            <div>
                <label class="block text-gray-700">Role</label>
                <select name="role" class="w-full p-2 border rounded" required>
                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                    <option value="subscriber" <?php echo $user['role'] === 'subscriber' ? 'selected' : ''; ?>>Subscriber</option>
                    <option value="free_user" <?php echo $user['role'] === 'free_user' ? 'selected' : ''; ?>>Free User</option>
                </select>
            </div>
            <button type="submit" class="w-full bg-blue-500 text-white p-2 rounded">Update User</button>
        </form>
    </main>
</body>
</html>