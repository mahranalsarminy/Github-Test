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

// Get favorites count for pagination
$stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ?");
$stmt->execute([$user_id]);
$total_favorites = $stmt->fetchColumn();
$total_pages = ceil($total_favorites / $per_page);

// Get favorites with pagination
$favorites = [];
$stmt = $pdo->prepare("SELECT w.*, f.created_at AS favorited_at, c.name AS category_name
                      FROM favorites f
                      JOIN wallpapers w ON f.wallpaper_id = w.id
                      LEFT JOIN categories c ON w.category_id = c.id
                      WHERE f.user_id = ?
                      ORDER BY f.created_at DESC
                      LIMIT ? OFFSET ?");
$stmt->execute([$user_id, $per_page, $offset]);
$favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle removing from favorites
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['remove_favorite'])) {
    $wallpaper_id = (int)$_POST['wallpaper_id'];
    
    // Remove from favorites
    $stmt = $pdo->prepare("DELETE FROM favorites WHERE user_id = ? AND wallpaper_id = ?");
    $result = $stmt->execute([$user_id, $wallpaper_id]);
    
    if ($result) {
        $success_message = "Wallpaper removed from favorites.";
        
        // Refresh the favorites list
        $stmt = $pdo->prepare("SELECT w.*, f.created_at AS favorited_at, c.name AS category_name
                             FROM favorites f
                             JOIN wallpapers w ON f.wallpaper_id = w.id
                             LEFT JOIN categories c ON w.category_id = c.id
                             WHERE f.user_id = ?
                             ORDER BY f.created_at DESC
                             LIMIT ? OFFSET ?");
        $stmt->execute([$user_id, $per_page, $offset]);
        $favorites = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Update total count
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM favorites WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $total_favorites = $stmt->fetchColumn();
        $total_pages = ceil($total_favorites / $per_page);
    } else {
        $error_message = "Failed to remove from favorites. Please try again.";
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $site_settings['language']; ?>" dir="<?php echo $site_settings['language'] === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Favorites - <?php echo safe_echo($site_settings['site_name']); ?></title>
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
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: space-between;
        }
        
        @media (min-width: 768px) {
            .page-header {
                flex-direction: row;
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
        
        .card-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 1.5rem;
        }
        
        .card {
            background-color: var(--bg-card);
            border-radius: 8px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            display: flex;
            flex-direction: column;
        }
        
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.15);
        }
        
        .card-image-container {
            position: relative;
            padding-top: 75%; /* 4:3 Aspect Ratio */
            overflow: hidden;
        }
        
        .card-image {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }
        
        .card:hover .card-image {
            transform: scale(1.05);
        }
        
        .card-body {
            padding: 1.2rem;
            flex-grow: 1;
            display: flex;
            flex-direction: column;
        }
        
        .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }
        
        .card-meta {
            margin-bottom: 0.75rem;
            color: var(--text-secondary);
            font-size: 0.9rem;
        }
        
        .card-category {
            display: inline-block;
            background-color: var(--accent-color);
            color: white;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-size: 0.75rem;
            margin-bottom: 0.75rem;
        }
        
        .card-actions {
            margin-top: auto;
            display: flex;
            justify-content: space-between;
            gap: 0.5rem;
        }
        
        .btn {
            display: inline-block;
            padding: 0.6rem 1rem;
            font-size: 0.9rem;
            font-weight: 500;
            border-radius: 4px;
            text-align: center;
            cursor: pointer;
            transition: background-color 0.3s, color 0.3s;
            flex: 1;
        }
        
        .btn-primary {
            background-color: var(--accent-color);
            color: white;
            border: none;
        }
        
        .btn-primary:hover {
            background-color: var(--accent-hover);
        }
        
        .btn-secondary {
            background-color: transparent;
            color: var(--text-primary);
            border: 1px solid var(--border-color);
        }
        
        .btn-secondary:hover {
            background-color: var(--bg-secondary);
        }
        
        .btn-danger {
            background-color: #e74c3c;
            color: white;
            border: none;
        }
        
        .btn-danger:hover {
            background-color: #c0392b;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            margin-top: 2rem;
            gap: 0.5rem;
        }
        
        .pagination a, .pagination span {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 4px;
            text-decoration: none;
            background-color: var(--bg-card);
            color: var(--text-primary);
            border: 1px solid var(--border-color);
            transition: background-color 0.3s;
        }
        
        .pagination a:hover {
            background-color: var(--bg-secondary);
        }
        
        .pagination .active {
            background-color: var(--accent-color);
            color: white;
            border-color: var(--accent-color);
        }
        
        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            background-color: var(--bg-card);
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .empty-state i {
            font-size: 3rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
            opacity: 0.5;
        }
        
        .empty-state h2 {
            font-size: 1.5rem;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }
        
        .empty-state p {
            color: var(--text-secondary);
            max-width: 500px;
            margin: 0 auto 1.5rem;
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 8px;
            color: #fff;
        }
        
        .alert-success {
            background-color: #2ecc71;
        }
        
        .alert-error {
            background-color: #e74c3c;
        }
        
        .breadcrumbs {
            display: flex;
            margin-bottom: 1.5rem;
            flex-wrap: wrap;
        }
        
        .breadcrumbs a, .breadcrumbs span {
            color: var(--text-secondary);
            text-decoration: none;
            margin-right: 0.5rem;
            display: flex;
            align-items: center;
        }
        
        .breadcrumbs a:hover {
            color: var(--accent-color);
        }
        
        .breadcrumbs i {
            margin-left: 0.5rem;
            font-size: 0.8rem;
        }
        
        .breadcrumbs .current {
            color: var(--text-primary);
            font-weight: 500;
        }
        
        .action-buttons {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        
        @media (max-width: 640px) {
            .action-buttons {
                flex-direction: column;
            }
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
            <div class="breadcrumbs">
                <a href="index.php">Home <i class="fas fa-chevron-right"></i></a>
                <a href="profile.php">My Profile <i class="fas fa-chevron-right"></i></a>
                <span class="current">My Favorites</span>
            </div>
            
            <?php if (!empty($success_message)): ?>
            <div class="alert alert-success" role="alert">
                <?php echo $success_message; ?>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
            <div class="alert alert-error" role="alert">
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>
            
            <div class="page-header">
                <div class="header-title">
                    <h1>My Favorites</h1>
                    <p>Browse and manage your favorite wallpapers. Remove items or download them again.</p>
                </div>
                <div class="header-actions">
                    <p>Total favorites: <strong><?php echo $total_favorites; ?></strong></p>
                </div>
            </div>
            
            <div class="action-buttons">
                <a href="profile.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Profile
                </a>
                <?php if (count($favorites) > 0): ?>
                <button id="select-all-btn" class="btn btn-secondary">
                    <i class="fas fa-check-square"></i> Select All
                </button>
                <button id="deselect-all-btn" class="btn btn-secondary" style="display:none;">
                    <i class="fas fa-square"></i> Deselect All
                </button>
                <button id="remove-selected-btn" class="btn btn-danger" style="display:none;">
                    <i class="fas fa-trash"></i> Remove Selected
                </button>
                <?php endif; ?>
            </div>
            
            <?php if (count($favorites) > 0): ?>
                <div class="card-grid">
                    <?php foreach ($favorites as $favorite): ?>
                        <div class="card" data-id="<?php echo $favorite['id']; ?>">
                            <div class="card-image-container">
                             <img src="<?php echo safe_echo($favorite['thumbnail_url']); ?>" alt="<?php echo safe_echo($favorite['title']); ?>" class="card-image">
                                <div class="favorite-checkbox" style="position:absolute; top:10px; right:10px; background:rgba(0,0,0,0.5); border-radius:4px; padding:5px; display:none;">
                                    <input type="checkbox" class="select-favorite" id="select-<?php echo $favorite['id']; ?>" data-id="<?php echo $favorite['id']; ?>">
                                    <label for="select-<?php echo $favorite['id']; ?>" style="cursor:pointer;"></label>
                                </div>
                            </div>
                            <div class="card-body">
                                <span class="card-category"><?php echo safe_echo($favorite['category_name'] ?? 'General'); ?></span>
                                <h3 class="card-title"><?php echo safe_echo($favorite['title']); ?></h3>
                                <div class="card-meta">
                                    <p><i class="far fa-clock"></i> Added: <?php echo date('M j, Y', strtotime($favorite['favorited_at'])); ?></p>
                                    <p><i class="fas fa-desktop"></i> <?php echo safe_echo($favorite['resolution']); ?></p>
                                </div>
                                <div class="card-actions">
                                    <a href="wallpaper.php?id=<?php echo $favorite['id']; ?>" class="btn btn-secondary">View</a>
                                    <a href="download.php?id=<?php echo $favorite['id']; ?>" class="btn btn-primary">Download</a>
                                    <form method="post" style="flex:1;">
                                        <input type="hidden" name="wallpaper_id" value="<?php echo $favorite['id']; ?>">
                                        <button type="submit" name="remove_favorite" class="btn btn-danger" style="width:100%;">Remove</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=1">First</a>
                        <a href="?page=<?php echo $page - 1; ?>">Previous</a>
                    <?php endif; ?>
                    
                    <?php
                    $start = max(1, $page - 2);
                    $end = min($total_pages, $page + 2);
                    
                    for ($i = $start; $i <= $end; $i++): ?>
                        <a href="?page=<?php echo $i; ?>" class="<?php echo ($i === $page) ? 'active' : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                    
                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?php echo $page + 1; ?>">Next</a>
                        <a href="?page=<?php echo $total_pages; ?>">Last</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="empty-state">
                    <i class="far fa-heart"></i>
                    <h2>No Favorites Yet</h2>
                    <p>You haven't added any wallpapers to your favorites list yet. Browse our collection and click the heart icon to add wallpapers to your favorites.</p>
                    <a href="index.php" class="btn btn-primary">Browse Wallpapers</a>
                </div>
            <?php endif; ?>
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
            const selectAllBtn = document.getElementById('select-all-btn');
            const deselectAllBtn = document.getElementById('deselect-all-btn');
            const removeSelectedBtn = document.getElementById('remove-selected-btn');
            const favoriteCheckboxes = document.querySelectorAll('.select-favorite');
            const checkboxContainers = document.querySelectorAll('.favorite-checkbox');
            
            if (selectAllBtn && deselectAllBtn && removeSelectedBtn) {
                // Initialize selection mode
                let selectionMode = false;
                
                // Toggle selection mode
                selectAllBtn.addEventListener('click', function() {
                    selectionMode = true;
                    selectAllBtn.style.display = 'none';
                    deselectAllBtn.style.display = 'inline-flex';
                    removeSelectedBtn.style.display = 'inline-flex';
                    
                    // Show checkboxes
                    checkboxContainers.forEach(container => {
                        container.style.display = 'block';
                    });
                    
                    // Select all checkboxes
                    favoriteCheckboxes.forEach(checkbox => {
                        checkbox.checked = true;
                    });
                    
                    // Count selected items
                    updateSelectedCount();
                });
                
                // Deselect all
                deselectAllBtn.addEventListener('click', function() {
                    // Uncheck all checkboxes
                    favoriteCheckboxes.forEach(checkbox => {
                        checkbox.checked = false;
                    });
                    
                    // Count selected items
                    updateSelectedCount();
                });
                
                // Handle checkbox changes
                favoriteCheckboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', function() {
                        updateSelectedCount();
                    });
                });
                
                // Remove selected items
                removeSelectedBtn.addEventListener('click', function() {
                    const selectedIds = [];
                    favoriteCheckboxes.forEach(checkbox => {
                        if (checkbox.checked) {
                            selectedIds.push(checkbox.dataset.id);
                        }
                    });
                    
                    if (selectedIds.length > 0) {
                        if (confirm(`Are you sure you want to remove ${selectedIds.length} item(s) from favorites?`)) {
                            // Create a form to submit the selected IDs
                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.style.display = 'none';
                            
                            // Add each selected ID as a hidden input
                            selectedIds.forEach(id => {
                                const input = document.createElement('input');
                                input.type = 'hidden';
                                input.name = 'selected_ids[]';
                                input.value = id;
                                form.appendChild(input);
                            });
                            
                            // Add a submit button
                            const submitBtn = document.createElement('input');
                            submitBtn.type = 'hidden';
                            submitBtn.name = 'remove_selected';
                            submitBtn.value = '1';
                            form.appendChild(submitBtn);
                            
                            // Append the form to the body and submit it
                            document.body.appendChild(form);
                            form.submit();
                        }
                    }
                });
                
                // Function to update the selected count
                function updateSelectedCount() {
                    let count = 0;
                    favoriteCheckboxes.forEach(checkbox => {
                        if (checkbox.checked) {
                            count++;
                        }
                    });
                    
                    // Update button text
                    removeSelectedBtn.innerHTML = `<i class="fas fa-trash"></i> Remove Selected (${count})`;
                    
                    // Toggle visibility of remove button
                    if (count > 0) {
                        removeSelectedBtn.style.display = 'inline-flex';
                    } else {
                        removeSelectedBtn.style.display = 'none';
                    }
                }
                
                // Add hover effect for cards
                document.querySelectorAll('.card').forEach(card => {
                    card.addEventListener('mouseenter', function() {
                        if (selectionMode) {
                            this.querySelector('.favorite-checkbox').style.display = 'block';
                        }
                    });
                    
                    card.addEventListener('mouseleave', function() {
                        if (!selectionMode) {
                            this.querySelector('.favorite-checkbox').style.display = 'none';
                        }
                    });
                });
            }
            
            // Last updated info
            const lastUpdatedInfo = document.createElement('p');
            lastUpdatedInfo.textContent = 'Last updated: March 20, 2025 06:21:48 UTC';
            lastUpdatedInfo.style.textAlign = 'center';
            lastUpdatedInfo.style.margin = '2rem 0';
            lastUpdatedInfo.style.fontSize = '0.8rem';
            lastUpdatedInfo.style.color = 'var(--text-secondary)';
            document.querySelector('.page-container').appendChild(lastUpdatedInfo);
            
            // Welcome message for user
            const welcomeMessage = document.createElement('div');
            welcomeMessage.style.padding = '1rem';
            welcomeMessage.style.marginBottom = '1rem';
            welcomeMessage.style.borderRadius = '8px';
            welcomeMessage.style.backgroundColor = 'var(--bg-secondary)';
            welcomeMessage.style.display = 'flex';
            welcomeMessage.style.alignItems = 'center';
            welcomeMessage.style.justifyContent = 'space-between';
            
            const welcomeText = document.createElement('p');
            welcomeText.style.margin = '0';
            welcomeText.innerHTML = `<strong>Welcome, ${user.username || 'mahranalsarminy'}!</strong> Here are your favorite wallpapers.`;
            
            const closeBtn = document.createElement('button');
            closeBtn.innerHTML = '&times;';
            closeBtn.style.background = 'none';
            closeBtn.style.border = 'none';
            closeBtn.style.fontSize = '1.2rem';
            closeBtn.style.cursor = 'pointer';
            closeBtn.style.color = 'var(--text-primary)';
            closeBtn.addEventListener('click', () => {
                welcomeMessage.style.display = 'none';
                localStorage.setItem('hideWelcomeMessage', 'true');
            });
            
            welcomeMessage.appendChild(welcomeText);
            welcomeMessage.appendChild(closeBtn);
            
            // Only show if not hidden
            if (localStorage.getItem('hideWelcomeMessage') !== 'true') {
                document.querySelector('.page-container').insertBefore(welcomeMessage, document.querySelector('.page-header'));
            }
        });
    </script>
</body>
</html>