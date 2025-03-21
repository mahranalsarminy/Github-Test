<?php
require_once(__DIR__ . '/../includes/init.php');
$stmt = $pdo->query("SELECT site_name, site_logo, dark_mode, language FROM site_settings WHERE id = 1");
$site_settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Get privacy content from database
$stmt = $pdo->query("SELECT title, content FROM privacy_content WHERE is_active = 1 ORDER BY sort_order ASC");
$privacy_sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?php echo $site_settings['language']; ?>" dir="<?php echo $site_settings['language'] === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Privacy Policy - <?php echo $site_settings['site_name']; ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/dark-mode.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Cairo|Roboto&display=swap">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .privacy-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: var(--bg-card);
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .privacy-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .privacy-header h1 {
            font-size: 2.5rem;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }
        
        .privacy-header .date {
            color: var(--text-secondary);
            font-style: italic;
        }
        
        .privacy-content {
            color: var(--text-primary);
            line-height: 1.8;
        }
        
        .privacy-content section {
            margin-bottom: 2rem;
        }
        
        .privacy-content h2 {
            color: var(--accent-color);
            margin-bottom: 1rem;
            font-size: 1.8rem;
        }
        
        .privacy-content p {
            margin-bottom: 1rem;
        }
        
        .privacy-content ul {
            margin-left: 2rem;
            margin-bottom: 1rem;
        }
        
        .privacy-content li {
            margin-bottom: 0.5rem;
        }
        
        .privacy-content a {
            color: var(--link-color);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .privacy-content a:hover {
            text-decoration: underline;
        }
        
        /* Dark mode specific styles handled by CSS variables */
        
        @media (max-width: 768px) {
            .privacy-container {
                padding: 1.5rem;
                margin: 1rem;
            }
            
            .privacy-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body class="<?php echo $site_settings['dark_mode'] ? 'dark-mode' : 'light-mode'; ?>">
    <!-- Skip to main content link for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <!-- Header -->
    <?php include '../theme/homepage/header.php'; ?>

    <!-- Main Content -->
    <main id="main-content" role="main">
        <div class="privacy-container">
            <div class="privacy-header">
                <h1>Privacy Policy</h1>
                <p class="date">Last Updated: <?php echo date('F j, Y'); ?></p>
            </div>

            <div class="privacy-content">
                <?php 
                if (count($privacy_sections) > 0) {
                    foreach ($privacy_sections as $section) {
                        echo '<section>';
                        echo '<h2>' . htmlspecialchars($section['title']) . '</h2>';
                        echo $section['content']; // Already contains HTML formatting from the database
                        echo '</section>';
                    }
                } else {
                    echo '<p>Our privacy policy is currently being updated. Please check back soon.</p>';
                }
                ?>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include '../theme/homepage/footer.php'; ?>

    <!-- Accessibility button -->
    <div id="accessibility-toggle" class="accessibility-button" aria-label="Accessibility options">
        <i class="fas fa-universal-access"></i>
    </div>

    <!-- Accessibility menu -->
    <?php include '../theme/homepage/accessibility.php'; ?>

    <!-- Scripts -->
    <script src="/assets/js/scripts.js"></script>
    <script src="/assets/js/accessibility.js"></script>
</body>
</html>