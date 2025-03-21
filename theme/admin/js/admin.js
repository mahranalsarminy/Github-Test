document.addEventListener('DOMContentLoaded', function() {
    // Language Switcher
    const languageItems = document.querySelectorAll('.language-item');
    languageItems.forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            const newLang = item.getAttribute('data-lang');
            
            // إرسال طلب لتحديث اللغة عبر AJAX
            fetch('/admin/ajax/update_language.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: new URLSearchParams({
                    language: newLang
                })
            }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    // إذا تم تحديث اللغة بنجاح، نقوم بتحديث الصفحة
                    window.location.reload();
                } else {
                    console.error('Error updating language:', data.message);
                }
            });
        });
    });

    // Dark Mode Toggle
    const darkModeButton = document.getElementById('darkModeToggle');
    darkModeButton.addEventListener('click', function() {
        // إرسال طلب لتحديث وضع الداكن عبر AJAX
        const darkModeStatus = <?php echo $darkMode ? 'true' : 'false'; ?>; // نرسل قيمة صحيحة (true أو false)
        
        fetch('/admin/ajax/update_theme.php', {  // المسار إلى ملف update_theme.php
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                dark_mode: darkModeStatus ? '1' : '0' // تحويل القيمة إلى 1 أو 0 حسب الوضع
            })
        }).then(response => response.json())
        .then(data => {
            if (data.success) {
                // إذا تم تحديث الوضع بنجاح، نقوم بتحديث الصفحة
                window.location.reload();
            } else {
                console.error('Error updating theme:', data.message);
            }
        });
    });
});

