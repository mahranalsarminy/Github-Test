<?php
require_once(__DIR__ . '/includes/init.php');

// Fetch site settings
$stmt = $pdo->query("SELECT site_name, site_logo, dark_mode, language FROM site_settings WHERE id = 1");
$site_settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Safe echo function
function safe_echo($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Set cookie to show account deleted message only once
$show_message = true;
if (!isset($_COOKIE['account_deleted'])) {
    setcookie('account_deleted', '1', time() + 3600, '/'); // 1-hour cookie
} else {
    $show_message = false;
    // Redirect to homepage if someone visits this page directly without deletion
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="<?php echo $site_settings['language']; ?>" dir="<?php echo $site_settings['language'] === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Deleted - <?php echo safe_echo($site_settings['site_name']); ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/dark-mode.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Cairo|Roboto&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <meta http-equiv="refresh" content="10;url=index.php">
    <style>
        .confirmation-container {
            max-width: 600px;
            margin: 4rem auto;
            padding: 0 1rem;
            text-align: center;
        }
        
        .confirmation-card {
            background-color: var(--bg-card);
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            padding: 3rem 2rem;
        }
        
        .success-icon {
            font-size: 5rem;
            color: #2ecc71;
            margin-bottom: 1.5rem;
        }
        
        .confirmation-card h1 {
            font-size: 2rem;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }
        
        .confirmation-card p {
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
            font-size: 1.1rem;
        }
        
        .timer {
            margin: 2rem 0;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }
        
        .btn {
            display: inline-block;
            padding: 0.75rem 1.5rem;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s, color 0.3s;
            text-decoration: none;
        }
        
        .btn-primary {
            background-color: var(--accent-color);
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background-color: var(--accent-hover);
        }
    </style>
</head>
<body class="<?php echo $site_settings['dark_mode'] ? 'dark-mode' : 'light-mode'; ?>">
    <!-- Skip to main content link for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <!-- Header (simplified) -->
    <header>
        <div style="text-align: center; padding: 1rem;">
            <a href="index.php">
                <img src="<?php echo safe_echo($site_settings['site_logo']); ?>" alt="<?php echo safe_echo($site_settings['site_name']); ?>" style="max-height: 50px;">
            </a>
        </div>
    </header>

    <!-- Main Content -->
    <main id="main-content" role="main">
        <div class="confirmation-container">
            <div class="confirmation-card">
                <i class="fas fa-check-circle success-icon"></i>
                <h1>Account Successfully Deleted</h1>
                <p>Your account has been permanently deleted and all your data has been removed from our system.</p>
                <p>Thank you for being a member of our community. We're sorry to see you go.</p>
                <p>If you ever change your mind, you're always welcome to create a new account.</p>
                
                <div class="timer">You will be redirected to the homepage in <span id="countdown">10</span> seconds...</div>
                
                <a href="index.php" class="btn btn-primary">Return to Homepage</a>
            </div>
            
            <!-- Last updated indicator -->
            <p style="text-align: center; margin-top: 2rem; color: var(--text-secondary); font-size: 0.8rem;">
                Last updated: 2025-03-20 06:33:31 UTC
            </p>
        </div>
    </main>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Clear all user cookies/local storage
            document.cookie.split(';').forEach(function(cookie) {
                const [name] = cookie.trim().split('=');
                if (name !== 'account_deleted') {
                    document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;`;
                }
            });
            
            // Clear local storage
            localStorage.clear();
            
            // Countdown timer
            let countdown = 10;
            const countdownElement = document.getElementById('countdown');
            
            setInterval(function() {
                countdown--;
                countdownElement.textContent = countdown;
                
                if (countdown <= 0) {
                    window.location.href = 'index.php';
                }
            }, 1000);
        });
    </script>
</body>
</html>