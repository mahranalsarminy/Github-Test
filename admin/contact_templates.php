<?php
define('ROOT_DIR', dirname(__DIR__));
require_once ROOT_DIR . '/includes/init.php';
require_once ROOT_DIR . '/includes/contact_helpers.php';
require_admin();

$error_message = '';
$success_message = '';
// Set default language
$default_lang = 'en';

// Get language from session or default
$current_lang = $_SESSION['lang'] ?? $default_lang;

// Load language file
$lang_file = ROOT_DIR . '/lang/templates/' . $current_lang . '.php';
if (file_exists($lang_file)) {
    require_once $lang_file;
} else {
    // Fallback to default language
    require_once ROOT_DIR . '/lang/templates/en.php';
}
// Handle template actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_template'])) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO contact_templates (title, content, type, is_active, created_at, updated_at)
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ");
            $stmt->execute([
                trim($_POST['title']),
                $_POST['content'],
                $_POST['type'],
                isset($_POST['is_active']) ? 1 : 0
            ]);
            $success_message = $lang['templates']['messages']['added'];
        } catch (PDOException $e) {
            $error_message = $lang['templates']['messages']['error_add'];
        }
    } elseif (isset($_POST['update_template'])) {
        try {
            $stmt = $pdo->prepare("
                UPDATE contact_templates 
                SET title = ?, content = ?, type = ?, is_active = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->execute([
                trim($_POST['title']),
                $_POST['content'],
                $_POST['type'],
                isset($_POST['is_active']) ? 1 : 0,
                $_POST['template_id']
            ]);
            $success_message = $lang['templates']['messages']['updated'];
        } catch (PDOException $e) {
            $error_message = $lang['templates']['messages']['error_update'];
        }
    } elseif (isset($_POST['delete_template'])) {
        try {
            $stmt = $pdo->prepare("DELETE FROM contact_templates WHERE id = ?");
            $stmt->execute([$_POST['template_id']]);
            $success_message = $lang['templates']['messages']['deleted'];
        } catch (PDOException $e) {
            $error_message = $lang['templates']['messages']['error_delete'];
        }
    } elseif (isset($_POST['restore_defaults'])) {
        try {
            // Default templates in English
            $default_templates = [
                [
                    'title' => 'Auto Reply - General',
                    'content' => "Dear {name},\n\nThank you for contacting us. We have received your message and will respond to your inquiry as soon as possible.\n\nBest regards,\n[Your Company Name]",
                    'type' => 'auto_reply',
                    'is_active' => 1
                ],
                [
                    'title' => 'Support Request Acknowledgment',
                    'content' => "Dear {name},\n\nWe have received your support request regarding '{subject}'. Our team will review your inquiry and get back to you within 24-48 hours.\n\nBest regards,\n[Your Company Name]",
                    'type' => 'auto_reply',
                    'is_active' => 1
                ],
                [
                    'title' => 'General Response Template',
                    'content' => "Dear {name},\n\nThank you for your message. In response to your inquiry:\n\n[Your response here]\n\nIf you have any further questions, please don't hesitate to contact us.\n\nBest regards,\n[Your Name]\n[Your Company Name]",
                    'type' => 'admin_reply',
                    'is_active' => 1
                ],
                [
                    'title' => 'Technical Support Response',
                    'content' => "Dear {name},\n\nRegarding your technical inquiry:\n\n[Technical details/solution here]\n\nIf you need further assistance, please provide:\n1. Detailed steps to reproduce the issue\n2. Any error messages you receive\n3. Screenshots if applicable\n\nBest regards,\nTechnical Support Team",
                    'type' => 'admin_reply',
                    'is_active' => 1
                ]
            ];

            // Clear existing templates
            $pdo->query("TRUNCATE TABLE contact_templates");

            // Insert default templates
            $stmt = $pdo->prepare("
                INSERT INTO contact_templates (title, content, type, is_active, created_at, updated_at)
                VALUES (?, ?, ?, ?, NOW(), NOW())
            ");

            foreach ($default_templates as $template) {
                $stmt->execute([
                    $template['title'],
                    $template['content'],
                    $template['type'],
                    $template['is_active']
                ]);
            }

            $success_message = $lang['templates']['messages']['restored'];
        } catch (PDOException $e) {
            $error_message = $lang['templates']['messages']['error_restore'];
        }
    }
}

// Get templates
try {
    $templates = $pdo->query("
        SELECT * FROM contact_templates 
        ORDER BY type, title
    ")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $templates = [];
    $error_message = $lang['templates']['messages']['error_fetch'];
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang['code']; ?>" dir="<?php echo $lang['dir']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['templates']['title']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <?php include 'sidebar.php'; ?>

    <div class="main-container ml-64 p-4">
        <main class="container mx-auto mt-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-bold"><?php echo $lang['templates']['title']; ?></h1>
                <div class="space-x-2">
                    <button onclick="showAddTemplate()" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                        <i class="fas fa-plus mr-2"></i><?php echo $lang['templates']['add_new']; ?>
                    </button>
                    <form method="POST" class="inline">
                        <button type="submit" name="restore_defaults" 
                                onclick="return confirm('<?php echo $lang['templates']['confirm_restore']; ?>')"
                                class="bg-yellow-500 text-white px-4 py-2 rounded hover:bg-yellow-600">
                            <i class="fas fa-sync mr-2"></i><?php echo $lang['templates']['restore_defaults']; ?>
                        </button>
                    </form>
                </div>
            </div>

            <?php if ($error_message): ?>
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if ($success_message): ?>
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <!-- Templates Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <?php foreach ($templates as $template): ?>
                    <div class="bg-white rounded-lg shadow-lg p-6">
                        <div class="flex justify-between items-start mb-4">
                            <div>
                                <h3 class="text-xl font-semibold"><?php echo htmlspecialchars($template['title']); ?></h3>
                                <span class="inline-block px-2 py-1 rounded text-sm <?php echo $template['type'] === 'auto_reply' ? 'bg-blue-100 text-blue-800' : 'bg-green-100 text-green-800'; ?>">
                                    <?php echo $lang['templates']['template_types'][$template['type']]; ?>
                                </span>
                                <?php if ($template['is_active']): ?>
                                    <span class="inline-block px-2 py-1 rounded text-sm bg-green-100 text-green-800 ml-2">
                                        <?php echo $lang['templates']['status']['active']; ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div class="space-x-2">
                                <button onclick="editTemplate(<?php echo htmlspecialchars(json_encode($template)); ?>)"
                                        class="text-blue-500 hover:text-blue-700">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form method="POST" class="inline">
                                    <input type="hidden" name="template_id" value="<?php echo $template['id']; ?>">
                                    <button type="submit" name="delete_template"
                                            onclick="return confirm('<?php echo $lang['templates']['messages']['confirm_delete']; ?>')"
                                            class="text-red-500 hover:text-red-700">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        <div class="prose max-w-none">
                            <?php echo nl2br(htmlspecialchars($template['content'])); ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </main>
    </div>

    <!-- Template Modal -->
    <div id="templateModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 max-w-2xl w-full mx-4">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xl font-bold" id="modalTitle"></h3>
                <button onclick="closeModal()" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <form id="templateForm" method="POST" class="space-y-4">
                <input type="hidden" name="template_id" id="template_id">
                
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                        <?php echo $lang['templates']['fields']['title']; ?>
                    </label>
                    <input type="text" name="title" id="template_title"
                           class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400"
                           required>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                        <?php echo $lang['templates']['fields']['type']; ?>
                    </label>
                    <select name="type" id="template_type"
                            class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
                        <?php foreach ($lang['templates']['template_types'] as $value => $label): ?>
                            <option value="<?php echo $value; ?>"><?php echo $label; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">
                        <?php echo $lang['templates']['fields']['content']; ?>
                    </label>
                    <textarea name="content" id="template_content"
                              class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400"
                              rows="10" required></textarea>
                </div>

                <div class="flex items-center">
                    <input type="checkbox" name="is_active" id="template_active" class="mr-2">
                    <label for="template_active"><?php echo $lang['templates']['status']['active']; ?></label>
                </div>

                <div class="flex justify-end space-x-4">
                    <button type="button" onclick="closeModal()"
                            class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">
                        <?php echo $lang['templates']['buttons']['cancel']; ?>
                    </button>
                    <button type="submit" id="submitBtn"
                            class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
                        <?php echo $lang['templates']['buttons']['save']; ?>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function showAddTemplate() {
            document.getElementById('modalTitle').textContent = '<?php echo $lang['templates']['add_new']; ?>';
            document.getElementById('templateForm').reset();
            document.getElementById('template_id').value = '';
            document.getElementById('submitBtn').name = 'add_template';
            document.getElementById('templateModal').classList.remove('hidden');
        }

        function editTemplate(template) {
            document.getElementById('modalTitle').textContent = '<?php echo $lang['templates']['buttons']['edit']; ?>';
                        document.getElementById('modalTitle').textContent = '<?php echo $lang['templates']['buttons']['edit']; ?>';
            document.getElementById('template_id').value = template.id;
            document.getElementById('template_title').value = template.title;
            document.getElementById('template_type').value = template.type;
            document.getElementById('template_content').value = template.content;
            document.getElementById('template_active').checked = template.is_active === '1';
            document.getElementById('submitBtn').name = 'update_template';
            document.getElementById('templateModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('templateModal').classList.add('hidden');
            document.getElementById('templateForm').reset();
        }

        // Initialize any rich text editors or additional features
        document.addEventListener('DOMContentLoaded', function() {
            // Handle form submission
            document.getElementById('templateForm').addEventListener('submit', function(e) {
                e.preventDefault();
                
                // Get form data
                const formData = new FormData(this);
                
                // Submit form
                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(html => {
                    // Reload page to show new/updated template
                    window.location.reload();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error saving template. Please try again.');
                });
            });
        });

        // Close modal when clicking outside
        window.onclick = function(event) {
            if (event.target === document.getElementById('templateModal')) {
                closeModal();
            }
        }

        // Helper function to escape HTML
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }

        // Add preview functionality if needed
        function previewTemplate() {
            const content = document.getElementById('template_content').value;
            // Add preview logic here
            alert('Preview functionality can be implemented here');
        }
    </script>

    <?php if (isset($_GET['debug'])): ?>
    <script>
        // Debug information
        console.log('Templates loaded:', <?php echo json_encode($templates); ?>);
    </script>
    <?php endif; ?>
</body>
</html>