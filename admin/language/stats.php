<?php
/**
 * WallPix CMS - Language Statistics Dashboard
 * This file displays statistics about language translation completion
 * 
 * @version 1.0.0
 * @author WallPix Team
 * @copyright 2025 WallPix
 * Last Updated: 2025-03-18 14:09:10
 * Updated By: mahranalsarminy
 */

// Set page title
$pageTitle = 'Language Statistics - WallPix Admin';

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

// Reference language (usually English)
$referenceLanguage = 'en';

// Calculate statistics
$stats = [];
$totalSections = count($interfaceSections);
$overallStats = [
    'total_sections' => $totalSections,
    'total_languages' => count($languages),
    'total_keys' => 0,
    'translated_keys' => 0,
    'avg_completion' => 0,
    'fully_translated' => 0
];
// Get reference language keys count for each section
$referenceSectionCounts = [];
$referenceTotal = 0;
foreach ($interfaceSections as $sectionKey => $sectionName) {
    $refFile = "../../lang/{$referenceLanguage}/{$sectionKey}.php";
    
    if (file_exists($refFile)) {
        $strings = include $refFile;
        
        if (is_array($strings)) {
            $count = count($strings);
            $referenceSectionCounts[$sectionKey] = $count;
            $referenceTotal += $count;
        } else {
            $referenceSectionCounts[$sectionKey] = 0;
        }
    } else {
        $referenceSectionCounts[$sectionKey] = 0;
    }
}
$overallStats['total_keys'] = $referenceTotal;

// Calculate statistics for each language
foreach ($languages as $language) {
    $langCode = $language['lang_code'];
    
    // Skip reference language from stats
    if ($langCode === $referenceLanguage) {
        continue;
    }
    
    $stats[$langCode] = [
        'name' => $language['lang_name'],
        'is_active' => $language['is_active'],
        'total_keys' => $referenceTotal,
        'translated_keys' => 0,
        'empty_keys' => 0,
        'completion_percent' => 0,
        'sections' => [],
        'missing_sections' => 0
    ];
    
    $totalTranslated = 0;
    $totalEmpty = 0;
    $missingSections = 0;
    
    // Check each section
    foreach ($interfaceSections as $sectionKey => $sectionName) {
        $refCount = $referenceSectionCounts[$sectionKey];
        $langFile = "../../lang/{$langCode}/{$sectionKey}.php";
        
        $sectionStats = [
            'name' => $sectionName,
            'total_keys' => $refCount,
            'translated_keys' => 0,
            'empty_keys' => 0,
            'completion_percent' => 0,
            'file_exists' => false
        ];
        
        if (file_exists($langFile)) {
            $sectionStats['file_exists'] = true;
            $strings = include $langFile;
            
            if (is_array($strings)) {
                $translatedCount = 0;
                $emptyCount = 0;
                
                // Count non-empty translations
                foreach ($strings as $key => $value) {
                    if (!empty($value)) {
                        $translatedCount++;
                    } else {
                        $emptyCount++;
                    }
                }
                
                $sectionStats['translated_keys'] = $translatedCount;
                $sectionStats['empty_keys'] = $emptyCount;
                
                // Calculate completion percentage for this section
                if ($refCount > 0) {
                    $sectionStats['completion_percent'] = round(($translatedCount / $refCount) * 100, 1);
                }
                
                $totalTranslated += $translatedCount;
                $totalEmpty += $emptyCount;
            }
        } else {
            $missingSections++;
        }
        
        $stats[$langCode]['sections'][$sectionKey] = $sectionStats;
    }
    
    // Calculate overall completion percentage for this language
    if ($referenceTotal > 0) {
        $stats[$langCode]['translated_keys'] = $totalTranslated;
        $stats[$langCode]['empty_keys'] = $totalEmpty;
        $stats[$langCode]['completion_percent'] = round(($totalTranslated / $referenceTotal) * 100, 1);
        $stats[$langCode]['missing_sections'] = $missingSections;
        
        $overallStats['translated_keys'] += $totalTranslated;
        
        // Count fully translated languages (95%+ completion)
        if ($stats[$langCode]['completion_percent'] >= 95) {
            $overallStats['fully_translated']++;
        }
    }
}

