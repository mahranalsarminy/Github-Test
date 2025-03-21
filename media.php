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

  <!-- ملفات التنسيق العامة -->
  <link rel="stylesheet" href="/assets/css/styles.css">
  <link rel="stylesheet" href="/assets/css/dark-mode.css">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Cairo|Roboto&display=swap">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

  <style>
    /* تعريف المتغيرات الافتراضية للوضع الفاتح */
    :root {
      --bg-primary: #ffffff;
      --bg-secondary: #f7f7f7;
      --text-primary: #333333;
      --text-secondary: #555555;
    }
    /* المتغيرات للوضع الداكن */
    body.dark-mode {
      --bg-primary: #121212;
      --bg-secondary: #1e1e1e;
      --text-primary: #e0e0e0;
      --text-secondary: #cccccc;
    }
    /* حاوية الصفحة */
    .media-container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 1rem;
      font-family: 'Roboto','Cairo', sans-serif;
      background: var(--bg-primary);
      color: var(--text-primary);
    }
    /* ترويسة الصفحة */
    .category-header {
      margin-bottom: 2rem;
      text-align: center;
    }
    .category-header h1 {
      margin-bottom: 0.5rem;
    }
    /* شبكة المحتوى */
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
    /* البطاقة الرئيسية للوسائط */
    .media-box {
      position: relative;
      border-radius: 0.5rem;
      overflow: hidden;
      background: var(--bg-secondary);
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      transition: transform 0.3s, box-shadow 0.3s;
    }
    .media-box:hover {
      transform: translateY(-5px);
      box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
    }
    /* عرض الصورة أو الفيديو داخل البطاقة */
    .media-box img,
    .media-box video,
    .media-box iframe {
      width: 100%;
      height: auto;
      display: block;
      object-fit: cover;
      max-height: 80vh;
    }
    /* استعلام وسائط للأجهزة الصغيرة */
    @media (max-width: 640px) {
      .media-box img,
      .media-box video,
      .media-box iframe {
        object-fit: contain;
        max-height: none;
      }
    }
    /* بادجات الوسائط */
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
    /* تفاصيل الوسائط */
    .media-details {
      background: var(--bg-secondary);
      padding: 2rem;
      border-radius: 0.5rem;
      box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
      border: 1px solid rgba(0,0,0,0.1);
    }
    .media-details h2,
    .media-details h3 {
      margin-bottom: 1rem;
      color: var(--text-primary);
    }
    .detail-group { margin-bottom: 2rem; }
    .detail-group:last-child { margin-bottom: 0; }
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
      border: 1px solid rgba(0,0,0,0.1);
    }
    .detail-divider {
      border-bottom: 1px solid rgba(0,0,0,0.1);
      margin: 1rem 0;
    }
    /* الوسوم */
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
    /* زر التنزيل */
    .download-button {
      width: 100%;
      padding: 1rem;
      background: #1E3A8A;
      color: #fff;
      border: none;
      border-radius: 0.5rem;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: background 0.2s;
    }
    .download-button:hover { background: #1D4ED8; }
    .download-button:disabled {
      background: #9ca3af;
      cursor: not-allowed;
    }
    /* النوافذ المنبثقة (المودال) */
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
    /* رابط التخطي لإمكانية الوصول */
    .skip-link {
      position: absolute;
      top: -40px;
      left: 0;
      background: #4A90E2;
      color: #fff;
      padding: 8px;
      z-index: 1000;
      transition: top 0.3s;
    }
    .skip-link:focus { top: 0; }
    /* مثال لوضع التباين العالي */
    .high-contrast {
      --bg-primary: #000000;
      --bg-secondary: #121212;
      --text-primary: #FFFFFF;
      --text-secondary: #EEEEEE;
      --border-color: #FFFFFF;
    }
  </style>
</head>
<body class="<?php echo $site_settings['dark_mode'] ? 'dark-mode' : 'light-mode'; ?>">
  <!-- Skip to main content link for accessibility -->
  <a href="#main-content" class="skip-link">Skip to main content</a>

  <!-- الهيدر -->
  <?php include 'theme/homepage/header.php'; ?>

  <main id="main-content" class="media-container">
    <!-- ترويسة المحتوى -->
    <div class="category-header">
      <h1><?php echo htmlspecialchars($media['title']); ?></h1>
      <p class="text-gray-600 dark:text-gray-300 mb-6"></p>
    </div>

    <!-- شبكة المحتوى -->
    <div class="media-grid">
      <!-- بطاقة الوسائط -->
      <div class="media-box">
        <!-- بادجات -->
        <div class="media-badges">
          <?php if ($media['featured']): ?>
            <span class="badge badge-featured">Featured</span>
          <?php endif; ?>
          <?php if ($media['paid_content']): ?>
            <span class="badge badge-premium">Premium</span>
          <?php endif; ?>
          <?php if ($media['ai_enhanced']): ?>
            <span class="badge badge-ai">AI Enhanced</span>
          <?php endif; ?>
        </div>

        <!-- عرض الفيديو أو الصورة -->
        <?php if (strpos($media['file_type'], 'video/') === 0): ?>
          <?php if ($media['file_path']): ?>
            <video controls poster="<?php echo htmlspecialchars($media['thumbnail_url']); ?>" style="width:100%; height:auto;">
              <source src="<?php echo htmlspecialchars($media['file_path']); ?>" type="<?php echo htmlspecialchars($media['file_type']); ?>">
              Your browser does not support HTML5 video.
            </video>
          <?php else: ?>
            <!-- عرض الفيديو من مصدر خارجي -->
            <div class="external-video">
                <iframe src="<?php echo htmlspecialchars($media['external_url']); ?>" frameborder="0" allowfullscreen style="width:100%; height:auto;"></iframe>
            </div>
        <?php endif; ?>
        <?php else: ?>
        <!-- عرض الصور بشكل استجابة لحجم الشاشة -->
        <picture>
            <!-- صورة لحجم الشاشات الكبيرة -->
            <source media="(min-width: 1024px)" 
                    srcset="<?php echo htmlspecialchars($media['external_url'] ?? $media['file_path']); ?>">
            <!-- صورة لحجم الشاشات المتوسطة -->
            <source media="(min-width: 640px)" 
                    srcset="<?php echo htmlspecialchars($media['thumbnail_url']); ?>">
            <!-- الصورة الافتراضية للأجهزة الصغيرة -->
            <img src="<?php echo htmlspecialchars($media['thumbnail_url']); ?>" 
                 alt="<?php echo htmlspecialchars($media['title']); ?>"
                 loading="lazy" style="width:100%; height:auto;">
        </picture>
        <?php endif; ?>
      </div>

      <!-- تفاصيل الوسائط -->
      <div class="media-details">
        <!-- المعلومات الأساسية -->
        <div class="detail-group">
          <h2 class="text-xl font-bold">Details</h2>
          <div class="mb-1">
            <div class="detail-label">Category</div>
            <a href="/category.php?slug=<?php echo htmlspecialchars($media['category_slug']); ?>" class="detail-value" style="text-decoration:none; color:#1D4ED8;">
              <?php echo htmlspecialchars($media['category_name']); ?>
            </a>
          </div>
          <div class="mb-1">
            <div class="detail-label">Type</div>
            <div class="detail-value"><?php echo htmlspecialchars($media['file_type']); ?></div>
          </div>
          <?php if ($media['width'] && $media['height']): ?>
          <div class="mb-1">
            <div class="detail-label">Dimensions</div>
            <div class="detail-value"><?php echo $media['width']; ?> x <?php echo $media['height']; ?></div>
          </div>
          <?php endif; ?>
          <div class="mb-1">
            <div class="detail-label">File Size</div>
            <div class="detail-value"><?php echo htmlspecialchars($media['file_size']); ?></div>
          </div>
        </div>

        <div class="detail-divider"></div>

        <!-- الإحصائيات -->
        <div class="detail-group">
          <h3 class="text-lg font-semibold">Statistics</h3>
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

        <div class="detail-divider"></div>

        <!-- الوسوم -->
        <?php if (!empty($tags)): ?>
        <div class="detail-group">
          <h3 class="text-lg font-semibold">Tags</h3>
          <div class="tags-list">
            <?php foreach ($tags as $tag): ?>
              <a href="/search.php?tag=<?php echo htmlspecialchars($tag['slug']); ?>" class="tag">
                <?php echo htmlspecialchars($tag['name']); ?>
              </a>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>

        <div class="detail-divider"></div>

        <!-- زر التنزيل -->
        <button class="download-button" data-token="<?php echo $download_token; ?>" onclick="initiateDownload(this)">
          Download
        </button>
      </div>
    </div>
  </main>

  <!-- نافذة التنزيل (مودال) -->
  <div id="downloadModal" class="modal">
    <div class="modal-content">
      <h3 class="text-xl font-bold mb-4">Preparing Download</h3>
      <div class="spinner"></div>
      <p>Please wait while we prepare your download...</p>
    </div>
  </div>

  <?php include 'theme/homepage/footer.php'; ?>

  <!-- زر إمكانية الوصول -->
  <div id="accessibility-toggle" class="accessibility-button" aria-label="Accessibility options">
      <i class="fas fa-universal-access"></i>
  </div>

  <script>
    async function initiateDownload(button) {
      const modal = document.getElementById('downloadModal');
      const token = button.dataset.token;
      
      // عرض المودال
      modal.style.display = 'flex';
      
      // تأخير محاكاة تحضير التنزيل
      setTimeout(async () => {
        try {
          const response = await fetch('/api/download.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ token })
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

    // إغلاق المودال عند النقر خارج محتواه
    window.addEventListener('click', (event) => {
      const modal = document.getElementById('downloadModal');
      if (event.target === modal) {
        modal.style.display = 'none';
      }
    });

    // تهيئة ميزات إمكانية الوصول
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
  
    document.addEventListener('DOMContentLoaded', initializeAccessibilityFeatures);
  </script>

  <?php include 'theme/homepage/accessibility.php'; ?>

  <!-- سكربتات عامة -->
  <script src="/assets/js/scripts.js"></script>
  <script src="/assets/js/accessibility.js"></script>
</body>
</html>
