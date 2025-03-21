<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Load language file
$lang = require __DIR__ . '/../lang/admin/' . ($_SESSION['language'] ?? 'en') . '.php';

// Handle subscription actions
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_plan'])) {
        $plan_id = $_POST['plan_id'];
        $stmt = $pdo->prepare("DELETE FROM subscriptions WHERE id = ?");
        $stmt->execute([$plan_id]);
        $success_message = "Subscription deleted successfully!";
    }
}

// Fetch all subscriptions
$stmt = $pdo->query("SELECT * FROM subscriptions ORDER BY start_date DESC");
$plan_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subscriptions - WallPix Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .main-container {
            margin-left: 250px; /* Adjust this value based on the width of your sidebar */
            padding: 16px;
            flex: 1;
        }
        .form-container {
            max-width: 800px;
            margin: 0 auto;
        }
        footer {
            background-color: #f8f9fa;
            padding: 16px;
            text-align: center;
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Sidebar -->
    <?php include 'sidebar.php'; ?>

    <div class="main-container flex-1">
        <main class="mt-8 p-6 bg-white rounded-lg shadow-md">
            <h1 class="text-3xl font-bold text-center mb-8">Manage Subscriptions</h1>

            <?php if (!empty($error_message)): ?>
                <div class="bg-red-200 text-red-800 p-4 mb-4"><?php echo htmlspecialchars($error_message); ?></div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="bg-green-200 text-green-800 p-4 mb-4"><?php echo htmlspecialchars($success_message); ?></div>
            <?php endif; ?>

            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="p-2">Plan</th>
                        <th class="p-2">User ID</th>
                        <th class="p-2">Start Date</th>
                        <th class="p-2">End Date</th>
                        <th class="p-2">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($plan_list as $plan): ?>
                        <tr class="border-b">
                            <td class="p-2"><?php echo htmlspecialchars($plan['plan']); ?></td>
                            <td class="p-2"><?php echo htmlspecialchars($plan['user_id']); ?></td>
                            <td class="p-2"><?php echo htmlspecialchars($plan['start_date']); ?></td>
                            <td class="p-2"><?php echo htmlspecialchars($plan['end_date']); ?></td>
                            <td class="p-2">
                                <form method="POST" action="/admin/plans.php" class="inline">
                                    <input type="hidden" name="plan_id" value="<?php echo htmlspecialchars($plan['id']); ?>">
                                    <button type="submit" name="delete_plan" class="bg-red-500 text-white p-2 rounded">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </main>
    </div>

</body>
</html>