<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/functions.php';

// Load language file
$lang = require __DIR__ . '/../lang/admin/' . ($_SESSION['language'] ?? 'en') . '.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $title = trim($_POST['title'] ?? '');
    $file = $_FILES['file'] ?? null;
    $category = trim($_POST['category'] ?? '');
    $featured = isset($_POST['featured']) ? 1 : 0;

    if (empty($title) || empty($file) || empty($category)) {
        echo "Error: All fields are required.";
        exit;
    }

    // Process uploaded file
    $allowed_images = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $allowed_videos = ['mp4', 'webm', 'avi'];
    $file_name = basename($file['name']);
    $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    if (!in_array($file_extension, array_merge($allowed_images, $allowed_videos))) {
        echo "Error: Invalid file type.";
        exit;
    }

    $upload_dir = UPLOADS_DIR . '/' . (in_array($file_extension, $allowed_images) ? 'images' : 'videos');
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $new_file_name = uniqid() . '.' . $file_extension;
    $file_path = $upload_dir . '/' . $new_file_name;

    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        // Insert into database
        db_query("INSERT INTO media (title, file_path, type, category, featured, created_at) VALUES (:title, :file_path, :type, :category, :featured, NOW())", [
            'title' => $title,
            'file_path' => $file_path,
            'type' => in_array($file_extension, $allowed_images) ? 'image' : 'video',
            'category' => $category,
            'featured' => $featured
        ]);

        header('Location: /admin/dashboard');
        exit;
    } else {
        echo "Error: File upload failed.";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="<?= $_SESSION['language'] ?? 'en' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $lang['upload_media'] ?></title>
    <link rel="stylesheet" href="/assets/css/tailwind.css">
</head>
<body class="bg-white dark:bg-gray-900">
<main class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-center mb-8"><?= $lang['upload_media'] ?></h1>
    <form method="POST" enctype="multipart/form-data">
        <label for="title"><?= $lang['title'] ?></label>
        <input type="text" id="title" name="title" required class="w-full p-2 border rounded mb-4">

        <label for="file"><?= $lang['type'] ?></label>
        <input type="file" id="file" name="file" accept="image/*, video/*" required class="w-full p-2 border rounded mb-4">

        <label for="category"><?= $lang['category'] ?></label>
        <input type="text" id="category" name="category" required class="w-full p-2 border rounded mb-4">

        <label for="featured"><?= $lang['featured'] ?></label>
        <select id="featured" name="featured" class="w-full p-2 border rounded mb-4">
            <option value="0">No</option>
            <option value="1">Yes</option>
        </select>

        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded"><?= $lang['upload_media'] ?></button>
    </form>
</main>
</body>
</html>