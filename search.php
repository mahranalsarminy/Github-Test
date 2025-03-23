<?php
/**
 * Search Page for WallPix
 * Allows searching by name and tags
 *
 * @package WallPix
 * @version 1.0.0
 */

// Include required files
require_once __DIR__ . '/includes/init.php';

// Set page title
$pageTitle = 'Search Results';

// Current UTC date/time 
$currentDateTime = '2025-03-23 06:14:35';
// Current user
$currentUser = 'mahranalsarminy';

// Get search parameters
$searchQuery = trim($_GET['q'] ?? '');
$searchTag = trim($_GET['tag'] ?? '');
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$perPage = 20; // Number of items per page
$offset = ($page - 1) * $perPage;

// Search results
$mediaItems = [];
$totalItems = 0;

// Search filters
$filters = [];
$joinTags = false;

// Search logic
try {
    // Base query parts
    $selectQuery = "
        SELECT DISTINCT m.id, m.title, m.description, m.file_path, m.thumbnail_url, 
            m.width, m.height, m.featured, m.paid_content, m.orientation,
            m.background_color, m.resolution_id, m.publish_date, m.created_at
    ";
    
    $fromQuery = " FROM media m ";
    
    $whereConditions = [];
    $whereConditions[] = "m.status = 1"; // Only active items
    
    // Search by name (title or description)
    if (!empty($searchQuery)) {
        $searchTerms = explode(' ', $searchQuery);
        $titleDescConditions = [];
        
        foreach ($searchTerms as $term) {
            $term = trim($term);
            if (strlen($term) > 1) { // Ignore very short terms
                $titleDescConditions[] = "(m.title LIKE :term_title OR m.description LIKE :term_desc)";
                $filters[':term_title'] = '%' . $term . '%';
                $filters[':term_desc'] = '%' . $term . '%';
            }
        }
        
        if (!empty($titleDescConditions)) {
            $whereConditions[] = '(' . implode(' AND ', $titleDescConditions) . ')';
        }
    }
    
    // Search by tag
    if (!empty($searchTag)) {
        $joinTags = true;
        $fromQuery .= " INNER JOIN media_tags mt ON m.id = mt.media_id 
                        INNER JOIN tags t ON mt.tag_id = t.id ";
        $whereConditions[] = "t.name = :tag_name";
        $filters[':tag_name'] = $searchTag;
    }
    
    // Complete the WHERE clause
    $whereQuery = " WHERE " . implode(' AND ', $whereConditions);
    
    // Order by
    $orderQuery = " ORDER BY m.featured DESC, m.created_at DESC";
    
    // Limit for pagination
    $limitQuery = " LIMIT :offset, :per_page";
    
    // Count total results first (for pagination)
    $countQuery = "SELECT COUNT(DISTINCT m.id) " . $fromQuery . $whereQuery;
    $stmt = $pdo->prepare($countQuery);
    foreach ($filters as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $totalItems = (int)$stmt->fetchColumn();
    
    // Now get the actual results with pagination
    $fullQuery = $selectQuery . $fromQuery . $whereQuery . $orderQuery . $limitQuery;
    $stmt = $pdo->prepare($fullQuery);
    foreach ($filters as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindValue(':per_page', $perPage, PDO::PARAM_INT);
    $stmt->execute();
    $mediaItems = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get related tags for each media item
    if (!empty($mediaItems)) {
        $mediaIds = array_column($mediaItems, 'id');
        $placeholders = implode(',', array_fill(0, count($mediaIds), '?'));
        
        $tagQuery = "
            SELECT mt.media_id, t.name 
            FROM media_tags mt 
            INNER JOIN tags t ON mt.tag_id = t.id 
            WHERE mt.media_id IN ($placeholders)
        ";
        
        $stmt = $pdo->prepare($tagQuery);
        foreach ($mediaIds as $index => $id) {
            $stmt->bindValue($index + 1, $id);
        }
        $stmt->execute();
        $tagResults = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Group tags by media_id
        $mediaTags = [];
        foreach ($tagResults as $tag) {
            $mediaTags[$tag['media_id']][] = $tag['name'];
        }
        
        // Add tags to media items
        foreach ($mediaItems as &$item) {
            $item['tags'] = $mediaTags[$item['id']] ?? [];
        }
    }
    
    // Calculate pagination
    $totalPages = ceil($totalItems / $perPage);
    $prevPage = ($page > 1) ? $page - 1 : null;
    $nextPage = ($page < $totalPages) ? $page + 1 : null;
    
    // Build search query string for pagination links
    $queryParams = [];
    if (!empty($searchQuery)) {
        $queryParams[] = "q=" . urlencode($searchQuery);
    }
    if (!empty($searchTag)) {
        $queryParams[] = "tag=" . urlencode($searchTag);
    }
    $queryString = implode('&', $queryParams);
    
} catch (Exception $e) {
    // Log error
    error_log('Search error: ' . $e->getMessage());
    
    // Show error message
    $error = "Sorry, an error occurred while processing your search. Please try again.";
}

// Get trending tags
try {
    $trendingTagsQuery = "
        SELECT t.id, t.name, COUNT(mt.media_id) as count 
        FROM tags t 
        INNER JOIN media_tags mt ON t.id = mt.tag_id 
        INNER JOIN media m ON mt.media_id = m.id 
        WHERE m.status = 1 
        GROUP BY t.id 
        ORDER BY count DESC 
        LIMIT 20
    ";
    $stmt = $pdo->prepare($trendingTagsQuery);
    $stmt->execute();
    $trendingTags = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $trendingTags = [];
}

// Get recent searches from cookie if available
$recentSearches = [];
if (isset($_COOKIE['recent_searches'])) {
    $recentSearches = json_decode($_COOKIE['recent_searches'], true) ?? [];
}

// Include the header
include_once 'theme/homepage/header.php';
?>
<!DOCTYPE html>
<html lang="<?php echo $site_settings['language']; ?>" dir="<?php echo $site_settings['language'] === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $site_settings['site_name']; ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/dark-mode.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Cairo|Roboto&display=swap">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- إضافة الوصف والكلمات المفتاحية من قاعدة البيانات -->
    <meta name="description" content="<?php echo htmlspecialchars($site_settings['site_description'] ?? ''); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($site_settings['site_keywords'] ?? ''); ?>">
</head>
<body class="<?php echo $site_settings['dark_mode'] ? 'dark-mode' : 'light-mode'; ?>">
    <!-- Skip to main content link for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>
<!-- Hero Section -->
<section class="bg-gradient-to-r from-blue-500 to-purple-600 py-12">
    <div class="container mx-auto px-4">
        <div class="text-center">
            <h1 class="text-3xl font-bold text-white mb-4">
                <?php if (!empty($searchQuery) || !empty($searchTag)): ?>
                    Search Results
                    <?php if (!empty($searchQuery)): ?>
                        for "<?php echo htmlspecialchars($searchQuery); ?>"
                    <?php endif; ?>
                    <?php if (!empty($searchTag)): ?>
                        <?php echo !empty($searchQuery) ? ' with tag:' : 'for tag:'; ?>
                        <span class="bg-white/20 text-white px-2 py-1 rounded-full text-sm"><?php echo htmlspecialchars($searchTag); ?></span>
                    <?php endif; ?>
                <?php else: ?>
                    All Media
                <?php endif; ?>
            </h1>
            <p class="text-white/80 max-w-2xl mx-auto">
                <?php echo number_format($totalItems); ?> results found. Browse through our collection or refine your search.
            </p>
            
            <!-- Search Form -->
            <div class="mt-8 max-w-3xl mx-auto">
                <form action="search.php" method="get" class="flex flex-col md:flex-row gap-4">
                    <div class="flex-1">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-search text-gray-400"></i>
                            </div>
                            <input type="text" name="q" value="<?php echo htmlspecialchars($searchQuery); ?>" 
                                class="block w-full pl-10 pr-3 py-3 border border-transparent rounded-lg bg-white/10 backdrop-blur-sm text-white placeholder-white/60 focus:outline-none focus:ring-2 focus:ring-white/50"
                                placeholder="Search by title, description...">
                        </div>
                    </div>
                    
                    <div class="md:w-1/4">
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-tag text-gray-400"></i>
                            </div>
                            <input type="text" name="tag" value="<?php echo htmlspecialchars($searchTag); ?>" 
                                class="block w-full pl-10 pr-3 py-3 border border-transparent rounded-lg bg-white/10 backdrop-blur-sm text-white placeholder-white/60 focus:outline-none focus:ring-2 focus:ring-white/50"
                                placeholder="Filter by tag...">
                        </div>
                    </div>
                    
                    <button type="submit" class="md:w-auto px-6 py-3 bg-white text-blue-600 font-medium rounded-lg hover:bg-white/90 transition-all shadow-lg">
                        Search
                    </button>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- Popular Tags -->
<section class="bg-gray-50 py-4 border-b border-gray-200">
    <div class="container mx-auto px-4">
        <div class="flex flex-wrap items-center gap-2">
            <span class="text-sm font-medium text-gray-700">Popular Tags:</span>
            <?php
            $displayCount = min(10, count($trendingTags));
            for ($i = 0; $i < $displayCount; $i++):
                $tag = $trendingTags[$i];
            ?>
                <a href="search.php?tag=<?php echo urlencode($tag['name']); ?>" 
                    class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?php echo ($searchTag === $tag['name']) ? 'bg-blue-100 text-blue-800' : 'bg-gray-100 text-gray-800 hover:bg-gray-200'; ?>">
                    <?php echo htmlspecialchars($tag['name']); ?>
                </a>
            <?php endfor; ?>
            
            <a href="tags.php" class="text-blue-600 hover:text-blue-800 text-sm ml-auto">
                <i class="fas fa-tags mr-1"></i> View All Tags
            </a>
        </div>
    </div>
</section>

<!-- Main Content -->
<div class="container mx-auto px-4 py-8">
    <?php if (isset($error)): ?>
        <!-- Error Message -->
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-circle text-red-400"></i>
                </div>
                <div class="ml-3">
                    <p class="text-sm text-red-700"><?php echo $error; ?></p>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if (empty($mediaItems) && !isset($error)): ?>
        <!-- No Results Found -->
        <div class="text-center py-12">
            <div class="w-24 h-24 mx-auto mb-6 flex items-center justify-center bg-gray-100 rounded-full">
                <i class="fas fa-search text-4xl text-gray-400"></i>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">No results found</h3>
            <p class="text-gray-600 mb-6 max-w-md mx-auto">
                We couldn't find any media matching your search criteria. Try different keywords or browse our categories.
            </p>
            <div class="flex flex-wrap justify-center gap-4">
                <a href="index.php" class="px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition-all shadow">
                    <i class="fas fa-home mr-2"></i> Go to Homepage
                </a>
                <a href="categories.php" class="px-6 py-2 bg-white text-gray-800 font-medium rounded-lg hover:bg-gray-100 transition-all border">
                    <i class="fas fa-folder mr-2"></i> Browse Categories
                </a>
            </div>
        </div>
    <?php else: ?>
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-900">
                    <?php if (!empty($searchQuery) || !empty($searchTag)): ?>
                        Search Results
                    <?php else: ?>
                        All Media
                    <?php endif; ?>
                </h2>
                <p class="text-gray-600"><?php echo number_format($totalItems); ?> items found</p>
            </div>
            
            <!-- Sort Options -->
            <div class="flex items-center gap-2">
                <span class="text-sm text-gray-700">Sort by:</span>
                <select class="border-gray-300 rounded-md text-sm focus:ring-blue-500 focus:border-blue-500">
                    <option>Newest</option>
                    <option>Popular</option>
                    <option>A-Z</option>
                </select>
            </div>
        </div>
        
        <!-- Media Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-6">
            <?php foreach ($mediaItems as $item): ?>
                <div class="group relative rounded-lg overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300">
                    <a href="media.php?id=<?php echo $item['id']; ?>" class="block aspect-w-4 aspect-h-5 bg-gray-100 overflow-hidden">
                        <?php if (!empty($item['thumbnail_url'])): ?>
                            <img src="<?php echo htmlspecialchars($item['thumbnail_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <?php elseif (!empty($item['file_path'])): ?>
                            <img src="<?php echo htmlspecialchars($item['file_path']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['title']); ?>" 
                                 class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-500">
                        <?php else: ?>
                            <div class="w-full h-full flex items-center justify-center bg-gray-200">
                                <i class="fas fa-image text-3xl text-gray-400"></i>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($item['featured']): ?>
                            <div class="absolute top-2 left-2">
                                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-yellow-100 text-yellow-800">
                                    <i class="fas fa-star mr-1"></i> Featured
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($item['paid_content']): ?>
                            <div class="absolute top-2 right-2">
                                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-purple-100 text-purple-800">
                                    <i class="fas fa-crown mr-1"></i> Premium
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 flex items-end transition-opacity duration-300">
                            <div class="p-3 w-full">
                                <span class="text-white text-sm font-medium line-clamp-1"><?php echo htmlspecialchars($item['title']); ?></span>
                                <div class="flex items-center justify-between mt-2">
                                    <span class="text-white/80 text-xs">
                                        <i class="far fa-eye mr-1"></i> 
                                        <?php echo rand(100, 10000); ?>
                                    </span>
                                    <button class="text-white/80 hover:text-white text-xs">
                                        <i class="far fa-heart"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </a>
                    
                    <div class="p-3 bg-white">
                        <h3 class="font-medium text-gray-900 truncate" title="<?php echo htmlspecialchars($item['title']); ?>">
                            <?php echo htmlspecialchars($item['title']); ?>
                        </h3>
                        
                        <div class="flex flex-wrap mt-2 gap-1">
                            <?php foreach (array_slice(($item['tags'] ?? []), 0, 2) as $tag): ?>
                                <a href="search.php?tag=<?php echo urlencode($tag); ?>" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800 hover:bg-gray-200">
                                    <?php echo htmlspecialchars($tag); ?>
                                </a>
                            <?php endforeach; ?>
                            
                            <?php if (count(($item['tags'] ?? [])) > 2): ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-500">
                                    +<?php echo count($item['tags']) - 2; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex items-center justify-between mt-3 text-xs text-gray-500">
                            <span><i class="far fa-calendar mr-1"></i> <?php echo date('M j, Y', strtotime($item['created_at'])); ?></span>
                            <?php if ($item['width'] && $item['height']): ?>
                                <span><i class="fas fa-ruler-combined mr-1"></i> <?php echo $item['width']; ?>×<?php echo $item['height']; ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="mt-12 flex justify-center">
            <nav class="inline-flex rounded-md shadow">
                <?php if ($prevPage): ?>
                    <a href="?<?php echo $queryString; ?>&page=<?php echo $prevPage; ?>" class="relative inline-flex items-center px-4 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                        <i class="fas fa-chevron-left mr-1"></i> Previous
                    </a>
                <?php else: ?>
                    <span class="relative inline-flex items-center px-4 py-2 rounded-l-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                        <i class="fas fa-chevron-left mr-1"></i> Previous
                    </span>
                <?php endif; ?>
                
                <div class="flex">
                    <?php
                    // Determine which page numbers to show
                    $startPage = max(1, min($page - 2, $totalPages - 4));
                    $endPage = min($totalPages, max(5, $page + 2));
                    
                    // Show first page if not included in range
                    if ($startPage > 1): 
                    ?>
                        <a href="?<?php echo $queryString; ?>&page=1" class="relative inline-flex items-center px-4 py-2 border-t border-b border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                            1
                        </a>
                        <?php if ($startPage > 2): ?>
                            <span class="relative inline-flex items-center px-3 py-2 border-t border-b border-gray-300 bg-white text-sm font-medium text-gray-700">
                                ...
                            </span>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                        <?php if ($i == $page): ?>
                            <span class="relative inline-flex items-center px-4 py-2 border-t border-b border-gray-300 bg-blue-50 text-sm font-medium text-blue-600">
                                <?php echo $i; ?>
                            </span>
                        <?php else: ?>
                            <a href="?<?php echo $queryString; ?>&page=<?php echo $i; ?>" class="relative inline-flex items-center px-4 py-2 border-t border-b border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                                <?php echo $i; ?>
                            </a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($endPage < $totalPages): ?>
                        <?php if ($endPage < $totalPages - 1): ?>
                            <span class="relative inline-flex items-center px-3 py-2 border-t border-b border-gray-300 bg-white text-sm font-medium text-gray-700">
                                ...
                            </span>
                        <?php endif; ?>
                        <a href="?<?php echo $queryString; ?>&page=<?php echo $totalPages; ?>" class="relative inline-flex items-center px-4 py-2 border-t border-b border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                            <?php echo $totalPages; ?>
                        </a>
                    <?php endif; ?>
                </div>
                
                <?php if ($nextPage): ?>
                    <a href="?<?php echo $queryString; ?>&page=<?php echo $nextPage; ?>" class="relative inline-flex items-center px-4 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50">
                        Next <i class="fas fa-chevron-right ml-1"></i>
                    </a>
                <?php else: ?>
                    <span class="relative inline-flex items-center px-4 py-2 rounded-r-md border border-gray-300 bg-gray-100 text-sm font-medium text-gray-400 cursor-not-allowed">
                        Next <i class="fas fa-chevron-right ml-1"></i>
                    </span>
                <?php endif; ?>
            </nav>
        </div>
        <?php endif; ?>
    <?php endif; ?>
    
    <!-- Recent Searches -->
    <?php if (!empty($recentSearches)): ?>
    <div class="mt-10 p-4 bg-gray-50 rounded-lg">
        <h3 class="text-lg font-medium text-gray-900 mb-3">Recent Searches</h3>
        <div class="flex flex-wrap gap-2">
            <?php foreach (array_slice($recentSearches, 0, 10) as $search): ?>
                <a href="search.php?q=<?php echo urlencode($search); ?>" class="inline-flex items-center px-3 py-1 rounded-full text-sm bg-white border border-gray-300 text-gray-700 hover:bg-gray-50">
                    <i class="fas fa-history mr-1 text-gray-400"></i> <?php echo htmlspecialchars($search); ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Related Categories -->
    <div class="mt-10">
        <h2 class="text-xl font-bold text-gray-900 mb-6">Browse Categories</h2>
        
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <?php
            // Get categories
            $categoriesQuery = "SELECT id, name, image FROM categories WHERE parent_id IS NULL AND status = 1 ORDER BY display_order, name LIMIT 12";
            $stmt = $pdo->prepare($categoriesQuery);
            $stmt->execute();
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($categories as $category):
                $categoryImage = !empty($category['image']) ? $category['image'] : '/assets/images/default-category.jpg';
            ?>
                <a href="category.php?id=<?php echo $category['id']; ?>" class="group block bg-white rounded-lg overflow-hidden shadow-sm hover:shadow-md transition-shadow">
                    <div class="aspect-w-16 aspect-h-9 bg-gray-100 overflow-hidden">
                        <img src="<?php echo htmlspecialchars($categoryImage); ?>" alt="<?php echo htmlspecialchars($category['name']); ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    </div>
                    <div class="p-3 text-center">
                        <h3 class="text-sm font-medium text-gray-900">
                            <?php echo htmlspecialchars($category['name']); ?>
                        </h3>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- Floating Accessibility Icon -->
<div id="accessibility-toggle" class="fixed bottom-6 right-6 z-50">
    <button aria-label="Accessibility options" class="w-12 h-12 rounded-full bg-blue-600 text-white flex items-center justify-center shadow-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
        <i class="fas fa-universal-access"></i>
    </button>
</div>

<!-- Accessibility Panel -->
<div id="accessibility-panel" class="fixed bottom-20 right-6 z-50 bg-white rounded-lg shadow-xl p-4 w-72 transform scale-0 origin-bottom-right transition-transform duration-300">
    <div class="flex justify-between items-center mb-4">
        <h3 class="font-medium text-gray-900">Accessibility Options</h3>
        <button id="close-accessibility" class="text-gray-400 hover:text-gray-500">
            <i class="fas fa-times"></i>
        </button>
    </div>
    
    <div class="space-y-4">
        <!-- Font Size -->
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Text Size</label>
            <div class="flex items-center">
                <button id="decrease-text" class="p-2 bg-gray-100 rounded-l-md hover:bg-gray-200">
                    <i class="fas fa-minus"></i>
                </button>
                <div class="flex-1 h-10 bg-gray-100 flex items-center justify-center">
                    <span id="text-size-label">Normal</span>
                </div>
                <button id="increase-text" class="p-2 bg-gray-100 rounded-r-md hover:bg-gray-200">
                    <i class="fas fa-plus"></i>
                </button>
            </div>
        </div>
        
        <!-- High Contrast -->
        <div>
            <div class="flex items-center justify-between">
                <label for="high-contrast" class="text-sm font-medium text-gray-700">High Contrast</label>
                <label class="switch">
                    <input type="checkbox" id="high-contrast">
                    <span class="slider round"></span>
                </label>
            </div>
        </div>
        
        <!-- Focus Mode -->
        <div>
            <div class="flex items-center justify-between">
                <label for="focus-mode" class="text-sm font-medium text-gray-700">Focus Mode</label>
                <label class="switch">
                    <input type="checkbox" id="focus-mode">
                    <span class="slider round"></span>
                </label>
            </div>
        </div>
        
        <!-- Reset Button -->
        <button id="reset-accessibility" class="w-full py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700">
            Reset Settings
        </button>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Save search query if not empty
    <?php if (!empty($searchQuery)): ?>
    // Send AJAX request to save search
    const searchTerm = '<?php echo addslashes($searchQuery); ?>';
    
    // Use existing save-search.php endpoint
    fetch('ajax/save-search.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'search=' + encodeURIComponent(searchTerm)
    });
    <?php endif; ?>
    
    // Accessibility panel
    const accessibilityToggle = document.getElementById('accessibility-toggle');
    const accessibilityPanel = document.getElementById('accessibility-panel');
    const closeAccessibility = document.getElementById('close-accessibility');
    
    if (accessibilityToggle && accessibilityPanel && closeAccessibility) {
        accessibilityToggle.addEventListener('click', function() {
            if (accessibilityPanel.style.transform === 'scale(1)') {
                accessibilityPanel.style.transform = 'scale(0)';
            } else {
                accessibilityPanel.style.transform = 'scale(1)';
            }
        });
        
        closeAccessibility.addEventListener('click', function() {
            accessibilityPanel.style.transform = 'scale(0)';
        });
    }
    
    // Text size controls
    const decreaseText = document.getElementById('decrease-text');
    const increaseText = document.getElementById('increase-text');
    const textSizeLabel = document.getElementById('text-size-label');
    
    let currentTextSize = parseInt(localStorage.getItem('textSize') || '0');
    
    // Initialize text size
    applyTextSize();
    
    // Text size functions
    function applyTextSize() {
        const htmlElement = document.documentElement;
        
        // Reset classes
        htmlElement.classList.remove('text-xs', 'text-sm', 'text-base', 'text-lg', 'text-xl');
        
        // Apply appropriate class based on current size
        if (currentTextSize === -2) {
            htmlElement.classList.add('text-xs');
            textSizeLabel.textContent = 'Very Small';
        } else if (currentTextSize === -1) {
            htmlElement.classList.add('text-sm');
            textSizeLabel.textContent = 'Small';
        } else if (currentTextSize === 0) {
            htmlElement.classList.add('text-base');
            textSizeLabel.textContent = 'Normal';
        } else if (currentTextSize === 1) {
            htmlElement.classList.add('text-lg');
            textSizeLabel.textContent = 'Large';
        } else if (currentTextSize === 2) {
            htmlElement.classList.add('text-xl');
            textSizeLabel.textContent = 'Very Large';
        }
        
        // Save to localStorage
        localStorage.setItem('textSize', currentTextSize.toString());
    }
    
    if (decreaseText) {
        decreaseText.addEventListener('click', function() {
            if (currentTextSize > -2) {
                currentTextSize--;
                applyTextSize();
            }
        });
    }
    
    if (increaseText) {
        increaseText.addEventListener('click', function() {
            if (currentTextSize < 2) {
                currentTextSize++;
                applyTextSize();
            }
        });
    }
    
    // High contrast mode
    const highContrastToggle = document.getElementById('high-contrast');
    
    // Initialize high contrast
    if (localStorage.getItem('highContrast') === 'true') {
        document.body.classList.add('high-contrast');
        if (highContrastToggle) highContrastToggle.checked = true;
    }
    
    if (highContrastToggle) {
        highContrastToggle.addEventListener('change', function() {
            if (this.checked) {
                document.body.classList.add('high-contrast');
                localStorage.setItem('highContrast', 'true');
            } else {
                document.body.classList.remove('high-contrast');
                localStorage.setItem('highContrast', 'false');
            }
        });
    }
    
    // Focus mode
    const focusModeToggle = document.getElementById('focus-mode');
    
    // Initialize focus mode
    if (localStorage.getItem('focusMode') === 'true') {
        document.body.classList.add('focus-mode');
        if (focusModeToggle) focusModeToggle.checked = true;
    }
    
    if (focusModeToggle) {
        focusModeToggle.addEventListener('change', function() {
            if (this.checked) {
                document.body.classList.add('focus-mode');
                localStorage.setItem('focusMode', 'true');
            } else {
                document.body.classList.remove('focus-mode');
                localStorage.setItem('focusMode', 'false');
            }
        });
    }
    
    // Reset accessibility settings
    const resetAccessibility = document.getElementById('reset-accessibility');
    
    if (resetAccessibility) {
        resetAccessibility.addEventListener('click', function() {
            // Reset text size
            currentTextSize = 0;
            applyTextSize();
            
            // Reset high contrast
            document.body.classList.remove('high-contrast');
            if (highContrastToggle) highContrastToggle.checked = false;
            localStorage.setItem('highContrast', 'false');
            
            // Reset focus mode
            document.body.classList.remove('focus-mode');
            if (focusModeToggle) focusModeToggle.checked = false;
            localStorage.setItem('focusMode', 'false');
        });
    }
    
    // Implement tag auto-suggestions
    const tagInput = document.querySelector('input[name="tag"]');
    if (tagInput) {
        const createSuggestionsList = function() {
            const suggestionsDiv = document.createElement('div');
            suggestionsDiv.className = 'absolute z-10 w-full mt-1 bg-white border border-gray-300 rounded-md shadow-lg max-h-60 overflow-y-auto';
            suggestionsDiv.id = 'tag-suggestions';
            suggestionsDiv.style.display = 'none';
            tagInput.parentNode.appendChild(suggestionsDiv);
            return suggestionsDiv;
        };
        
        const suggestionsDiv = createSuggestionsList();
        
        tagInput.addEventListener('input', function() {
            const query = this.value.trim();
            if (query.length < 2) {
                suggestionsDiv.style.display = 'none';
                return;
            }
            
            fetch('ajax/tag-suggestions.php?q=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    suggestionsDiv.innerHTML = '';
                    
                    if (data.length > 0) {
                        data.forEach(tag => {
                            const div = document.createElement('div');
                            div.className = 'p-2 cursor-pointer hover:bg-gray-100 text-gray-700';
                            div.textContent = tag.name;
                            div.addEventListener('click', function() {
                                tagInput.value = tag.name;
                                suggestionsDiv.style.display = 'none';
                            });
                            suggestionsDiv.appendChild(div);
                        });
                        suggestionsDiv.style.display = 'block';
                    } else {
                        suggestionsDiv.style.display = 'none';
                    }
                });
        });
        
        document.addEventListener('click', function(e) {
            if (!tagInput.contains(e.target) && !suggestionsDiv.contains(e.target)) {
                suggestionsDiv.style.display = 'none';
            }
        });
    }
});
</script>

