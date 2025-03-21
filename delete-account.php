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

// Safe echo function
function safe_echo($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Check for form submission
$error_message = '';
$confirmation_step = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // First confirmation step
    if (isset($_POST['confirm_deletion'])) {
        $password = $_POST['password'] ?? '';
        
        // Verify password
        if (empty($password)) {
            $error_message = "Please enter your password to confirm account deletion.";
        } elseif (!password_verify($password, $user['password'])) {
            $error_message = "Incorrect password. Please try again.";
            
            // Log failed attempt
            $stmt = $pdo->prepare("INSERT INTO activities (user_id, description, ip_address) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, "Failed account deletion attempt - incorrect password", $_SERVER['REMOTE_ADDR']]);
        } else {
            // Password correct, show final confirmation step
            $confirmation_step = true;
        }
    }
    
    // Final deletion step
    if (isset($_POST['execute_deletion'])) {
        // Verify the reason is provided if required
        if (isset($_POST['reason_required']) && empty($_POST['deletion_reason'])) {
            $error_message = "Please provide a reason for deleting your account.";
        } else {
            // Start transaction
            $pdo->beginTransaction();
            
            try {
                // Save reason if provided
                $reason = $_POST['deletion_reason'] ?? 'No reason provided';
                
                // Log account deletion in a separate table for analytics
                $stmt = $pdo->prepare("INSERT INTO deleted_accounts (user_id, username, email, deletion_reason, ip_address, created_at, deletion_date) 
                                      VALUES (?, ?, ?, ?, ?, ?, NOW())");
                $stmt->execute([
                    $user_id, 
                    $user['username'], 
                    $user['email'], 
                    $reason, 
                    $_SERVER['REMOTE_ADDR'], 
                    $user['created_at']
                ]);
                
                // Delete user content
                // This assumes there are foreign key constraints set up with ON DELETE CASCADE
                // Otherwise, you would need to delete records from all related tables here
                
                // Delete favorites
                $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // Delete downloads
                $stmt = $pdo->prepare("DELETE FROM downloads WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // Delete comments
                $stmt = $pdo->prepare("DELETE FROM comments WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // Delete subscriptions
                $stmt = $pdo->prepare("DELETE FROM subscriptions WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // Delete activities
                $stmt = $pdo->prepare("DELETE FROM activities WHERE user_id = ?");
                $stmt->execute([$user_id]);
                
                // Delete the user account
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                
                // Commit transaction
                $pdo->commit();
                
                // Destroy the session
                session_destroy();
                
                // Redirect to confirmation page
                header('Location: account-deleted.php');
                exit;
            } catch (Exception $e) {
                // Roll back transaction on error
                $pdo->rollBack();
                $error_message = "An error occurred while deleting your account. Please try again or contact support.";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $site_settings['language']; ?>" dir="<?php echo $site_settings['language'] === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Account - <?php echo safe_echo($site_settings['site_name']); ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/dark-mode.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Cairo|Roboto&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .delete-container {
            max-width: 600px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .delete-card {
            background-color: var(--bg-card);
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .delete-header {
            background-color: #e74c3c;
            color: white;
            padding: 1.5rem;
            text-align: center;
            position: relative;
        }
        
        .delete-header h1 {
            margin: 0;
            font-size: 1.8rem;
        }
        
        .delete-header p {
            margin: 0.5rem 0 0;
            opacity: 0.9;
        }
        
        .delete-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }
        
        .delete-body {
            padding: 2rem;
        }
        
        .delete-steps {
            margin-bottom: 2rem;
        }
        
        .step {
            display: flex;
            margin-bottom: 1rem;
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            background-color: var(--accent-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            margin-right: 1rem;
            flex-shrink: 0;
        }
        
        .step-content {
            color: var(--text-primary);
        }
        
        .warning-box {
            background-color: rgba(231, 76, 60, 0.1);
            border-left: 4px solid #e74c3c;
            padding: 1.2rem;
            margin-bottom: 2rem;
            color: var(--text-primary);
        }
        
        .warning-box h3 {
            color: #e74c3c;
            margin-top: 0;
            margin-bottom: 0.5rem;
        }
        
        .warning-box ul {
            margin: 0;
            padding-left: 1.2rem;
        }
        
        .warning-box li {
            margin-bottom: 0.5rem;
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
        
        .btn-group {
            display: flex;
            gap: 1rem;
        }
        
        @media (max-width: 640px) {
            .btn-group {
                flex-direction: column;
            }
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s, color 0.3s;
            text-align: center;
            flex: 1;
            text-decoration: none;
        }
        
        .btn-danger {
            background-color: #e74c3c;
            color: white;
            border: none;
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
        }
        
        .btn-secondary {
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }
        
        .btn-secondary:hover {
            background-color: var(--border-color);
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
        }
        
        .alert-error {
            background-color: rgba(231, 76, 60, 0.1);
            border: 1px solid #e74c3c;
            color: #e74c3c;
        }
        
        .breadcrumbs {
            margin-bottom: 1.5rem;
            display: flex;
            flex-wrap: wrap;
        }
        
        .breadcrumbs a, .breadcrumbs span {
            color: var(--text-secondary);
            text-decoration: none;
            margin-right: 0.5rem;
            display: flex;
            align-items: center;
        }
        
        .breadcrumbs a:hover {
            color: var(--accent-color);
        }
        
        .breadcrumbs i {
            margin-left: 0.5rem;
            font-size: 0.8rem;
        }
        
        .breadcrumbs .current {
            color: var(--text-primary);
            font-weight: 500;
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
        <div class="delete-container">
            <!-- Breadcrumbs -->
            <div class="breadcrumbs">
                <a href="index.php">Home <i class="fas fa-chevron-right"></i></a>
                <a href="profile.php">My Profile <i class="fas fa-chevron-right"></i></a>
                <span class="current">Delete Account</span>
            </div>
            
            <div class="delete-card">
                <div class="delete-header">
                    <i class="fas fa-exclamation-triangle delete-icon"></i>
                    <h1>Delete Your Account</h1>
                    <p>This action is permanent and cannot be undone</p>
                </div>
                
                <div class="delete-body">
                    <?php if (!$confirmation_step): ?>
                        <!-- Initial Deletion Warning -->
                        <div class="warning-box">
                            <h3>Warning: This is permanent!</h3>
                            <p>Deleting your account will:</p>
                            <ul>
                                <li>Permanently remove your profile and personal data</li>
                                <li>Delete all your favorites and download history</li>
                                <li>Cancel any active subscriptions</li>
                                <li>Remove your comments and activity history</li>
                                <li>Remove your access to premium content</li>
                            </ul>
                        </div>
                        
                        <div class="delete-steps">
                            <h3>Account Deletion Process:</h3>
                            <div class="step">
                                <div class="step-number">1</div>
                                <div class="step-content">Enter your password for verification</div>
                            </div>
                            <div class="step">
                                <div class="step-number">2</div>
                                <div class="step-content">Select a reason for leaving (optional but appreciated)</div>
                            </div>
                            <div class="step">
                                <div class="step-number">3</div>
                                <div class="step-content">Confirm final deletion</div>
                            </div>
                        </div>
                        
                        <?php if ($error_message): ?>
                        <div class="alert alert-error" role="alert">
                            <?php echo $error_message; ?>
                        </div>
                        <?php endif; ?>
                        
                        <form method="post" action="delete-account.php">
                            <div class="form-group">
                                <label for="password">Enter your password to continue</label>
                                <input type="password" id="password" name="password" class="form-control" required>
                            </div>
                            
                            <div class="btn-group">
                                <a href="profile.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" name="confirm_deletion" class="btn btn-danger">Continue to Verification</button>
                            </div>
                        </form>
                    
                    <?php else: ?>
                        <!-- Final Confirmation Step -->
                        <div class="warning-box">
                            <h3>Final Confirmation</h3>
                            <p>Are you absolutely sure you want to delete your account?</p>
                            <p><strong>Username:</strong> <?php echo safe_echo($user['username']); ?></p>
                            <p><strong>Email:</strong> <?php echo safe_echo($user['email']); ?></p>
                            <p><strong>Member since:</strong> <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                            <p>This action is permanent and cannot be reversed.</p>
                        </div>
                        
                                                <?php if ($error_message): ?>
                        <div class="alert alert-error" role="alert">
                            <?php echo $error_message; ?>
                        </div>
                        <?php endif; ?>
                        
                        <form method="post" action="delete-account.php">
                            <div class="form-group">
                                <label for="deletion_reason">Please tell us why you're leaving:</label>
                                <select id="deletion_reason" name="deletion_reason" class="form-control">
                                    <option value="">-- Select a reason --</option>
                                    <option value="Found a better service">Found a better service</option>
                                    <option value="Not using the service anymore">Not using the service anymore</option>
                                    <option value="Privacy concerns">Privacy concerns</option>
                                    <option value="Too many emails">Too many emails</option>
                                    <option value="Technical issues">Technical issues</option>
                                    <option value="Customer service">Unsatisfied with customer service</option>
                                    <option value="Other">Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group" id="other_reason_container" style="display:none;">
                                <label for="other_reason">Additional details (optional):</label>
                                <textarea id="other_reason" name="other_reason" class="form-control" placeholder="Please share more details about why you're leaving..."></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label class="checkbox-container">
                                    <input type="checkbox" id="confirm_checkbox" required>
                                    <span class="checkmark"></span>
                                    I understand that this action is permanent and all my data will be deleted
                                </label>
                            </div>
                            
                            <div class="btn-group">
                                <a href="profile.php" class="btn btn-secondary">Cancel</a>
                                <button type="submit" name="execute_deletion" class="btn btn-danger" id="delete-btn" disabled>Permanently Delete My Account</button>
                            </div>
                        </form>
                    <?php endif; ?>
                    
                    <!-- Help section -->
                    <div style="margin-top: 2rem; padding-top: 1.5rem; border-top: 1px solid var(--border-color);">
                        <h3>Need help?</h3>
                        <p>If you're experiencing issues with our service, we'd love to help you resolve them before you go.</p>
                        <p>Contact our support team: <a href="mailto:support@example.com">support@example.com</a></p>
                    </div>
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
            // Toggle other reason field visibility when "Other" is selected
            const reasonSelect = document.getElementById('deletion_reason');
            const otherReasonContainer = document.getElementById('other_reason_container');
            
            if (reasonSelect && otherReasonContainer) {
                reasonSelect.addEventListener('change', function() {
                    if (this.value === 'Other') {
                        otherReasonContainer.style.display = 'block';
                    } else {
                        otherReasonContainer.style.display = 'none';
                    }
                });
            }
            
            // Enable/disable delete button based on confirmation checkbox
            const confirmCheckbox = document.getElementById('confirm_checkbox');
            const deleteBtn = document.getElementById('delete-btn');
            
            if (confirmCheckbox && deleteBtn) {
                confirmCheckbox.addEventListener('change', function() {
                    deleteBtn.disabled = !this.checked;
                });
            }
            
            // Show a final confirmation dialog when submit button is clicked
            const deleteForm = document.querySelector('form');
            if (deleteForm) {
                deleteForm.addEventListener('submit', function(e) {
                    if (document.querySelector('button[name="execute_deletion"]') && !confirm('Are you absolutely sure you want to permanently delete your account? This action CANNOT be undone.')) {
                        e.preventDefault();
                        return false;
                    }
                });
            }
            
            // Timer to auto-redirect if user stays on page too long
            let confirmationTimer = 1800; // 30 minutes in seconds
            
            if (document.querySelector('button[name="execute_deletion"]')) {
                const timerDisplay = document.createElement('div');
                timerDisplay.style.textAlign = 'center';
                timerDisplay.style.margin = '1rem 0';
                timerDisplay.style.color = 'var(--text-secondary)';
                document.querySelector('.delete-body').appendChild(timerDisplay);
                
                const updateTimer = setInterval(function() {
                    confirmationTimer--;
                    
                    const minutes = Math.floor(confirmationTimer / 60);
                    const seconds = confirmationTimer % 60;
                    
                    timerDisplay.textContent = `This page will expire in ${minutes}:${seconds < 10 ? '0' : ''}${seconds}`;
                    
                    if (confirmationTimer <= 0) {
                        clearInterval(updateTimer);
                        window.location.href = 'profile.php';
                    }
                }, 1000);
            }
            
            // Log timestamp for analytics
            console.log('Delete account page loaded at:', new Date());
            
            // Fade in the delete card for visual effect
            const deleteCard = document.querySelector('.delete-card');
            if (deleteCard) {
                deleteCard.style.opacity = '0';
                deleteCard.style.transition = 'opacity 0.5s ease';
                
                setTimeout(() => {
                    deleteCard.style.opacity = '1';
                }, 100);
            }
            
            // Focus trap for accessibility
            const focusableElements = 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])';
            const modal = document.querySelector('.delete-card');
            
            if (modal) {
                const firstFocusableElement = modal.querySelectorAll(focusableElements)[0];
                const focusableContent = modal.querySelectorAll(focusableElements);
                const lastFocusableElement = focusableContent[focusableContent.length - 1];
                
                document.addEventListener('keydown', function(e) {
                    let isTabPressed = e.key === 'Tab' || e.keyCode === 9;
                    
                    if (!isTabPressed) {
                        return;
                    }
                    
                    if (e.shiftKey) { 
                        if (document.activeElement === firstFocusableElement) {
                            lastFocusableElement.focus();
                            e.preventDefault();
                        }
                    } else {
                        if (document.activeElement === lastFocusableElement) {
                            firstFocusableElement.focus();
                            e.preventDefault();
                        }
                    }
                });
                
                firstFocusableElement.focus();
            }
        });
    </script>
</body>
</html>    