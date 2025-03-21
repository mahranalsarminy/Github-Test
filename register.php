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
$username = '';
$email = '';

// Process registration form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    
    // Validation
    $errors = [];
    
    if (empty($username)) {
        $errors[] = "Username is required.";
    } elseif (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = "Username must be between 3 and 50 characters.";
    }
    
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    } else {
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "This email is already registered.";
        }
    }
    
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    
    if (empty($errors)) {
        // Create the user account
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, created_at, last_ip) 
                              VALUES (?, ?, ?, 'free_user', NOW(), ?)");
        $result = $stmt->execute([$username, $email, $hashed_password, $ip_address]);
        
        if ($result) {
            $user_id = $pdo->lastInsertId();
            
            // Log successful registration
            $stmt = $pdo->prepare("INSERT INTO activities (user_id, description) VALUES (?, ?)");
            $stmt->execute([$user_id, "New user registered"]);
            
            $success = "Registration successful! You can now log in.";
            
            // Optionally auto-login the user after registration
            // $_SESSION['user_id'] = $user_id;
            // $_SESSION['username'] = $username;
            // $_SESSION['email'] = $email;
            // $_SESSION['role'] = 'free_user';
            // header('Location: index.php');
            // exit;
        } else {
            $error = "An error occurred during registration. Please try again.";
        }
    } else {
        $error = implode("<br>", $errors);
    }
}

