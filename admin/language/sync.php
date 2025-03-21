<?php
/**
 * WallPix CMS - Language Synchronization Tool
 * This file handles synchronization of language keys across all language files
 * 
 * @version 1.0.0
 * @author WallPix Team
 * @copyright 2025 WallPix
 * Last Updated: 2025-03-18 13:45:57
 * Updated By: mahranalsarminy
 */

// Set page title
$pageTitle = 'Language Sync Tool - WallPix Admin';

// Include header
require_once '../../theme/admin/header.php';

// Current date and time in UTC
$currentDateTime = '2025-03-18 13:45:57';
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

// Initialize variables
$success = false;
$error = '';
$message = '';
$syncResults = [];
$sourceLanguage = isset($_POST['source_language']) ? $_POST['source_language'] : 'en';
$targetLanguage = isset($_POST['target_language']) ? $_POST['target_language'] : '';

// Fetch available languages
$languages = [];
try {
    $stmt = $pdo->query("
        SELECT id, lang_code, lang_name, lang_direction, is_active
        FROM languages
        ORDER BY lang_name ASC
    ");
    $languages = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}
// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (isset($_POST['sync_languages'])) {
            // Verify source language exists
            if (!file_exists("../../lang/{$sourceLanguage}")) {
                throw new Exception("Source language directory does not exist.");
            }
            
            // Get target languages array
            $targetsToSync = [];
            if (!empty($targetLanguage)) {
                // Sync specific target language
                if (!file_exists("../../lang/{$targetLanguage}")) {
                    throw new Exception("Target language directory does not exist.");
                }
                $targetsToSync = [$targetLanguage];
            } else {
                // Sync all languages except source
                $langDirs = glob("../../lang/*", GLOB_ONLYDIR);
                foreach ($langDirs as $dir) {
                    $langCode = basename($dir);
                    if ($langCode !== $sourceLanguage) {
                        $targetsToSync[] = $langCode;
                    }
                }
            }
            
            // Initialize sync results
            foreach ($targetsToSync as $target) {
                $syncResults[$target] = [
                    'name' => '',
                    'sections' => [],
                    'added_keys' => 0,
                    'total_keys' => 0,
                    'missing_keys' => 0
                ];
                
                // Get language name
                foreach ($languages as $lang) {
                    if ($lang['lang_code'] === $target) {
                        $syncResults[$target]['name'] = $lang['lang_name'];
                        break;
                    }
                }
            }
            
            // Process each section
            foreach ($interfaceSections as $sectionKey => $sectionName) {
                $sourceFile = "../../lang/{$sourceLanguage}/{$sectionKey}.php";
                
                // Skip if source file doesn't exist
                if (!file_exists($sourceFile)) {
                    continue;
                }
                
                // Load source keys
                $sourceStrings = include $sourceFile;
                if (!is_array($sourceStrings)) {
                    $sourceStrings = [];
                }
                
                // For each target language
                foreach ($targetsToSync as $target) {
                    $targetFile = "../../lang/{$target}/{$sectionKey}.php";
                    $targetDir = dirname($targetFile);
                    
                    // Create target directory if it doesn't exist
                    if (!file_exists($targetDir)) {
                        mkdir($targetDir, 0755, true);
                    }
                    
                    // Load target file if exists
                    $targetStrings = [];
                    if (file_exists($targetFile)) {
                        $targetStrings = include $targetFile;
                        if (!is_array($targetStrings)) {
                            $targetStrings = [];
                        }
                    }
                    
                    // Calculate metrics
                    $initialKeyCount = count($targetStrings);
                    $sourceKeyCount = count($sourceStrings);
                    $missingKeys = [];
                    
                    // Find missing keys in target
                    foreach ($sourceStrings as $key => $value) {
                        if (!isset($targetStrings[$key])) {
                            $targetStrings[$key] = ''; // Add empty string as placeholder
                            $missingKeys[] = $key;
                        }
                    }
                    
                    // Save updated target file
                    $fileContent = "<?php\n/**\n * WallPix CMS - {$sectionName} Language File ({$target})\n * @version 1.0.0\n * Last Updated: {$currentDateTime}\n * By: {$currentUser}\n */\n\n\$lang = " . var_export($targetStrings, true) . ";\n\nreturn \$lang;\n";
                    file_put_contents($targetFile, $fileContent);
                    
                    // Record section results
                    $syncResults[$target]['sections'][$sectionKey] = [
                        'name' => $sectionName,
                        'total_keys' => $sourceKeyCount,
                        'added_keys' => count($missingKeys),
                        'missing_keys' => $missingKeys
                    ];
                    
                    // Update overall stats
                    $syncResults[$target]['added_keys'] += count($missingKeys);
                    $syncResults[$target]['total_keys'] += $sourceKeyCount;
                    $syncResults[$target]['missing_keys'] += count($missingKeys);
                }
            }
            
            $success = true;
            $message = "Language synchronization completed successfully.";
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
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
                    <?php echo $lang['language_sync_tool'] ?? 'Language Synchronization Tool'; ?>
                </h1>
                <p class="mt-2 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                    <?php echo $lang['language_sync_description'] ?? 'Synchronize translation keys across all language files'; ?>
                </p>
            </div>
            <div class="mt-4 md:mt-0 flex space-x-2">
                <a href="index.php" class="btn bg-gray-500 hover:bg-gray-600 text-white">
                    <i class="fas fa-arrow-left mr-2"></i> <?php echo $lang['back_to_languages'] ?? 'Back to Languages'; ?>
                </a>
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
        
        <!-- Sync Configuration Form -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6 <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
            <h2 class="text-xl font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                <?php echo $lang['sync_configuration'] ?? 'Sync Configuration'; ?>
            </h2>
            <form method="POST" action="">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="mb-4">
                        <label for="source_language" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?> mb-2">
                            <?php echo $lang['source_language'] ?? 'Source Language'; ?> <span class="text-red-500">*</span>
                        </label>
                        <select id="source_language" name="source_language" 
                                class="form-select w-full rounded-md <?php echo $darkMode ? 'bg-gray-600 border-gray-500 text-white' : ''; ?>" required>
                            <?php foreach ($languages as $language): ?>
                                <option value="<?php echo $language['lang_code']; ?>" <?php echo $language['lang_code'] === $sourceLanguage ? 'selected' : ''; ?>>
                                    <?php echo $language['lang_name'] . ' (' . $language['lang_code'] . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-1 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                            <?php echo $lang['source_language_help'] ?? 'The language from which to copy missing keys'; ?>
                        </p>
                    </div>
                    
                    <div class="mb-4">
                        <label for="target_language" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?> mb-2">
                            <?php echo $lang['target_language'] ?? 'Target Language'; ?>
                        </label>
                        <select id="target_language" name="target_language" 
                                class="form-select w-full rounded-md <?php echo $darkMode ? 'bg-gray-600 border-gray-500 text-white' : ''; ?>">
                            <option value=""><?php echo $lang['all_languages'] ?? 'All Languages'; ?></option>
                            <?php foreach ($languages as $language): ?>
                                <?php if ($language['lang_code'] !== $sourceLanguage): ?>
                                    <option value="<?php echo $language['lang_code']; ?>" <?php echo $language['lang_code'] === $targetLanguage ? 'selected' : ''; ?>>
                                        <?php echo $language['lang_name'] . ' (' . $language['lang_code'] . ')'; ?>
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                        <p class="text-xs text-gray-500 mt-1 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                            <?php echo $lang['target_language_help'] ?? 'Leave blank to sync all languages'; ?>
                        </p>
                    </div>
                </div>
                
                <div class="flex justify-end mt-4">
                    <button type="submit" name="sync_languages" class="btn bg-blue-500 hover:bg-blue-600 text-white">
                        <i class="fas fa-sync-alt mr-2"></i> <?php echo $lang['start_synchronization'] ?? 'Start Synchronization'; ?>
                    </button>
                </div>
            </form>
        </div>
        <!-- Sync Results -->
        <?php if ($success && !empty($syncResults)): ?>
            <div class="bg-white rounded-lg shadow-md p-6 mb-6 <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                <h2 class="text-xl font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                    <?php echo $lang['sync_results'] ?? 'Synchronization Results'; ?>
                </h2>
                
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <?php foreach ($syncResults as $langCode => $result): ?>
                        <div class="border rounded-lg <?php echo $darkMode ? 'border-gray-600' : 'border-gray-200'; ?>">
                            <div class="px-4 py-3 border-b <?php echo $darkMode ? 'border-gray-600 bg-gray-750' : 'border-gray-200 bg-gray-50'; ?>">
                                <h3 class="font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                                    <?php echo $result['name']; ?> (<?php echo $langCode; ?>)
                                </h3>
                            </div>
                            <div class="p-4">
                                <div class="flex items-center justify-between mb-3">
                                    <div class="text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-600'; ?>">
                                        <span class="font-medium"><?php echo $result['total_keys']; ?></span> total keys
                                    </div>
                                    <div class="text-sm <?php echo $result['added_keys'] > 0 ? ($darkMode ? 'text-yellow-300' : 'text-yellow-600') : ($darkMode ? 'text-green-300' : 'text-green-600'); ?>">
                                        <span class="font-medium"><?php echo $result['added_keys']; ?></span> keys added
                                    </div>
                                </div>
                                
                                <?php if ($result['added_keys'] > 0): ?>
                                    <div class="mt-4">
                                        <h4 class="text-sm font-medium mb-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                                            <?php echo $lang['sections_updated'] ?? 'Sections Updated'; ?>:
                                        </h4>
                                        <ul class="space-y-2">
                                            <?php foreach ($result['sections'] as $sectionKey => $section): ?>
                                                <?php if ($section['added_keys'] > 0): ?>
                                                    <li class="text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-600'; ?>">
                                                        <div class="flex items-center justify-between">
                                                            <div>
                                                                <span class="font-medium"><?php echo $section['name']; ?></span>
                                                            </div>
                                                            <div>
                                                                <span class="text-xs px-2 py-1 rounded-full <?php echo $darkMode ? 'bg-yellow-900 text-yellow-200' : 'bg-yellow-100 text-yellow-800'; ?>">
                                                                    +<?php echo $section['added_keys']; ?> keys
                                                                </span>
                                                            </div>
                                                        </div>
                                                        
                                                        <?php if (!empty($section['missing_keys'])): ?>
                                                            <div class="mt-1 pl-4 text-xs <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                                                <?php echo implode(', ', array_slice($section['missing_keys'], 0, 5)); ?>
                                                                <?php if (count($section['missing_keys']) > 5): ?>
                                                                    <span class="text-xs <?php echo $darkMode ? 'text-gray-500' : 'text-gray-400'; ?>">
                                                                        and <?php echo count($section['missing_keys']) - 5; ?> more...
                                                                    </span>
                                                                <?php endif; ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </li>
                                                <?php endif; ?>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                <?php else: ?>
                                    <div class="text-sm italic <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                        <?php echo $lang['no_missing_keys'] ?? 'No missing keys were found. This language is fully synchronized.'; ?>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="mt-4 text-right">
                                    <a href="index.php?action=edit&lang=<?php echo $langCode; ?>&section=homepage" class="text-sm text-blue-600 hover:text-blue-800 <?php echo $darkMode ? 'text-blue-400 hover:text-blue-300' : ''; ?>">
                                        <i class="fas fa-edit mr-1"></i> <?php echo $lang['edit_translations'] ?? 'Edit Translations'; ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- Sync Information -->
        <div class="bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
            <h2 class="text-xl font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                <?php echo $lang['about_language_sync'] ?? 'About Language Synchronization'; ?>
            </h2>
            
            <div class="prose max-w-none <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                <p>
                    The language synchronization tool helps keep all language files updated with the latest translation keys. When you add new content or features to your site, you might add new translation keys to your primary language (usually English). This tool ensures that those keys are also available in all other languages.
                </p>
                <h3 class="<?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">How it works:</h3>
                <ol>
                    <li>The tool scans the source language files for all translation keys.</li>
                    <li>It then checks each target language file for missing keys.</li>
                    <li>Any missing keys are added to the target language files with empty values.</li>
                    <li>Translators can then fill in the missing translations through the language editor.</li>
                </ol>
                <p>
                    <strong>Note:</strong> This tool only adds missing keys; it doesn't overwrite existing translations or remove keys that may be in target languages but not in the source.
                </p>
            </div>
        </div>
    </div>
</div>

<?php
// Include footer
require_once '../../theme/admin/footer.php';
?>        
        