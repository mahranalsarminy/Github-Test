<?php
require_once(__DIR__ . '/../includes/init.php');

// التحقق من أن الطلب يحتوي على معلمة البحث
$query = isset($_GET['q']) ? trim($_GET['q']) : '';

// قائمة الاقتراحات
$suggestions = [];

// الرد بتنسيق JSON
header('Content-Type: application/json');

// إذا كان الاستعلام قصيرًا جدًا، عودة بقائمة فارغة
if (strlen($query) < 2) {
    echo json_encode(['suggestions' => $suggestions]);
    exit;
}

try {
    // 1. البحث عن اقتراحات مطابقة في السجلات السابقة
    $stmt = $pdo->prepare("
        SELECT keyword, search_count FROM search_suggestions 
        WHERE keyword LIKE ? 
        ORDER BY search_count DESC, keyword ASC 
        LIMIT 10
    ");
    $stmt->execute(['%' . $query . '%']);
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 2. البحث عن عناوين الوسائط التي تطابق الاستعلام
    $stmt = $pdo->prepare("
        SELECT title as keyword FROM media 
        WHERE status = 1 AND title LIKE ? 
        ORDER BY featured DESC, views DESC, created_at DESC 
        LIMIT 5
    ");
    $stmt->execute(['%' . $query . '%']);
    $media_matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. البحث عن أسماء الفئات التي تطابق الاستعلام
    $stmt = $pdo->prepare("
        SELECT name as keyword FROM categories 
        WHERE name LIKE ? 
        ORDER BY display_order ASC 
        LIMIT 3
    ");
    $stmt->execute(['%' . $query . '%']);
    $category_matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 4. دمج النتائج مع إزالة التكرار
    $all_matches = array_merge($matches, $media_matches, $category_matches);
    $unique_keywords = [];
    
    foreach ($all_matches as $match) {
        $keyword = mb_strtolower($match['keyword']);
        if (!isset($unique_keywords[$keyword])) {
            $unique_keywords[$keyword] = [
                'keyword' => $match['keyword'],
                'count' => $match['search_count'] ?? 0
            ];
        }
    }
    
    // 5. ترتيب الاقتراحات حسب العداد ثم الأبجدية
    usort($unique_keywords, function($a, $b) {
        if ($a['count'] === $b['count']) {
            return strcmp($a['keyword'], $b['keyword']);
        }
        return $b['count'] - $a['count'];
    });
    
    // 6. تحويل النتائج إلى الشكل المطلوب
    $suggestions = array_map(function($item) {
        return ['keyword' => $item['keyword']];
    }, array_slice(array_values($unique_keywords), 0, 10));
    
} catch (PDOException $e) {
    // تسجيل الخطأ ولكن لا نعرضه للمستخدم
    error_log("خطأ في اقتراحات البحث: " . $e->getMessage());
}

// إرجاع النتائج
echo json_encode(['suggestions' => $suggestions]);