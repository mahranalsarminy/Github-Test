<?php
require_once(__DIR__ . '/includes/init.php');

// Get media ID and validate
$media_id = filter_var($_GET['id'] ?? null, FILTER_VALIDATE_INT);
if (!$media_id) {
    header('Location: /404.php');
    exit;
}

// Get site settings
$stmt = $pdo->query("SELECT site_name, site_logo, dark_mode, language FROM site_settings WHERE id = 1");
$site_settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Get current time in UTC
$current_time = gmdate('Y-m-d H:i:s');

// Get media details with related information
$stmt = $pdo->prepare("
    SELECT 
        m.*,
        c.name as category_name,
        c.slug as category_slug,
        COALESCE(m.ai_description, m.description) as enhanced_description,
        COUNT(DISTINCT mv.id) as view_count,
        COUNT(DISTINCT md.id) as download_count
    FROM media m
    LEFT JOIN categories c ON m.category_id = c.id
    LEFT JOIN media_views mv ON m.id = mv.media_id
    LEFT JOIN media_downloads md ON m.id = md.media_id
    WHERE m.id = ?
    GROUP BY m.id
");
$stmt->execute([$media_id]);
$media = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$media) {
    header('Location: /404.php');
    exit;
}

// Get media tags
$stmt = $pdo->prepare("
    SELECT t.* 
    FROM tags t
    JOIN media_tags mt ON t.id = mt.tag_id
    WHERE mt.media_id = ?
");
$stmt->execute([$media_id]);
$tags = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Record view
if (!isset($_SESSION['viewed_media'][$media_id])) {
    $stmt = $pdo->prepare("
        INSERT INTO media_views (media_id, user_id, session_id, viewed_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->execute([
        $media_id,
        $_SESSION['user_id'] ?? null,
        session_id()
    ]);
    $_SESSION['viewed_media'][$media_id] = true;
}

// Check if user is subscribed
$is_subscribed = false;
$current_user = null;
if (isset($_SESSION['user_id'])) {
    // Get user information
    $stmt = $pdo->prepare("
        SELECT username, email, role
        FROM users
        WHERE id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $current_user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check subscription status
    $stmt = $pdo->prepare("
        SELECT status 
        FROM user_subscriptions 
        WHERE user_id = ? 
        AND status = 'active'
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $is_subscribed = $stmt->fetch(PDO::FETCH_ASSOC) !== false;
}

// Generate download token
$download_token = bin2hex(random_bytes(16));
$_SESSION['download_tokens'][$download_token] = [
    'media_id' => $media_id,
    'expires' => time() + 3600 // 1 hour expiry
];
?>
<!DOCTYPE html>
<html lang="<?php echo $site_settings['language']; ?>" dir="<?php echo $site_settings['language'] === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($media['title']); ?> - <?php echo htmlspecialchars($site_settings['site_name']); ?></title>

  <!-- CSS Files -->
  <link rel="stylesheet" href="/assets/css/styles.css">
  <link rel="stylesheet" href="/assets/css/dark-mode.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Cairo|Roboto&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  <style>
    /* Default variables for light mode */
    :root {
      --bg-primary: #ffffff;
      --bg-secondary: #f7f7f7;
      --text-primary: #333333;
      --text-secondary: #555555;
      --accent-color: #1E3A8A;
      --accent-hover: #1D4ED8;
      --border-color: rgba(0,0,0,0.1);
    }
    
    /* Dark mode variables */
    body.dark-mode {
      --bg-primary: #121212;
      --bg-secondary: #1e1e1e;
      --text-primary: #e0e0e0;
      --text-secondary: #cccccc;
      --border-color: rgba(255,255,255,0.1);
    }
    
    /* Main container styles */
    .media-container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 1rem;
      font-family: 'Roboto','Cairo', sans-serif;
      background: var(--bg-primary);
      color: var(--text-primary);
    }
    
    /* Media grid layout */
    .media-grid {
      display: grid;
      grid-template-columns: 1fr;
      gap: 2rem;
    }
    
    @media (min-width: 1024px) {
      .media-grid {
        grid-template-columns: 2fr 1fr;
      }
    }
    
    /* Media details card */
    .media-details {
      background: var(--bg-secondary);
      padding: 2rem;
      border-radius: 0.5rem;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      border: 1px solid var(--border-color);
    }
    
    /* Detail group styling */
    .detail-group { 
      margin-bottom: 2rem; 
    }
    
    .detail-group:last-child { 
      margin-bottom: 0; 
    }
    
    .detail-label {
      font-size: 0.875rem;
      color: var(--text-secondary);
      margin-bottom: 0.5rem;
    }
    
    .detail-value {
      font-size: 1rem;
      color: var(--text-primary);
      padding: 0.5rem 1rem;
      background-color: var(--bg-primary);
      border-radius: 0.25rem;
      border: 1px solid var(--border-color);
    }
    
    /* Divider styling */
    .detail-divider {
      border-bottom: 1px solid var(--border-color);
      margin: 1rem 0;
    }
    
    /* Button styling */
    .download-button, .subscription-button {
      width: 100%;
      padding: 1rem;
      background: var(--accent-color);
      color: #fff;
      border: none;
      border-radius: 0.5rem;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
    }
    
    .download-button:hover, .subscription-button:hover { 
      background: var(--accent-hover); 
    }
    
    /* Thumbnail container */
    .thumbnail-container {
      width: 100%;
      height: auto;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      background-color: var(--bg-secondary);
    }
    
    .thumbnail-container img {
      max-width: 100%;
      max-height: 100%;
      object-fit: cover;
    }
    
    /* Media box */
    .media-box {
      position: relative;
      border-radius: 0.5rem;
      overflow: hidden;
      background: var(--bg-secondary);
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s, box-shadow 0.3s;
    }
    
    /* Badges */
    .media-badges {
      position: absolute;
      top: 1rem;
      left: 1rem;
      display: flex;
      gap: 0.5rem;
      z-index: 10;
    }
    
    .badge {
      padding: 0.5rem 1rem;
      border-radius: 0.25rem;
      font-size: 0.875rem;
      font-weight: 600;
      color: #fff;
    }
    
    .badge-featured { background-color: #3b82f6; }
    .badge-premium { background-color: #f59e0b; }
    .badge-ai { background-color: #10b981; }
    
    /* Tags styling */
    .tags-list {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
    }
    
    .tag {
      background: #e0f2fe;
      color: #0284c7;
      padding: 0.25rem 0.75rem;
      border-radius: 1rem;
      font-size: 0.875rem;
      transition: background 0.2s;
      text-decoration: none;
    }
    
    .tag:hover { background: #bae6fd; }
    
    /* Modal styling */
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 1000;
      align-items: center;
      justify-content: center;
    }
    
    .modal-content {
      background: var(--bg-primary);
      padding: 2rem;
      border-radius: 0.5rem;
      text-align: center;
      max-width: 90%;
      width: 400px;
    }
    
    .spinner {
      border: 4px solid rgba(0, 0, 0, 0.1);
      border-top: 4px solid #3b82f6;
      border-radius: 50%;
      width: 40px;
      height: 40px;
      animation: spin 1s linear infinite;
      margin: 1rem auto;
    }
    
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    
    /* Color chip styling */
    .color-chip {
      display: inline-block;
      width: 24px;
      height: 24px;
      border-radius: 4px;
      margin-right: 8px;
      border: 1px solid var(--border-color);
    }
    
    .color-chips {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      margin-top: 8px;
    }
  </style>
</head>

<body class="<?php echo $site_settings['dark_mode'] ? 'dark-mode' : 'light-mode'; ?>">
  <!-- Skip to main content link for accessibility -->
  <a href="#main-content" class="skip-link">Skip to main content</a>

  <!-- Header inclusion -->
  <?php include 'theme/homepage/header.php'; ?>

  <main id="main-content" class="media-container">
    <!-- Content header -->
    <div class="category-header">
    </div>

    <!-- Content grid -->
    <div class="media-grid">
      <!-- Media display box -->
      <div class="media-box">
        <!-- Badges -->
        <div class="media-badges">
          <?php if (isset($media['featured']) && $media['featured']): ?>
            <span class="badge badge-featured">Featured</span>
          <?php endif; ?>
          <?php if (isset($media['paid_content']) && $media['paid_content']): ?>
            <span class="badge badge-premium">Premium</span>
          <?php endif; ?>
          <?php if (isset($media['ai_enhanced']) && $media['ai_enhanced']): ?>
            <span class="badge badge-ai">AI Enhanced</span>
          <?php endif; ?>
        </div>

        <!-- Media content display (video or image) -->
        <?php if (isset($media['file_type']) && strpos($media['file_type'], 'video/') === 0): ?>
            <video controls poster="<?php echo htmlspecialchars($media['thumbnail_url'] ?? ''); ?>" 
                   style="width:100%; height:100%; object-fit: cover;">
                <source src="<?php echo htmlspecialchars($media['file_path']); ?>" type="<?php echo htmlspecialchars($media['file_type']); ?>">
                Your browser does not support HTML5 video.
            </video>
        <?php else: ?>
            <img src="<?php echo htmlspecialchars($media['file_path'] ?? $media['thumbnail_url'] ?? 'fallback.jpg'); ?>" 
                 alt="<?php echo htmlspecialchars($media['title']); ?>" 
                 loading="lazy" 
                 style="width:100%; height:100%; object-fit: cover;">
        <?php endif; ?>
    </div>

      <!-- Media details -->
      <div class="media-details">
        <!-- Current date and user info section -->
        <div class="detail-group">
          <div class="mb-3">
            <h2 class="text-xl font-bold mb-4">Name</h2>
            <div class="detail-value"><?php echo htmlspecialchars($media['title']); ?></div>
          </div>
        </div>
        
        <div class="detail-divider"></div>
        
        <!-- Basic information section -->
        <div class="detail-group">
          <h2 class="text-xl font-bold mb-4">Details</h2>
          
          <div class="mb-3">
            <div class="detail-label">Category</div>
            <a href="/category.php?slug=<?php echo htmlspecialchars($media['category_slug'] ?? ''); ?>" class="detail-value block" style="text-decoration:none; color:var(--accent-color);">
              <?php echo htmlspecialchars($media['category_name'] ?? 'Uncategorized'); ?>
            </a>
          </div>
          
          <div class="mb-3">
            <div class="detail-label">Type</div>
            <div class="detail-value"><?php echo htmlspecialchars($media['file_type'] ?? 'Unknown'); ?></div>
          </div>
          
          <?php if (isset($media['width']) && isset($media['height']) && $media['width'] && $media['height']): ?>
          <div class="mb-3">
            <div class="detail-label">Dimensions</div>
            <div class="detail-value"><?php echo $media['width']; ?> x <?php echo $media['height']; ?> px</div>
          </div>
          <?php endif; ?>
          
          <?php if (isset($media['orientation']) && $media['orientation']): ?>
          <div class="mb-3">
            <div class="detail-label">Orientation</div>
            <div class="detail-value"><?php echo htmlspecialchars($media['orientation']); ?></div>
          </div>
          <?php endif; ?>
          
          <?php if (isset($media['size_type']) && $media['size_type']): ?>
          <div class="mb-3">
            <div class="detail-label">Size Type</div>
            <div class="detail-value"><?php echo htmlspecialchars($media['size_type']); ?></div>
          </div>
          <?php endif; ?>
          
          <?php if (isset($media['file_size']) && $media['file_size']): ?>
          <div class="mb-3">
            <div class="detail-label">File Size</div>
            <div class="detail-value"><?php echo htmlspecialchars($media['file_size']); ?></div>
          </div>
          <?php endif; ?>
          
          <?php if (isset($media['color']) && $media['color']): ?>
          <div class="mb-3">
            <div class="detail-label">Color</div>
            <div class="detail-value"><?php echo htmlspecialchars($media['color']); ?></div>
          </div>
          <?php endif; ?>
          
          <?php if (isset($media['background_color']) && $media['background_color']): ?>
          <div class="mb-3">
            <div class="detail-label">Background Color</div>
            <div class="detail-value">
              <span class="color-chip" style="background-color: <?php echo htmlspecialchars($media['background_color']); ?>"></span>
              <?php echo htmlspecialchars($media['background_color']); ?>
            </div>
          </div>
          <?php endif; ?>
          
          <?php if (isset($media['associated_colors']) && $media['associated_colors']): ?>
          <div class="mb-3">
            <div class="detail-label">Associated Colors</div>
            <div class="detail-value">
              <?php 
              $colors = explode(',', $media['associated_colors']);
              if (!empty($colors)): ?>
                <div class="color-chips">
                  <?php foreach($colors as $color): ?>
                    <span class="color-chip" style="background-color: <?php echo htmlspecialchars(trim($color)); ?>"></span>
                  <?php endforeach; ?>
                </div>
                <?php echo htmlspecialchars($media['associated_colors']); ?>
              <?php endif; ?>
            </div>
          </div>
          <?php endif; ?>
          
          <?php if (isset($media['quality']) && $media['quality']): ?>
          <div class="mb-3">
            <div class="detail-label">Quality</div>
            <div class="detail-value"><?php echo htmlspecialchars($media['quality']); ?></div>
          </div>
          <?php endif; ?>
          
          <?php if (isset($media['resolution']) && $media['resolution']): ?>
          <div class="mb-3">
            <div class="detail-label">Resolution</div>
            <div class="detail-value"><?php echo htmlspecialchars($media['resolution']); ?></div>
          </div>
          <?php endif; ?>
          
          <?php if (isset($media['license']) && $media['license']): ?>
          <div class="mb-3">
            <div class="detail-label">License</div>
            <div class="detail-value"><?php echo htmlspecialchars($media['license']); ?></div>
          </div>
          <?php endif; ?>
          
          <div class="mb-3">
            <div class="detail-label">Content Type</div>
            <div class="detail-value">
              <?php echo (isset($media['paid_content']) && $media['paid_content']) ? 'Premium Content' : 'Free Content'; ?>
            </div>
          </div>
        </div>
        
        <?php if (isset($media['ai_description']) && $media['ai_description']): ?>
        <div class="detail-divider"></div>
        
        <div class="detail-group">
          <h3 class="text-lg font-semibold mb-3">AI / Description</h3>
          <div class="detail-value" style="white-space: pre-line;">
              <?php echo htmlspecialchars($media['description']); ?> ,<?php echo htmlspecialchars($media['ai_description']); ?>
          </div>
        </div>
        <?php endif; ?>
        
        <div class="detail-divider"></div>
        
        <!-- Statistics section -->
        <div class="detail-group">
          <h3 class="text-lg font-semibold mb-3">Statistics</h3>
          <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem;">
            <div>
              <div class="detail-label">Views</div>
              <div class="detail-value"><?php echo number_format($media['view_count']); ?></div>
            </div>
            <div>
              <div class="detail-label">Downloads</div>
              <div class="detail-value"><?php echo number_format($media['download_count']); ?></div>
            </div>
          </div>
        </div>
        
        <!-- Tags section -->
        <?php if (!empty($tags)): ?>
        <div class="detail-divider"></div>
        
        <div class="detail-group">
          <h3 class="text-lg font-semibold mb-3">Tags</h3>
          <div class="tags-list flex flex-wrap">
            <?php foreach ($tags as $tag): ?>
              <a href="/search.php?tag=<?php echo htmlspecialchars($tag['slug']); ?>" class="tag">
                <?php echo htmlspecialchars($tag['name']); ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
        
        <div class="detail-divider"></div>
        
        <!-- Action button section -->
        <?php if (isset($media['paid_content']) && $media['paid_content'] && !$is_subscribed): ?>
          <button class="subscription-button" onclick="window.location.href='/subscription.php'">
            <i class="fas fa-crown mr-2"></i> Purchase Subscription
          </button>
          <p class="text-sm text-center mt-3 text-gray-500 dark:text-gray-400">
            This is premium content. Subscribe to download this and other premium content.
          </p>
        <?php else: ?>
          <button class="download-button" data-token="<?php echo $download_token; ?>" onclick="initiateDownload(this)">
            <i class="fas fa-download mr-2"></i> Download
          </button>
        <?php endif; ?>
      </div>
    </div>
  </main>
  
  <!-- Download modal -->
  <div id="downloadModal" class="modal">
    <div class="modal-content">
      <h3 class="text-xl font-bold mb-4">Preparing Download</h3>
      <div class="spinner"></div>
      <p>Please wait while we prepare your download...</p>
    </div>
  </div>

  <!-- Footer inclusion -->
  <?php include 'theme/homepage/footer.php'; ?>
  
  <!-- Accessibility toggle button -->
  <div id="accessibility-toggle" class="accessibility-button" aria-label="Accessibility options">
      <i class="fas fa-universal-access"></i>
  </div>

  <script>
    // Download initiation functionality
    async function initiateDownload(button) {
      const modal = document.getElementById('downloadModal');
      const token = button.dataset.token;
      
      // Display modal
      modal.style.display = 'flex';
      
      // Simulate download preparation with timeout
      setTimeout(async () => {
        try {
          const response = await fetch('/api/download.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ 
              token: token
            })
          });
          
          if (!response.ok) {
            throw new Error('Failed to prepare download');
          }
          
          const data = await response.json();
          if (data.success && data.url) {
            window.location.href = data.url;
          } else {
            throw new Error('Invalid response data');
          }
        } catch (error) {
          console.error('Download error:', error);
          alert('An error occurred while preparing your download. Please try again.');
        } finally {
          modal.style.display = 'none';
        }
      }, 2000);
    }
    
    // Modal click outside to close
    window.addEventListener('click', (event) => {
      const modal = document.getElementById('downloadModal');
      if (event.target === modal) {
        modal.style.display = 'none';
      }
    });
    
    // Initialize accessibility features
    function initializeAccessibilityFeatures() {
      const fontSizeControls = document.querySelectorAll('[data-font-size]');
      fontSizeControls.forEach(control => {
        control.addEventListener('click', () => {
          const size = control.dataset.fontSize;
          document.documentElement.style.fontSize = size;
          localStorage.setItem('preferred-font-size', size);
        });
      });
      
      const highContrastToggle = document.querySelector('#high-contrast-toggle');
      if (highContrastToggle) {
        highContrastToggle.addEventListener('click', () => {
          document.body.classList.toggle('high-contrast');
          localStorage.setItem('high-contrast', document.body.classList.contains('high-contrast'));
        });
      }
      
      document.addEventListener('keydown', (e) => {
        if (e.key === 'Tab') {
          document.body.classList.add('keyboard-navigation');
        }
      });
      
      document.addEventListener('mousedown', () => {
        document.body.classList.remove('keyboard-navigation');
      });
    }
    
    document.addEventListener('DOMContentLoaded', function() {
      initializeAccessibilityFeatures();
    });
  </script>

  <!-- Include accessibility panel -->
  <?php include 'theme/homepage/accessibility.php'; ?>
  
  <!-- General scripts -->
  <script src="/assets/js/scripts.js"></script>
  <script src="/assets/js/accessibility.js"></script>
</body>
</html>