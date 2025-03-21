<div id="accessibility-panel" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden">
    <div class="absolute right-0 top-0 h-full bg-white dark:bg-gray-900 w-80 shadow-lg transform transition-transform p-5">
        <div class="flex justify-between items-center mb-6 border-b pb-4">
            <h3 class="text-xl font-bold text-gray-800 dark:text-white">Accessibility Options</h3>
            <button id="close-accessibility" class="text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-white transition">
                <i class="fas fa-times"></i>
            </button>
        </div>
        
        <div class="space-y-6">
            <!-- خيارات حجم النص -->
            <div>
                <h4 class="font-medium text-gray-800 dark:text-white mb-2">Text Size</h4>
                <div class="flex space-x-3">
                    <button class="font-size-option px-3 py-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded text-sm" data-size="0.8">A-</button>
                    <button class="font-size-option px-3 py-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded text-base" data-size="1">A</button>
                    <button class="font-size-option px-3 py-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded text-lg" data-size="1.2">A+</button>
                    <button class="font-size-option px-3 py-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded text-xl" data-size="1.4">A++</button>
                </div>
            </div>
            
            <!-- خيارات التباين -->
            <div>
                <h4 class="font-medium text-gray-800 dark:text-white mb-2">Contrast</h4>
                <div class="flex space-x-3">
                    <button class="contrast-option px-4 py-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded" data-contrast="normal">Normal</button>
                    <button class="contrast-option px-4 py-2 bg-gray-100 dark:bg-gray-800 hover:bg-gray-200 dark:hover:bg-gray-700 rounded" data-contrast="high">High Contrast</button>
                </div>
            </div>
            
            <!-- خيارات تحويل النص إلى كلام -->
            <div>
                <h4 class="font-medium text-gray-800 dark:text-white mb-2">Screen Reader</h4>
                <div class="flex items-center">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="screen-reader-toggle" class="sr-only peer">
                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300">Enable Screen Reader</span>
                    </label>
                </div>
            </div>
            
            <!-- خيارات التنقل باستخدام لوحة المفاتيح -->
            <div>
                <h4 class="font-medium text-gray-800 dark:text-white mb-2">Keyboard Navigation</h4>
                <div class="flex items-center">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" id="keyboard-nav-toggle" class="sr-only peer" checked>
                        <div class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300">Enable Keyboard Navigation</span>
                    </label>
                </div>
                <p class="text-gray-600 dark:text-gray-400 text-sm mt-2">
                    Press Tab to navigate between elements and Enter to select.
                </p>
            </div>
            
            <!-- زر إعادة الضبط -->
            <div class="pt-4 border-t border-gray-200 dark:border-gray-700">
                <button id="reset-accessibility" class="w-full py-2 px-4 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded text-gray-800 dark:text-white transition">
                    Reset All Settings
                </button>
            </div>
        </div>
    </div>
</div>