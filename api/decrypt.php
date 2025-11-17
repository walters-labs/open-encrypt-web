<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/var/www/open-encrypt.com/html/error.log');
error_reporting(E_ALL);

header('Content-Type: application/json');

include_once(__DIR__ . '/../include/db_config.php');
include_once(__DIR__ . '/../include/Database.php');
include_once(__DIR__ . '/../include/utils.php');       // contains get_db()
include_once(__DIR__ . '/../include/encryption.php');  // crypto functions
include_once(__DIR__ . '/utils.php');                  // API-level utilities

$db = get_db();

/* 1. Validate API key */
$api_key = validate_api_key($db);

/* 2. Rate limit check */
$limit = fetch_rate_limit($db, $api_key);
check_rate_limit($db, $api_key, $limit);

/* 3. Parse JSON */
$data = json_decode(file_get_contents('php://input'), true);

$secretKey  = $data['secret_key'] ?? '';
$ciphertext = $data['ciphertext'] ?? '';
$method     = $data['method'] ?? 'ring_lwe';

if (!$secretKey || !$ciphertext) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing secret_key or ciphertext']);
    exit;
}

if (!valid_secret_key($secretKey, $method)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid secret key']);
    exit;
}

try {
    $plaintext = decrypt_message($secretKey, $ciphertext, $method);

    echo json_encode([
        'status'    => 'success',
        'method'    => $method,
        'plaintext' => $plaintext,
    ]);

} catch (Exception $e) {
    error_log("decrypt error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'Decryption failed']);
}