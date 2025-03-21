<?php
require_once(__DIR__ . '/includes/init.php');

error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);

// Get and sanitize parameters
$view_type = isset($_GET['view']) ? htmlspecialchars($_GET['view']) : 'grid';
$media_type = isset($_GET['type']) ? htmlspecialchars($_GET['type']) : 'all';
$orientation = isset($_GET['orientation']) ? htmlspecialchars($_GET['orientation']) : 'all';
$resolution = isset($_GET['resolution']) ? htmlspecialchars($_GET['resolution']) : 'all';
$sort = isset($_GET['sort']) ? htmlspecialchars($_GET['sort']) : 'latest';
$search = isset($_GET['search']) ? htmlspecialchars($_GET['search']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 24;

// Get site settings
$stmt = $pdo->query("SELECT site_name, site_logo, dark_mode, language FROM site_settings WHERE id = 1");
$site_settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Build base query
$query = "SELECT DISTINCT 
            m.*, 
            GROUP_CONCAT(DISTINCT t.name) as tags,
            GROUP_CONCAT(DISTINCT t.slug) as tag_slugs
          FROM media m 
          LEFT JOIN media_tags mt ON m.id = mt.media_id 
          LEFT JOIN tags t ON mt.tag_id = t.id 
          WHERE m.status = 1";

$params = [];

// Apply filters
if ($media_type !== 'all') {
    if ($media_type === 'images') {
        $query .= " AND m.file_type LIKE 'image%'";
    } elseif ($media_type === 'videos') {
        $query .= " AND m.file_type LIKE 'video%'";
    }
}

if ($orientation !== 'all') {
    $query .= " AND m.orientation = ?";
    $params[] = $orientation;
}

if ($resolution !== 'all') {
    $query .= " AND CONCAT(m.width, 'x', m.height) = ?";
    $params[] = $resolution;
}

if ($search) {
    $query .= " AND (m.title LIKE ? OR m.description LIKE ? OR t.name LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Group by to handle the GROUP_CONCAT
$query .= " GROUP BY m.id";

// Apply sorting
switch ($sort) {
    case 'oldest':
        $query .= " ORDER BY m.created_at ASC";
        break;
    case 'title':
        $query .= " ORDER BY m.title ASC";
        break;
    case 'featured':
        $query .= " ORDER BY m.featured DESC, m.created_at DESC";
        break;
    default: // latest
        $query .= " ORDER BY m.created_at DESC";
}
// Get total count for pagination
$count_query = "SELECT COUNT(DISTINCT m.id) FROM media m 
                LEFT JOIN media_tags mt ON m.id = mt.media_id 
                LEFT JOIN tags t ON mt.tag_id = t.id 
                WHERE m.status = 1";

// Apply the same filters to the count query
if ($media_type !== 'all') {
    if ($media_type === 'images') {
        $count_query .= " AND m.file_type LIKE 'image%'";
    } elseif ($media_type === 'videos') {
        $count_query .= " AND m.file_type LIKE 'video%'";
    }
}

if ($orientation !== 'all') {
    $count_query .= " AND m.orientation = ?";
}

if ($resolution !== 'all') {
    $count_query .= " AND CONCAT(m.width, 'x', m.height) = ?";
}

if ($search) {
    $count_query .= " AND (m.title LIKE ? OR m.description LIKE ? OR t.name LIKE ?)";
}

$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_items = $count_stmt->fetchColumn();
$total_pages = ceil($total_items / $per_page);

// Add pagination
$query .= " LIMIT ? OFFSET ?";
$params[] = $per_page;
$params[] = ($page - 1) * $per_page;

// Execute main query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$media_items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get available resolutions
$stmt = $pdo->query("SELECT resolution FROM resolutions ORDER BY resolution");
$resolutions = $stmt->fetchAll(PDO::FETCH_COLUMN);

?>
<!DOCTYPE html>
<html lang="<?php echo $site_settings['language']; ?>" 
      dir="<?php echo $site_settings['language'] === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gallery - <?php echo htmlspecialchars($site_settings['site_name']); ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/dark-mode.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Cairo|Roboto&display=swap">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    
    <style>
        .gallery-container {
            max-width: 1920px;
            margin: 0 auto;
            padding: 2rem;
        }
        
        .filters-container {
            background: var(--bg-secondary);
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin-bottom: 2rem;
        }
        
        .media-grid {
            display: grid;
            gap: 1.5rem;
            transition: all 0.3s ease;
        }
        
        .media-grid.grid-view { grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)); }
        .media-grid.vertical-view { grid-template-columns: 1fr; }
        .media-grid.horizontal-view { grid-template-columns: repeat(2, 1fr); }
        .media-grid.cards-view { grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); }
        
        .media-item {
            position: relative;
            border-radius: 0.5rem;
            overflow: hidden;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }
        
        .media-item:hover {
            transform: translateY(-5px);
        }
        
        .media-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            padding: 1rem;
            background: linear-gradient(transparent, rgba(0,0,0,0.8));
            color: white;
        }
        
        .media-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }
        
        .tag {
            background: rgba(255,255,255,0.2);
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
        }
        
        .paid-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(0,0,0,0.7);
            color: #ffd700;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
            font-size: 0.75rem;
        }
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

    <main class="gallery-container">
        <div class="filters-container">
            <form id="filters-form" class="grid grid-cols-1 md:grid-cols-6 gap-4">
                <div class="md:col-span-2">
                    <input type="text" name="search" placeholder="Search..." 
                           value="<?php echo htmlspecialchars($search); ?>" 
                           class="w-full rounded-md border-gray-300">
                </div>
                
                <select name="type" class="form-select">
                    <option value="all" <?php echo $media_type === 'all' ? 'selected' : ''; ?>>All Media</option>
                    <option value="images" <?php echo $media_type === 'images' ? 'selected' : ''; ?>>Images</option>
                    <option value="videos" <?php echo $media_type === 'videos' ? 'selected' : ''; ?>>Videos</option>
                </select>

                <select name="orientation" class="form-select">
                    <option value="all" <?php echo $orientation === 'all' ? 'selected' : ''; ?>>All Orientations</option>
                    <option value="portrait" <?php echo $orientation === 'portrait' ? 'selected' : ''; ?>>Portrait</option>
                    <option value="landscape" <?php echo $orientation === 'landscape' ? 'selected' : ''; ?>>Landscape</option>
                </select>

                <select name="resolution" class="form-select">
                    <option value="all" <?php echo $resolution === 'all' ? 'selected' : ''; ?>>All Resolutions</option>
                    <?php foreach ($resolutions as $res): ?>
                        <option value="<?php echo htmlspecialchars($res); ?>" 
                                <?php echo $resolution === $res ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($res); ?>
                        </option>
                    <?php endforeach; ?>
                </select>

                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-filter"></i> Apply Filters
                </button>
            </form>

            <div class="view-options mt-4 flex gap-2">
                <?php foreach(['grid', 'vertical', 'horizontal', 'cards'] as $type): ?>
                    <button class="btn <?php echo $view_type === $type ? 'btn-primary' : 'btn-secondary'; ?>"
                            data-view="<?php echo $type; ?>">
                        <i class="fas fa-<?php echo $type === 'grid' ? 'th' : 
                                              ($type === 'vertical' ? 'bars' : 
                                               ($type === 'horizontal' ? 'th-large' : 'id-card')); ?>"></i>
                        <?php echo ucfirst($type); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="media-grid <?php echo $view_type; ?>-view" id="media-container">
            <?php foreach ($media_items as $item): ?>
                <div class="media-item">
                    <?php if (strpos($item['file_type'], 'video') === 0): ?>
                        <video class="w-full h-full object-cover" poster="<?php echo htmlspecialchars($item['thumbnail_url']); ?>">
                            <source src="<?php echo htmlspecialchars($item['file_path']); ?>" 
                                    type="<?php echo htmlspecialchars($item['file_type']); ?>">
                        </video>
                        <div class="video-badge">
                            <i class="fas fa-play"></i>
                        </div>
                    <?php else: ?>
                        <img src="<?php echo htmlspecialchars($item['file_path']); ?>" 
                             alt="<?php echo htmlspecialchars($item['title']); ?>"
                             loading="lazy"
                             class="w-full h-full object-cover">
                    <?php endif; ?>
                    
                    <?php if ($item['paid_content']): ?>
                        <div class="paid-badge">
                            <i class="fas fa-crown"></i> Premium
                        </div>
                    <?php endif; ?>

                    <div class="media-info">
                        <h3 class="text-lg font-bold"><?php echo htmlspecialchars($item['title']); ?></h3>
                        <?php if ($item['tags']): ?>
                            <div class="media-tags">
                                <?php foreach(explode(',', $item['tags']) as $tag): ?>
                                    <span class="tag"><?php echo htmlspecialchars($tag); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <?php if ($total_pages > 1): ?>
            <div class="pagination flex justify-center gap-2 mt-8">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>&view=<?php echo $view_type; ?>&type=<?php echo $media_type; ?>&orientation=<?php echo $orientation; ?>&resolution=<?php echo $resolution; ?>&sort=<?php echo $sort; ?>&search=<?php echo urlencode($search); ?>" 
                       class="btn btn-secondary">
                        <i class="fas fa-chevron-left"></i>
                    </a>
                <?php endif; ?>
                
                <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                    <a href="?page=<?php echo $i; ?>&view=<?php echo $view_type; ?>&type=<?php echo $media_type; ?>&orientation=<?php echo $orientation; ?>&resolution=<?php echo $resolution; ?>&sort=<?php echo $sort; ?>&search=<?php echo urlencode($search); ?>" 
                       class="btn <?php echo $i === $page ? 'btn-primary' : 'btn-secondary'; ?>">
                        <?php echo $i; ?>
                    </a>
                <?php endfor; ?>
                
                <?php if ($page < $total_pages): ?>
                    <a href="?page=<?php echo $page + 1; ?>&view=<?php echo $view_type; ?>&type=<?php echo $media_type; ?>&orientation=<?php echo $orientation; ?>&resolution=<?php echo $resolution; ?>&sort=<?php echo $sort; ?>&search=<?php echo urlencode($search); ?>" 
                       class="btn btn-secondary">
                        <i class="fas fa-chevron-right"></i>
                    </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>
    <?php include 'theme/homepage/footer.php'; ?>
    <!-- Accessibility Menu -->
    <?php include 'theme/homepage/accessibility.php'; ?>

    <!-- Scripts -->
    <script src="/assets/js/scripts.js"></script>
    <script src="/assets/js/accessibility.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // View type switching
            const viewButtons = document.querySelectorAll('[data-view]');
            const mediaGrid = document.querySelector('.media-grid');
            
            viewButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const viewType = button.dataset.view;
                    
                    // Update grid class
                    mediaGrid.className = `media-grid ${viewType}-view`;
                    
                    // Update URL without refresh
                    const url = new URL(window.location);
                    url.searchParams.set('view', viewType);
                    window.history.pushState({}, '', url);
                    
                    // Update button states
                    viewButtons.forEach(btn => {
                        btn.classList.toggle('btn-primary', btn === button);
                        btn.classList.toggle('btn-secondary', btn !== button);
                    });
                });
            });

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

            // Lazy loading for images
            if ('IntersectionObserver' in window) {
                const imageObserver = new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting) {
                            const img = entry.target;
                            img.src = img.dataset.src;
                            img.classList.remove('loading-skeleton');
                            observer.unobserve(img);
                        }
                    });
                });

                document.querySelectorAll('img[loading="lazy"]').forEach(img => {
                    img.dataset.src = img.src;
                    img.src = '';
                    img.classList.add('loading-skeleton');
                    imageObserver.observe(img);
                });
            }
        });
    </script>
</body>
</html>    