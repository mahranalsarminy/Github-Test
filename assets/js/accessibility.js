/**
 * Scripts for Accessibility Features
 */
document.addEventListener('DOMContentLoaded', function() {
    setupAccessibilityPanel();
    setupFontSizeOptions();
    setupContrastOptions();
    setupScreenReader();
    setupKeyboardNavigation();
});

/**
 * إعداد لوحة إمكانية الوصول
 */
function setupAccessibilityPanel() {
    const accessibilityToggle = document.getElementById('accessibility-toggle');
    const accessibilityPanel = document.getElementById('accessibility-panel');
    const closeAccessibility = document.getElementById('close-accessibility');
    
    if (!accessibilityToggle || !accessibilityPanel) return;
    
    accessibilityToggle.addEventListener('click', function() {
        accessibilityPanel.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    });
    
    if (closeAccessibility) {
        closeAccessibility.addEventListener('click', function() {
            accessibilityPanel.classList.add('hidden');
            document.body.style.overflow = '';
        });
    }
    
    // إغلاق اللوحة عند النقر خارجها
    accessibilityPanel.addEventListener('click', function(e) {
        if (e.target === accessibilityPanel) {
            accessibilityPanel.classList.add('hidden');
            document.body.style.overflow = '';
        }
    });
}

/**
 * إعداد خيارات حجم الخط
 */
function setupFontSizeOptions() {
    const fontSizeButtons = document.querySelectorAll('.font-size-option');
    const htmlElement = document.documentElement;
    
    // التحقق من وجود الإعداد المحفوظ
    const savedFontSize = localStorage.getItem('accessibility_fontSize');
    if (savedFontSize) {
        applyFontSize(savedFontSize);
    }
    
    fontSizeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const size = this.getAttribute('data-size');
            applyFontSize(size);
            
            // حفظ الإعداد
            localStorage.setItem('accessibility_fontSize', size);
            
            // تحديث حالة الأزرار
            fontSizeButtons.forEach(btn => btn.classList.remove('bg-blue-500', 'text-white'));
            this.classList.add('bg-blue-500', 'text-white');
        });
    });
    
    function applyFontSize(size) {
        htmlElement.style.fontSize = `${size}em`;
    }
}

/**
 * إعداد خيارات التباين
 */
function setupContrastOptions() {
    const contrastButtons = document.querySelectorAll('.contrast-option');
    const bodyElement = document.body;
    
    // التحقق من وجود الإعداد المحفوظ
    const savedContrast = localStorage.getItem('accessibility_contrast');
    if (savedContrast === 'high') {
        applyHighContrast();
    }
    
    contrastButtons.forEach(button => {
        button.addEventListener('click', function() {
            const contrast = this.getAttribute('data-contrast');
            
            if (contrast === 'high') {
                applyHighContrast();
                localStorage.setItem('accessibility_contrast', 'high');
            } else {
                bodyElement.classList.remove('high-contrast');
                localStorage.setItem('accessibility_contrast', 'normal');
            }
            
            // تحديث حالة الأزرار
            contrastButtons.forEach(btn => btn.classList.remove('bg-blue-500', 'text-white'));
            this.classList.add('bg-blue-500', 'text-white');
        });
    });
    
    function applyHighContrast() {
        bodyElement.classList.add('high-contrast');
    }
}

/**
 * إعداد قارئ الشاشة
 */
function setupScreenReader() {
    const screenReaderToggle = document.getElementById('screen-reader-toggle');
    
    if (!screenReaderToggle) return;
    
    // التحقق من وجود الإعداد المحفوظ
    const savedScreenReader = localStorage.getItem('accessibility_screenReader');
    if (savedScreenReader === 'enabled') {
        screenReaderToggle.checked = true;
        enableScreenReader();
    }
    
    screenReaderToggle.addEventListener('change', function() {
        if (this.checked) {
            enableScreenReader();
            localStorage.setItem('accessibility_screenReader', 'enabled');
        } else {
            disableScreenReader();
            localStorage.setItem('accessibility_screenReader', 'disabled');
        }
    });
    
    function enableScreenReader() {
        document.body.classList.add('screen-reader-enabled');
        // إضافة علامات aria وتحسينات ميسرة أخرى
    }
    
    function disableScreenReader() {
        document.body.classList.remove('screen-reader-enabled');
    }
}

/**
 * إعداد التنقل بلوحة المفاتيح
 */
function setupKeyboardNavigation() {
    const keyboardNavToggle = document.getElementById('keyboard-nav-toggle');
    
    if (!keyboardNavToggle) return;
    
    // التحقق من وجود الإعداد المحفوظ
    const savedKeyboardNav = localStorage.getItem('accessibility_keyboardNav');
    if (savedKeyboardNav === 'disabled') {
        keyboardNavToggle.checked = false;
        disableKeyboardNavigation();
    }
    
    keyboardNavToggle.addEventListener('change', function() {
        if (this.checked) {
            enableKeyboardNavigation();
            localStorage.setItem('accessibility_keyboardNav', 'enabled');
        } else {
            disableKeyboardNavigation();
            localStorage.setItem('accessibility_keyboardNav', 'disabled');
        }
    });
    
    function enableKeyboardNavigation() {
        document.body.classList.remove('no-outline');
    }
    
    function disableKeyboardNavigation() {
        document.body.classList.add('no-outline');
    }
}

/**
 * إعادة ضبط إعدادات إمكانية الوصول
 */
document.getElementById('reset-accessibility')?.addEventListener('click', function() {
    // مسح الإعدادات المخزنة
    localStorage.removeItem('accessibility_fontSize');
    localStorage.removeItem('accessibility_contrast');
    localStorage.removeItem('accessibility_screenReader');
    localStorage.removeItem('accessibility_keyboardNav');
    
    // إعادة تحميل الصفحة
    window.location.reload();
});