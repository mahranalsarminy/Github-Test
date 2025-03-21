// Toggle dark mode
document.getElementById('mode-toggle').addEventListener('click', function() {
    document.body.classList.toggle('dark-mode');
    this.textContent = document.body.classList.contains('dark-mode') ? 'Day Mode' : 'Dark Mode';
});

// Show/hide member menu
document.getElementById('member-menu-toggle').addEventListener('click', function() {
    document.getElementById('member-menu').classList.toggle('hidden');
});

// Change language
document.getElementById('language-selector').addEventListener('change', function() {
    if (this.value === 'ar') {
        document.documentElement.setAttribute('dir', 'rtl');
        document.body.classList.add('rtl');
    } else {
        document.documentElement.setAttribute('dir', 'ltr');
        document.body.classList.remove('rtl');
    }
});