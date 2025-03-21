<?php
define('ROOT_DIR', dirname(__DIR__));
require_once ROOT_DIR . '/includes/init.php';
require_once ROOT_DIR . '/includes/contact_helpers.php';
require_admin();
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

$error_message = '';
$success_message = '';

// Get current settings
try {
    $stmt = $pdo->query("SELECT * FROM contact_settings WHERE id = 1");
    $settings = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get available templates for auto-reply
    $stmt = $pdo->query("SELECT id, title FROM contact_templates WHERE type = 'auto_reply' AND is_active = 1");
    $templates = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = $lang['contact_settings']['messages']['error_fetch'];
    $settings = [];
    $templates = [];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['save_settings'])) {
        try {
            // Validate email
            if (!filter_var($_POST['contact_email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception($lang['contact_settings']['messages']['invalid_email']);
            }

            // Prepare required fields
            $required_fields = isset($_POST['required_fields']) ? json_encode($_POST['required_fields']) : '[]';

            $stmt = $pdo->prepare("
                UPDATE contact_settings SET
                contact_email = ?,
                email_subject_prefix = ?,
                recaptcha_site_key = ?,
                recaptcha_secret_key = ?,
                enable_auto_reply = ?,
                auto_reply_template_id = ?,
                enable_attachments = ?,
                max_file_size = ?,
                allowed_file_types = ?,
                required_fields = ?,
                updated_at = NOW()
                WHERE id = 1
            ");

            $result = $stmt->execute([
                trim($_POST['contact_email']),
                trim($_POST['email_subject_prefix']),
                trim($_POST['recaptcha_site_key']),
                trim($_POST['recaptcha_secret_key']),
                isset($_POST['enable_auto_reply']) ? 1 : 0,
                empty($_POST['auto_reply_template_id']) ? null : (int)$_POST['auto_reply_template_id'],
                isset($_POST['enable_attachments']) ? 1 : 0,
                (float)($_POST['max_file_size'] ?? 5),
                trim($_POST['allowed_file_types'] ?? 'pdf,doc,docx,jpg,jpeg,png'),
                $required_fields
            ]);

            if ($result) {
                // Refresh settings
                $stmt = $pdo->query("SELECT * FROM contact_settings WHERE id = 1");
                $settings = $stmt->fetch(PDO::FETCH_ASSOC);
                $success_message = $lang['contact_settings']['messages']['saved'];
            } else {
                throw new Exception($lang['contact_settings']['messages']['error_save']);
            }
        } catch (Exception $e) {
            $error_message = $e->getMessage();
        }
    }
}

// Get required fields from settings
$required_fields = isset($settings['required_fields']) ? json_decode($settings['required_fields'], true) : [];
?>

<!DOCTYPE html>
<html lang="<?php echo $lang['code']; ?>" dir="<?php echo $lang['dir']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $lang['contact_settings']['title']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
     <?php include 'sidebar.php'; ?>

    <div class="main-container ml-64 p-4">
        <main class="container mx-auto mt-8">
            <h1 class="text-3xl font-bold mb-6"><?php echo $lang['contact_settings']['title']; ?></h1>

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

            <form method="POST" class="bg-white rounded-lg shadow-lg p-6 space-y-6">
                <!-- Email Settings -->
                <div class="border-b pb-6">
                    <h2 class="text-xl font-semibold mb-4">
                        <?php echo $lang['contact_settings']['sections']['email']; ?>
                    </h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">
                                <?php echo $lang['contact_settings']['fields']['contact_email']; ?>
                            </label>
                            <input type="email" name="contact_email" required
                                   value="<?php echo htmlspecialchars($settings['contact_email'] ?? ''); ?>"
                                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
                            <p class="text-gray-600 text-sm mt-1">
                                <?php echo $lang['contact_settings']['help_text']['contact_email']; ?>
                            </p>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">
                                <?php echo $lang['contact_settings']['fields']['email_subject_prefix']; ?>
                            </label>
                            <input type="text" name="email_subject_prefix"
                                   value="<?php echo htmlspecialchars($settings['email_subject_prefix'] ?? ''); ?>"
                                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
                        </div>
                    </div>
                </div>

                <!-- reCAPTCHA Settings -->
                <div class="border-b pb-6">
                    <h2 class="text-xl font-semibold mb-4">
                        <?php echo $lang['contact_settings']['sections']['recaptcha']; ?>
                    </h2>
                    <div class="space-y-4">
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">
                                <?php echo $lang['contact_settings']['fields']['recaptcha_site_key']; ?>
                            </label>
                            <input type="text" name="recaptcha_site_key"
                                   value="<?php echo htmlspecialchars($settings['recaptcha_site_key'] ?? ''); ?>"
                                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">
                                <?php echo $lang['contact_settings']['fields']['recaptcha_secret_key']; ?>
                            </label>
                            <input type="password" name="recaptcha_secret_key"
                                   value="<?php echo htmlspecialchars($settings['recaptcha_secret_key'] ?? ''); ?>"
                                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
                        </div>
                    </div>
                </div>

                <!-- Auto Reply Settings -->
                <div class="border-b pb-6">
                    <h2 class="text-xl font-semibold mb-4">
                        <?php echo $lang['contact_settings']['sections']['auto_reply']; ?>
                    </h2>
                    <div class="space-y-4">
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" name="enable_auto_reply"
                                       <?php echo ($settings['enable_auto_reply'] ?? 0) ? 'checked' : ''; ?>
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2">
                                    <?php echo $lang['contact_settings']['fields']['enable_auto_reply']; ?>
                                </span>
                            </label>
                        </div>
                        <div class="flex items-end space-x-4">
                            <div class="flex-1">
                                <label class="block text-gray-700 font-medium mb-2">
                                    <?php echo $lang['contact_settings']['fields']['auto_reply_template']; ?>
                                </label>
                                <select name="auto_reply_template_id"
                                        class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
                                    <option value=""><?php echo $lang['contact_settings']['select_template']; ?></option>
                                    <?php foreach ($templates as $template): ?>
                                        <option value="<?php echo $template['id']; ?>"
                                                <?php echo ($settings['auto_reply_template_id'] ?? '') == $template['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($template['title']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <a href="contact_templates.php?action=new&type=auto_reply" 
                                   class="inline-flex items-center px-4 py-2 bg-blue-500 text-white rounded hover:bg-blue-600">
                                    <i class="fas fa-plus mr-2"></i>
                                    <?php echo $lang['contact_settings']['buttons']['create_template']; ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form Settings -->
                <div class="border-b pb-6">
                    <h2 class="text-xl font-semibold mb-4">
                        <?php echo $lang['contact_settings']['sections']['form']; ?>
                    </h2>
                    <div class="space-y-4">
                        <div>
                            <label class="flex items-center">
                                <input type="checkbox" name="enable_attachments"
                                       <?php echo ($settings['enable_attachments'] ?? 0) ? 'checked' : ''; ?>
                                       class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                <span class="ml-2">
                                    <?php echo $lang['contact_settings']['fields']['enable_attachments']; ?>
                                </span>
                            </label>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">
                                <?php echo $lang['contact_settings']['fields']['max_file_size']; ?>
                            </label>
                            <input type="number" name="max_file_size"
                                   value="<?php echo htmlspecialchars($settings['max_file_size'] ?? '5'); ?>"
                                   min="1" max="50"
                                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">
                                <?php echo $lang['contact_settings']['fields']['allowed_file_types']; ?>
                            </label>
                            <input type="text" name="allowed_file_types"
                                   value="<?php echo htmlspecialchars($settings['allowed_file_types'] ?? 'pdf,doc,docx,jpg,jpeg,png'); ?>"
                                   class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-400">
                            <p class="text-gray-600 text-sm mt-1">
                                <?php echo $lang['contact_settings']['help_text']['file_types']; ?>
                            </p>
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">
                                <?php echo $lang['contact_settings']['fields']['required_fields']; ?>
                            </label>
                            <div class="space-y-2">
                                <?php foreach ($lang['contact_settings']['form_fields'] as $field => $label): ?>
                                    <label class="flex items-center">
                                        <input type="checkbox" name="required_fields[]"
                                               value="<?php echo $field; ?>"
                                               <?php echo in_array($field, $required_fields) ? 'checked' : ''; ?>
                                               class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        <span class="ml-2"><?php echo $label; ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Save Button -->
                <div class="flex justify-end">
                    <button type="submit" name="save_settings"
                            class="bg-green-500 text-white px-6 py-2 rounded hover:bg-green-600">
                        <?php echo $lang['contact_settings']['buttons']['save']; ?>
                    </button>
                </div>
            </form>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Handle auto-reply dependencies
            const autoReplyCheckbox = document.querySelector('input[name="enable_auto_reply"]');
            const templateSelect = document.querySelector('select[name="auto_reply_template_id"]');
            
            function toggleTemplateSelect() {
                templateSelect.disabled = !autoReplyCheckbox.checked;
                if (!autoReplyCheckbox.checked) {
                    templateSelect.value = '';
                }
            }
            
            autoReplyCheckbox.addEventListener('change', toggleTemplateSelect);
            toggleTemplateSelect();

            // Handle attachment settings dependencies
            const attachmentsCheckbox = document.querySelector('input[name="enable_attachments"]');
            const fileSizeInput = document.querySelector('input[name="max_file_size"]');
            const fileTypesInput = document.querySelector('input[name="allowed_file_types"]');
            
            function toggleAttachmentFields() {
                fileSizeInput.disabled = !attachmentsCheckbox.checked;
                                fileTypesInput.disabled = !attachmentsCheckbox.checked;
                if (!attachmentsCheckbox.checked) {
                    fileSizeInput.value = '5';
                    fileTypesInput.value = 'pdf,doc,docx,jpg,jpeg,png';
                }
            }
            
            attachmentsCheckbox.addEventListener('change', toggleAttachmentFields);
            toggleAttachmentFields();

            // Form validation
            document.querySelector('form').addEventListener('submit', function(e) {
                const email = document.querySelector('input[name="contact_email"]').value;
                if (!email) {
                    e.preventDefault();
                    alert(<?php echo json_encode($lang['contact_settings']['messages']['email_required']); ?>);
                    return;
                }

                if (autoReplyCheckbox.checked && !templateSelect.value) {
                    e.preventDefault();
                    alert(<?php echo json_encode($lang['contact_settings']['messages']['template_required']); ?>);
                    return;
                }

                if (attachmentsCheckbox.checked) {
                    const fileSize = parseFloat(fileSizeInput.value);
                    if (isNaN(fileSize) || fileSize < 1 || fileSize > 50) {
                        e.preventDefault();
                        alert(<?php echo json_encode($lang['contact_settings']['messages']['invalid_file_size']); ?>);
                        return;
                    }

                    if (!fileTypesInput.value.trim()) {
                        e.preventDefault();
                        alert(<?php echo json_encode($lang['contact_settings']['messages']['file_types_required']); ?>);
                        return;
                    }
                }
            });

            // Show success message
            <?php if ($success_message): ?>
            showMessage(<?php echo json_encode($success_message); ?>, 'success');
            <?php endif; ?>

            // Show error message
            <?php if ($error_message): ?>
            showMessage(<?php echo json_encode($error_message); ?>, 'error');
            <?php endif; ?>
        });

        function showMessage(message, type = 'success') {
            const alert = document.createElement('div');
            alert.className = `fixed top-4 right-4 p-4 rounded-lg ${
                type === 'success' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'
            }`;
            alert.textContent = message;
            document.body.appendChild(alert);
            setTimeout(() => alert.remove(), 3000);
        }
    </script>
</body>
</html>