<?php
/**
 * Admin Ads Management Page
 * 
 * @package WallPix
 * @version 1.0.0
 */

// Set page title
$pageTitle = 'Ads Management';

// Include header
require_once '../../theme/admin/header.php';

// Include sidebar
require_once '../../theme/admin/slidbar.php';

// Common ad sizes for reference
$adSizes = [
    'banner' => [
        'name' => 'Banner',
        'sizes' => [
            '468x60' => 'Banner (468x60)',
            '728x90' => 'Leaderboard (728x90)',
            '970x90' => 'Large Leaderboard (970x90)',
            '970x250' => 'Billboard (970x250)',
        ]
    ],
    'square' => [
        'name' => 'Square & Rectangle',
        'sizes' => [
            '200x200' => 'Small Square (200x200)',
            '250x250' => 'Square (250x250)',
            '300x250' => 'Medium Rectangle (300x250)',
            '336x280' => 'Large Rectangle (336x280)',
            '300x600' => 'Half Page (300x600)',
        ]
    ],
    'skyscraper' => [
        'name' => 'Skyscraper',
        'sizes' => [
            '120x600' => 'Skyscraper (120x600)',
            '160x600' => 'Wide Skyscraper (160x600)',
            '300x1050' => 'Portrait (300x1050)',
        ]
    ],
    'responsive' => [
        'name' => 'Responsive',
        'sizes' => [
            'responsive' => 'Responsive Ad',
            'fluid' => 'Fluid Ad',
        ]
    ],
    'mobile' => [
        'name' => 'Mobile',
        'sizes' => [
            '320x50' => 'Mobile Banner (320x50)',
            '320x100' => 'Large Mobile Banner (320x100)',
        ]
    ],
];

// Common ad positions
$adPositions = [
    'header' => 'Header',
    'before_content' => 'Before Content',
    'middle_content' => 'Middle of Content',
    'after_content' => 'After Content',
    'sidebar_top' => 'Sidebar Top',
    'sidebar_middle' => 'Sidebar Middle',
    'sidebar_bottom' => 'Sidebar Bottom',
    'footer' => 'Footer',
];

// Process form submission
$successMessage = '';
$errorMessage = '';

