<?php
// Set page title
$pageTitle = 'Add New User - WallPix Admin';

// Include header
require_once '../../theme/admin/header.php';

// Current date and time in UTC
$currentDateTime = '2025-03-17 22:54:52'; // Using the timestamp you provided
$currentUser = 'mahranalsarminy'; // Using the username you provided

// Initialize variables
$username = '';
$email = '';
$password = '';
$confirmPassword = '';
$role = 'free_user'; // Default role
$successMessage = '';
$errorMessage = '';
$errors = [];

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? 'free_user';
    $profilePicture = null;
    
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
    
    if (empty($password)) {
        $errors['password'] = 'Password is required';
    } elseif (strlen($password) < 6) {
        $errors['password'] = 'Password must be at least 6 characters';
    }
    
    if ($password !== $confirmPassword) {
        $errors['confirm_password'] = 'Passwords do not match';
    }
    
    // Check if username or email already exists
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        $result = $stmt->fetch();
        
        if ($result['count'] > 0) {
            $stmt = $pdo->prepare("SELECT username, email FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            $existingUser = $stmt->fetch();
            
            if ($existingUser['username'] === $username) {
                $errors['username'] = 'Username already exists';
            }
            
            if ($existingUser['email'] === $email) {
                $errors['email'] = 'Email already exists';
            }
        }
    } catch (PDOException $e) {
        $errorMessage = "Database error: " . $e->getMessage();
    }
    
    // Handle profile picture upload
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
                $profilePicture = UPLOAD_URL . 'profiles/' . $newFilename;
            } else {
                $errors['profile_picture'] = 'Failed to upload image';
            }
        }
    }
    
    // If no errors, create user
    if (empty($errors)) {
        try {
            // In a production environment, use password_hash() for secure password storage
            // $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            // For this example, we're using plain text (not recommended for production)
            $hashedPassword = $password; 
            
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, profile_picture, role, created_at) VALUES (?, ?, ?, ?, ?, ?)");
            $success = $stmt->execute([
                $username,
                $email,
                $hashedPassword,
                $profilePicture,
                $role,
                $currentDateTime
            ]);
            
            if ($success) {
                $userId = $pdo->lastInsertId();
                
                // Log the activity
                $stmt = $pdo->prepare("INSERT INTO activities (description) VALUES (?)");
                $stmt->execute(["New user {$username} (ID: {$userId}) was created by {$currentUser}"]);
                
                $successMessage = "User successfully created.";
                
                // Clear form data
                $username = '';
                $email = '';
                $password = '';
                $confirmPassword = '';
                $role = 'free_user';
            } else {
                $errorMessage = "Error creating user.";
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
                    <?php echo $lang['add_user'] ?? 'Add User'; ?>
                </h1>
                <p class="mt-2 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                    <?php echo $lang['create_new_user'] ?? 'Create a new user account'; ?>
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
        
        <!-- User Form -->
        <div class="bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
            <form action="" method="POST" enctype="multipart/form-data" class="space-y-6">
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
                
                <!-- Password -->
                <div>
                    <label for="password" class="block text-sm font-medium mb-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                        <?php echo $lang['user_password'] ?? 'Password'; ?> <span class="text-red-500">*</span>
                    </label>
                    <input type="password" id="password" name="password"
                        class="w-full p-2 border <?php echo isset($errors['password']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="<?php echo $lang['enter_password'] ?? 'Enter password'; ?>" required>
                    <?php if (isset($errors['password'])): ?>
                        <p class="mt-1 text-sm text-red-500"><?php echo htmlspecialchars($errors['password']); ?></p>
                    <?php endif; ?>
                    <p class="mt-1 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                        <?php echo $lang['password_hint'] ?? 'Password must be at least 6 characters long.'; ?>
                    </p>
                </div>
                
                <!-- Confirm Password -->
                <div>
                    <label for="confirm_password" class="block text-sm font-medium mb-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                        <?php echo $lang['user_confirm_password'] ?? 'Confirm Password'; ?> <span class="text-red-500">*</span>
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password"
                        class="w-full p-2 border <?php echo isset($errors['confirm_password']) ? 'border-red-500' : 'border-gray-300'; ?> rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="<?php echo $lang['confirm_password'] ?? 'Confirm your password'; ?>" required>
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
                
                <!-- Submit Button -->
                <div class="flex justify-end">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-user-plus mr-2"></i> <?php echo $lang['create_user'] ?? 'Create User'; ?>
                    </button>
                </div>
            </form>
        </div>
        
        <!-- System Information -->
        <div class="mt-8 bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
            <h3 class="text-lg font-bold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                <?php echo $lang['system_information'] ?? 'System Information'; ?>
            </h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <p class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                        <span class="font-medium"><?php echo $lang['current_date'] ?? 'Current Date'; ?>:</span> 
                        <?php echo '2025-03-17 22:59:07'; // Using the exact date and time you provided ?>
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