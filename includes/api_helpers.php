<?php
// Function to send JSON response
function send_json_response($data, $status_code = 200) {
    http_response_code($status_code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Function to validate API requests
function validate_api_request($required_fields, $input_data) {
    $missing_fields = [];
    foreach ($required_fields as $field) {
        if (!isset($input_data[$field]) || empty($input_data[$field])) {
            $missing_fields[] = $field;
        }
    }
    if (!empty($missing_fields)) {
        send_json_response(['error' => 'Missing required fields: ' . implode(', ', $missing_fields)], 400);
    }
}