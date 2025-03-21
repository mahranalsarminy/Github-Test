<?php
// Set page title
$pageTitle = 'Edit User - WallPix Admin';

// Include header
require_once '../../theme/admin/header.php';

// Get current date and time in UTC
$currentDateTime = '2025-03-17 22:59:07'; // Using the timestamp you provided
$currentUser = 'mahranalsarminy'; // Using the username you provided

// Initialize variables
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$username = '';
$email = '';
$role = '';
$profilePicture = '';
$createdAt = '';
$successMessage = '';
$errorMessage = '';
$errors = [];

// Check if user exists
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();
    
    if (!$user) {
        $errorMessage = "User not found.";
    } else {
        $username = $user['username'];
        $email = $user['email'];
        $role = $user['role'];
        $profilePicture = $user['profile_picture'];
        $createdAt = $user['created_at'];
    }
} catch (PDOException $e) {
    $errorMessage = "Database error: " . $e->getMessage();
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    // Get form data
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'free_user';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Validate input
    if (empty($username)) {
        $errors['username'] = 'Username is required';
    } elseif (strlen($username) < 3) {
        $errors['username'] = 'Username must be at least 3 characters';
    }
    
    if (empty($email)) {
        $errors['email'] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Invalid email format';
    }
    
    // Check if username or email already exists (but not for the current user)
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE (username = ? OR email = ?) AND id != ?");
        $stmt->execute([$username, $email, $userId]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            $stmt = $pdo->prepare("SELECT username, email FROM users WHERE (username = ? OR email = ?) AND id != ?");
            $stmt->execute([$username, $email, $userId]);
            $existingUser = $stmt->fetch();
            
            if ($existingUser && $existingUser['username'] === $username) {
                $errors['username'] = 'Username already exists';
            }
            
            if ($existingUser && $existingUser['email'] === $email) {
                $errors['email'] = 'Email already exists';
            }
        }
    } catch (PDOException $e) {
        $errorMessage = "Database error: " . $e->getMessage();
    }
    
    // Check password if provided
    if (!empty($newPassword)) {
        if (strlen($newPassword) < 6) {
            $errors['new_password'] = 'Password must be at least 6 characters';
        }
        
        if ($newPassword !== $confirmPassword) {
            $errors['confirm_password'] = 'Passwords do not match';
        }
    }
    
    // Handle profile picture upload
    $uploadedProfilePicture = $profilePicture;
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
        $maxSize = 2 * 1024 * 1024; // 2MB
        
        if (!in_array($_FILES['profile_picture']['type'], $allowedTypes)) {
            $errors['profile_picture'] = 'Only JPG, PNG, and GIF images are allowed';
        } elseif ($_FILES['profile_picture']['size'] > $maxSize) {
            $errors['profile_picture'] = 'Image size should not exceed 2MB';
        } else {
            // Generate unique filename
            $fileExtension = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
            $newFilename = uniqid('user_') . '.' . $fileExtension;
            $uploadPath = UPLOAD_DIR . 'profiles/' . $newFilename;
            
            // Create directory if it doesn't exist
            if (!is_dir(UPLOAD_DIR . 'profiles/')) {
                mkdir(UPLOAD_DIR . 'profiles/', 0755, true);
            }
            
            // Move uploaded file
            if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $uploadPath)) {
                $uploadedProfilePicture = UPLOAD_URL . 'profiles/' . $newFilename;
                
                // Delete old profile picture if it exists
                if (!empty($profilePicture) && file_exists($_SERVER['DOCUMENT_ROOT'] . $profilePicture)) {
                    unlink($_SERVER['DOCUMENT_ROOT'] . $profilePicture);
                }
            } else {
                $errors['profile_picture'] = 'Failed to upload image';
            }
        }
    }
    // If no errors, update user
    if (empty($errors)) {
        try {
            // Start building the query
            $sql = "UPDATE users SET username = ?, email = ?, role = ?, profile_picture = ?";
            $params = [$username, $email, $role, $uploadedProfilePicture];
            
            // Add password to update if provided
            if (!empty($newPassword)) {
                // In a production environment, use password_hash() for secure password storage
                // $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
                
                // For this example, we're using plain text (not recommended for production)
                $hashedPassword = $newPassword;
                
                $sql .= ", password = ?";
                $params[] = $hashedPassword;
            }
            
            // Add the WHERE clause and user ID
            $sql .= " WHERE id = ?";
            $params[] = $userId;
            
            // Execute the update
            $stmt = $pdo->prepare($sql);
            $success = $stmt->execute($params);
            
            if ($success) {
                // Log the activity
                $stmt = $pdo->prepare("INSERT INTO activities (description) VALUES (?)");
                $stmt->execute(["User {$username} (ID: {$userId}) was updated by {$currentUser} on {$currentDateTime}"]);
                
                $successMessage = "User successfully updated.";
                
                // Refresh user data
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();
                
                if ($user) {
                    $username = $user['username'];
                    $email = $user['email'];
                    $role = $user['role'];
                    $profilePicture = $user['profile_picture'];
                    $createdAt = $user['created_at'];
                }
            } else {
                $errorMessage = "Error updating user.";
            }
        } catch (PDOException $e) {
            $errorMessage = "Database error: " . $e->getMessage();
        }
    }
}
// Include sidebar
require_once '../../theme/admin/slidbar.php';
?>

