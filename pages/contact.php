<?php
require_once(__DIR__ . '/../includes/init.php');

// Fetch site settings
$stmt = $pdo->query("SELECT site_name, site_logo, dark_mode, language FROM site_settings WHERE id = 1");
$site_settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Fetch contact settings
$stmt = $pdo->query("SELECT contact_email, recaptcha_site_key, enable_attachments, max_file_size, allowed_file_types, required_fields FROM contact_settings WHERE id = 1");
$contact_settings = $stmt->fetch(PDO::FETCH_ASSOC);

// Parse required fields from JSON string
$required_fields = json_decode($contact_settings['required_fields'] ?? '[]', true);

// Process form submission
$success_message = '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get IP address for rate limiting and blocking
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? '';
    
    // Check if IP is blacklisted
    $stmt = $pdo->prepare("SELECT id FROM contact_blacklist WHERE ip_address = ? AND (expires_at IS NULL OR expires_at > NOW())");
    $stmt->execute([$ip_address]);
    
    if ($stmt->rowCount() > 0) {
        $error_message = "Sorry, you are not allowed to submit messages at this time.";
    } else {
        // Check for rate limiting
        $stmt = $pdo->prepare("SELECT attempt_count, last_attempt FROM contact_attempts WHERE ip_address = ?");
        $stmt->execute([$ip_address]);
        $attempt = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $can_submit = true;
        if ($attempt) {
            $last_attempt = strtotime($attempt['last_attempt']);
            $attempt_count = $attempt['attempt_count'];
            
            // Rate limit: more than 5 attempts in an hour
            if ($attempt_count >= 5 && (time() - $last_attempt) < 3600) {
                $can_submit = false;
                $error_message = "You've reached the maximum number of contact attempts. Please try again later.";
            }
        }
        
        if ($can_submit) {
            // Validate required fields
            $name = trim($_POST['name'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $subject = trim($_POST['subject'] ?? '');
            $message = trim($_POST['message'] ?? '');
            
            $field_errors = [];
            
            if (in_array('name', $required_fields) && empty($name)) {
                $field_errors[] = "Name is required.";
            }
            
            if (in_array('email', $required_fields) && empty($email)) {
                $field_errors[] = "Email is required.";
            } elseif (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $field_errors[] = "Please enter a valid email address.";
            }
            
            if (in_array('subject', $required_fields) && empty($subject)) {
                $field_errors[] = "Subject is required.";
            }
            
            if (in_array('message', $required_fields) && empty($message)) {
                $field_errors[] = "Message is required.";
            }
            
            // Validate reCAPTCHA if enabled
            if (!empty($contact_settings['recaptcha_site_key']) && isset($_POST['g-recaptcha-response'])) {
                // This would normally include server-side verification of the reCAPTCHA response
                // For a complete implementation, you would send the response to Google's API
            }
            
            if (empty($field_errors)) {
                // Insert the message
                $stmt = $pdo->prepare("INSERT INTO contact_messages (name, email, subject, message, ip_address, user_agent) 
                                      VALUES (?, ?, ?, ?, ?, ?)");
                $result = $stmt->execute([
                    $name, 
                    $email, 
                    $subject, 
                    $message,
                    $ip_address,
                    $_SERVER['HTTP_USER_AGENT'] ?? ''
                ]);
                
                if ($result) {
                    // Update or insert attempt tracking
                    if ($attempt) {
                        $stmt = $pdo->prepare("UPDATE contact_attempts SET attempt_count = attempt_count + 1, last_attempt = NOW() WHERE ip_address = ?");
                        $stmt->execute([$ip_address]);
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO contact_attempts (ip_address) VALUES (?)");
                        $stmt->execute([$ip_address]);
                    }
                    
                    // Send auto-reply if enabled
                    if ($contact_settings['enable_auto_reply']) {
                        // Fetch auto-reply template
                        $stmt = $pdo->prepare("SELECT content FROM contact_templates WHERE type = 'auto_reply' AND is_active = 1 AND id = ?");
                        $stmt->execute([$contact_settings['auto_reply_template_id']]);
                        $template = $stmt->fetch(PDO::FETCH_ASSOC);
                        
                        if ($template) {
                            // In a real implementation, you would send an email here
                            // using the template and replacing placeholders
                        }
                    }
                    
                    $success_message = "Your message has been sent successfully. We'll get back to you soon!";
                    
                    // Clear form fields after successful submission
                    $name = $email = $subject = $message = '';
                } else {
                    $error_message = "An error occurred while sending your message. Please try again.";
                }
            } else {
                $error_message = implode("<br>", $field_errors);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $site_settings['language']; ?>" dir="<?php echo $site_settings['language'] === 'ar' ? 'rtl' : 'ltr'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Us - <?php echo $site_settings['site_name']; ?></title>
    <link rel="stylesheet" href="/assets/css/styles.css">
    <link rel="stylesheet" href="/assets/css/dark-mode.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Cairo|Roboto&display=swap">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <?php if (!empty($contact_settings['recaptcha_site_key'])): ?>
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
    <?php endif; ?>
    <style>
        .contact-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: var(--bg-card);
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .contact-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border-color);
        }
        
        .contact-header h1 {
            font-size: 2.5rem;
            color: var(--text-primary);
            margin-bottom: 1rem;
        }
        
        .contact-header p {
            color: var(--text-secondary);
            line-height: 1.6;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .contact-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 3rem;
        }
        
        .contact-info {
            color: var(--text-primary);
        }
        
        .contact-info h2 {
            color: var(--accent-color);
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
        }
        
        .contact-info-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 1.5rem;
        }
        
        .contact-info-item i {
            font-size: 1.5rem;
            color: var(--accent-color);
            margin-right: 1rem;
            min-width: 30px;
            text-align: center;
        }
        
        .contact-info-item .content {
            flex-grow: 1;
        }
        
        .contact-info-item .content h3 {
            margin: 0 0 0.5rem;
            font-size: 1.2rem;
            color: var(--text-primary);
        }
        
        .contact-info-item .content p {
            margin: 0;
            color: var(--text-secondary);
        }
        
        .contact-form {
            color: var(--text-primary);
        }
        
        .contact-form h2 {
            color: var(--accent-color);
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-primary);
            font-weight: 500;
        }
        
        .form-group label .required {
            color: #e74c3c;
        }
        
        .form-control {
            width: 100%;
            padding: 0.75rem;
            font-size: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            background-color: var(--bg-input);
            color: var(--text-primary);
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--accent-color);
            outline: none;
            box-shadow: 0 0 0 3px rgba(var(--accent-color-rgb), 0.2);
        }
        
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
        }
        
        .btn-primary {
            display: inline-block;
            background-color: var(--accent-color);
            color: #fff;
            border: none;
            padding: 0.75rem 2rem;
            font-size: 1rem;
            font-weight: 500;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        
        .btn-primary:hover {
            background-color: var(--accent-hover);
        }
        
        .alert {
            padding: 1rem;
            margin-bottom: 1.5rem;
            border-radius: 4px;
        }
        
        .alert-success {
            background-color: rgba(46, 204, 113, 0.1);
            border: 1px solid #2ecc71;
            color: #2ecc71;
        }
        
        .alert-error {
            background-color: rgba(231, 76, 60, 0.1);
            border: 1px solid #e74c3c;
            color: #e74c3c;
        }
        
        /* Dark mode specific styles handled by CSS variables */
        
        .social-links {
            margin-top: 2rem;
        }
        
        .social-links a {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: var(--bg-secondary);
            color: var(--text-primary);
            border-radius: 50%;
            margin-right: 0.75rem;
            text-decoration: none;
            transition: background-color 0.3s, color 0.3s;
        }
        
        .social-links a:hover {
            background-color: var(--accent-color);
            color: #fff;
        }
        
        @media (max-width: 768px) {
            .contact-container {
                padding: 1.5rem;
                margin: 1rem;
            }
            
            .contact-layout {
                grid-template-columns: 1fr;
            }
            
            .contact-header h1 {
                font-size: 2rem;
            }
        }
    </style>
</head>
<body class="<?php echo $site_settings['dark_mode'] ? 'dark-mode' : 'light-mode'; ?>">
    <!-- Skip to main content link for accessibility -->
    <a href="#main-content" class="skip-link">Skip to main content</a>

    <!-- Header -->
    <?php include '../theme/homepage/header.php'; ?>

    <!-- Main Content -->
    <main id="main-content" role="main">
        <div class="contact-container">
            <div class="contact-header">
                <h1>Contact Us</h1>
                <p>We'd love to hear from you! Feel free to reach out using the form below or through our contact information.</p>
            </div>

            <div class="contact-layout">
                <div class="contact-info">
                    <h2>Get In Touch</h2>
                    
                    <div class="contact-info-item">
                        <i class="fas fa-envelope"></i>
                        <div class="content">
                            <h3>Email</h3>
                            <p><?php echo htmlspecialchars($contact_settings['contact_email'] ?? 'info@wallpix.top'); ?></p>
                        </div>
                    </div>
                    
                    <div class="contact-info-item">
                        <i class="fas fa-clock"></i>
                        <div class="content">
                            <h3>Response Time</h3>
                            <p>We typically respond within 24-48 hours</p>
                        </div>
                    </div>
                    
                    <?php
                    // Fetch site_settings for social links
                    $stmt = $pdo->query("SELECT facebook_url, twitter_url, instagram_url FROM site_settings WHERE id = 1");
                    $social = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    if (!empty($social['facebook_url']) || !empty($social['twitter_url']) || !empty($social['instagram_url'])):
                    ?>
                    <div class="social-links">
                        <?php if (!empty($social['facebook_url'])): ?>
                        <a href="<?php echo htmlspecialchars($social['facebook_url']); ?>" target="_blank" rel="noopener noreferrer" aria-label="Facebook">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($social['twitter_url'])): ?>
                        <a href="<?php echo htmlspecialchars($social['twitter_url']); ?>" target="_blank" rel="noopener noreferrer" aria-label="Twitter">
                                                        <i class="fab fa-twitter"></i>
                        </a>
                        <?php endif; ?>
                        
                        <?php if (!empty($social['instagram_url'])): ?>
                        <a href="<?php echo htmlspecialchars($social['instagram_url']); ?>" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="contact-form">
                    <h2>Send Us a Message</h2>
                    
                    <?php if (!empty($success_message)): ?>
                    <div class="alert alert-success" role="alert">
                        <?php echo $success_message; ?>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($error_message)): ?>
                    <div class="alert alert-error" role="alert">
                        <?php echo $error_message; ?>
                    </div>
                    <?php endif; ?>
                    
                    <form action="contact.php" method="post" id="contactForm">
                        <div class="form-group">
                            <label for="name">
                                Name <?php echo in_array('name', $required_fields) ? '<span class="required">*</span>' : ''; ?>
                            </label>
                            <input type="text" class="form-control" id="name" name="name" 
                                value="<?php echo htmlspecialchars($name ?? ''); ?>" 
                                <?php echo in_array('name', $required_fields) ? 'required' : ''; ?>>
                        </div>
                        
                        <div class="form-group">
                            <label for="email">
                                Email <?php echo in_array('email', $required_fields) ? '<span class="required">*</span>' : ''; ?>
                            </label>
                            <input type="email" class="form-control" id="email" name="email" 
                                value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                                <?php echo in_array('email', $required_fields) ? 'required' : ''; ?>>
                        </div>
                        
                        <div class="form-group">
                            <label for="subject">
                                Subject <?php echo in_array('subject', $required_fields) ? '<span class="required">*</span>' : ''; ?>
                            </label>
                            <input type="text" class="form-control" id="subject" name="subject" 
                                value="<?php echo htmlspecialchars($subject ?? ''); ?>" 
                                <?php echo in_array('subject', $required_fields) ? 'required' : ''; ?>>
                        </div>
                        
                        <div class="form-group">
                            <label for="message">
                                Message <?php echo in_array('message', $required_fields) ? '<span class="required">*</span>' : ''; ?>
                            </label>
                            <textarea class="form-control" id="message" name="message" 
                                <?php echo in_array('message', $required_fields) ? 'required' : ''; ?>><?php echo htmlspecialchars($message ?? ''); ?></textarea>
                        </div>
                        
                        <?php if (!empty($contact_settings['recaptcha_site_key'])): ?>
                        <div class="form-group">
                            <div class="g-recaptcha" data-sitekey="<?php echo htmlspecialchars($contact_settings['recaptcha_site_key']); ?>"></div>
                        </div>
                        <?php endif; ?>
                        
                        <button type="submit" class="btn-primary">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Footer -->
    <?php include '../theme/homepage/footer.php'; ?>

    <!-- Accessibility button -->
    <div id="accessibility-toggle" class="accessibility-button" aria-label="Accessibility options">
        <i class="fas fa-universal-access"></i>
    </div>

    <!-- Accessibility menu -->
    <?php include '../theme/homepage/accessibility.php'; ?>

    <!-- Scripts -->
    <script src="/assets/js/scripts.js"></script>
    <script src="/assets/js/accessibility.js"></script>
    
    <!-- Form validation script -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('contactForm');
        
        if (form) {
            form.addEventListener('submit', function(e) {
                const requiredFields = form.querySelectorAll('[required]');
                let isValid = true;
                
                requiredFields.forEach(function(field) {
                    if (!field.value.trim()) {
                        isValid = false;
                        field.classList.add('error');
                    } else {
                        field.classList.remove('error');
                    }
                });
                
                const emailField = form.querySelector('#email');
                if (emailField && emailField.value.trim() && !isValidEmail(emailField.value.trim())) {
                    isValid = false;
                    emailField.classList.add('error');
                }
                
                if (!isValid) {
                    e.preventDefault();
                }
            });
        }
        
        function isValidEmail(email) {
            const re = /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
            return re.test(String(email).toLowerCase());
        }
    });
    </script>
</body>
</html>