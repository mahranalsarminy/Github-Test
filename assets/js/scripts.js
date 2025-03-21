/**
 * Main Scripts for WallPix
 */
document.addEventListener('DOMContentLoaded', function() {
    // تبديل الوضع المظلم/الفاتح
    setupThemeToggle();
    
    // إعداد قائمة الجوال
    setupMobileMenu();
    
    // إعداد أزرار التمرير للفئات
    setupCategoryScrolling();
    
    // إعداد زر العودة للأعلى
    setupBackToTop();
    
    // ترتيب عناصر التنقل بلوحة المفاتيح
    setupKeyboardNavigation();
});

/**
 * إعداد زر تبديل المظهر (مظلم/فاتح)
 */
function setupThemeToggle() {
    const themeToggle = document.getElementById('theme-toggle');
    if (!themeToggle) return;
    
    // التأكد من أن الوضع الذي كان مفعلًا في الجلسة يتم تطبيقه
    const currentTheme = localStorage.getItem('theme') || 'light'; // استرجاع التفضيل من الـ localStorage
    if (currentTheme === 'dark') {
        document.body.classList.add('dark-mode');
    }

    themeToggle.addEventListener('click', function() {
        document.body.classList.toggle('dark-mode');
        
        // تحديث أيقونة الزر
        const icon = themeToggle.querySelector('i');
        if (icon) {
            if (document.body.classList.contains('dark-mode')) {
                icon.classList.remove('fa-moon');
                icon.classList.add('fa-sun');
            } else {
                icon.classList.remove('fa-sun');
                icon.classList.add('fa-moon');
            }
        }
        
        // حفظ تفضيل المظهر في الـ localStorage
        const theme = document.body.classList.contains('dark-mode') ? 'dark' : 'light';
        saveThemePreference(theme);
    });
}

/**
 * حفظ تفضيل المظهر
 */
function saveThemePreference(theme) {
    localStorage.setItem('theme', theme); // حفظ التفضيل في localStorage بدلاً من إرسال طلب إلى السيرفر
}

/**
 * إعداد قائمة الجوال
 */
function setupMobileMenu() {
    const menuToggle = document.getElementById('mobile-menu-toggle');
    const closeMenu = document.getElementById('close-mobile-menu');
    const mobileMenu = document.getElementById('mobile-menu');
    
    if (!menuToggle || !mobileMenu) return;
    
    menuToggle.addEventListener('click', function() {
        mobileMenu.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
    });
    
    if (closeMenu) {
        closeMenu.addEventListener('click', function() {
            mobileMenu.classList.add('hidden');
            document.body.style.overflow = '';
        });
    }
    
    // إغلاق القائمة عند النقر خارجها
    mobileMenu.addEventListener('click', function(e) {
        if (e.target === mobileMenu) {
            mobileMenu.classList.add('hidden');
            document.body.style.overflow = '';
        }
    });
}

/**
 * إعداد أزرار التمرير في قسم الفئات
 */
function setupCategoryScrolling() {
    const scrollLeftBtn = document.getElementById('scrollLeft');
    const scrollRightBtn = document.getElementById('scrollRight');
    const container = document.querySelector('.categories-container');
    
    if (!scrollLeftBtn || !scrollRightBtn || !container) return;
    
    scrollLeftBtn.addEventListener('click', function() {
        container.scrollBy({ left: -300, behavior: 'smooth' });
    });
    
    scrollRightBtn.addEventListener('click', function() {
        container.scrollBy({ left: 300, behavior: 'smooth' });
    });
}

/**
 * إعداد زر العودة للأعلى
 */
function setupBackToTop() {
    const backToTop = document.getElementById('back-to-top');
    if (!backToTop) return;
    
    backToTop.addEventListener('click', function(e) {
        e.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
    
    // إظهار/إخفاء الزر بناءً على موضع التمرير
    window.addEventListener('scroll', function() {
        if (window.pageYOffset > 300) {
            backToTop.classList.add('visible');
        } else {
            backToTop.classList.remove('visible');
        }
    });
}

/**
 * إعداد التنقل بلوحة المفاتيح
 */
function setupKeyboardNavigation() {
    // يتم تفعيله افتراضيًا عن طريق HTML
}
