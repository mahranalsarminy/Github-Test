<?php
require_once __DIR__ . '/../includes/init.php';

// تحديد الوقت الحالي والمستخدم
define('CURRENT_UTC_TIME', '2025-03-14 03:05:07');
define('CURRENT_USER', 'mahranalsarminy');

// التحقق من وجود استعلام البحث
$query = trim($_GET['q'] ?? '');
if (empty($query) || strlen($query) < 2) {
    header('Content-Type: application/json');
    echo json_encode(['suggestions' => []]);
    exit;
}

try {
    // البحث عن الاقتراحات
    $stmt = $pdo->prepare("
        SELECT keyword, search_count
        FROM search_suggestions
        WHERE keyword LIKE :query
        ORDER BY search_count DESC, last_searched_at DESC
        LIMIT 10
    ");
    $stmt->execute(['query' => $query . '%']);
    $suggestions = $stmt->fetchAll();

    // البحث في العناوين
    $stmt = $pdo->prepare("
        SELECT DISTINCT title
        FROM wallpapers
        WHERE title LIKE :query
        AND status = 'active'
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute(['query' => '%' . $query . '%']);
    $titles = $stmt->fetchAll(PDO::FETCH_COLUMN);

    // دمج النتائج
    $results = [];
    
    // إضافة الاقتراحات من جدول search_suggestions
    foreach ($suggestions as $suggestion) {
        $results[] = [
            'title' => $suggestion['keyword'],
            'type' => 'suggestion',
            'count' => $suggestion['search_count']
        ];
    }

    // إضافة العناوين من جدول wallpapers
    foreach ($titles as $title) {
        if (!in_array($title, array_column($results, 'title'))) {
            $results[] = [
                'title' => $title,
                'type' => 'wallpaper'
            ];
        }
    }

    // تحديث سجل البحث
    $stmt = $pdo->prepare("CALL update_search_suggestion(:keyword)");
    $stmt->execute(['keyword' => $query]);

    // إرجاع النتائج
    header('Content-Type: application/json');
    echo json_encode([
        'suggestions' => $results
    ]);

} catch (PDOException $e) {
    error_log("Search suggestion error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'An error occurred while fetching suggestions',
        'suggestions' => []
    ]);
}