// Check if ads table exists, otherwise create it
try {
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'ads'")->fetchColumn();
    
    if (!$tableCheck) {
        $pdo->exec("
            CREATE TABLE `ads` (
              `id` int NOT NULL AUTO_INCREMENT,
              `name` varchar(100) NOT NULL COMMENT 'Ad name',
              `ad_code` text NOT NULL COMMENT 'JavaScript ad code',
              `position` varchar(50) DEFAULT NULL COMMENT 'Default position (header, sidebar, content, footer)',
              `size` varchar(50) NOT NULL COMMENT 'Ad size (e.g., 300x250)',
              `type` varchar(50) NOT NULL DEFAULT 'adsense' COMMENT 'Ad type (adsense, custom, etc)',
              `status` tinyint(1) NOT NULL DEFAULT '1' COMMENT 'Ad status (active/inactive)',
              `start_date` datetime DEFAULT NULL COMMENT 'Campaign start date',
              `end_date` datetime DEFAULT NULL COMMENT 'Campaign end date',
              `impressions` int DEFAULT '0' COMMENT 'Impressions counter',
              `clicks` int DEFAULT '0' COMMENT 'Estimated clicks counter',
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `position_idx` (`position`),
              KEY `status_idx` (`status`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

            CREATE TABLE `ad_placements` (
              `id` int NOT NULL AUTO_INCREMENT,
              `page` varchar(100) NOT NULL COMMENT 'Page or section where ad appears (home, category, article, etc)',
              `ad_id` int NOT NULL COMMENT 'Ad ID',
              `position` varchar(50) NOT NULL COMMENT 'Position on page',
              `priority` int DEFAULT '0' COMMENT 'Display priority',
              `is_active` tinyint(1) DEFAULT '1',
              `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY `ad_id_idx` (`ad_id`),
              KEY `page_position_idx` (`page`, `position`),
              CONSTRAINT `fk_ad_id` FOREIGN KEY (`ad_id`) REFERENCES `ads` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;
        ");
        
        // Insert sample ads
        $sampleAds = [
            [
                'name' => 'Google AdSense Header Banner',
                'ad_code' => '<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-XXXXXXXXXXXXXXXX" crossorigin="anonymous"></script>
<!-- Header Banner -->
<ins class="adsbygoogle"
     style="display:inline-block;width:728px;height:90px"
     data-ad-client="ca-pub-XXXXXXXXXXXXXXXX"
     data-ad-slot="XXXXXXXXXX"></ins>
<script>
     (adsbygoogle = window.adsbygoogle || []).push({});
</script>',
                'position' => 'header',
                'size' => '728x90',
                'type' => 'adsense',
                'status' => 1
            ],
            [
                'name' => 'Sidebar Rectangle Ad',
                'ad_code' => '<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-XXXXXXXXXXXXXXXX" crossorigin="anonymous"></script>
<!-- Sidebar Ad -->
<ins class="adsbygoogle"
     style="display:inline-block;width:300px;height:250px"
     data-ad-client="ca-pub-XXXXXXXXXXXXXXXX"
     data-ad-slot="XXXXXXXXXX"></ins>
<script>
     (adsbygoogle = window.adsbygoogle || []).push({});
</script>',
                'position' => 'sidebar_top',
                'size' => '300x250',
                'type' => 'adsense',
                'status' => 1
            ],
            [
                'name' => 'Responsive In-Content Ad',
                'ad_code' => '<script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-XXXXXXXXXXXXXXXX" crossorigin="anonymous"></script>
<!-- In-Content Ad -->
<ins class="adsbygoogle"
     style="display:block"
     data-ad-client="ca-pub-XXXXXXXXXXXXXXXX"
     data-ad-slot="XXXXXXXXXX"
     data-ad-format="auto"
     data-full-width-responsive="true"></ins>
<script>
     (adsbygoogle = window.adsbygoogle || []).push({});
</script>',
                'position' => 'middle_content',
                'size' => 'responsive',
                'type' => 'adsense',
                'status' => 1
            ],
            [
                'name' => 'Custom HTML Banner',
                'ad_code' => '<div style="width: 300px; height: 250px; background-color: #f0f0f0; border: 1px solid #ccc; display: flex; align-items: center; justify-content: center; text-align: center; padding: 10px; box-sizing: border-box;">
    <a href="https://example.com" target="_blank" style="text-decoration: none; color: #333;">
        <strong style="display: block; margin-bottom: 5px; font-size: 18px;">Your Ad Here</strong>
        <p style="margin: 0; font-size: 14px;">Click here to contact us</p>
    </a>
</div>',
                'position' => 'sidebar_bottom',
                'size' => '300x250',
                'type' => 'custom',
                'status' => 1
            ]
        ];
        
        $insertAdStmt = $pdo->prepare("
            INSERT INTO ads (name, ad_code, position, size, type, status, created_at)
            VALUES (:name, :ad_code, :position, :size, :type, :status, NOW())
        ");
        
        foreach ($sampleAds as $ad) {
            $insertAdStmt->execute([
                'name' => $ad['name'],
                'ad_code' => $ad['ad_code'],
                'position' => $ad['position'],
                'size' => $ad['size'],
                'type' => $ad['type'],
                'status' => $ad['status']
            ]);
        }
    }
} catch (Exception $e) {
    $errorMessage = 'Error checking or creating tables: ' . $e->getMessage();
}

// Process form submission - add ad
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_ad'])) {
        $name = $_POST['name'] ?? '';
        $ad_code = $_POST['ad_code'] ?? '';
        $position = $_POST['position'] ?? '';
        $size = $_POST['size'] ?? 'responsive';
        $type = $_POST['type'] ?? 'adsense';
        $status = isset($_POST['status']) ? 1 : 0;
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        
        // Validate required fields
        if (empty($name) || empty($ad_code)) {
            $errorMessage = 'Please fill in all required fields.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO ads (name, ad_code, position, size, type, status, start_date, end_date) 
                    VALUES (:name, :ad_code, :position, :size, :type, :status, :start_date, :end_date)
                ");
                
                $stmt->execute([
                    'name' => $name,
                    'ad_code' => $ad_code,
                    'position' => $position,
                    'size' => $size,
                    'type' => $type,
                    'status' => $status,
                    'start_date' => $start_date,
                    'end_date' => $end_date
                ]);
                
                // Log activity
                $activityQuery = $pdo->prepare(
                    "INSERT INTO activities (user_id, description, created_at)
                    VALUES (:user_id, :description, NOW())"
                );
                
                $activityQuery->execute([
                    'user_id' => $_SESSION['user_id'] ?? null,
                    'description' => 'Added new ad: ' . $name
                ]);
                
                $successMessage = 'Ad added successfully!';
            } catch (Exception $e) {
                $errorMessage = 'Error adding ad: ' . $e->getMessage();
            }
        }
    }
        // Process form submission - update ad
    if (isset($_POST['update_ad'])) {
        $id = $_POST['id'] ?? 0;
        $name = $_POST['name'] ?? '';
        $ad_code = $_POST['ad_code'] ?? '';
        $position = $_POST['position'] ?? '';
        $size = $_POST['size'] ?? 'responsive';
        $type = $_POST['type'] ?? 'adsense';
        $status = isset($_POST['status']) ? 1 : 0;
        $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
        $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
        
        if (empty($name) || empty($ad_code) || empty($id)) {
            $errorMessage = 'Please fill in all required fields.';
        } else {
            try {
                $stmt = $pdo->prepare("
                    UPDATE ads SET 
                    name = :name,
                    ad_code = :ad_code,
                    position = :position,
                    size = :size,
                    type = :type,
                    status = :status,
                    start_date = :start_date,
                    end_date = :end_date,
                    updated_at = NOW()
                    WHERE id = :id
                ");
                
                $stmt->execute([
                    'id' => $id,
                    'name' => $name,
                    'ad_code' => $ad_code,
                    'position' => $position,
                    'size' => $size,
                    'type' => $type,
                    'status' => $status,
                    'start_date' => $start_date,
                    'end_date' => $end_date
                ]);
                
                // Log activity
                $activityQuery = $pdo->prepare(
                    "INSERT INTO activities (user_id, description, created_at)
                    VALUES (:user_id, :description, NOW())"
                );
                
                $activityQuery->execute([
                    'user_id' => $_SESSION['user_id'] ?? null,
                    'description' => 'Updated ad: ' . $name
                ]);
                
                $successMessage = 'Ad updated successfully!';
            } catch (Exception $e) {
                $errorMessage = 'Error updating ad: ' . $e->getMessage();
            }
        }
    }
    
    // Process form submission - delete ad
    if (isset($_POST['delete_ad'])) {
        $id = $_POST['id'] ?? 0;
        
        if (empty($id)) {
            $errorMessage = 'Invalid ad ID.';
        } else {
            try {
                // Get ad name for activity log
                $nameStmt = $pdo->prepare("SELECT name FROM ads WHERE id = :id");
                $nameStmt->execute(['id' => $id]);
                $adName = $nameStmt->fetchColumn();
                
                // Delete ad
                $stmt = $pdo->prepare("DELETE FROM ads WHERE id = :id");
                $stmt->execute(['id' => $id]);
                
                // Log activity
                $activityQuery = $pdo->prepare(
                    "INSERT INTO activities (user_id, description, created_at)
                    VALUES (:user_id, :description, NOW())"
                );
                
                $activityQuery->execute([
                    'user_id' => $_SESSION['user_id'] ?? null,
                    'description' => 'Deleted ad: ' . $adName
                ]);
                
                $successMessage = 'Ad deleted successfully!';
            } catch (Exception $e) {
                $errorMessage = 'Error deleting ad: ' . $e->getMessage();
            }
        }
    }
}

// Get all ads
$ads = [];
try {
    $adsQuery = $pdo->query("SELECT * FROM ads ORDER BY created_at DESC");
    $ads = $adsQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $errorMessage = 'Error fetching ads: ' . $e->getMessage();
}

// Get existing ads for templates
$templateAds = [];
try {
    $templateQuery = $pdo->query("SELECT * FROM ads WHERE status = 1 ORDER BY name ASC");
    $templateAds = $templateQuery->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Silently handle error
}
?>

<!-- Main content container -->
<div class="content-wrapper p-4 sm:ml-64">
    <div class="p-4 mt-14">
        <div class="bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-800' : ''; ?>">
            
            <?php if ($successMessage): ?>
                <div class="p-4 mb-4 text-sm text-green-700 bg-green-100 rounded-lg dark:bg-green-200 dark:text-green-800" role="alert">
                    <span class="font-medium"><i class="fas fa-check-circle mr-2"></i> <?php echo htmlspecialchars($successMessage); ?></span>
                </div>
            <?php endif; ?>
            
            <?php if ($errorMessage): ?>
                <div class="p-4 mb-4 text-sm text-red-700 bg-red-100 rounded-lg dark:bg-red-200 dark:text-red-800" role="alert">
                    <span class="font-medium"><i class="fas fa-exclamation-circle mr-2"></i> <?php echo htmlspecialchars($errorMessage); ?></span>
                </div>
            <?php endif; ?>
                        <!-- Fixed Tab Navigation -->
            <div class="mb-6 border-b border-gray-200 <?php echo $darkMode ? 'border-gray-700' : ''; ?>">
                <ul class="flex flex-wrap -mb-px" id="adsTabs">
                    <li class="mr-2">
                        <button type="button" class="inline-block p-4 border-b-2 border-blue-600 rounded-t-lg active text-blue-600" id="manage-tab" data-target="manage-content">
                            <i class="fas fa-cog mr-2"></i> Manage Ads
                        </button>
                    </li>
                    <li class="mr-2">
                        <button type="button" class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300" id="create-tab" data-target="create-content">
                            <i class="fas fa-plus mr-2"></i> Create Ad
                        </button>
                    </li>
                    <li class="mr-2">
                        <button type="button" class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300" id="preview-tab" data-target="preview-content">
                            <i class="fas fa-eye mr-2"></i> Ad Templates
                        </button>
                    </li>
                    <li>
                        <button type="button" class="inline-block p-4 border-b-2 border-transparent rounded-t-lg hover:text-gray-600 hover:border-gray-300" id="help-tab" data-target="help-content">
                            <i class="fas fa-question-circle mr-2"></i> Help
                        </button>
                    </li>
                </ul>
            </div>
            
            <!-- Tab Content -->
            <div id="adsTabContent">
                <!-- Manage Ads Tab -->
                <div id="manage-content" class="tab-content">
                    <h2 class="text-xl font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                        <i class="fas fa-list-ul mr-2"></i> Your Ad Units
                    </h2>
                    
                    <?php if (empty($ads)): ?>
                        <div class="bg-gray-50 p-6 rounded-lg text-center <?php echo $darkMode ? 'bg-gray-700 text-gray-300' : 'text-gray-600'; ?>">
                            <i class="fas fa-info-circle text-4xl mb-2"></i>
                            <p class="text-lg mb-3">You haven't created any ads yet.</p>
                            <p class="mb-4">Start by creating your first ad unit using the "Create Ad" tab.</p>
                            <button type="button" id="createFirstAdBtn" class="text-white bg-blue-600 hover:bg-blue-700 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 mr-2 mb-2">
                                <i class="fas fa-plus mr-2"></i> Create Your First Ad
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm text-left text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                                <thead class="text-xs text-gray-700 uppercase bg-gray-50 <?php echo $darkMode ? 'bg-gray-700 text-gray-400' : ''; ?>">
                                    <tr>
                                        <th scope="col" class="px-6 py-3">Name</th>
                                        <th scope="col" class="px-6 py-3">Size</th>
                                        <th scope="col" class="px-6 py-3">Position</th>
                                        <th scope="col" class="px-6 py-3">Status</th>
                                        <th scope="col" class="px-6 py-3">Type</th>
                                        <th scope="col" class="px-6 py-3">Created</th>
                                        <th scope="col" class="px-6 py-3">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ads as $ad): ?>
                                        <tr class="bg-white border-b <?php echo $darkMode ? 'bg-gray-800 border-gray-700' : ''; ?>">
                                            <td class="px-6 py-4 font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                                <?php echo htmlspecialchars($ad['name']); ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php echo htmlspecialchars($ad['size']); ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php echo $ad['position'] ? htmlspecialchars($adPositions[$ad['position']] ?? $ad['position']) : '-'; ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php if ($ad['status']): ?>
                                                    <span class="bg-green-100 text-green-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded-full <?php echo $darkMode ? 'bg-green-900 text-green-300' : ''; ?>">
                                                        Active
                                                    </span>
                                                <?php else: ?>
                                                    <span class="bg-red-100 text-red-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded-full <?php echo $darkMode ? 'bg-red-900 text-red-300' : ''; ?>">
                                                        Inactive
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php echo ucfirst(htmlspecialchars($ad['type'])); ?>
                                            </td>
                                            <td class="px-6 py-4">
                                                <?php echo date('M j, Y', strtotime($ad['created_at'])); ?>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <button type="button" class="text-blue-600 hover:text-blue-900 mr-3 edit-ad-btn" data-id="<?php echo $ad['id']; ?>">
                                                    <i class="fas fa-edit"></i> Edit
                                                </button>
                                                <button type="button" class="text-green-600 hover:text-green-900 mr-3 view-code-btn" data-id="<?php echo $ad['id']; ?>">
                                                    <i class="fas fa-code"></i> Code
                                                </button>
                                                <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" class="inline">
                                                    <input type="hidden" name="id" value="<?php echo $ad['id']; ?>">
                                                    <button type="submit" name="delete_ad" class="text-red-600 hover:text-red-900" onclick="return confirm('Are you sure you want to delete this ad?');">
                                                        <i class="fas fa-trash"></i> Delete
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Create Ad Tab -->
                <div id="create-content" class="tab-content hidden">
                    <h2 class="text-xl font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                        <i class="fas fa-plus-circle mr-2"></i> Create New Ad Unit
                    </h2>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" id="createAdForm">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="name" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                    Ad Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="name" name="name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>" placeholder="e.g., Sidebar Banner 300x250" required>
                            </div>
                            
                            <div>
                                <label for="type" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                    Ad Type
                                </label>
                                <select id="type" name="type" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                    <option value="adsense">Google AdSense</option>
                                    <option value="custom">Custom HTML/JavaScript</option>
                                    <option value="image">Image Banner</option>
                                </select>
                            </div>
                            <div>
                                <label for="size" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                    Ad Size
                                </label>
                                <select id="size" name="size" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                    <option value="responsive">Responsive</option>
                                    <?php foreach ($adSizes as $category): ?>
                                        <optgroup label="<?php echo htmlspecialchars($category['name']); ?>">
                                            <?php foreach ($category['sizes'] as $sizeKey => $sizeName): ?>
                                                <option value="<?php echo htmlspecialchars($sizeKey); ?>"><?php echo htmlspecialchars($sizeName); ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="position" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                    Default Position
                                </label>
                                <select id="position" name="position" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                    <option value="">-- No default position --</option>
                                    <?php foreach ($adPositions as $posKey => $posName): ?>
                                        <option value="<?php echo htmlspecialchars($posKey); ?>"><?php echo htmlspecialchars($posName); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="start_date" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                    Start Date (optional)
                                </label>
                                <input type="datetime-local" id="start_date" name="start_date" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                            </div>
                            
                            <div>
                                <label for="end_date" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                    End Date (optional)
                                </label>
                                <input type="datetime-local" id="end_date" name="end_date" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                            </div>
                            
                            <div class="col-span-1 md:col-span-2">
                                <div class="flex items-center mb-2">
                                    <input id="status" name="status" type="checkbox" checked class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : ''; ?>">
                                    <label for="status" class="ml-2 text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                        Active
                                    </label>
                                </div>
                                <p class="text-xs <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    When unchecked, this ad will not be displayed on your site.
                                </p>
                            </div>
                            
                            <div class="col-span-1 md:col-span-2">
                                <label for="ad_code" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                    Ad Code <span class="text-red-500">*</span> 
                                    <span class="text-xs <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">(Paste your AdSense code or custom HTML/JavaScript)</span>
                                </label>
                                <textarea id="ad_code" name="ad_code" rows="8" class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 font-mono <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>" placeholder="<script async src=&quot;https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-XXXXXXXXXXXXXXXX&quot; crossorigin=&quot;anonymous&quot;></script>
<!-- Ad Unit Name -->
<ins class=&quot;adsbygoogle&quot;
     style=&quot;display:block&quot;
     data-ad-client=&quot;ca-pub-XXXXXXXXXXXXXXXX&quot;
     data-ad-slot=&quot;XXXXXXXXXX&quot;
     data-ad-format=&quot;auto&quot;
     data-full-width-responsive=&quot;true&quot;></ins>
<script>
     (adsbygoogle = window.adsbygoogle || []).push({});
</script>" required></textarea>
                            </div>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" name="add_ad" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center <?php echo $darkMode ? 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-800' : ''; ?>">
                                <i class="fas fa-plus mr-2"></i> Create Ad
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Ad Templates Tab -->
                <div id="preview-content" class="tab-content hidden">
                    <h2 class="text-xl font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                        <i class="fas fa-puzzle-piece mr-2"></i> Ad Templates
                    </h2>
                    
                    <p class="mb-6 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-600'; ?>">
                        Use these templates to easily add ads to your website. Click on a template to view the code snippet you can add to your pages.
                    </p>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- Template cards for different ad types -->
                        <div class="p-6 rounded-lg shadow-md cursor-pointer hover:shadow-lg transition duration-300 <?php echo $darkMode ? 'bg-gray-700 hover:bg-gray-600' : 'bg-white hover:bg-gray-50'; ?>" data-template="responsive">
                            <div class="flex items-center mb-4">
                                <div class="rounded-full p-3 mr-4 <?php echo $darkMode ? 'bg-blue-900 text-blue-200' : 'bg-blue-50 text-blue-800'; ?>">
                                    <i class="fas fa-expand-arrows-alt text-lg"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                        Responsive Ad
                                    </h3>
                                    <p class="text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">
                                        Adapts to any container width
                                    </p>
                                </div>
                            </div>
                            <div class="border <?php echo $darkMode ? 'border-gray-600' : 'border-gray-200'; ?> rounded-lg p-2 bg-gray-50 <?php echo $darkMode ? 'bg-gray-800' : ''; ?> flex items-center justify-center min-h-[80px]">
                                <div class="ad-placeholder">
                                    <span>Responsive Advertisement</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-6 rounded-lg shadow-md cursor-pointer hover:shadow-lg transition duration-300 <?php echo $darkMode ? 'bg-gray-700 hover:bg-gray-600' : 'bg-white hover:bg-gray-50'; ?>" data-template="header">
                            <div class="flex items-center mb-4">
                                <div class="rounded-full p-3 mr-4 <?php echo $darkMode ? 'bg-green-900 text-green-200' : 'bg-green-50 text-green-800'; ?>">
                                    <i class="fas fa-window-maximize text-lg"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                        Header Banner
                                    </h3>
                                    <p class="text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">
                                        Placed at the top of your page
                                    </p>
                                </div>
                            </div>
                            <div class="border <?php echo $darkMode ? 'border-gray-600' : 'border-gray-200'; ?> rounded-lg p-2 bg-gray-50 <?php echo $darkMode ? 'bg-gray-800' : ''; ?> flex items-center justify-center min-h-[80px]">
                                <div class="ad-placeholder header-placeholder">
                                    <span>Header Banner (728×90)</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="p-6 rounded-lg shadow-md cursor-pointer hover:shadow-lg transition duration-300 <?php echo $darkMode ? 'bg-gray-700 hover:bg-gray-600' : 'bg-white hover:bg-gray-50'; ?>" data-template="sidebar_top">
                            <div class="flex items-center mb-4">
                                <div class="rounded-full p-3 mr-4 <?php echo $darkMode ? 'bg-purple-900 text-purple-200' : 'bg-purple-50 text-purple-800'; ?>">
                                    <i class="fas fa-columns text-lg"></i>
                                </div>
                                <div>
                                    <h3 class="text-lg font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                        Sidebar Ad
                                    </h3>
                                    <p class="text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">
                                        Fits in your sidebar
                                    </p>
                                </div>
                            </div>
                            <div class="border <?php echo $darkMode ? 'border-gray-600' : 'border-gray-200'; ?> rounded-lg p-2 bg-gray-50 <?php echo $darkMode ? 'bg-gray-800' : ''; ?> flex items-center justify-center min-h-[80px]">
                                <div class="ad-placeholder">
                                    <span>Sidebar Ad (300×250)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Help Tab -->
                <div id="help-content" class="tab-content hidden">
                    <h2 class="text-xl font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                        <i class="fas fa-question-circle mr-2"></i> Help & Guidelines
                    </h2>
                    
                    <div class="mb-8">
                        <h3 class="text-lg font-medium mb-3 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                            <i class="fas fa-book mr-2"></i> Google AdSense Guidelines
                        </h3>
                        
                        <div class="bg-blue-50 p-4 rounded-lg mb-4 <?php echo $darkMode ? 'bg-blue-900' : ''; ?>">
                            <p class="text-blue-800 mb-2 <?php echo $darkMode ? 'text-blue-200' : ''; ?>">
                                <i class="fas fa-info-circle mr-2"></i> <strong>Important:</strong> Always follow Google AdSense program policies to avoid account suspension.
                            </p>
                        </div>
                        
                        <div class="space-y-4 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-600'; ?>">
                            <div>
                                <h4 class="font-medium mb-1 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">Ad Placement</h4>
                                <ul class="list-disc list-inside space-y-1 ml-4">
                                    <li>Don't place more than one large ad unit per page</li>
                                    <li>Maximum of 3 AdSense ads per page</li>
                                    <li>Don't place ads on pages with minimal content</li>
                                    <li>Don't place ads in pop-ups or dialogs</li>
                                    <li>Ads must not be placed on pages where the primary focus is adult, shocking, or illegal content</li>
                                </ul>
                            </div>
                            
                            <div>
                                <h4 class="font-medium mb-1 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">Ad Behavior</h4>
                                <ul class="list-disc list-inside space-y-1 ml-4">
                                    <li>Don't click on your own ads or encourage others to click them</li>
                                    <li>Don't use incentives to encourage users to click ads</li>
                                    <li>Don't modify the AdSense code in any way that affects ad delivery</li>
                                </ul>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <a href="https://support.google.com/adsense/answer/48182" target="_blank" class="text-blue-600 hover:underline <?php echo $darkMode ? 'text-blue-400' : ''; ?>">
                                <i class="fas fa-external-link-alt mr-1"></i> Read the full AdSense program policies
                            </a>
                        </div>
                    </div>
                    
                    <div class="mb-8">
                        <h3 class="text-lg font-medium mb-3 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                            <i class="fas fa-code mr-2"></i> How to Use Ad Templates
                        </h3>
                        
                        <div class="space-y-4 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-600'; ?>">
                            <p>
                                Our ad system allows you to easily embed ads in your pages using PHP functions or HTML code snippets. 
                                Here's how to use them:
                            </p>
                            
                            <div>
                                <h4 class="font-medium mb-1 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">PHP Method (Recommended)</h4>
                                <p class="mb-2">Add this to your PHP templates to display ads:</p>
                                <pre class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-3 font-mono overflow-x-auto <?php echo $darkMode ? 'bg-gray-800 border-gray-600 text-white' : ''; ?>">// Display ad by position
&lt;?php display_ad('header'); ?&gt;

// Or display specific ad by ID
&lt;?php display_ad(1); ?&gt;</pre>
                            </div>
                            
                            <div>
                                <h4 class="font-medium mb-1 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">HTML Method</h4>
                                <p class="mb-2">Use this HTML code for static pages:</p>
                                <pre class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-3 font-mono overflow-x-auto <?php echo $darkMode ? 'bg-gray-800 border-gray-600 text-white' : ''; ?>">&lt;div class="ad-container" data-ad-position="header"&gt;&lt;/div&gt;

&lt;!-- Or by specific ID --&gt;
&lt;div class="ad-container" data-ad-id="1"&gt;&lt;/div&gt;</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Edit Ad Modal -->
            <div id="editAdModal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
                <div class="fixed inset-0 bg-black opacity-50"></div>
                <div class="relative bg-white rounded-lg shadow-lg p-6 w-full max-w-4xl <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                    <button type="button" class="absolute top-3 right-3 text-gray-400 hover:text-gray-900 rounded-lg text-sm w-8 h-8 inline-flex justify-center items-center <?php echo $darkMode ? 'hover:text-white' : ''; ?>" id="closeEditAdModal">
                        <i class="fas fa-times"></i>
                        <span class="sr-only">Close</span>
                    </button>
                    
                    <h3 class="text-xl font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                        <i class="fas fa-edit mr-2"></i> Edit Ad
                    </h3>
                    
                    <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST" id="editAdForm">
                        <input type="hidden" id="edit_id" name="id">
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <label for="edit_name" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                    Ad Name <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="edit_name" name="name" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>" required>
                            </div>
                            
                            <div>
                                <label for="edit_type" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                    Ad Type
                                </label>
                                <select id="edit_type" name="type" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                    <option value="adsense">Google AdSense</option>
                                    <option value="custom">Custom HTML/JavaScript</option>
                                    <option value="image">Image Banner</option>
                                </select>
                            </div>
                            
                            <div>
                                <label for="edit_size" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                    Ad Size
                                </label>
                                <select id="edit_size" name="size" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                    <option value="responsive">Responsive</option>
                                    <?php foreach ($adSizes as $category): ?>
                                        <optgroup label="<?php echo htmlspecialchars($category['name']); ?>">
                                            <?php foreach ($category['sizes'] as $sizeKey => $sizeName): ?>
                                                <option value="<?php echo htmlspecialchars($sizeKey); ?>"><?php echo htmlspecialchars($sizeName); ?></option>
                                            <?php endforeach; ?>
                                        </optgroup>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="edit_position" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                    Default Position
                                </label>
                                <select id="edit_position" name="position" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                    <option value="">-- No default position --</option>
                                    <?php foreach ($adPositions as $posKey => $posName): ?>
                                        <option value="<?php echo htmlspecialchars($posKey); ?>"><?php echo htmlspecialchars($posName); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div>
                                <label for="edit_start_date" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                    Start Date (optional)
                                </label>
                                <input type="datetime-local" id="edit_start_date" name="start_date" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                            </div>
                            
                            <div>
                                <label for="edit_end_date" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                    End Date (optional)
                                </label>
                                <input type="datetime-local" id="edit_end_date" name="end_date" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                            </div>
                            
                            <div class="col-span-1 md:col-span-2">
                                <div class="flex items-center mb-2">
                                    <input id="edit_status" name="status" type="checkbox" class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 <?php echo $darkMode ? 'bg-gray-700 border-gray-600' : ''; ?>">
                                    <label for="edit_status" class="ml-2 text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                        Active
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-span-1 md:col-span-2">
                                <label for="edit_ad_code" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                    Ad Code <span class="text-red-500">*</span>
                                </label>
                                <textarea id="edit_ad_code" name="ad_code" rows="8" class="block p-2.5 w-full text-sm text-gray-900 bg-gray-50 rounded-lg border border-gray-300 focus:ring-blue-500 focus:border-blue-500 font-mono <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>" required></textarea>
                            </div>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="button" class="mr-2 px-4 py-2 text-sm font-medium text-gray-500 bg-gray-100 rounded-lg border border-gray-200 hover:bg-gray-200 focus:ring-4 focus:ring-gray-300 <?php echo $darkMode ? 'bg-gray-600 text-gray-300 border-gray-500 hover:bg-gray-500' : ''; ?>" id="cancelEditAdModal">
                                Cancel
                            </button>
                            <button type="submit" name="update_ad" class="text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center <?php echo $darkMode ? 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-800' : ''; ?>">
                                <i class="fas fa-save mr-2"></i> Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- View Ad Code Modal -->
            <div id="viewCodeModal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
                <div class="fixed inset-0 bg-black opacity-50"></div>
                <div class="relative bg-white rounded-lg shadow-lg p-6 w-full max-w-2xl <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                    <button type="button" class="absolute top-3 right-3 text-gray-400 hover:text-gray-900 rounded-lg text-sm w-8 h-8 inline-flex justify-center items-center <?php echo $darkMode ? 'hover:text-white' : ''; ?>" id="closeViewCodeModal">
                        <i class="fas fa-times"></i>
                        <span class="sr-only">Close</span>
                    </button>
                    
                    <h3 class="text-xl font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                        <i class="fas fa-code mr-2"></i> <span id="viewCodeAdName">Ad Code</span>
                    </h3>
                    
                    <div class="mb-6">
                        <h4 class="text-md font-medium mb-2 <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                            PHP Implementation
                        </h4>
                        <div class="relative mb-4">
                            <pre id="viewCodePHP" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-4 font-mono overflow-x-auto <?php echo $darkMode ? 'bg-gray-800 border-gray-600 text-white' : ''; ?>"></pre>
                        </div>
                        
                        <h4 class="text-md font-medium mb-2 <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                            HTML Implementation
                        </h4>
                        <div class="relative mb-4">
                            <pre id="viewCodeHTML" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-4 font-mono overflow-x-auto <?php echo $darkMode ? 'bg-gray-800 border-gray-600 text-white' : ''; ?>"></pre>
                        </div>
                        
                        <h4 class="text-md font-medium mb-2 <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                            Raw Ad Code
                        </h4>
                        <div class="relative">
                            <pre id="viewCodeRaw" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-4 font-mono overflow-x-auto <?php echo $darkMode ? 'bg-gray-800 border-gray-600 text-white' : ''; ?>"></pre>
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="button" class="px-4 py-2 text-sm font-medium text-gray-500 bg-gray-100 rounded-lg border border-gray-200 hover:bg-gray-200 focus:ring-4 focus:ring-gray-300 <?php echo $darkMode ? 'bg-gray-600 text-gray-300 border-gray-500 hover:bg-gray-500' : ''; ?>" id="cancelViewCodeModal">
                            Close
                        </button>
                    </div>
                </div>
            </div>

            <!-- Template Modal -->
            <div id="templateModal" class="fixed inset-0 flex items-center justify-center z-50 hidden">
                <div class="fixed inset-0 bg-black opacity-50"></div>
                <div class="relative bg-white rounded-lg shadow-lg p-6 w-full max-w-2xl <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                    <button type="button" class="absolute top-3 right-3 text-gray-400 hover:text-gray-900 rounded-lg text-sm w-8 h-8 inline-flex justify-center items-center <?php echo $darkMode ? 'hover:text-white' : ''; ?>" id="closeTemplateModal">
                        <i class="fas fa-times"></i>
                        <span class="sr-only">Close</span>
                    </button>
                    
                    <h3 class="text-xl font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>" id="templateTitle">
                        Ad Template
                    </h3>
                    
                    <div class="mb-6">
                        <div class="template-preview border <?php echo $darkMode ? 'border-gray-600' : 'border-gray-200'; ?> rounded-lg p-4 bg-gray-50 <?php echo $darkMode ? 'bg-gray-800' : ''; ?> flex items-center justify-center min-h-[150px] mb-4">
                            <!-- Preview will be inserted here -->
                        </div>
                        
                        <p class="text-sm mb-4 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-600'; ?>">
                            To use this template in your site, copy and paste the following code:
                        </p>
                        
                        <div class="relative">
                            <pre id="templateCode" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-4 font-mono overflow-x-auto <?php echo $darkMode ? 'bg-gray-800 border-gray-600 text-white' : ''; ?>"></pre>
                            <button type="button" id="copyCodeBtn" class="absolute top-2 right-2 text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded text-xs px-2 py-1 <?php echo $darkMode ? 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-800' : ''; ?>">
                                <i class="fas fa-copy mr-1"></i> Copy
                            </button>
                        </div>
                        
                        <!-- If using existing ads -->
                        <?php if (!empty($templateAds)): ?>
                        <div class="mt-4">
                            <label for="selectExistingAd" class="block mb-2 text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                Or use an existing ad:
                            </label>
                            <select id="selectExistingAd" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 <?php echo $darkMode ? 'bg-gray-700 border-gray-600 text-white' : ''; ?>">
                                <option value="">-- Select an ad --</option>
                                <?php foreach ($templateAds as $ad): ?>
                                    <option value="<?php echo $ad['id']; ?>"><?php echo htmlspecialchars($ad['name']); ?> (<?php echo htmlspecialchars($ad['size']); ?>)</option>
                                <?php endforeach; ?>
                            </select>
                            <div class="mt-2" id="existingAdCodeContainer" style="display: none;">
                                <pre id="existingAdCode" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg p-4 font-mono overflow-x-auto <?php echo $darkMode ? 'bg-gray-800 border-gray-600 text-white' : ''; ?>"></pre>
                                <button type="button" id="copyExistingCodeBtn" class="mt-2 text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:ring-blue-300 font-medium rounded-lg text-xs px-3 py-1.5 <?php echo $darkMode ? 'bg-blue-600 hover:bg-blue-700 focus:ring-blue-800' : ''; ?>">
                                    <i class="fas fa-copy mr-1"></i> Copy Code
                                </button>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="button" class="px-4 py-2 text-sm font-medium text-gray-500 bg-gray-100 rounded-lg border border-gray-200 hover:bg-gray-200 focus:ring-4 focus:ring-gray-300 <?php echo $darkMode ? 'bg-gray-600 text-gray-300 border-gray-500 hover:bg-gray-500' : ''; ?>" id="cancelTemplateModal">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Ad Templates Styling */
.ad-container {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 100%;
    font-family: Arial, sans-serif;
    position: relative;
}
.ad-placeholder {
    background: repeating-linear-gradient(
        45deg,
        <?php echo $darkMode ? '#333333' : '#f0f0f0'; ?>,
        <?php echo $darkMode ? '#333333' : '#f0f0f0'; ?> 10px,
        <?php echo $darkMode ? '#444444' : '#f9f9f9'; ?> 10px,
        <?php echo $darkMode ? '#444444' : '#f9f9f9'; ?> 20px
    );
    border: 1px dashed <?php echo $darkMode ? '#555555' : '#cccccc'; ?>;
    color: <?php echo $darkMode ? '#bbbbbb' : '#666666'; ?>;
    width: 100%;
    height: 100%;
    min-height: 60px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    padding: 0.5rem;
    text-align: center;
}
.header-placeholder {
    min-height: 90px;
    max-width: 728px;
}
.mobile-placeholder {
    min-height: 50px;
    max-width: 320px;
}
.responsive-ad {
    width: 100%;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching functionality
    const tabButtons = document.querySelectorAll('#adsTabs button');
    const tabContents = document.querySelectorAll('.tab-content');
    console.log('Tab Buttons:', tabButtons);
console.log('Tab Contents:', tabContents);

tabButtons.forEach(button => {
    console.log('Button Target:', button.getAttribute('data-target'));
});
    
    // Set up tab click event
    tabButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const targetId = this.getAttribute('data-target');
            
            // Deactivate all tabs
            tabButtons.forEach(tab => {
                tab.classList.remove('text-blue-600', 'border-blue-600', 'active');
                tab.classList.add('border-transparent', 'hover:text-gray-600', 'hover:border-gray-300');
            });
            
            // Hide all tab contents
            tabContents.forEach(content => {
                content.classList.add('hidden');
            });
            
            // Activate current tab
            this.classList.add('text-blue-600', 'border-blue-600', 'active');
            this.classList.remove('border-transparent', 'hover:text-gray-600', 'hover:border-gray-300');
            
            // Show current tab content
            document.getElementById(targetId).classList.remove('hidden');
        });
    });
    
    // Handle create first ad button
    const createFirstAdBtn = document.getElementById('createFirstAdBtn');
    if (createFirstAdBtn) {
        createFirstAdBtn.addEventListener('click', function() {
            // Find and click the Create Ad tab
            document.getElementById('create-tab').click();
        });
    }
    
    // View Code Modal functionality
    const viewCodeModal = document.getElementById('viewCodeModal');
    const viewCodeBtns = document.querySelectorAll('.view-code-btn');
    
    if (viewCodeBtns.length > 0) {
        viewCodeBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const adId = this.getAttribute('data-id');
                
                // Fetch ad data via AJAX
                fetch(`index.php?action=get_ad&id=${adId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const ad = data.ad;
                            document.getElementById('viewCodeAdName').textContent = ad.name;
                            document.getElementById('viewCodePHP').textContent = `<?php display_ad(${adId}); // Display "${ad.name}" ?>`;
                            document.getElementById('viewCodeHTML').textContent = `<div class="ad-container" data-ad-id="${adId}"></div>`;
                            document.getElementById('viewCodeRaw').textContent = ad.ad_code;
                            
                            // Show the modal
                            viewCodeModal.classList.remove('hidden');
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to fetch ad data');
                    });
            });
        });
    }
    
    // Edit Ad functionality
    const editAdModal = document.getElementById('editAdModal');
    const editAdBtns = document.querySelectorAll('.edit-ad-btn');
    
    if (editAdBtns.length > 0) {
        editAdBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                const adId = this.getAttribute('data-id');
                
                // Fetch ad data via AJAX
                fetch(`index.php?action=get_ad&id=${adId}`)
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            const ad = data.ad;
                            
                            // Fill in the form
                            document.getElementById('edit_id').value = ad.id;
                            document.getElementById('edit_name').value = ad.name;
                            document.getElementById('edit_type').value = ad.type;
                            document.getElementById('edit_size').value = ad.size;
                            document.getElementById('edit_position').value = ad.position || '';
                            document.getElementById('edit_ad_code').value = ad.ad_code;
                            
                            // Handle dates
                            if (ad.start_date) {
                                // Convert to datetime-local format (YYYY-MM-DDThh:mm)
                                document.getElementById('edit_start_date').value = ad.start_date.substr(0, 16);
                            } else {
                                document.getElementById('edit_start_date').value = '';
                            }
                            
                            if (ad.end_date) {
                                document.getElementById('edit_end_date').value = ad.end_date.substr(0, 16);
                            } else {
                                document.getElementById('edit_end_date').value = '';
                            }
                            
                            // Handle status checkbox
                            document.getElementById('edit_status').checked = ad.status == 1;
                            
                            // Show the modal
                            editAdModal.classList.remove('hidden');
                        } else {
                            alert('Error: ' + data.message);
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Failed to fetch ad data');
                    });
            });
        });
    }
    
    // Close modal buttons
    document.querySelectorAll('.fixed button[type="button"]').forEach(button => {
        if (button.classList.contains('absolute') || button.textContent.trim() === 'Close' || button.textContent.trim() === 'Cancel') {
            button.addEventListener('click', function() {
                // Find the parent modal
                const modal = this.closest('.fixed');
                if (modal) {
                    modal.classList.add('hidden');
                }
            });
        }
    });
    
    // Template Modals
    const templateModal = document.getElementById('templateModal');
    const templateCards = document.querySelectorAll('.cursor-pointer');
    
    templateCards.forEach(card => {
        card.addEventListener('click', function() {
            const title = this.querySelector('h3').textContent.trim();
            document.getElementById('templateTitle').textContent = title + ' Template';
            
            // Copy preview content
            const preview = this.querySelector('.ad-placeholder').cloneNode(true);
            const previewContainer = document.querySelector('.template-preview');
            previewContainer.innerHTML = '';
            previewContainer.appendChild(preview);
            
            // Set template code based on type
            let templateCode = '';
            if (title.includes('Responsive')) {
                templateCode = `<!-- Responsive Ad -->
<div class="ad-container responsive-ad">
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-XXXXXXXX" crossorigin="anonymous"></script>
    <ins class="adsbygoogle"
        style="display:block"
        data-ad-client="ca-pub-XXXXXXXX"
        data-ad-slot="XXXXXXXX"
        data-ad-format="auto"
        data-full-width-responsive="true"></ins>
    <script>
        (adsbygoogle = window.adsbygoogle || []).push({});
    </script>
</div>`;
            } else if (title.includes('Header')) {
                templateCode = `<!-- Header Banner -->
<div class="ad-container header-banner">
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-XXXXXXXX" crossorigin="anonymous"></script>
    <ins class="adsbygoogle"
        style="display:inline-block;width:728px;height:90px"
        data-ad-client="ca-pub-XXXXXXXX"
        data-ad-slot="XXXXXXXX"></ins>
    <script>
        (adsbygoogle = window.adsbygoogle || []).push({});
    </script>
</div>`;
            } else if (title.includes('Sidebar')) {
                templateCode = `<!-- Sidebar Ad -->
<div class="ad-container sidebar-ad">
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-XXXXXXXX" crossorigin="anonymous"></script>
    <ins class="adsbygoogle"
        style="display:inline-block;width:300px;height:250px"
        data-ad-client="ca-pub-XXXXXXXX"
        data-ad-slot="XXXXXXXX"></ins>
    <script>
        (adsbygoogle = window.adsbygoogle || []).push({});
    </script>
</div>`;
            }
            
            document.getElementById('templateCode').textContent = templateCode;
            templateModal.classList.remove('hidden');
        });
    });
    
    // Copy code functionality
    const copyCodeBtn = document.getElementById('copyCodeBtn');
    if (copyCodeBtn) {
        copyCodeBtn.addEventListener('click', function() {
            const code = document.getElementById('templateCode').textContent;
            navigator.clipboard.writeText(code).then(() => {
                this.textContent = 'Copied!';
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-copy mr-1"></i> Copy';
                }, 2000);
            });
        });
    }
    
    // Existing ad selection
    const selectExistingAd = document.getElementById('selectExistingAd');
    if (selectExistingAd) {
        selectExistingAd.addEventListener('change', function() {
            const adId = this.value;
            const container = document.getElementById('existingAdCodeContainer');
            
            if (adId) {
                const code = `<?php display_ad(${adId}); // Display selected ad ?>`;
                document.getElementById('existingAdCode').textContent = code;
                container.style.display = 'block';
            } else {
                container.style.display = 'none';
            }
        });
    }
    
    // Copy existing ad code functionality
    const copyExistingCodeBtn = document.getElementById('copyExistingCodeBtn');
    if (copyExistingCodeBtn) {
        copyExistingCodeBtn.addEventListener('click', function() {
            const code = document.getElementById('existingAdCode').textContent;
            navigator.clipboard.writeText(code).then(() => {
                this.textContent = 'Copied!';
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-copy mr-1"></i> Copy Code';
                }, 2000);
            });
        });
    }
});
</script>

<?php
// Handle AJAX requests for ad data
if (isset($_GET['action']) && $_GET['action'] === 'get_ad' && isset($_GET['id'])) {
    $adId = (int)$_GET['id'];
    
    try {
        $stmt = $pdo->prepare("SELECT * FROM ads WHERE id = :id");
        $stmt->execute(['id' => $adId]);
        $ad = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($ad) {
            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'ad' => $ad]);
        } else {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'message' => 'Ad not found']);
        }
        
        exit;
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Error fetching ad: ' . $e->getMessage()]);
        exit;
    }
}

// Include footer
require_once '../../theme/admin/footer.php';
?>        