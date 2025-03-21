<?php
// تضمين ملف قاعدة البيانات
require_once __DIR__ . '/includes/db.php';

// التحقق من أن المستخدم مسجل الدخول
session_start();
if (!isset($_SESSION['user_id'])) {
    die("You must be logged in to add a comment.");
}

// التحقق من أن الطلب POST صالح
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // استقبال البيانات من النموذج
    $mediaId = $_POST['media_id'] ?? null;
    $comment = trim($_POST['comment'] ?? '');

    // التحقق من صحة البيانات
    if (empty($mediaId) || empty($comment)) {
        die("Invalid input. Please provide both media ID and comment.");
    }

    // التحقق من وجود الوسائط (Media)
    $stmtMedia = $pdo->prepare("SELECT id FROM media WHERE id = ?");
    $stmtMedia->execute([$mediaId]);
    $mediaExists = $stmtMedia->fetch();

    if (!$mediaExists) {
        die("Media not found.");
    }

    // إضافة التعليق إلى قاعدة البيانات
    $userId = $_SESSION['user_id'];
    $stmtInsert = $pdo->prepare("INSERT INTO comments (user_id, media_id, comment) VALUES (?, ?, ?)");
    $stmtInsert->execute([$userId, $mediaId, $comment]);

    if ($stmtInsert->rowCount() > 0) {
        // إعادة توجيه المستخدم إلى صفحة الوسائط مع رسالة منبثقة
        echo "<script>
                alert('Comment added successfully!');
                window.location.href = '/media.php?id=" . urlencode($mediaId) . "';
              </script>";
        exit;
    } else {
        echo "Failed to add comment.";
    }
} else {
    die("Invalid request method.");
}
?>