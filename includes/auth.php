<?php
// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if user is logged in
 *
 * @return boolean
 */
if (!function_exists('is_logged_in')) {
    function is_logged_in() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
}

/**
 * Check if user is admin
 *
 * @return boolean
 */
if (!function_exists('is_admin_logged_in')) {
    function is_admin_logged_in() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']) && 
               isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
    }
}

/**
 * Redirect if user is not logged in
 *
 * @param string $redirect_url URL to redirect to
 * @return void
 */
if (!function_exists('require_login')) {
    function require_login($redirect_url = '/login.php') {
        if (!is_logged_in()) {
            header("Location: $redirect_url");
            exit;
        }
    }
}

/**
 * Redirect if user is not an admin
 *
 * @param string $redirect_url URL to redirect to
 * @return void
 */
if (!function_exists('require_admin')) {
    function require_admin($redirect_url = '/admin/login.php') {
        if (!is_admin_logged_in()) {
            header("Location: $redirect_url");
            exit;
        }
    }
}

/**
 * Get current user ID
 *
 * @return int|null
 */
if (!function_exists('get_current_user_id')) {
    function get_current_user_id() {
        return $_SESSION['user_id'] ?? null;
    }
}

/**
 * Get current user role
 *
 * @return string|null
 */
if (!function_exists('get_current_user_role')) {
    function get_current_user_role() {
        return $_SESSION['user_role'] ?? null;
    }
}

/**
 * Get current user data
 *
 * @return array|null
 */
if (!function_exists('get_current_user_data')) {
    function get_current_user_data() {
        if (!is_logged_in()) {
            return null;
        }
        
        global $pdo;
        
        $stmt = $pdo->prepare("SELECT id, username, email, profile_picture, role, bio FROM users WHERE id = ?");
        $stmt->execute([get_current_user_id()]);
        
        return $stmt->fetch();
    }
}