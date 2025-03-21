<?php
define('ROOT_DIR', dirname(__DIR__));
require_once ROOT_DIR . '/includes/init.php';
require_once ROOT_DIR . '/includes/contact_helpers.php';
require_admin();

$error_message = '';
$success_message = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['reply_message'])) {
        $message_id = (int)$_POST['message_id'];
        $reply = trim($_POST['reply']);
        $admin_notes = trim($_POST['admin_notes'] ?? '');

        try {
            $stmt = $pdo->prepare("
                UPDATE contact_messages 
                SET status = 'replied',
                    reply_message = ?,
                    reply_date = CURRENT_TIMESTAMP,
                    admin_notes = ?
                WHERE id = ?
            ");
            
            $stmt->execute([$reply, $admin_notes, $message_id]);

            // Get message details
            $stmt = $pdo->prepare("SELECT * FROM contact_messages WHERE id = ?");
            $stmt->execute([$message_id]);
            $message = $stmt->fetch(PDO::FETCH_ASSOC);

            // Send reply email
            sendContactEmail(
                $message['email'],
                "Reply to Your Message: " . $message['subject'],
                $reply,
                getenv('CONTACT_ADMIN_EMAIL'),
                getenv('MAIL_FROM_NAME')
            );

            $success_message = "Reply sent successfully.";
        } catch (PDOException $e) {
            error_log("Reply Error: " . $e->getMessage());
            $error_message = "Error occurred while sending the reply.";
        }
    } elseif (isset($_POST['update_status'])) {
        $message_id = (int)$_POST['message_id'];
        $new_status = $_POST['new_status'];
        
        try {
            $stmt = $pdo->prepare("UPDATE contact_messages SET status = ? WHERE id = ?");
            $stmt->execute([$new_status, $message_id]);
            $success_message = "Message status updated.";
        } catch (PDOException $e) {
            $error_message = "Error occurred while updating the status.";
        }
    } elseif (isset($_POST['delete_message'])) {
        $message_id = (int)$_POST['message_id'];
        
        try {
            $stmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = ?");
            $stmt->execute([$message_id]);
            $success_message = "Message deleted successfully.";
        } catch (PDOException $e) {
            $error_message = "Error occurred while deleting the message.";
        }
    }
}

// Get messages with filters
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 20;

$where_conditions = [];
$params = [];

if ($status_filter !== 'all') {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
}

