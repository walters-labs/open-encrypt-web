<?php
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$path   = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Strip /api/v1 prefix
$path = preg_replace('#^/api/v1#', '', $path);

// Only allow POST for certain endpoints, GET for health
if ($method === 'POST') {
    switch ($path) {
        case '/keys':
            include __DIR__ . '/../keygen.php';
            break;

        case '/encrypt':
            include __DIR__ . '/../encrypt.php';
            break;

        case '/decrypt':
            include __DIR__ . '/../decrypt.php';
            break;

        default:
            http_response_code(404);
            echo json_encode(['error' => 'Not Found']);
    }
} elseif ($method === 'GET' && $path === '/health') {
    include __DIR__ . '/../health.php';  // <-- include the real health check script here
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
}
