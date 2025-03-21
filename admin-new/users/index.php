<?php
// Set page title
$pageTitle = 'User Management - WallPix Admin';

// Include header
require_once '../../theme/admin/header.php';

// Initialize variables
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$role = isset($_GET['role']) ? $_GET['role'] : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Current date and user information
$currentDateTime = '2025-03-17 22:43:22'; // Current UTC datetime
$currentUser = 'mahranalsarminy'; // Current user login

// Handle user deletion
if (isset($_POST['delete_user']) && isset($_POST['user_id'])) {
    $userId = (int)$_POST['user_id'];
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Delete related records first
        $pdo->prepare("DELETE FROM subscriptions WHERE user_id = ?")->execute([$userId]);
        $pdo->prepare("DELETE FROM user_subscriptions WHERE user_id = ?")->execute([$userId]);
        $pdo->prepare("DELETE FROM payments WHERE user_id = ?")->execute([$userId]);
        $pdo->prepare("DELETE FROM notifications WHERE user_id = ?")->execute([$userId]);
        
        // Delete the user
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $deleted = $stmt->execute([$userId]);
        
        // Log the activity
        $stmt = $pdo->prepare("INSERT INTO activities (description) VALUES (?)");
        $stmt->execute(["User with ID {$userId} was deleted by {$currentUser} on {$currentDateTime}"]);
        
        // Commit transaction
        $pdo->commit();
        
        $successMessage = "User successfully deleted.";
    } catch (PDOException $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        $errorMessage = "Error deleting user: " . $e->getMessage();
    }
}

// Build query based on filters
$params = [];
$whereConditions = [];

