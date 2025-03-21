<div class="fixed w-64 bg-gray-800 text-white h-full flex flex-col">
    <div class="p-4">
        <h1 class="text-2xl font-bold">WallPix Admin</h1>
    </div>
    <nav class="mt-8 flex flex-col space-y-2">
        <a href="/admin/dashboard" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700">Dashboard</a>
        <a href="/admin/users.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700">Users</a>
        <a href="/admin/categories.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700">Categories</a>
        <a href="/admin/plans.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700">Plans</a>
        <a href="/admin/media.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700">Media</a>
        <a href="/admin/tags.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700">Tags</a>
        <a href="/admin/settings.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700">Settings</a>
                <div class="mt-8">
            <h2 class="text-lg font-semibold flex items-center cursor-pointer" onclick="toggleSubPages()">
                <span>Pages</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="ml-2 w-4 h-4 transition-transform duration-200 transform" id="arrow-icon" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                </svg>
            </h2>
            <div id="sub-pages" class="ml-4 hidden space-y-2">
                <a href="/admin/about.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700">About Us</a>
                <a href="/admin/features.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700">Features</a>
                <a href="/admin/plans.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700">Plans</a>
                <a href="/admin/privacy.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700">Privacy Policy</a>
                <a href="/admin/terms.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700">Terms & Conditions</a>
            </div>
        </div>
        <a href="/admin/contact.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-gray-700">Contact US</a>
        <a href="/admin/logout.php" class="block py-2.5 px-4 rounded transition duration-200 hover:bg-red-600 text-red-500">Logout</a>
    </nav>
</div>

<script>
    // دالة لفتح وإغلاق الفئات الفرعية عند النقر على Pages
    function toggleSubPages() {
        const subPages = document.getElementById('sub-pages');
        const arrowIcon = document.getElementById('arrow-icon');
        
        // إخفاء أو إظهار الفئات الفرعية
        subPages.classList.toggle('hidden');
        
        // تدوير السهم عند النقر
        arrowIcon.classList.toggle('transform rotate-180');
    }
</script>
