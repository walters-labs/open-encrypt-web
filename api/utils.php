<?php
// open-encrypt/utils.php

include_once __DIR__ . '/../include/db_config.php';
include_once __DIR__ . '/../include/Database.php';

function get_db() {
    global $conn;
    static $db = null;
    if ($db === null) {
        $db = new Database($conn);
    }
    return $db;
}

// Validate admin API key (must have admin = TRUE and active = TRUE)
function validate_admin_api_key(Database $db) {
    $api_key = null;
    if (!empty($_SERVER['HTTP_X_API_KEY'])) {
        $api_key = $_SERVER['HTTP_X_API_KEY'];
    } else if (isset($_GET['api_key'])) {
        $api_key = $_GET['api_key'];
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Missing API key']);
        exit;
    }

    // Query admin and active flags
    $row = $db->fetchOne(
        "SELECT admin, active FROM api_keys WHERE api_key = $1",
        [$api_key]
    );

    if (!$row || !$row['active']) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid or inactive API key']);
        exit;
    }

    if (!(bool)$row['admin']) {
        http_response_code(403);
        echo json_encode(['error' => 'Admin privileges required']);
        exit;
    }

    return $api_key;
}

// Validate normal API key (active = TRUE)
function validate_api_key(Database $db) {
    $api_key = null;
    if (!empty($_SERVER['HTTP_X_API_KEY'])) {
        $api_key = $_SERVER['HTTP_X_API_KEY'];
    } else if (isset($_GET['api_key'])) {
        $api_key = $_GET['api_key'];
    } else {
        http_response_code(401);
        echo json_encode(['error' => 'Missing API key']);
        exit;
    }

    $row = $db->fetchOne(
        "SELECT active FROM api_keys WHERE api_key = $1",
        [$api_key]
    );

    if (!$row || !$row['active']) {
        http_response_code(403);
        echo json_encode(['error' => 'Invalid or inactive API key']);
        exit;
    }

    return $api_key;
}

// Fetch rate limit for the key (requests per minute or similar)
function fetch_rate_limit(Database $db, string $api_key) {
    $row = $db->fetchOne(
        "SELECT rate_limit FROM api_keys WHERE api_key = $1",
        [$api_key]
    );

    if (!$row) {
        http_response_code(403);
        echo json_encode(['error' => 'API key not found']);
        exit;
    }

    return (int)$row['rate_limit'];
}

// Rate limit check and increment usage, returns true if allowed, else 429
function check_rate_limit(Database $db, string $api_key, int $limit) {
    $time_window = date('Y-m-d H:i:00');  // minute resolution

    // PostgreSQL UPSERT syntax (assuming unique constraint on (api_key, time_window))
    $sql = "
        INSERT INTO api_key_usage (api_key, time_window, count)
        VALUES ($1, $2, 1)
        ON CONFLICT (api_key, time_window) DO UPDATE
        SET count = api_key_usage.count + 1;
    ";

    $ok = $db->execute($sql, [$api_key, $time_window]);

    if (!$ok) {
        http_response_code(500);
        echo json_encode(['error' => 'Failed to update rate limit']);
        exit;
    }

    // Now fetch the current count
    $row = $db->fetchOne(
        "SELECT count FROM api_key_usage WHERE api_key = $1 AND time_window = $2",
        [$api_key, $time_window]
    );

    $current_count = (int)($row['count'] ?? 0);

    if ($current_count > $limit) {
        http_response_code(429);
        echo json_encode(['error' => 'Rate limit exceeded']);
        exit;
    }

    return true;
}

// Helper to parse JSON body
function get_json_input() {
    $raw = file_get_contents("php://input");
    return json_decode($raw, true);
}