<!-- Main Content -->
<div class="content-wrapper min-h-screen bg-gray-100 <?php echo $darkMode ? 'dark-mode' : ''; ?>">
    <div class="px-6 py-8">
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                    <?php echo $lang['edit_user'] ?? 'Edit User'; ?>
                </h1>
                <p class="mt-2 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                    <?php echo $lang['update_user_details'] ?? 'Update user account details'; ?>
                    <span class="ml-2"><?php echo $currentDateTime; ?></span>
                </p>
            </div>
            <div class="mt-4 md:mt-0">
                <a href="index.php" class="btn bg-gray-500 hover:bg-gray-600 text-white">
                    <i class="fas fa-arrow-left mr-2"></i> <?php echo $lang['back_to_users'] ?? 'Back to Users'; ?>
                </a>
            </div>
        </div>
        <?php if (!empty($successMessage)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <span class="font-bold">Success:</span> <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($errorMessage)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <span class="font-bold">Error:</span> <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>
        
        <?php if (!$user): ?>
            <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                <span class="font-bold">Warning:</span> <?php echo $lang['user_not_found'] ?? 'User not found. Please select a valid user.'; ?>
            </div>
            <div class="mt-4">
                <a href="index.php" class="btn btn-primary">
                    <i class="fas fa-arrow-left mr-2"></i> <?php echo $lang['back_to_users'] ?? 'Back to Users'; ?>
                </a>
            </div>
        <?php else: ?>
            <!-- User Form -->
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
                <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
                    <!-- Current Profile Picture Preview -->
                    <div class="flex flex-col items-center mb-4">
                        <?php if (!empty($profilePicture)): ?>
                            <img src="<?php echo htmlspecialchars($profilePicture); ?>" alt="Profile Picture" class="h-24 w-24 object-cover rounded-full mb-2">
                        <?php else: ?>
                            <div class="h-24 w-24 bg-gray-200 flex items-center justify-center rounded-full mb-2 <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                                <i class="fas fa-user text-gray-400 text-3xl"></i>
                            </div>
                        <?php endif; ?>
                        <p class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                            <?php echo $lang['current_profile_picture'] ?? 'Current Profile Picture'; ?>
                        </p>
                    </div>
                    
                    <!-- Username -->
                    <div>
                        <label for="username" class="block text-sm font-medium mb-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                            <?php echo $lang['user_username'] ?? 'Username'; ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>"
                            class="w-full p-2 border <?php echo isset($errors['username']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="<?php echo $lang['enter_username'] ?? 'Enter username'; ?>" required>
                        <?php if (isset($errors['username'])): ?>
                            <p class="mt-1 text-sm text-red-500"><?php echo htmlspecialchars($errors['username']); ?></p>
                        <?php endif; ?>
                    </div>
                    
                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium mb-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                            <?php echo $lang['user_email'] ?? 'Email'; ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>"
                            class="w-full p-2 border <?php echo isset($errors['email']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="<?php echo $lang['enter_email'] ?? 'Enter email address'; ?>" required>
                        <?php if (isset($errors['email'])): ?>
                            <p class="mt-1 text-sm text-red-500"><?php echo htmlspecialchars($errors['email']); ?></p>
                        <?php endif; ?>
                    </div>
                    <!-- Role -->
                    <div>
                        <label for="role" class="block text-sm font-medium mb-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                            <?php echo $lang['user_role'] ?? 'Role'; ?> <span class="text-red-500">*</span>
                        </label>
                        <select id="role" name="role" 
                            class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                            <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>>
                                <?php echo $lang['admin'] ?? 'Admin'; ?>
                            </option>
                            <option value="subscriber" <?php echo $role === 'subscriber' ? 'selected' : ''; ?>>
                                <?php echo $lang['subscriber'] ?? 'Subscriber'; ?>
                            </option>
                            <option value="free_user" <?php echo $role === 'free_user' ? 'selected' : ''; ?>>
                                <?php echo $lang['free_user'] ?? 'Free User'; ?>
                            </option>
                        </select>
                    </div>
                    
                    <!-- New Password -->
                    <div>
                        <label for="new_password" class="block text-sm font-medium mb-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                            <?php echo $lang['new_password'] ?? 'New Password'; ?>
                        </label>
                        <input type="password" id="new_password" name="new_password"
                            class="w-full p-2 border <?php echo isset($errors['new_password']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="<?php echo $lang['enter_new_password'] ?? 'Enter new password (leave blank to keep current)'; ?>">
                        <?php if (isset($errors['new_password'])): ?>
                            <p class="mt-1 text-sm text-red-500"><?php echo htmlspecialchars($errors['new_password']); ?></p>
                        <?php endif; ?>
                        <p class="mt-1 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                            <?php echo $lang['password_change_hint'] ?? 'Leave blank to keep the current password.'; ?>
                        </p>
                    </div>
                    
                    <!-- Confirm Password -->
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium mb-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                            <?php echo $lang['confirm_password'] ?? 'Confirm Password'; ?>
                        </label>
                        <input type="password" id="confirm_password" name="confirm_password"
                            class="w-full p-2 border <?php echo isset($errors['confirm_password']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            placeholder="<?php echo $lang['confirm_new_password'] ?? 'Confirm new password'; ?>">
                        <?php if (isset($errors['confirm_password'])): ?>
                            <p class="mt-1 text-sm text-red-500"><?php echo htmlspecialchars($errors['confirm_password']); ?></p>
                        <?php endif; ?>
                    </div>
                    <!-- Profile Picture -->
                    <div>
                        <label for="profile_picture" class="block text-sm font-medium mb-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                            <?php echo $lang['user_profile'] ?? 'Profile Picture'; ?>
                        </label>
                        <input type="file" id="profile_picture" name="profile_picture" 
                            class="w-full p-2 border <?php echo isset($errors['profile_picture']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                            accept=".jpg,.jpeg,.png,.gif">
                        <?php if (isset($errors['profile_picture'])): ?>
                            <p class="mt-1 text-sm text-red-500"><?php echo htmlspecialchars($errors['profile_picture']); ?></p>
                        <?php endif; ?>
                        <p class="mt-1 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                            <?php echo $lang['allowed_formats'] ?? 'Allowed formats: JPG, PNG, GIF. Max size: 2MB.'; ?>
                        </p>
                    </div>
                    
                    <!-- Created Date (Read-only) -->
                    <div>
                        <label class="block text-sm font-medium mb-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                            <?php echo $lang['created_at'] ?? 'Created At'; ?>
                        </label>
                        <input type="text" value="<?php echo date('M j, Y H:i', strtotime($createdAt)); ?>" class="w-full p-2 bg-gray-100 border border-gray-300 rounded-md <?php echo $darkMode ? 'bg-gray-700' : ''; ?>" readonly>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="flex justify-end">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i> <?php echo $lang['update'] ?? 'Update User'; ?>
                        </button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
        
        <!-- System Information -->
        <div class="mt-8 bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
            <h3 class="text-lg font-bold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                <?php echo $lang['system_information'] ?? 'System Information'; ?>
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                        <span class="font-medium"><?php echo $lang['current_date'] ?? 'Current Date'; ?>:</span> 
                        <?php echo '2025-03-17 23:13:44'; // Using the exact date and time you provided ?>
                    </p>
                    <p class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                        <span class="font-medium"><?php echo $lang['logged_in_as'] ?? 'Logged in as'; ?>:</span> 
                        <?php echo 'mahranalsarminy'; // Using the username you provided ?>
                    </p>
                </div>
                <div>
                    <p class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                        <span class="font-medium"><?php echo $lang['php_version'] ?? 'PHP Version'; ?>:</span> 
                        <?php echo phpversion(); ?>
                    </p>
                    <p class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                        <span class="font-medium"><?php echo $lang['server'] ?? 'Server'; ?>:</span> 
                        <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Include footer -->
<?php require_once '../../theme/admin/footer.php'; ?>					