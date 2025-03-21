<?php
require_once(__DIR__ . '/../includes/init.php');
$stmt = $pdo->query("SELECT site_name, site_logo, dark_mode, language FROM site_settings WHERE id = 1");
$site_settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Get about content from database
$stmt = $pdo->query("SELECT title, content, image_url FROM about_content WHERE is_active = 1 ORDER BY sort_order ASC");
$about_sections = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get features for the features section
$stmt = $pdo->query("SELECT title, description, icon_class, icon_color FROM features WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 6");
$features = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?php echo $site_settings['language']; ?>" dir="<?php echo $site_settings['language'] === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - <?php echo $site_settings['site_name']; ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/dark-mode.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Cairo|Roboto&display=swap">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .about-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .about-hero {
            background-color: var(--bg-card);
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            margin-bottom: 3rem;
        }
        
        .about-hero-content {
            padding: 3rem;
            text-align: center;
        }
        
        .about-hero h1 {
            font-size: 2.5rem;
            color: var(--text-primary);
            margin-bottom: 1.5rem;
            position: relative;
            display: inline-block;
        }
        
        .about-hero h1:after {
            content: '';
            display: block;
            width: 100px;
            height: 4px;
            background: var(--accent-color);
            margin: 0.8rem auto 0;
            border-radius: 2px;
        }
        
        .about-hero p {
            font-size: 1.2rem;
            color: var(--text-secondary);
            max-width: 800px;
            margin: 0 auto;
            line-height: 1.8;
        }
        
        .about-section {
            display: flex;
            flex-direction: row;
            align-items: center;
            margin-bottom: 4rem;
            background-color: var(--bg-card);
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .about-section:nth-child(even) {
            flex-direction: row-reverse;
        }
        
        .about-section-content {
            flex: 1;
            padding: 3rem;
        }
        
        .about-section-image {
            flex: 1;
            min-height: 400px;
            background-size: cover;
            background-position: center;
            position: relative;
        }
        
        .about-section-image img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .about-section h2 {
            color: var(--accent-color);
            font-size: 2rem;
            margin-bottom: 1.5rem;
        }
        
        .about-section-content p {
            color: var(--text-primary);
            line-height: 1.8;
            margin-bottom: 1rem;
        }
        
        .features-section {
            padding: 4rem 0;
        }
        
        .features-section h2 {
            text-align: center;
            color: var(--text-primary);
            font-size: 2.2rem;
            margin-bottom: 3rem;
            position: relative;
        }
        
        .features-section h2:after {
            content: '';
            display: block;
            width: 100px;
            height: 4px;
            background: var(--accent-color);
            margin: 0.8rem auto 0;
            border-radius: 2px;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(330px, 1fr));
            gap: 2rem;
        }
        
        .feature-card {
            background-color: var(--bg-card);
            border-radius: 8px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
        }
        
        .feature-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 60px;
            height: 60px;
            background-color: var(--bg-secondary);
            color: var(--accent-color);
            border-radius: 50%;
            margin-bottom: 1.5rem;
            font-size: 1.5rem;
        }
        
        .feature-card h3 {
            color: var(--text-primary);
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }
        
        .feature-card p {
            color: var(--text-secondary);
            line-height: 1.6;
        }
        
        .team-section {
            padding: 4rem 0;
            text-align: center;
        }
        
        .team-section h2 {
            color: var(--text-primary);
            font-size: 2.2rem;
            margin-bottom: 3rem;
            position: relative;
        }
        
        .team-section h2:after {
            content: '';
            display: block;
            width: 100px;
            height: 4px;
            background: var(--accent-color);
            margin: 0.8rem auto 0;
            border-radius: 2px;
        }
        
        .call-to-action {
            background-color: var(--accent-color);
            padding: 4rem 2rem;
            text-align: center;
            border-radius: 8px;
            margin-top: 3rem;
            margin-bottom: 3rem;
        }
        
        .call-to-action h2 {
            color: #ffffff;
            font-size: 2.2rem;
            margin-bottom: 1.5rem;
        }
        
        .call-to-action p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 1.2rem;
            max-width: 800px;
            margin: 0 auto 2rem;
        }
        
        .cta-button {
            display: inline-block;
            background-color: #ffffff;
            color: var(--accent-color);
            padding: 0.75rem 2rem;
            border-radius: 4px;
            font-weight: 700;
            text-decoration: none;
            transition: background-color 0.3s, transform 0.3s;
        }
        
        .cta-button:hover {
            background-color: rgba(255, 255, 255, 0.9);
            transform: translateY(-2px);
        }
        
        /* Dark mode adjustments handled via CSS variables */
        
        @media (max-width: 992px) {
            .about-section, .about-section:nth-child(even) {
                flex-direction: column;
            }
            
            .about-section-image {
                width: 100%;
                height: 300px;
                min-height: auto;
            }
            
            .about-section-content {
                padding: 2rem;
            }
        }
        
        @media (max-width: 768px) {
            .about-hero-content {
                padding: 2rem;
            }
            
            .about-hero h1 {
                font-size: 2rem;
            }
            
            .features-grid {
                grid-template-columns: 1fr;
            }
            
            .call-to-action {
                padding: 3rem 1.5rem;
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
        <div class="about-container">
            <!-- Hero Section -->
            <div class="about-hero">
                <div class="about-hero-content">
                    <h1>About <?php echo htmlspecialchars($site_settings['site_name']); ?></h1>
                    <p>Discover our story, mission, and the team behind <?php echo htmlspecialchars($site_settings['site_name']); ?>, 
                    your premier destination for breathtaking wallpapers and media.</p>
                </div>
            </div>

            <!-- About Sections from Database -->
            <?php if (count($about_sections) > 0): ?>
                <?php foreach ($about_sections as $index => $section): ?>
                    <div class="about-section">
                        <div class="about-section-content">
                            <h2><?php echo htmlspecialchars($section['title']); ?></h2>
                            <?php echo $section['content']; // Already contains HTML formatting from the database ?>
                        </div>
                        <?php if (!empty($section['image_url'])): ?>
                            <div class="about-section-image">
                                <img src="<?php echo htmlspecialchars($section['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($section['title']); ?>" 
                                     loading="lazy">
                            </div>
                        <?php else: ?>
                            <div class="about-section-image" style="background-image: url('/assets/images/default-about.jpg');"></div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="about-section">
                    <div class="about-section-content">
                        <h2>Our Story</h2>
                        <p>Content is currently being updated. Please check back soon for more information about us.</p>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Features Section -->
            <?php if (count($features) > 0): ?>
                <div class="features-section">
                    <h2>Our Features</h2>
                    <div class="features-grid">
                        <?php foreach ($features as $feature): ?>
                            <div class="feature-card">
                                <div class="feature-icon">
                                    <?php if (!empty($feature['icon_class'])): ?>
                                        <i class="<?php echo htmlspecialchars($feature['icon_class']); ?>" 
                                           style="color: <?php echo htmlspecialchars($feature['icon_color']); ?>;"></i>
                                    <?php else: ?>
                                        <i class="fas fa-star"></i>
                                    <?php endif; ?>
                                </div>
                                <h3><?php echo htmlspecialchars($feature['title']); ?></h3>
                                <?php echo $feature['description']; // Already contains HTML formatting ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Call to Action -->
            <div class="call-to-action">
                <h2>Ready to Explore?</h2>
                <p>Discover our extensive collection of high-quality wallpapers, backgrounds, and media designed exclusively for you.</p>
                <a href="/" class="cta-button">Browse Our Collection</a>
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
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Add fade-in animation for sections when they come into view
            const aboutSections = document.querySelectorAll('.about-section, .feature-card');
            
            const observerOptions = {
                root: null,
                rootMargin: '0px',
                threshold: 0.15
            };
            
            const observer = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = 1;
                        entry.target.style.transform = 'translateY(0)';
                        observer.unobserve(entry.target);
                    }
                });
            }, observerOptions);
            
            aboutSections.forEach(section => {
                section.style.opacity = 0;
                section.style.transform = 'translateY(20px)';
                section.style.transition = 'opacity 0.6s ease-out, transform 0.6s ease-out';
                observer.observe(section);
            });
        });
    </script>
</body>
</html>