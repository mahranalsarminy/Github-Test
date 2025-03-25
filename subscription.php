<?php
require_once(__DIR__ . '/includes/init.php');
session_start();

// Fetch site settings
$stmt = $pdo->query("SELECT site_name, site_logo, dark_mode, language FROM site_settings WHERE id = 1");
$site_settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user data if logged in
$user = null;
if (isset($_SESSION['user_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get subscription plans from database
$stmt = $pdo->query("
    SELECT * FROM subscription_plans 
    WHERE is_active = 1 
    ORDER BY display_order ASC
");

$subscription_plans = [];
if ($stmt) {
    $subscription_plans = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    // Fallback if the table doesn't exist yet
    $subscription_plans = [
        [
            'id' => 1,
            'name' => 'Basic',
            'description' => 'Essential features for casual users',
            'monthly_price' => 9.99,
            'annual_price' => 99.99,
            'annual_discount' => 16,
            'is_popular' => 0
        ],
        [
            'id' => 2,
            'name' => 'Premium',
            'description' => 'Perfect for regular users who need more',
            'monthly_price' => 19.99,
            'annual_price' => 199.99,
            'annual_discount' => 17,
            'is_popular' => 1
        ],
        [
            'id' => 3,
            'name' => 'Professional',
            'description' => 'Ultimate option for power users and professionals',
            'monthly_price' => 29.99,
            'annual_price' => 299.99,
            'annual_discount' => 17,
            'is_popular' => 0
        ]
    ];
}

// Get features for each plan
foreach ($subscription_plans as &$plan) {
    try {
        $stmt = $pdo->prepare("
            SELECT f.* 
            FROM subscription_features f
            JOIN subscription_plan_features pf ON f.id = pf.feature_id
            WHERE pf.plan_id = ? AND f.is_active = 1
            ORDER BY f.display_order ASC
        ");
        $stmt->execute([$plan['id']]);
        $plan['features'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Fallback features if the tables don't exist yet
        if ($plan['id'] == 1) {
            $plan['features'] = [
                ['name' => 'Download up to 50 files per day'],
                ['name' => 'Access to standard quality content'],
                ['name' => 'Ad-free browsing experience'],
                ['name' => 'Basic customer support'],
                ['name' => 'Access to standard categories']
            ];
        } else if ($plan['id'] == 2) {
            $plan['features'] = [
                ['name' => 'Unlimited downloads'],
                ['name' => 'Access to all premium content'],
                ['name' => 'HD quality downloads'],
                ['name' => 'Priority email support'],
                ['name' => 'Advanced search filters'],
                ['name' => 'Early access to new content']
            ];
        } else {
            $plan['features'] = [
                ['name' => 'Everything in Premium plus:'],
                ['name' => 'AI-enhanced content access'],
                ['name' => '4K quality downloads'],
                ['name' => 'Custom API access'],
                ['name' => '24/7 priority support'],
                ['name' => 'White label rights'],
                ['name' => 'Bulk downloads']
            ];
        }
    }
}
unset($plan); // Break reference

// Get testimonials
$testimonials = [];
try {
    $stmt = $pdo->query("
        SELECT t.*, u.username, u.profile_image
        FROM testimonials t
        LEFT JOIN users u ON t.user_id = u.id
        WHERE t.is_approved = 1
        ORDER BY RAND()
        LIMIT 4
    ");
    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Silently fail if the testimonials table doesn't exist yet
}

// Get subscription settings
$settings = [];
try {
    $stmt = $pdo->query("SELECT * FROM subscription_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (PDOException $e) {
    // Silently fail if the settings table doesn't exist yet
    $settings = [
        'hero_title' => 'Unlock Premium Content & Features',
        'hero_subtitle' => 'Get unlimited access to high-quality media, advanced features, and AI-enhanced content with our premium subscription plans.',
        'offer_enabled' => '1',
        'offer_title' => 'Special Launch Offer - 30% Off',
        'offer_text' => 'Limited time promotion ends soon!',
        'offer_end_date' => date('Y-m-d H:i:s', strtotime('+10 days')),
        'offer_discount_percent' => '30',
        'downloads_stat' => '45000',
        'premium_content_stat' => '12000',
        'happy_customers_stat' => '2500',
        'ai_enhanced_content_stat' => '8000',
        'progress_bar_percent' => '75',
        'progress_bar_text' => 'of subscriptions for this month already taken. Don\'t miss out!',
        'guarantee_text' => '30-Day Money-Back Guarantee. No questions asked.'
    ];
}

// Format number with K, M, etc. suffix
function formatNumber($num) {
    if(preg_match('/^(\d+)K\+$/', $num, $matches)) {
        $num = $matches[1] * 1000;
    } elseif(preg_match('/^(\d+\.?\d*)M\+$/', $num, $matches)) {
        $num = $matches[1] * 1000000;
    } elseif(preg_match('/^(\d+)\+$/', $num, $matches)) {
        $num = $matches[1];
    }
    
    if($num >= 1000000) {
        return round($num / 1000000, 1) . 'M+';
    } elseif($num >= 1000) {
        return round($num / 1000, 1) . 'K+';
    }
    return $num . '+';
}

// Safe echo function
function safe_echo($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Calculate plan benefits
$additional_stats = [
    'downloads_per_month' => formatNumber($settings['downloads_stat'] ?? rand(10000, 50000)),
    'premium_content_count' => formatNumber($settings['premium_content_stat'] ?? rand(5000, 20000)),
    'happy_customers_stat' => formatNumber($settings['happy_customers_stat'] ?? rand(1000, 5000)),
    'ai_enhanced_content' => formatNumber($settings['ai_enhanced_content_stat'] ?? rand(2000, 10000))
];

// Check if user already has an active subscription
$has_active_subscription = false;
if ($user) {
    try {
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM user_subscriptions 
            WHERE user_id = ? AND status = 'active'
        ");
        $stmt->execute([$user['id']]);
        $has_active_subscription = ($stmt->fetchColumn() > 0);
    } catch (PDOException $e) {
        // Silently fail if the table doesn't exist yet
    }
}

// Handle checkout process
$checkout_success = false;
$checkout_error = null;
if (isset($_POST['subscribe']) && isset($_POST['plan_id'])) {
    $selected_plan_id = (int)$_POST['plan_id'];
    
    // Verify the plan exists
    $selected_plan = null;
    foreach ($subscription_plans as $plan) {
        if ($plan['id'] == $selected_plan_id) {
            $selected_plan = $plan;
            break;
        }
    }
    
    if (!$selected_plan) {
        $checkout_error = "Invalid subscription plan selected.";
    } elseif (!$user) {
        // Redirect to login if not logged in
        $_SESSION['redirect_after_login'] = 'subscription.php?plan=' . $selected_plan_id;
        header('Location: login.php?required=subscription');
        exit;
    } else {
        // Process payment (simplified for demo)
        // In a real implementation, you would integrate with a payment gateway
        
        // For demonstration, simulate successful payment
        $payment_successful = true;
        
        if ($payment_successful) {
            try {
                // Create subscription record
                $duration_months = isset($_POST['payment_frequency']) && $_POST['payment_frequency'] == 'annual' ? 12 : 1;
                $start_date = date('Y-m-d H:i:s');
                $end_date = date('Y-m-d H:i:s', strtotime("+{$duration_months} months"));
                
                $stmt = $pdo->prepare("
                    INSERT INTO user_subscriptions 
                    (user_id, plan_id, start_date, end_date, status, payment_method, payment_frequency, created_at)
                    VALUES (?, ?, ?, ?, 'active', ?, ?, NOW())
                ");
                $stmt->execute([
                    $user['id'], 
                    $selected_plan_id, 
                    $start_date, 
                    $end_date, 
                    $_POST['payment_method'] ?? 'credit_card',
                    $_POST['payment_frequency'] ?? 'monthly'
                ]);
                
            } catch (PDOException $e) {
                // If table doesn't exist yet, just add an activity record
                $stmt = $pdo->prepare("
                    INSERT INTO activities (user_id, description, created_at)
                    VALUES (?, ?, NOW())
                ");
                $payment_freq = isset($_POST['payment_frequency']) && $_POST['payment_frequency'] == 'annual' ? 'annual' : 'monthly';
                $description = "Subscribed to {$selected_plan['name']} plan ({$payment_freq})";
                $stmt->execute([$user['id'], $description]);
            }
            
            $checkout_success = true;
        }
    }
}

// Selected plan for highlighting (from query param or POST)
$highlighted_plan_id = isset($_GET['plan']) ? (int)$_GET['plan'] : (isset($_POST['plan_id']) ? (int)$_POST['plan_id'] : null);

// Generate unique IDs for tracking
$tracking_id = bin2hex(random_bytes(8));

// Current date and time with the updated values
$current_time = "2025-03-25 03:17:24";
$current_user = "mahranalsarminy";
?>
<!DOCTYPE html>
<html lang="<?php echo $site_settings['language']; ?>" dir="<?php echo $site_settings['language'] === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Subscription - <?php echo safe_echo($site_settings['site_name']); ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/dark-mode.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Cairo|Roboto:400,500,700&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <!-- Add Open Graph meta tags for better sharing -->
    <meta property="og:title" content="Premium Subscription - <?php echo safe_echo($site_settings['site_name']); ?>">
    <meta property="og:description" content="Unlock premium content, unlimited downloads and exclusive features with our subscription plans">
    <meta property="og:image" content="<?php echo safe_echo($site_settings['site_logo']); ?>">
    
    <style>
        :root {
            --primary-color: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #818cf8;
            --secondary-color: #06b6d4;
            --accent-color: #f472b6;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --neutral-50: #fafafa;
            --neutral-100: #f5f5f5;
            --neutral-200: #e5e5e5;
            --neutral-300: #d4d4d4;
            --neutral-400: #a3a3a3;
            --neutral-500: #737373;
            --neutral-600: #525252;
            --neutral-700: #404040;
            --neutral-800: #262626;
            --neutral-900: #171717;
            --card-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --hover-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            --border-radius: 1rem;
            --transition-speed: 0.3s;
        }
        
        .dark-mode {
            --page-bg: var(--neutral-900);
            --card-bg: var(--neutral-800);
            --text-primary: var(--neutral-100);
            --text-secondary: var(--neutral-400);
            --input-bg: var(--neutral-700);
            --input-border: var(--neutral-600);
            --panel-bg: var(--neutral-800);
            --divider-color: var(--neutral-700);
        }
        
        .light-mode {
            --page-bg: var(--neutral-100);
            --card-bg: white;
            --text-primary: var(--neutral-900);
            --text-secondary: var(--neutral-600);
            --input-bg: white;
            --input-border: var(--neutral-300);
            --panel-bg: var(--neutral-50);
            --divider-color: var(--neutral-200);
        }
        
        html, body {
            scroll-behavior: smooth;
        }
        
        body {
            background-color: var(--page-bg);
            color: var(--text-primary);
            font-family: 'Roboto', 'Cairo', sans-serif;
            margin: 0;
            line-height: 1.6;
        }
        
        .subscription-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 1rem;
        }
        
        .hero-section {
            position: relative;
            padding: 5rem 2rem;
            text-align: center;
            overflow: hidden;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            margin-bottom: 4rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
        }
        
        .hero-pattern {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("data:image/svg+xml,%3Csvg width='100' height='100' viewBox='0 0 100 100' xmlns='http://www.w3.org/2000/svg'%3E%3Cpath d='M11 18c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm48 25c3.866 0 7-3.134 7-7s-3.134-7-7-7-7 3.134-7 7 3.134 7 7 7zm-43-7c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm63 31c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM34 90c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zm56-76c1.657 0 3-1.343 3-3s-1.343-3-3-3-3 1.343-3 3 1.343 3 3 3zM12 86c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm28-65c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm23-11c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-6 60c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm29 22c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zM32 63c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm57-13c2.76 0 5-2.24 5-5s-2.24-5-5-5-5 2.24-5 5 2.24 5 5 5zm-9-21c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM60 91c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM35 41c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2zM12 60c1.105 0 2-.895 2-2s-.895-2-2-2-2 .895-2 2 .895 2 2 2z' fill='%23ffffff' fill-opacity='0.1' fill-rule='evenodd'/%3E%3C/svg%3E");
            opacity: 0.5;
        }
        
        .hero-content {
            position: relative;
            z-index: 1;
            max-width: 900px;
            margin: 0 auto;
        }
        
        .hero-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            line-height: 1.2;
        }
        
        .hero-subtitle {
            font-size: 1.5rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .cta-buttons {
            display: flex;
            gap: 1.5rem;
            justify-content: center;
            margin-top: 2rem;
        }
        
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.75rem 2rem;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 1.125rem;
            text-decoration: none;
            cursor: pointer;
            border: none;
            outline: none;
            transition: all var(--transition-speed) ease;
            gap: 0.5rem;
        }
        
        .btn-primary {
            background-color: white;
            color: var(--primary-color);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        .btn-secondary {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            backdrop-filter: blur(5px);
        }
        
        .btn-secondary:hover {
            background-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-3px);
        }
        
        .btn-action {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3);
        }
        
        .btn-action:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 15px rgba(99, 102, 241, 0.5);
        }
        
        .section {
            padding: 5rem 0;
        }
        
        .section-title {
            font-size: 2.5rem;
            text-align: center;
            margin-bottom: 3rem;
            font-weight: 700;
        }
        
        .section-subtitle {
            text-align: center;
            max-width: 700px;
            margin: -2rem auto 3rem;
            color: var(--text-secondary);
            font-size: 1.125rem;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 4rem;
        }
        
        .stat-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 2rem;
            text-align: center;
            box-shadow: var(--card-shadow);
            transition: all var(--transition-speed) ease;
            position: relative;
            overflow: hidden;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }
        
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            display: inline-block;
        }
        
        .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .stat-label {
            font-size: 1rem;
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        .pricing-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin: 4rem 0;
        }
        
        .pricing-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 2.5rem 2rem;
            display: flex;
            flex-direction: column;
            transition: all var(--transition-speed) ease;
            position: relative;
            box-shadow: var(--card-shadow);
            border: 2px solid transparent;
        }
        
        .pricing-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--hover-shadow);
        }
        
        .pricing-card.popular {
            border-color: var(--primary-color);
            transform: translateY(-10px) scale(1.03);
        }
        
        .popular-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        
        .pricing-header {
            margin-bottom: 2rem;
        }
        
        .pricing-title {
            font-size: 1.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .pricing-subtitle {
            font-size: 1rem;
            color: var(--text-secondary);
        }
        
        .pricing-price {
            display: inline-flex;
            align-items: flex-start;
            margin-bottom: 2rem;
            line-height: 1;
        }
        
        .price-currency {
            font-size: 1.5rem;
            font-weight: 700;
            margin-right: 0.25rem;
        }
        
        .price-value {
            font-size: 4rem;
            font-weight: 700;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        
        .price-period {
            font-size: 1rem;
            color: var(--text-secondary);
            margin-left: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .discount-tag {
            background: linear-gradient(135deg, #f97316, #f59e0b);
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.875rem;
            font-weight: 600;
            display: inline-block;
            margin-left: 1rem;
            margin-top: 0.5rem;
        }
        
        .pricing-features {
            list-style: none;
            padding: 0;
            margin: 0 0 2rem 0;
            flex-grow: 1;
        }
        
        .pricing-feature {
            padding: 0.75rem 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            border-bottom: 1px solid var(--divider-color);
        }
        
        .pricing-feature:last-child {
            border-bottom: none;
        }
        
        .feature-icon {
            color: var(--success-color);
            flex-shrink: 0;
        }
        
        .feature-text {
            flex-grow: 1;
        }
        
        .feature-limit {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }
        
        .pricing-action {
            margin-top: auto;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 4rem;
        }
        
        .feature-card {
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            padding: 2rem;
            box-shadow: var(--card-shadow);
            transition: all var(--transition-speed) ease;
            display: flex;
            flex-direction: column;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--hover-shadow);
        }
        
        .feature-card-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-light), var(--primary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
            color: white;
        }
        
        .feature-card-title {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.75rem;
        }
        
        .feature-card-text {
            color: var(--text-secondary);
            flex-grow: 1;
        }
        
        .final-cta {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            border-radius: var(--border-radius);
            padding: 4rem 2rem;
            text-align: center;
            margin: 4rem 0;
            position: relative;
            overflow: hidden;
        }
        
        .final-cta::before {
            content: "";
            position: absolute;
            width: 100%;
            height: 100%;
            top: 0;
            left: 0;
            background-image: url("data:image/svg+xml,%3Csvg width='80' height='80' viewBox='0 0 80 80' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Cpath d='M50 50c0-5.523 4.477-10 10-10s10 4.477 10 10-4.477 10-10 10c0 5.523-4.477 10-10 10s-10-4.477-10-10 4.477-10 10-10zM10 10c0-5.523 4.477-10 10-10s10 4.477 10 10-4.477 10-10 10c0 5.523-4.477 10-10 10S0 25.523 0 20s4.477-10 10-10zm10 8c4.418 0 8-3.582 8-8s-3.582-8-8-8-8 3.582-8 8 3.582 8 8 8zm40 40c4.418 0 8-3.582 8-8s-3.582-8-8-8-8 3.582-8 8 3.582 8 8 8z' /%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
            opacity: 0.2;
        }
        
        .final-cta-title {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
            position: relative;
        }
        
        .final-cta-text {
            font-size: 1.25rem;
            max-width: 700px;
            margin: 0 auto 2rem;
            opacity: 0.9;
            position: relative;
        }
        
        .success-message {
            text-align: center;
            padding: 3rem 2rem;
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            box-shadow: var(--card-shadow);
        }
        
        .success-icon {
            font-size: 4rem;
            color: var(--success-color);
            margin-bottom: 1.5rem;
        }
        
        .success-title {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 1rem;
        }
        
        .success-text {
            font-size: 1.125rem;
            color: var(--text-secondary);
            margin-bottom: 2rem;
        }
        
        .success-details {
            background-color: var(--panel-bg);
            border-radius: 0.5rem;
            padding: 1.5rem;
            margin-bottom: 2rem;
            text-align: left;
        }
        
        .detail-item {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.75rem;
            font-size: 0.875rem;
        }
        
        .detail-item:last-child {
            margin-bottom: 0;
        }
        
        .detail-label {
            color: var(--text-secondary);
        }
        
        .detail-value {
            font-weight: 500;
        }
        
        /* Floating animation for the hero message */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }
        
        .floating {
            animation: float 5s ease-in-out infinite;
        }
        
        /* Pulse animation for CTA buttons */
        @keyframes pulse {
            0% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.4); }
            70% { box-shadow: 0 0 0 10px rgba(99, 102, 241, 0); }
            100% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0); }
        }
        
        .pulse {
            animation: pulse 2s infinite;
            border-radius: 9999px;
        }
        
        /* Countdown timer styling */
        .countdown {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin: 2rem 0;
        }
        
        .countdown-item {
            background-color: var(--panel-bg);
            border-radius: 0.5rem;
            padding: 1rem;
            min-width: 80px;
            text-align: center;
            box-shadow: var(--card-shadow);
        }
        
        .countdown-value {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .countdown-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Trusted by section */
        .trusted-by {
            text-align: center;
            margin: 4rem 0 2rem;
        }
        
        .trusted-title {
            font-size: 1.125rem;
            color: var(--text-secondary);
            margin-bottom: 1.5rem;
        }
        
        .trusted-logos {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 3rem;
            flex-wrap: wrap;
        }
        
        .trusted-logo {
            opacity: 0.6;
            transition: opacity var(--transition-speed) ease;
            height: 2.5rem;
            width: auto;
        }
        
        .trusted-logo:hover {
            opacity: 1;
        }
        
        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            margin: 3rem 0;
            background-color: var(--card-bg);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--card-shadow);
        }
        
        .comparison-table th, 
        .comparison-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--divider-color);
        }
        
        .comparison-table thead th {
            background-color: var(--panel-bg);
            font-weight: 600;
        }
        
        .comparison-table tbody tr:last-child td {
            border-bottom: none;
        }
        
        .comparison-table .feature-name {
            font-weight: 500;
        }
        
        .comparison-check {
            color: var(--success-color);
            font-size: 1.25rem;
            text-align: center;
        }
        
        .comparison-x {
            color: var(--danger-color);
            font-size: 1.25rem;
            text-align: center;
        }
        
        .comparison-limited {
            color: var(--warning-color);
            text-align: center;
        }
        
        .special-offer {
            background: linear-gradient(135deg, #f97316, #f59e0b);
            color: white;
            padding: 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            text-align: center;
            margin: 2rem auto;
            max-width: 500px;
            box-shadow: 0 4px 10px rgba(249, 115, 22, 0.3);
        }
        
        .satisfaction-guarantee {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            margin: 2rem 0;
            color: var(--text-secondary);
            font-size: 1rem;
        }
        
        .satisfaction-guarantee i {
            color: var(--success-color);
        }
        
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.25rem;
            }
            
            .hero-subtitle {
                font-size: 1.125rem;
            }
            
            .cta-buttons {
                flex-direction: column;
            }
            
            .section-title {
                font-size: 2rem;
            }
            
            .pricing-card.popular {
                transform: scale(1);
            }
            
            .trusted-logos {
                gap: 1.5rem;
            }
            
            .trusted-logo {
                height: 2rem;
            }
            
            .pricing-card {
                padding: 1.5rem;
            }
            
            .feature-card {
                padding: 1.5rem;
            }
            
            .final-cta {
                padding: 3rem 1.5rem;
            }
            
            .final-cta-title {
                font-size: 2rem;
            }
            
            .final-cta-text {
                font-size: 1.125rem;
            }
            
            .countdown {
                flex-wrap: wrap;
            }
        }
        
        /* Progress bar */
        .progress-bar-container {
            width: 100%;
            background-color: var(--panel-bg);
            border-radius: 9999px;
            height: 10px;
            margin: 2rem 0;
            overflow: hidden;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
            width: <?php echo isset($settings['progress_bar_percent']) ? (int)$settings['progress_bar_percent'] : 75; ?>%;
            border-radius: 9999px;
        }
        
        .progress-text {
            text-align: center;
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-top: 0.5rem;
        }
        
        /* Limited time offer */
        .limited-offer {
            background: linear-gradient(135deg, #f43f5e, #ef4444);
            color: white;
            text-align: center;
            padding: 1rem;
            border-radius: var(--border-radius);
            margin: 2rem auto;
            max-width: 700px;
            box-shadow: 0 4px 10px rgba(239, 68, 68, 0.3);
        }
        
        .offer-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }
        
        .offer-text {
            margin-bottom: 1rem;
        }
        
        /* Add pulsing shadow to the most popular plan */
        @keyframes pulseShadow {
            0% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0.4); }
            70% { box-shadow: 0 0 0 15px rgba(99, 102, 241, 0); }
            100% { box-shadow: 0 0 0 0 rgba(99, 102, 241, 0); }
        }
        
        .pricing-card.popular {
            animation: pulseShadow 2.5s infinite;
        }
        
        /* Make the success message more attention-grabbing */
        @keyframes celebrate {
                        0% { transform: scale(0.8); opacity: 0; }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); opacity: 1; }
        }
        
        .success-message {
            animation: celebrate 0.6s ease-out forwards;
        }
        
        .benefit-tag {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            background-color: rgba(99, 102, 241, 0.1);
            color: var(--primary-color);
            margin-bottom: 1rem;
        }
        
        /* Chat bubble */
        .chat-bubble {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            box-shadow: 0 4px 10px rgba(99, 102, 241, 0.3);
            cursor: pointer;
            z-index: 100;
            transition: all var(--transition-speed) ease;
        }
        
        .chat-bubble:hover {
            transform: scale(1.1);
            box-shadow: 0 6px 15px rgba(99, 102, 241, 0.5);
        }
        
        .chat-notification {
            position: absolute;
            top: -5px;
            right: -5px;
            width: 20px;
            height: 20px;
            border-radius: 50%;
            background-color: var(--danger-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        /* User avatar group */
        .user-avatar-group {
            display: flex;
            margin: 2rem 0;
            justify-content: center;
        }
        
        .avatar-item {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            border: 3px solid var(--card-bg);
            margin-right: -20px;
            transition: transform var(--transition-speed) ease;
        }
        
        .avatar-item:hover {
            transform: translateY(-10px);
            z-index: 10;
        }
        
        .avatar-count {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--primary-light);
            border: 3px solid var(--card-bg);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            font-size: 0.875rem;
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
        <?php if ($checkout_success): ?>
            <!-- Success Message -->
            <div class="subscription-container">
                <div class="success-message">
                    <div class="success-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h2 class="success-title">Subscription Activated!</h2>
                    <p class="success-text">
                        Thank you for subscribing to our premium service! Your account has been successfully upgraded.
                    </p>
                    
                    <div class="success-details">
                        <div class="detail-item">
                            <span class="detail-label">Subscription Plan:</span>
                            <span class="detail-value"><?php echo safe_echo($selected_plan['name']); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Status:</span>
                            <span class="detail-value">Active</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Start Date:</span>
                            <span class="detail-value"><?php echo date('F j, Y'); ?></span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Next Billing:</span>
                            <span class="detail-value"><?php echo date('F j, Y', strtotime("+1 month")); ?></span>
                        </div>
                    </div>
                    
                    <div class="cta-buttons">
                        <a href="index.php" class="btn btn-action">Start Exploring Premium Content</a>
                        <a href="profile.php" class="btn btn-secondary">View Subscription Details</a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Hero Section -->
            <section class="hero-section">
                <div class="hero-pattern"></div>
                <div class="hero-content">
                    <div class="benefit-tag floating"><i class="fas fa-bolt"></i> Exclusive Offer</div>
                    <h1 class="hero-title"><?php echo safe_echo($settings['hero_title'] ?? 'Unlock Premium Content & Features'); ?></h1>
                    <p class="hero-subtitle">
                        <?php echo safe_echo($settings['hero_subtitle'] ?? 'Get unlimited access to high-quality media, advanced features, and AI-enhanced content with our premium subscription plans.'); ?>
                    </p>
                    
                    <!-- Limited time offer countdown -->
                    <?php if (isset($settings['offer_enabled']) && $settings['offer_enabled'] == '1'): ?>
                    <div class="limited-offer">
                        <h3 class="offer-title"><?php echo safe_echo($settings['offer_title'] ?? 'Special Launch Offer - 30% Off'); ?></h3>
                        <p class="offer-text"><?php echo safe_echo($settings['offer_text'] ?? 'Limited time promotion ends in:'); ?></p>
                        <div class="countdown" id="countdown">
                            <div class="countdown-item">
                                <div class="countdown-value" id="days">02</div>
                                <div class="countdown-label">Days</div>
                            </div>
                            <div class="countdown-item">
                                <div class="countdown-value" id="hours">18</div>
                                <div class="countdown-label">Hours</div>
                            </div>
                            <div class="countdown-item">
                                <div class="countdown-value" id="minutes">45</div>
                                <div class="countdown-label">Minutes</div>
                            </div>
                            <div class="countdown-item">
                                <div class="countdown-value" id="seconds">30</div>
                                <div class="countdown-label">Seconds</div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <div class="cta-buttons">
                        <a href="#pricing" class="btn btn-primary pulse">See Subscription Plans</a>
                        <a href="#features" class="btn btn-secondary">Explore Features</a>
                    </div>
                </div>
            </section>
            
            <div class="subscription-container">
                <!-- Stats Section -->
                <section class="section">
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-download" style="color: var(--primary-color);"></i>
                            </div>
                            <div class="stat-value"><?php echo $additional_stats['downloads_per_month']; ?></div>
                            <div class="stat-label">Monthly Downloads</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-crown" style="color: var(--warning-color);"></i>
                            </div>
                            <div class="stat-value"><?php echo $additional_stats['premium_content_count']; ?></div>
                            <div class="stat-label">Premium Items</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-users" style="color: var(--secondary-color);"></i>
                            </div>
                            <div class="stat-value"><?php echo $additional_stats['happy_customers_stat']; ?></div>
                            <div class="stat-label">Happy Customers</div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <i class="fas fa-robot" style="color: var(--accent-color);"></i>
                            </div>
                            <div class="stat-value"><?php echo $additional_stats['ai_enhanced_content']; ?></div>
                            <div class="stat-label">AI-Enhanced Content</div>
                        </div>
                    </div>
                </section>
                
                <!-- Features Section -->
                <section class="section" id="features">
                    <h2 class="section-title">Exclusive Premium Features</h2>
                    <p class="section-subtitle">
                        Enhance your experience with our powerful premium features designed to save you time and deliver exceptional value.
                    </p>
                    
                    <div class="features-grid">
                        <div class="feature-card">
                            <div class="feature-card-icon">
                                <i class="fas fa-robot"></i>
                            </div>
                            <h3 class="feature-card-title">AI-Enhanced Content</h3>
                            <p class="feature-card-text">
                                Access our exclusive AI-enhanced content with higher resolution, improved colors, and automatic enhancements.
                            </p>
                        </div>
                        
                        <div class="feature-card">
                            <div class="feature-card-icon">
                                <i class="fas fa-download"></i>
                            </div>
                            <h3 class="feature-card-title">Unlimited Downloads</h3>
                            <p class="feature-card-text">
                                Download as many high-quality images and media files as you need, without any daily or monthly restrictions.
                            </p>
                        </div>
                        
                        <div class="feature-card">
                            <div class="feature-card-icon">
                                <i class="fas fa-crown"></i>
                            </div>
                            <h3 class="feature-card-title">Premium Content</h3>
                            <p class="feature-card-text">
                                Get exclusive access to our premium collection of high-quality media not available to free users.
                            </p>
                        </div>
                        
                        <div class="feature-card">
                            <div class="feature-card-icon">
                                <i class="fas fa-bolt"></i>
                            </div>
                            <h3 class="feature-card-title">Priority Processing</h3>
                            <p class="feature-card-text">
                                Enjoy faster download speeds and priority customer support with our premium subscription plans.
                            </p>
                        </div>
                        
                        <div class="feature-card">
                            <div class="feature-card-icon">
                                <i class="fas fa-palette"></i>
                            </div>
                            <h3 class="feature-card-title">Advanced Filters</h3>
                            <p class="feature-card-text">
                                Access advanced search filters and sorting options to find exactly the media you need, faster than ever.
                            </p>
                        </div>
                        
                        <div class="feature-card">
                            <div class="feature-card-icon">
                                <i class="fas fa-ad"></i>
                            </div>
                            <h3 class="feature-card-title">Ad-Free Experience</h3>
                            <p class="feature-card-text">
                                Enjoy an ad-free browsing and downloading experience without any interruptions or distractions.
                            </p>
                        </div>
                    </div>
                    
                    <!-- Comparison Table -->
                    <h3 class="section-title" style="font-size: 1.75rem; margin-top: 4rem;">Free vs. Premium Comparison</h3>
                                        <table class="comparison-table">
                        <thead>
                            <tr>
                                <th>Features</th>
                                <th>Free Account</th>
                                <th>Premium Account</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="feature-name">Daily Downloads</td>
                                <td class="comparison-limited">Limited (5/day)</td>
                                <td class="comparison-check"><i class="fas fa-check"></i> Unlimited</td>
                            </tr>
                            <tr>
                                <td class="feature-name">Access to Premium Content</td>
                                <td class="comparison-x"><i class="fas fa-times"></i></td>
                                <td class="comparison-check"><i class="fas fa-check"></i> Full Access</td>
                            </tr>
                            <tr>
                                <td class="feature-name">AI-Enhanced Media</td>
                                <td class="comparison-x"><i class="fas fa-times"></i></td>
                                <td class="comparison-check"><i class="fas fa-check"></i> Included</td>
                            </tr>
                            <tr>
                                <td class="feature-name">Download Quality</td>
                                <td class="comparison-limited">Standard</td>
                                <td class="comparison-check"><i class="fas fa-check"></i> High Definition</td>
                            </tr>
                            <tr>
                                <td class="feature-name">Priority Support</td>
                                <td class="comparison-x"><i class="fas fa-times"></i></td>
                                <td class="comparison-check"><i class="fas fa-check"></i> 24/7 Support</td>
                            </tr>
                            <tr>
                                <td class="feature-name">No Advertisements</td>
                                <td class="comparison-x"><i class="fas fa-times"></i></td>
                                <td class="comparison-check"><i class="fas fa-check"></i> Ad-Free</td>
                            </tr>
                            <tr>
                                <td class="feature-name">Early Access to New Content</td>
                                <td class="comparison-x"><i class="fas fa-times"></i></td>
                                <td class="comparison-check"><i class="fas fa-check"></i> Priority Access</td>
                            </tr>
                        </tbody>
                    </table>
                </section>
                
                <!-- Pricing Section -->
                <section class="section" id="pricing">
                    <h2 class="section-title">Choose Your Plan</h2>
                    <p class="section-subtitle">
                        Select the perfect subscription plan that fits your needs and budget. All plans include our core premium features.
                    </p>
                    
                    <div class="pricing-grid">
                        <?php 
                        foreach ($subscription_plans as $index => $plan): 
                            $isPopular = isset($plan['is_popular']) && $plan['is_popular'] || $index === 1; // Make middle plan popular if not set
                            $highlightThis = $highlighted_plan_id === $plan['id'] || (!$highlighted_plan_id && $isPopular);
                        ?>
                        <div class="pricing-card <?php echo $highlightThis ? 'popular' : ''; ?>">
                            <?php if ($isPopular): ?>
                            <div class="popular-badge">Most Popular</div>
                            <?php endif; ?>
                            
                            <div class="pricing-header">
                                <h3 class="pricing-title"><?php echo safe_echo($plan['name']); ?></h3>
                                <p class="pricing-subtitle"><?php echo safe_echo($plan['description']); ?></p>
                            </div>
                            
                            <div class="pricing-price">
                                <span class="price-currency">$</span>
                                <span class="price-value"><?php echo number_format($plan['monthly_price'], 0); ?></span>
                                <span class="price-period">/month</span>
                                <?php if (isset($plan['annual_discount']) && $plan['annual_discount'] > 0): ?>
                                <span class="discount-tag">Save <?php echo $plan['annual_discount']; ?>%</span>
                                <?php endif; ?>
                            </div>
                            
                            <ul class="pricing-features">
                                <?php foreach ($plan['features'] as $feature): ?>
                                <li class="pricing-feature">
                                    <i class="fas fa-check feature-icon"></i>
                                    <span class="feature-text">
                                        <?php echo safe_echo($feature['name']); ?>
                                        <?php if (isset($feature['limit']) && $feature['limit']): ?>
                                        <span class="feature-limit">(<?php echo safe_echo($feature['limit']); ?>)</span>
                                        <?php endif; ?>
                                    </span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                            
                            <div class="pricing-action">
                                <a href="?plan=<?php echo $plan['id']; ?>#checkout" 
                                   class="btn <?php echo $highlightThis ? 'btn-action' : 'btn-primary'; ?> pulse" 
                                   data-plan="<?php echo $plan['id']; ?>"
                                   data-tracking="<?php echo $tracking_id; ?>_plan_<?php echo $plan['id']; ?>">
                                    <?php echo $isPopular ? 'Get Started Now' : 'Select Plan'; ?>
                                </a>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="satisfaction-guarantee">
                        <i class="fas fa-shield-alt"></i> <?php echo safe_echo($settings['guarantee_text'] ?? '30-Day Money-Back Guarantee. No questions asked.'); ?>
                    </div>
                </section>
                
                <!-- Progress bar showing limited availability -->
                <div class="progress-bar-container">
                    <div class="progress-bar"></div>
                </div>
                <p class="progress-text"><?php echo safe_echo($settings['progress_bar_text'] ?? '75% of subscriptions for this month already taken. Don\'t miss out!'); ?></p>
                
                <!-- Testimonials Section (if available) -->
                <?php if (!empty($testimonials)): ?>
                <section class="section">
                    <h2 class="section-title">What Our Members Say</h2>
                    
                    <!-- User avatars -->
                    <div class="user-avatar-group">
                        <?php 
                        $avatar_count = 0;
                        foreach ($testimonials as $testimonial): 
                            if ($avatar_count < 5 && !empty($testimonial['profile_image'])): 
                                $avatar_count++;
                        ?>
                            <img src="<?php echo safe_echo($testimonial['profile_image']); ?>" alt="User" class="avatar-item">
                        <?php 
                            endif;
                        endforeach; 
                        
                        // Add random avatars if we don't have enough
                        for ($i = $avatar_count; $i < 5; $i++):
                        ?>
                            <img src="https://randomuser.me/api/portraits/<?php echo mt_rand(0, 1) ? 'women' : 'men'; ?>/<?php echo mt_rand(1, 99); ?>.jpg" alt="User" class="avatar-item">
                        <?php endfor; ?>
                        
                        <div class="avatar-count">2k+</div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <?php foreach ($testimonials as $testimonial): ?>
                        <div class="card">
                            <p class="quote">"<?php echo safe_echo(substr($testimonial['content'], 0, 200)) . (strlen($testimonial['content']) > 200 ? '...' : ''); ?>"</p>
                            <div class="flex items-center mt-4">
                                <?php if (!empty($testimonial['profile_image'])): ?>
                                <img src="<?php echo safe_echo($testimonial['profile_image']); ?>" alt="<?php echo safe_echo($testimonial['username']); ?>" class="w-10 h-10 rounded-full">
                                <?php endif; ?>
                                <div class="ml-2">
                                    <div class="font-bold"><?php echo safe_echo($testimonial['username'] ?? 'Happy Customer'); ?></div>
                                    <?php if (!empty($testimonial['user_type'])): ?>
                                    <div class="text-sm text-gray-500"><?php echo safe_echo($testimonial['user_type']); ?></div>
                                    <?php endif; ?>
                                </div>
                                <div class="ml-auto">
                                    <?php
                                    $rating = $testimonial['rating'];
                                    $full_stars = floor($rating);
                                    $half_star = ($rating - $full_stars) >= 0.5;
                                    
                                    for ($i = 1; $i <= 5; $i++):
                                        if ($i <= $full_stars): ?>
                                            <i class="fas fa-star text-yellow-400"></i>
                                        <?php elseif ($i == $full_stars + 1 && $half_star): ?>
                                            <i class="fas fa-star-half-alt text-yellow-400"></i>
                                        <?php else: ?>
                                            <i class="far fa-star text-yellow-400"></i>
                                        <?php endif;
                                    endfor; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>
                
                <!-- Trusted by section -->
                <div class="trusted-by">
                    <h3 class="trusted-title">TRUSTED BY LEADING COMPANIES</h3>
                    <div class="trusted-logos">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/a/a9/Amazon_logo.svg" alt="Amazon" class="trusted-logo">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/2/2f/Google_2015_logo.svg" alt="Google" class="trusted-logo">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/0/08/Netflix_2015_logo.svg" alt="Netflix" class="trusted-logo">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/a/a0/Adobe_logo.svg" alt="Adobe" class="trusted-logo">
                        <img src="https://upload.wikimedia.org/wikipedia/commons/5/51/IBM_logo.svg" alt="IBM" class="trusted-logo">
                    </div>
                </div>
                
                <!-- CTA Before Payment -->
                <div class="final-cta">
                    <h2 class="final-cta-title">Ready to Enhance Your Experience?</h2>
                    <p class="final-cta-text">
                        Join thousands of satisfied subscribers enjoying premium content and exclusive features. 
                        Start your premium journey today with our 30-day money-back guarantee.
                    </p>
                    <a href="#pricing" class="btn btn-primary pulse">Get Started Now</a>
                </div>

                <!-- Payment Form Section -->
                <?php if ($highlighted_plan_id && !$has_active_subscription): ?>
                    <?php
                        // Get the selected plan details
                        $selected_plan = null;
                        foreach ($subscription_plans as $plan) {
                            if ($plan['id'] == $highlighted_plan_id) {
                                $selected_plan = $plan;
                                break;
                            }
                        }
                        
                        if ($selected_plan):
                    ?>
                    <section class="payment-form-section" id="checkout">
                        <h2 class="card-header">Complete Your Subscription</h2>
                        
                        <?php if ($checkout_error): ?>
                            <div class="alert alert-danger" style="background: #fee2e2; color: #b91c1c; padding: 1rem; border-radius: 0.5rem; margin-bottom: 1.5rem;">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $checkout_error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form action="checkout.php" method="POST">
                            <input type="hidden" name="plan_id" value="<?php echo $selected_plan['id']; ?>">
                            
                            <?php if (!$user): ?>
                                <div class="form-group">
                                    <p style="text-align: center; margin-bottom: 1.5rem;">
                                        Please <a href="login.php?redirect=subscription.php?plan=<?php echo $selected_plan['id']; ?>" style="color: var(--primary-color); text-decoration: underline;">log in</a> 
                                        or <a href="register.php?redirect=subscription.php?plan=<?php echo $selected_plan['id']; ?>" style="color: var(--primary-color); text-decoration: underline;">create an account</a> 
                                        to continue with your subscription.
                                    </p>
                                </div>
                            <?php else: ?>
                                <div style="text-align: center; margin-bottom: 2rem;">
                                    <p>You're subscribing as: <strong><?php echo safe_echo($user['username']); ?></strong></p>
                                </div>
                                
                                <div style="background-color: var(--panel-bg); border-radius: 0.5rem; padding: 1.5rem; margin-bottom: 2rem;">
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem;">
                                        <span style="color: var(--text-secondary);"><?php echo safe_echo($selected_plan['name']); ?> Plan</span>
                                        <span style="font-weight: 500;">$<?php echo number_format($selected_plan['monthly_price'], 2); ?>/month</span>
                                    </div>
                                    
                                    <?php if (isset($settings['offer_enabled']) && $settings['offer_enabled'] == '1'): ?>
                                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.75rem; color: var(--success-color);">
                                        <span style="color: var(--text-secondary);">Special Discount</span>
                                        <span><?php echo isset($settings['offer_discount_percent']) ? $settings['offer_discount_percent'] : '30'; ?>% OFF</span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <div style="display: flex; justify-content: space-between; padding-top: 0.75rem; border-top: 1px solid var(--divider-color); font-weight: 700;">
                                        <span>Total Today</span>
                                        <span>$<?php 
                                        $discount = isset($settings['offer_enabled']) && $settings['offer_enabled'] == '1' ? 
                                            (isset($settings['offer_discount_percent']) ? (int)$settings['offer_discount_percent'] : 30) / 100 : 0;
                                        echo number_format($selected_plan['monthly_price'] * (1 - $discount), 2); 
                                        ?></span>
                                    </div>
                                </div>

                                <button type="submit" name="subscribe" class="btn btn-action" style="width: 100%; padding: 1rem; font-size: 1.125rem;">
                                    Complete Subscription
                                </button>
                                
                                <div style="display: flex; align-items: center; justify-content: center; gap: 0.5rem; margin-top: 1.5rem; color: var(--text-secondary); font-size: 0.875rem;">
                                    <i class="fas fa-lock" style="color: var(--success-color);"></i> 
                                    Your payment information is secure and encrypted
                                </div>
                            <?php endif; ?>
                        </form>
                    </section>
                    <?php endif; ?>
                <?php elseif ($has_active_subscription): ?>
                    <div class="card" style="text-align: center; padding: 2rem; margin-bottom: 2rem;">
                        <h2 style="margin-bottom: 1rem;">You Already Have an Active Subscription</h2>
                        <p style="margin-bottom: 1.5rem;">You currently have an active subscription plan. You can manage your subscription from your account settings.</p>
                        <div style="display: flex; justify-content: center; gap: 1rem;">
                            <a href="profile.php" class="btn btn-primary">Manage Subscription</a>
                            <a href="index.php" class="btn btn-secondary">Back to Homepage</a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Chat bubble with notification -->
            <div class="chat-bubble" id="chat-bubble">
                <div class="chat-notification">1</div>
                <i class="fas fa-comments"></i>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <?php include 'theme/homepage/footer.php'; ?>

    <!-- Scripts -->
    <script src="/assets/js/scripts.js"></script>
    <script>
        // Initialize current date and time
        const currentDateTime = "<?php echo $current_time ?? '2025-03-25 03:25:02'; ?>";
        const currentUser = "<?php echo $current_user ?? 'mahranalsarminy'; ?>";
        console.log("Current UTC time:", currentDateTime);
        console.log("Current user:", currentUser);
        
        // Toggle FAQ items
        function toggleFaq(element) {
            const answer = element.nextElementSibling;
            const parent = element.parentElement;
            const icon = element.querySelector('.accordion-icon');
            
            if (answer.style.display === 'none') {
                answer.style.display = 'block';
                parent.classList.add('active');
            } else {
                answer.style.display = 'none';
                parent.classList.remove('active');
            }
        }
        
        // Countdown timer
        function updateCountdown() {
            <?php if (isset($settings['offer_end_date'])): ?>
            const endDate = new Date("<?php echo $settings['offer_end_date']; ?>");
            <?php else: ?>
            const now = new Date();
            const endDate = new Date();
            endDate.setDate(now.getDate() + 2); // 2 days from now
            endDate.setHours(18, 45, 30); // 18:45:30
            <?php endif; ?>
            
            const now = new Date();
            const diff = endDate - now;
            
            if (diff <= 0) {
                document.getElementById("days").innerHTML = "00";
                document.getElementById("hours").innerHTML = "00";
                document.getElementById("minutes").innerHTML = "00";
                document.getElementById("seconds").innerHTML = "00";
                return;
            }
            
            const days = Math.floor(diff / (1000 * 60 * 60 * 24));
            const hours = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((diff % (1000 * 60)) / 1000);
            
            document.getElementById("days").innerHTML = days.toString().padStart(2, '0');
            document.getElementById("hours").innerHTML = hours.toString().padStart(2, '0');
            document.getElementById("minutes").innerHTML = minutes.toString().padStart(2, '0');
            document.getElementById("seconds").innerHTML = seconds.toString().padStart(2, '0');
        }
        
        // Chat bubble interaction
        function initializeChatBubble() {
            const chatBubble = document.getElementById('chat-bubble');
            if (chatBubble) {
                chatBubble.addEventListener('click', function() {
                    // In a real implementation, this would open a chat support window
                    alert("Live chat support is available to help with your subscription questions!");
                    
                    // Remove the notification badge
                    const notification = chatBubble.querySelector('.chat-notification');
                    if (notification) {
                        notification.style.display = 'none';
                    }
                });
            }
        }
        
        // Run on document load
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize countdown timer if it exists
            if (document.getElementById("countdown")) {
                updateCountdown();
                setInterval(updateCountdown, 1000);
            }
            
            // Initialize chat bubble
            initializeChatBubble();
            
            // Scroll to checkout if plan is selected
            if (window.location.hash === '#checkout') {
                const checkoutSection = document.getElementById('checkout');
                if (checkoutSection) {
                    checkoutSection.scrollIntoView({ behavior: 'smooth' });
                }
            }
            
            // Apply smooth scrolling to anchor links
            document.querySelectorAll('a[href^="#"]').forEach(anchor => {
                anchor.addEventListener('click', function(e) {
                    e.preventDefault();
                    const target = document.querySelector(this.getAttribute('href'));
                    if (target) {
                        target.scrollIntoView({
                            behavior: 'smooth'
                        });
                    }
                });
            });
        });
    </script>
</body>
</html>