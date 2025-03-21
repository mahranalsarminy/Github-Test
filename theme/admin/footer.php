<?php
/**
 * Admin Panel Footer Template
 *
 * @package WallPix
 * @version 1.0.0
 */

// Current date and time in UTC
$currentDateTime = '2025-03-18 10:17:55'; 
?>
    <!-- Footer -->
    <footer class="bg-white <?php echo $darkMode ? 'bg-gray-800 text-gray-400' : 'text-gray-600'; ?> py-4 mt-auto">
        <div class="container mx-auto px-6">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="mb-2 md:mb-0">
                    <p class="text-sm">
                        &copy; <?php echo date('Y'); ?> WallPix.Top. <?php echo $lang['all_rights_reserved'] ?? 'All rights reserved'; ?>.
                    </p>
                </div>
                <div class="flex space-x-4">
                    <a href="<?php echo $siteUrl; ?>/privacy-policy" class="text-sm hover:<?php echo $darkMode ? 'text-gray-300' : 'text-gray-800'; ?>">
                        <?php echo $lang['privacy_policy'] ?? 'Privacy Policy'; ?>
                    </a>
                    <a href="<?php echo $siteUrl; ?>/terms-of-service" class="text-sm hover:<?php echo $darkMode ? 'text-gray-300' : 'text-gray-800'; ?>">
                        <?php echo $lang['terms_of_service'] ?? 'Terms of Service'; ?>
                    </a>
                    <a href="<?php echo $siteUrl; ?>/contact" class="text-sm hover:<?php echo $darkMode ? 'text-gray-300' : 'text-gray-800'; ?>">
                        <?php echo $lang['contact'] ?? 'Contact'; ?>
                    </a>
                </div>
            </div>
        </div>
    </footer>
    
    <!-- JavaScript Footer - Load scripts at the end for better performance -->
    <script>
        // Dark mode toggle
        document.addEventListener('DOMContentLoaded', function() {
            const darkModeToggle = document.getElementById('darkModeToggle');
            
            if (darkModeToggle) {
                darkModeToggle.addEventListener('click', function() {
                    // Send AJAX request to toggle dark mode
                    fetch('<?php echo $adminUrl; ?>/ajax/toggle-dark-mode.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({ toggle: true }),
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Reload the page to apply the new theme
                            window.location.reload();
                        }
                    })
                    .catch(error => {
                        console.error('Error toggling dark mode:', error);
                    });
                });
            }
            
            // Language dropdown toggle
            const languageDropdown = document.getElementById('languageDropdown');
            const languageDropdownMenu = document.getElementById('languageDropdownMenu');
            
            if (languageDropdown && languageDropdownMenu) {
                languageDropdown.addEventListener('click', function(e) {
                    e.preventDefault();
                    languageDropdownMenu.classList.toggle('hidden');
                });
                
                // Close the dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!languageDropdown.contains(e.target)) {
                        languageDropdownMenu.classList.add('hidden');
                    }
                });
                
                // Language selection
                const languageItems = document.querySelectorAll('.language-item');
                languageItems.forEach(item => {
                    item.addEventListener('click', function(e) {
                        e.preventDefault();
                        const lang = this.getAttribute('data-lang');
                        
                        // Send AJAX request to change language
                        fetch('<?php echo $adminUrl; ?>/ajax/change-language.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                            },
                            body: JSON.stringify({ lang }),
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Reload the page with the new language
                                window.location.reload();
                            }
                        })
                        .catch(error => {
                            console.error('Error changing language:', error);
                        });
                    });
                });
            }
            
            // User dropdown toggle
            const userDropdown = document.getElementById('userDropdown');
            const userDropdownMenu = document.getElementById('userDropdownMenu');
            
            if (userDropdown && userDropdownMenu) {
                userDropdown.addEventListener('click', function(e) {
                    e.preventDefault();
                    userDropdownMenu.classList.toggle('hidden');
                });
                
                // Close the dropdown when clicking outside
                document.addEventListener('click', function(e) {
                    if (!userDropdown.contains(e.target)) {
                        userDropdownMenu.classList.add('hidden');
                    }
                });
            }
        });
    </script>
</body>
</html>