<style>
/* Accessibility Styles */
.high-contrast {
    filter: invert(100%) hue-rotate(180deg);
}

.high-contrast img {
    filter: invert(100%) hue-rotate(180deg);
}

.focus-mode .container > *:not(:focus-within):not(.focus-exempt) {
    opacity: 0.5;
}

.focus-mode *:focus-within {
    outline: 2px solid #3b82f6 !important;
}

/* Toggle Switch Styles */
.switch {
    position: relative;
    display: inline-block;
    width: 46px;
    height: 24px;
}

.switch input {
    opacity: 0;
    width: 0;
    height: 0;
}

.slider {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: .4s;
}

.slider:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: .4s;
}

input:checked + .slider {
    background-color: #2563eb;
}

input:focus + .slider {
    box-shadow: 0 0 1px #2563eb;
}

input:checked + .slider:before {
    transform: translateX(22px);
}

.slider.round {
    border-radius: 34px;
}

.slider.round:before {
    border-radius: 50%;
}
</style>

    <!-- الطبقة السادسة: الفوتر -->
    <?php include 'theme/homepage/footer.php'; ?>

    <!-- زر إمكانية الوصول -->
    <div id="accessibility-toggle" class="accessibility-button" aria-label="Accessibility options">
        <i class="fas fa-universal-access"></i>
    </div>

    <!-- قائمة إمكانية الوصول -->
    <?php include 'theme/homepage/accessibility.php'; ?>

    <!-- Scripts -->
    <script src="/assets/js/scripts.js"></script>
    <script src="/assets/js/search.js"></script>
    <script src="/assets/js/accessibility.js"></script>
</body>
</html>