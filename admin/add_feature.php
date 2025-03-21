<?php
define('ROOT_DIR', dirname(__DIR__));
require_once ROOT_DIR . '/includes/init.php';
require_admin();

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = $_POST['description']; // Allow HTML content
    $sort_order = (int)$_POST['sort_order'];
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $image_url = null;

    // Handle image upload
    if (isset($_FILES['feature_image']) && $_FILES['feature_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = ROOT_DIR . '/uploads/features/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }

        $file_info = pathinfo($_FILES['feature_image']['name']);
        $file_extension = strtolower($file_info['extension']);
        $allowed_extensions = ['png', 'jpg', 'jpeg', 'gif'];

        if (in_array($file_extension, $allowed_extensions)) {
            // Generate unique filename
            $new_filename = 'feature_' . time() . '_' . uniqid() . '.' . $file_extension;
            $upload_path = $upload_dir . $new_filename;

            if (move_uploaded_file($_FILES['feature_image']['tmp_name'], $upload_path)) {
                $image_url = '/uploads/features/' . $new_filename;
            } else {
                $error_message = "Failed to upload image.";
            }
        } else {
            $error_message = "Invalid file type. Only PNG, JPG, JPEG, and GIF are allowed.";
        }
    }

    if (empty($title)) {
        $error_message = "Title is required.";
    } elseif (empty($error_message)) {
        try {
            $stmt = $pdo->prepare("
                INSERT INTO features (title, description, image_url, sort_order, is_active) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$title, $description, $image_url, $sort_order, $is_active]);
            $success_message = "Feature added successfully!";
            
            // Clear form after successful submission
            $title = $description = '';
            $sort_order = 0;
            $is_active = 1;
        } catch (PDOException $e) {
            error_log("Database Error: " . $e->getMessage());
            $error_message = "Error adding feature. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Feature - WallPix Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://cdn.tiny.cloud/1/89a26gz76i0oxsdzd5o6zakeqzyyejsuqc1ta3qip1um7icg/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
    <style>
        .tox-tinymce {
            border-radius: 0.375rem;
        }
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            object-fit: contain;
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php include 'sidebar.php'; ?>

    <div class="main-container ml-64 p-4">
        <main class="container mx-auto mt-8">
            <div class="flex justify-between items-center mb-6">
                <h1 class="text-3xl font-semibold">Add New Feature</h1>
                <a href="features.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                    Back to Features
                </a>
            </div>

            <?php if (!empty($error_message)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4">
                    <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4">
                    <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <div class="bg-white rounded-lg shadow-lg p-6">
                <form method="POST" enctype="multipart/form-data" class="space-y-6">
                    <!-- Title -->
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Title</label>
                        <input type="text" name="title" 
                               value="<?php echo isset($title) ? htmlspecialchars($title) : ''; ?>" 
                               class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-400" 
                               required>
                    </div>

                    <!-- Description -->
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Description</label>
                        <textarea name="description" id="description" 
                                  class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-400"><?php echo isset($description) ? $description : ''; ?></textarea>
                    </div>

                    <!-- Image Upload -->
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Feature Image</label>
                        <input type="file" name="feature_image" 
                               accept="image/png,image/jpeg,image/gif"
                               class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-400">
                        <p class="text-sm text-gray-500 mt-1">
                            Supported formats: PNG, JPG, GIF. Max size: 2MB
                        </p>
                    </div>

                    <!-- Sort Order -->
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Sort Order</label>
                        <input type="number" name="sort_order" 
                               value="<?php echo isset($sort_order) ? $sort_order : 0; ?>" 
                               class="w-full p-2 border rounded focus:ring-2 focus:ring-blue-400">
                    </div>

                    <!-- Active Status -->
                    <div class="flex items-center">
                        <input type="checkbox" name="is_active" id="is_active" 
                               <?php echo (!isset($is_active) || $is_active) ? 'checked' : ''; ?>
                               class="mr-2">
                        <label for="is_active" class="text-gray-700 font-semibold">Active</label>
                    </div>

                    <!-- Submit Button -->
                    <div class="flex justify-end space-x-4">
                        <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
                            Add Feature
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        tinymce.init({
            selector: '#description',
            plugins: 'advlist autolink lists link image charmap preview anchor pagebreak searchreplace visualblocks code fullscreen insertdatetime media table code help wordcount',
            toolbar: 'undo redo | blocks | bold italic forecolor backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
            content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; }',
            height: 300,
            setup: function(editor) {
                editor.on('change', function() {
                    editor.save();
                });
            }
        });
    </script>
</body>
</html>