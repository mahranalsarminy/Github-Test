<?php
require_once(__DIR__ . '/includes/init.php');
require_once(__DIR__ . '/includes/db.php');

// Ensure the user is logged in
require_login();

// Generate a simple math question for validation
$number1 = rand(1, 10);
$number2 = rand(1, 10);
$correct_answer = $number1 + $number2;

// Store the correct answer in the session for validation
$_SESSION['correct_answer'] = $correct_answer;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_answer = $_POST['answer'];
    $userId = $_SESSION['user_id'];

    if ($user_answer == $_SESSION['correct_answer']) {
        // Delete user from the database
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$userId]);

        // Destroy session and redirect to the homepage
        session_destroy();
        header("Location: login");
        exit();
    } else {
        $error_message = "Incorrect answer. Please try again.";
    }
}

function safe_echo($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Account - Media Platform</title>
    
    <!-- Stylesheets -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/custom.css" rel="stylesheet">
    
    <style>
        .delete-card {
            background-color: #f0f4f8;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .dark-mode .delete-card {
            background-color: #2d3748;
            color: #edf2f7;
            box-shadow: 0 4px 6px rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body class="bg-gray-50 dark:bg-gray-900 dark:text-gray-200">
    <?php include 'templates/header.php'; ?>

    <div class="container mx-auto px-4 py-12">
        <div class="delete-card mx-auto max-w-md">
            <h2 class="text-2xl font-bold mb-4">Delete Account</h2>
            <p class="mb-4">To delete your account, please solve the following equation:</p>
            <form method="POST" action="delete-user.php">
                <label for="equation" class="block text-lg mb-2"><?php echo safe_echo($number1); ?> + <?php echo safe_echo($number2); ?> = </label>
                <input type="number" id="equation" name="answer" class="w-full p-2 mb-4 border border-gray-300 rounded">
                <?php if (isset($error_message)): ?>
                    <p class="text-red-500 mb-4"><?php echo safe_echo($error_message); ?></p>
                <?php endif; ?>
                <button type="submit" class="w-full bg-red-500 hover:bg-red-600 text-white font-bold py-2 px-4 rounded">Confirm Deletion</button>
            </form>
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>
</html>