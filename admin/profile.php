<?php
/**
 * Profile Management
 *
 * @package WallPix
 * @version 1.0.0
 */

// Enable error reporting
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include the Composer autoload file
require_once '../../vendor/autoload.php';

// Load environment variables from the .env file
$dotenv = Dotenv\Dotenv::createImmutable('/home/uniplat/public_html');
$dotenv->load();

// Set page title
$pageTitle = "Profile Management";

// Include header and sidebar
require_once '../../theme/admin/header.php';
require_once '../../theme/admin/slidbar.php';

// Initialize message variables
$successMessage = '';
$errorMessage = '';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check action type
    if (isset($_POST['action'])) {
        // Connect to database
        $db = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
        if ($db->connect_error) {
            die("Connection failed: " . $db->connect_error);
        }

        switch ($_POST['action']) {
            case 'update_profile':
                // Update profile information
                if (isset($_POST['username']) && !empty($_POST['username']) &&
                    isset($_POST['email']) && !empty($_POST['email']) &&
                    isset($_POST['full_name']) && !empty($_POST['full_name'])) {
                    
                    $username = trim($db->real_escape_string($_POST['username']));
                    $email = trim($db->real_escape_string($_POST['email']));
                    $full_name = trim($db->real_escape_string($_POST['full_name']));
                    $bio = isset($_POST['bio']) ? trim($db->real_escape_string($_POST['bio'])) : '';

                    $query = "UPDATE users SET username = '$username', email = '$email', full_name = '$full_name', bio = '$bio' WHERE id = {$_SESSION['user_id']}";
                    
                    if ($db->query($query)) {
                        $successMessage = "Profile updated successfully!";
                    } else {
                        $errorMessage = "Failed to update profile: " . $db->error;
                    }
                } else {
                    $errorMessage = "All fields are required!";
                }
                break;

            case 'change_password':
                // Change password
                if (isset($_POST['current_password']) && !empty($_POST['current_password']) &&
                    isset($_POST['new_password']) && !empty($_POST['new_password']) &&
                    isset($_POST['confirm_password']) && !empty($_POST['confirm_password'])) {

                    $current_password = trim($db->real_escape_string($_POST['current_password']));
                    $new_password = trim($db->real_escape_string($_POST['new_password']));
                    $confirm_password = trim($db->real_escape_string($_POST['confirm_password']));

                    if ($new_password === $confirm_password) {
                        // Verify current password
                        $query = "SELECT password FROM users WHERE id = {$_SESSION['user_id']}";
                        $result = $db->query($query);
                        if ($result && $result->num_rows > 0) {
                            $row = $result->fetch_assoc();
                            if (password_verify($current_password, $row['password'])) {
                                // Update password
                                $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
                                $update_query = "UPDATE users SET password = '$new_password_hash' WHERE id = {$_SESSION['user_id']}";
                                if ($db->query($update_query)) {
                                    $successMessage = "Password changed successfully!";
                                } else {
                                    $errorMessage = "Failed to change password: " . $db->error;
                                }
                            } else {
                                $errorMessage = "Current password is incorrect!";
                            }
                        } else {
                            $errorMessage = "User not found!";
                        }
                    } else {
                        $errorMessage = "New password and confirm password do not match!";
                    }
                } else {
                    $errorMessage = "All fields are required!";
                }
                break;
        }

        $db->close();
    }
}

// Fetch user information
$db = new mysqli($_ENV['DB_HOST'], $_ENV['DB_USER'], $_ENV['DB_PASSWORD'], $_ENV['DB_NAME']);
if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

$query = "SELECT username, email, full_name, bio FROM users WHERE id = {$_SESSION['user_id']}";
$result = $db->query($query);
$user = [];

if ($result) {
    $user = $result->fetch_assoc();
    $result->free();
}

$db->close();

// Current date and time info as provided
$currentDateTime = '2025-03-21 17:45:01'; // UTC
$currentUser = 'mahranalsarminy';
?>

<div class="content-wrapper p-4 sm:ml-64">
    <div class="p-4 border-2 border-gray-200 rounded-lg dark:border-gray-700 mt-14">
        <div class="flex justify-between items-center mb-4">
            <h1 class="text-2xl font-semibold text-gray-800 dark:text-white">
                <i class="fas fa-user-circle mr-2"></i> Profile Management
            </h1>
            <div class="flex items-center text-sm text-gray-600 dark:text-gray-400">
                <i class="fas fa-clock mr-1"></i>
                <span><?php echo htmlspecialchars($currentDateTime); ?> (UTC)</span>
            </div>
        </div>

        <?php if ($successMessage): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                <p><?php echo htmlspecialchars($successMessage); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                <p><?php echo htmlspecialchars($errorMessage); ?></p>
            </div>
        <?php endif; ?>

        <!-- Update Profile Form -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 mb-6">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Update Profile</h2>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="space-y-4">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="username" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Username</label>
                        <input type="text" id="username" name="username" required value="<?php echo htmlspecialchars($user['username']); ?>"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="Username">
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                        <input type="email" id="email" name="email" required value="<?php echo htmlspecialchars($user['email']); ?>"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="Email">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label for="full_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Full Name</label>
                        <input type="text" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($user['full_name']); ?>"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="Full Name">
                    </div>
                    
                    <div>
                        <label for="bio" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Bio</label>
                        <textarea id="bio" name="bio" rows="3"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="Tell us about yourself"><?php echo htmlspecialchars($user['bio']); ?></textarea>
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <i class="fas fa-save mr-2"></i>Save Profile
                    </button>
                </div>
            </form>
        </div>

        <!-- Change Password Form -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4">
            <h2 class="text-lg font-semibold text-gray-800 dark:text-white mb-4">Change Password</h2>
            
            <form method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" class="space-y-4">
                <input type="hidden" name="action" value="change_password">
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Current Password</label>
                        <input type="password" id="current_password" name="current_password" required 
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="Current Password">
                    </div>
                    
                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">New Password</label>
                        <input type="password" id="new_password" name="new_password" required 
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="New Password">
                    </div>
                    
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirm Password</label>
                        <input type="password" id="confirm_password" name="confirm_password" required 
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            placeholder="Confirm Password">
                    </div>
                </div>

                <div class="mt-4">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <i class="fas fa-lock mr-2"></i>Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Include footer -->
<?php require_once '../../theme/admin/footer.php'; ?>