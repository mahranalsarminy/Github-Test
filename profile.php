<?php
require_once(__DIR__ . '/includes/init.php');
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch site settings
$stmt = $pdo->query("SELECT site_name, site_logo, dark_mode, language FROM site_settings WHERE id = 1");
$site_settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user data
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Session exists but user doesn't - force logout
    session_destroy();
    header('Location: login.php');
    exit;
}

// Get user subscription data
$stmt = $pdo->prepare("SELECT * FROM subscriptions WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmt->execute([$user_id]);
$subscription = $stmt->fetch(PDO::FETCH_ASSOC);

// Default to free if no subscription found
if (!$subscription) {
    $subscription = [
        'subscription_plan' => 'free',
        'start_date' => null,
        'end_date' => null,
        'status' => 'active'
    ];
}

// Handle active tab
$active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'settings';
$valid_tabs = ['settings', 'favorites', 'downloads', 'subscription', 'security'];
if (!in_array($active_tab, $valid_tabs)) {
    $active_tab = 'settings';
}

// Safe echo function
function safe_echo($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Handle profile update
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle different form submissions based on action
    if (isset($_POST['update_profile'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $bio = trim($_POST['bio'] ?? '');
        
        // Validation
        $errors = [];
        
        if (empty($username)) {
            $errors[] = "Username is required.";
        }
        
        if (empty($email)) {
            $errors[] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Please enter a valid email address.";
        }
        
        // Check if email belongs to another user
        if ($email !== $user['email']) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $errors[] = "This email is already registered to another account.";
            }
        }
        
        // Handle profile picture upload
        if (!empty($_FILES['profile_picture']['name'])) {
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $max_size = 2 * 1024 * 1024; // 2MB
            
            if (!in_array($_FILES['profile_picture']['type'], $allowed_types)) {
                $errors[] = "Only JPEG, PNG and GIF images are allowed.";
            } elseif ($_FILES['profile_picture']['size'] > $max_size) {
                $errors[] = "Image size must be less than 2MB.";
            } else {
                // Process the upload
                $upload_dir = 'uploads/profile_pictures/';
                
                // Create directory if it doesn't exist
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $filename = $user_id . '_' . time() . '_' . basename($_FILES['profile_picture']['name']);
                $target_file = $upload_dir . $filename;
                
                if (move_uploaded_file($_FILES['profile_picture']['tmp_name'], $target_file)) {
                    // Delete old profile picture if it exists and isn't the default
                    if (!empty($user['profile_picture']) && $user['profile_picture'] != 'assets/images/default-avatar.png' && file_exists($user['profile_picture'])) {
                        unlink($user['profile_picture']);
                    }
                    
                    $profile_picture = $target_file;
                } else {
                    $errors[] = "Failed to upload profile picture.";
                }
            }
        } else {
            $profile_picture = $user['profile_picture'];
        }
        
        if (empty($errors)) {
            // Update user profile
            $stmt = $pdo->prepare("UPDATE users SET username = ?, email = ?, bio = ?" . 
                                 (!empty($profile_picture) ? ", profile_picture = ?" : "") . 
                                 " WHERE id = ?");
            
            $params = [$username, $email, $bio];
            if (!empty($profile_picture)) {
                $params[] = $profile_picture;
            }
            $params[] = $user_id;
            
            $result = $stmt->execute($params);
            
            if ($result) {
                // Update session data
                $_SESSION['username'] = $username;
                $_SESSION['email'] = $email;
                
                // Log activity
                $stmt = $pdo->prepare("INSERT INTO activities (user_id, description) VALUES (?, ?)");
                $stmt->execute([$user_id, "Updated profile information"]);
                
                $success_message = "Profile updated successfully.";
                
                // Refresh user data
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error_message = "An error occurred while updating your profile.";
            }
        } else {
            $error_message = implode("<br>", $errors);
        }
    } elseif (isset($_POST['update_password'])) {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validation
        $errors = [];
        
        if (empty($current_password)) {
            $errors[] = "Current password is required.";
        } elseif (!password_verify($current_password, $user['password'])) {
            $errors[] = "Current password is incorrect.";
        }
        
        if (empty($new_password)) {
            $errors[] = "New password is required.";
        } elseif (strlen($new_password) < 8) {
            $errors[] = "Password must be at least 8 characters long.";
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = "Passwords do not match.";
        }
        
        if (empty($errors)) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $result = $stmt->execute([$hashed_password, $user_id]);
            
            if ($result) {
                // Log activity
                $stmt = $pdo->prepare("INSERT INTO activities (user_id, description, ip_address) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, "Changed password", $_SERVER['REMOTE_ADDR'] ?? '']);
                
                $success_message = "Password updated successfully.";
                $active_tab = 'security';
            } else {
                $error_message = "An error occurred while updating your password.";
            }
        } else {
            $error_message = implode("<br>", $errors);
            $active_tab = 'security';
        }
    } elseif (isset($_POST['toggle_favorite'])) {
        $wallpaper_id = (int)$_POST['wallpaper_id'];
        
        // Check if already favorited
        $stmt = $pdo->prepare("SELECT id FROM favorites WHERE user_id = ? AND wallpaper_id = ?");
        $stmt->execute([$user_id, $wallpaper_id]);
        $favorite = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($favorite) {
            // Remove from favorites
            $stmt = $pdo->prepare("DELETE FROM favorites WHERE id = ?");
            $stmt->execute([$favorite['id']]);
            $success_message = "Wallpaper removed from favorites.";
        } else {
            $error_message = "An error occurred.";
        }
        
        $active_tab = 'favorites';
    }
}

// Get favorites
$favorites = [];
if ($active_tab === 'favorites') {
    $stmt = $pdo->prepare("SELECT w.*, f.created_at AS favorited_at 
                         FROM favorites f
                         JOIN wallpapers w ON f.wallpaper_id = w.id
                         WHERE f.user_id = ?
                         ORDER BY f.created_at DESC
                         LIMIT 20");
    $stmt->execute([$user_id]);
    $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get downloads
$downloads = [];
if ($active_tab === 'downloads') {
    $stmt = $pdo->prepare("SELECT w.*, d.download_date, d.download_type
                         FROM downloads d
                         JOIN wallpapers w ON d.wallpaper_id = w.id
                         WHERE d.user_id = ?
                         ORDER BY d.download_date DESC
                         LIMIT 20");
    $stmt->execute([$user_id]);
    $downloads = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get subscription plans
$subscription_plans = [];
if ($active_tab === 'subscription') {
    $stmt = $pdo->query("SELECT * FROM subscription_plans WHERE is_active = 1 ORDER BY price");
    $subscription_plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>
<!DOCTYPE html>
<html lang="<?php echo $site_settings['language']; ?>" dir="<?php echo $site_settings['language'] === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - <?php echo safe_echo($site_settings['site_name']); ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/dark-mode.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Cairo|Roboto&display=swap">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .profile-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .profile-header {
            background-color: var(--bg-card);
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            position: relative;
            overflow: hidden;
        }
        
        @media (min-width: 640px) {
            .profile-header {
                flex-direction: row;
                justify-content: space-between;
            }
        }
        
        .user-info {
            flex: 1;
            text-align: center;
        }
        
        @media (min-width: 640px) {
            .user-info {
                text-align: left;
            }
        }
        
        .user-info h1 {
            font-size: 1.5rem;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .user-info p {
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }
        
        .profile-picture {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid var(--accent-color);
        }
        
        .vip-badge {
            display: inline-block;
            background-color: var(--accent-color);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: bold;
            margin-left: 0.5rem;
        }
        
        .tab-menu {
            display: flex;
            flex-wrap: wrap;
            background-color: var(--bg-card);
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin-top: 1.5rem;
            overflow: hidden;
        }
        
        .tab-menu a {
            padding: 1rem;
            color: var(--text-primary);
            text-decoration: none;
            flex-grow: 1;
            text-align: center;
            transition: all 0.3s;
            border-bottom: 3px solid transparent;
        }
        
        .tab-menu a:hover {
            background-color: var(--bg-secondary);
        }
        
        .tab-menu a.active {
            color: var(--accent-color);
            font-weight: 600;
            border-bottom-color: var(--accent-color);
        }
        
        .tab-content {
            background-color: var(--bg-card);
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-top: 1.5rem;
        }
        
        .tab-pane {
            display: none;
        }
        
        .tab-pane.active {
            display: block;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 500;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            font-size: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background-color: var(--bg-input);
            color: var(--text-primary);
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--accent-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(var(--accent-color-rgb), 0.2);
        }
        
        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }
        
        .btn-primary {
            display: inline-block;
            background-color: var(--accent-color);
            color: #fff;
            border: none;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-align: center;
        }
        
        .btn-primary:hover {
            background-color: var(--accent-hover);
        }
        
        .btn-secondary {
            display: inline-block;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-align: center;
        }
        
        .btn-secondary:hover {
            background-color: var(--border-color);
        }
        
        .btn-danger {
            display: inline-block;
            background-color: #e74c3c;
            color: #fff;
            border: none;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
            text-align: center;
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: rgba(46, 204, 113, 0.1);
            border: 1px solid #2ecc71;
            color: #2ecc71;
        }
        
        .alert-error {
            background-color: rgba(231, 76, 60, 0.1);
            border: 1px solid #e74c3c;
            color: #e74c3c;
        }
        
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
        }
        
        .card {
            background-color: var(--bg-card);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        .card-image {
            width: 100%;
            height: 180px;
            object-fit: cover;
        }
        
        .card-body {
            padding: 1.2rem;
        }
        
        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .card-text {
            color: var(--text-secondary);
            font-size: 0.9rem;
            margin-bottom: 0.8rem;
        }
        
        .card-footer {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 1.2rem;
            background-color: var(--bg-secondary);
            border-top: 1px solid var(--border-color);
        }
        
        .card-actions {
            margin-top: 1rem;
            display: flex;
            justify-content: space-between;
        }
        
        .card-actions button, .card-actions a {
            padding: 0.5rem 0.75rem;
            font-size: 0.875rem;
        }
        
        .subscription-plans {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        
        .plan-card {
            background-color: var(--bg-card);
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            border: 2px solid var(--border-color);
            transition: transform 0.3s;
            display: flex;
            flex-direction: column;
            position: relative;
        }
        
        .plan-card.active {
            border-color: var(--accent-color);
        }
        
        .plan-card:hover {
            transform: translateY(-5px);
        }
        
        .plan-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }
        
        .plan-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .plan-price {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--accent-color);
            margin-bottom: 0.5rem;
        }
        
        .plan-period {
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .plan-features {
            margin-bottom: 1.5rem;
            flex-grow: 1;
        }
        
        .plan-feature {
            display: flex;
            align-items: center;
            margin-bottom: 0.8rem;
            color: var(--text-primary);
        }
        
        .plan-feature i {
            color: var(--accent-color);
            margin-right: 0.5rem;
        }
        
        .plan-action {
            text-align: center;
        }
        
        .plan-badge {
            position: absolute;
            top: -10px;
            right: -10px;
            background-color: var(--accent-color);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        
        .mini-cards {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-top: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .mini-card {
            background-color: var(--bg-secondary);
            padding: 1.5rem;
            border-radius: 8px;
            text-align: center;
            color: var(--text-primary);
            text-decoration: none;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .mini-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 15px rgba(0, 0, 0, 0.1);
        }
        
        .mini-card.card-settings { background-color: rgba(52, 152, 219, 0.2); }
        .mini-card.card-favorites { background-color: rgba(46, 204, 113, 0.2); }
        .mini-card.card-downloads { background-color: rgba(241, 196, 15, 0.2); }
        .mini-card.card-subscription { background-color: rgba(155, 89, 182, 0.2); }
        .mini-card.card-security { background-color: rgba(52, 73, 94, 0.2); }
        .mini-card.card-logout { background-color: rgba(189, 195, 199, 0.2); }
        .mini-card.card-delete { background-color: rgba(231, 76, 60, 0.2); }
        .mini-card.card-admin { background-color: rgba(142, 68, 173, 0.2); }
        
        .dark-mode .mini-card.card-settings { background-color: rgba(52, 152, 219, 0.3); }
        .dark-mode .mini-card.card-favorites { background-color: rgba(46, 204, 113, 0.3); }
        .dark-mode .mini-card.card-downloads { background-color: rgba(241, 196, 15, 0.3); }
        .dark-mode .mini-card.card-subscription { background-color: rgba(155, 89, 182, 0.3); }
        .dark-mode .mini-card.card-security { background-color: rgba(52, 73, 94, 0.3); }
        .dark-mode .mini-card.card-logout { background-color: rgba(189, 195, 199, 0.3); }
        .dark-mode .mini-card.card-delete { background-color: rgba(231, 76, 60, 0.3); }
        .dark-mode .mini-card.card-admin { background-color: rgba(142, 68, 173, 0.3); }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-secondary);
        }
        
        .empty-state i {
            font-size: 3rem;
            margin-bottom: 1.5rem;
            opacity: 0.5;
        }
        
        .empty-state h3 {
            font-size: 1.5rem;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }
        
        .empty-state p {
            max-width: 500px;
            margin: 0 auto;
        }
    </style>
</head>
<body class="<?php echo $site_settings['dark_mode'] ? 'dark-mode' : 'light-mode'; ?>">
    <!-- Skip to main content link for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <!-- Header -->
    <?php include 'theme/homepage/header.php'; ?>

    <!-- Main Content -->
    <main id="main-content" role="main">
        <div class="profile-container">
            <!-- Profile Header -->
            <div class="profile-header">
                <div class="user-info">
                    <h1><?php echo safe_echo($user['username']); ?></h1>
                    <p>
                        Subscription: <?php echo safe_echo(ucfirst($subscription['subscription_plan'])); ?>
                        <?php if ($subscription['subscription_plan'] !== 'free'): ?>
                            <span class="vip-badge">VIP</span>
                        <?php endif; ?>
                    </p>
                    <?php if ($subscription['subscription_plan'] !== 'free' && $subscription['end_date']): ?>
                        <p>Expires on: <?php echo date('F j, Y', strtotime($subscription['end_date'])); ?></p>
                    <?php endif; ?>
                    <p>Member since: <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                </div>
                <div class="profile-picture-container">
                    <img src="<?php echo !empty($user['profile_picture']) ? safe_echo($user['profile_picture']) : 'assets/images/default-avatar.png'; ?>" alt="Profile Picture" class="profile-picture">
                </div>
            </div>
            
            <!-- Mini Cards -->
            <div class="mini-cards">
                <a href="?tab=settings" class="mini-card card-settings">
                    <i class="fas fa-cog fa-2x mb-2"></i>
                    <p class="font-semibold text-lg">Settings</p>
                </a>
                <a href="?tab=favorites" class="mini-card card-favorites">
                    <i class="fas fa-heart fa-2x mb-2"></i>
                    <p class="font-semibold text-lg">My Favorites</p>
                </a>
                <a href="?tab=downloads" class="mini-card card-downloads">
                    <i class="fas fa-download fa-2x mb-2"></i>
                    <p class="font-semibold text-lg">My Downloads</p>
                </a>
                <a href="?tab=subscription" class="mini-card card-subscription">
                    <i class="fas fa-crown fa-2x mb-2"></i>
                    <p class="font-semibold text-lg">Subscription</p>
                </a>
                <a href="?tab=security" class="mini-card card-security">
                    <i class="fas fa-lock fa-2x mb-2"></i>
                    <p class="font-semibold text-lg">Security</p>
                </a>
                <a href="logout.php" class="mini-card card-logout">
                    <i class="fas fa-sign-out-alt fa-2x mb-2"></i>
                    <p class="font-semibold text-lg">Log Out</p>
                </a>
                <?php if ($user['role'] === 'admin'): ?>
                <a href="admin/index.php" class="mini-card card-admin">
                    <i class="fas fa-tools fa-2x mb-2"></i>
                    <p class="font-semibold text-lg">Site Management</p>
                </a>
                <?php endif; ?>
                <a href="#delete-modal" class="mini-card card-delete" id="show-delete-modal">
                    <i class="fas fa-trash-alt fa-2x mb-2"></i>
                    <p class="font-semibold text-lg">Delete Account</p>
                </a>
            </div>

            <!-- Tab Menu -->
            <div class="tab-menu" role="tablist">
                <a href="?tab=settings" class="<?php echo $active_tab === 'settings' ? 'active' : ''; ?>" role="tab" aria-selected="<?php echo $active_tab === 'settings' ? 'true' : 'false'; ?>">Settings</a>
                <a href="?tab=favorites" class="<?php echo $active_tab === 'favorites' ? 'active' : ''; ?>" role="tab" aria-selected="<?php echo $active_tab === 'favorites' ? 'true' : 'false'; ?>">My Favorites</a>
                <a href="?tab=downloads" class="<?php echo $active_tab === 'downloads' ? 'active' : ''; ?>" role="tab" aria-selected="<?php echo $active_tab === 'downloads' ? 'true' : 'false'; ?>">My Downloads</a>
                                <a href="?tab=subscription" class="<?php echo $active_tab === 'subscription' ? 'active' : ''; ?>" role="tab" aria-selected="<?php echo $active_tab === 'subscription' ? 'true' : 'false'; ?>">Subscription</a>
                <a href="?tab=security" class="<?php echo $active_tab === 'security' ? 'active' : ''; ?>" role="tab" aria-selected="<?php echo $active_tab === 'security' ? 'true' : 'false'; ?>">Security</a>
            </div>

            <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo $success_message; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
            <div class="alert alert-error" role="alert">
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>

            <!-- Tab Content -->
            <div class="tab-content">
                <!-- Settings Tab -->
                <div class="tab-pane <?php echo $active_tab === 'settings' ? 'active' : ''; ?>" id="settings" role="tabpanel">
                    <h2>Profile Settings</h2>
                    <p>Update your personal information and profile settings.</p>
                    
                    <form action="profile.php?tab=settings" method="post" enctype="multipart/form-data" class="mt-4">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" class="form-control" id="username" name="username" value="<?php echo safe_echo($user['username']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" class="form-control" id="email" name="email" value="<?php echo safe_echo($user['email']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="bio">Bio</label>
                            <textarea class="form-control" id="bio" name="bio"><?php echo safe_echo($user['bio'] ?? ''); ?></textarea>
                            <small class="form-text text-muted">Tell us something about yourself (optional)</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="profile_picture">Profile Picture</label>
                            <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
                            <small class="form-text text-muted">Maximum file size: 2MB. Supported formats: JPEG, PNG, GIF</small>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn-primary">Save Changes</button>
                    </form>
                </div>
                
                <!-- Favorites Tab -->
                <div class="tab-pane <?php echo $active_tab === 'favorites' ? 'active' : ''; ?>" id="favorites" role="tabpanel">
                    <h2>My Favorites</h2>
                    <p>Your favorite wallpapers and images.</p>
                    
                    <?php if (count($favorites) > 0): ?>
                        <div class="card-grid">
                            <?php foreach ($favorites as $favorite): ?>
                                <div class="card">
                                    <img src="<?php echo safe_echo($favorite['thumbnail_url']); ?>" alt="<?php echo safe_echo($favorite['title']); ?>" class="card-image">
                                    <div class="card-body">
                                        <h3 class="card-title"><?php echo safe_echo($favorite['title']); ?></h3>
                                        <p class="card-text">Added on: <?php echo date('M j, Y', strtotime($favorite['favorited_at'])); ?></p>
                                        <div class="card-actions">
                                            <a href="wallpaper.php?id=<?php echo $favorite['id']; ?>" class="btn-secondary">View</a>
                                            <form action="profile.php?tab=favorites" method="post" style="display: inline;">
                                                <input type="hidden" name="wallpaper_id" value="<?php echo $favorite['id']; ?>">
                                                <button type="submit" name="toggle_favorite" class="btn-danger">Remove</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="far fa-heart"></i>
                            <h3>No Favorites Yet</h3>
                            <p>You haven't added any wallpapers to your favorites list. Browse our collection and click the heart icon to save your favorite wallpapers.</p>
                            <a href="index.php" class="btn-primary mt-4">Browse Wallpapers</a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Downloads Tab -->
                <div class="tab-pane <?php echo $active_tab === 'downloads' ? 'active' : ''; ?>" id="downloads" role="tabpanel">
                    <h2>My Downloads</h2>
                    <p>Wallpapers and images you've downloaded.</p>
                    
                    <?php if (count($downloads) > 0): ?>
                        <div class="card-grid">
                            <?php foreach ($downloads as $download): ?>
                                <div class="card">
                                    <img src="<?php echo safe_echo($download['thumbnail_url']); ?>" alt="<?php echo safe_echo($download['title']); ?>" class="card-image">
                                    <div class="card-body">
                                        <h3 class="card-title"><?php echo safe_echo($download['title']); ?></h3>
                                        <p class="card-text">
                                            Downloaded on: <?php echo date('M j, Y', strtotime($download['download_date'])); ?><br>
                                            Resolution: <?php echo safe_echo($download['download_type']); ?>
                                        </p>
                                        <div class="card-actions">
                                            <a href="wallpaper.php?id=<?php echo $download['id']; ?>" class="btn-secondary">View</a>
                                            <a href="download.php?id=<?php echo $download['id']; ?>&resolution=<?php echo $download['download_type']; ?>" class="btn-primary">Download Again</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-download"></i>
                            <h3>No Downloads Yet</h3>
                            <p>You haven't downloaded any wallpapers yet. Browse our collection and download wallpapers to see them here.</p>
                            <a href="index.php" class="btn-primary mt-4">Browse Wallpapers</a>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Subscription Tab -->
                <div class="tab-pane <?php echo $active_tab === 'subscription' ? 'active' : ''; ?>" id="subscription" role="tabpanel">
                    <h2>Subscription</h2>
                    <p>Manage your subscription plan and payment details.</p>
                    
                    <div class="subscription-status">
                        <h3>Current Plan: <?php echo safe_echo(ucfirst($subscription['subscription_plan'])); ?></h3>
                        <?php if ($subscription['subscription_plan'] !== 'free'): ?>
                            <p>Your subscription is active until <?php echo date('F j, Y', strtotime($subscription['end_date'])); ?>.</p>
                            <?php if (strtotime($subscription['end_date']) < strtotime('+7 days')): ?>
                                <div class="alert alert-error">
                                    <strong>Note:</strong> Your subscription is expiring soon. Please renew to continue enjoying premium features.
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p>You're currently using our free plan. Upgrade to access premium features and content.</p>
                        <?php endif; ?>
                    </div>
                    
                    <h3 class="mt-4">Available Plans</h3>
                    
                    <?php if (count($subscription_plans) > 0): ?>
                        <div class="subscription-plans mt-4">
                            <?php foreach ($subscription_plans as $plan): ?>
                                <div class="plan-card <?php echo $subscription['subscription_plan'] === $plan['name'] ? 'active' : ''; ?>">
                                    <?php if ($plan['is_popular']): ?>
                                        <span class="plan-badge">Most Popular</span>
                                    <?php endif; ?>
                                    
                                    <div class="plan-header">
                                        <h4 class="plan-title"><?php echo safe_echo(ucfirst($plan['name'])); ?></h4>
                                        <div class="plan-price">$<?php echo number_format($plan['price'], 2); ?></div>
                                        <div class="plan-period">per <?php echo safe_echo($plan['duration_unit']); ?></div>
                                    </div>
                                    
                                    <div class="plan-features">
                                        <?php 
                                        $features = json_decode($plan['features'], true);
                                        if ($features): 
                                            foreach ($features as $feature):
                                        ?>
                                            <div class="plan-feature">
                                                <i class="fas fa-check"></i>
                                                <span><?php echo safe_echo($feature); ?></span>
                                            </div>
                                        <?php 
                                            endforeach;
                                        endif;
                                        ?>
                                    </div>
                                    
                                    <div class="plan-action">
                                        <?php if ($subscription['subscription_plan'] === $plan['name']): ?>
                                            <button class="btn-secondary" disabled>Current Plan</button>
                                        <?php else: ?>
                                            <a href="checkout.php?plan=<?php echo $plan['id']; ?>" class="btn-primary">Upgrade Now</a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p>No subscription plans are currently available.</p>
                    <?php endif; ?>
                </div>
                
                <!-- Security Tab -->
                <div class="tab-pane <?php echo $active_tab === 'security' ? 'active' : ''; ?>" id="security" role="tabpanel">
                    <h2>Security Settings</h2>
                    <p>Manage your password and account security.</p>
                    
                    <form action="profile.php?tab=security" method="post" class="mt-4">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" class="form-control" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" class="form-control" id="new_password" name="new_password" required>
                            <small class="form-text text-muted">Password must be at least 8 characters long.</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <button type="submit" name="update_password" class="btn-primary">Update Password</button>
                    </form>
                    
                    <div class="security-info mt-5">
                        <h3>Login History</h3>
                        <p>Recent login activity for your account.</p>
                        
                        <table class="table mt-3" style="width:100%; border-collapse: collapse;">
                            <thead>
                                <tr>
                                    <th style="padding:10px; text-align:left; border-bottom:1px solid var(--border-color);">Date</th>
                                    <th style="padding:10px; text-align:left; border-bottom:1px solid var(--border-color);">IP Address</th>
                                    <th style="padding:10px; text-align:left; border-bottom:1px solid var(--border-color);">Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Fetch recent login attempts
                                $stmt = $pdo->prepare("SELECT * FROM login_attempts WHERE email = ? ORDER BY created_at DESC LIMIT 5");
                                $stmt->execute([$user['email']]);
                                $logins = $stmt->fetchAll(PDO::FETCH_ASSOC);
                                
                                foreach ($logins as $login):
                                ?>
                                <tr>
                                    <td style="padding:10px; border-bottom:1px solid var(--border-color);"><?php echo date('M j, Y H:i', strtotime($login['created_at'])); ?></td>
                                    <td style="padding:10px; border-bottom:1px solid var(--border-color);"><?php echo safe_echo($login['ip_address']); ?></td>
                                    <td style="padding:10px; border-bottom:1px solid var(--border-color);">
                                        <?php if ($login['status'] === 'successful'): ?>
                                            <span style="color:#2ecc71">Successful</span>
                                        <?php else: ?>
                                            <span style="color:#e74c3c">Failed</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                
                                <?php if (empty($logins)): ?>
                                <tr>
                                    <td colspan="3" style="padding:10px; text-align:center;">No login history available.</td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                        
                        <div class="mt-4">
                            <p><strong>Last login:</strong> <?php echo date('F j, Y H:i', strtotime($user['last_login'] ?? 'now')); ?></p>
                            <p><strong>Last IP address:</strong> <?php echo safe_echo($user['last_ip'] ?? '-'); ?></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Delete Account Modal -->
        <div id="delete-modal" class="modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; z-index:1000; background-color:rgba(0,0,0,0.5);">
            <div class="modal-content" style="max-width:500px; margin:10% auto; background-color:var(--bg-card); padding:2rem; border-radius:8px; box-shadow:0 4px 15px rgba(0,0,0,0.2);">
                <div class="modal-header" style="margin-bottom:1.5rem;">
                    <h3 style="color:var(--text-primary); margin:0;">Delete Account</h3>
                </div>
                <div class="modal-body" style="margin-bottom:1.5rem;">
                    <p style="color:var(--text-primary); margin-bottom:1rem;">Are you sure you want to delete your account? This action cannot be undone and all your data will be permanently lost.</p>
                    <form action="delete-account.php" method="post" id="delete-account-form">
                        <div class="form-group">
                            <label for="delete-password">Enter your password to confirm</label>
                            <input type="password" class="form-control" id="delete-password" name="password" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer" style="display:flex; justify-content:space-between;">
                    <button id="close-delete-modal" class="btn-secondary">Cancel</button>
                    <button form="delete-account-form" type="submit" class="btn-danger">Delete My Account</button>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include 'theme/homepage/footer.php'; ?>

    <!-- Accessibility button -->
    <div id="accessibility-toggle" class="accessibility-button" aria-label="Accessibility options">
        <i class="fas fa-universal-access"></i>
    </div>

    <!-- Accessibility menu -->
    <?php include 'theme/homepage/accessibility.php'; ?>

    <!-- Scripts -->
    <script src="/assets/js/scripts.js"></script>
    <script src="/assets/js/accessibility.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Tab navigation
            const tabLinks = document.querySelectorAll('.tab-menu a');
            const tabPanes = document.querySelectorAll('.tab-pane');
            
            // Password strength validation
            const newPassword = document.getElementById('new_password');
            const confirmPassword = document.getElementById('confirm_password');
            
            if (newPassword && confirmPassword) {
                confirmPassword.addEventListener('input', function() {
                    if (this.value !== newPassword.value) {
                        this.setCustomValidity("Passwords don't match");
                    } else {
                        this.setCustomValidity('');
                    }
                });
            }
            
            // Modal functionality
            const showDeleteModalBtn = document.getElementById('show-delete-modal');
            const deleteModal = document.getElementById('delete-modal');
            const closeDeleteModalBtn = document.getElementById('close-delete-modal');
            
            if (showDeleteModalBtn && deleteModal && closeDeleteModalBtn) {
                showDeleteModalBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    deleteModal.style.display = 'block';
                    document.body.style.overflow = 'hidden'; // Prevent scrolling
                    document.getElementById('delete-password').focus();
                });
                
                closeDeleteModalBtn.addEventListener('click', function() {
                    deleteModal.style.display = 'none';
                    document.body.style.overflow = '';
                });
                
                // Close modal when clicking outside
                window.addEventListener('click', function(e) {
                    if (e.target === deleteModal) {
                        deleteModal.style.display = 'none';
                        document.body.style.overflow = '';
                    }
                });
                
                // Close modal with ESC key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape' && deleteModal.style.display === 'block') {
                        deleteModal.style.display = 'none';
                        document.body.style.overflow = '';
                    }
                });
            }
            
            // Preview uploaded profile image
            const profilePictureInput = document.getElementById('profile_picture');
            if (profilePictureInput) {
                profilePictureInput.addEventListener('change', function() {
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            document.querySelector('.profile-picture').src = e.target.result;
                        };
                        reader.readAsDataURL(this.files[0]);
                    }
                });
            }
            
            // Dynamic content loading for favorites/downloads tabs to improve performance
            const favoriteTab = document.querySelector('a[href="?tab=favorites"]');
            const downloadTab = document.querySelector('a[href="?tab=downloads"]');
            
            if (favoriteTab && downloadTab) {
                // Add loading indicators or progressive loading if needed
            }
            
            // Set last active tab in local storage
            const activeTab = '<?php echo $active_tab; ?>';
            if (activeTab) {
                localStorage.setItem('lastActiveTab', activeTab);
            }
            
            // Display current date for visual reference
            const currentDate = document.createElement('div');
            currentDate.style.fontSize = '0.8rem';
            currentDate.style.color = 'var(--text-secondary)';
            currentDate.style.textAlign = 'center';
            currentDate.style.marginTop = '2rem';
            currentDate.innerHTML = 'Last updated: <?php echo date("F j, Y, g:i a", time()); ?>';
            document.querySelector('main').appendChild(currentDate);
            
            // Accessibility enhancements
            document.querySelectorAll('.form-control').forEach(input => {
                input.addEventListener('invalid', function() {
                    this.setAttribute('aria-invalid', 'true');
                });
                
                input.addEventListener('input', function() {
                    if (this.validity.valid) {
                        this.removeAttribute('aria-invalid');
                    }
                });
            });
        });
    </script>
</body>
</html>