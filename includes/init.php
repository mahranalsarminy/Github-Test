<?php
// Start the session only if it hasn't already been started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load environment variables from .env file
require_once __DIR__ . '/../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

// Database connection using PDO
try {
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_NAME'] ?? 'codehub_wwqqq';
    $username = $_ENV['DB_USER'] ?? 'root';
    $password = $_ENV['DB_PASSWORD'] ?? '';
    $charset = 'utf8mb4';

    $dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    $pdo = new PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

// Globalize the $pdo object for use in other scripts
$GLOBALS['pdo'] = $pdo;

// Helper functions
function get_media_count(): int {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) FROM media");
    return (int) $stmt->fetchColumn();
}

function get_user_count(): int {
    global $pdo;
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    return (int) $stmt->fetchColumn();
}

function get_subscription_stats(): array {
    global $pdo;
    $stmt = $pdo->query("SELECT status, COUNT(*) AS count FROM user_subscriptions GROUP BY status");
    $stats = ['active' => 0, 'expired' => 0, 'cancelled' => 0];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $stats[$row['status']] = $row['count'];
    }
    return $stats;
}

function get_recent_media(int $limit): array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, title, url AS thumbnail, created_at FROM media ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_recent_users(int $limit): array {
    global $pdo;
    $stmt = $pdo->prepare("SELECT id, email, role, created_at FROM users ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function get_site_settings(): array {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM site_settings LIMIT 1");
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

// Redirect function
function redirect(string $url): void {
    header("Location: $url");
    exit;
}

// Authentication check
function is_logged_in(): bool {
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        return true;
    }
    
    if (isset($_COOKIE['remember_token']) && !empty($_COOKIE['remember_token'])) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM auth_tokens WHERE token = ? AND expires_at > NOW()");
            $stmt->execute([$_COOKIE['remember_token']]);
            $token = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($token) {
                $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                $stmt->execute([$token['user_id']]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    
                    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    
                    $activity_stmt = $pdo->prepare("INSERT INTO activities (user_id, description) VALUES (?, ?)");
                    $activity_stmt->execute([$user['id'], 'Logged in via remember me cookie']);
                    
                    return true;
                }
            }
        } catch (PDOException $e) {
            error_log("Remember me error: " . $e->getMessage());
        }
        
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    return false;
}

function is_admin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function require_login(): void {
    if (!is_logged_in()) {
        redirect('/login.php');
    }
}

function require_admin(): void {
    if (!is_admin()) {
        redirect('/');
    }
}

/**
 * Get current logged-in user data
 *
 * @return array|null User data or null if not logged in
 */
function get_logged_user() {
    if (!is_logged_in()) {
        return null;
    }
    
    global $pdo;
    try {
        $stmt = $pdo->prepare("SELECT id, username, email, profile_picture, role, bio, created_at, last_login 
                               FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching user data: " . $e->getMessage());
        return null;
    }
}

/**
 * Log a user out
 *
 * @param bool $clear_remember Whether to clear the remember me cookie
 * @return void
 */
function logout_user($clear_remember = true): void {
    if (isset($_SESSION['user_id'])) {
        global $pdo;
        try {
            $activity_stmt = $pdo->prepare("INSERT INTO activities (user_id, description) VALUES (?, ?)");
            $activity_stmt->execute([$_SESSION['user_id'], 'User logged out']);
            
            if ($clear_remember && isset($_COOKIE['remember_token'])) {
                $token = $_COOKIE['remember_token'];
                $stmt = $pdo->prepare("DELETE FROM auth_tokens WHERE token = ?");
                $stmt->execute([$token]);
                setcookie('remember_token', '', time() - 3600, '/');
            }
        } catch (PDOException $e) {
            error_log("Logout error: " . $e->getMessage());
        }
    }
    
    $_SESSION = [];
    session_destroy();
}

/**
 * Check if a username or email is available
 *
 * @param string $username The username to check
 * @param string $email The email to check
 * @param int $exclude_user_id User ID to exclude (for updates)
 * @return array An array with 'username' and 'email' keys indicating availability
 */
function check_credentials_availability($username, $email, $exclude_user_id = 0): array {
    global $pdo;
    $result = ['username' => true, 'email' => true];
    
    try {
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $exclude_user_id]);
        if ($stmt->fetchColumn()) {
            $result['username'] = false;
        }
        
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $exclude_user_id]);
        if ($stmt->fetchColumn()) {
            $result['email'] = false;
        }
    } catch (PDOException $e) {
        error_log("Error checking credentials availability: " . $e->getMessage());
    }
    
    return $result;
}

/**
 * Update user profile
 *
 * @param int $user_id User ID
 * @param array $data Profile data to update
 * @return bool Whether the update was successful
 */
function update_user_profile($user_id, $data): bool {
    global $pdo;
    $allowed_fields = ['username', 'email', 'bio', 'profile_picture'];
    $update_data = array_intersect_key($data, array_flip($allowed_fields));
    
    if (empty($update_data)) {
        return false;
    }
    
    try {
        $sql = "UPDATE users SET ";
        $params = [];
        
        foreach ($update_data as $field => $value) {
            $sql .= "$field = ?, ";
            $params[] = $value;
        }
        
        $sql = rtrim($sql, ', ') . ", updated_at = NOW() WHERE id = ?";
        $params[] = $user_id;
        
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log("Error updating user profile: " . $e->getMessage());
        return false;
    }
}

/**
 * Change user password
 *
 * @param int $user_id User ID
 * @param string $current_password Current password
 * @param string $new_password New password
 * @return array Result with 'success' boolean and 'message' string
 */
function change_user_password($user_id, $current_password, $new_password): array {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return ['success' => false, 'message' => 'المستخدم غير موجود'];
        }
        
        if (!password_verify($current_password, $user['password'])) {
            return ['success' => false, 'message' => 'كلمة المرور الحالية غير صحيحة'];
        }
        
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$hashed_password, $user_id]);
        
        $activity_stmt = $pdo->prepare("INSERT INTO activities (user_id, description) VALUES (?, ?)");
        $activity_stmt->execute([$user_id, 'Password changed']);
        
        return ['success' => true, 'message' => 'تم تغيير كلمة المرور بنجاح'];
    } catch (PDOException $e) {
        error_log("Error changing password: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'حدث خطأ أثناء تغيير كلمة المرور'
        ];
    }
}

/**
 * Generate a random token
 *
 * @param int $length Token length
 * @return string Random token
 */
function generate_token($length = 32): string {
    return bin2hex(random_bytes($length / 2));
}

// Debugging function
function dd($data): void {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    exit;
}

// Secure output function
function h($string): string {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// CSRF token functions
function generate_csrf_token(): string {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf_token($token): bool {
    return isset($_SESSION['csrf_token']) && $_SESSION['csrf_token'] === $token;
}

// Define site URL from environment variables or server info
define('SITE_URL', $_ENV['BASE_URL'] ?? ('https://' . $_SERVER['HTTP_HOST']));
define('BASE_PATH', __DIR__ . '/../');

// Set default timezone
date_default_timezone_set('UTC');
?>