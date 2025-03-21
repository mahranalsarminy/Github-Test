<?php
require_once(__DIR__ . '/../includes/init.php');

// التحقق من أن الطلب هو POST وليس GET
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // طريقة غير مسموح بها
    exit;
}

// استخراج استعلام البحث
$query = isset($_POST['query']) ? trim($_POST['query']) : '';

// التحقق من أن الاستعلام ليس فارغًا
if (empty($query) || strlen($query) < 2) {
    http_response_code(400); // طلب غير صالح
    echo json_encode(['error' => 'استعلام البحث قصير جدًا أو فارغ']);
    exit;
}

// حد أقصى لطول الاستعلام
$query = substr($query, 0, 100);

// تنظيف الاستعلام من أي رموز خاصة
$query = filter_var($query, FILTER_SANITIZE_STRING);

try {
    // التحقق مما إذا كان الاستعلام موجودًا مسبقًا
    $stmt = $pdo->prepare("SELECT id, search_count FROM search_suggestions WHERE keyword = ?");
    $stmt->execute([$query]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // تحديث العداد للاستعلام الموجود
        $stmt = $pdo->prepare("UPDATE search_suggestions SET search_count = search_count + 1, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$result['id']]);
        
        $response = [
            'success' => true,
            'message' => 'تم تحديث عداد البحث',
            'count' => $result['search_count'] + 1
        ];
    } else {
        // إضافة استعلام بحث جديد
        $stmt = $pdo->prepare("INSERT INTO search_suggestions (keyword, search_count) VALUES (?, 1)");
        $stmt->execute([$query]);
        
        $response = [
            'success' => true,
            'message' => 'تم حفظ استعلام البحث الجديد',
            'count' => 1
        ];
    }
    
    // إرجاع استجابة نجاح
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (PDOException $e) {
    // تسجيل الخطأ داخليًا
    error_log("خطأ في حفظ البحث: " . $e->getMessage());
    
    // إرجاع استجابة خطأ للعميل
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ أثناء حفظ استعلام البحث'
    ]);
}