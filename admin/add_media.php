<?php
define('ROOT_DIR', dirname(__DIR__));
require_once ROOT_DIR . '/includes/init.php';
require_admin();

// إعداد سجل الأخطاء
ini_set('log_errors', 1);
ini_set('error_log', ROOT_DIR . '/logs/php_errors.log');
error_reporting(E_ALL);

// تأكد من وجود مجلد السجلات
if (!is_dir(ROOT_DIR . '/logs')) {
    mkdir(ROOT_DIR . '/logs', 0755, true);
}

$page_title = "Add Media";
$errors = [];
$success = false;

// جلب التصنيفات للقائمة المنسدلة
$stmt = $pdo->query("SELECT id, name FROM categories WHERE is_active = 1 ORDER BY name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
// معالجة النموذج المرسل
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        // بدء المعاملة لضمان تماسك البيانات
        $pdo->beginTransaction();
        
        // استخراج القيم من النموذج
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $category_id = $_POST['category_id'] ?? 1;
        $status = isset($_POST['status_input']) ? (int)$_POST['status_input'] : 0;
        $featured = isset($_POST['featured_input']) ? (int)$_POST['featured_input'] : 0;
        $background_color = $_POST['background_color'] ?? '#FFFFFF';
        $maintain_aspect_ratio = isset($_POST['maintain_aspect_ratio_input']) ? (int)$_POST['maintain_aspect_ratio_input'] : 0;
        $orientation = $_POST['orientation'] ?? 'portrait';
        $tags = $_POST['tags'] ?? '';
        $owner = $_POST['owner'] ?? '';
        $license = $_POST['license'] ?? '';
        $publish_date = $_POST['publish_date'] ?? date('Y-m-d');
        $paid_content = isset($_POST['paid_content_input']) ? (int)$_POST['paid_content_input'] : 0;
        $watermark_text = isset($_POST['watermark_text']) ? $_POST['watermark_text'] : null;
        $media_type = $_POST['media_type'] ?? 'single_file';
        
        if ($media_type == 'single_file' || $media_type == 'multiple_files') {
            // التعامل مع رفع الملفات
            if (isset($_FILES['media_files']) && !empty($_FILES['media_files']['name'][0])) {
                $files = $_FILES['media_files'];
                $total_files = count($files['name']);
                
                for ($i = 0; $i < $total_files; $i++) {
                    if ($files['error'][$i] == 0) {
                        $file_name = time() . '_' . $files['name'][$i]; // إضافة طابع زمني لمنع تكرار أسماء الملفات
                        $file_tmp = $files['tmp_name'][$i];
                        $file_type = $files['type'][$i];
                        $file_size = $files['size'][$i];
                        
                        // تحديد مسار التخزين
                        $upload_dir = 'uploads/media/' . date('Y/m/');
                        $upload_path = ROOT_DIR . '/' . $upload_dir;
                        
                        // إنشاء المجلد إذا لم يكن موجودًا
                        if (!file_exists($upload_path)) {
                            if (!mkdir($upload_path, 0755, true)) {
                                throw new Exception("فشل في إنشاء مجلد التحميل: $upload_path");
                            }
                        }
                        
                        $file_path = $upload_dir . $file_name;
                        $full_path = ROOT_DIR . '/' . $file_path;
                        
                        // نقل الملف المؤقت إلى المجلد المستهدف
                        if (!move_uploaded_file($file_tmp, $full_path)) {
                            throw new Exception("فشل في رفع الملف: $file_name");
                        }
                        
                        // تطبيق العلامة المائية إذا طلبت
                        if ($watermark_text && strpos($file_type, 'image/') === 0) {
                            apply_watermark($full_path, $watermark_text);
                        }
                        
                        // استعلام إدراج في قاعدة البيانات
                        $stmt = $pdo->prepare("
                            INSERT INTO media (
                                title, description, category_id, file_name, file_path, 
                                file_type, file_size, status, featured, background_color, 
                                maintain_aspect_ratio, orientation, owner, license, 
                                publish_date, paid_content, watermark_text, created_at
                            ) VALUES (
                                ?, ?, ?, ?, ?, 
                                ?, ?, ?, ?, ?, 
                                ?, ?, ?, ?, 
                                ?, ?, ?, NOW()
                            )
                        ");
                        
                        $result = $stmt->execute([
                            $title, $description, $category_id, $file_name, $file_path,
                            $file_type, $file_size, $status, $featured, $background_color,
                            $maintain_aspect_ratio, $orientation, $owner, $license,
                            $publish_date, $paid_content, $watermark_text
                        ]);
                        
                        if (!$result) {
                            throw new Exception("فشل في حفظ بيانات الملف في قاعدة البيانات");
                        }
                        
                        // معالجة العلامات إذا وجدت
                        if (!empty($tags)) {
                            processMediaTags($pdo->lastInsertId(), $tags);
                        }
                    } else {
                        // التعامل مع أخطاء الرفع
                        throw new Exception("خطأ في رفع الملف: " . getFileErrorMessage($files['error'][$i]));
                    }
                }
            } else {
                throw new Exception("لم يتم اختيار أي ملف للرفع");
            }
        } else {
            // التعامل مع الروابط الخارجية
            $urls = explode("\n", trim($_POST['media_urls'] ?? ''));
            if (empty($urls[0])) {
                throw new Exception("لم يتم إدخال أي رابط");
            }
            
            foreach ($urls as $url) {
                $url = trim($url);
                if (filter_var($url, FILTER_VALIDATE_URL)) {
                    $stmt = $pdo->prepare("
                        INSERT INTO media (
                            title, description, category_id, external_url, status, 
                            featured, background_color, maintain_aspect_ratio, orientation, 
                            owner, license, publish_date, paid_content, created_at
                        ) VALUES (
                            ?, ?, ?, ?, ?, 
                            ?, ?, ?, ?, 
                            ?, ?, ?, ?, NOW()
                        )
                    ");
                    
                    $result = $stmt->execute([
                        $title, $description, $category_id, $url, $status,
                        $featured, $background_color, $maintain_aspect_ratio, $orientation,
                        $owner, $license, $publish_date, $paid_content
                    ]);
                    
                    if (!$result) {
                        throw new Exception("فشل في حفظ بيانات الرابط في قاعدة البيانات");
                    }
                    
                    // معالجة العلامات إذا وجدت
                    if (!empty($tags)) {
                        processMediaTags($pdo->lastInsertId(), $tags);
                    }
                } else {
                    throw new Exception("رابط غير صالح: $url");
                }
            }
        }
        
        // إتمام المعاملة إذا لم يحدث أي أخطاء
        $pdo->commit();
        
        $_SESSION['success'] = 'تمت إضافة الوسائط بنجاح';
        header("Location: media.php");
        exit;
        
    } catch (Exception $e) {
        // التراجع عن المعاملة في حالة حدوث أخطاء
        $pdo->rollBack();
        $_SESSION['error'] = 'حدث خطأ: ' . $e->getMessage();
    }
}
// دالة لمعالجة العلامات
function processMediaTags($media_id, $tags_string) {
    global $pdo;
    
    if (empty($tags_string)) return;
    
    $tags_array = explode(',', $tags_string);
    foreach ($tags_array as $tag_name) {
        $tag_name = trim($tag_name);
        if (empty($tag_name)) continue;
        
        // بحث عن العلامة أو إنشاؤها إذا لم تكن موجودة
        $slug = strtolower(preg_replace('/[^a-z0-9]+/', '-', $tag_name));
        
        $stmt = $pdo->prepare("SELECT id FROM tags WHERE slug = ?");
        $stmt->execute([$slug]);
        $tag = $stmt->fetch();
        
        if (!$tag) {
            $stmt = $pdo->prepare("INSERT INTO tags (name, slug, created_at) VALUES (?, ?, NOW())");
            $stmt->execute([$tag_name, $slug]);
            $tag_id = $pdo->lastInsertId();
        } else {
            $tag_id = $tag['id'];
        }
        
        // ربط العلامة بالوسائط
        $stmt = $pdo->prepare("INSERT INTO media_tags (media_id, tag_id) VALUES (?, ?)");
        $stmt->execute([$media_id, $tag_id]);
    }
}

// دالة لتطبيق العلامة المائية على الصور
function apply_watermark($file_path, $watermark_text) {
    if (empty($watermark_text)) return false;
    
    // التحقق من وجود الملف
    if (!file_exists($file_path)) return false;
    
    $image = @imagecreatefromstring(file_get_contents($file_path));
    if (!$image) return false;
    
    $width = imagesx($image);
    $height = imagesy($image);
    
    // إنشاء نص العلامة المائية
    $font_size = 20;
    $font_color = imagecolorallocatealpha($image, 255, 255, 255, 50);
    $font_angle = 0;
    
    // استخدام الخط البسيط في حالة عدم توفر الخط المخصص
    $text_width = strlen($watermark_text) * 8;
    $text_x = ($width - $text_width) / 2;
    $text_y = $height - 20;
    
    imagestring($image, 5, $text_x, $text_y, $watermark_text, $font_color);
    
    // حفظ الصورة بنفس التنسيق الأصلي
    switch (strtolower(pathinfo($file_path, PATHINFO_EXTENSION))) {
        case 'jpeg':
        case 'jpg':
            imagejpeg($image, $file_path, 90);
            break;
        case 'png':
            imagepng($image, $file_path, 9);
            break;
        case 'gif':
            imagegif($image, $file_path);
            break;
        default:
            // التنسيق غير مدعوم
            imagedestroy($image);
            return false;
    }
    
    imagedestroy($image);
    return true;
}

// دالة للحصول على رسالة خطأ رفع الملفات
function getFileErrorMessage($error_code) {
    switch ($error_code) {
        case UPLOAD_ERR_INI_SIZE:
            return "حجم الملف يتجاوز الحد الأقصى المسموح به في إعدادات PHP";
        case UPLOAD_ERR_FORM_SIZE:
            return "حجم الملف يتجاوز الحد الأقصى المسموح به في النموذج";
        case UPLOAD_ERR_PARTIAL:
            return "تم رفع جزء من الملف فقط";
        case UPLOAD_ERR_NO_FILE:
            return "لم يتم رفع أي ملف";
        case UPLOAD_ERR_NO_TMP_DIR:
            return "المجلد المؤقت غير موجود";
        case UPLOAD_ERR_CANT_WRITE:
            return "فشل في كتابة الملف على القرص";
        case UPLOAD_ERR_EXTENSION:
            return "توقف رفع الملف بسبب امتداد PHP";
        default:
            return "خطأ غير معروف";
    }
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
    <link href="https://cdn.jsdelivr.net/npm/@yaireo/tagify/dist/tagify.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/@yaireo/tagify"></script>
    <style>
        .preview-image {
            max-width: 100px;
            max-height: 100px;
            margin: 5px;
            border: 1px solid #ccc;
        }
        .preview-container {
            display: flex;
            flex-wrap: wrap;
            margin-top: 10px;
            padding: 5px;
            border: 1px dashed #ccc;
            min-height: 50px;
        }
        .toggle-button.active {
            background-color: #4CAF50;
            color: white;
        }
    </style>
</head>
<body class="bg-gray-100 dark:bg-gray-900 dark:text-white">
    <?php include 'theme/header.php'; ?>

    <div class="flex flex-1 flex-col md:flex-row">
        <aside class="md:w-64 w-full md:block hidden">
            <?php include 'theme/sidebar.php'; ?>
        </aside>

        <main class="flex-1 p-4 mt-16 w-full">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6 w-full">
                <h1 class="text-2xl font-bold mb-4 text-center">Add Media</h1>

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
                            <div class="form-group mb-4">
                                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Title</label>
                                <input type="text" name="title" id="title" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-800 dark:text-white">
                            </div>

                            <div class="form-group mb-4">
                                <label for="description" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Description</label>
                                <textarea name="description" id="description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-800 dark:text-white"></textarea>
                            </div>

                            <div class="form-group mb-4">
                                <label for="category_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Category</label>
                                <select name="category_id" id="category_id" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-800 dark:text-white">
                                    <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['name']); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="form-group mb-4">
                                <label for="media_type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Media Type</label>
                                <select name="media_type" id="media_type" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-800 dark:text-white">
                                    <option value="single_file">Upload Single File</option>
                                    <option value="multiple_files">Upload Multiple Files</option>
                                    <option value="single_url">Single URL</option>
                                    <option value="multiple_urls">Multiple URLs</option>
                                </select>
                            </div>

                            <!-- قسم رفع الملفات (استبدلنا FilePond بنموذج HTML عادي) -->
                            <div id="file_upload_section" class="form-group mb-4">
                                <label for="media_files" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Upload Files</label>
                                <input type="file" name="media_files[]" id="media_files" multiple class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-800 dark:text-white">
                                <div id="file_preview" class="preview-container">
                                    <!-- هنا ستظهر معاينة الملفات بعد اختيارها -->
                                </div>
                            </div>

                            <!-- قسم الروابط الخارجية -->
                            <div id="url_upload_section" class="form-group mb-4" style="display: none;">
                                <label for="media_urls" class="block text-sm font-medium text-gray-700 dark:text-gray-300">External URLs</label>
                                <textarea name="media_urls" id="media_urls" rows="4" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-800 dark:text-white" placeholder="Enter one URL per line"></textarea>
                                <div id="url_preview" class="preview-container">
                                    <!-- هنا ستظهر معاينة الروابط بعد إدخالها -->
                                </div>
                            </div>
                        </div>
                        <div>
                            <div class="form-group mb-4">
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status</label>
                                <button type="button" id="status_btn" class="toggle-button bg-gray-300 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded">
                                    <span class="mr-2">Inactive</span>
                                    <i class="fas fa-toggle-off"></i>
                                </button>
                                <input type="hidden" name="status_input" id="status_input" value="0">
                            </div>

                            <div class="form-group mb-4">
                                <label for="featured" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Featured</label>
                                <button type="button" id="featured_btn" class="toggle-button bg-gray-300 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded">
                                    <span class="mr-2">Not Featured</span>
                                    <i class="fas fa-toggle-off"></i>
                                </button>
                                <input type="hidden" name="featured_input" id="featured_input" value="0">
                            </div>

                            <div class="form-group mb-4">
                                <label for="background_color" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Background Color</label>
                                <input type="color" name="background_color" id="background_color" value="#FFFFFF" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>

                            <div class="form-group mb-4">
                                <label for="maintain_aspect_ratio" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Maintain Aspect Ratio</label>
                                <button type="button" id="maintain_aspect_ratio_btn" class="toggle-button bg-gray-300 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded">
                                    <span class="mr-2">No</span>
                                    <i class="fas fa-toggle-off"></i>
                                </button>
                                <input type="hidden" name="maintain_aspect_ratio_input" id="maintain_aspect_ratio_input" value="0">
                            </div>

                            <div class="form-group mb-4">
                                <label for="orientation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Orientation</label>
                                <select name="orientation" id="orientation" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-800 dark:text-white">
                                    <option value="portrait">Portrait</option>
                                    <option value="landscape">Landscape</option>
                                </select>
                            </div>

                            <div class="form-group mb-4">
                                <label for="owner" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Owner</label>
                                <input type="text" name="owner" id="owner" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-800 dark:text-white">
                            </div>

                            <div class="form-group mb-4">
                                <label for="license" class="block text-sm font-medium text-gray-700 dark:text-gray-300">License</label>
                                <input type="text" name="license" id="license" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-800 dark:text-white">
                            </div>

                            <div class="form-group mb-4">
                                <label for="publish_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Publish Date</label>
                                <input type="date" name="publish_date" id="publish_date" value="<?php echo date('Y-m-d'); ?>" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-800 dark:text-white">
                            </div>
                            <div class="form-group mb-4">
                                <label for="paid_content" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Paid Content</label>
                                <button type="button" id="paid_content_btn" class="toggle-button bg-gray-300 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded">
                                    <span class="mr-2">Free</span>
                                    <i class="fas fa-toggle-off"></i>
                                </button>
                                <input type="hidden" name="paid_content_input" id="paid_content_input" value="0">
                            </div>

                            <div class="form-group mb-4">
                                <label for="watermark" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Watermark</label>
                                <button type="button" id="watermark_btn" class="toggle-button bg-gray-300 dark:bg-gray-700 text-gray-700 dark:text-gray-300 px-4 py-2 rounded">
                                    <span class="mr-2">No Watermark</span>
                                    <i class="fas fa-toggle-off"></i>
                                </button>
                                <input type="hidden" name="watermark_input" id="watermark_input" value="0">
                            </div>
                            
                            <div id="watermark_text_section" class="form-group mb-4" style="display: none;">
                                <label for="watermark_text" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Watermark Text</label>
                                <input type="text" name="watermark_text" id="watermark_text" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-800 dark:text-white">
                            </div>

                            <div class="form-group mb-4">
                                <label for="tags" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tags</label>
                                <input type="text" name="tags" id="tags" placeholder="Comma separated tags" class="tagify mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm dark:bg-gray-800 dark:text-white">
                            </div>
                        </div>
                    </div>

                    <div class="mt-6 text-center">
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600 dark:bg-blue-700 dark:hover:bg-blue-800">
                            Add Media
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>
    <script>
        // تهيئة Tagify
        new Tagify(document.querySelector('#tags'));

        // معالجة تغيير نوع الوسائط
        document.getElementById('media_type').addEventListener('change', function() {
            const fileUploadSection = document.getElementById('file_upload_section');
            const urlUploadSection = document.getElementById('url_upload_section');
            
            if (this.value === 'single_file' || this.value === 'multiple_files') {
                fileUploadSection.style.display = 'block';
                urlUploadSection.style.display = 'none';
            } else {
                fileUploadSection.style.display = 'none';
                urlUploadSection.style.display = 'block';
            }
        });

        // معاينة الملفات المختارة
        document.getElementById('media_files').addEventListener('change', function() {
            const previewContainer = document.getElementById('file_preview');
            previewContainer.innerHTML = '';
            
            if (this.files) {
                Array.from(this.files).forEach(file => {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const img = document.createElement('img');
                            img.src = e.target.result;
                            img.className = 'preview-image';
                            previewContainer.appendChild(img);
                        };
                        reader.readAsDataURL(file);
                    } else {
                        // معاينة للملفات غير الصور
                        const fileDiv = document.createElement('div');
                        fileDiv.className = 'preview-image flex items-center justify-center bg-gray-200 dark:bg-gray-700';
                        fileDiv.textContent = file.name.substring(0, 10) + '...';
                        previewContainer.appendChild(fileDiv);
                    }
                });
            }
        });

        // معاينة الروابط المدخلة
        document.getElementById('media_urls').addEventListener('input', function() {
            const previewContainer = document.getElementById('url_preview');
            previewContainer.innerHTML = '';
            
            const urls = this.value.split('\n').map(url => url.trim()).filter(url => url !== '');
            
            urls.forEach(url => {
                if (url.match(/\.(jpeg|jpg|gif|png)$/i) !== null) {
                    const img = document.createElement('img');
                    img.src = url;
                    img.className = 'preview-image';
                    img.onerror = function() {
                        this.src = 'https://via.placeholder.com/100x100?text=Invalid+URL';
                    };
                    previewContainer.appendChild(img);
                } else {
                    const linkDiv = document.createElement('div');
                    linkDiv.className = 'preview-image flex items-center justify-center bg-gray-200 dark:bg-gray-700';
                    linkDiv.textContent = url.substring(0, 15) + '...';
                    previewContainer.appendChild(linkDiv);
                }
            });
        });

        // معالجة أزرار التبديل
        const toggleButtons = {
            'status_btn': {
                input: 'status_input',
                onText: 'Active',
                offText: 'Inactive',
                icon: true
            },
            'featured_btn': {
                input: 'featured_input',
                onText: 'Featured',
                offText: 'Not Featured',
                icon: true
            },
            'maintain_aspect_ratio_btn': {
                input: 'maintain_aspect_ratio_input',
                onText: 'Yes',
                offText: 'No',
                icon: true
            },
            'paid_content_btn': {
                input: 'paid_content_input',
                onText: 'Paid',
                offText: 'Free',
                icon: true
            },
            'watermark_btn': {
                input: 'watermark_input',
                onText: 'Add Watermark',
                offText: 'No Watermark',
                icon: true,
                extra: function(isActive) {
                    document.getElementById('watermark_text_section').style.display = isActive ? 'block' : 'none';
                }
            }
        };

        // تهيئة أزرار التبديل
        Object.keys(toggleButtons).forEach(btnId => {
            const config = toggleButtons[btnId];
            const btn = document.getElementById(btnId);
            const input = document.getElementById(config.input);
            
            btn.addEventListener('click', function() {
                const isActive = !btn.classList.contains('active');
                
                if (isActive) {
                    btn.classList.add('active', 'bg-green-500');
                    btn.classList.remove('bg-gray-300', 'dark:bg-gray-700');
                    
                    if (config.icon) {
                        const icon = btn.querySelector('i');
                        if (icon) {
                            icon.classList.remove('fa-toggle-off');
                            icon.classList.add('fa-toggle-on');
                        }
                    }
                    
                    const textSpan = btn.querySelector('span');
                    if (textSpan && config.onText) {
                        textSpan.textContent = config.onText;
                    }
                    
                    input.value = '1';
                } else {
                    btn.classList.remove('active', 'bg-green-500');
                    btn.classList.add('bg-gray-300', 'dark:bg-gray-700');
                    
                    if (config.icon) {
                        const icon = btn.querySelector('i');
                        if (icon) {
                            icon.classList.remove('fa-toggle-on');
                            icon.classList.add('fa-toggle-off');
                        }
                    }
                    
                    const textSpan = btn.querySelector('span');
                    if (textSpan && config.offText) {
                        textSpan.textContent = config.offText;
                    }
                    
                    input.value = '0';
                }
                
                if (config.extra && typeof config.extra === 'function') {
                    config.extra(isActive);
                }
            });
        });
    </script>
</body>
</html>
<?php include 'theme/footer.php'; ?>    