<?php
function route_request($uri, $method) {
    switch ($uri) {
        case '/api/featured-media':
            if ($method === 'GET') {
                require_once __DIR__ . '/controllers/MediaController.php';
                get_featured_media();
            }
            break;

        case '/api/search':
            if ($method === 'GET') {
                require_once __DIR__ . '/controllers/MediaController.php';
                search_media();
            }
            break;

        case '/api/register':
            if ($method === 'POST') {
                require_once __DIR__ . '/controllers/AuthController.php';
                register_user();
            }
            break;

        case '/api/login':
            if ($method === 'POST') {
                require_once __DIR__ . '/controllers/AuthController.php';
                login_user();
            }
            break;

        case preg_match('/^\/api\/media\/(\d+)$/', $uri, $matches):
            if ($method === 'GET') {
                require_once __DIR__ . '/controllers/MediaController.php';
                get_media_details($matches[1]);
            }
            break;

        case preg_match('/^\/api\/media\/(\d+)\/comment$/', $uri, $matches):
            if ($method === 'POST') {
                require_once __DIR__ . '/controllers/CommentController.php';
                add_comment($matches[1]);
            }
            break;

        case preg_match('/^\/api\/media\/(\d+)\/rate$/', $uri, $matches):
            if ($method === 'POST') {
                require_once __DIR__ . '/controllers/RatingController.php';
                rate_media($matches[1]);
            }
            break;

        case '/api/plans':
            if ($method === 'GET') {
                require_once __DIR__ . '/controllers/PlanController.php';
                get_subscription_plans();
            }
            break;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint not found']);
            break;
    }
}
?>