<?php
require_once(__DIR__ . '/includes/init.php');

// التحقق من أن الطلب هو POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // طريقة غير مسموح بها
    exit;
}

// استخراج قيمة السمة
$theme = isset($_POST['theme']) ? trim($_POST['theme']) : '';

// التحقق من صحة القيمة
if (!in_array($theme, ['light', 'dark'])) {
    http_response_code(400); // طلب غير صالح
    echo json_encode(['error' => 'قيمة سمة غير صالحة']);
    exit;
}

// حفظ القيمة في جلسة المستخدم
$_SESSION['theme'] = $theme;

// إذا كان المستخدم مسجل الدخول، حفظ التفضيل في قاعدة البيانات
$user_id = $_SESSION['user_id'] ?? null;
if ($user_id) {
    try {
        $stmt = $pdo->prepare("UPDATE users SET theme_preference = ? WHERE id = ?");
        $stmt->execute([$theme, $user_id]);
    } catch (PDOException $e) {
        error_log("خطأ في حفظ تفضيل السمة: " . $e->getMessage());
    }
}

// إرجاع استجابة نجاح
header('Content-Type: application/json');
echo json_encode([
    'success' => true,
    'message' => 'تم حفظ تفضيل السمة بنجاح',
    'theme' => $theme
]);