<?php
require_once(__DIR__ . '/includes/init.php');
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Fetch site settings
$stmt = $pdo->query("SELECT site_name, site_logo, dark_mode, language FROM site_settings WHERE id = 1");
$site_settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Initialize variables
$error = '';
$success = '';
$email = '';
$step = 'request'; // 'request' or 'reset'

// Function to generate a secure random token
function generateToken($length = 32) {
    return bin2hex(random_bytes($length));
}

// Check if this is a reset token verification
if (isset($_GET['token']) && !empty($_GET['token'])) {
    $token = $_GET['token'];
    $email = isset($_GET['email']) ? $_GET['email'] : '';
    
    // Verify token exists and is valid
    $stmt = $pdo->prepare("SELECT * FROM password_reset_tokens 
                          WHERE token = ? AND email = ? AND expires_at > NOW() AND used = 0");
    $stmt->execute([$token, $email]);
    $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($tokenData) {
        $step = 'reset'; // Valid token, show reset form
    } else {
        $error = "This password reset link is invalid or has expired. Please request a new one.";
    }
}

// Process password request form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_reset'])) {
    $email = trim($_POST['email']);
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    
    // Simple validation
    if (empty($email)) {
        $error = "Please enter your email address.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if the email exists
        $stmt = $pdo->prepare("SELECT id, username, email, active FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            if ($user['active'] == 1) {
                // Generate a reset token
                $token = generateToken();
                $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
                
                // Check for existing tokens and invalidate them
                $stmt = $pdo->prepare("UPDATE password_reset_tokens SET used = 1 WHERE email = ? AND used = 0");
                $stmt->execute([$email]);
                
                // Insert new token
                $stmt = $pdo->prepare("INSERT INTO password_reset_tokens (email, token, created_at, expires_at, ip_address) 
                                      VALUES (?, ?, NOW(), ?, ?)");
                $stmt->execute([$email, $token, $expires, $ip_address]);
                
                // Build reset URL
                $resetUrl = "https://{$_SERVER['HTTP_HOST']}/forgot-password.php?token=" . urlencode($token) . "&email=" . urlencode($email);
                
                // In a real implementation, you would send an email here
                // For demonstration purposes, we'll just show the success message
                // and link (in real-world, you'd use a proper email function)
                
                // Log the password reset request
                $stmt = $pdo->prepare("INSERT INTO activities (user_id, description, ip_address) VALUES (?, ?, ?)");
                $stmt->execute([$user['id'], "Password reset requested", $ip_address]);
                
                $success = "A password reset link has been sent to your email. Please check your inbox.";
                
                // For demo purposes only - would be removed in production
                // $success .= " <a href='$resetUrl'>Click here to reset your password</a>";
            } else {
                $error = "This account has been deactivated. Please contact support.";
            }
        } else {
            // For security reasons, show the same success message even if email doesn't exist
            $success = "If this email is registered in our system, a password reset link has been sent. Please check your inbox.";
        }
    }
}

