<?php
function validate_request($required_fields) {
    $data = json_decode(file_get_contents('php://input'), true);
    foreach ($required_fields as $field) {
        if (!isset($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "$field is required"]);
            exit;
        }
    }
}
?>