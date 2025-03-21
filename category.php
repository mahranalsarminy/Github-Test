<?php
require_once(__DIR__ . '/includes/init.php');

// Get site settings for dark mode and language
$stmt = $pdo->query("SELECT site_name, site_logo, dark_mode, language FROM site_settings WHERE id = 1");
$site_settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Get category by slug
$slug = filter_var($_GET['slug'] ?? '', FILTER_SANITIZE_STRING);
if (empty($slug)) {
    header('Location: /404.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM categories WHERE slug = ?");
$stmt->execute([$slug]);
$category = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$category) {
    header('Location: /404.php');
    exit;
}

// Get filter parameters
$type = filter_var($_GET['type'] ?? 'all', FILTER_SANITIZE_STRING);
$resolution = filter_var($_GET['resolution'] ?? 'all', FILTER_SANITIZE_STRING);
$orientation = filter_var($_GET['orientation'] ?? 'all', FILTER_SANITIZE_STRING);
$view = filter_var($_GET['view'] ?? 'grid', FILTER_SANITIZE_STRING);
$sort = filter_var($_GET['sort'] ?? 'latest', FILTER_SANITIZE_STRING);

// Build the media query with AI-enhanced filtering
$mediaQuery = "
    SELECT m.*, 
           COALESCE(m.ai_description, m.description) as enhanced_description,
           m.content_quality_score,
           m.engagement_score,
           COUNT(DISTINCT mv.id) as view_count,
           COUNT(DISTINCT md.id) as download_count
    FROM media m
    LEFT JOIN media_views mv ON m.id = mv.media_id
    LEFT JOIN media_downloads md ON m.id = md.media_id
    WHERE m.category_id = :category_id
    AND m.status = 1
";

// Apply filters
if ($type !== 'all') {
    $mediaQuery .= " AND m.file_type " . ($type === 'video' ? "LIKE 'video/%'" : "LIKE 'image/%'");
}

if ($orientation !== 'all') {
    $mediaQuery .= " AND m.orientation = :orientation";
}

if ($resolution !== 'all') {
    $mediaQuery .= " AND m.width || 'x' || m.height = :resolution";
}

// Group and sort
$mediaQuery .= " GROUP BY m.id ";

switch ($sort) {
    case 'popular':
        $mediaQuery .= " ORDER BY (view_count + download_count) DESC";
        break;
    case 'quality':
        $mediaQuery .= " ORDER BY (content_quality_score + engagement_score) DESC";
        break;
    default:
        $mediaQuery .= " ORDER BY m.created_at DESC";
}

$stmt = $pdo->prepare($mediaQuery);
$params = ['category_id' => $category['id']];

if ($orientation !== 'all') {
    $params['orientation'] = $orientation;
}
if ($resolution !== 'all') {
    $params['resolution'] = $resolution;
}

$stmt->execute($params);
$media_items = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="<?php echo $site_settings['language']; ?>" 
      dir="<?php echo $site_settings['language'] === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($category['name']); ?> - <?php echo htmlspecialchars($site_settings['site_name']); ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/dark-mode.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Cairo|Roboto&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Add custom styles for category page */
        .filters {
            background: var(--bg-secondary);
            padding: 1rem;
            border-radius: 8px;
            margin: 1rem 0;
        }
        
        .view-controls {
            display: flex;
            gap: 1rem;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .media-grid {
            display: grid;
            gap: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .media-grid.grid-view {
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        }
        
        .media-grid.list-view {
            grid-template-columns: 1fr;
        }
        
        .media-item {
            position: relative;
            border-radius: 8px;
            overflow: hidden;
            background: var(--bg-secondary);
            transition: transform 0.3s ease;
        }
        
        .media-item:hover {
            transform: translateY(-5px);
        }
        
        .media-content {
            position: relative;
            padding-top: 56.25%; /* 16:9 Aspect Ratio */
        }
        
        .media-content img,
        .media-content video {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .media-info {
            padding: 1rem;
            background: rgba(var(--bg-secondary-rgb), 0.9);
        }
        
        .ai-enhanced {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(var(--primary-rgb), 0.9);
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            color: var(--text-light);
            font-size: 0.8rem;
        }
    </style>
</head>
<body class="<?php echo $site_settings['dark_mode'] ? 'dark-mode' : 'light-mode'; ?>">
    <!-- Skip to main content link for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <?php include 'theme/homepage/header.php'; ?>
    
    <!-- Search Box Section -->
    <?php include 'theme/homepage/search-box.php'; ?>

    <main id="main-content" role="main" class="container mx-auto px-4 py-8">
        <div class="category-header">
            <h1 class="text-3xl font-bold mb-4"><?php echo htmlspecialchars($category['name']); ?></h1>
            <?php if ($category['description']): ?>
                <p class="text-gray-600 dark:text-gray-300 mb-6">
                    <?php echo htmlspecialchars($category['description']); ?>
                </p>
            <?php endif; ?>
        </div>

        <!-- Filters Section -->
        <div class="filters">
            <form id="filterForm" class="flex flex-wrap gap-4">
                <input type="hidden" name="slug" value="<?php echo htmlspecialchars($slug); ?>">
                
                <div class="filter-group">
                    <label for="type">Content Type:</label>
                    <select name="type" id="type" class="filter-select">
                        <option value="all" <?php echo $type === 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="image" <?php echo $type === 'image' ? 'selected' : ''; ?>>Images</option>
                        <option value="video" <?php echo $type === 'video' ? 'selected' : ''; ?>>Videos</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="resolution">Resolution:</label>
                    <select name="resolution" id="resolution" class="filter-select">
                        <option value="all">All Resolutions</option>
                        <?php
                        $resolutions = $pdo->query("SELECT DISTINCT resolution FROM resolutions ORDER BY resolution")->fetchAll();
                        foreach ($resolutions as $res) {
                            $selected = ($resolution === $res['resolution']) ? 'selected' : '';
                            echo "<option value='{$res['resolution']}' {$selected}>{$res['resolution']}</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="orientation">Orientation:</label>
                    <select name="orientation" id="orientation" class="filter-select">
                        <option value="all" <?php echo $orientation === 'all' ? 'selected' : ''; ?>>All</option>
                        <option value="portrait" <?php echo $orientation === 'portrait' ? 'selected' : ''; ?>>Portrait</option>
                        <option value="landscape" <?php echo $orientation === 'landscape' ? 'selected' : ''; ?>>Landscape</option>
                    </select>
                </div>

                <div class="filter-group">
                    <label for="sort">Sort By:</label>
                    <select name="sort" id="sort" class="filter-select">
                        <option value="latest" <?php echo $sort === 'latest' ? 'selected' : ''; ?>>Latest</option>
                        <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
                        <option value="quality" <?php echo $sort === 'quality' ? 'selected' : ''; ?>>Highest Quality</option>
                    </select>
                </div>
            </form>
        </div>

        <!-- View Controls -->
        <div class="view-controls">
            <button class="view-btn" data-view="grid" aria-label="Grid view">
                <i class="fas fa-th-large"></i>
            </button>
            <button class="view-btn" data-view="list" aria-label="List view">
                <i class="fas fa-list"></i>
            </button>
        </div>

        <!-- Media Grid -->
        <div class="media-grid <?php echo $view === 'grid' ? 'grid-view' : 'list-view'; ?>">
            <?php foreach ($media_items as $item): ?>
                <article class="media-item" data-type="<?php echo strpos($item['file_type'], 'video/') === 0 ? 'video' : 'image'; ?>">
                    <div class="media-content">
                        <?php if (strpos($item['file_type'], 'video/') === 0): ?>
                            <?php if ($item['file_path']): ?>
                                <video controls poster="<?php echo htmlspecialchars($item['thumbnail_url']); ?>">
                                    <source src="<?php echo htmlspecialchars($item['file_path']); ?>" type="<?php echo htmlspecialchars($item['file_type']); ?>">
                                    Your browser does not support the video tag.
                                </video>
                            <?php else: ?>
                                <div class="external-video" data-url="<?php echo htmlspecialchars($item['external_url']); ?>">
                                    <img src="<?php echo htmlspecialchars($item['thumbnail_url']); ?>" alt="Video thumbnail">
                                    <button class="play-btn" aria-label="Play video">
                                        <i class="fas fa-play"></i>
                                    </button>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <?php if ($item['file_path']): ?>
                                <img src="<?php echo htmlspecialchars($item['file_path']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['title']); ?>"
                                     loading="lazy">
                            <?php else: ?>
                                <img src="<?php echo htmlspecialchars($item['external_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($item['title']); ?>"
                                     loading="lazy">
                            <?php endif; ?>
                        <?php endif; ?>
                        
                        <?php if ($item['ai_enhanced']): ?>
                            <span class="ai-enhanced">AI Enhanced</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="media-info">
                        <h2 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($item['title']); ?></h2>
                        <p class="text-sm text-gray-600 dark:text-gray-300">
                            <?php echo htmlspecialchars($item['enhanced_description']); ?>
                        </p>
                        <div class="media-stats flex gap-4 mt-2 text-sm">
                            <span><i class="fas fa-eye"></i> <?php echo number_format($item['view_count']); ?></span>
                            <span><i class="fas fa-download"></i> <?php echo number_format($item['download_count']); ?></span>
                            <?php if ($item['content_quality_score'] > 0.7): ?>
                                <span class="quality-badge">
                                    <i class="fas fa-star"></i> High Quality
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </main>

    <?php include 'theme/homepage/footer.php'; ?>

    <!-- Accessibility Menu -->
    <?php include 'theme/homepage/accessibility.php'; ?>

    <!-- Scripts -->
    <script src="/assets/js/scripts.js"></script>
    <script src="/assets/js/accessibility.js"></script>
    <script>
        // Filter form handling
        document.getElementById('filterForm').querySelectorAll('select').forEach(select => {
            select.addEventListener('change', () => {
                document.getElementById('filterForm').submit();
            });
        });

        // View controls
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                const view = btn.dataset.view;
                const grid = document.querySelector('.media-grid');
                grid.className = `media-grid ${view}-view`;
                
                // Update URL without page reload
                const url = new URL(window.location);
                url.searchParams.set('view', view);
                window.history.pushState({}, '', url);
            });
        });

        // External video handling
        document.querySelectorAll('.external-video').forEach(video => {
            const playBtn = video.querySelector('.play-btn');
            playBtn?.addEventListener('click', () => {
                const url = video.dataset.url;
                // Handle different video platforms (YouTube, Vimeo, etc.)
                // Implement video player loading logic here
            });
        });

        // Lazy loading for images
        if ('loading' in HTMLImageElement.prototype) {
            document.querySelectorAll('img[loading="lazy"]').forEach(img => {
                img.style.opacity = 0;
                img.addEventListener('load', () => {
                    img.style.transition = 'opacity 0.3s ease';
                    img.style.opacity = 1;
                });
            });
        }

        // Intersection Observer for lazy loading and view tracking
        const observerOptions = {
            root: null,
            rootMargin: '50px',
            threshold: 0.1
        };

        const handleIntersection = (entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const mediaItem = entry.target;
                    
                    // Track view if not already tracked
                    if (!mediaItem.dataset.viewed) {
                        trackMediaView(mediaItem.dataset.mediaId);
                        mediaItem.dataset.viewed = 'true';
                    }

                    // Load high-quality version of image if available
                    const img = mediaItem.querySelector('img[data-high-quality]');
                    if (img && !img.dataset.loaded) {
                        img.src = img.dataset.highQuality;
                        img.dataset.loaded = 'true';
                    }
                }
            });
        };

        const observer = new IntersectionObserver(handleIntersection, observerOptions);
        document.querySelectorAll('.media-item').forEach(item => observer.observe(item));

        // Media view tracking
        async function trackMediaView(mediaId) {
            try {
                const response = await fetch('/api/track-view.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ mediaId })
                });
                
                if (!response.ok) {
                    throw new Error('Failed to track view');
                }
            } catch (error) {
                console.error('Error tracking media view:', error);
            }
        }

        // Dark mode handling
        const darkModeToggle = document.querySelector('#dark-mode-toggle');
        if (darkModeToggle) {
            darkModeToggle.addEventListener('click', () => {
                document.body.classList.toggle('dark-mode');
                
                // Save preference to server
                fetch('/api/update-preferences.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        dark_mode: document.body.classList.contains('dark-mode')
                    })
                });
            });
        }

        // Accessibility features
        function initializeAccessibilityFeatures() {
            // Font size controls
            const fontSizeControls = document.querySelectorAll('[data-font-size]');
            fontSizeControls.forEach(control => {
                control.addEventListener('click', () => {
                    const size = control.dataset.fontSize;
                    document.documentElement.style.fontSize = size;
                    localStorage.setItem('preferred-font-size', size);
                });
            });

            // High contrast mode
            const highContrastToggle = document.querySelector('#high-contrast-toggle');
            if (highContrastToggle) {
                highContrastToggle.addEventListener('click', () => {
                    document.body.classList.toggle('high-contrast');
                    localStorage.setItem('high-contrast', 
                        document.body.classList.contains('high-contrast'));
                });
            }

            // Focus indicators
            document.addEventListener('keydown', (e) => {
                if (e.key === 'Tab') {
                    document.body.classList.add('keyboard-navigation');
                }
            });

            document.addEventListener('mousedown', () => {
                document.body.classList.remove('keyboard-navigation');
            });
        }

        // Initialize features
        initializeAccessibilityFeatures();

        // Media filters animation
        const filterGroups = document.querySelectorAll('.filter-group');
        filterGroups.forEach(group => {
            group.addEventListener('change', (e) => {
                const select = e.target;
                select.classList.add('filter-changed');
                setTimeout(() => select.classList.remove('filter-changed'), 300);
            });
        });

        // Handle resolution-based image loading
        function loadAppropriateImage(img) {
            const devicePixelRatio = window.devicePixelRatio || 1;
            const viewportWidth = window.innerWidth;
            
            let appropriateUrl = img.dataset.src; // Default source

            if (devicePixelRatio > 1 && viewportWidth > 1024) {
                appropriateUrl = img.dataset.highRes || appropriateUrl;
            } else if (viewportWidth < 768) {
                appropriateUrl = img.dataset.lowRes || appropriateUrl;
            }

            img.src = appropriateUrl;
        }

        // Apply resolution-based loading to all images
        document.querySelectorAll('img[data-src]').forEach(loadAppropriateImage);

        // Reapply on window resize
        let resizeTimeout;
        window.addEventListener('resize', () => {
            clearTimeout(resizeTimeout);
            resizeTimeout = setTimeout(() => {
                document.querySelectorAll('img[data-src]').forEach(loadAppropriateImage);
            }, 250);
        });
    </script>
</body>
</html>        