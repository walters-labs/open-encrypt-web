<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/var/www/open-encrypt.com/html/error.log');
error_reporting(E_ALL);

header('Content-Type: application/json');

include_once(__DIR__ . '/../include/db_config.php');
include_once(__DIR__ . '/../include/Database.php');
include_once(__DIR__ . '/../include/utils.php');       // contains get_db()
include_once(__DIR__ . '/utils.php');                  // API-level utilities

try {
    $db = get_db();

    /* 1. Validate API key */
    $api_key = validate_api_key($db);

    /* 2. Rate limit */
    $limit = fetch_rate_limit($db, $api_key);
    check_rate_limit($db, $api_key, $limit);

    // Lightweight DB check using fetchOne
    $result = $db->fetchOne("SELECT 1", [], "");
    if ($result === false) {
        throw new Exception("Database query failed");
    }

    echo json_encode([
        'status'    => 'ok',
        'timestamp' => time(),
        // 'api_key_valid' => ($api_key !== null), // uncomment if validating API key
        'message'   => 'API is healthy',
    ]);
} catch (Exception $e) {
    error_log('health check error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status'  => 'error',
        'message' => 'Health check failed',
    ]);
}
