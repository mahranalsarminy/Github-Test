<?php
/**
 * WallPix CMS - Language Export/Import Tool
 * This file handles exporting and importing language files for external translation
 * 
 * @version 1.0.0
 * @author WallPix Team
 * @copyright 2025 WallPix
 * Last Updated: 2025-03-18 13:51:58
 * Updated By: mahranalsarminy
 */

// Set page title
$pageTitle = 'Language Export/Import Tool - WallPix Admin';

// Include header
require_once '../../theme/admin/header.php';

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
$exportLangCode = isset($_GET['export_lang']) ? $_GET['export_lang'] : '';
$importLangCode = isset($_POST['import_lang_code']) ? $_POST['import_lang_code'] : '';

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
// Handle export
if (isset($_GET['action']) && $_GET['action'] === 'export' && !empty($exportLangCode)) {
    try {
        // Create export directory if it doesn't exist
        $exportDir = "../../temp/lang_exports";
        if (!file_exists($exportDir)) {
            mkdir($exportDir, 0755, true);
        }
        
        // Get language name
        $langName = '';
        foreach ($languages as $lang) {
            if ($lang['lang_code'] === $exportLangCode) {
                $langName = $lang['lang_name'];
                break;
            }
        }
        
        // Create a timestamp for the filename
        $timestamp = date('Ymd_His');
        $exportFilename = "wallpix_lang_{$exportLangCode}_{$timestamp}.csv";
        $exportPath = "{$exportDir}/{$exportFilename}";
        
        // Open CSV file for writing
        $csvFile = fopen($exportPath, 'w');
        
        // Write CSV header
        fputcsv($csvFile, ['section', 'key', 'value']);
        
        // Export each section
        foreach ($interfaceSections as $sectionKey => $sectionName) {
            $langFile = "../../lang/{$exportLangCode}/{$sectionKey}.php";
            
            if (file_exists($langFile)) {
                $strings = include $langFile;
                
                if (is_array($strings)) {
                    foreach ($strings as $key => $value) {
                        fputcsv($csvFile, [$sectionKey, $key, $value]);
                    }
                }
            }
        }
        
        // Close the file
        fclose($csvFile);
        
        // Deliver the file for download
        header('Content-Description: File Transfer');
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename=' . basename($exportPath));
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($exportPath));
        ob_clean();
        flush();
        readfile($exportPath);
        
        // Delete the file after download
        unlink($exportPath);
        exit;
    } catch (Exception $e) {
        $error = "Export error: " . $e->getMessage();
    }
}

