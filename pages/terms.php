<?php
require_once(__DIR__ . '/../includes/init.php');
$stmt = $pdo->query("SELECT site_name, site_logo, dark_mode, language FROM site_settings WHERE id = 1");
$site_settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Get terms & conditions content from database
$stmt = $pdo->query("SELECT title, content FROM terms_content WHERE is_active = 1 ORDER BY sort_order ASC");
$terms_sections = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?php echo $site_settings['language']; ?>" dir="<?php echo $site_settings['language'] === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Terms & Conditions - <?php echo $site_settings['site_name']; ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/dark-mode.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Cairo|Roboto&display=swap">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .terms-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: var(--bg-card);
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .terms-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .terms-header h1 {
            font-size: 2.5rem;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }
        
        .terms-header .date {
            color: var(--text-secondary);
            font-style: italic;
        }
        
        .terms-content {
            color: var(--text-primary);
            line-height: 1.8;
        }
        
        .terms-content section {
            margin-bottom: 2.5rem;
        }
        
        .terms-content h2 {
            color: var(--accent-color);
            margin-bottom: 1rem;
            font-size: 1.8rem;
        }
        
        .terms-content p {
            margin-bottom: 1rem;
        }
        
        .terms-content ul, .terms-content ol {
            margin: 1rem 0 1rem 2rem;
        }
        
        .terms-content li {
            margin-bottom: 0.5rem;
        }
        
        .terms-content a {
            color: var(--link-color);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .terms-content a:hover {
            text-decoration: underline;
        }
        
        .terms-content blockquote {
            background-color: var(--bg-secondary);
            padding: 1.5rem;
            border-left: 4px solid var(--accent-color);
            margin: 1.5rem 0;
            border-radius: 4px;
        }
        
        .terms-content blockquote p:last-child {
            margin-bottom: 0;
        }
        
        .terms-footer {
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--border-color);
            text-align: center;
            color: var(--text-secondary);
        }
        
        .table-of-contents {
            background-color: var(--bg-secondary);
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        
        .table-of-contents h3 {
            margin-top: 0;
            margin-bottom: 1rem;
            color: var(--accent-color);
        }
        
        .table-of-contents ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }
        
        .table-of-contents li {
            margin-bottom: 0.5rem;
        }
        
        .table-of-contents a {
            text-decoration: none;
            color: var(--text-primary);
            transition: color 0.3s;
            display: block;
            padding: 0.3rem 0;
        }
        
        .table-of-contents a:hover {
            color: var(--accent-color);
        }
        
        @media (max-width: 768px) {
            .terms-container {
                padding: 1.5rem;
                margin: 1rem;
            }
            
            .terms-header h1 {
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
        <div class="terms-container">
            <div class="terms-header">
                <h1>Terms & Conditions</h1>
                <p class="date">Last Updated: <?php echo date('F j, Y'); ?></p>
            </div>

            <?php if (count($terms_sections) > 0): ?>
                <!-- Table of Contents -->
                <div class="table-of-contents">
                    <h3>Table of Contents</h3>
                    <ul>
                        <?php foreach ($terms_sections as $index => $section): ?>
                            <li>
                                <a href="#section-<?php echo $index + 1; ?>"><?php echo htmlspecialchars($section['title']); ?></a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <div class="terms-content">
                    <?php 
                    foreach ($terms_sections as $index => $section): 
                        $section_id = 'section-' . ($index + 1);
                    ?>
                        <section id="<?php echo $section_id; ?>">
                            <h2><?php echo htmlspecialchars($section['title']); ?></h2>
                            <?php echo $section['content']; // Already contains HTML formatting from the database ?>
                        </section>
                    <?php endforeach; ?>
                </div>
                
                <div class="terms-footer">
                    <p>
                        By using <?php echo htmlspecialchars($site_settings['site_name']); ?>, you agree to these terms and conditions. 
                        For any questions regarding our terms, please contact us.
                    </p>
                </div>
            <?php else: ?>
                <div class="terms-content">
                    <p>Our Terms & Conditions are currently being updated. Please check back soon.</p>
                </div>
            <?php endif; ?>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Smooth scroll for table of contents links
            const tocLinks = document.querySelectorAll('.table-of-contents a');
            
            for (let link of tocLinks) {
                link.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const targetId = this.getAttribute('href').substring(1);
                    const targetElement = document.getElementById(targetId);
                    
                    if (targetElement) {
                        // Scroll to the section
                        targetElement.scrollIntoView({
                            behavior: 'smooth'
                        });
                        
                        // Set focus to the section for accessibility
                        targetElement.setAttribute('tabindex', '-1');
                        targetElement.focus();
                    }
                });
            }
        });
    </script>
</body>
</html>