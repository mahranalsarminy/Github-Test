<?php
// Set page title
$pageTitle = 'Geographic Reports - WallPix Admin';

// Include header
require_once '../../theme/admin/header.php';

// Current date and time in UTC
$currentDateTime = '2025-03-24 12:13:47';
$currentUser = 'mahranalsarminy';

// Initialize variables
$period = isset($_GET['period']) ? $_GET['period'] : 'month';
$validPeriods = ['week', 'month', 'year', 'custom'];

if (!in_array($period, $validPeriods)) {
    $period = 'month';
}

// Date range for custom period
$startDate = isset($_GET['start']) ? $_GET['start'] : date('Y-m-d', strtotime('-30 days'));
$endDate = isset($_GET['end']) ? $_GET['end'] : date('Y-m-d');

// Determine date range based on period
switch ($period) {
    case 'week':
        $startDate = date('Y-m-d', strtotime('-7 days'));
        $endDate = date('Y-m-d');
        break;
    case 'month':
        $startDate = date('Y-m-d', strtotime('-30 days'));
        $endDate = date('Y-m-d');
        break;
    case 'year':
        $startDate = date('Y-m-d', strtotime('-1 year'));
        $endDate = date('Y-m-d');
        break;
    // For custom, use the provided dates
}

// Search and filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$continentFilter = isset($_GET['continent']) ? $_GET['continent'] : 'all';

// Check if country column exists in users table
$countryColumnExists = false;
try {
    $checkColumnQuery = $pdo->query("SHOW COLUMNS FROM users LIKE 'country'");
    $countryColumnExists = $checkColumnQuery->rowCount() > 0;
} catch (Exception $e) {
    // Column doesn't exist
}