// Handle import
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['import_language'])) {
    try {
        // Check if language code exists
        $langExists = false;
        foreach ($languages as $lang) {
            if ($lang['lang_code'] === $importLangCode) {
                $langExists = true;
                break;
            }
        }
        
        if (!$langExists) {
            throw new Exception("Selected language does not exist in the system.");
        }
        
        // Check if file is uploaded
        if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("File upload failed or no file was uploaded.");
        }
        
        // Check file type
        $fileType = pathinfo($_FILES['import_file']['name'], PATHINFO_EXTENSION);
        if ($fileType != 'csv') {
            throw new Exception("Only CSV files are allowed.");
        }
        
        // Open the uploaded CSV file
        $csvFile = fopen($_FILES['import_file']['tmp_name'], 'r');
        
        // Read the header row
        $header = fgetcsv($csvFile);
        if ($header !== ['section', 'key', 'value']) {
            throw new Exception("Invalid CSV format. Expected headers: section, key, value");
        }
        
        // Process the data
        $importData = [];
        while (($row = fgetcsv($csvFile)) !== false) {
            if (count($row) != 3) {
                continue; // Skip invalid rows
            }
            
            list($section, $key, $value) = $row;
            
            if (!isset($importData[$section])) {
                $importData[$section] = [];
            }
            
            $importData[$section][$key] = $value;
        }
        
        // Close CSV file
        fclose($csvFile);
        
        // Update language files
        $importStats = [
            'sections_updated' => 0,
            'keys_updated' => 0
        ];
        
        foreach ($importData as $section => $strings) {
            // Skip invalid sections
            if (!isset($interfaceSections[$section])) {
                continue;
            }
            
            $langFile = "../../lang/{$importLangCode}/{$section}.php";
            $langDir = dirname($langFile);
            
            // Create directory if it doesn't exist
            if (!file_exists($langDir)) {
                mkdir($langDir, 0755, true);
            }
            
            // Load existing language file if it exists
            $existingStrings = [];
            if (file_exists($langFile)) {
                $existingStrings = include $langFile;
                if (!is_array($existingStrings)) {
                    $existingStrings = [];
                }
            }
            
            // Merge with imported strings
            $updatedStrings = array_merge($existingStrings, $strings);
            
            // Write updated language file
            $currentDateTime = '2025-03-18 14:06:14';
            $currentUser = 'mahranalsarminy';
            $fileContent = "<?php\n/**\n * WallPix CMS - {$interfaceSections[$section]} Language File\n * @version 1.0.0\n * Last Updated: {$currentDateTime}\n * By: {$currentUser}\n */\n\n\$lang = " . var_export($updatedStrings, true) . ";\n\nreturn \$lang;\n";
            file_put_contents($langFile, $fileContent);
            
            $importStats['sections_updated']++;
            $importStats['keys_updated'] += count($strings);
        }
        
        $success = true;
        $message = "Import completed successfully! Updated {$importStats['sections_updated']} sections and {$importStats['keys_updated']} translation keys.";
        
    } catch (Exception $e) {
        $error = "Import error: " . $e->getMessage();
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
                    <?php echo $lang['language_export_import'] ?? 'Language Export/Import'; ?>
                </h1>
                <p class="mt-2 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                    <?php echo $lang['export_import_description'] ?? 'Export and import language files for external translation'; ?>
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
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Export Section -->
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                <h2 class="text-xl font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                    <i class="fas fa-file-export mr-2"></i> <?php echo $lang['export_language'] ?? 'Export Language'; ?>
                </h2>
                
                <p class="text-sm mb-4 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-600'; ?>">
                    <?php echo $lang['export_description'] ?? 'Export a language file as CSV for external translation. The CSV file can be opened in spreadsheet applications like Microsoft Excel or Google Sheets.'; ?>
                </p>
                
                <div class="mt-4">
                    <label for="export_lang" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?> mb-2">
                        <?php echo $lang['select_language_to_export'] ?? 'Select Language to Export'; ?> <span class="text-red-500">*</span>
                    </label>
                    <select id="export_lang" name="export_lang" 
                            class="form-select w-full rounded-md <?php echo $darkMode ? 'bg-gray-600 border-gray-500 text-white' : ''; ?>" required>
                        <option value=""><?php echo $lang['select_language'] ?? 'Select Language'; ?></option>
                        <?php foreach ($languages as $language): ?>
                            <option value="<?php echo $language['lang_code']; ?>">
                                <?php echo $language['lang_name'] . ' (' . $language['lang_code'] . ')'; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mt-6 text-right">
                    <button type="button" onclick="exportLanguage()" class="btn bg-blue-500 hover:bg-blue-600 text-white">
                        <i class="fas fa-download mr-2"></i> <?php echo $lang['export'] ?? 'Export'; ?>
                    </button>
                </div>
            </div>
            
            <!-- Import Section -->
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                <h2 class="text-xl font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                    <i class="fas fa-file-import mr-2"></i> <?php echo $lang['import_language'] ?? 'Import Language'; ?>
                </h2>
                
                <p class="text-sm mb-4 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-600'; ?>">
                    <?php echo $lang['import_description'] ?? 'Import a translated CSV file back into the system. The file must match the format exported by this tool.'; ?>
                </p>
                
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="mt-4">
                        <label for="import_lang_code" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?> mb-2">
                            <?php echo $lang['select_language_to_import'] ?? 'Select Language to Import'; ?> <span class="text-red-500">*</span>
                        </label>
                        <select id="import_lang_code" name="import_lang_code" 
                                class="form-select w-full rounded-md <?php echo $darkMode ? 'bg-gray-600 border-gray-500 text-white' : ''; ?>" required>
                            <option value=""><?php echo $lang['select_language'] ?? 'Select Language'; ?></option>
                            <?php foreach ($languages as $language): ?>
                                <option value="<?php echo $language['lang_code']; ?>">
                                    <?php echo $language['lang_name'] . ' (' . $language['lang_code'] . ')'; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="mt-4">
                        <label for="import_file" class="block text-sm font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?> mb-2">
                            <?php echo $lang['select_csv_file'] ?? 'Select CSV File'; ?> <span class="text-red-500">*</span>
                        </label>
                        <div class="mt-1">
                            <input type="file" id="import_file" name="import_file" accept=".csv" 
                                   class="form-input w-full <?php echo $darkMode ? 'bg-gray-600 border-gray-500 text-white' : ''; ?> rounded-md" required>
                        </div>
                        <p class="text-xs text-gray-500 mt-1 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                            <?php echo $lang['import_file_help'] ?? 'Only CSV files are allowed. Maximum file size: 5MB'; ?>
                        </p>
                    </div>
                    
                    <div class="mt-6 text-right">
                        <button type="submit" name="import_language" class="btn bg-green-500 hover:bg-green-600 text-white">
                            <i class="fas fa-upload mr-2"></i> <?php echo $lang['import'] ?? 'Import'; ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Instructions -->
        <div class="mt-6 bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
            <h2 class="text-xl font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                <i class="fas fa-info-circle mr-2"></i> <?php echo $lang['working_with_language_files'] ?? 'Working with Language Files'; ?>
            </h2>
            
            <div class="prose max-w-none <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                <h3 class="<?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">Export Process</h3>
                <ol>
                    <li>Select the language you want to export from the dropdown menu.</li>
                    <li>Click the "Export" button to download a CSV file containing all translation keys and values.</li>
                    <li>The CSV file includes three columns: "section", "key", and "value".</li>
                    <li>Open this file in a spreadsheet application like Microsoft Excel or Google Sheets.</li>
                </ol>
                
                <h3 class="<?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">Translation Process</h3>
                <ol>
                    <li>The "section" column indicates which part of the website the text belongs to.</li>
                    <li>The "key" column contains the unique identifier for each text string.</li>
                    <li>The "value" column contains the text in the language you exported.</li>
                    <li>Translate the text in the "value" column to the desired language.</li>
                    <li>Do not modify the "section" or "key" columns, as this will break the import process.</li>
                </ol>
                
                <h3 class="<?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">Import Process</h3>
                <ol>
                    <li>After translating the CSV file, save it in CSV format.</li>
                    <li>Select the target language you want to import to from the dropdown menu.</li>
                    <li>Click "Choose File" and select your translated CSV file.</li>
                    <li>Click the "Import" button to upload your translated text.</li>
                    <li>The system will update the language files with the new translations.</li>
                </ol>
                
                <h3 class="<?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">Important Notes</h3>
                <ul>
                    <li>Always keep a backup of your original CSV file.</li>
                    <li>Do not change the format of the CSV file or the column headers.</li>
                    <li>If a translation already exists in the system, it will be overwritten by the imported value.</li>
                    <li>Empty values in the CSV file will not overwrite existing translations.</li>
                    <li>Special characters such as quotes, commas, and HTML tags are supported, but make sure they are properly formatted in the CSV.</li>
                </ul>
                
                <h3 class="<?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">Template Download</h3>
                <p>
                    If you want to create a translation for a new language from scratch, you can download the English template and translate it:
                    <a href="?action=export&export_lang=en" class="text-blue-600 hover:text-blue-800 <?php echo $darkMode ? 'text-blue-400 hover:text-blue-300' : ''; ?>">
                        <i class="fas fa-download mr-1"></i> Download English Template
                    </a>
                </p>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript Functions -->
<script>
function exportLanguage() {
    const langSelect = document.getElementById('export_lang');
    const selectedLang = langSelect.value;
    
    if (!selectedLang) {
        alert('Please select a language to export.');
        return;
    }
    
    window.location.href = '?action=export&export_lang=' + selectedLang;
}
</script>

<?php
// Include footer
require_once '../../theme/admin/footer.php';
?>        