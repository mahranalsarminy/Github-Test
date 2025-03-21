<?php
require_once(__DIR__ . '/../includes/init.php');

// التحقق من أذونات المستخدم (يجب أن يكون مدير)
if (!is_admin()) {
    die('غير مسموح: يجب أن تكون مديرًا للوصول إلى هذه الصفحة.');
}

// تحميل وتنفيذ SQL من ملف
function executeSQLFile($pdo, $filepath) {
    if (!file_exists($filepath)) {
        return [
            'success' => false,
            'message' => "الملف غير موجود: $filepath"
        ];
    }
    
    $sql = file_get_contents($filepath);
    if (!$sql) {
        return [
            'success' => false,
            'message' => "فشل قراءة ملف SQL"
        ];
    }
    
    // تقسيم SQL إلى استعلامات فردية
    $queries = explode(';', $sql);
    $totalQueries = count($queries);
    $successCount = 0;
    $errors = [];
    
    try {
        foreach ($queries as $query) {
            $query = trim($query);
            if (empty($query)) continue;
            
            try {
                if ($pdo->exec($query) !== false) {
                    $successCount++;
                }
            } catch (PDOException $e) {
                $errors[] = $e->getMessage();
            }
        }
        
        return [
            'success' => true,
            'executed' => $successCount,
            'total' => $totalQueries,
            'errors' => $errors
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => $e->getMessage()
        ];
    }
}

// التحقق من التحديثات المطلوبة
function checkRequiredUpdates($pdo) {
    $updates = [];
    
    // التحقق من وجود أعمدة site_settings
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM site_settings LIKE 'latest_items_count'");
        $updates['latest_items_count'] = ($stmt->rowCount() === 0);
    } catch (PDOException $e) {
        $updates['site_settings_error'] = $e->getMessage();
    }
    
    // التحقق من وجود جدول search_box_settings
    try {
        $stmt = $pdo->query("SHOW TABLES LIKE 'search_box_settings'");
        $updates['search_box_settings'] = ($stmt->rowCount() === 0);
    } catch (PDOException $e) {
        $updates['search_settings_error'] = $e->getMessage();
    }
    
    // التحقق من وجود عمود bg_color في جدول categories
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM categories LIKE 'bg_color'");
        $updates['bg_color'] = ($stmt->rowCount() === 0);
    } catch (PDOException $e) {
        $updates['categories_error'] = $e->getMessage();
    }
    
    return $updates;
}

// بيانات النموذج
$result = null;
$updates_needed = checkRequiredUpdates($pdo);
$update_required = !empty(array_filter($updates_needed, function($value) {
    return $value === true;
}));

// إذا تم إرسال النموذج، نفذ التحديثات
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_database'])) {
    $result = executeSQLFile($pdo, __DIR__ . '/../database_updates.sql');
    
    if ($result['success']) {
        // إعادة التحقق من التحديثات للتأكد من تطبيقها
        $updates_needed = checkRequiredUpdates($pdo);
        $update_required = !empty(array_filter($updates_needed, function($value) {
            return $value === true;
        }));
    }
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحديث قاعدة البيانات</title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <style>
        .update-container {
            max-width: 800px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .success-message {
            background-color: #d4edda;
            color: #155724;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .error-message {
            background-color: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        .warning-message {
            background-color: #fff3cd;
            color: #856404;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 10px;
            text-align: right;
            border-bottom: 1px solid #eee;
        }
        th {
            background-color: #f5f5f5;
        }
        .update-btn {
            background-color: #4A90E2;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
        }
        .update-btn:hover {
            background-color: #3A78C3;
        }
        .update-btn:disabled {
            background-color: #ccc;
            cursor: not-allowed;
        }
    </style>
</head>
<body>
    <div class="update-container">
        <h1>تحديث قاعدة البيانات</h1>
        
        <?php if ($result !== null): ?>
            <?php if ($result['success']): ?>
                <div class="success-message">
                    <h3>تم تنفيذ التحديثات بنجاح!</h3>
                    <p>تم تنفيذ <?php echo $result['executed']; ?> من أصل <?php echo $result['total']; ?> استعلام.</p>
                    
                    <?php if (!empty($result['errors'])): ?>
                        <div class="warning-message">
                            <h4>ملاحظات:</h4>
                            <ul>
                                <?php foreach ($result['errors'] as $error): ?>
                                    <li><?php echo htmlspecialchars($error); ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="error-message">
                    <h3>فشل تنفيذ التحديثات</h3>
                    <p><?php echo htmlspecialchars($result['message']); ?></p>
                </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <?php if ($update_required): ?>
            <div class="warning-message">
                <h3>تحديثات قاعدة البيانات مطلوبة</h3>
                <p>يلزم إجراء التحديثات التالية لدعم الميزات الجديدة في الصفحة الرئيسية:</p>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>العنصر</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (isset($updates_needed['latest_items_count'])): ?>
                        <tr>
                            <td>أعمدة إعدادات الموقع الجديدة</td>
                            <td>
                                <?php if ($updates_needed['latest_items_count']): ?>
                                    <span style="color: red;">غير موجود</span>
                                <?php else: ?>
                                    <span style="color: green;">موجود</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    
                    <?php if (isset($updates_needed['search_box_settings'])): ?>
                        <tr>
                            <td>جدول إعدادات صندوق البحث</td>
                            <td>
                                <?php if ($updates_needed['search_box_settings']): ?>
                                    <span style="color: red;">غير موجود</span>
                                <?php else: ?>
                                    <span style="color: green;">موجود</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                    
                    <?php if (isset($updates_needed['bg_color'])): ?>
                        <tr>
                            <td>عمود لون خلفية الفئة</td>
                            <td>
                                <?php if ($updates_needed['bg_color']): ?>
                                    <span style="color: red;">غير موجود</span>
                                <?php else: ?>
                                    <span style="color: green;">موجود</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <form method="POST" action="">
                <button type="submit" name="update_database" class="update-btn">تطبيق تحديثات قاعدة البيانات</button>
            </form>
        <?php else: ?>
            <div class="success-message">
                <h3>قاعدة البيانات محدثة</h3>
                <p>تم تنفيذ جميع التحديثات المطلوبة بنجاح للصفحة الرئيسية الجديدة.</p>
            </div>
            
            <p><a href="/admin/index.php" style="color: #4A90E2;">العودة إلى لوحة الإدارة</a></p>
        <?php endif; ?>
    </div>
</body>
</html>