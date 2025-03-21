<?php
// Function to hash passwords using bcrypt
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

// Function to verify passwords
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

// Function to generate a JWT token
function generate_jwt($payload) {
    $secret_key = getenv('JWT_SECRET');
    $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
    $base64_header = base64url_encode($header);
    $base64_payload = base64url_encode(json_encode($payload));
    $signature = hash_hmac('sha256', "$base64_header.$base64_payload", $secret_key, true);
    $base64_signature = base64url_encode($signature);
    return "$base64_header.$base64_payload.$base64_signature";
}

// Helper function for base64 URL encoding
function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

// Function to validate CSRF tokens
function validate_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Function to resize and compress images using Intervention Image
function process_image($file_path, $max_width = 1920, $quality = 80) {
    require_once __DIR__ . '/../vendor/autoload.php';
    $image = \Intervention\Image\ImageManagerStatic::make($file_path);
    $image->resize($max_width, null, function ($constraint) {
        $constraint->aspectRatio();
    });
    $image->save($file_path, $quality);
}

// Function to add a watermark to images
function add_watermark($file_path, $watermark_text = 'WallPix') {
    require_once __DIR__ . '/../vendor/autoload.php';
    $image = \Intervention\Image\ImageManagerStatic::make($file_path);
    $image->text($watermark_text, $image->width() - 20, $image->height() - 20, function ($font) {
        $font->file(__DIR__ . '/../assets/fonts/arial.ttf');
        $font->size(24);
        $font->color('#ffffff');
        $font->align('right');
        $font->valign('bottom');
    });
    $image->save($file_path);
}

// Function to log errors
function log_error($message) {
    $log_file = __DIR__ . '/../logs/error.log';
    file_put_contents($log_file, '[' . date('Y-m-d H:i:s') . '] ' . $message . PHP_EOL, FILE_APPEND);
}