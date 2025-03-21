<?php
// Set page title
$pageTitle = 'Language Management - WallPix Admin';

// Include header
require_once '../../theme/admin/header.php';

// Current date and time in UTC
$currentDateTime = '2025-03-18 13:25:22';
$currentUser = 'mahranalsarminy';

// Available interface sections (pages)
$interfaceSections = [
    'homepage' => 'Homepage',
    'profile' => 'User Profile',
    'login' => 'Login & Registration',
    'search' => 'Search Page',
    'category' => 'Category Pages',
    'media' => 'Media Details',
    'download' => 'Download Page',
    'user' => 'User Public Profile',
    'contact' => 'Contact Page',
    'about' => 'About Page',
    'footer' => 'Footer',
    'header' => 'Header & Navigation',
    'subscription' => 'Subscription Pages',
    'payment' => 'Payment Pages',
    'notification' => 'Notifications',
    'messages' => 'User Messages',
    'gallery' => 'Gallery Pages',
    'tags' => 'Tags Pages',
    'collections' => 'Collections Pages',
    'error' => 'Error Pages'
];

// Default section for new page loads
$currentSection = isset($_GET['section']) ? $_GET['section'] : 'homepage';

// Initialize actions
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$languageCode = isset($_GET['lang']) ? $_GET['lang'] : '';
$success = false;
$error = '';
$message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Add new language
        if (isset($_POST['add_language'])) {
            $newLangCode = $_POST['language_code'];
            $newLangName = $_POST['language_name'];
            $newLangDirection = $_POST['language_direction'];
            
            // Validate inputs
            if (empty($newLangCode) || empty($newLangName)) {
                throw new Exception('Language code and name are required.');
            }
            
            // Check if language code already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM languages WHERE lang_code = ?");
            $stmt->execute([$newLangCode]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('This language code already exists.');
            }
            
            // Insert new language
            $stmt = $pdo->prepare("
                INSERT INTO languages (lang_code, lang_name, lang_direction, is_active, created_at)
                VALUES (?, ?, ?, 1, NOW())
            ");
            $stmt->execute([$newLangCode, $newLangName, $newLangDirection]);
            
            // Create language directory and files
            $langPath = "../../lang/{$newLangCode}";
            if (!file_exists($langPath)) {
                mkdir($langPath, 0755, true);
            }
            
            // Create default files for each section
            foreach ($interfaceSections as $sectionKey => $sectionName) {
                $sectionPath = "{$langPath}/{$sectionKey}.php";
                if (!file_exists($sectionPath)) {
                    // Create basic language file with default English strings as template
                    $defaultContent = "<?php\n/**\n * WallPix CMS - {$sectionName} Language File ({$newLangName})\n * @version 1.0.0\n * Created: {$currentDateTime}\n * By: {$currentUser}\n */\n\n\$lang = [];\n\n// Add your translations here\n\nreturn \$lang;\n";
                    file_put_contents($sectionPath, $defaultContent);
                }
            }
            
            $success = true;
            $message = "Language '{$newLangName}' added successfully!";
        }
        
        // Update language translations
        if (isset($_POST['save_translations'])) {
            $updateLangCode = $_POST['update_lang_code'];
            $updateSection = $_POST['update_section'];
            
            // Get updated translations
            $keys = $_POST['key'];
            $values = $_POST['value'];
            
            // Prepare language file path
            $langFile = "../../lang/{$updateLangCode}/{$updateSection}.php";
            
            // Load existing translations
            $existingLang = [];
            if (file_exists($langFile)) {
                $existingLang = include $langFile;
                if (!is_array($existingLang)) {
                    $existingLang = [];
                }
            }
            
            // Update translations
            $updatedLang = $existingLang;
            for ($i = 0; $i < count($keys); $i++) {
                if (!empty($keys[$i])) {
                    $updatedLang[$keys[$i]] = $values[$i];
                }
            }
            
            // Save translations to file
            $fileContent = "<?php\n/**\n * WallPix CMS - {$interfaceSections[$updateSection]} Language File\n * @version 1.0.0\n * Last Updated: {$currentDateTime}\n * By: {$currentUser}\n */\n\n\$lang = " . var_export($updatedLang, true) . ";\n\nreturn \$lang;\n";
            file_put_contents($langFile, $fileContent);
            
            $success = true;
            $message = "Translations for {$interfaceSections[$updateSection]} ({$updateLangCode}) updated successfully!";
            
            // Update current view
            $languageCode = $updateLangCode;
            $currentSection = $updateSection;
            $action = 'edit';
        }
        
        // Update language status
        if (isset($_POST['update_status'])) {
            $langId = $_POST['language_id'];
            $status = $_POST['status'] ? 1 : 0;
            
            $stmt = $pdo->prepare("UPDATE languages SET is_active = ? WHERE id = ?");
            $stmt->execute([$status, $langId]);
            
            $success = true;
            $message = "Language status updated successfully!";
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fetch available languages
$languages = [];
try {
    $stmt = $pdo->query("
        SELECT id, lang_code, lang_name, lang_direction, is_active, created_at
        FROM languages
        ORDER BY lang_name ASC
    ");
    $languages = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Include sidebar
require_once '../../theme/admin/slidbar.php';
?>

<!-- Main Content -->
<div class="content-wrapper min-h-screen bg-gray-100 <?php echo $darkMode ? 'dark bg-gray-800 text-white' : ''; ?>">
    <div class="px-6 py-8">
<div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between">
    <div>
        <h1 class="text-3xl font-bold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
            <?php echo $lang['languages'] ?? 'Languages'; ?>
        </h1>
        <p class="mt-2 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
            <?php echo $lang['manage_languages'] ?? 'Manage languages and translations'; ?>
        </p>
    </div>
    <div class="mt-4 md:mt-0 space-y-2 md:space-y-0 md:flex md:space-x-2">
        <a href="../settings.php" class="btn block w-full md:w-auto bg-gray-500 hover:bg-gray-600 text-white text-center">
            <i class="fas fa-cog mr-2"></i> <?php echo $lang['back_to_settings'] ?? 'Back to Settings'; ?>
        </a>
        <div class="flex space-x-2 mt-2 md:mt-0">
            <a href="sync.php" class="btn flex-1 md:flex-none bg-indigo-500 hover:bg-indigo-600 text-white text-center">
                <i class="fas fa-sync-alt mr-2"></i> <?php echo $lang['sync'] ?? 'Sync'; ?>
            </a>
            <a href="export-import.php" class="btn flex-1 md:flex-none bg-green-500 hover:bg-green-600 text-white text-center">
                <i class="fas fa-exchange-alt mr-2"></i> <?php echo $lang['import_export'] ?? 'Import/Export'; ?>
            </a>
            <a href="stats.php" class="btn flex-1 md:flex-none bg-purple-500 hover:bg-purple-600 text-white text-center">
                <i class="fas fa-chart-bar mr-2"></i> <?php echo $lang['stats'] ?? 'Stats'; ?>
            </a>
        </div>
        <button onclick="toggleAddLanguageForm()" class="btn block w-full md:w-auto bg-blue-500 hover:bg-blue-600 text-white text-center mt-2 md:mt-0">
            <i class="fas fa-plus mr-2"></i> <?php echo $lang['add_language'] ?? 'Add Language'; ?>
        </button>
    </div>
</div>
        <?php if ($error): ?>
            <div class="mb-6 bg-red-100 border border-red-200 text-red-700 px-4 py-3 rounded relative <?php echo $darkMode ? 'bg-red-900 border-red-700 text-red-200' : ''; ?>">
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        
        <?php if ($success && $message): ?>
            <div class="mb-6 bg-green-100 border border-green-200 text-green-700 px-4 py-3 rounded relative <?php echo $darkMode ? 'bg-green-900 border-green-700 text-green-200' : ''; ?>">
                <span class="block sm:inline"><?php echo $message; ?></span>
            </div>
        <?php endif; ?>
        
        <!-- Add Language Form (Hidden by Default) -->
        <div id="addLanguageForm" class="mb-6 bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-700' : ''; ?> hidden">
            <h2 class="text-xl font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                <?php echo $lang['add_new_language'] ?? 'Add New Language'; ?>
            </h2>
            <form method="POST" action="">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="mb-4">
                        <label for="language_code" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?> mb-2">
                            <?php echo $lang['language_code'] ?? 'Language Code'; ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="language_code" name="language_code" 
                               class="form-input w-full rounded-md <?php echo $darkMode ? 'bg-gray-600 border-gray-500 text-white' : ''; ?>" 
                               placeholder="en, ar, fr, es..." required maxlength="5">
                        <p class="text-xs text-gray-500 mt-1 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                            2-5 characters code (ISO 639-1)
                        </p>
                    </div>
                    
                    <div class="mb-4">
                        <label for="language_name" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?> mb-2">
                            <?php echo $lang['language_name'] ?? 'Language Name'; ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="language_name" name="language_name" 
                               class="form-input w-full rounded-md <?php echo $darkMode ? 'bg-gray-600 border-gray-500 text-white' : ''; ?>" 
                               placeholder="English, Arabic, French..." required>
                    </div>
                    
                    <div class="mb-4">
                        <label for="language_direction" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?> mb-2">
                            <?php echo $lang['language_direction'] ?? 'Text Direction'; ?>
                        </label>
                        <select id="language_direction" name="language_direction"
                                class="form-select w-full rounded-md <?php echo $darkMode ? 'bg-gray-600 border-gray-500 text-white' : ''; ?>">
                            <option value="ltr">Left to Right (LTR)</option>
                            <option value="rtl">Right to Left (RTL)</option>
                        </select>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="button" onclick="toggleAddLanguageForm()" class="btn bg-gray-300 hover:bg-gray-400 text-gray-800 mr-2 <?php echo $darkMode ? 'bg-gray-600 hover:bg-gray-500 text-white' : ''; ?>">
                        <?php echo $lang['cancel'] ?? 'Cancel'; ?>
                    </button>
                    <button type="submit" name="add_language" class="btn bg-blue-500 hover:bg-blue-600 text-white">
                        <?php echo $lang['add_language'] ?? 'Add Language'; ?>
                    </button>
                </div>
            </form>
        </div>
        
        <?php if ($action === 'list'): ?>
            <!-- Language List -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-600' : ''; ?>">
                        <thead class="bg-gray-50 <?php echo $darkMode ? 'bg-gray-800' : ''; ?>">
                            <tr>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                    <?php echo $lang['language'] ?? 'Language'; ?>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                    <?php echo $lang['code'] ?? 'Code'; ?>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                    <?php echo $lang['direction'] ?? 'Direction'; ?>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                    <?php echo $lang['status'] ?? 'Status'; ?>
                                </th>
                                <th scope="col" class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                    <?php echo $lang['date_added'] ?? 'Date Added'; ?>
                                </th>
                                                                <th scope="col" class="px-6 py-3 text-right text-xs font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                    <?php echo $lang['actions'] ?? 'Actions'; ?>
                                </th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200 <?php echo $darkMode ? 'bg-gray-700 divide-gray-600' : ''; ?>">
                            <?php if (count($languages) > 0): ?>
                                <?php foreach ($languages as $language): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap <?php echo $darkMode ? 'text-white' : ''; ?>">
                                            <?php echo htmlspecialchars($language['lang_name']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap <?php echo $darkMode ? 'text-white' : ''; ?>">
                                            <?php echo htmlspecialchars($language['lang_code']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap <?php echo $darkMode ? 'text-white' : ''; ?>">
                                            <?php echo strtoupper($language['lang_direction']); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <form method="POST" action="" class="inline-block">
                                                <input type="hidden" name="language_id" value="<?php echo $language['id']; ?>">
                                                <input type="hidden" name="status" value="<?php echo $language['is_active'] ? '0' : '1'; ?>">
                                                <button type="submit" name="update_status" class="rounded-full px-3 py-1 text-xs font-medium <?php echo $language['is_active'] ? ($darkMode ? 'bg-green-900 text-green-200' : 'bg-green-100 text-green-800') : ($darkMode ? 'bg-red-900 text-red-200' : 'bg-red-100 text-red-800'); ?>">
                                                    <?php echo $language['is_active'] ? ($lang['active'] ?? 'Active') : ($lang['inactive'] ?? 'Inactive'); ?>
                                                </button>
                                            </form>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">
                                            <?php echo date('Y-m-d', strtotime($language['created_at'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="?action=edit&lang=<?php echo $language['lang_code']; ?>&section=homepage" class="text-indigo-600 hover:text-indigo-900 mr-3 <?php echo $darkMode ? 'text-indigo-400 hover:text-indigo-300' : ''; ?>">
                                                <i class="fas fa-edit"></i> <?php echo $lang['edit'] ?? 'Edit'; ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">
                                        <?php echo $lang['no_languages'] ?? 'No languages found.'; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
        <?php if ($action === 'edit' && !empty($languageCode)): ?>
            <!-- Language Edit Section -->
            <div class="bg-white rounded-lg shadow-md overflow-hidden p-6 <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
                    <div>
                        <?php 
                        // Get language name
                        $langName = '';
                        foreach ($languages as $lang) {
                            if ($lang['lang_code'] === $languageCode) {
                                $langName = $lang['lang_name'];
                                break;
                            }
                        }
                        ?>
                        <h2 class="text-xl font-semibold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                            <?php echo $lang['edit_language'] ?? 'Edit Language'; ?>: <?php echo htmlspecialchars($langName); ?> (<?php echo htmlspecialchars($languageCode); ?>)
                        </h2>
                        <p class="mt-1 text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-600'; ?>">
                            <?php echo $lang['section'] ?? 'Section'; ?>: <?php echo $interfaceSections[$currentSection] ?? $currentSection; ?>
                        </p>
                    </div>
                    
                    <div class="mt-4 md:mt-0">
                        <a href="?action=list" class="btn bg-gray-500 hover:bg-gray-600 text-white">
                            <i class="fas fa-arrow-left mr-2"></i> <?php echo $lang['back_to_languages'] ?? 'Back to Languages'; ?>
                        </a>
                    </div>
                </div>
                
                <!-- Section Tabs -->
                <div class="mb-6 border-b border-gray-200 <?php echo $darkMode ? 'border-gray-600' : ''; ?>">
                    <ul class="flex flex-wrap -mb-px text-sm font-medium">
                        <?php foreach ($interfaceSections as $sectionKey => $sectionName): ?>
                            <li class="mr-2">
                                <a href="?action=edit&lang=<?php echo $languageCode; ?>&section=<?php echo $sectionKey; ?>" 
                                   class="inline-block p-4 rounded-t-lg <?php echo ($currentSection === $sectionKey) ? 
                                        ($darkMode ? 'text-blue-300 border-b-2 border-blue-300' : 'text-blue-600 border-b-2 border-blue-600') : 
                                        ($darkMode ? 'text-gray-400 hover:text-gray-300' : 'text-gray-500 hover:text-gray-600'); ?>">
                                    <?php echo $sectionName; ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                
                <!-- Translation Form -->
                <?php
                // Load source language file (English as reference)
                $sourceLangFile = "../../lang/en/{$currentSection}.php";
                $sourceStrings = [];
                if (file_exists($sourceLangFile)) {
                    $sourceStrings = include $sourceLangFile;
                    if (!is_array($sourceStrings)) {
                        $sourceStrings = [];
                    }
                }
                
                // Load target language file
                $targetLangFile = "../../lang/{$languageCode}/{$currentSection}.php";
                $targetStrings = [];
                if (file_exists($targetLangFile)) {
                    $targetStrings = include $targetLangFile;
                    if (!is_array($targetStrings)) {
                        $targetStrings = [];
                    }
                }
                
                // Merge keys (ensure we display all available keys)
                $allKeys = array_unique(array_merge(array_keys($sourceStrings), array_keys($targetStrings)));
                sort($allKeys);
                
                // Pagination logic
                $itemsPerPage = 20; // Display 20 keys per page
                $totalItems = count($allKeys);
                $totalPages = ceil($totalItems / $itemsPerPage);
                $currentPage = isset($_GET['page']) ? max(1, min($totalPages, intval($_GET['page']))) : 1;
                $offset = ($currentPage - 1) * $itemsPerPage;
                $currentPageKeys = array_slice($allKeys, $offset, $itemsPerPage);
                ?>
                <form method="POST" action="">
                    <input type="hidden" name="update_lang_code" value="<?php echo $languageCode; ?>">
                    <input type="hidden" name="update_section" value="<?php echo $currentSection; ?>">
                    
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-600' : ''; ?>">
                            <thead class="bg-gray-50 <?php echo $darkMode ? 'bg-gray-800' : ''; ?>">
                                <tr>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider" width="40%">
                                        <?php echo $lang['key'] ?? 'Key'; ?>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider" width="30%">
                                        <?php echo $lang['english_reference'] ?? 'English Reference'; ?>
                                    </th>
                                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider" width="30%">
                                        <?php echo $lang['translation'] ?? 'Translation'; ?>
                                    </th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200 <?php echo $darkMode ? 'bg-gray-700 divide-gray-600' : ''; ?>">
                                <?php foreach ($currentPageKeys as $index => $key): ?>
                                    <tr class="<?php echo ($index % 2 === 0) ? ($darkMode ? 'bg-gray-750' : 'bg-gray-50') : ''; ?>">
                                        <td class="px-6 py-4 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-800'; ?>">
                                            <input type="text" name="key[<?php echo $index; ?>]" value="<?php echo htmlspecialchars($key); ?>" 
                                                   class="form-input w-full border-gray-300 <?php echo $darkMode ? 'bg-gray-600 border-gray-500 text-white' : ''; ?> rounded-md"
                                                   readonly>
                                        </td>
                                        <td class="px-6 py-4 text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">
                                            <?php echo isset($sourceStrings[$key]) ? htmlspecialchars($sourceStrings[$key]) : '<em class="text-gray-400">Not defined</em>'; ?>
                                        </td>
                                        <td class="px-6 py-4">
                                            <input type="text" name="value[<?php echo $index; ?>]" value="<?php echo isset($targetStrings[$key]) ? htmlspecialchars($targetStrings[$key]) : ''; ?>" 
                                                   class="form-input w-full border-gray-300 <?php echo $darkMode ? 'bg-gray-600 border-gray-500 text-white' : ''; ?> rounded-md"
                                                   placeholder="<?php echo isset($sourceStrings[$key]) ? htmlspecialchars($sourceStrings[$key]) : ''; ?>" dir="<?php echo ($languageCode === 'ar' || $languageCode === 'he') ? 'rtl' : 'ltr'; ?>">
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($totalPages > 1): ?>
                        <div class="mt-6 flex items-center justify-between border-t border-gray-200 <?php echo $darkMode ? 'border-gray-600' : ''; ?> py-3">
                            <div class="flex items-center">
                                <p class="text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                    <?php echo $lang['showing'] ?? 'Showing'; ?> 
                                    <span class="font-medium"><?php echo $offset + 1; ?></span> 
                                    <?php echo $lang['to'] ?? 'to'; ?> 
                                    <span class="font-medium"><?php echo min($offset + $itemsPerPage, $totalItems); ?></span> 
                                    <?php echo $lang['of'] ?? 'of'; ?> 
                                    <span class="font-medium"><?php echo $totalItems; ?></span> 
                                    <?php echo $lang['entries'] ?? 'entries'; ?>
                                </p>
                            </div>
                            
                            <div class="flex space-x-2">
                                <?php if ($currentPage > 1): ?>
                                    <a href="?action=edit&lang=<?php echo $languageCode; ?>&section=<?php echo $currentSection; ?>&page=<?php echo $currentPage - 1; ?>" 
                                       class="btn <?php echo $darkMode ? 'bg-gray-600 hover:bg-gray-500 text-white' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'; ?>">
                                        <?php echo $lang['previous'] ?? 'Previous'; ?>
                                    </a>
                                <?php endif; ?>
                                
                                <?php for ($i = max(1, $currentPage - 2); $i <= min($totalPages, $currentPage + 2); $i++): ?>
                                    <a href="?action=edit&lang=<?php echo $languageCode; ?>&section=<?php echo $currentSection; ?>&page=<?php echo $i; ?>" 
                                       class="btn <?php echo $i === $currentPage ? 
                                            ($darkMode ? 'bg-blue-600 hover:bg-blue-700 text-white' : 'bg-blue-100 border-blue-500 text-blue-700') : 
                                            ($darkMode ? 'bg-gray-600 hover:bg-gray-500 text-white' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'); ?>">
                                        <?php echo $i; ?>
                                    </a>
                                <?php endfor; ?>
                                
                                <?php if ($currentPage < $totalPages): ?>
                                    <a href="?action=edit&lang=<?php echo $languageCode; ?>&section=<?php echo $currentSection; ?>&page=<?php echo $currentPage + 1; ?>" 
                                       class="btn <?php echo $darkMode ? 'bg-gray-600 hover:bg-gray-500 text-white' : 'bg-white border-gray-300 text-gray-700 hover:bg-gray-50'; ?>">
                                        <?php echo $lang['next'] ?? 'Next'; ?>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <div class="mt-6 flex justify-between">
                        <div>
                            <button type="button" class="btn bg-green-500 hover:bg-green-600 text-white" onclick="addNewTranslationKey()">
                                <i class="fas fa-plus mr-2"></i> <?php echo $lang['add_key'] ?? 'Add New Key'; ?>
                            </button>
                        </div>
                        <div>
                            <button type="submit" name="save_translations" class="btn bg-blue-500 hover:bg-blue-600 text-white">
                                <i class="fas fa-save mr-2"></i> <?php echo $lang['save_translations'] ?? 'Save Translations'; ?>
                            </button>
                        </div>
                    </div>
                    
                    <!-- Hidden template for new key -->
                    <div id="newKeyTemplate" class="hidden">
                        <div class="mt-6 bg-yellow-50 p-4 rounded-md border border-yellow-200 <?php echo $darkMode ? 'bg-yellow-900 border-yellow-800 text-yellow-200' : ''; ?>">
                            <h3 class="text-lg font-medium mb-3 <?php echo $darkMode ? 'text-yellow-200' : 'text-yellow-800'; ?>">
                                <?php echo $lang['add_new_key'] ?? 'Add New Translation Key'; ?>
                            </h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium <?php echo $darkMode ? 'text-yellow-200' : 'text-yellow-700'; ?> mb-1">
                                        <?php echo $lang['key_name'] ?? 'Key Name'; ?> <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="new_key" class="form-input w-full border-yellow-300 <?php echo $darkMode ? 'bg-yellow-800 border-yellow-700 text-white' : ''; ?> rounded-md" 
                                           placeholder="e.g. welcome_message" required>
                                    <p class="text-xs mt-1 <?php echo $darkMode ? 'text-yellow-300' : 'text-yellow-600'; ?>">
                                        <?php echo $lang['key_name_help'] ?? 'Use lowercase letters, numbers and underscores only.'; ?>
                                    </p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium <?php echo $darkMode ? 'text-yellow-200' : 'text-yellow-700'; ?> mb-1">
                                        <?php echo $lang['translation_value'] ?? 'Translation Value'; ?> <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" name="new_value" class="form-input w-full border-yellow-300 <?php echo $darkMode ? 'bg-yellow-800 border-yellow-700 text-white' : ''; ?> rounded-md" 
                                           placeholder="e.g. Welcome to our website" required dir="<?php echo ($languageCode === 'ar' || $languageCode === 'he') ? 'rtl' : 'ltr'; ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </div>
</div>
<!-- JavaScript Functions -->
<script>
function toggleAddLanguageForm() {
    const form = document.getElementById('addLanguageForm');
    form.classList.toggle('hidden');
}

function addNewTranslationKey() {
    const template = document.getElementById('newKeyTemplate');
    const newKeySection = template.cloneNode(true);
    newKeySection.id = '';
    newKeySection.classList.remove('hidden');
    
    // Insert before the form's last div (which contains the save button)
    const form = template.parentNode;
    const lastDiv = form.lastElementChild;
    form.insertBefore(newKeySection, lastDiv);
    
    // Focus on the new key input
    newKeySection.querySelector('input[name="new_key"]').focus();
}

// Initialize tooltips if available
document.addEventListener('DOMContentLoaded', function () {
    if (typeof tippy !== 'undefined') {
        tippy('[data-tooltip]', {
            content: (reference) => reference.getAttribute('data-tooltip'),
            arrow: true,
            placement: 'top',
        });
    }
});
</script>

<?php
// Include footer
require_once '../../theme/admin/footer.php';
?>