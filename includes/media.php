<?php
// Function to upload media files
function upload_media($file, $type) {
    $allowed_images = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    $allowed_videos = ['mp4', 'webm', 'avi'];
    $allowed_extensions = $type === 'image' ? $allowed_images : $allowed_videos;

    $file_name = basename($file['name']);
    $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

    if (!in_array($file_extension, $allowed_extensions)) {
        return ['error' => 'Invalid file type.'];
    }

    $upload_dir = UPLOADS_DIR . '/' . $type . 's/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $new_file_name = uniqid() . '.' . $file_extension;
    $file_path = $upload_dir . $new_file_name;

    if (move_uploaded_file($file['tmp_name'], $file_path)) {
        if ($type === 'image') {
            process_image($file_path);
            if ($_SESSION['role'] !== 'premium') {
                add_watermark($file_path);
            }
        }
        return ['file_path' => $file_path];
    } else {
        return ['error' => 'File upload failed.'];
    }
}

// Function to retrieve featured media
function get_featured_media($type, $limit) {
    return db_fetch_all("SELECT id, title, file_path, category FROM media WHERE type = :type AND featured = 1 LIMIT :limit", [
        'type' => $type,
        'limit' => $limit,
    ]);
}