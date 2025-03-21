<!DOCTYPE html>
<html lang="en" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - WallPix</title>
    <link href="/admin/theme/css/style.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="/admin/theme/js/scripts.js" defer></script>
    <script>
        document.addEventListener('DOMContentLoaded', (event) => {
            const modeToggle = document.getElementById('mode-toggle');
            const body = document.body;

            if (localStorage.getItem('theme') === 'dark') {
                body.classList.add('dark');
                modeToggle.textContent = 'Light Mode';
            } else {
                body.classList.remove('dark');
                modeToggle.textContent = 'Dark Mode';
            }

            modeToggle.addEventListener('click', () => {
                body.classList.toggle('dark');
                if (body.classList.contains('dark')) {
                    localStorage.setItem('theme', 'dark');
                    modeToggle.textContent = 'Light Mode';
                } else {
                    localStorage.setItem('theme', 'light');
                    modeToggle.textContent = 'Dark Mode';
                }
            });
        });
    </script>
</head>
<body class="bg-gray-100 dark:bg-gray-900 dark:text-white">
<header class="bg-gray-800 text-white p-4 flex justify-between items-center">
    <div class="flex items-center">
        <img src="/admin/theme/images/admin-panel.png" alt="Admin Panel" class="w-10 h-10 mr-3">
        <h1 class="text-2xl font-bold">Admin Panel</h1>
    </div>
    <div class="flex items-center">
        <!-- Language Selector -->
        <select id="language-selector" class="bg-gray-700 text-white p-2 rounded mr-4">
            <option value="en">English</option>
            <option value="ar">العربية</option>
        </select>
        <!-- View Website Button -->
        <a href="../?lang=en" target="_blank" class="bg-blue-500 text-white p-2 rounded mr-4">View Website</a>
        <!-- Day/Night Mode Toggle -->
        <button id="mode-toggle" class="bg-gray-700 text-white p-2 rounded mr-4">Dark Mode</button>
        <!-- Member Picture with Dropdown -->
        <div class="relative">
            <img src="/admin/theme/images/member.png" alt="Member" class="w-10 h-10 rounded-full cursor-pointer" id="member-menu-toggle">
            <div id="member-menu" class="absolute right-0 mt-2 w-48 bg-white dark:bg-gray-800 rounded shadow-lg hidden">
                <a href="../profile.php" class="block px-4 py-2 text-gray-800 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-700">Profile</a>
                <a href="../logout.php" class="block px-4 py-2 text-gray-800 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-700">Logout</a>
            </div>
        </div>
    </div>
</header>
</body>
</html>
