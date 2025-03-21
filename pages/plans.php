<?php 
// Include the header file
require_once __DIR__ . '/../templates/header.php';
?>

<main class="min-h-screen flex flex-col justify-between py-8">
    <section class="container mx-auto mt-8 px-4">
        <h1 class="text-3xl font-semibold text-center text-gray-800"><?php echo $lang['plans']; ?></h1>
        <p class="text-center mt-4 text-gray-600">Choose the plan that suits your needs:</p>

        <section class="mt-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Free Plan -->
                <div class="bg-white p-6 rounded-lg shadow-lg flex flex-col items-center transition-all hover:shadow-2xl hover:bg-gray-100">
                    <div class="bg-blue-500 text-white p-4 rounded-full mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800">Free Plan</h3>
                    <p class="text-2xl font-bold text-gray-800">€0/month</p>
                    <ul class="mt-4 text-gray-600 space-y-4">
                        <li class="border-b border-gray-300 pb-2">50 Daily Image Downloads</li>
                        <li class="border-b border-gray-300 pb-2">50 Daily Image Downloads</li>
                        <li class="border-b border-gray-300 pb-2">50 Daily Image Downloads</li>
                        <li class="border-b border-gray-300 pb-2">50 Daily Image Downloads</li>
                        <li class="border-b border-gray-300 pb-2">50 Daily Image Downloads</li>
                        <li class="border-b border-gray-300 pb-2">50 Daily Image Downloads</li>
                        <li class="border-b border-gray-300 pb-2">50 Daily Image Downloads</li>
                        <li class="border-b border-gray-300 pb-2">50 Daily Image Downloads</li>
                        <li class="border-b border-gray-300 pb-2">50 Daily Video Downloads</li>
                        <li>Watermark Disabled</li>
                    </ul>
                    <button class="mt-6 bg-blue-500 text-white py-2 px-6 rounded-lg hover:bg-blue-600">Subscribe</button>
                </div>

                <!-- Regular Plan -->
                <div class="bg-white p-6 rounded-lg shadow-lg flex flex-col items-center transition-all hover:shadow-2xl hover:bg-gray-100">
                    <div class="bg-green-500 text-white p-4 rounded-full mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800">Regular Plan</h3>
                    <p class="text-2xl font-bold text-gray-800">€5/month</p>
                    <ul class="mt-4 text-gray-600 space-y-4">
                        <li class="border-b border-gray-300 pb-2">50 Daily Image Downloads</li>
                        <li class="border-b border-gray-300 pb-2">50 Daily Image Downloads</li>
                        <li class="border-b border-gray-300 pb-2">50 Daily Image Downloads</li>
                        <li class="border-b border-gray-300 pb-2">50 Daily Image Downloads</li>
                        <li class="border-b border-gray-300 pb-2">50 Daily Image Downloads</li>
                        <li class="border-b border-gray-300 pb-2">50 Daily Image Downloads</li>
                        <li class="border-b border-gray-300 pb-2">50 Daily Image Downloads</li>
                        <li class="border-b border-gray-300 pb-2">50 Daily Image Downloads</li>
                        <li class="border-b border-gray-300 pb-2">50 Daily Video Downloads</li>
                        <li>Watermark Disabled</li>
                    </ul>
                    <button class="mt-6 bg-green-500 text-white py-2 px-6 rounded-lg hover:bg-green-600">Subscribe</button>
                </div>

                <!-- Premium Plan -->
                <div class="bg-white p-6 rounded-lg shadow-lg flex flex-col items-center transition-all hover:shadow-2xl hover:bg-gray-100">
                    <div class="bg-yellow-500 text-white p-4 rounded-full mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-800">Premium Plan</h3>
                    <p class="text-2xl font-bold text-gray-800">€10/month</p>
                    <ul class="mt-4 text-gray-600 space-y-4">
                        <li class="border-b border-gray-300 pb-2">50 Daily Image Downloads</li>
                        <li class="border-b border-gray-300 pb-2">50 Daily Image Downloads</li>
                        <li class="border-b border-gray-300 pb-2">50 Daily Image Downloads</li>
                        <li class="border-b border-gray-300 pb-2">50 Daily Image Downloads</li>
                        <li class="border-b border-gray-300 pb-2">50 Daily Image Downloads</li>
                        <li class="border-b border-gray-300 pb-2">50 Daily Image Downloads</li>
                        <li class="border-b border-gray-300 pb-2">50 Daily Image Downloads</li>
                        <li class="border-b border-gray-300 pb-2">50 Daily Image Downloads</li>
                        <li class="border-b border-gray-300 pb-2">50 Daily Video Downloads</li>
                        <li>Watermark Disabled</li>
                    </ul>
                    <button class="mt-6 bg-yellow-500 text-white py-2 px-6 rounded-lg hover:bg-yellow-600">Subscribe</button>
                </div>
            </div>
        </section>
    </section>
</main>

<!-- Footer -->
<footer class="bg-gray-800 text-white py-4 mt-8">
    <div class="container mx-auto text-center">
        <?php require_once __DIR__ . '/../templates/footer.php'; ?> 
    </div>
</footer>