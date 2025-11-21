<?php
error_reporting(E_ALL);
ini_set('display_errors', '1');

include_once __DIR__ . '/../include/db_config.php';
include_once __DIR__ . '/../include/Database.php';

$db = new Database($conn);

header('Content-Type: application/json'); // Set the content type to JSON

$response = ['status' => 'failure'];

// Function to generate a secure token
function generate_token(): string {
    return bin2hex(random_bytes(16)); // 32 characters long
}

// Validate username input from form
function validate_username(string $username, int $max_len, array &$response): bool {
    if (empty($username)) {
        error_log("Error: username is empty.");
        return false;
    }
    // Check username contains only alphabets, numbers, and underscores
    if (!preg_match("/^[a-zA-Z0-9_]*$/", $username)) {
        error_log("Error: username contains invalid characters.");
        return false;
    }
    if (strlen($username) > $max_len) {
        error_log("Error: username is too long: " . strlen($username));
        return false;
    }
    return true;
}

// Validate password
function validate_password(string $password, int $max_len = 24, array &$response): bool {
    if (empty($password)) {
        error_log("Error: password is empty.");
        return false;
    }
    if (!preg_match("/^[a-zA-Z0-9_-]*$/", $password)) {
        error_log("Error: password contains invalid characters.");
        return false;
    }
    if (strlen($password) > $max_len) {
        error_log("Error: password is too long: " . strlen($password));
        return false;
    }
    return true;
}

// Store the generated login token in the login_info table
function store_token(Database $db, string $username, string $token, array &$response): void {
    try {
        $ok = $db->execute(
            "UPDATE login_info SET token = $1 WHERE username = $2",
            [$token, $username]
        );

        if (!$ok) {
            $response['error'] = "Failed to update token for $username.";
        }
    } catch (Exception $e) {
        $response['error'] = "store_token exception: " . $e->getMessage();
    }
}

// Get the raw POST data (JSON input)
$data = json_decode(file_get_contents('php://input'), true);

$username = "";
$valid_username = false;
if (isset($data['username'])) {
    $username = $data['username'];
    // Check username validity AND username not already taken
    $valid_username = validate_username($username, 14, $response) && !$db->exists('login_info', 'username', $username);
}

$password = "";
$valid_password = false;
if (isset($data['password'])) {
    $password = $data['password'];
    $valid_password = validate_password($password, 24, $response);
}

if ($valid_username && $valid_password) {
    try {
        // Hash the password securely
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // Insert username + hashed password into login_info
        $ok = $db->execute(
            "INSERT INTO login_info (username, password) VALUES ($1, $2)",
            [$username, $hashed_password]
        );

        if ($ok) {
            $response['status'] = 'success';
            $response['token'] = generate_token();
            store_token($db, $username, $response['token'], $response);
        } else {
            $response['error'] = "Failed to insert new user.";
        }
    } catch (Exception $e) {
        $response['error'] = "Insert exception: " . $e->getMessage();
    }
}

echo json_encode($response);
