<?php
require_once(__DIR__ . '/includes/init.php');
session_start();

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: profile.php');
    exit;
}

// Fetch site settings
$stmt = $pdo->query("SELECT site_name, site_logo, dark_mode, language FROM site_settings WHERE id = 1");
$site_settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Initialize variables
$error = '';
$email = '';

// Check for login attempts limit
function checkLoginAttempts($pdo, $email) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts 
                          WHERE email = ? AND status = 'failed' 
                          AND created_at > DATE_SUB(NOW(), INTERVAL 30 MINUTE)");
    $stmt->execute([$email]);
    return $stmt->fetchColumn();
}

// Record login attempt
function recordLoginAttempt($pdo, $email, $status, $ip_address, $is_admin = 0, $details = null) {
    $stmt = $pdo->prepare("INSERT INTO login_attempts (email, status, ip_address, is_admin, attempt_details) 
                          VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$email, $status, $ip_address, $is_admin, $details]);
}

// Process login form
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    
    // Simple validation
    if (empty($email) || empty($password)) {
        $error = "Please enter both email and password.";
    } else {
        // Check for excessive login attempts
        $attempts = checkLoginAttempts($pdo, $email);
        if ($attempts >= 5) {
            $error = "Too many failed login attempts. Please try again later.";
            
            // Set lock time if not already set
            $stmt = $pdo->prepare("UPDATE login_attempts SET lock_time = NOW() 
                                  WHERE email = ? AND lock_time IS NULL");
            $stmt->execute([$email]);
        } else {
            // Verify user credentials
            $stmt = $pdo->prepare("SELECT id, username, email, password, role, active FROM users WHERE email = ?");
            $stmt->execute([$email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password']) && $user['active'] == 1) {
                // Successful login
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                
                // Record successful login
                recordLoginAttempt($pdo, $email, 'successful', $ip_address, ($user['role'] === 'admin' ? 1 : 0));
                
                // Update last login and IP
                $stmt = $pdo->prepare("UPDATE users SET last_login = NOW(), last_ip = ? WHERE id = ?");
                $stmt->execute([$ip_address, $user['id']]);
                
                // Record activity
                if ($user['role'] === 'admin') {
                    $stmt = $pdo->prepare("INSERT INTO activities (user_id, description) VALUES (?, ?)");
                    $stmt->execute([$user['id'], "Successfully logged in"]);
                }
                
                // Redirect to homepage
                header('Location: profile.php');
                exit;
            } else {
                // Failed login
                if (!$user) {
                    $error = "Email not found.";
                } elseif ($user['active'] != 1) {
                    $error = "Account is disabled. Please contact support.";
                } else {
                    $error = "Invalid password.";
                }
                
                // Record failed login attempt
                recordLoginAttempt(
                    $pdo, 
                    $email, 
                    'failed', 
                    $ip_address, 
                    0, 
                    json_encode(['reason' => $error, 'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''])
                );
            }
        }
    }
}

// Process Google login
if (isset($_GET['google_token'])) {
    // In a real implementation, you would validate the Google token
    // and extract the user data
    
    // This is a placeholder for demonstration purposes
    $google_email = ""; // Would come from token verification
    $google_name = ""; // Would come from token verification
    
    // Check if the user exists
    $stmt = $pdo->prepare("SELECT id, username, email, role FROM users WHERE email = ?");
    $stmt->execute([$google_email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // User exists, log them in
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        
        // Record successful login
        recordLoginAttempt($pdo, $google_email, 'successful', $_SERVER['REMOTE_ADDR'] ?? '', ($user['role'] === 'admin' ? 1 : 0), 'Google login');
        
        // Redirect to homepage
        header('Location: profile.php');
        exit;
    } else {
        // User doesn't exist, create a new account
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, created_at) VALUES (?, ?, ?, 'free_user', NOW())");
        $hash = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT); // Random secure password
        $stmt->execute([$google_name, $google_email, $hash]);
        
        $new_user_id = $pdo->lastInsertId();
        
        // Log the new user in
        $_SESSION['user_id'] = $new_user_id;
        $_SESSION['username'] = $google_name;
        $_SESSION['email'] = $google_email;
        $_SESSION['role'] = 'free_user';
        
        // Redirect to homepage
        header('Location: profile.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $site_settings['language']; ?>" dir="<?php echo $site_settings['language'] === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo $site_settings['site_name']; ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/dark-mode.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Cairo|Roboto&display=swap">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Google Sign-In API -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <style>
        .login-container {
            max-width: 500px;
            margin: 3rem auto;
            padding: 2rem;
            background-color: var(--bg-card);
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        
        .login-header h1 {
            font-size: 2rem;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .login-header p {
            color: var(--text-secondary);
        }
        
        .login-form {
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
        
        .social-login {
            text-align: center;
            margin-top: 2rem;
            padding-top: 2rem;
            border-top: 1px solid var(--border-color);
        }
        
        .social-login p {
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
        
        .register-link {
            text-align: center;
            margin-top: 1rem;
            color: var(--text-secondary);
        }
        
        .register-link a {
            color: var(--accent-color);
            text-decoration: none;
        }
        
        .register-link a:hover {
            text-decoration: underline;
        }
        
        .forgot-password {
            display: block;
            text-align: right;
            margin-top: 0.5rem;
            color: var(--text-secondary);
            text-decoration: none;
            font-size: 0.9rem;
        }
        
        .forgot-password:hover {
            color: var(--accent-color);
            text-decoration: underline;
        }
        
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
        html[dir="rtl"] .forgot-password {
            text-align: left;
        }
        
        html[dir="rtl"] .google-btn {
            flex-direction: row-reverse;
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
        <div class="login-container">
            <div class="login-header">
                <h1>Login to Your Account</h1>
                <p>Welcome back! Please enter your credentials to access your account.</p>
            </div>

            <?php if (!empty($error)): ?>
            <div class="alert alert-error" role="alert">
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <form class="login-form" method="post" action="login.php">
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <a href="forgot-password.php" class="forgot-password">Forgot password?</a>
                </div>
                
                <button type="submit" name="login" class="btn-primary">Login</button>
            </form>
            
            <div class="social-login">
                <p>Or sign in with</p>
                
                <!-- Google Sign-In Button -->
                <div id="g_id_onload"
                     data-client_id="YOUR_GOOGLE_CLIENT_ID"
                     data-context="signin"
                     data-ux_mode="popup"
                     data-callback="handleGoogleSignIn"
                     data-auto_prompt="false">
                </div>
                
                <div class="g_id_signin"
                     data-type="standard"
                     data-shape="rectangular"
                     data-theme="outline"
                     data-text="signin_with"
                     data-size="large"
                     data-logo_alignment="center"
                     data-width="100%">
                </div>
                
                <!-- Fallback Google button for browsers that don't support the Google Identity Services API -->
                <button type="button" class="google-btn" onclick="triggerGoogleSignIn()">
                                        <img src="https://www.gstatic.com/firebasejs/ui/2.0.0/images/auth/google.svg" alt="Google logo">
                    Sign in with Google
                </button>
            </div>
            
            <div class="register-link">
                Don't have an account? <a href="register.php">Register</a>
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
        // Handle Google Sign-In
        function handleGoogleSignIn(response) {
            // The ID token you need to pass to your backend
            const id_token = response.credential;
            
            // Send the token to your server
            const form = document.createElement('form');
            form.method = 'GET';
            form.action = 'login.php';
            
            const hiddenField = document.createElement('input');
            hiddenField.type = 'hidden';
            hiddenField.name = 'google_token';
            hiddenField.value = id_token;
            
            form.appendChild(hiddenField);
            document.body.appendChild(form);
            form.submit();
        }
        
        // Fallback function for browsers that don't support the Google Identity Services API
        function triggerGoogleSignIn() {
            document.querySelector('.g_id_signin').click();
        }
        
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('.login-form');
            
            form.addEventListener('submit', function(e) {
                const email = document.getElementById('email').value.trim();
                const password = document.getElementById('password').value.trim();
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
                
                if (password === '') {
                    isValid = false;
                    showError('password', 'Password is required');
                }
                
                if (!isValid) {
                    e.preventDefault();
                }
            });
            
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
            }
            
            function isValidEmail(email) {
                const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
                return re.test(String(email).toLowerCase());
            }
        });
        
        // Accessibility enhancements
        document.addEventListener('DOMContentLoaded', function() {
            // Add aria-invalid attribute to form fields with errors
            const errorFields = document.querySelectorAll('.form-error');
            errorFields.forEach(error => {
                const field = error.previousElementSibling;
                if (field) {
                    field.setAttribute('aria-invalid', 'true');
                    field.setAttribute('aria-describedby', field.id + '-error');
                    error.id = field.id + '-error';
                }
            });
            
            // Focus first field with error on page load
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