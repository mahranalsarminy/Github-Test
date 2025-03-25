<?php
// تمكين تسجيل الأخطاء (للتطوير فقط)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Define the project root directory
define('ROOT_DIR', dirname(dirname(__DIR__)));

// Include necessary files
require_once ROOT_DIR . '/includes/init.php';
require_admin(); // Ensure only admins can access this page

// Current Date and Time
$current_time = "2025-03-25 03:41:08";
$current_user = "mahranalsarminy";

// Get site settings
$stmt = $pdo->query("SELECT site_name, site_logo, dark_mode, language FROM site_settings WHERE id = 1");
$site_settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Create tables if they don't exist
function setupSubscriptionTables($pdo) {
    // Subscription Plans table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS subscription_plans (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            monthly_price DECIMAL(10,2) NOT NULL,
            annual_price DECIMAL(10,2) NOT NULL,
            annual_discount INT DEFAULT 0,
            is_popular TINYINT(1) DEFAULT 0,
            display_order INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )
    ");
    
    // Subscription Features table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS subscription_features (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            display_order INT DEFAULT 0,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");
    
    // Plan Features (junction table)
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS subscription_plan_features (
            id INT AUTO_INCREMENT PRIMARY KEY,
            plan_id INT NOT NULL,
            feature_id INT NOT NULL,
            feature_limit VARCHAR(100) DEFAULT NULL,
            is_included TINYINT(1) DEFAULT 1,
            FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE CASCADE,
            FOREIGN KEY (feature_id) REFERENCES subscription_features(id) ON DELETE CASCADE
        )
    ");
    
    // User Subscriptions table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS user_subscriptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            plan_id INT NOT NULL,
            start_date DATETIME NOT NULL,
            end_date DATETIME NOT NULL,
            status ENUM('active', 'cancelled', 'expired', 'pending') NOT NULL,
            payment_method VARCHAR(100) DEFAULT NULL,
            payment_frequency ENUM('monthly', 'annual') DEFAULT 'monthly',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
            FOREIGN KEY (plan_id) REFERENCES subscription_plans(id) ON DELETE RESTRICT
        )
    ");
    
    // Subscription Settings
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS subscription_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) NOT NULL,
            setting_value TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY (setting_key)
        )
    ");
    
    // Testimonials table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS testimonials (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT,
            content TEXT NOT NULL,
            rating DECIMAL(3,1) DEFAULT 5.0,
            user_type VARCHAR(100) DEFAULT NULL,
            is_approved TINYINT(1) DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
        )
    ");
    
    // Insert default settings if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM subscription_settings");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $defaultSettings = [
            ['hero_title', 'Unlock Premium Content & Features'],
            ['hero_subtitle', 'Get unlimited access to high-quality media, advanced features, and AI-enhanced content with our premium subscription plans.'],
            ['offer_enabled', '1'],
            ['offer_title', 'Special Launch Offer - 30% Off'],
            ['offer_text', 'Limited time promotion ends soon!'],
            ['offer_end_date', date('Y-m-d H:i:s', strtotime('+10 days'))],
            ['offer_discount_percent', '30'],
            ['downloads_stat', '45000'],
            ['premium_content_stat', '12000'],
            ['happy_customers_stat', '2500'],
            ['ai_enhanced_content_stat', '8000'],
            ['progress_bar_percent', '75'],
            ['progress_bar_text', 'of subscriptions for this month already taken. Don\'t miss out!'],
            ['guarantee_text', '30-Day Money-Back Guarantee. No questions asked.']
        ];
        
        $settingStmt = $pdo->prepare("INSERT INTO subscription_settings (setting_key, setting_value) VALUES (?, ?)");
        foreach ($defaultSettings as $setting) {
            $settingStmt->execute($setting);
        }
    }
      // Insert default features if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM subscription_features");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $defaultFeatures = [
            ['High-quality downloads', 1],
            ['HD quality content', 2],
            ['Unlimited downloads', 3],
            ['Ad-free experience', 4],
            ['Premium content access', 5],
            ['Priority support', 6],
            ['Early access to new content', 7],
            ['AI-enhanced media', 8],
            ['Advanced search filters', 9],
            ['Custom API access', 10],
            ['Priority processing', 11],
            ['Bulk downloads', 12]
        ];
        
        $featureStmt = $pdo->prepare("INSERT INTO subscription_features (name, display_order) VALUES (?, ?)");
        foreach ($defaultFeatures as $feature) {
            $featureStmt->execute($feature);
        }
    }
    
    // Insert default plans if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM subscription_plans");
    $stmt->execute();
    if ($stmt->fetchColumn() == 0) {
        $defaultPlans = [
            [
                'name' => 'Basic',
                'description' => 'Essential features for casual users',
                'monthly_price' => 9.99,
                'annual_price' => 99.99,
                'annual_discount' => 16,
                'is_popular' => 0,
                'display_order' => 1
            ],
            [
                'name' => 'Premium',
                'description' => 'Perfect for regular users who need more',
                'monthly_price' => 19.99,
                'annual_price' => 199.99,
                'annual_discount' => 17,
                'is_popular' => 1,
                'display_order' => 2
            ],
            [
                'name' => 'Professional',
                'description' => 'Ultimate option for power users and professionals',
                'monthly_price' => 29.99,
                'annual_price' => 299.99,
                'annual_discount' => 17,
                'is_popular' => 0,
                'display_order' => 3
            ]
        ];
        
        $planStmt = $pdo->prepare("
            INSERT INTO subscription_plans 
            (name, description, monthly_price, annual_price, annual_discount, is_popular, display_order) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($defaultPlans as $plan) {
            $planStmt->execute([
                $plan['name'],
                $plan['description'],
                $plan['monthly_price'],
                $plan['annual_price'],
                $plan['annual_discount'],
                $plan['is_popular'],
                $plan['display_order']
            ]);
            
            $planId = $pdo->lastInsertId();
            
            // Assign features to plans
            if ($plan['name'] == 'Basic') {
                $featuresForPlan = [1, 2, 4]; // Basic features
            } else if ($plan['name'] == 'Premium') {
                $featuresForPlan = [1, 2, 3, 4, 5, 6, 7, 9]; // Premium features
            } else {
                $featuresForPlan = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12]; // All features
            }
            
            $planFeatureStmt = $pdo->prepare("
                INSERT INTO subscription_plan_features (plan_id, feature_id) 
                VALUES (?, ?)
            ");
            
            foreach ($featuresForPlan as $featureId) {
                $planFeatureStmt->execute([$planId, $featureId]);
            }
        }
    }
}