if ($search) {
    $where_conditions[] = "(name LIKE ? OR email LIKE ? OR subject LIKE ? OR message LIKE ?)";
    $search_term = "%{$search}%";
    $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Get total count
$count_stmt = $pdo->prepare("SELECT COUNT(*) FROM contact_messages {$where_clause}");
$count_stmt->execute($params);
$total_messages = $count_stmt->fetchColumn();

$total_pages = ceil($total_messages / $per_page);
$offset = ($page - 1) * $per_page;

// Get messages
$stmt = $pdo->prepare("
    SELECT * FROM contact_messages 
    {$where_clause}
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
");

$params[] = $per_page;
$params[] = $offset;
$stmt->execute($params);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="<?php echo $lang['code']; ?>" dir="<?php echo $lang['dir']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Message Management - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include 'sidebar.php'; ?>

    <div class="main-container ml-64 p-4">
        <main class="container mx-auto mt-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold">Message Management</h1>
                <a href="/admin/contact_settings.php" class="bg-blue-500 text-white px-4 py-2 rounded-lg hover:bg-blue-600">
                    <i class="fas fa-cog mr-2"></i>Settings
                </a>
            </div>

            <?php if ($error_message): ?>
                <div class="bg-red-100 border-r-4 border-red-500 text-red-700 p-4 mb-6">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="bg-green-100 border-r-4 border-green-500 text-green-700 p-4 mb-6">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-6">
                <form method="GET" class="flex space-x-4">
                    <div class="flex-1">
                        <input type="text" name="search" 
                               value="<?php echo htmlspecialchars($search); ?>"
                               placeholder="Search..."
                               class="w-full px-4 py-2 border rounded-lg">
                    </div>
                    <div>
                        <select name="status" class="px-4 py-2 border rounded-lg">
                            <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>All Statuses</option>
                            <option value="new" <?php echo $status_filter === 'new' ? 'selected' : ''; ?>>New</option>
                            <option value="read" <?php echo $status_filter === 'read' ? 'selected' : ''; ?>>Read</option>
                            <option value="replied" <?php echo $status_filter === 'replied' ? 'selected' : ''; ?>>Replied</option>
                            <option value="archived" <?php echo $status_filter === 'archived' ? 'selected' : ''; ?>>Archived</option>
                        </select>
                    </div>
                    <button type="submit" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600">
                        <i class="fas fa-search mr-2"></i>Search
                    </button>
                </form>
            </div>

            <!-- Messages Table -->
            <div class="bg-white rounded-lg shadow-lg overflow-hidden">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50 text-gray-600 text-sm">
                            <th class="py-3 px-4 text-right">Date</th>
                            <th class="py-3 px-4 text-right">Name</th>
                            <th class="py-3 px-4 text-right">Email</th>
                            <th class="py-3 px-4 text-right">Subject</th>
                            <th class="py-3 px-4 text-center">Status</th>
                            <th class="py-3 px-4 text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($messages as $message): ?>
                            <tr class="border-b hover:bg-gray-50">
                                <td class="py-3 px-4"><?php echo date('Y-m-d H:i', strtotime($message['created_at'])); ?></td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($message['name']); ?></td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($message['email']); ?></td>
                                <td class="py-3 px-4"><?php echo htmlspecialchars($message['subject']); ?></td>
                                <td class="py-3 px-4 text-center">
                                    <span class="px-2 py-1 rounded text-sm
                                        <?php echo $message['status'] === 'new' ? 'bg-yellow-100 text-yellow-800' : 
                                            ($message['status'] === 'replied' ? 'bg-green-100 text-green-800' : 
                                            'bg-gray-100 text-gray-800'); ?>">
                                        <?php 
                                        $status_labels = [
                                            'new' => 'New',
                                            'read' => 'Read',
                                            'replied' => 'Replied',
                                            'archived' => 'Archived'
                                        ];
                                        echo $status_labels[$message['status']] ?? $message['status'];
                                        ?>
                                    </span>
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <div class="flex justify-center space-x-2">
                                        <button onclick="viewMessage(<?php echo $message['id']; ?>)"
                                                class="text-blue-500 hover:text-blue-700">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                        <button onclick="replyMessage(<?php echo $message['id']; ?>)"
                                                class="text-green-500 hover:text-green-700">
                                            <i class="fas fa-reply"></i>
                                        </button>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                                            <button type="submit" name="delete_message"
                                                    class="text-red-500 hover:text-red-700"
                                                    onclick="return confirm('Are you sure you want to delete this message?')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
            <div class="mt-6 flex justify-center">
                <div class="flex space-x-2">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?php echo $i; ?>&status=<?php echo $status_filter; ?>&search=<?php echo urlencode($search); ?>"
                           class="px-4 py-2 rounded-lg <?php echo $page === $i ? 'bg-blue-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-100'; ?>">
                            <?php echo $i; ?>
                        </a>
                    <?php endfor; ?>
                </div>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- View Message Modal -->
    <div id="viewMessageModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Message Details</h3>
                <button onclick="closeModal('viewMessageModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="messageContent" class="space-y-4">
                <!-- Message content will be loaded here -->
            </div>
        </div>
    </div>

    <!-- Reply Modal -->
    <div id="replyModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
        <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold">Reply to Message</h3>
                <button onclick="closeModal('replyModal')" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="replyForm" method="POST" class="space-y-4">
                <input type="hidden" name="message_id" id="reply_message_id">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Reply</label>
                    <textarea name="reply" rows="6" required
                              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400"></textarea>
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Admin Notes (Optional)</label>
                    <textarea name="admin_notes" rows="3"
                              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400"></textarea>
                </div>
                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeModal('replyModal')"
                            class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">
                        Cancel
                    </button>
                    <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
                        Send Reply
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function viewMessage(id) {
            fetch(`/admin/ajax/get_message.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    const messageContent = `
                        <div><strong>Name:</strong> ${data.name}</div>
                        <div><strong>Email:</strong> ${data.email}</div>
                        <div><strong>Subject:</strong> ${data.subject}</div>
                        <div><strong>Message:</strong> <p>${data.message}</p></div>
                    `;
                    document.getElementById('messageContent').innerHTML = messageContent;
                    document.getElementById('viewMessageModal').classList.remove('hidden');
                });
        }

        function replyMessage(id) {
            document.getElementById('reply_message_id').value = id;
            document.getElementById('replyModal').classList.remove('hidden');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).classList.add('hidden');
        }
    </script>
</body>
</html>
