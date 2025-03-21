<?php

/**
 * Get contact form settings from database
 * @return array
 */
function getContactSettings() {
    global $pdo;
    
    try {
        // تعديل الاستعلام ليتناسب مع هيكل الجدول الجديد
        $stmt = $pdo->query("SELECT * FROM contact_settings WHERE id = 1");
        $settings = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($settings) {
            // تحويل حقل required_fields من JSON إلى مصفوفة
            $settings['required_fields'] = json_decode($settings['required_fields'] ?? '[]', true);
            
            // تعيين القيم الافتراضية إذا كانت فارغة
            $defaults = [
                'contact_email' => 'play.earn.net@gmail.com',
                'email_subject_prefix' => 'WallPix.Top',
                'enable_auto_reply' => 0,
                'enable_attachments' => 0,
                'max_file_size' => 5,
                'allowed_file_types' => 'pdf,doc,docx,jpg,jpeg,png',
                'required_fields' => ['name', 'email', 'message']
            ];

            foreach ($defaults as $key => $value) {
                if (empty($settings[$key])) {
                    $settings[$key] = $value;
                }
            }
        } else {
            // إذا لم يتم العثور على إعدادات، استخدم القيم الافتراضية
            $settings = [
                'contact_email' => 'play.earn.net@gmail.com',
                'email_subject_prefix' => 'WallPix.Top',
                'recaptcha_site_key' => '',
                'recaptcha_secret_key' => '',
                'enable_auto_reply' => 0,
                'auto_reply_template_id' => null,
                'enable_attachments' => 0,
                'max_file_size' => 5,
                'allowed_file_types' => 'pdf,doc,docx,jpg,jpeg,png',
                'required_fields' => ['name', 'email', 'message']
            ];

            // إدراج الإعدادات الافتراضية في قاعدة البيانات
            $stmt = $pdo->prepare("
                INSERT INTO contact_settings (
                    contact_email, 
                    email_subject_prefix,
                    enable_auto_reply,
                    enable_attachments,
                    max_file_size,
                    allowed_file_types,
                    required_fields
                ) VALUES (?, ?, ?, ?, ?, ?, ?)
            ");

            $stmt->execute([
                $settings['contact_email'],
                $settings['email_subject_prefix'],
                $settings['enable_auto_reply'],
                $settings['enable_attachments'],
                $settings['max_file_size'],
                $settings['allowed_file_types'],
                json_encode($settings['required_fields'])
            ]);
        }

        return $settings;
    } catch (PDOException $e) {
        // تسجيل الخطأ وإرجاع الإعدادات الافتراضية
        error_log("Error fetching contact settings: " . $e->getMessage());
        return [
            'contact_email' => 'play.earn.net@gmail.com',
            'email_subject_prefix' => 'WallPix.Top',
            'recaptcha_site_key' => '',
            'recaptcha_secret_key' => '',
            'enable_auto_reply' => 0,
            'auto_reply_template_id' => null,
            'enable_attachments' => 0,
            'max_file_size' => 5,
            'allowed_file_types' => 'pdf,doc,docx,jpg,jpeg,png',
            'required_fields' => ['name', 'email', 'message']
        ];
    }
}

/**
 * Save contact form settings
 * @param array $settings
 * @return bool
 */
function saveContactSettings($settings) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            UPDATE contact_settings SET
                contact_email = ?,
                email_subject_prefix = ?,
                recaptcha_site_key = ?,
                recaptcha_secret_key = ?,
                enable_auto_reply = ?,
                auto_reply_template_id = ?,
                enable_attachments = ?,
                max_file_size = ?,
                allowed_file_types = ?,
                required_fields = ?,
                updated_at = CURRENT_TIMESTAMP
            WHERE id = 1
        ");

        return $stmt->execute([
            $settings['contact_email'],
            $settings['email_subject_prefix'],
            $settings['recaptcha_site_key'] ?? null,
            $settings['recaptcha_secret_key'] ?? null,
            $settings['enable_auto_reply'] ? 1 : 0,
            $settings['auto_reply_template_id'] ?? null,
            $settings['enable_attachments'] ? 1 : 0,
            $settings['max_file_size'] ?? 5,
            $settings['allowed_file_types'] ?? 'pdf,doc,docx,jpg,jpeg,png',
            is_array($settings['required_fields']) ? json_encode($settings['required_fields']) : $settings['required_fields']
        ]);
    } catch (PDOException $e) {
        error_log("Error saving contact settings: " . $e->getMessage());
        return false;
    }
}