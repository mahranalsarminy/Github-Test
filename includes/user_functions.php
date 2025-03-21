<?php
/**
 * User Functions - Contains all user-related functions
 */

/**
 * Check if a user is logged in
 *
 * @return bool
 */
function is_logged_in() {
    if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
    // Check if user is logged in via session
    if (isset($_SESSION['user_id']) && !empty($_SESSION['user_id'])) {
        return true;
    }
    
    // Check if user is logged in via remember me cookie
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
                    // Log the user in via session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    
                    // Update last login time
                    $stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                    $stmt->execute([$user['id']]);
                    
                    // Log activity
                    $activity_stmt = $pdo->prepare("INSERT INTO activities (user_id, description) VALUES (?, ?)");
                    $activity_stmt->execute([$user['id'], 'Logged in via remember me cookie']);
                    
                    return true;
                }
            }
        } catch (PDOException $e) {
            error_log("Remember me error: " . $e->getMessage());
        }
        
        // Invalid or expired token, clear the cookie
        setcookie('remember_token', '', time() - 3600, '/');
    }
    
    return false;
}

/**
 * Check if a user is an admin
 *
 * @return bool
 */
function is_admin() {
    return (isset($_SESSION['role']) && $_SESSION['role'] === 'admin');
}

/**
 * Get current user data
 *
 * @return array|null User data or null if not logged in
 */
function get_current_user() {
    if (!is_logged_in()) {
        return null;
    }
    
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT id, username, email, profile_picture, role, bio, created_at, last_login FROM users WHERE id = ?");
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
function logout_user($clear_remember = true) {
    // Log the activity
    if (isset($_SESSION['user_id'])) {
        global $pdo;
        try {
            $activity_stmt = $pdo->prepare("INSERT INTO activities (user_id, description) VALUES (?, ?)");
            $activity_stmt->execute([$_SESSION['user_id'], 'User logged out']);
            
            // If clear remember token is requested
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
    
    // Clear session variables
    $_SESSION = array();
    
    // Destroy the session
    session_destroy();
}

/**
 * Redirect the user to another page
 *
 * @param string $location The URL to redirect to
 * @return void
 */
function redirect($location) {
    header("Location: $location");
    exit;
}

/**
 * Get user by ID
 *
 * @param int $user_id The user ID
 * @return array|null User data or null if not found
 */
function get_user_by_id($user_id) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT id, username, email, profile_picture, role, bio, created_at, last_login FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching user by ID: " . $e->getMessage());
        return null;
    }
}

/**
 * Get user by username or email
 *
 * @param string $identifier Username or email
 * @return array|null User data or null if not found
 */
function get_user_by_identifier($identifier) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT id, username, email, profile_picture, role, bio, created_at, last_login FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$identifier, $identifier]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Error fetching user by identifier: " . $e->getMessage());
        return null;
    }
}

/**
 * Check if a username or email is available
 *
 * @param string $username The username to check
 * @param string $email The email to check
 * @param int $exclude_user_id User ID to exclude (for updates)
 * @return array An array with 'username' and 'email' keys indicating availability
 */
function check_credentials_availability($username, $email, $exclude_user_id = 0) {
    global $pdo;
    
    $result = [
        'username' => true,
        'email' => true
    ];
    
    try {
        // Check username
        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
        $stmt->execute([$username, $exclude_user_id]);
        if ($stmt->fetchColumn()) {
            $result['username'] = false;
        }
        
        // Check email
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
function update_user_profile($user_id, $data) {
    global $pdo;
    
    // Filter only allowed fields
    $allowed_fields = ['username', 'email', 'bio', 'profile_picture'];
    $update_data = array_intersect_key($data, array_flip($allowed_fields));
    
    if (empty($update_data)) {
        return false;
    }
    
    try {
        // Build the SQL query
        $sql = "UPDATE users SET ";
        $params = [];
        
        foreach ($update_data as $field => $value) {
            $sql .= "$field = ?, ";
            $params[] = $value;
        }
        
        $sql .= "updated_at = NOW() WHERE id = ?";
        $params[] = $user_id;
        
        $sql = str_replace(", updated_at", " updated_at", $sql);
        
        // Execute the query
        $stmt = $pdo->prepare($sql);
        return $stmt->execute($params);
    } catch (PDOException $e) {
        error_log("Error updating user profile: " . $e->getMessage());
        return false;
    }if (empty($update_data)) {
    return false; // لا يوجد شيء لتحديثه
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
function change_user_password($user_id, $current_password, $new_password) {
    global $pdo;
    
    try {
        // Get the user's current password hash
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            return [
                'success' => false,
                'message' => 'المستخدم غير موجود'
            ];
        }
        
        // Verify current password
        if (!password_verify($current_password, $user['password'])) {
            return [
                'success' => false,
                'message' => 'كلمة المرور الحالية غير صحيحة'
            ];
        }
        
        // Hash new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        // Update password
        $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$hashed_password, $user_id]);
        
        // Log activity
        $activity_stmt = $pdo->prepare("INSERT INTO activities (user_id, description) VALUES (?, ?)");
        $activity_stmt->execute([$user_id, 'Password changed']);
        
        return [
            'success' => true,
            'message' => 'تم تغيير كلمة المرور بنجاح'
        ];
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
function generate_token($length = 32) {
    return bin2hex(random_bytes($length / 2));
}