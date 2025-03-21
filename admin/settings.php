<?php
require_once '../includes/init.php';


// Ensure only admins can access this page
require_admin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        $stmt = $pdo->prepare("
            UPDATE site_settings 
            SET 
                site_name = :site_name,
                site_description = :site_description,
                site_keywords = :site_keywords,
                site_url = :site_url,
                site_email = :site_email,
                google_analytics = :google_analytics,
                latest_items_count = :latest_items_count,
                featured_wallpapers_count = :featured_wallpapers_count,
                featured_media_count = :featured_media_count,
                facebook_url = :facebook_url,
                twitter_url = :twitter_url,
                instagram_url = :instagram_url,
                youtube_url = :youtube_url,
                updated_at = :updated_at,
                updated_by = :updated_by
            WHERE id = 1
        ");

$stmt->execute([
    'site_name' => $_POST['site_name'],
    'site_description' => $_POST['site_description'],
    'site_keywords' => $_POST['site_keywords'],
    'site_url' => $_POST['site_url'],
    'site_email' => $_POST['site_email'],
    'google_analytics' => $_POST['google_analytics'],
    'latest_items_count' => max(5, min(20, (int)$_POST['latest_items_count'])),
    'featured_wallpapers_count' => max(5, min(20, (int)$_POST['featured_wallpapers_count'])),
    'featured_media_count' => max(5, min(20, (int)$_POST['featured_media_count'])),
    'facebook_url' => $_POST['facebook_url'],
    'twitter_url' => $_POST['twitter_url'],
    'instagram_url' => $_POST['instagram_url'],
    'youtube_url' => $_POST['youtube_url'],
    'updated_at' => date('Y-m-d H:i:s'),
    'updated_by' => $_SESSION['user_id'] ?? 'system'
]);


        $_SESSION['success'] = 'Settings updated successfully';
    } catch (PDOException $e) {
        error_log("Settings update error: " . $e->getMessage());
        $_SESSION['error'] = 'Error updating settings';
    }
    
    header("Location: settings.php");
    exit;
}

$settings = get_site_settings();
include 'theme/header.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $lang['code']; ?>" dir="<?php echo $lang['dir']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Settings - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="../assets/css/admin.css" rel="stylesheet">
    <style>
        input, textarea {
            display: block !important;
            width: 100% !important;
            padding: 0.5rem !important;
            border: 1px solid #e2e8f0 !important;
            border-radius: 0.375rem !important;
            background-color: white !important;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
    </style>
</head>
<body class="bg-gray-100 flex flex-col min-h-screen">
    <?php include 'theme/sidebar.php'; ?>

    <div class="flex flex-1 flex-col md:flex-row">
        <aside class="md:w-64 w-full md:block hidden">
            <?php include 'theme/sidebar.php'; ?>
        </aside>

        <main class="flex-1 p-4 mt-16 w-full">
            <div class="bg-white rounded-lg shadow p-6 w-full">
                <h1 class="text-2xl font-bold mb-4 text-center">Site Settings</h1>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>

                <form method="post">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <h2 class="text-xl font-semibold mb-4">Basic Settings</h2>

                            <div class="form-group">
                                <label>Site Name</label>
                                <input type="text" name="site_name" value="<?php echo h($settings['site_name']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Site Description</label>
                                <textarea name="site_description" rows="3"><?php echo h($settings['site_description']); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label>Keywords</label>
                                <textarea name="site_keywords" rows="2"><?php echo h($settings['site_keywords']); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label>Site URL</label>
                                <input type="url" name="site_url" value="<?php echo h($settings['site_url']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Site Email</label>
                                <input type="email" name="site_email" value="<?php echo h($settings['site_email']); ?>" required>
                            </div>

                            <div class="form-group">
                                <label>Google Analytics Code</label>
                                <textarea name="google_analytics" rows="3"><?php echo h($settings['google_analytics']); ?></textarea>
                            </div>
                        </div>

                        <div>
                            <h2 class="text-xl font-semibold mb-4">Display Settings</h2>

                            <div class="form-group">
                                <label>Latest Items Count (5-20)</label>
                                <input type="number" name="latest_items_count" 
                                       value="<?php echo (int)($settings['latest_items_count'] ?? 10); ?>" 
                                       min="5" max="20" required>
                            </div>

                            <div class="form-group">
                                <label>Featured Wallpapers Count (5-20)</label>
                                <input type="number" name="featured_wallpapers_count" 
                                       value="<?php echo (int)($settings['featured_wallpapers_count'] ?? 10); ?>" 
                                       min="5" max="20" required>
                            </div>

                            <div class="form-group">
                                <label>Featured Media Count (5-20)</label>
                                <input type="number" name="featured_media_count" 
                                       value="<?php echo (int)($settings['featured_media_count'] ?? 10); ?>" 
                                       min="5" max="20" required>
                            </div>

                            <h2 class="text-xl font-semibold mb-4 mt-6">Social Media</h2>

                            <div class="form-group">
                                <label>Facebook URL</label>
                                <input type="url" name="facebook_url" value="<?php echo h($settings['facebook_url']); ?>">
                            </div>

                            <div class="form-group">
                                <label>Twitter URL</label>
                                <input type="url" name="twitter_url" value="<?php echo h($settings['twitter_url']); ?>">
                            </div>

                            <div class="form-group">
                                <label>Instagram URL</label>
                                <input type="url" name="instagram_url" value="<?php echo h($settings['instagram_url']); ?>">
                            </div>

                            <div class="form-group">
                                <label>YouTube URL</label>
                                <input type="url" name="youtube_url" value="<?php echo h($settings['youtube_url']); ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 text-center">
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
</body>

</html>
<?php include 'theme/footer.php'; ?>