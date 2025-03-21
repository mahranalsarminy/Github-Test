<?php
function generate_jwt($payload) {
    $secret_key = getenv('JWT_SECRET');
    $header = json_encode(['alg' => 'HS256', 'typ' => 'JWT']);
    $base64_header = base64url_encode($header);
    $base64_payload = base64url_encode(json_encode($payload));
    $signature = hash_hmac('sha256', "$base64_header.$base64_payload", $secret_key, true);
    $base64_signature = base64url_encode($signature);
    return "$base64_header.$base64_payload.$base64_signature";
}

function validate_jwt($token) {
    $secret_key = getenv('JWT_SECRET');
    list($header, $payload, $signature) = explode('.', $token);
    $valid_signature = base64url_encode(hash_hmac('sha256', "$header.$payload", $secret_key, true));
    if ($signature !== $valid_signature) {
        return false;
    }
    return json_decode(base64url_decode($payload), true);
}

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    return base64_decode(str_pad(strtr($data, '-_', '+/'), strlen($data) % 4, '=', STR_PAD_RIGHT));
}
?>