if (!empty($search)) {
    $whereConditions[] = "(username LIKE ? OR email LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

if (!empty($role)) {
    $whereConditions[] = "role = ?";
    $params[] = $role;
}

$whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

// Get total users count for pagination
try {
    $countSql = "SELECT COUNT(*) as total FROM users {$whereClause}";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalUsers = $countStmt->fetch()['total'];
    $totalPages = ceil($totalUsers / $perPage);
} catch (PDOException $e) {
    $errorMessage = "Error counting users: " . $e->getMessage();
}

// Get users with pagination
try {
    $sql = "SELECT id, username, email, profile_picture, role, created_at 
            FROM users {$whereClause} 
            ORDER BY created_at DESC 
            LIMIT {$offset}, {$perPage}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    $errorMessage = "Error fetching users: " . $e->getMessage();
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
                    <?php echo $lang['users'] ?? 'Users'; ?>
                </h1>
                <p class="mt-2 text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?>">
                    <?php echo $lang['manage_users'] ?? 'Manage all users in the system'; ?>
                    <span class="ml-2"><?php echo $currentDateTime; ?></span>
                </p>
            </div>
            <div class="mt-4 md:mt-0">
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus mr-2"></i> <?php echo $lang['add_user'] ?? 'Add User'; ?>
                </a>
            </div>
        </div>
        
        <?php if (isset($successMessage)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                <span class="font-bold">Success:</span> <?php echo htmlspecialchars($successMessage); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($errorMessage)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <span class="font-bold">Error:</span> <?php echo htmlspecialchars($errorMessage); ?>
            </div>
        <?php endif; ?>
        
        <!-- Filters -->
        <div class="bg-white rounded-lg shadow-md p-6 mb-6 <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
            <form action="" method="GET" class="flex flex-col md:flex-row space-y-4 md:space-y-0 md:space-x-4">
                <div class="w-full md:w-1/3">
                    <label for="search" class="block text-sm font-medium mb-2 <?php echo $darkMode ? 'text-gray-400' : 'text-gray-700'; ?>">
                        <?php echo $lang['search'] ?? 'Search'; ?>
                    </label>
                    <input type="text" id="search" name="search" value="<?php echo htmlspecialchars($search); ?>"
                        class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                        placeholder="<?php echo $lang['search_by_username_email'] ?? 'Search by username or email'; ?>">
                </div>
                <div class="w-full md:w-1/3">
                    <label for="role" class="block text-sm font-medium mb-2 <?php echo $darkMode ? 'text-gray-400' : 'text-gray-700'; ?>">
                        <?php echo $lang['filter_by_role'] ?? 'Filter by Role'; ?>
                    </label>
                    <select id="role" name="role" class="w-full p-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value=""><?php echo $lang['all_roles'] ?? 'All Roles'; ?></option>
                        <option value="admin" <?php echo $role === 'admin' ? 'selected' : ''; ?>><?php echo $lang['admin'] ?? 'Admin'; ?></option>
                        <option value="subscriber" <?php echo $role === 'subscriber' ? 'selected' : ''; ?>><?php echo $lang['subscriber'] ?? 'Subscriber'; ?></option>
                        <option value="free_user" <?php echo $role === 'free_user' ? 'selected' : ''; ?>><?php echo $lang['free_user'] ?? 'Free User'; ?></option>
                    </select>
                </div>
                <div class="w-full md:w-1/3 flex items-end">
                    <button type="submit" class="w-full md:w-auto btn btn-primary">
                        <i class="fas fa-search mr-2"></i> <?php echo $lang['search'] ?? 'Search'; ?>
                    </button>
                    <a href="index.php" class="ml-2 btn bg-gray-300 hover:bg-gray-400 text-gray-800">
                        <i class="fas fa-sync-alt mr-2"></i> <?php echo $lang['reset'] ?? 'Reset'; ?>
                    </a>
                </div>
            </form>
        </div>
        
        <!-- Users Table -->
        <div class="bg-white rounded-lg shadow-md <?php echo $darkMode ? 'bg-gray-800 text-white' : ''; ?>">
            <div class="p-6 border-b <?php echo $darkMode ? 'border-gray-700' : 'border-gray-200'; ?>">
                <h2 class="text-lg font-bold <?php echo $darkMode ? 'text-white' : 'text-gray-800'; ?>">
                    <?php echo $lang['users_list'] ?? 'Users List'; ?>
                </h2>
                <p class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-600'; ?> mt-1">
                    <?php echo sprintf($lang['showing_results'] ?? 'Showing %d out of %d total users', count($users), $totalUsers); ?>
                </p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-700' : ''; ?>">
                    <thead>
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                <?php echo $lang['username'] ?? 'Username'; ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                <?php echo $lang['email'] ?? 'Email'; ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                <?php echo $lang['role'] ?? 'Role'; ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                <?php echo $lang['created_at'] ?? 'Created At'; ?>
                            </th>
                            <th class="px-6 py-3 text-left text-xs font-medium <?php echo $darkMode ? 'text-gray-400 bg-gray-700' : 'text-gray-500 bg-gray-50'; ?> uppercase tracking-wider">
                                <?php echo $lang['actions'] ?? 'Actions'; ?>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 <?php echo $darkMode ? 'divide-gray-700' : ''; ?>">
                        <?php if (isset($users) && count($users) > 0): ?>
                            <?php foreach ($users as $user): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-10 w-10">
                                                <?php if (!empty($user['profile_picture'])): ?>
                                                    <img class="h-10 w-10 rounded-full" src="<?php echo htmlspecialchars($user['profile_picture']); ?>" alt="Profile picture">
                                                <?php else: ?>
                                                    <div class="h-10 w-10 rounded-full bg-gray-300 flex items-center justify-center <?php echo $darkMode ? 'bg-gray-700' : ''; ?>">
                                                        <i class="fas fa-user text-gray-500"></i>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ml-4">
                                                <div class="<?php echo $darkMode ? 'text-white' : 'text-gray-900'; ?> font-medium">
                                                    <?php echo htmlspecialchars($user['username']); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">
                                        <?php echo htmlspecialchars($user['email']); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                            <?php 
                                                switch($user['role']) {
                                                    case 'admin': 
                                                        echo $darkMode ? 'bg-red-900 text-red-200' : 'bg-red-100 text-red-800';
                                                        break;
                                                    case 'subscriber': 
                                                        echo $darkMode ? 'bg-green-900 text-green-200' : 'bg-green-100 text-green-800';
                                                        break;
                                                    default: 
                                                        echo $darkMode ? 'bg-gray-700 text-gray-300' : 'bg-gray-100 text-gray-800';
                                                }
                                            ?>">
                                            <?php echo htmlspecialchars($user['role']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap <?php echo $darkMode ? 'text-gray-300' : 'text-gray-500'; ?>">
                                        <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <a href="edit.php?id=<?php echo $user['id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">
                                            <i class="fas fa-edit"></i> <?php echo $lang['edit'] ?? 'Edit'; ?>
                                        </a>
                                        <form method="POST" action="" class="inline-block" onsubmit="return confirm('<?php echo $lang['confirm_delete'] ?? 'Are you sure you want to delete this user?'; ?>');">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="delete_user" class="text-red-600 hover:text-red-900">
                                                <i class="fas fa-trash"></i> <?php echo $lang['delete'] ?? 'Delete'; ?>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center <?php echo $darkMode ? 'text-gray-400' : 'text-gray-500'; ?>">
                                    <?php echo $lang['no_users_found'] ?? 'No users found'; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
                <div class="px-6 py-4 border-t <?php echo $darkMode ? 'border-gray-700' : 'border-gray-200'; ?>">
                    <nav class="flex items-center justify-between">
                        <div class="flex-1 flex justify-between sm:hidden">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>" class="btn bg-white <?php echo $darkMode ? 'bg-gray-700 text-gray-300' : ''; ?>">
                                    <?php echo $lang['previous'] ?? 'Previous'; ?>
                                </a>
                            <?php endif; ?>
                            
                            <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>" class="btn bg-white <?php echo $darkMode ? 'bg-gray-700 text-gray-300' : ''; ?>">
                                    <?php echo $lang['next'] ?? 'Next'; ?>
                                </a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                            <div>
                                <p class="text-sm <?php echo $darkMode ? 'text-gray-400' : 'text-gray-700'; ?>">
                                    <?php echo sprintf($lang['showing_page'] ?? 'Showing page <span class="font-medium">%d</span> of <span class="font-medium">%d</span>', $page, $totalPages); ?>
                                </p>
                            </div>
                            
                            <div>
                                <nav class="relative z-0 inline-flex rounded-md shadow-sm -space-x-px" aria-label="Pagination">
                                    <?php if ($page > 1): ?>
                                        <a href="?page=1&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>" class="relative inline-flex items-center px-2 py-2 rounded-l-md border border-gray-300 bg-white text-sm font-medium <?php echo $darkMode ? 'border-gray-700 bg-gray-800 text-gray-400 hover:bg-gray-700' : 'text-gray-500 hover:bg-gray-50'; ?>">
                                            <i class="fas fa-angle-double-left"></i>
                                        </a>
                                        
                                        <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>" class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $darkMode ? 'border-gray-700 bg-gray-800 text-gray-400 hover:bg-gray-700' : 'text-gray-500 hover:bg-gray-50'; ?>">
                                            <i class="fas fa-angle-left"></i>
                                        </a>
                                    <?php endif; ?>
                                    
                                    <?php
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($totalPages, $startPage + 4);
                                    $startPage = max(1, $endPage - 4);
                                    
                                    for ($i = $startPage; $i <= $endPage; $i++):
                                    ?>
                                        <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>" class="relative inline-flex items-center px-4 py-2 border text-sm font-medium
                                            <?php if ($i === $page): ?>
                                                border-blue-500 bg-blue-50 text-blue-600 z-10 <?php echo $darkMode ? 'border-blue-700 bg-blue-900' : ''; ?>
                                            <?php else: ?>
                                                border-gray-300 bg-white <?php echo $darkMode ? 'border-gray-700 bg-gray-800 text-gray-400 hover:bg-gray-700' : 'text-gray-500 hover:bg-gray-50'; ?>
                                            <?php endif; ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $totalPages): ?>
                                        <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>" class="relative inline-flex items-center px-2 py-2 border border-gray-300 bg-white text-sm font-medium <?php echo $darkMode ? 'border-gray-700 bg-gray-800 text-gray-400 hover:bg-gray-700' : 'text-gray-500 hover:bg-gray-50'; ?>">
                                            <i class="fas fa-angle-right"></i>
                                        </a>
                                        
                                        <a href="?page=<?php echo $totalPages; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role); ?>" class="relative inline-flex items-center px-2 py-2 rounded-r-md border border-gray-300 bg-white text-sm font-medium <?php echo $darkMode ? 'border-gray-700 bg-gray-800 text-gray-400 hover:bg-gray-700' : 'text-gray-500 hover:bg-gray-50'; ?>">
                                            <i class="fas fa-angle-double-right"></i>
                                        </a>
                                    <?php endif; ?>
                                </nav>
                            </div>
                        </div>
                    </nav>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="mt-4 text-sm text-gray-500 <?php echo $darkMode ? 'text-gray-400' : ''; ?>">
            <?php echo $lang['current_time'] ?? 'Current Time'; ?>: <?php echo '2025-03-17 22:47:15'; ?> | 
            <?php echo $lang['logged_in_as'] ?? 'Logged in as'; ?>: <?php echo htmlspecialchars('mahranalsarminy'); ?>
        </div>
    </div>
</div>

<!-- Include footer -->
<?php require_once '../../theme/admin/footer.php'; ?>