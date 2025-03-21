<?php
define('ROOT_DIR', dirname(__DIR__));
require_once ROOT_DIR . '/includes/init.php';
require_admin();

$page_title = "Edit Media";

// Fetch categories for dropdown
$stmt = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch media to edit
$media_id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM media WHERE id = ?");
$stmt->execute([$media_id]);
$media = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$media) {
    $_SESSION['error'] = 'Media not found';
    header("Location: media.php");
    exit;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];
    $status = isset($_POST['status']) ? 1 : 0;
    $featured = isset($_POST['featured']) ? 1 : 0;
    $background_color = $_POST['background_color'];
    $maintain_aspect_ratio = isset($_POST['maintain_aspect_ratio']) ? 1 : 0;
    $orientation = $_POST['orientation'];
    $tags = $_POST['tags'];
    $owner = $_POST['owner'];
    $license = $_POST['license'];
    $publish_date = $_POST['publish_date'];
    $paid_content = isset($_POST['paid_content']) ? 1 : 0;
    $watermark_text = isset($_POST['watermark_text']) ? $_POST['watermark_text'] : null;
    $media_type = $_POST['media_type'];
    if ($media_type == 'single_file' || $media_type == 'multiple_files') {
        $files = $_FILES['media_files'];
        $total_files = count($files['name']);
        for ($i = 0; $i < $total_files; $i++) {
            if ($files['error'][$i] == 0) {
                $file_name = $files['name'][$i];
                $file_tmp = $files['tmp_name'][$i];
                $file_type = $files['type'][$i];
                $file_size = $files['size'][$i];
                $file_path = 'uploads/media/' . date('Y/m/') . $file_name;

                move_uploaded_file($file_tmp, ROOT_DIR . '/' . $file_path);

                // Apply watermark if enabled
                if ($watermark_text && strpos($file_type, 'image/') === 0) {
                    apply_watermark(ROOT_DIR . '/' . $file_path, $watermark_text);
                }

                $stmt = $pdo->prepare("
                    UPDATE media SET 
                        title = ?, 
                        description = ?, 
                        category_id = ?, 
                        file_name = ?, 
                        file_path = ?, 
                        file_type = ?, 
                        file_size = ?, 
                        status = ?, 
                        featured = ?, 
                        background_color = ?, 
                        maintain_aspect_ratio = ?, 
                        orientation = ?, 
                        owner = ?, 
                        license = ?, 
                        publish_date = ?, 
                        paid_content = ?, 
                        watermark_text = ?, 
                        updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$title, $description, $category_id, $file_name, $file_path, $file_type, $file_size, $status, $featured, $background_color, $maintain_aspect_ratio, $orientation, $owner, $license, $publish_date, $paid_content, $watermark_text, $media_id]);
            }
        }
    } else {
        $urls = explode("\n", trim($_POST['media_urls']));
        foreach ($urls as $url) {
            if (filter_var($url, FILTER_VALIDATE_URL)) {
                $stmt = $pdo->prepare("
                    UPDATE media SET 
                        title = ?, 
                        description = ?, 
                        category_id = ?, 
                        external_url = ?, 
                        status = ?, 
                        featured = ?, 
                        background_color = ?, 
                        maintain_aspect_ratio = ?, 
                        orientation = ?, 
                        owner = ?, 
                        license = ?, 
                        publish_date = ?, 
                        paid_content = ?, 
                        watermark_text = ?, 
                        updated_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$title, $description, $category_id, $url, $status, $featured, $background_color, $maintain_aspect_ratio, $orientation, $owner, $license, $publish_date, $paid_content, $watermark_text, $media_id]);
            }
        }
    }

    $_SESSION['success'] = 'Media updated successfully';
    header("Location: media.php");
    exit;
}
// Function to apply watermark to an image
function apply_watermark($file_path, $watermark_text) {
    $image = imagecreatefromstring(file_get_contents($file_path));
    $width = imagesx($image);
    $height = imagesy($image);
    
    // Create watermark text
    $font_size = 20;
    $font_color = imagecolorallocatealpha($image, 255, 255, 255, 50);
    $font_angle = 0;
    $font_file = ROOT_DIR . '/path/to/font.ttf'; // Path to your font file
    
    $text_box = imagettfbbox($font_size, $font_angle, $font_file, $watermark_text);
    $text_width = abs($text_box[4] - $text_box[0]);
    $text_height = abs($text_box[5] - $text_box[1]);
    
    // Center the text
    $text_x = ($width - $text_width) / 2;
    $text_y = ($height - $text_height) / 2 + $text_height;
    
    // Add the text to the image
    imagettftext($image, $font_size, $font_angle, $text_x, $text_y, $font_color, $font_file, $watermark_text);
    
    // Save the image
    switch (strtolower(pathinfo($file_path, PATHINFO_EXTENSION))) {
        case 'jpeg':
        case 'jpg':
            imagejpeg($image, $file_path);
            break;
        case 'png':
            imagepng($image, $file_path);
            break;
        case 'gif':
            imagegif($image, $file_path);
            break;
    }
    
    imagedestroy($image);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($page_title); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/filepond@4.30.4/dist/filepond.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/filepond-plugin-image-preview@4.6.11/dist/filepond-plugin-image-preview.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>
    <script src="https://cdn.jsdelivr.net/npm/filepond-plugin-image-preview@4.6.11/dist/filepond-plugin-image-preview.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/filepond@4.30.4/dist/filepond.min.js"></script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 dark:text-white">
    <?php include 'theme/header.php'; ?>

    <div class="flex flex-1 flex-col md:flex-row">
        <aside class="md:w-64 w-full md:block hidden">
            <?php include 'theme/sidebar.php'; ?>
        </aside>

        <main class="flex-1 p-4 mt-16 w-full">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 w-full">
                <h1 class="text-2xl font-bold mb-4 text-center">Edit Media</h1>

                <?php if (isset($_SESSION['success'])): ?>
                    <div class="bg-green-100 dark:bg-green-900 border border-green-400 text-green-700 dark:text-green-300 px-4 py-3 rounded relative mb-4">
                        <?php 
                        echo $_SESSION['success'];
                        unset($_SESSION['success']);
                        ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($_SESSION['error'])): ?>
                    <div class="bg-red-100 dark:bg-red-900 border border-red-400 text-red-700 dark:text-red-300 px-4 py-3 rounded relative mb-4">
                        <?php 
                        echo $_SESSION['error'];
                        unset($_SESSION['error']);
                        ?>
                    </div>
                <?php endif; ?>
                <form method="post" enctype="multipart/form-data">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <div class="form-group">
                                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                                <input type="text" name="title" id="title" value="<?php echo htmlspecialchars($media['title']); ?>" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>

                            <div class="form-group">
                                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                                <textarea name="description" id="description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"><?php echo htmlspecialchars($media['description']); ?></textarea>
                            </div>

                            <div class="form-group">
                                <label for="category_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Category</label>
                                <select name="category_id" id="category_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" <?php echo $category['id'] == $media['category_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($category['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="media_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Media Type</label>
                                <select name="media_type" id="media_type" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="single_file">Upload Single File</option>
                                    <option value="multiple_files">Upload Multiple Files</option>
                                    <option value="single_url" <?php echo !empty($media['external_url']) ? 'selected' : ''; ?>>Single URL</option>
                                    <option value="multiple_urls">Multiple URLs</option>
                                </select>
                            </div>

                            <div id="file_upload_section" class="form-group" style="display: <?php echo !empty($media['external_url']) ? 'none' : 'block'; ?>;">
                                <label for="media_files" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Upload Files</label>
                                <input type="file" name="media_files[]" id="media_files" multiple class="filepond mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>

                            <div id="url_upload_section" class="form-group" style="display: <?php echo !empty($media['external_url']) ? 'block' : 'none'; ?>;">
                                <label for="media_urls" class="block text-sm font-medium text-gray-700 dark:text-gray-300">External URLs</label>
                                <textarea name="media_urls" id="media_urls" rows="4" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"><?php echo htmlspecialchars($media['external_url']); ?></textarea>
                            </div>
                        </div>

                        <div>
                            <div class="form-group">
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                                <button type="button" id="status" name="status" class="toggle-button <?php echo $media['status'] ? 'bg-green-500 text-white' : 'bg-gray-300 dark:bg-gray-700 text-gray-700 dark:text-gray-300'; ?> px-4 py-2 rounded"><?php echo $media['status'] ? 'Active' : 'Inactive'; ?></button>
                                <input type="hidden" name="status" id="status_input" value="<?php echo $media['status']; ?>">
                            </div>

                            <div class="form-group">
                                <label for="featured" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Featured</label>
                                <button type="button" id="featured" name="featured" class="toggle-button <?php echo $media['featured'] ? 'bg-green-500 text-white' : 'bg-gray-300 dark:bg-gray-700 text-gray-700 dark:text-gray-300'; ?> px-4 py-2 rounded"><?php echo $media['featured'] ? 'Featured' : 'Not Featured'; ?></button>
                                <input type="hidden" name="featured" id="featured_input" value="<?php echo $media['featured']; ?>">
                            </div>

                            <div class="form-group">
                                <label for="background_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Background Color</label>
                                <input type="color" name="background_color" id="background_color" value="<?php echo htmlspecialchars($media['background_color']); ?>" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>

                            <div class="form-group">
                                <label for="maintain_aspect_ratio" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Maintain Aspect Ratio</label>
                                <button type="button" id="maintain_aspect_ratio" name="maintain_aspect_ratio" class="toggle-button <?php echo $media['maintain_aspect_ratio'] ? 'bg-green-500 text-white' : 'bg-gray-300 dark:bg-gray-700 text-gray-700 dark:text-gray-300'; ?> px-4 py-2 rounded"><?php echo $media['maintain_aspect_ratio'] ? 'Maintain' : 'Do Not Maintain'; ?></button>
                                <input type="hidden" name="maintain_aspect_ratio" id="maintain_aspect_ratio_input" value="<?php echo $media['maintain_aspect_ratio']; ?>">
                            </div>

                            <div class="form-group">
                                <label for="orientation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Orientation</label>
                                <select name="orientation" id="orientation" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="portrait" <?php echo $media['orientation'] == 'portrait' ? 'selected' : ''; ?>>Portrait</option>
                                    <option value="landscape" <?php echo $media['orientation'] == 'landscape' ? 'selected' : ''; ?>>Landscape</option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="owner" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Owner</label>
                                <input type="text" name="owner" id="owner" value="<?php echo htmlspecialchars($media['owner']); ?>" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>

                            <div class="form-group">
                                <label for="license" class="block text-sm font-medium text-gray-700 dark:text-gray-300">License</label>
                                <input type="text" name="license" id="license" value="<?php echo htmlspecialchars($media['license']); ?>" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>

                            <div class="form-group">
                                <label for="publish_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Publish Date</label>
                                <input type="date" name="publish_date" id="publish_date" value="<?php echo htmlspecialchars($media['publish_date']); ?>" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>

                            <div class="form-group">
                                <label for="paid_content" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Paid Content</label>
                                <button type="button" id="paid_content" name="paid_content" class="toggle-button <?php echo $media['paid_content'] ? 'bg-green-500 text-white' : 'bg-gray-300 dark:bg-gray-700 text-gray-700 dark:text-gray-300'; ?> px-4 py-2 rounded"><?php echo $media['paid_content'] ? 'Paid' : 'Free'; ?></button>
                                <input type="hidden" name="paid_content" id="paid_content_input" value="<?php echo $media['paid_content']; ?>">
                            </div>

                            <div class="form-group">
                                <label for="watermark" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Watermark</label>
                                <button type="button" id="watermark" name="watermark" class="toggle-button <?php echo $media['watermark_text'] ? 'bg-green-500 text-white' : 'bg-gray-300 dark:bg-gray-700 text-gray-700 dark:text-gray-300'; ?> px-4 py-2 rounded"><?php echo $media['watermark_text'] ? 'Watermarked' : 'Not Watermarked'; ?></button>
                                <input type="hidden" name="watermark" id="watermark_input" value="<?php echo $media['watermark_text'] ? '1' : '0'; ?>">
                            </div>

                            <div id="watermark_text_section" class="form-group" style="display: <?php echo $media['watermark_text'] ? 'block' : 'none'; ?>;">
                                <label for="watermark_text" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Watermark Text</label>
                                <input type="text" name="watermark_text" id="watermark_text" value="<?php echo htmlspecialchars($media['watermark_text']); ?>" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>

                            <div class="form-group">
                                <label for="tags" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tags</label>
                                <input type="text" name="tags" id="tags" placeholder="Comma separated tags" value="<?php echo htmlspecialchars($media['tags']); ?>" class="tagify mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 text-center">
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 dark:bg-blue-700 dark:hover:bg-blue-800">
                            Update Media
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
    <script>
        // Initialize Tagify
        new Tagify(document.querySelector('.tagify'));

        // Initialize FilePond
        FilePond.registerPlugin(FilePondPluginImagePreview);
        const inputElement = document.querySelector('input[type="file"]');
        const pond = FilePond.create(inputElement, {
            allowMultiple: true,
            instantUpload: false,
            allowReorder: true,
            maxFiles: 10,
            maxFileSize: '50MB',
            labelIdle: 'Drag & Drop your files or <span class="filepond--label-action">Browse</span>',
            acceptedFileTypes: ['image/*', 'video/*', 'audio/*']
        });

        // Handle media type change
        document.getElementById('media_type').addEventListener('change', function() {
            const fileUploadSection = document.getElementById('file_upload_section');
            const urlUploadSection = document.getElementById('url_upload_section');
            switch (this.value) {
                case 'single_file':
                case 'multiple_files':
                    fileUploadSection.style.display = 'block';
                    urlUploadSection.style.display = 'none';
                    break;
                case 'single_url':
                case 'multiple_urls':
                    fileUploadSection.style.display = 'none';
                    urlUploadSection.style.display = 'block';
                    break;
            }
        });

        // Toggle button styles and hidden inputs synchronization
        document.querySelectorAll('.toggle-button').forEach(button => {
            button.addEventListener('click', function() {
                const input = document.getElementById(this.id + '_input');
                const isActive = this.classList.toggle('bg-green-500');
                this.classList.toggle('text-white');
                input.value = isActive ? '1' : '0';
            });
        });

        document.getElementById('watermark').addEventListener('click', function() {
            const watermarkTextSection = document.getElementById('watermark_text_section');
            const displayState = this.classList.contains('bg-green-500') ? 'none' : 'block';
            watermarkTextSection.style.display = displayState;
        });
    </script>
</body>
</html>
<?php include 'theme/footer.php'; ?>