// Process password reset form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_password'])) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $token = $_POST['token'];
    $email = $_POST['email'];
    
    // Validate passwords
    if (empty($password)) {
        $error = "Please enter a new password.";
    } elseif (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Verify token is valid
        $stmt = $pdo->prepare("SELECT * FROM password_reset_tokens 
                              WHERE token = ? AND email = ? AND expires_at > NOW() AND used = 0");
        $stmt->execute([$token, $email]);
        $tokenData = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($tokenData) {
            // Token is valid, update the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = ?");
            $result = $stmt->execute([$hashed_password, $email]);
            
            if ($result) {
                // Mark token as used
                $stmt = $pdo->prepare("UPDATE password_reset_tokens SET used = 1 WHERE token = ?");
                $stmt->execute([$token]);
                
                // Get user id for activity log
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                $userId = $stmt->fetchColumn();
                
                // Log the password change
                $stmt = $pdo->prepare("INSERT INTO activities (user_id, description, ip_address) VALUES (?, ?, ?)");
                $stmt->execute([$userId, "Password reset successful", $_SERVER['REMOTE_ADDR'] ?? '']);
                
                $success = "Your password has been successfully reset. You can now log in with your new password.";
                $step = 'success'; // Show success message
            } else {
                $error = "An error occurred. Please try again.";
                $step = 'request'; // Go back to request step
            }
        } else {
            $error = "This password reset link is invalid or has expired. Please request a new one.";
            $step = 'request'; // Go back to request step
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $site_settings['language']; ?>" dir="<?php echo $site_settings['language'] === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?php echo $site_settings['site_name']; ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/dark-mode.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Cairo|Roboto&display=swap">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .forgot-container {
            max-width: 500px;
            margin: 3rem auto;
            padding: 2rem;
            background-color: var(--bg-card);
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .forgot-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .forgot-header h1 {
            font-size: 2rem;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .forgot-header p {
            color: var(--text-secondary);
            line-height: 1.6;
        }
        
        .forgot-form {
            margin-bottom: 2rem;
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
        
        .btn-primary {
            display: inline-block;
            width: 100%;
            background-color: var(--accent-color);
            color: #fff;
            border: none;
            padding: 0.75rem;
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
        
        .alert-success {
            background-color: rgba(46, 204, 113, 0.1);
            border: 1px solid #2ecc71;
            color: #2ecc71;
        }
        
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: var(--text-secondary);
        }
        
        .login-link a {
            color: var(--accent-color);
            text-decoration: none;
        }
        
        .login-link a:hover {
            text-decoration: underline;
        }
        
        .password-requirements {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-top: 0.5rem;
        }
        
        .password-strength {
            height: 5px;
            margin-top: 8px;
            border-radius: 2px;
            background-color: #eee;
            overflow: hidden;
        }
        
        .password-strength-meter {
            height: 100%;
            width: 0%;
            transition: width 0.3s, background-color 0.3s;
        }
        
        .weak { width: 25%; background-color: #e74c3c; }
        .medium { width: 50%; background-color: #f39c12; }
        .strong { width: 75%; background-color: #3498db; }
        .very-strong { width: 100%; background-color: #2ecc71; }
        
        .success-icon {
            text-align: center;
            margin: 1rem 0 2rem;
        }
        
        .success-icon i {
            font-size: 4rem;
            color: #2ecc71;
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
        <div class="forgot-container">
            <?php if ($step === 'request'): ?>
                <div class="forgot-header">
                    <h1>Forgot Password</h1>
                    <p>Enter your email address below and we'll send you a link to reset your password.</p>
                </div>
                
                <?php if (!empty($error)): ?>
                <div class="alert alert-error" role="alert">
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                <div class="alert alert-success" role="alert">
                    <?php echo $success; ?>
                </div>
                <?php else: ?>
                <form class="forgot-form" method="post" action="forgot-password.php" id="forgotForm">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>
                    
                    <button type="submit" name="request_reset" class="btn-primary">Send Reset Link</button>
                </form>
                <?php endif; ?>
            
            <?php elseif ($step === 'reset'): ?>
                <div class="forgot-header">
                    <h1>Reset Your Password</h1>
                    <p>Please enter a new password for your account.</p>
                </div>
                
                <?php if (!empty($error)): ?>
                <div class="alert alert-error" role="alert">
                    <?php echo $error; ?>
                </div>
                <?php endif; ?>
                
                <form class="forgot-form" method="post" action="forgot-password.php" id="resetForm">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
                    
                    <div class="form-group">
                        <label for="password">New Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                        <div class="password-strength">
                            <div class="password-strength-meter" id="password-strength-meter"></div>
                        </div>
                        <p class="password-requirements">Password must be at least 8 characters long.</p>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password">Confirm New Password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    
                                        <button type="submit" name="reset_password" class="btn-primary">Reset Password</button>
                </form>
            
            <?php elseif ($step === 'success'): ?>
                <div class="forgot-header">
                    <h1>Password Reset Complete</h1>
                </div>
                
                <div class="success-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                
                <div class="alert alert-success" role="alert">
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <div class="login-link">
                <a href="login.php">Back to Login</a>
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
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const forgotForm = document.getElementById('forgotForm');
            const resetForm = document.getElementById('resetForm');
            
            // Validation for forgot password form
            if (forgotForm) {
                forgotForm.addEventListener('submit', function(e) {
                    const email = document.getElementById('email').value.trim();
                    let isValid = true;
                    
                    // Clear previous error messages
                    document.querySelectorAll('.form-error').forEach(el => el.remove());
                    
                    if (email === '') {
                        isValid = false;
                        showError('email', 'Email is required');
                    } else if (!isValidEmail(email)) {
                        isValid = false;
                        showError('email', 'Please enter a valid email address');
                    }
                    
                    if (!isValid) {
                        e.preventDefault();
                    }
                });
            }
            
            // Validation for reset password form
            if (resetForm) {
                const passwordInput = document.getElementById('password');
                const confirmPasswordInput = document.getElementById('confirm_password');
                const passwordStrengthMeter = document.getElementById('password-strength-meter');
                
                resetForm.addEventListener('submit', function(e) {
                    let isValid = true;
                    
                    // Clear previous error messages
                    document.querySelectorAll('.form-error').forEach(el => el.remove());
                    
                    // Password validation
                    const password = passwordInput.value;
                    if (password === '') {
                        isValid = false;
                        showError('password', 'Password is required');
                    } else if (password.length < 8) {
                        isValid = false;
                        showError('password', 'Password must be at least 8 characters long');
                    }
                    
                    // Confirm password validation
                    const confirmPassword = confirmPasswordInput.value;
                    if (confirmPassword === '') {
                        isValid = false;
                        showError('confirm_password', 'Please confirm your password');
                    } else if (confirmPassword !== password) {
                        isValid = false;
                        showError('confirm_password', 'Passwords do not match');
                    }
                    
                    if (!isValid) {
                        e.preventDefault();
                    }
                });
                
                // Password strength meter
                if (passwordInput && passwordStrengthMeter) {
                    passwordInput.addEventListener('input', function() {
                        const strength = calculatePasswordStrength(this.value);
                        updatePasswordStrengthMeter(strength);
                    });
                }
                
                // Confirm password validation
                if (confirmPasswordInput && passwordInput) {
                    confirmPasswordInput.addEventListener('input', function() {
                        if (this.value !== passwordInput.value) {
                            this.setCustomValidity("Passwords don't match");
                        } else {
                            this.setCustomValidity('');
                        }
                    });
                }
            }
            
            function showError(fieldId, message) {
                const field = document.getElementById(fieldId);
                const errorDiv = document.createElement('div');
                errorDiv.className = 'form-error';
                errorDiv.style.color = '#e74c3c';
                errorDiv.style.fontSize = '0.875rem';
                errorDiv.style.marginTop = '0.375rem';
                errorDiv.textContent = message;
                
                field.parentNode.appendChild(errorDiv);
                field.style.borderColor = '#e74c3c';
                
                // Add aria attributes for accessibility
                field.setAttribute('aria-invalid', 'true');
                errorDiv.id = fieldId + '-error';
                field.setAttribute('aria-describedby', errorDiv.id);
            }
            
            function isValidEmail(email) {
                const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                return re.test(String(email).toLowerCase());
            }
            
            function calculatePasswordStrength(password) {
                let strength = 0;
                
                // Length check
                if (password.length >= 8) {
                    strength += 1;
                }
                if (password.length >= 12) {
                    strength += 1;
                }
                
                // Character variety checks
                if (/[A-Z]/.test(password)) { // Has uppercase
                    strength += 1;
                }
                if (/[a-z]/.test(password)) { // Has lowercase
                    strength += 1;
                }
                if (/[0-9]/.test(password)) { // Has number
                    strength += 1;
                }
                if (/[^A-Za-z0-9]/.test(password)) { // Has special character
                    strength += 1;
                }
                
                return Math.min(Math.floor(strength * 100 / 6), 100); // Convert to percentage
            }
            
            function updatePasswordStrengthMeter(strength) {
                const meter = document.getElementById('password-strength-meter');
                if (!meter) return;
                
                meter.style.width = strength + '%';
                
                if (strength === 0) {
                    meter.className = '';
                } else if (strength <= 25) {
                    meter.className = 'weak';
                } else if (strength <= 50) {
                    meter.className = 'medium';
                } else if (strength <= 75) {
                    meter.className = 'strong';
                } else {
                    meter.className = 'very-strong';
                }
            }
        });
        
        // Accessibility enhancements
        document.addEventListener('DOMContentLoaded', function() {
            // Focus first field with error on page load
            const errorFields = document.querySelectorAll('.form-error');
            if (errorFields.length > 0) {
                const firstErrorField = errorFields[0].previousElementSibling;
                if (firstErrorField) {
                    firstErrorField.focus();
                }
            } else {
                // Focus first input field on the form
                const firstInput = document.querySelector('form input:not([type="hidden"])');
                if (firstInput) {
                    firstInput.focus();
                }
            }
        });
    </script>
</body>
</html>