try {
    setupSubscriptionTables($pdo);
} catch (PDOException $e) {
    // Handle any database errors
    $error = "Database error: " . $e->getMessage();
}

// Handle actions and CRUD operations
$success_message = '';
$error_message = '';
$action = isset($_GET['action']) ? $_GET['action'] : (isset($_POST['action']) ? $_POST['action'] : 'list');
$section = isset($_GET['section']) ? $_GET['section'] : 'plans';
// Function for safe output - Using a unique name to avoid conflicts
function admin_safe_echo($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Handle Plans CRUD
if ($section == 'plans') {
    if ($action == 'add' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO subscription_plans 
                (name, description, monthly_price, annual_price, annual_discount, is_popular, display_order, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $_POST['name'],
                $_POST['description'],
                $_POST['monthly_price'],
                $_POST['annual_price'],
                $_POST['annual_discount'],
                isset($_POST['is_popular']) ? 1 : 0,
                $_POST['display_order'],
                isset($_POST['is_active']) ? 1 : 0
            ]);
            
            $planId = $pdo->lastInsertId();
            
            // Add features to the plan
            if (isset($_POST['features']) && is_array($_POST['features'])) {
                $featureStmt = $pdo->prepare("
                    INSERT INTO subscription_plan_features (plan_id, feature_id) 
                    VALUES (?, ?)
                ");
                
                foreach ($_POST['features'] as $featureId) {
                    $featureStmt->execute([$planId, $featureId]);
                }
            }
            
            $success_message = "Plan added successfully!";
        } catch (PDOException $e) {
            $error_message = "Error adding plan: " . $e->getMessage();
        }
        
    } else if ($action == 'edit' && isset($_GET['id'])) {
        // Load plan for editing
        $stmt = $pdo->prepare("SELECT * FROM subscription_plans WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $plan = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Get features for this plan
        $stmt = $pdo->prepare("
            SELECT feature_id 
            FROM subscription_plan_features 
            WHERE plan_id = ?
        ");
        $stmt->execute([$_GET['id']]);
        $plan_features = array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'feature_id');
        
    } else if ($action == 'update' && isset($_POST['id'])) {
        try {
            $stmt = $pdo->prepare("
                UPDATE subscription_plans 
                SET name = ?, description = ?, monthly_price = ?, annual_price = ?, 
                    annual_discount = ?, is_popular = ?, display_order = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['name'],
                $_POST['description'],
                $_POST['monthly_price'],
                $_POST['annual_price'],
                $_POST['annual_discount'],
                isset($_POST['is_popular']) ? 1 : 0,
                $_POST['display_order'],
                isset($_POST['is_active']) ? 1 : 0,
                $_POST['id']
            ]);
            
            // Update plan features (delete all and re-add)
            $stmt = $pdo->prepare("DELETE FROM subscription_plan_features WHERE plan_id = ?");
            $stmt->execute([$_POST['id']]);
            
            if (isset($_POST['features']) && is_array($_POST['features'])) {
                $featureStmt = $pdo->prepare("
                    INSERT INTO subscription_plan_features (plan_id, feature_id) 
                    VALUES (?, ?)
                ");
                
                foreach ($_POST['features'] as $featureId) {
                    $featureStmt->execute([$_POST['id'], $featureId]);
                }
            }
            
            $success_message = "Plan updated successfully!";
        } catch (PDOException $e) {
            $error_message = "Error updating plan: " . $e->getMessage();
        }
        
    } else if ($action == 'delete' && isset($_GET['id'])) {
        try {
            // Check if there are active subscriptions for this plan
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM user_subscriptions 
                WHERE plan_id = ? AND status = 'active'
            ");
            $stmt->execute([$_GET['id']]);
            
            if ($stmt->fetchColumn() > 0) {
                $error_message = "Cannot delete plan with active subscriptions. Deactivate the plan instead.";
            } else {
                // Delete plan features first (due to foreign key)
                $stmt = $pdo->prepare("DELETE FROM subscription_plan_features WHERE plan_id = ?");
                $stmt->execute([$_GET['id']]);
                
                // Then delete plan
                $stmt = $pdo->prepare("DELETE FROM subscription_plans WHERE id = ?");
                $stmt->execute([$_GET['id']]);
                
                $success_message = "Plan deleted successfully!";
            }
        } catch (PDOException $e) {
            $error_message = "Error deleting plan: " . $e->getMessage();
        }
    }
    
    // Get all plans for display
    $stmt = $pdo->query("
        SELECT * FROM subscription_plans 
        ORDER BY display_order ASC
    ");
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
// Handle Features CRUD
if ($section == 'features') {
    if ($action == 'add_feature' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO subscription_features 
                (name, display_order, is_active) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $_POST['name'],
                $_POST['display_order'],
                isset($_POST['is_active']) ? 1 : 0
            ]);
            
            $success_message = "Feature added successfully!";
        } catch (PDOException $e) {
            $error_message = "Error adding feature: " . $e->getMessage();
        }
    } else if ($action == 'edit_feature' && isset($_GET['id'])) {
        // Load feature for editing
        $stmt = $pdo->prepare("SELECT * FROM subscription_features WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $feature = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } else if ($action == 'update_feature' && isset($_POST['id'])) {
        try {
            $stmt = $pdo->prepare("
                UPDATE subscription_features 
                SET name = ?, display_order = ?, is_active = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $_POST['name'],
                $_POST['display_order'],
                isset($_POST['is_active']) ? 1 : 0,
                $_POST['id']
            ]);
            
            $success_message = "Feature updated successfully!";
        } catch (PDOException $e) {
            $error_message = "Error updating feature: " . $e->getMessage();
        }
        
    } else if ($action == 'delete_feature' && isset($_GET['id'])) {
        try {
            // Check if feature is used in any plans
            $stmt = $pdo->prepare("
                SELECT COUNT(*) FROM subscription_plan_features 
                WHERE feature_id = ?
            ");
            $stmt->execute([$_GET['id']]);
            
            if ($stmt->fetchColumn() > 0) {
                $error_message = "Cannot delete feature used in active plans. Remove it from plans first.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM subscription_features WHERE id = ?");
                $stmt->execute([$_GET['id']]);
                
                $success_message = "Feature deleted successfully!";
            }
        } catch (PDOException $e) {
            $error_message = "Error deleting feature: " . $e->getMessage();
        }
    }
    
    // Get all features for display
    $stmt = $pdo->query("
        SELECT * FROM subscription_features 
        ORDER BY display_order ASC
    ");
    $features = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle Settings
if ($section == 'settings') {
    if ($action == 'update_settings' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        try {
            $updateStmt = $pdo->prepare("
                UPDATE subscription_settings 
                SET setting_value = ? 
                WHERE setting_key = ?
            ");
            
            foreach ($_POST as $key => $value) {
                if (strpos($key, 'setting_') === 0) {
                    $settingKey = substr($key, 8); // Remove 'setting_' prefix
                    $updateStmt->execute([$value, $settingKey]);
                }
            }
            
            $success_message = "Settings updated successfully!";
        } catch (PDOException $e) {
            $error_message = "Error updating settings: " . $e->getMessage();
        }
    }
    
    // Get all settings
    $stmt = $pdo->query("SELECT * FROM subscription_settings");
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
}
// Handle Testimonials CRUD
if ($section == 'testimonials') {
    if ($action == 'add_testimonial' && $_SERVER['REQUEST_METHOD'] == 'POST') {
        try {
            $userId = !empty($_POST['user_id']) ? $_POST['user_id'] : null;
            
            $stmt = $pdo->prepare("
                INSERT INTO testimonials 
                (user_id, content, rating, user_type, is_approved) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $_POST['content'],
                $_POST['rating'],
                $_POST['user_type'],
                isset($_POST['is_approved']) ? 1 : 0
            ]);
            
            $success_message = "Testimonial added successfully!";
        } catch (PDOException $e) {
            $error_message = "Error adding testimonial: " . $e->getMessage();
        }
    } else if ($action == 'edit_testimonial' && isset($_GET['id'])) {
        // Load testimonial for editing
        $stmt = $pdo->prepare("SELECT * FROM testimonials WHERE id = ?");
        $stmt->execute([$_GET['id']]);
        $testimonial = $stmt->fetch(PDO::FETCH_ASSOC);
        
    } else if ($action == 'update_testimonial' && isset($_POST['id'])) {
        try {
            $userId = !empty($_POST['user_id']) ? $_POST['user_id'] : null;
            
            $stmt = $pdo->prepare("
                UPDATE testimonials 
                SET user_id = ?, content = ?, rating = ?, user_type = ?, is_approved = ?
                WHERE id = ?
            ");
            $stmt->execute([
                $userId,
                $_POST['content'],
                $_POST['rating'],
                $_POST['user_type'],
                isset($_POST['is_approved']) ? 1 : 0,
                $_POST['id']
            ]);
            
            $success_message = "Testimonial updated successfully!";
        } catch (PDOException $e) {
            $error_message = "Error updating testimonial: " . $e->getMessage();
        }
        
    } else if ($action == 'delete_testimonial' && isset($_GET['id'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM testimonials WHERE id = ?");
            $stmt->execute([$_GET['id']]);
            
            $success_message = "Testimonial deleted successfully!";
        } catch (PDOException $e) {
            $error_message = "Error deleting testimonial: " . $e->getMessage();
        }
    } else if ($action == 'toggle_approval' && isset($_GET['id'])) {
        try {
            $stmt = $pdo->prepare("
                UPDATE testimonials 
                SET is_approved = NOT is_approved
                WHERE id = ?
            ");
            $stmt->execute([$_GET['id']]);
            
            $success_message = "Testimonial approval status toggled successfully!";
        } catch (PDOException $e) {
            $error_message = "Error updating testimonial: " . $e->getMessage();
        }
    }
    
    // Get all testimonials for display
    $stmt = $pdo->query("
        SELECT t.*, u.username, u.email, u.profile_image
        FROM testimonials t
        LEFT JOIN users u ON t.user_id = u.id
        ORDER BY t.created_at DESC
    ");
    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Handle subscriptions management
if ($section == 'subscriptions') {
    // Get all subscriptions for display with pagination
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $per_page = 15;
    $offset = ($page - 1) * $per_page;
    
    $status_filter = isset($_GET['status']) ? $_GET['status'] : '';
    $where_clause = '';
    $params = [];
    
    if ($status_filter) {
        $where_clause = "WHERE us.status = ?";
        $params[] = $status_filter;
    }
    
    $count_stmt = $pdo->prepare("
        SELECT COUNT(*) 
        FROM user_subscriptions us
        $where_clause
    ");
    $count_stmt->execute($params);
    $total_subscriptions = $count_stmt->fetchColumn();
    $total_pages = ceil($total_subscriptions / $per_page);
    
    $stmt = $pdo->prepare("
        SELECT us.*, u.username, u.email, sp.name as plan_name
        FROM user_subscriptions us
        JOIN users u ON us.user_id = u.id
        JOIN subscription_plans sp ON us.plan_id = sp.id
        $where_clause
        ORDER BY us.created_at DESC
        LIMIT ? OFFSET ?
    ");
    
    $params[] = $per_page;
    $params[] = $offset;
    $stmt->execute($params);
    $subscriptions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Handle status change
    if ($action == 'change_status' && isset($_GET['id']) && isset($_GET['status'])) {
        $valid_statuses = ['active', 'cancelled', 'expired', 'pending'];
        
        if (in_array($_GET['status'], $valid_statuses)) {
            try {
                $stmt = $pdo->prepare("
                    UPDATE user_subscriptions 
                    SET status = ?
                    WHERE id = ?
                ");
                $stmt->execute([$_GET['status'], $_GET['id']]);
                
                $success_message = "Subscription status updated successfully!";
            } catch (PDOException $e) {
                $error_message = "Error updating subscription status: " . $e->getMessage();
            }
        } else {
            $error_message = "Invalid subscription status!";
        }
    }
}

// Get all features for plan form
$stmt = $pdo->query("
    SELECT * FROM subscription_features 
    WHERE is_active = 1
    ORDER BY display_order ASC
");
$all_features = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Management - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        :root {
            --primary: #4f46e5;
            --secondary: #818cf8;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #06b6d4;
            --light: #f3f4f6;
            --dark: #1f2937;
            --body-bg: #f9fafb;
            --card-bg: #ffffff;
            --text-primary: #111827;
            --text-secondary: #4b5563;
            --border-color: #e5e7eb;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 0;
            background-color: var(--body-bg);
            color: var(--text-primary);
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        h1, h2, h3, h4, h5, h6 {
            margin-top: 0;
            color: var(--text-primary);
        }

        .page-header {
            background-color: var(--card-bg);
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .page-title {
            font-size: 1.8rem;
            margin: 0;
        }

        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 8px 16px;
            border-radius: 4px;
            font-weight: 500;
            text-decoration: none;
            cursor: pointer;
            transition: background-color 0.2s, transform 0.1s;
            border: none;
            font-size: 0.9rem;
            gap: 6px;
        }

        .btn-primary {
            background-color: var(--primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: #4338ca;
            transform: translateY(-1px);
        }

        .btn-secondary {
            background-color: var(--light);
            color: var(--dark);
            border: 1px solid var(--border-color);
        }

        .btn-secondary:hover {
            background-color: #e5e7eb;
            transform: translateY(-1px);
        }

        .btn-success {
            background-color: var(--success);
            color: white;
        }

        .btn-success:hover {
            background-color: #059669;
            transform: translateY(-1px);
        }

        .btn-danger {
            background-color: var(--danger);
            color: white;
        }

        .btn-danger:hover {
            background-color: #dc2626;
            transform: translateY(-1px);
        }

        .btn-info {
            background-color: var(--info);
            color: white;
        }

        .btn-info:hover {
            background-color: #0891b2;
            transform: translateY(-1px);
        }

        .btn-sm {
            padding: 4px 10px;
            font-size: 0.8rem;
        }

        .btn-group {
            display: flex;
            gap: 8px;
        }

        .alert {
            padding: 12px;
            margin-bottom: 20px;
            border-radius: 4px;
        }

        .alert-success {
            background-color: #d1fae5;
            color: #065f46;
            border-left: 4px solid var(--success);
        }

        .alert-danger {
            background-color: #fee2e2;
            color: #b91c1c;
            border-left: 4px solid var(--danger);
        }
                .card {
            background-color: var(--card-bg);
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            padding: 20px;
            margin-bottom: 20px;
        }

        .card-header {
            font-size: 1.2rem;
            font-weight: 500;
            margin-bottom: 16px;
            border-bottom: 1px solid var(--border-color);
            padding-bottom: 10px;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
        }

        .data-table th, 
        .data-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .data-table th {
            background-color: var(--light);
            font-weight: 500;
            color: var(--text-secondary);
        }

        .data-table tbody tr:hover {
            background-color: #f9fafb;
        }

        .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .badge-primary {
            background-color: #e0e7ff;
            color: #4f46e5;
        }

        .badge-success {
            background-color: #d1fae5;
            color: #065f46;
        }

        .badge-danger {
            background-color: #fee2e2;
            color: #b91c1c;
        }

        .badge-warning {
            background-color: #fef3c7;
            color: #92400e;
        }

        .tabs {
            display: flex;
            gap: 2px;
            margin-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }

        .tab {
            padding: 10px 20px;
            cursor: pointer;
            transition: background-color 0.2s;
            border-bottom: 2px solid transparent;
            font-weight: 500;
            color: var(--text-secondary);
            text-decoration: none;
        }

        .tab:hover {
            background-color: #f9fafb;
            color: var(--primary);
        }

        .tab.active {
            border-bottom-color: var(--primary);
            color: var(--primary);
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
        }

        .form-control {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 1rem;
            transition: border-color 0.2s;
        }

        .form-control:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
        }

        textarea.form-control {
            min-height: 100px;
            resize: vertical;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 10px;
        }

        .form-check input[type="checkbox"] {
            width: 16px;
            height: 16px;
        }

        .pagination {
            display: flex;
            list-style: none;
            padding: 0;
            margin: 20px 0 0;
            justify-content: center;
        }

        .pagination li {
            margin: 0 5px;
        }

        .pagination a, .pagination span {
            display: inline-block;
            padding: 5px 10px;
            text-decoration: none;
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            border-radius: 4px;
        }

        .pagination a:hover {
            background-color: var(--light);
        }

        .pagination .active {
            background-color: var(--primary);
            color: white;
            border-color: var(--primary);
        }

        .pagination .disabled {
            color: var(--text-secondary);
            pointer-events: none;
        }

        .flex {
            display: flex;
        }

        .items-center {
            align-items: center;
        }

        .justify-between {
            justify-content: space-between;
        }

        .gap-2 {
            gap: 8px;
        }

        .flex-wrap {
            flex-wrap: wrap;
        }

        .flex-col {
            flex-direction: column;
        }

        .w-full {
            width: 100%;
        }

        .text-sm {
            font-size: 0.875rem;
        }

        .font-medium {
            font-weight: 500;
        }

        .text-gray {
            color: var(--text-secondary);
        }

        .mt-4 {
            margin-top: 16px;
        }

        .grid {
            display: grid;
        }

        .grid-cols-2 {
            grid-template-columns: repeat(2, 1fr);
        }

        .grid-cols-3 {
            grid-template-columns: repeat(3, 1fr);
        }

        .gap-4 {
            gap: 16px;
        }

        .settings-form .form-group {
            margin-bottom: 1.5rem;
        }

        .settings-form h3 {
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid var(--border-color);
            color: var(--text-secondary);
            font-size: 1rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .plan-features-list {
            list-style: none;
            padding: 0;
            margin: 0;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 10px;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-active {
            background-color: #d1fae5;
            color: #065f46;
        }

        .status-pending {
            background-color: #fef3c7;
            color: #92400e;
        }

        .status-cancelled {
            background-color: #fee2e2;
            color: #b91c1c;
        }

        .status-expired {
            background-color: #f3f4f6;
            color: #4b5563;
        }
                @media (max-width: 768px) {
            .data-table {
                font-size: 0.85rem;
            }

            .grid-cols-3 {
                grid-template-columns: 1fr;
            }

            .grid-cols-2 {
                grid-template-columns: 1fr;
            }
        }
        
        /* Dropdown styles */
        .dropdown {
            position: relative;
            display: inline-block;
        }
        
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            background-color: var(--card-bg);
            min-width: 160px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            z-index: 1;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .dropdown-item {
            padding: 10px 15px;
            text-decoration: none;
            display: block;
            color: var(--text-primary);
            font-size: 0.9rem;
            transition: background-color 0.2s;
        }
        
        .dropdown-item:hover {
            background-color: var(--light);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="page-header">
            <h1 class="page-title">Subscription Management</h1>
            <div>
                <span class="text-sm text-gray"><?php echo $current_time; ?> - <?php echo $current_user; ?></span>
                <a href="/admin/index.php" class="btn btn-secondary" style="margin-left: 10px;">
                    <i class="fas fa-arrow-left"></i> Back to Dashboard
                </a>
            </div>
        </div>
        
        <?php if (!empty($success_message)): ?>
        <div class="alert alert-success">
            <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
        </div>
        <?php endif; ?>
        
        <?php if (!empty($error_message)): ?>
        <div class="alert alert-danger">
            <i class="fas fa-exclamation-circle"></i> <?php echo $error_message; ?>
        </div>
        <?php endif; ?>
        
        <div class="tabs">
            <a href="?section=plans" class="tab <?php echo $section == 'plans' ? 'active' : ''; ?>">
                <i class="fas fa-list"></i> Subscription Plans
            </a>
            <a href="?section=features" class="tab <?php echo $section == 'features' ? 'active' : ''; ?>">
                <i class="fas fa-check-square"></i> Plan Features
            </a>
            <a href="?section=subscriptions" class="tab <?php echo $section == 'subscriptions' ? 'active' : ''; ?>">
                <i class="fas fa-users"></i> User Subscriptions
            </a>
            <a href="?section=testimonials" class="tab <?php echo $section == 'testimonials' ? 'active' : ''; ?>">
                <i class="fas fa-comment"></i> Testimonials
            </a>
            <a href="?section=settings" class="tab <?php echo $section == 'settings' ? 'active' : ''; ?>">
                <i class="fas fa-cog"></i> Settings
            </a>
        </div>
               <?php if ($section == 'plans'): ?>
            <?php if ($action == 'add'): ?>
                <div class="card">
                    <h2 class="card-header">Add New Subscription Plan</h2>
                    <form action="?section=plans" method="post">
                        <input type="hidden" name="action" value="add">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="name" class="form-label">Plan Name</label>
                                <input type="text" name="name" id="name" class="form-control" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="display_order" class="form-label">Display Order</label>
                                <input type="number" name="display_order" id="display_order" class="form-control" value="0" min="0">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description" class="form-label">Description</label>
                            <textarea name="description" id="description" class="form-control"></textarea>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="monthly_price" class="form-label">Monthly Price</label>
                                <input type="number" name="monthly_price" id="monthly_price" class="form-control" step="0.01" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="annual_price" class="form-label">Annual Price</label>
                                <input type="number" name="annual_price" id="annual_price" class="form-control" step="0.01" required>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="annual_discount" class="form-label">Annual Discount (%)</label>
                                <input type="number" name="annual_discount" id="annual_discount" class="form-control" min="0" max="100" value="0">
                            </div>
                            
                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" name="is_popular" id="is_popular">
                                    <label for="is_popular">Mark as Most Popular</label>
                                </div>
                                
                                <div class="form-check">
                                    <input type="checkbox" name="is_active" id="is_active" checked>
                                    <label for="is_active">Active</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Plan Features</label>
                            <div class="plan-features-list">
                                <?php foreach ($all_features as $feature): ?>
                                <div class="form-check">
                                    <input type="checkbox" name="features[]" id="feature_<?php echo $feature['id']; ?>" value="<?php echo $feature['id']; ?>">
                                    <label for="feature_<?php echo $feature['id']; ?>"><?php echo admin_safe_echo($feature['name']); ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="btn-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Plan
                            </button>
                            <a href="?section=plans" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
                            <?php elseif ($action == 'edit' && isset($plan)): ?>
                <div class="card">
                    <h2 class="card-header">Edit Subscription Plan</h2>
                    <form action="?section=plans" method="post">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="id" value="<?php echo $plan['id']; ?>">
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="name" class="form-label">Plan Name</label>
                                <input type="text" name="name" id="name" class="form-control" value="<?php echo admin_safe_echo($plan['name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="display_order" class="form-label">Display Order</label>
                                <input type="number" name="display_order" id="display_order" class="form-control" value="<?php echo admin_safe_echo($plan['display_order']); ?>" min="0">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="description" class="form-label">Description</label>
                            <textarea name="description" id="description" class="form-control"><?php echo admin_safe_echo($plan['description']); ?></textarea>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="monthly_price" class="form-label">Monthly Price</label>
                                <input type="number" name="monthly_price" id="monthly_price" class="form-control" step="0.01" value="<?php echo admin_safe_echo($plan['monthly_price']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="annual_price" class="form-label">Annual Price</label>
                                <input type="number" name="annual_price" id="annual_price" class="form-control" step="0.01" value="<?php echo admin_safe_echo($plan['annual_price']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="annual_discount" class="form-label">Annual Discount (%)</label>
                                <input type="number" name="annual_discount" id="annual_discount" class="form-control" min="0" max="100" value="<?php echo admin_safe_echo($plan['annual_discount']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <div class="form-check">
                                    <input type="checkbox" name="is_popular" id="is_popular" <?php echo $plan['is_popular'] ? 'checked' : ''; ?>>
                                    <label for="is_popular">Mark as Most Popular</label>
                                </div>
                                
                                <div class="form-check">
                                    <input type="checkbox" name="is_active" id="is_active" <?php echo $plan['is_active'] ? 'checked' : ''; ?>>
                                    <label for="is_active">Active</label>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label class="form-label">Plan Features</label>
                            <div class="plan-features-list">
                                <?php foreach ($all_features as $feature): ?>
                                <div class="form-check">
                                    <input type="checkbox" name="features[]" id="feature_<?php echo $feature['id']; ?>" value="<?php echo $feature['id']; ?>" 
                                        <?php echo in_array($feature['id'], $plan_features) ? 'checked' : ''; ?>>
                                    <label for="feature_<?php echo $feature['id']; ?>"><?php echo admin_safe_echo($feature['name']); ?></label>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        
                        <div class="btn-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Plan
                            </button>
                            <a href="?section=plans" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
                            <?php else: ?>
                <div class="flex justify-between items-center mb-4">
                    <h2>Subscription Plans</h2>
                    <a href="?section=plans&action=add" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Plan
                    </a>
                </div>
                
                <div class="card">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Name</th>
                                <th>Monthly Price</th>
                                <th>Annual Price</th>
                                <th>Status</th>
                                <th>Popular</th>
                                <th>Features</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($plans)): ?>
                                <?php foreach ($plans as $plan): ?>
                                    <tr>
                                        <td><?php echo admin_safe_echo($plan['display_order']); ?></td>
                                        <td>
                                            <strong><?php echo admin_safe_echo($plan['name']); ?></strong>
                                            <?php if (!empty($plan['description'])): ?>
                                                <br>
                                                <span class="text-sm text-gray"><?php echo admin_safe_echo($plan['description']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td>$<?php echo admin_safe_echo(number_format($plan['monthly_price'], 2)); ?></td>
                                        <td>
                                            $<?php echo admin_safe_echo(number_format($plan['annual_price'], 2)); ?>
                                            <?php if ($plan['annual_discount'] > 0): ?>
                                                <br><span class="badge badge-success"><?php echo $plan['annual_discount']; ?>% off</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($plan['is_active']): ?>
                                                <span class="badge badge-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($plan['is_popular']): ?>
                                                <span class="badge badge-primary"><i class="fas fa-star"></i> Popular</span>
                                            <?php else: ?>
                                                <span class="text-sm text-gray">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            // Get feature count
                                            $stmt = $pdo->prepare("
                                                SELECT COUNT(*) FROM subscription_plan_features 
                                                WHERE plan_id = ?
                                            ");
                                            $stmt->execute([$plan['id']]);
                                            $feature_count = $stmt->fetchColumn();
                                            
                                            echo $feature_count . ' features';
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="?section=plans&action=edit&id=<?php echo $plan['id']; ?>" class="btn btn-info btn-sm">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <a href="?section=plans&action=delete&id=<?php echo $plan['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this plan?');">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" style="text-align: center;">No subscription plans found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
                    <?php elseif ($section == 'features'): ?>
            <?php if ($action == 'add_feature'): ?>
                <div class="card">
                    <h2 class="card-header">Add New Feature</h2>
                    <form action="?section=features" method="post">
                        <input type="hidden" name="action" value="add_feature">
                        
                        <div class="form-group">
                            <label for="name" class="form-label">Feature Name</label>
                            <input type="text" name="name" id="name" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="display_order" class="form-label">Display Order</label>
                            <input type="number" name="display_order" id="display_order" class="form-control" value="0" min="0">
                        </div>
                        
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" id="is_active" checked>
                                <label for="is_active">Active</label>
                            </div>
                        </div>
                        
                        <div class="btn-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Save Feature
                            </button>
                            <a href="?section=features" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php elseif ($action == 'edit_feature' && isset($feature)): ?>
                <div class="card">
                    <h2 class="card-header">Edit Feature</h2>
                    <form action="?section=features" method="post">
                        <input type="hidden" name="action" value="update_feature">
                        <input type="hidden" name="id" value="<?php echo $feature['id']; ?>">
                        
                        <div class="form-group">
                            <label for="name" class="form-label">Feature Name</label>
                            <input type="text" name="name" id="name" class="form-control" value="<?php echo admin_safe_echo($feature['name']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="display_order" class="form-label">Display Order</label>
                            <input type="number" name="display_order" id="display_order" class="form-control" value="<?php echo admin_safe_echo($feature['display_order']); ?>" min="0">
                        </div>
                        
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" id="is_active" <?php echo $feature['is_active'] ? 'checked' : ''; ?>>
                                <label for="is_active">Active</label>
                            </div>
                        </div>
                        
                        <div class="btn-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Feature
                            </button>
                            <a href="?section=features" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php else: ?>
                <div class="flex justify-between items-center mb-4">
                    <h2>Plan Features</h2>
                    <a href="?section=features&action=add_feature" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Feature
                    </a>
                </div>
                
                <div class="card">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Order</th>
                                <th>Feature Name</th>
                                <th>Status</th>
                                <th>Used In Plans</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($features)): ?>
                                <?php foreach ($features as $feature): ?>
                                    <tr>
                                        <td><?php echo admin_safe_echo($feature['display_order']); ?></td>
                                        <td><?php echo admin_safe_echo($feature['name']); ?></td>
                                        <td>
                                            <?php if ($feature['is_active']): ?>
                                                <span class="badge badge-success">Active</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Inactive</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            // Get plan count for this feature
                                            $stmt = $pdo->prepare("
                                                SELECT COUNT(*) FROM subscription_plan_features 
                                                WHERE feature_id = ?
                                            ");
                                            $stmt->execute([$feature['id']]);
                                            $plan_count = $stmt->fetchColumn();
                                            
                                            echo $plan_count . ' plans';
                                            ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="?section=features&action=edit_feature&id=<?php echo $feature['id']; ?>" class="btn btn-info btn-sm">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <a href="?section=features&action=delete_feature&id=<?php echo $feature['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this feature?');">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center;">No features found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
                    <?php elseif ($section == 'settings'): ?>
            <div class="card">
                <h2 class="card-header">Subscription Page Settings</h2>
                <form action="?section=settings" method="post" class="settings-form">
                    <input type="hidden" name="action" value="update_settings">
                    
                    <h3>Hero Section</h3>
                    <div class="form-group">
                        <label for="hero_title" class="form-label">Hero Title</label>
                        <input type="text" name="setting_hero_title" id="hero_title" class="form-control" value="<?php echo admin_safe_echo($settings['hero_title'] ?? ''); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="hero_subtitle" class="form-label">Hero Subtitle</label>
                        <textarea name="setting_hero_subtitle" id="hero_subtitle" class="form-control"><?php echo admin_safe_echo($settings['hero_subtitle'] ?? ''); ?></textarea>
                    </div>
                    
                    <h3>Special Offer</h3>
                    <div class="form-group">
                        <div class="form-check">
                            <input type="checkbox" name="setting_offer_enabled" id="offer_enabled" value="1" <?php echo isset($settings['offer_enabled']) && $settings['offer_enabled'] == '1' ? 'checked' : ''; ?>>
                            <label for="offer_enabled">Enable Special Offer</label>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label for="offer_title" class="form-label">Offer Title</label>
                            <input type="text" name="setting_offer_title" id="offer_title" class="form-control" value="<?php echo admin_safe_echo($settings['offer_title'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                                                        <label for="offer_text" class="form-label">Offer Text</label>
                            <input type="text" name="setting_offer_text" id="offer_text" class="form-control" value="<?php echo admin_safe_echo($settings['offer_text'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label for="offer_end_date" class="form-label">Offer End Date</label>
                            <input type="datetime-local" name="setting_offer_end_date" id="offer_end_date" class="form-control" value="<?php echo isset($settings['offer_end_date']) ? date('Y-m-d\TH:i', strtotime($settings['offer_end_date'])) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="offer_discount_percent" class="form-label">Discount Percentage</label>
                            <input type="number" name="setting_offer_discount_percent" id="offer_discount_percent" class="form-control" min="0" max="100" value="<?php echo admin_safe_echo($settings['offer_discount_percent'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <h3>Stats Section</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label for="downloads_stat" class="form-label">Monthly Downloads Stat</label>
                            <input type="text" name="setting_downloads_stat" id="downloads_stat" class="form-control" value="<?php echo admin_safe_echo($settings['downloads_stat'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="premium_content_stat" class="form-label">Premium Content Stat</label>
                            <input type="text" name="setting_premium_content_stat" id="premium_content_stat" class="form-control" value="<?php echo admin_safe_echo($settings['premium_content_stat'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label for="happy_customers_stat" class="form-label">Happy Customers Stat</label>
                            <input type="text" name="setting_happy_customers_stat" id="happy_customers_stat" class="form-control" value="<?php echo admin_safe_echo($settings['happy_customers_stat'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="ai_enhanced_content_stat" class="form-label">AI Enhanced Content Stat</label>
                            <input type="text" name="setting_ai_enhanced_content_stat" id="ai_enhanced_content_stat" class="form-control" value="<?php echo admin_safe_echo($settings['ai_enhanced_content_stat'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <h3>Progress Bar</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label for="progress_bar_percent" class="form-label">Progress Bar Percentage</label>
                            <input type="number" name="setting_progress_bar_percent" id="progress_bar_percent" class="form-control" min="0" max="100" value="<?php echo admin_safe_echo($settings['progress_bar_percent'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="progress_bar_text" class="form-label">Progress Bar Text</label>
                            <input type="text" name="setting_progress_bar_text" id="progress_bar_text" class="form-control" value="<?php echo admin_safe_echo($settings['progress_bar_text'] ?? ''); ?>">
                        </div>
                    </div>
                    
                    <h3>Other Settings</h3>
                    <div class="form-group">
                        <label for="guarantee_text" class="form-label">Guarantee Text</label>
                        <input type="text" name="setting_guarantee_text" id="guarantee_text" class="form-control" value="<?php echo admin_safe_echo($settings['guarantee_text'] ?? ''); ?>">
                    </div>
                    
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save Settings
                    </button>
                </form>
            </div>
                    <?php elseif ($section == 'testimonials'): ?>
            <?php if ($action == 'add_testimonial'): ?>
                <div class="card">
                    <h2 class="card-header">Add New Testimonial</h2>
                    <form action="?section=testimonials" method="post">
                        <input type="hidden" name="action" value="add_testimonial">
                        
                        <div class="form-group">
                            <label for="user_id" class="form-label">User ID (Optional)</label>
                            <input type="number" name="user_id" id="user_id" class="form-control">
                            <small class="text-gray">Leave blank for testimonials without a user account</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="content" class="form-label">Testimonial Content</label>
                            <textarea name="content" id="content" class="form-control" required></textarea>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="rating" class="form-label">Rating (1-5)</label>
                                <input type="number" name="rating" id="rating" class="form-control" min="1" max="5" step="0.1" value="5">
                            </div>
                            
                            <div class="form-group">
                                <label for="user_type" class="form-label">User Role/Position</label>
                                <input type="text" name="user_type" id="user_type" class="form-control" placeholder="e.g., Graphic Designer">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" name="is_approved" id="is_approved" checked>
                                <label for="is_approved">Approved</label>
                            </div>
                        </div>
                        
                        <div class="btn-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Add Testimonial
                            </button>
                            <a href="?section=testimonials" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php elseif ($action == 'edit_testimonial' && isset($testimonial)): ?>
                <div class="card">
                    <h2 class="card-header">Edit Testimonial</h2>
                    <form action="?section=testimonials" method="post">
                        <input type="hidden" name="action" value="update_testimonial">
                        <input type="hidden" name="id" value="<?php echo $testimonial['id']; ?>">
                        
                        <div class="form-group">
                            <label for="user_id" class="form-label">User ID (Optional)</label>
                            <input type="number" name="user_id" id="user_id" class="form-control" value="<?php echo admin_safe_echo($testimonial['user_id']); ?>">
                            <small class="text-gray">Leave blank for testimonials without a user account</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="content" class="form-label">Testimonial Content</label>
                            <textarea name="content" id="content" class="form-control" required><?php echo admin_safe_echo($testimonial['content']); ?></textarea>
                        </div>
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="form-group">
                                <label for="rating" class="form-label">Rating (1-5)</label>
                                <input type="number" name="rating" id="rating" class="form-control" min="1" max="5" step="0.1" value="<?php echo admin_safe_echo($testimonial['rating']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="user_type" class="form-label">User Role/Position</label>
                                <input type="text" name="user_type" id="user_type" class="form-control" value="<?php echo admin_safe_echo($testimonial['user_type']); ?>" placeholder="e.g., Graphic Designer">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <div class="form-check">
                                <input type="checkbox" name="is_approved" id="is_approved" <?php echo $testimonial['is_approved'] ? 'checked' : ''; ?>>
                                <label for="is_approved">Approved</label>
                            </div>
                        </div>
                        
                        <div class="btn-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Update Testimonial
                            </button>
                            <a href="?section=testimonials" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
                            <?php else: ?>
                <div class="flex justify-between items-center mb-4">
                    <h2>Testimonials</h2>
                    <a href="?section=testimonials&action=add_testimonial" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Add New Testimonial
                    </a>
                </div>
                
                <div class="card">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>User</th>
                                <th>Content</th>
                                <th>Rating</th>
                                <th>Status</th>
                                <th>Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($testimonials)): ?>
                                <?php foreach ($testimonials as $testimonial): ?>
                                    <tr>
                                        <td><?php echo admin_safe_echo($testimonial['id']); ?></td>
                                        <td>
                                            <?php if ($testimonial['user_id']): ?>
                                                <strong><?php echo admin_safe_echo($testimonial['username'] ?? 'User #' . $testimonial['user_id']); ?></strong>
                                            <?php else: ?>
                                                <span class="text-sm text-gray">No User</span>
                                            <?php endif; ?>
                                            <?php if (!empty($testimonial['user_type'])): ?>
                                                <br>
                                                <span class="text-sm text-gray"><?php echo admin_safe_echo($testimonial['user_type']); ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo admin_safe_echo(substr($testimonial['content'], 0, 100)) . (strlen($testimonial['content']) > 100 ? '...' : ''); ?></td>
                                        <td>
                                            <?php
                                            $rating = $testimonial['rating'];
                                            $full_stars = floor($rating);
                                            $half_star = round(($rating - $full_stars) * 2) / 2 > 0;
                                            $empty_stars = 5 - $full_stars - ($half_star ? 1 : 0);
                                            
                                            for ($i = 0; $i < $full_stars; $i++) {
                                                echo '<i class="fas fa-star" style="color: #f59e0b;"></i>';
                                            }
                                            
                                            if ($half_star) {
                                                echo '<i class="fas fa-star-half-alt" style="color: #f59e0b;"></i>';
                                            }
                                            
                                            for ($i = 0; $i < $empty_stars; $i++) {
                                                echo '<i class="far fa-star" style="color: #f59e0b;"></i>';
                                            }
                                            ?>
                                            <span class="text-sm">(<?php echo $rating; ?>)</span>
                                        </td>
                                        <td>
                                            <?php if ($testimonial['is_approved']): ?>
                                                <span class="badge badge-success">Approved</span>
                                            <?php else: ?>
                                                <span class="badge badge-warning">Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?php echo date('Y-m-d', strtotime($testimonial['created_at'])); ?></td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="?section=testimonials&action=toggle_approval&id=<?php echo $testimonial['id']; ?>" class="btn btn-sm <?php echo $testimonial['is_approved'] ? 'btn-warning' : 'btn-success'; ?>">
                                                    <?php echo $testimonial['is_approved'] ? '<i class="fas fa-times"></i> Unapprove' : '<i class="fas fa-check"></i> Approve'; ?>
                                                </a>
                                                <a href="?section=testimonials&action=edit_testimonial&id=<?php echo $testimonial['id']; ?>" class="btn btn-info btn-sm">
                                                    <i class="fas fa-edit"></i> Edit
                                                </a>
                                                <a href="?section=testimonials&action=delete_testimonial&id=<?php echo $testimonial['id']; ?>" class="btn btn-danger btn-sm" onclick="return confirm('Are you sure you want to delete this testimonial?');">
                                                    <i class="fas fa-trash"></i> Delete
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" style="text-align: center;">No testimonials found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
                    <?php elseif ($section == 'subscriptions'): ?>
            <div class="card">
                <h2 class="card-header">User Subscriptions</h2>
                
                <div class="flex justify-between items-center mb-4">
                    <div class="flex gap-2">
                        <a href="?section=subscriptions" class="btn btn-sm <?php echo empty($status_filter) ? 'btn-primary' : 'btn-secondary'; ?>">All</a>
                        <a href="?section=subscriptions&status=active" class="btn btn-sm <?php echo $status_filter === 'active' ? 'btn-primary' : 'btn-secondary'; ?>">Active</a>
                        <a href="?section=subscriptions&status=cancelled" class="btn btn-sm <?php echo $status_filter === 'cancelled' ? 'btn-primary' : 'btn-secondary'; ?>">Cancelled</a>
                        <a href="?section=subscriptions&status=expired" class="btn btn-sm <?php echo $status_filter === 'expired' ? 'btn-primary' : 'btn-secondary'; ?>">Expired</a>
                        <a href="?section=subscriptions&status=pending" class="btn btn-sm <?php echo $status_filter === 'pending' ? 'btn-primary' : 'btn-secondary'; ?>">Pending</a>
                    </div>
                </div>
                
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User</th>
                            <th>Plan</th>
                            <th>Start Date</th>
                            <th>End Date</th>
                            <th>Status</th>
                            <th>Payment</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($subscriptions)): ?>
                            <?php foreach ($subscriptions as $subscription): ?>
                                <tr>
                                    <td><?php echo admin_safe_echo($subscription['id']); ?></td>
                                    <td>
                                        <strong><?php echo admin_safe_echo($subscription['username']); ?></strong>
                                        <br>
                                        <span class="text-sm text-gray"><?php echo admin_safe_echo($subscription['email']); ?></span>
                                    </td>
                                    <td><?php echo admin_safe_echo($subscription['plan_name']); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($subscription['start_date'])); ?></td>
                                    <td><?php echo date('Y-m-d', strtotime($subscription['end_date'])); ?></td>
                                    <td>
                                        <?php 
                                        $status_class = '';
                                        switch ($subscription['status']) {
                                            case 'active':
                                                $status_class = 'status-active';
                                                break;
                                            case 'cancelled':
                                                $status_class = 'status-cancelled';
                                                break;
                                            case 'expired':
                                                $status_class = 'status-expired';
                                                break;
                                            case 'pending':
                                                $status_class = 'status-pending';
                                                break;
                                        }
                                        ?>
                                        <span class="status-badge <?php echo $status_class; ?>"><?php echo ucfirst($subscription['status']); ?></span>
                                    </td>
                                    <td>
                                        <?php echo admin_safe_echo(ucfirst($subscription['payment_method'] ?? 'N/A')); ?>
                                        <br>
                                        <span class="text-sm text-gray"><?php echo ucfirst($subscription['payment_frequency']); ?></span>
                                    </td>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-secondary btn-sm">Status <i class="fas fa-caret-down"></i></button>
                                            <div class="dropdown-content">
                                                <a href="?section=subscriptions&action=change_status&id=<?php echo $subscription['id']; ?>&status=active" class="dropdown-item">Mark Active</a>
                                                <a href="?section=subscriptions&action=change_status&id=<?php echo $subscription['id']; ?>&status=cancelled" class="dropdown-item">Mark Cancelled</a>
                                                <a href="?section=subscriptions&action=change_status&id=<?php echo $subscription['id']; ?>&status=expired" class="dropdown-item">Mark Expired</a>
                                                <a href="?section=subscriptions&action=change_status&id=<?php echo $subscription['id']; ?>&status=pending" class="dropdown-item">Mark Pending</a>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center;">No subscriptions found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">
                        <?php if ($page > 1): ?>
                            <li><a href="?section=subscriptions<?php echo $status_filter ? '&status=' . $status_filter : ''; ?>&page=1">&laquo; First</a></li>
                            <li><a href="?section=subscriptions<?php echo $status_filter ? '&status=' . $status_filter : ''; ?>&page=<?php echo $page - 1; ?>">Prev</a></li>
                        <?php endif; ?>
                        
                        <?php
                        $start = max(1, $page - 2);
                        $end = min($start + 4, $total_pages);
                        $start = max(1, $end - 4);
                        
                        for ($i = $start; $i <= $end; $i++):
                        ?>
                            <li>
                                <?php if ($i == $page): ?>
                                    <span class="active"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?section=subscriptions<?php echo $status_filter ? '&status=' . $status_filter : ''; ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $total_pages): ?>
                            <li><a href="?section=subscriptions<?php echo $status_filter ? '&status=' . $status_filter : ''; ?>&page=<?php echo $page + 1; ?>">Next</a></li>
                            <li><a href="?section=subscriptions<?php echo $status_filter ? '&status=' . $status_filter : ''; ?>&page=<?php echo $total_pages; ?>">Last &raquo;</a></li>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Update date and time in the header
        document.addEventListener('DOMContentLoaded', function() {
            // Current values from PHP
            const currentDateTime = "<?php echo $current_time; ?>";
            const currentUser = "<?php echo $current_user; ?>";
            
            // Simple dropdown functionality
            const dropdowns = document.querySelectorAll('.dropdown');
            
            dropdowns.forEach(dropdown => {
                const button = dropdown.querySelector('button');
                const content = dropdown.querySelector('.dropdown-content');
                
                button.addEventListener('click', function(event) {
                    event.stopPropagation();
                    content.style.display = content.style.display === 'block' ? 'none' : 'block';
                });
                
                // Close when clicking outside
                document.addEventListener('click', function() {
                    content.style.display = 'none';
                });
                
                // Prevent dropdown content clicks from closing the dropdown
                content.addEventListener('click', function(event) {
                    event.stopPropagation();
                });
            });
        });
    </script>
</body>
</html>