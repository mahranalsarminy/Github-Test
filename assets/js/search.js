/**
 * Scripts for Search Box
 */
document.addEventListener('DOMContentLoaded', function() {
    setupSearchBox();
});

/**
 * إعداد صندوق البحث الذكي
 */
function setupSearchBox() {
    const searchInput = document.getElementById('search-input');
    const suggestionsDiv = document.getElementById('search-suggestions');
    let debounceTimer;
    
    if (!searchInput || !suggestionsDiv) return;
    
    // معالجة الإدخال في حقل البحث
    searchInput.addEventListener('input', function() {
        const query = searchInput.value.trim();
        
        // إلغاء المؤقت السابق وإنشاء مؤقت جديد
        clearTimeout(debounceTimer);
        
        if (query.length > 1) {
            debounceTimer = setTimeout(() => {
                fetchSearchSuggestions(query, suggestionsDiv);
            }, 300);
        } else {
            suggestionsDiv.innerHTML = '';
            suggestionsDiv.classList.add('hidden');
        }
    });
    
    // إخفاء الاقتراحات عند النقر خارجها
    document.addEventListener('click', function(e) {
        if (e.target !== searchInput && !suggestionsDiv.contains(e.target)) {
            suggestionsDiv.classList.add('hidden');
        }
    });
    
    // التنقل بين الاقتراحات باستخدام لوحة المفاتيح
    searchInput.addEventListener('keydown', function(e) {
        const suggestions = suggestionsDiv.querySelectorAll('a');
        
        if (suggestions.length === 0) return;
        
        if (e.key === 'ArrowDown' || e.key === 'ArrowUp') {
            e.preventDefault();
            
            const current = suggestionsDiv.querySelector('.bg-gray-100') || null;
            let next;
            
            if (e.key === 'ArrowDown') {
                next = current ? current.nextElementSibling : suggestions[0];
                if (!next) next = suggestions[0];
            } else {
                next = current ? current.previousElementSibling : suggestions[suggestions.length - 1];
                if (!next) next = suggestions[suggestions.length - 1];
            }
            
            if (current) current.classList.remove('bg-gray-100', 'dark:bg-gray-700');
            next.classList.add('bg-gray-100', 'dark:bg-gray-700');
            searchInput.value = next.getAttribute('data-keyword');
        }
        
        if (e.key === 'Enter') {
            const selected = suggestionsDiv.querySelector('.bg-gray-100');
            if (selected) {
                e.preventDefault();
                selected.click();
            }
        }
    });
}

/**
 * جلب اقتراحات البحث من الخادم
 */
function fetchSearchSuggestions(query, suggestionsDiv) {
    fetch(`/ajax/search-suggestions.php?q=${encodeURIComponent(query)}`)
        .then(response => response.json())
        .then(data => {
            // تفريغ محتوى القائمة
            suggestionsDiv.innerHTML = '';
            
            if (data.suggestions && data.suggestions.length > 0) {
                // إنشاء عناصر الاقتراحات
                data.suggestions.forEach(item => {
                    const link = document.createElement('a');
                    link.href = `/search.php?q=${encodeURIComponent(item.keyword)}`;
                    link.setAttribute('data-keyword', item.keyword);
                    link.className = 'block px-4 py-2 text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700';
                    link.textContent = item.keyword;
                    
                    suggestionsDiv.appendChild(link);
                });
                
                // إظهار قائمة الاقتراحات
                suggestionsDiv.classList.remove('hidden');
            } else {
                suggestionsDiv.classList.add('hidden');
            }
        })
        .catch(error => {
            console.error('Error fetching search suggestions:', error);
            suggestionsDiv.classList.add('hidden');
        });
}

/**
 * حفظ البحث في قاعدة البيانات
 */
function saveSearchQuery(query) {
    if (!query.trim()) return;
    
    fetch('/ajax/save-search.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `query=${encodeURIComponent(query)}`
    }).catch(error => {
        console.error('Error saving search query:', error);
    });
}