<?php
require_once(__DIR__ . '/includes/init.php');
session_start();

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Fetch site settings
$stmt = $pdo->query("SELECT site_name, site_logo, dark_mode, language FROM site_settings WHERE id = 1");
$site_settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Get user data
$user_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    // Session exists but user doesn't - force logout
    session_destroy();
    header('Location: login.php');
    exit;
}

// Safe echo function
function safe_echo($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Handle pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$per_page = 12;
$offset = ($page - 1) * $per_page;

// Handle filtering
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$valid_filters = ['all', 'week', 'month', 'year'];
if (!in_array($filter, $valid_filters)) {
    $filter = 'all';
}

// Build filter query
$date_filter = '';
if ($filter === 'week') {
    $date_filter = "AND d.download_date >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
} elseif ($filter === 'month') {
    $date_filter = "AND d.download_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
} elseif ($filter === 'year') {
    $date_filter = "AND d.download_date >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
}

// Get downloads count for pagination
$stmt = $pdo->prepare("SELECT COUNT(*) FROM downloads d WHERE d.user_id = ? $date_filter");
$stmt->execute([$user_id]);
$total_downloads = $stmt->fetchColumn();
$total_pages = ceil($total_downloads / $per_page);

// Get downloads with pagination
$downloads = [];
$stmt = $pdo->prepare("SELECT w.*, d.download_date, d.download_type, c.name AS category_name
                      FROM downloads d
                      JOIN wallpapers w ON d.wallpaper_id = w.id
                      LEFT JOIN categories c ON w.category_id = c.id
                      WHERE d.user_id = ? $date_filter
                      ORDER BY d.download_date DESC
                      LIMIT ? OFFSET ?");
$stmt->execute([$user_id, $per_page, $offset]);
$downloads = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get download statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total_count FROM downloads WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_count = $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COUNT(*) as month_count FROM downloads WHERE user_id = ? AND download_date >= DATE_SUB(NOW(), INTERVAL 1 MONTH)");
$stmt->execute([$user_id]);
$month_count = $stmt->fetchColumn();

// Get most downloaded category
$stmt = $pdo->prepare("SELECT c.name, COUNT(*) as count 
                      FROM downloads d
                      JOIN wallpapers w ON d.wallpaper_id = w.id
                      JOIN categories c ON w.category_id = c.id
                      WHERE d.user_id = ?
                      GROUP BY c.id
                      ORDER BY count DESC
                      LIMIT 1");
$stmt->execute([$user_id]);
$favorite_category = $stmt->fetch(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="<?php echo $site_settings['language']; ?>" dir="<?php echo $site_settings['language'] === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Downloads - <?php echo safe_echo($site_settings['site_name']); ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/dark-mode.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Cairo|Roboto&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .page-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .page-header {
            background-color: var(--bg-card);
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        
        .header-content {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        
        @media (min-width: 768px) {
            .header-content {
                flex-direction: row;
                justify-content: space-between;
            }
        }
        
        .header-title h1 {
            font-size: 1.8rem;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .header-title p {
            color: var(--text-secondary);
            max-width: 600px;
        }
        
        .header-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }
        
        .stat-card {
            background-color: var(--bg-secondary);
            padding: 1rem;
            border-radius: 8px;
            min-width: 150px;
            text-align: center;
        }
        
        .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--accent-color);
        }
        
        .stat-label {
            font-size: 0.9rem;
            color: var(--text-secondary);
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
        <div class="page-container">
            <!-- Breadcrumbs -->
            <div class="breadcrumbs" style="margin-bottom: 1.5rem;">
                <a href="index.php">Home <i class="fas fa-chevron-right"></i></a>
                <a href="profile.php">My Profile <i class="fas fa-chevron-right"></i></a>
                <span class="current">My Downloads</span>
            </div>
            
            <div class="page-header">
                <div class="header-content">
                    <div class="header-title">
                        <h1>My Downloads</h1>
                        <p>Track and manage your downloaded wallpapers.</p>
                    </div>
                    <div class="header-stats">
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $total_count; ?></div>
                            <div class="stat-label">Total Downloads</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo $month_count; ?></div>
                            <div class="stat-label">This Month</div>
                        </div>
                        <?php if ($favorite_category): ?>
                        <div class="stat-card">
                            <div class="stat-value"><?php echo safe_echo($favorite_category['name']); ?></div>
                            <div class="stat-label">Favorite Category</div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Filter and Actions Bar -->
            <div style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 1rem; margin-bottom: 1.5rem;">
                <div class="filter-options">
                    <div style="display: flex; gap: 0.5rem;">
                        <a href="?filter=all" class="btn <?php echo $filter === 'all' ? 'btn-primary' : 'btn-secondary'; ?>">All Time</a>
                        <a href="?filter=week" class="btn <?php echo $filter === 'week' ? 'btn-primary' : 'btn-secondary'; ?>">This Week</a>
                        <a href="?filter=month" class="btn <?php echo $filter === 'month' ? 'btn-primary' : 'btn-secondary'; ?>">This Month</a>
                        <a href="?filter=year" class="btn <?php echo $filter === 'year' ? 'btn-primary' : 'btn-secondary'; ?>">This Year</a>
                    </div>
                </div>
                
                <a href="profile.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Profile
                </a>
            </div>
            <?php if (count($downloads) > 0): ?>
                <!-- Downloads Grid -->
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 1.5rem;">
                    <?php foreach ($downloads as $download): ?>
                        <div class="card" style="background-color: var(--bg-card); border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); transition: transform 0.3s ease, box-shadow 0.3s ease; display: flex; flex-direction: column;">
                            <div style="position: relative; padding-top: 75%; overflow: hidden;">
                                <img src="<?php echo safe_echo($download['thumbnail_url']); ?>" alt="<?php echo safe_echo($download['title']); ?>" style="position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; transition: transform 0.5s ease;">
                                <div style="position: absolute; bottom: 0; left: 0; right: 0; padding: 0.5rem; background: linear-gradient(transparent, rgba(0, 0, 0, 0.7)); color: white;">
                                    <div style="display: inline-block; background-color: var(--accent-color); padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.75rem;"><?php echo safe_echo($download['download_type']); ?></div>
                                </div>
                            </div>
                            <div style="padding: 1.2rem; flex-grow: 1; display: flex; flex-direction: column;">
                                <h3 style="font-size: 1.1rem; font-weight: 600; color: var(--text-primary); margin-bottom: 0.5rem;"><?php echo safe_echo($download['title']); ?></h3>
                                
                                <div style="margin-bottom: 0.75rem; color: var(--text-secondary); font-size: 0.9rem;">
                                    <p><i class="far fa-clock"></i> Downloaded: <?php echo date('M j, Y', strtotime($download['download_date'])); ?></p>
                                    <p><i class="fas fa-th"></i> Category: <?php echo safe_echo($download['category_name'] ?? 'General'); ?></p>
                                </div>
                                
                                <div style="margin-top: auto; display: flex; gap: 0.5rem;">
                                    <a href="wallpaper.php?id=<?php echo $download['id']; ?>" class="btn btn-secondary" style="flex: 1; text-align: center;">View</a>
                                    <a href="download.php?id=<?php echo $download['id']; ?>&type=<?php echo urlencode($download['download_type']); ?>" class="btn btn-primary" style="flex: 1; text-align: center;">Download Again</a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div style="display: flex; justify-content: center; gap: 0.5rem; margin-top: 2rem;">
                    <?php if ($page > 1): ?>
                        <a href="?filter=<?php echo $filter; ?>&page=1" class="btn btn-secondary">First</a>
                        <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page - 1; ?>" class="btn btn-secondary">Previous</a>
                    <?php endif; ?>
                    
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    
                    for ($i = $start; $i <= $end; $i++): ?>
                        <a href="?filter=<?php echo $filter; ?>&page=<?php echo $i; ?>" class="btn <?php echo ($i === $page) ? 'btn-primary' : 'btn-secondary'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page + 1; ?>" class="btn btn-secondary">Next</a>
                        <a href="?filter=<?php echo $filter; ?>&page=<?php echo $total_pages; ?>" class="btn btn-secondary">Last</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            <?php else: ?>
                <!-- Empty State -->
                <div style="text-align: center; padding: 3rem 1rem; background-color: var(--bg-card); border-radius: 8px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                    <i class="fas fa-download" style="font-size: 3rem; color: var(--text-secondary); margin-bottom: 1rem; opacity: 0.5;"></i>
                    <h2 style="font-size: 1.5rem; color: var(--text-primary); margin-bottom: 1rem;">No Downloads Yet</h2>
                    <p style="color: var(--text-secondary); max-width: 500px; margin: 0 auto 1.5rem;">You haven't downloaded any wallpapers yet. Browse our collection and download wallpapers to see them here.</p>
                    <a href="index.php" class="btn btn-primary">Browse Wallpapers</a>
                </div>
            <?php endif; ?>
            
            <!-- User tips section -->
            <div style="margin-top: 2rem; background-color: var(--bg-card); border-radius: 8px; padding: 1.5rem; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                <h3 style="color: var(--text-primary); margin-bottom: 0.5rem;">Pro Tips</h3>
                <ul style="list-style-type: none; padding: 0; color: var(--text-secondary);">
                    <li style="padding: 0.5rem 0; border-bottom: 1px solid var(--border-color);"><i class="fas fa-info-circle" style="color: var(--accent-color); margin-right: 0.5rem;"></i> Downloads are tracked when you use the download button on wallpaper pages.</li>
                    <li style="padding: 0.5rem 0; border-bottom: 1px solid var(--border-color);"><i class="fas fa-info-circle" style="color: var(--accent-color); margin-right: 0.5rem;"></i> <?php echo ($user['subscription_plan'] !== 'free' ? 'As a premium member, you have unlimited downloads.' : 'Free users are limited to 5 downloads per day.'); ?></li>
                    <li style="padding: 0.5rem 0;"><i class="fas fa-info-circle" style="color: var(--accent-color); margin-right: 0.5rem;"></i> Downloaded wallpapers are available in your profile for easy access.</li>
                </ul>
            </div>
            
            <!-- Last updated indicator -->
            <p style="text-align: center; margin-top: 2rem; color: var(--text-secondary); font-size: 0.8rem;">
                Last updated: 2025-03-20 06:26:54 UTC | User: mahranalsarminy
            </p>
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
        document.addEventListener('DOMContentLoaded', function() {
            // Add hover effects for cards
            document.querySelectorAll('.card').forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                    this.style.boxShadow = '0 12px 20px rgba(0, 0, 0, 0.15)';
                    const img = this.querySelector('img');
                    if (img) img.style.transform = 'scale(1.05)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '0 4px 6px rgba(0, 0, 0, 0.1)';
                    const img = this.querySelector('img');
                    if (img) img.style.transform = 'scale(1)';
                });
            });
            
            // Track downloads
            document.querySelectorAll('a[href^="download.php"]').forEach(link => {
                link.addEventListener('click', function() {
                    // You could add analytics tracking here
                    console.log('Download clicked:', this.href);
                });
            });
        });
    </script>
</body>
</html>                