// Process Google registration
if (isset($_GET['google_token'])) {
    // In a real implementation, you would validate the Google token
    // and extract the user data
    
    // This is a placeholder for demonstration purposes
    $google_email = ""; // Would come from token verification
    $google_name = ""; // Would come from token verification
    
    // Check if the user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$google_email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // User already exists, redirect to login
        $_SESSION['message'] = "An account with this email already exists. Please log in.";
        header('Location: login.php');
        exit;
    } else {
        // Create new user account
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
        $hashed_password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT); // Random secure password
        
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, created_at, last_ip) 
                             VALUES (?, ?, ?, 'free_user', NOW(), ?)");
        $result = $stmt->execute([$google_name, $google_email, $hashed_password, $ip_address]);
        
        if ($result) {
            $user_id = $pdo->lastInsertId();
            
            // Log successful registration
            $stmt = $pdo->prepare("INSERT INTO activities (user_id, description) VALUES (?, ?)");
            $stmt->execute([$user_id, "New user registered via Google"]);
            
            // Auto-login the user
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $google_name;
            $_SESSION['email'] = $google_email;
            $_SESSION['role'] = 'free_user';
            
            header('Location: index.php');
            exit;
        } else {
            $_SESSION['error'] = "An error occurred during registration. Please try again.";
            header('Location: register.php');
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $site_settings['language']; ?>" dir="<?php echo $site_settings['language'] === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo $site_settings['site_name']; ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/dark-mode.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Cairo|Roboto&display=swap">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Sign-In API -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <style>
        .register-container {
            max-width: 600px;
            margin: 3rem auto;
            padding: 2rem;
            background-color: var(--bg-card);
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .register-header h1 {
            font-size: 2rem;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .register-header p {
            color: var(--text-secondary);
        }
        
        .register-form {
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
        
        .social-register {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }
        
        .social-register p {
            margin-bottom: 1rem;
            color: var(--text-secondary);
        }
        
        .google-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            background-color: #fff;
            color: #757575;
            border: 1px solid #ddd;
            padding: 0.75rem;
            width: 100%;
            border-radius: 4px;
            font-weight: 500;
            cursor: pointer;
            transition: background-color 0.3s, box-shadow 0.3s;
            margin-bottom: 1rem;
        }
        
        .google-btn:hover {
            background-color: #f9f9f9;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }
        
        .google-btn img {
            height: 20px;
        }
        
        .login-link {
            text-align: center;
            margin-top: 1rem;
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
        
        .terms-checkbox {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }
        
        .terms-checkbox input {
            margin-right: 10px;
            margin-top: 5px;
        }
        
        .terms-checkbox label {
            font-size: 0.95rem;
            color: var(--text-secondary);
        }
        
        .terms-checkbox a {
            color: var(--accent-color);
            text-decoration: none;
        }
        
        .terms-checkbox a:hover {
            text-decoration: underline;
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
        
        /* Dark mode compatibility */
        .dark-mode .google-btn {
            background-color: #2d2d2d;
            color: #e0e0e0;
            border-color: #444;
        }
        
        .dark-mode .google-btn:hover {
            background-color: #3a3a3a;
        }
        
        /* RTL adjustments */
        html[dir="rtl"] .google-btn {
            flex-direction: row-reverse;
        }
        
        html[dir="rtl"] .terms-checkbox input {
            margin-right: 0;
            margin-left: 10px;
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
        <div class="register-container">
            <div class="register-header">
                <h1>Create an Account</h1>
                <p>Join <?php echo htmlspecialchars($site_settings['site_name']); ?> to access our exclusive collection of wallpapers and media.</p>
            </div>

            <?php if (!empty($error)): ?>
            <div class="alert alert-error" role="alert">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($success)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo $success; ?>
                <p><a href="login.php">Click here to log in</a></p>
            </div>
            <?php else: ?>
            <form class="register-form" method="post" action="register.php" id="registerForm">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="password-strength">
                        <div class="password-strength-meter" id="password-strength-meter"></div>
                                        <p class="password-requirements">Password must be at least 8 characters long.</p>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                </div>
                
                <div class="terms-checkbox">
                    <input type="checkbox" id="terms" name="terms" required>
                    <label for="terms">I agree to the <a href="pages/terms.php">Terms & Conditions</a> and <a href="pages/privacy.php">Privacy Policy</a></label>
                </div>
                
                <button type="submit" name="register" class="btn-primary">Create Account</button>
            </form>
            
            <div class="social-register">
                <p>Or register with</p>
                
                <!-- Google Sign-In Button -->
                <div id="g_id_onload"
                     data-client_id="YOUR_GOOGLE_CLIENT_ID"
                     data-context="signup"
                     data-ux_mode="popup"
                     data-callback="handleGoogleSignUp"
                     data-auto_prompt="false">
                </div>
                
                <div class="g_id_signin"
                     data-type="standard"
                     data-shape="rectangular"
                     data-theme="outline"
                     data-text="signup_with"
                     data-size="large"
                     data-logo_alignment="center"
                     data-width="100%">
                </div>
                
                <!-- Fallback Google button for browsers that don't support the Google Identity Services API -->
                <button type="button" class="google-btn" onclick="triggerGoogleSignUp()">
                    <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google logo">
                    Sign up with Google
                </button>
            </div>
            <?php endif; ?>
            
            <div class="login-link">
                Already have an account? <a href="login.php">Log in</a>
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
        // Handle Google Sign-Up
        function handleGoogleSignUp(response) {
            // The ID token you need to pass to your backend
            const id_token = response.credential;
            
            // Send the token to your server
            const form = document.createElement('form');
            form.method = 'GET';
            form.action = 'register.php';
            
            const hiddenField = document.createElement('input');
            hiddenField.type = 'hidden';
            hiddenField.name = 'google_token';
            hiddenField.value = id_token;
            
            form.appendChild(hiddenField);
            document.body.appendChild(form);
            form.submit();
        }
        
        // Fallback function for browsers that don't support the Google Identity Services API
        function triggerGoogleSignUp() {
            document.querySelector('.g_id_signin').click();
        }
        
        // Form validation and password strength meter
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registerForm');
            const passwordInput = document.getElementById('password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const passwordStrengthMeter = document.getElementById('password-strength-meter');
            
            if (form) {
                form.addEventListener('submit', function(e) {
                    let isValid = true;
                    
                    // Clear previous error messages
                    document.querySelectorAll('.form-error').forEach(el => el.remove());
                    
                    // Username validation
                    const username = document.getElementById('username').value.trim();
                    if (username === '') {
                        isValid = false;
                        showError('username', 'Username is required');
                    } else if (username.length < 3 || username.length > 50) {
                        isValid = false;
                        showError('username', 'Username must be between 3 and 50 characters');
                    }
                    
                    // Email validation
                    const email = document.getElementById('email').value.trim();
                    if (email === '') {
                        isValid = false;
                        showError('email', 'Email is required');
                    } else if (!isValidEmail(email)) {
                        isValid = false;
                        showError('email', 'Please enter a valid email address');
                    }
                    
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
                    
                    // Terms agreement validation
                    const termsCheckbox = document.getElementById('terms');
                    if (!termsCheckbox.checked) {
                        isValid = false;
                        showError('terms', 'You must agree to the Terms & Conditions and Privacy Policy');
                    }
                    
                    if (!isValid) {
                        e.preventDefault();
                    }
                });
                
                // Password strength meter
                passwordInput.addEventListener('input', function() {
                    const strength = calculatePasswordStrength(this.value);
                    updatePasswordStrengthMeter(strength);
                });
                
                // Confirm password validation
                confirmPasswordInput.addEventListener('input', function() {
                    if (this.value !== passwordInput.value) {
                        this.setCustomValidity("Passwords don't match");
                    } else {
                        this.setCustomValidity('');
                    }
                });
            }
            
            function showError(fieldId, message) {
                const field = document.getElementById(fieldId);
                const errorDiv = document.createElement('div');
                errorDiv.className = 'form-error';
                errorDiv.style.color = '#e74c3c';
                errorDiv.style.fontSize = '0.875rem';
                errorDiv.style.marginTop = '0.375rem';
                errorDiv.textContent = message;
                
                if (field.type === 'checkbox') {
                    field.parentNode.appendChild(errorDiv);
                } else {
                    field.parentNode.appendChild(errorDiv);
                }
                
                if (field.type !== 'checkbox') {
                    field.style.borderColor = '#e74c3c';
                }
                
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
                passwordStrengthMeter.style.width = strength + '%';
                
                if (strength === 0) {
                    passwordStrengthMeter.className = '';
                } else if (strength <= 25) {
                    passwordStrengthMeter.className = 'weak';
                } else if (strength <= 50) {
                    passwordStrengthMeter.className = 'medium';
                } else if (strength <= 75) {
                    passwordStrengthMeter.className = 'strong';
                } else {
                    passwordStrengthMeter.className = 'very-strong';
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
            }
        });
    </script>
</body>
</html>