// Get geographic data
try {
    if ($countryColumnExists) {
        // Real data from users table
        $query = "SELECT 
                    COALESCE(country, 'Unknown') as country,
                    COUNT(*) as user_count,
                    COUNT(DISTINCT md.user_id) as active_users,
                    COUNT(DISTINCT md.id) as downloads
                  FROM users u
                  LEFT JOIN media_downloads md ON u.id = md.user_id AND md.downloaded_at BETWEEN ? AND ?
                  WHERE u.created_at <= ?
                  GROUP BY country
                  ORDER BY user_count DESC";
        
        $stmt = $pdo->prepare($query);
        $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59', $endDate . ' 23:59:59']);
        $countryData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // Generate demo data for visualization
        $countryData = [
            ['country' => 'United States', 'user_count' => 2450, 'active_users' => 1654, 'downloads' => 14320],
            ['country' => 'United Kingdom', 'user_count' => 1580, 'active_users' => 983, 'downloads' => 7845],
            ['country' => 'India', 'user_count' => 1235, 'active_users' => 765, 'downloads' => 5897],
            ['country' => 'Germany', 'user_count' => 950, 'active_users' => 589, 'downloads' => 4578],
            ['country' => 'Canada', 'user_count' => 820, 'active_users' => 512, 'downloads' => 3956],
            ['country' => 'Australia', 'user_count' => 745, 'active_users' => 465, 'downloads' => 3589],
            ['country' => 'Brazil', 'user_count' => 650, 'active_users' => 398, 'downloads' => 2987],
            ['country' => 'France', 'user_count' => 610, 'active_users' => 378, 'downloads' => 2854],
            ['country' => 'Japan', 'user_count' => 580, 'active_users' => 354, 'downloads' => 2756],
            ['country' => 'Netherlands', 'user_count' => 520, 'active_users' => 321, 'downloads' => 2489],
            ['country' => 'Italy', 'user_count' => 480, 'active_users' => 312, 'downloads' => 2345],
            ['country' => 'Spain', 'user_count' => 450, 'active_users' => 289, 'downloads' => 2178],
            ['country' => 'Sweden', 'user_count' => 390, 'active_users' => 245, 'downloads' => 1925],
            ['country' => 'Russia', 'user_count' => 350, 'active_users' => 217, 'downloads' => 1765],
            ['country' => 'Mexico', 'user_count' => 320, 'active_users' => 198, 'downloads' => 1543],
            ['country' => 'Unknown', 'user_count' => 895, 'active_users' => 356, 'downloads' => 2934],
        ];
    }
    
    // Group countries by continent for charts
    $continentMapping = [
        'North America' => ['United States', 'Canada', 'Mexico'],
        'Europe' => ['United Kingdom', 'Germany', 'France', 'Netherlands', 'Italy', 'Spain', 'Sweden', 'Russia'],
        'Asia' => ['India', 'Japan', 'China', 'South Korea', 'Singapore'],
        'Oceania' => ['Australia', 'New Zealand'],
        'South America' => ['Brazil', 'Argentina', 'Colombia', 'Chile'],
        'Africa' => ['South Africa', 'Nigeria', 'Egypt', 'Morocco'],
        'Other' => ['Unknown']
    ];
    
    $continentData = [
        'North America' => ['count' => 0, 'downloads' => 0],
        'Europe' => ['count' => 0, 'downloads' => 0],
        'Asia' => ['count' => 0, 'downloads' => 0],
        'Oceania' => ['count' => 0, 'downloads' => 0],
        'South America' => ['count' => 0, 'downloads' => 0],
        'Africa' => ['count' => 0, 'downloads' => 0],
        'Other' => ['count' => 0, 'downloads' => 0]
    ];
    
    foreach ($countryData as $data) {
        $assigned = false;
        foreach ($continentMapping as $continent => $countries) {
            if (in_array($data['country'], $countries)) {
                $continentData[$continent]['count'] += $data['user_count'];
                $continentData[$continent]['downloads'] += $data['downloads'];
                $assigned = true;
                break;
            }
        }
        
        if (!$assigned) {
            $continentData['Other']['count'] += $data['user_count'];
            $continentData['Other']['downloads'] += $data['downloads'];
        }
    }
    
    // Prepare data for charts
    $continentLabels = array_keys($continentData);
    $continentUserCounts = array_column(array_values($continentData), 'count');
    $continentDownloadCounts = array_column(array_values($continentData), 'downloads');
    
    // Prepare country data for map chart
    $countryLabels = array_column($countryData, 'country');
    $countryUserCounts = array_column($countryData, 'user_count');
    
    // Calculate totals
    $totalUsers = array_sum($countryUserCounts);
    $totalDownloads = array_sum(array_column($countryData, 'downloads'));
    
    // Filter countries data if continent filter is applied
    if ($continentFilter !== 'all') {
        $filteredCountryData = [];
        foreach ($countryData as $data) {
            foreach ($continentMapping as $continent => $countries) {
                if ($continent === $continentFilter && in_array($data['country'], $countries)) {
                    $filteredCountryData[] = $data;
                    break;
                }
            }
        }
        $countryData = $filteredCountryData;
    }
    
    // Apply search filter
    if (!empty($search)) {
        $filtered = [];
        foreach ($countryData as $data) {
            if (stripos($data['country'], $search) !== false) {
                $filtered[] = $data;
            }
        }
        $countryData = $filtered;
    }
    
} catch (PDOException $e) {
    $errorMessage = "Database error: " . $e->getMessage();
    $countryData = [];
    $continentData = [];
    $continentLabels = [];
    $continentUserCounts = [];
    $continentDownloadCounts = [];
    $countryLabels = [];
    $countryUserCounts = [];
    $totalUsers = 0;
    $totalDownloads = 0;
}

// Include sidebar
require_once '../../theme/admin/slidbar.php';
?>

