<?php
// Set page title
$pageTitle = 'Geographic Reports - WallPix Admin';

// Include header
require_once '../../theme/admin/header.php';

// Current date and time in UTC
$currentDateTime = '2025-03-18 11:31:16';
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

// Get regional data
try {
    // Regional user data
    $stmt = $pdo->prepare("
        SELECT 
            country, 
            COUNT(*) as user_count 
        FROM users 
        WHERE created_at BETWEEN ? AND ? 
        GROUP BY country 
        ORDER BY user_count DESC
    ");
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    $usersByCountry = $stmt->fetchAll();
    
    // Regional download data
    $stmt = $pdo->prepare("
        SELECT 
            u.country, 
            COUNT(d.id) as download_count 
        FROM downloads d
        JOIN users u ON d.user_id = u.id
        WHERE d.download_date BETWEEN ? AND ?
        GROUP BY u.country
        ORDER BY download_count DESC
    ");
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    $downloadsByCountry = $stmt->fetchAll();
    
    // Regional revenue data
    $stmt = $pdo->prepare("
        SELECT 
            u.country, 
            SUM(p.amount) as revenue 
        FROM payments p
        JOIN users u ON p.user_id = u.id
        WHERE p.payment_date BETWEEN ? AND ? AND p.status = 'completed'
        GROUP BY u.country
        ORDER BY revenue DESC
    ");
    $stmt->execute([$startDate . ' 00:00:00', $endDate . ' 23:59:59']);
    $revenueByCountry = $stmt->fetchAll();
    
    // Format data for map visualization
    $mapData = [];
    
    // Process users by country
    foreach ($usersByCountry as $country) {
        $countryCode = $country['country'] ?? 'Unknown';
        if (!isset($mapData[$countryCode])) {
            $mapData[$countryCode] = [
                'users' => 0,
                'downloads' => 0,
                'revenue' => 0
            ];
        }
        $mapData[$countryCode]['users'] = (int)$country['user_count'];
    }
    
    // Process downloads by country
    foreach ($downloadsByCountry as $country) {
        $countryCode = $country['country'] ?? 'Unknown';
        if (!isset($mapData[$countryCode])) {
            $mapData[$countryCode] = [
                'users' => 0,
                'downloads' => 0,
                'revenue' => 0
            ];
        }
        $mapData[$countryCode]['downloads'] = (int)$country['download_count'];
    }
    
    // Process revenue by country
    foreach ($revenueByCountry as $country) {
        $countryCode = $country['country'] ?? 'Unknown';
        if (!isset($mapData[$countryCode])) {
            $mapData[$countryCode] = [
                'users' => 0,
                'downloads' => 0,
                'revenue' => 0
            ];
        }
        $mapData[$countryCode]['revenue'] = (float)$country['revenue'];
    }
    
} catch (PDOException $e) {
    $errorMessage = "Database error: " . $e->getMessage();
}

// Include sidebar
require_once '../../theme/admin/slidbar.php';
?>
<!-- Main Content -->
<div class="content-wrapper min-h-screen bg-gray-100 <?php echo $darkMode ? 'dark-mode' : ''; ?>">
    <div class="px-6 py-8">
        <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between">
            <div>
                <h1 class="text-3xl font-bold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                    <?php echo $lang['geographic_reports'] ?? 'Geographic Reports'; ?>
                </h1>
                <p class="mt-2 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                    <?php echo $lang['regional_distribution_data'] ?? 'Analyze user activity by region'; ?>
                    <span class="ml-2"><?php echo $startDate; ?> - <?php echo $endDate; ?></span>
                </p>
            </div>
            <div class="mt-4 md:mt-0 flex space-x-2">
                <a href="index.php" class="btn bg-gray-500 hover:bg-gray-600 text-white">
                    <i class="fas fa-arrow-left mr-2"></i> <?php echo $lang['back_to_dashboard'] ?? 'Back to Dashboard'; ?>
                </a>
                <a href="export-geography.php?period=<?php echo $period; ?>&start=<?php echo $startDate; ?>&end=<?php echo $endDate; ?>" class="btn bg-green-500 hover:bg-green-600 text-white">
                    <i class="fas fa-file-excel mr-2"></i> <?php echo $lang['export_csv'] ?? 'Export CSV'; ?>
                </a>
            </div>
        </div>
        
        <!-- Period Selector -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
            <form action="" method="GET" class="flex flex-wrap items-end space-y-4 md:space-y-0 space-x-0 md:space-x-4">
                <div class="w-full md:w-auto">
                    <label for="period" class="block text-sm font-medium mb-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
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
                <div id="customDateFields" class="flex flex-wrap space-y-4 md:space-y-0 space-x-0 md:space-x-4 <?php echo $period === 'custom' ? '' : 'hidden'; ?>">
                    <div class="w-full md:w-auto">
                        <label for="start" class="block text-sm font-medium mb-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                            <?php echo $lang['start_date'] ?? 'Start Date'; ?>
                        </label>
                        <input type="date" id="start" name="start" value="<?php echo $startDate; ?>"
                            class="w-full md:w-auto p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div class="w-full md:w-auto">
                        <label for="end" class="block text-sm font-medium mb-2 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                            <?php echo $lang['end_date'] ?? 'End Date'; ?>
                        </label>
                        <input type="date" id="end" name="end" value="<?php echo $endDate; ?>"
                            class="w-full md:w-auto p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>
                
                <div class="w-full md:w-auto">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter mr-2"></i> <?php echo $lang['apply'] ?? 'Apply'; ?>
                    </button>
                </div>
            </form>
        </div>
        
        <!-- Map Visualization -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
            <h3 class="text-lg font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                <?php echo $lang['world_map_visualization'] ?? 'World Map Visualization'; ?>
            </h3>
            
            <div class="flex items-center mb-4">
                <span class="text-sm font-medium mr-4 <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>">
                    <?php echo $lang['show_data'] ?? 'Show Data'; ?>:
                </span>
                <div class="flex space-x-4">
                    <label class="inline-flex items-center">
                        <input type="radio" name="map-data" value="users" class="form-radio" checked onclick="updateMapVisualization('users')">
                        <span class="ml-2 text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>"><?php echo $lang['users'] ?? 'Users'; ?></span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="map-data" value="downloads" class="form-radio" onclick="updateMapVisualization('downloads')">
                        <span class="ml-2 text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>"><?php echo $lang['downloads'] ?? 'Downloads'; ?></span>
                    </label>
                    <label class="inline-flex items-center">
                        <input type="radio" name="map-data" value="revenue" class="form-radio" onclick="updateMapVisualization('revenue')">
                        <span class="ml-2 text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-700'; ?>"><?php echo $lang['revenue'] ?? 'Revenue'; ?></span>
                    </label>
                </div>
            </div>
            
            <div class="world-map-container" style="height: 500px;">
                <div id="world-map" style="width: 100%; height: 100%;"></div>
            </div>
        </div>
        
        <!-- Tabular Data -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
            <h3 class="text-lg font-semibold mb-4 <?php echo $darkMode ? 'text-white' : 'text-gray-700'; ?>">
                <?php echo $lang['regional_data'] ?? 'Regional Data'; ?>
            </h3>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-700' : ''; ?>">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                <?php echo $lang['country'] ?? 'Country'; ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                <?php echo $lang['users'] ?? 'Users'; ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                <?php echo $lang['downloads'] ?? 'Downloads'; ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                <?php echo $lang['revenue'] ?? 'Revenue'; ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-700' : ''; ?>">
                        <?php 
                        // Sort countries by user count
                        arsort($mapData);
                        
                        foreach ($mapData as $country => $data) : 
                            if ($country === 'Unknown' || empty($country)) continue; // Skip unknown countries
                        ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                    <?php echo htmlspecialchars($country); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                    <?php echo number_format($data['users']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                    <?php echo number_format($data['downloads']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm <?php echo $darkMode ? 'text-gray-300' : 'text-gray-900'; ?>">
                                    $<?php echo number_format($data['revenue'], 2); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Last Update Info -->
        <div class="mt-6 text-right text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
            <?php echo $lang['last_updated'] ?? 'Last Updated'; ?>: 
            <?php echo $currentDateTime; // Using the timestamp you provided: 2025-03-18 11:37:48 ?>
            | <?php echo $lang['user'] ?? 'User'; ?>: 
            <?php echo htmlspecialchars($currentUser); // Using the username you provided: mahranalsarminy ?>
        </div>
    </div>
</div>
<!-- Include Required Libraries -->
<link rel="stylesheet" href="https://unpkg.com/jsvectormap@1.5.3/dist/css/jsvectormap.min.css" />
<script src="https://unpkg.com/jsvectormap@1.5.3/dist/js/jsvectormap.min.js"></script>
<script src="https://unpkg.com/jsvectormap@1.5.3/dist/maps/world.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Toggle custom date fields
    window.toggleCustomDateFields = function() {
        const periodSelect = document.getElementById('period');
        const customDateFields = document.getElementById('customDateFields');
        
        if (periodSelect.value === 'custom') {
            customDateFields.classList.remove('hidden');
        } else {
            customDateFields.classList.add('hidden');
        }
    }
    
    // Map data
    const mapData = <?php echo json_encode($mapData); ?>;
    
    // Initialize map with users data as default
    let currentDataType = 'users';
    let map;
    
    // Initialize the map
    initMap();
    
    // Update map visualization when data type changes
    window.updateMapVisualization = function(dataType) {
        currentDataType = dataType;
        map.remove();
        initMap();
    };
    
    function initMap() {
        // Create data object for the map
        const mapValues = {};
        let maxValue = 0;
        
        // Process data based on selected data type
        Object.keys(mapData).forEach(country => {
            if (country && country !== 'Unknown') {
                const value = mapData[country][currentDataType];
                mapValues[country] = value;
                
                // Track max value for color scale
                if (value > maxValue) {
                    maxValue = value;
                }
            }
        });
        
        // Map visualization options
        let mapTitle = '';
        switch (currentDataType) {
            case 'users':
                mapTitle = '<?php echo $lang['user_distribution'] ?? 'User Distribution'; ?>';
                break;
            case 'downloads':
                mapTitle = '<?php echo $lang['download_distribution'] ?? 'Download Distribution'; ?>';
                break;
            case 'revenue':
                mapTitle = '<?php echo $lang['revenue_distribution'] ?? 'Revenue Distribution'; ?>';
                break;
        }
        
        // Create the map
        map = new jsVectorMap({
            selector: '#world-map',
            map: 'world',
            backgroundColor: '<?php echo $darkMode ? "#1F2937" : "#ffffff"; ?>',
            zoomButtons: true,
            zoomOnScroll: true,
            regionsSelectable: false,
            markersSelectable: false,
            regionStyle: {
                initial: {
                    fill: '<?php echo $darkMode ? "#4B5563" : "#E5E7EB"; ?>',
                    stroke: '<?php echo $darkMode ? "#374151" : "#D1D5DB"; ?>',
                    "stroke-width": 0.5,
                    "stroke-opacity": 1
                },
                hover: {
                    "fill-opacity": 0.8,
                    cursor: 'pointer'
                },
                selected: {
                    fill: '<?php echo $darkMode ? "#60A5FA" : "#3B82F6"; ?>'
                },
                selectedHover: {}
            },
            labels: {
                markers: {
                    render: function(marker) {
                        return marker.name;
                    },
                },
                regions: {
                    render: function(code) {
                        const value = mapValues[code];
                        if (value) {
                            if (currentDataType === 'revenue') {
                                return `${code}: $${value.toFixed(2)}`;
                            } else {
                                return `${code}: ${value}`;
                            }
                        }
                        return code;
                    }
                }
            },
            series: {
                regions: [{
                    attribute: 'fill',
                    scale: {
                        min: '<?php echo $darkMode ? "#BFDBFE" : "#93C5FD"; ?>',
                        max: '<?php echo $darkMode ? "#1D4ED8" : "#2563EB"; ?>'
                    },
                    values: mapValues,
                    legend: {
                        vertical: true,
                        title: mapTitle
                    }
                }]
            },
            onRegionTipShow: function(event, element, code) {
                const value = mapValues[code];
                if (value) {
                    let tooltipContent = '';
                    if (currentDataType === 'users') {
                        tooltipContent = `<strong>${element.text()}</strong><br>${value.toLocaleString()} users`;
                    } else if (currentDataType === 'downloads') {
                        tooltipContent = `<strong>${element.text()}</strong><br>${value.toLocaleString()} downloads`;
                    } else if (currentDataType === 'revenue') {
                        tooltipContent = `<strong>${element.text()}</strong><br>$${value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 })} revenue`;
                    }
                    element.html(tooltipContent);
                } else {
                    element.html(`<strong>${element.text()}</strong><br>No data available`);
                }
            }
        });
    }
});
</script>

<?php
// Include footer
require_once '../../theme/admin/footer.php';
?>