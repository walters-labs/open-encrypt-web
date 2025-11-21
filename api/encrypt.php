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

/* 2. Rate limit */
$limit = fetch_rate_limit($db, $api_key);
check_rate_limit($db, $api_key, $limit);

/* 3. Parse JSON */
$data = json_decode(file_get_contents('php://input'), true);

$public_key = $data['public_key'] ?? '';
$plaintext = $data['plaintext'] ?? '';
$method    = $data['method'] ?? 'ring_lwe';

if (!$public_key || !$plaintext) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing public_key or plaintext']);
    exit;
}

if (!valid_public_key($public_key, $method)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid public key']);
    exit;
}

try {
    $ciphertext = encrypt_message($public_key, $plaintext, $method);

    echo json_encode([
        'status'     => 'success',
        'method'     => $method,
        'ciphertext' => $ciphertext,
    ]);

} catch (Exception $e) {
    error_log("encrypt error: " . $e->getMessage());

    $status_code = (strpos($e->getMessage(), 'Invalid') !== false) ? 400 : 500;
    http_response_code($status_code);
    
    echo json_encode(['error' => 'Encryption failed. Please check your method and public key.']);
    exit;
}