// Calculate overall average completion percentage
if ((count($languages) - 1) > 0 && $referenceTotal > 0) {
    $overallStats['avg_completion'] = round($overallStats['translated_keys'] / ((count($languages) - 1) * $referenceTotal) * 100, 1);
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
                    <?php echo $lang['language_statistics'] ?? 'Language Statistics'; ?>
                </h1>
                <p class="mt-2 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                    <?php echo $lang['statistics_description'] ?? 'Translation completion statistics for all languages'; ?>
                </p>
            </div>
            <div class="mt-4 md:mt-0 flex space-x-2">
                <a href="index.php" class="btn bg-gray-500 hover:bg-gray-600 text-white">
                    <i class="fas fa-arrow-left mr-2"></i> <?php echo $lang['back_to_languages'] ?? 'Back to Languages'; ?>
                </a>
                <a href="sync.php" class="btn bg-blue-500 hover:bg-blue-600 text-white">
                    <i class="fas fa-sync-alt mr-2"></i> <?php echo $lang['sync_languages'] ?? 'Sync Languages'; ?>
                </a>
            </div>
        </div>
        
        <!-- Overall Stats -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600 <?php echo $darkMode ? 'bg-blue-900 text-blue-200' : ''; ?>">
                        <i class="fas fa-language text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium uppercase <?php echo $darkMode ? 'text-gray-300' : 'text-gray-600'; ?>">
                            <?php echo $lang['total_languages'] ?? 'Total Languages'; ?>
                        </p>
                        <p class="text-2xl font-semibold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                            <?php echo $overallStats['total_languages']; ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600 <?php echo $darkMode ? 'bg-green-900 text-green-200' : ''; ?>">
                        <i class="fas fa-check-circle text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium uppercase <?php echo $darkMode ? 'text-gray-300' : 'text-gray-600'; ?>">
                            <?php echo $lang['fully_translated'] ?? 'Fully Translated'; ?>
                        </p>
                        <p class="text-2xl font-semibold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                            <?php echo $overallStats['fully_translated']; ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600 <?php echo $darkMode ? 'bg-yellow-900 text-yellow-200' : ''; ?>">
                        <i class="fas fa-key text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium uppercase <?php echo $darkMode ? 'text-gray-300' : 'text-gray-600'; ?>">
                            <?php echo $lang['total_strings'] ?? 'Total Strings'; ?>
                        </p>
                        <p class="text-2xl font-semibold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                            <?php echo $overallStats['total_keys']; ?>
                        </p>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-indigo-100 text-indigo-600 <?php echo $darkMode ? 'bg-indigo-900 text-indigo-200' : ''; ?>">
                        <i class="fas fa-chart-pie text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium uppercase <?php echo $darkMode ? 'text-gray-300' : 'text-gray-600'; ?>">
                            <?php echo $lang['avg_completion'] ?? 'Avg. Completion'; ?>
                        </p>
                        <p class="text-2xl font-semibold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                            <?php echo $overallStats['avg_completion']; ?>%
                        </p>
                    </div>
                </div>
            </div>
        </div>
        <!-- Language Completion Overview - Header -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden mb-6 <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
            <div class="border-b <?php echo $darkMode ? 'border-gray-600' : 'border-gray-200'; ?>">
                <div class="px-6 py-4">
                    <h2 class="font-semibold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                        <?php echo $lang['language_completion'] ?? 'Language Completion Overview'; ?>
                    </h2>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-600' : ''; ?>">
                    <thead class="bg-gray-50 <?php echo $darkMode ? 'bg-gray-800' : ''; ?>">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                <?php echo $lang['language'] ?? 'Language'; ?>
                            </th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                <?php echo $lang['status'] ?? 'Status'; ?>
                            </th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                <?php echo $lang['translated'] ?? 'Translated'; ?>
                            </th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                <?php echo $lang['missing'] ?? 'Missing'; ?>
                            </th>
                            <th scope="col" class="px-6 py-3 text-center text-xs font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                <?php echo $lang['completion'] ?? 'Completion'; ?>
                            </th>
                            <th scope="col" class="px-6 py-3 text-right text-xs font-medium <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                <?php echo $lang['actions'] ?? 'Actions'; ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200 <?php echo $darkMode ? 'bg-gray-700 divide-gray-600' : ''; ?>">
                        <?php foreach ($languages as $language): ?>
                            <?php 
                            $langCode = $language['lang_code'];
                            
                            if ($langCode === $referenceLanguage) {
                                // Display reference language (usually English)
                                $completionClass = 'bg-blue-100 text-blue-800';
                                if ($darkMode) {
                                    $completionClass = 'bg-blue-900 text-blue-200';
                                }
                                $completionPercent = 100;
                            } else {
                                // Display translation languages
                                $completionPercent = isset($stats[$langCode]) ? $stats[$langCode]['completion_percent'] : 0;
                                
                                if ($completionPercent >= 90) {
                                    $completionClass = 'bg-green-100 text-green-800';
                                    if ($darkMode) {
                                        $completionClass = 'bg-green-900 text-green-200';
                                    }
                                } elseif ($completionPercent >= 60) {
                                    $completionClass = 'bg-yellow-100 text-yellow-800';
                                    if ($darkMode) {
                                        $completionClass = 'bg-yellow-900 text-yellow-200';
                                    }
                                } else {
                                    $completionClass = 'bg-red-100 text-red-800';
                                    if ($darkMode) {
                                        $completionClass = 'bg-red-900 text-red-200';
                                    }
                                }
                            }
                            ?>
                            <tr class="hover:bg-gray-50 <?php echo $darkMode ? 'hover:bg-gray-650' : ''; ?>">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="text-sm font-medium <?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                            <?php echo htmlspecialchars($language['lang_name']); ?>
                                            <?php if ($langCode === $referenceLanguage): ?>
                                                <span class="ml-2 text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-800 <?php echo $darkMode ? 'bg-blue-900 text-blue-200' : ''; ?>">
                                                    <?php echo $lang['reference'] ?? 'Reference'; ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> ml-2">
                                            (<?php echo $langCode; ?>)
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $language['is_active'] ? 
                                            ($darkMode ? 'bg-green-900 text-green-200' : 'bg-green-100 text-green-800') : 
                                            ($darkMode ? 'bg-gray-800 text-gray-300' : 'bg-gray-100 text-gray-800'); ?>">
                                        <?php echo $language['is_active'] ? ($lang['active'] ?? 'Active') : ($lang['inactive'] ?? 'Inactive'); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">
                                    <?php if ($langCode === $referenceLanguage): ?>
                                        <?php echo $referenceTotal; ?> / <?php echo $referenceTotal; ?>
                                    <?php else: ?>
                                        <?php echo isset($stats[$langCode]) ? $stats[$langCode]['translated_keys'] : 0; ?> / <?php echo $referenceTotal; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">
                                    <?php if ($langCode === $referenceLanguage): ?>
                                        0
                                    <?php else: ?>
                                        <?php echo isset($stats[$langCode]) ? ($referenceTotal - $stats[$langCode]['translated_keys']) : $referenceTotal; ?>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="w-full bg-gray-200 rounded-full h-2.5 <?php echo $darkMode ? 'bg-gray-600' : ''; ?>">
                                        <div class="h-2.5 rounded-full <?php echo $completionClass; ?>" style="width: <?php echo $completionPercent; ?>%"></div>
                                    </div>
                                    <div class="text-xs font-medium mt-1 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">
                                        <?php echo $completionPercent; ?>%
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="index.php?action=edit&lang=<?php echo $langCode; ?>&section=homepage" class="text-indigo-600 hover:text-indigo-900 mr-2 <?php echo $darkMode ? 'text-indigo-400 hover:text-indigo-300' : ''; ?>">
                                        <i class="fas fa-edit"></i> <?php echo $lang['edit'] ?? 'Edit'; ?>
                                    </a>
                                    <?php if ($langCode !== $referenceLanguage): ?>
                                        <a href="sync.php?source_language=<?php echo $referenceLanguage; ?>&target_language=<?php echo $langCode; ?>" class="text-blue-600 hover:text-blue-900 <?php echo $darkMode ? 'text-blue-400 hover:text-blue-300' : ''; ?>">
                                            <i class="fas fa-sync-alt"></i> <?php echo $lang['sync'] ?? 'Sync'; ?>
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <!-- Section Details -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
            <div class="border-b <?php echo $darkMode ? 'border-gray-600' : 'border-gray-200'; ?>">
                <div class="px-6 py-4 flex justify-between items-center">
                    <h2 class="font-semibold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                        <?php echo $lang['section_details'] ?? 'Section Details'; ?>
                    </h2>
                    <div>
                        <select id="language-filter" onchange="filterLanguage()" class="form-select rounded-md text-sm <?php echo $darkMode ? 'bg-gray-600 border-gray-500 text-white' : ''; ?>">
                            <option value=""><?php echo $lang['select_language'] ?? 'Select Language'; ?></option>
                            <?php foreach ($languages as $language): ?>
                                <?php if ($language['lang_code'] !== $referenceLanguage): ?>
                                    <option value="<?php echo $language['lang_code']; ?>">
                                        <?php echo $language['lang_name']; ?> (<?php echo $language['lang_code']; ?>)
                                    </option>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div id="sectionDetails" class="p-6">
                <div class="flex items-center justify-center h-40 text-center">
                    <p class="text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                        <?php echo $lang['select_language_to_view_details'] ?? 'Select a language to view section details.'; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Language Section Details Template (Hidden) -->
<template id="language-section-template">
    <div class="language-section-details">
        <h3 class="text-lg font-medium mb-4 language-name <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>"></h3>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 section-grid">
            <!-- Section cards will be dynamically inserted here -->
        </div>
    </div>
</template>

<!-- Section Card Template (Hidden) -->
<template id="section-card-template">
    <div class="section-card border rounded-lg overflow-hidden <?php echo $darkMode ? 'border-gray-600' : 'border-gray-200'; ?>">
        <div class="section-header px-4 py-2 border-b <?php echo $darkMode ? 'border-gray-600 bg-gray-750' : 'border-gray-200 bg-gray-50'; ?>">
            <h4 class="font-medium section-title <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>"></h4>
        </div>
        <div class="p-4">
            <div class="flex justify-between mb-2">
                <div class="text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-600'; ?>">
                    <span class="section-translated"></span> / <span class="section-total"></span> <?php echo $lang['strings'] ?? 'strings'; ?>
                </div>
                <div class="text-sm section-percent font-semibold"></div>
            </div>
            <div class="w-full bg-gray-200 rounded-full h-2 mb-3 <?php echo $darkMode ? 'bg-gray-600' : ''; ?>">
                <div class="section-progress h-2 rounded-full"></div>
            </div>
            <div class="mt-2 text-right">
                <a href="#" class="section-edit-link text-sm text-blue-600 hover:text-blue-800 <?php echo $darkMode ? 'text-blue-400 hover:text-blue-300' : ''; ?>">
                    <i class="fas fa-edit mr-1"></i> <?php echo $lang['edit_section'] ?? 'Edit Section'; ?>
                </a>
            </div>
        </div>
    </div>
</template>

<script>
// Language data for client-side filtering
const languageData = <?php echo json_encode($stats); ?>;
const interfaceSections = <?php echo json_encode($interfaceSections); ?>;
const referenceLanguage = '<?php echo $referenceLanguage; ?>';

function filterLanguage() {
    const langCode = document.getElementById('language-filter').value;
    const sectionDetailsElement = document.getElementById('sectionDetails');
    
    if (!langCode) {
        sectionDetailsElement.innerHTML = `
            <div class="flex items-center justify-center h-40 text-center">
                <p class="text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
                    <?php echo $lang['select_language_to_view_details'] ?? 'Select a language to view section details.'; ?>
                </p>
            </div>
        `;
        return;
    }
    
    // Clone the template
    const template = document.getElementById('language-section-template').content.cloneNode(true);
    const languageData = <?php echo json_encode($stats); ?>;
    
    // Update language name
    const languageName = document.querySelector(`#language-filter option[value="${langCode}"]`).textContent;
    template.querySelector('.language-name').textContent = languageName;
    
    // Get the section grid where we'll add all section cards
    const sectionGrid = template.querySelector('.section-grid');
    
    // Get language specific data
    const langData = languageData[langCode];
    
    if (langData && langData.sections) {
        // Sort sections by completion percent (ascending)
        const sortedSections = Object.keys(langData.sections)
            .sort((a, b) => langData.sections[a].completion_percent - langData.sections[b].completion_percent);
        
        // Add each section
        for (const sectionKey of sortedSections) {
            const sectionData = langData.sections[sectionKey];
            const sectionName = interfaceSections[sectionKey] || sectionKey;
            
            // Create section card from template
            const sectionTemplate = document.getElementById('section-card-template').content.cloneNode(true);
            const card = sectionTemplate.querySelector('.section-card');
            
            // Update section data
            sectionTemplate.querySelector('.section-title').textContent = sectionName;
            sectionTemplate.querySelector('.section-translated').textContent = sectionData.translated_keys;
            sectionTemplate.querySelector('.section-total').textContent = sectionData.total_keys;
            sectionTemplate.querySelector('.section-percent').textContent = `${sectionData.completion_percent}%`;
            
            // Set progress bar color and width
            const progressBar = sectionTemplate.querySelector('.section-progress');
            progressBar.style.width = `${sectionData.completion_percent}%`;
            
            // Set progress bar color based on completion
            if (sectionData.completion_percent >= 90) {
                progressBar.classList.add('<?php echo $darkMode ? "bg-green-600" : "bg-green-500"; ?>');
            } else if (sectionData.completion_percent >= 60) {
                progressBar.classList.add('<?php echo $darkMode ? "bg-yellow-600" : "bg-yellow-500"; ?>');
            } else {
                progressBar.classList.add('<?php echo $darkMode ? "bg-red-600" : "bg-red-500"; ?>');
            }
            
            // Set edit link
            const editLink = sectionTemplate.querySelector('.section-edit-link');
            editLink.href = `index.php?action=edit&lang=${langCode}&section=${sectionKey}`;
            
            // Append to grid
            sectionGrid.appendChild(card);
        }
    }
    
    // Clear existing content and add the new details
    sectionDetailsElement.innerHTML = '';
    sectionDetailsElement.appendChild(template);
}
</script>

<?php
// Include footer
require_once '../../theme/admin/footer.php';
?>