<!-- Main Content -->
<div class="content-wrapper p-4 sm:ml-64">
    <div class="p-4 mt-14">
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold <?php echo isset($darkMode) && $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                    <?php echo $lang['geographic_reports'] ?? 'Geographic Reports'; ?>
                </h1>
                <p class="mt-2 text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                    <?php echo $lang['geo_description'] ?? 'Analyze user distribution and activity by country'; ?>
                </p>
            </div>
            <div class="mt-4 md:mt-0">
                <a href="export.php?type=geography&period=<?php echo $period; ?>&start=<?php echo $startDate; ?>&end=<?php echo $endDate; ?>" class="btn bg-green-500 hover:bg-green-600 text-white">
                    <i class="fas fa-file-excel mr-2"></i> <?php echo $lang['export_csv'] ?? 'Export CSV'; ?>
                </a>
            </div>
        </div>

        <!-- Period Selector -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6 <?php echo isset($darkMode) && $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
            <form action="" method="GET" class="flex flex-wrap items-end gap-4">
                <div class="w-full md:w-auto">
                    <label for="period" class="block text-sm font-medium mb-2 <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                        <?php echo $lang['period'] ?? 'Period'; ?>
                    </label>
                    <select id="period" name="period" 
                        class="w-full md:w-40 p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        onchange="toggleCustomDateFields()">
                        <option value="week" <?php echo $period === 'week' ? 'selected' : ''; ?>>
                            <?php echo $lang['last_7_days'] ?? 'Last 7 days'; ?>
                        </option>
                        <option value="month" <?php echo $period === 'month' ? 'selected' : ''; ?>>
                            <?php echo $lang['last_30_days'] ?? 'Last 30 days'; ?>
                        </option>
                        <option value="year" <?php echo $period === 'year' ? 'selected' : ''; ?>>
                            <?php echo $lang['last_year'] ?? 'Last year'; ?>
                        </option>
                        <option value="custom" <?php echo $period === 'custom' ? 'selected' : ''; ?>>
                            <?php echo $lang['custom_range'] ?? 'Custom range'; ?>
                        </option>
                    </select>
                </div>
                
                <!-- Custom date fields -->
                <div id="customDateFields" class="flex flex-wrap gap-4 <?php echo $period === 'custom' ? '' : 'hidden'; ?>">
                    <div class="w-full md:w-auto">
                        <label for="start" class="block text-sm font-medium mb-2 <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                            <?php echo $lang['start_date'] ?? 'Start Date'; ?>
                        </label>
                        <input type="date" id="start" name="start" value="<?php echo $startDate; ?>"
                            class="w-full md:w-auto p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="w-full md:w-auto">
                        <label for="end" class="block text-sm font-medium mb-2 <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                            <?php echo $lang['end_date'] ?? 'End Date'; ?>
                        </label>
                        <input type="date" id="end" name="end" value="<?php echo $endDate; ?>"
                            class="w-full md:w-auto p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                
                <!-- Continent filter -->
                <div class="w-full md:w-auto">
                    <label for="continent" class="block text-sm font-medium mb-2 <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                        <?php echo $lang['continent'] ?? 'Continent'; ?>
                    </label>
                    <select id="continent" name="continent" class="w-full md:w-48 p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="all" <?php echo $continentFilter === 'all' ? 'selected' : ''; ?>>
                            <?php echo $lang['all_continents'] ?? 'All Continents'; ?>
                        </option>
                        <option value="North America" <?php echo $continentFilter === 'North America' ? 'selected' : ''; ?>>
                            <?php echo $lang['north_america'] ?? 'North America'; ?>
                        </option>
                        <option value="Europe" <?php echo $continentFilter === 'Europe' ? 'selected' : ''; ?>>
                            <?php echo $lang['europe'] ?? 'Europe'; ?>
                        </option>
                        <option value="Asia" <?php echo $continentFilter === 'Asia' ? 'selected' : ''; ?>>
                            <?php echo $lang['asia'] ?? 'Asia'; ?>
                        </option>
                        <option value="South America" <?php echo $continentFilter === 'South America' ? 'selected' : ''; ?>>
                            <?php echo $lang['south_america'] ?? 'South America'; ?>
                        </option>
                        <option value="Africa" <?php echo $continentFilter === 'Africa' ? 'selected' : ''; ?>>
                            <?php echo $lang['africa'] ?? 'Africa'; ?>
                        </option>
                        <option value="Oceania" <?php echo $continentFilter === 'Oceania' ? 'selected' : ''; ?>>
                            <?php echo $lang['oceania'] ?? 'Oceania'; ?>
                        </option>
                    </select>
                </div>
                
                <!-- Search box -->
                <div class="w-full md:w-auto flex-grow">
                    <label for="search" class="block text-sm font-medium mb-2 <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                        <?php echo $lang['search'] ?? 'Search'; ?>
                    </label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="<?php echo $lang['search_countries'] ?? 'Search countries...'; ?>"
                        class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>
                
                <!-- Apply button -->
                <div class="w-full md:w-auto">
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <i class="fas fa-filter mr-2"></i> <?php echo $lang['apply'] ?? 'Apply'; ?>
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-6">
            <!-- Total Users Card -->
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo isset($darkMode) && $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-500 bg-opacity-10 text-blue-500">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold <?php echo isset($darkMode) && $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                            <?php echo $lang['total_users'] ?? 'Total Users'; ?>
                        </h3>
                        <p class="text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                            <?php echo $lang['by_region'] ?? 'By Region'; ?>
                        </p>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-center justify-between">
                        <p class="text-2xl font-bold <?php echo isset($darkMode) && $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                            <?php echo number_format($totalUsers); ?>
                        </p>
                        <p class="text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                            <?php echo $lang['worldwide'] ?? 'Worldwide'; ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Top Country Card -->
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo isset($darkMode) && $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-500 bg-opacity-10 text-green-500">
                        <i class="fas fa-flag text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold <?php echo isset($darkMode) && $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                            <?php echo $lang['top_country'] ?? 'Top Country'; ?>
                        </h3>
                        <p class="text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                            <?php echo $lang['by_users'] ?? 'By Users'; ?>
                        </p>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-center justify-between">
                        <?php 
                            $topCountry = !empty($countryData) ? $countryData[0]['country'] : 'N/A'; 
                            $topCountryUsers = !empty($countryData) ? $countryData[0]['user_count'] : 0;
                            $topCountryPercent = $totalUsers > 0 ? round(($topCountryUsers / $totalUsers) * 100) : 0;
                        ?>
                        <p class="text-2xl font-bold <?php echo isset($darkMode) && $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                            <?php echo htmlspecialchars($topCountry); ?>
                        </p>
                        <p class="text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                            <?php echo $topCountryPercent; ?>% <?php echo $lang['of_users'] ?? 'of users'; ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Total Downloads Card -->
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo isset($darkMode) && $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-500 bg-opacity-10 text-purple-500">
                        <i class="fas fa-download text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold <?php echo isset($darkMode) && $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                            <?php echo $lang['total_downloads'] ?? 'Total Downloads'; ?>
                        </h3>
                        <p class="text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                            <?php echo $lang['in_period'] ?? 'In Period'; ?>
                        </p>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-center justify-between">
                        <p class="text-2xl font-bold <?php echo isset($darkMode) && $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                            <?php echo number_format($totalDownloads); ?>
                        </p>
                        <p class="text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                            <?php echo $lang['worldwide'] ?? 'Worldwide'; ?>
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Countries Card -->
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo isset($darkMode) && $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-500 bg-opacity-10 text-yellow-500">
                        <i class="fas fa-globe text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold <?php echo isset($darkMode) && $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                            <?php echo $lang['countries'] ?? 'Countries'; ?>
                        </h3>
                        <p class="text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                            <?php echo $lang['user_locations'] ?? 'User Locations'; ?>
                        </p>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="flex items-center justify-between">
                        <p class="text-2xl font-bold <?php echo isset($darkMode) && $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                            <?php echo count($countryData); ?>
                        </p>
                        <p class="text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                            <?php echo $lang['represented'] ?? 'Represented'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Chart Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
            <!-- Continent Distribution Chart -->
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo isset($darkMode) && $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
                <h3 class="text-lg font-semibold mb-4 <?php echo isset($darkMode) && $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                    <?php echo $lang['users_by_continent'] ?? 'Users by Continent'; ?>
                </h3>
                <div class="relative" style="height: 300px;">
                    <canvas id="continentChart"></canvas>
                </div>
            </div>
            
            <!-- Downloads by Continent Chart -->
            <div class="bg-white rounded-lg shadow-md p-6 <?php echo isset($darkMode) && $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
                <h3 class="text-lg font-semibold mb-4 <?php echo isset($darkMode) && $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                    <?php echo $lang['downloads_by_continent'] ?? 'Downloads by Continent'; ?>
                </h3>
                <div class="relative" style="height: 300px;">
                    <canvas id="downloadChart"></canvas>
                </div>
            </div>
        </div>
        
        <!-- Country List -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6 <?php echo isset($darkMode) && $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
            <h3 class="text-lg font-semibold mb-4 <?php echo isset($darkMode) && $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                <?php echo $lang['country_statistics'] ?? 'Country Statistics'; ?>
            </h3>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 <?php echo isset($darkMode) && $darkMode ? 'divide-gray-700' : ''; ?>">
                    <thead class="<?php echo isset($darkMode) && $darkMode ? 'bg-gray-700' : 'bg-gray-50'; ?>">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                <?php echo $lang['country'] ?? 'Country'; ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                <?php echo $lang['users'] ?? 'Users'; ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                <?php echo $lang['active_users'] ?? 'Active Users'; ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                <?php echo $lang['downloads'] ?? 'Downloads'; ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-500'; ?> uppercase tracking-wider">
                                <?php echo $lang['percentage'] ?? 'Percentage'; ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="<?php echo isset($darkMode) && $darkMode ? 'bg-gray-800 divide-gray-700' : 'bg-white divide-gray-200'; ?>">
                        <?php if (count($countryData) > 0): ?>
                            <?php foreach($countryData as $country): ?>
                                <tr class="<?php echo isset($darkMode) && $darkMode ? 'hover:bg-gray-700' : 'hover:bg-gray-50'; ?>">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium <?php echo isset($darkMode) && $darkMode ? 'text-white' : 'text-gray-900'; ?>">
                                        <?php echo htmlspecialchars($country['country']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                        <?php echo number_format($country['user_count']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                        <?php echo number_format($country['active_users']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                        <?php echo number_format($country['downloads']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php 
                                            $percentage = $totalUsers > 0 ? round(($country['user_count'] / $totalUsers) * 100, 1) : 0;
                                        ?>
                                        <div class="flex items-center">
                                            <span class="text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-300' : 'text-gray-900'; ?> mr-2">
                                                <?php echo $percentage; ?>%
                                            </span>
                                            <div class="w-24 bg-gray-200 rounded-full h-2 <?php echo isset($darkMode) && $darkMode ? 'bg-gray-700' : ''; ?>">
                                                <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $percentage; ?>%"></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    <?php 
                                    if (!empty($search)) {
                                        echo $lang['no_countries_found_search'] ?? 'No countries found matching your search criteria.';
                                    } elseif ($continentFilter !== 'all') {
                                        echo $lang['no_countries_found_continent'] ?? 'No countries found in this continent for the selected period.';
                                    } else {
                                        echo $lang['no_countries_found'] ?? 'No country data available for the selected period.';
                                    }
                                    ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Last Update Info -->
        <div class="mt-6 text-right text-sm <?php echo isset($darkMode) && $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
            <?php echo $lang['last_updated'] ?? 'Last Updated'; ?>: 
            <?php echo $currentDateTime; ?>
            | <?php echo $lang['user'] ?? 'User'; ?>: 
            <?php echo htmlspecialchars($currentUser); ?>
        </div>
    </div>
</div>

<!-- Include Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>

<!-- JavaScript for Charts -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Function to toggle custom date fields
    window.toggleCustomDateFields = function() {
        const periodSelect = document.getElementById('period');
        const customDateFields = document.getElementById('customDateFields');
        
        if (periodSelect.value === 'custom') {
            customDateFields.classList.remove('hidden');
        } else {
            customDateFields.classList.add('hidden');
        }
    }
    
    // Continent distribution chart
    const continentCtx = document.getElementById('continentChart').getContext('2d');
    const continentChart = new Chart(continentCtx, {
        type: 'doughnut',
        data: {
            labels: <?php echo json_encode($continentLabels ?? []); ?>,
            datasets: [{
                data: <?php echo json_encode($continentUserCounts ?? []); ?>,
                backgroundColor: [
                    'rgba(59, 130, 246, 0.7)',
                    'rgba(16, 185, 129, 0.7)',
                    'rgba(245, 158, 11, 0.7)',
                    'rgba(239, 68, 68, 0.7)',
                    'rgba(139, 92, 246, 0.7)',
                    'rgba(236, 72, 153, 0.7)',
                    'rgba(107, 114, 128, 0.7)'
                ],
                borderColor: [
                    'rgba(59, 130, 246, 1)',
                    'rgba(16, 185, 129, 1)',
                    'rgba(245, 158, 11, 1)',
                    'rgba(239, 68, 68, 1)',
                    'rgba(139, 92, 246, 1)',
                    'rgba(236, 72, 153, 1)',
                    'rgba(107, 114, 128, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        color: '<?php echo isset($darkMode) && $darkMode ? "rgba(255, 255, 255, 0.8)" : "rgba(0, 0, 0, 0.8)"; ?>',
                        font: {
                            size: 12
                        },
                        boxWidth: 15
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const label = context.label || '';
                            const value = context.raw || 0;
                            const total = context.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                            const percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
    
    // Downloads by continent chart
    const downloadCtx = document.getElementById('downloadChart').getContext('2d');
    const downloadChart = new Chart(downloadCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($continentLabels ?? []); ?>,
            datasets: [{
                label: '<?php echo $lang['downloads'] ?? 'Downloads'; ?>',
                data: <?php echo json_encode($continentDownloadCounts ?? []); ?>,
                backgroundColor: 'rgba(139, 92, 246, 0.7)',
                borderColor: 'rgba(139, 92, 246, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    display: true,
                    ticks: {
                        color: '<?php echo isset($darkMode) && $darkMode ? "rgba(255, 255, 255, 0.6)" : "rgba(0, 0, 0, 0.6)"; ?>'
                    },
                    grid: {
                        display: false
                    }
                },
                y: {
                    display: true,
                    ticks: {
                        color: '<?php echo isset($darkMode) && $darkMode ? "rgba(255, 255, 255, 0.6)" : "rgba(0, 0, 0, 0.6)"; ?>'
                    },
                    grid: {
                        color: '<?php echo isset($darkMode) && $darkMode ? "rgba(255, 255, 255, 0.1)" : "rgba(0, 0, 0, 0.1)"; ?>'
                    }
                }
            }
        }
    });
});
</script>

<?php
// Include footer
require_once '../../theme/admin/footer.php';
?>