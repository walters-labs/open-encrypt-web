<?php
ini_set('display_errors', 0);  
ini_set('log_errors', 1);      
ini_set('error_log', '/var/www/open-encrypt.com/html/error.log');  
error_reporting(E_ALL);        

include_once __DIR__ . '/../include/db_config.php';
include_once __DIR__ . '/../include/Database.php';

$db = new Database($conn);

header('Content-Type: application/json'); 

$response = [];

// Get the raw POST data (JSON input)
$data = json_decode(file_get_contents('php://input'), true);

if ($data) {
    $username = $data['username'] ?? 'unknown';
    $action   = $data['action'] ?? 'none';
    error_log("POST received for user '{$username}', action '{$action}'.");
} else {
    error_log("Empty or invalid JSON in POST.");
}

$response['status'] = 'failure';

// Fetch the token from the database and verify it matches the given token
function verify_token(Database $db, string $username, string $token): bool {
    error_log("running verify_token for user: " . $username);
    try {
        $stored_token = $db->fetchOne(
            "SELECT token FROM login_info WHERE username = $1",
            [$username]
        );

        if ($stored_token === null) {
            error_log("stored token is null for user: " . $username);
            return false;
        }

        return ($stored_token['token'] ?? null) === $token;
    } catch (Exception $e) {
        error_log("verify_token exception: " . $e->getMessage());
        return false;
    }
}

// decrypt a message using the secret key
function decrypt_message(string $secret_key, string $ciphertext, string $encryption_method = "ring_lwe") {
    error_log("running decrypt_message with method: " . $encryption_method);
    $binary_path = "/var/www/open-encrypt.com/html/bin/";
    $command = escapeshellcmd(
        $binary_path
        . ($encryption_method === "ring_lwe" ? "ring-lwe-v0.1.8" : "module-lwe-v0.1.5")
        . " decrypt "
        . "--secret "
        . escapeshellarg(trim($secret_key))
        . " "
        . escapeshellarg(trim($ciphertext))
    ) . " 2>&1";
    return shell_exec($command);
}

// Encrypt a message using the given public key
function encrypt_message(string $public_key, string $plaintext, string $encryption_method = "ring_lwe") {
    error_log("running encrypt_message with method: " . $encryption_method);
    $binary_path = "/var/www/open-encrypt.com/html/bin/";
    $binary = ($encryption_method === "ring_lwe" ? "ring-lwe-v0.1.8" : "module-lwe-v0.1.5");
    $binary_full = $binary_path . $binary;

    if ($encryption_method === "ring_lwe") {
        $command = escapeshellcmd(
            $binary_full 
            . " encrypt "
            . "--pubkey " 
            . escapeshellarg(trim($public_key))
            . " " 
            . escapeshellarg(trim($plaintext))
        ) . " 2>&1";
    } else {
        $tmp_pubkey_file = tempnam(sys_get_temp_dir(), "pubkey_");
        file_put_contents($tmp_pubkey_file, trim($public_key));

        $command = escapeshellcmd(
            $binary_full 
            . " encrypt "
            . "--pubkey-file " 
            . escapeshellarg($tmp_pubkey_file)
            . " " 
            . escapeshellarg(trim($plaintext))
        ) . " 2>&1";
    }

    $encrypted_string = shell_exec($command);

    if (isset($tmp_pubkey_file) && file_exists($tmp_pubkey_file)) {
        unlink($tmp_pubkey_file);
    }

    return $encrypted_string;
}

// validate user input for secret keys
function valid_secret_key(string $secret_key, string $encryption_method = "ring_lwe"): bool {
    error_log("running valid_secret_key with method: " . $encryption_method);
    if (empty($secret_key)) {
        error_log("Error: secret key is empty.");
        return false;
    }

    if (!preg_match("/^[A-Za-z0-9+\/]+={0,2}$/", $secret_key)) {
        error_log("Error: secret key is not valid base64 string.");
        return false;
    }

    if ($encryption_method === "ring_lwe" && strlen($secret_key) > 10936) {
        error_log("Error: ring-lwe secret key is too long: " . strlen($secret_key));
        return false;
    }
    if ($encryption_method === "module_lwe" && strlen($secret_key) > 43704) {
        error_log("Error: module-lwe secret key is too long: " . strlen($secret_key));
        return false;
    }

    return true;
}

// validate user input for public keys
function valid_public_key(string $public_key, string $encryption_method = "ring_lwe"): bool {
    error_log("running valid_public_key with method: " . $encryption_method);
    if (empty($public_key)) {
        error_log("Error: public key is empty.");
        return false;
    }

    if (!preg_match("/^[A-Za-z0-9+\/]+={0,2}$/", $public_key)) {
        error_log("Error: public key is not a valid base64 string.");
        return false;
    }

    if ($encryption_method === "ring_lwe" && strlen($public_key) > 21856) {
        error_log("Error: ring-lwe public key exceeds maximum length: " . strlen($public_key));
        return false;
    }
    if ($encryption_method === "module_lwe" && strlen($public_key) > 393228) {
        error_log("Error: module-lwe public key exceeds maximum length: " . strlen($public_key));
        return false;
    }

    return true;
}

// validate username input from form
function valid_username(string $username, int $max_len): bool {
    if (empty($username)) {
        error_log("Error: username is empty.");
        return false;
    }
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

// validate message input from form
function valid_message(string $message, int $max_len): bool {
    if (empty($message)) {
        error_log("Error: message is empty.");
        return false;
    }
    if (!preg_match("/^[a-zA-Z0-9_ !?.:;~@#,()+=&$-]*$/", $message)) {
        error_log("Error: message contains invalid characters.");
        return false;
    }
    if (strlen($message) > $max_len) {
        error_log("Error: message is too long: " . strlen($message));
        return false;
    }
    return true;
}

// Fetch messages for a given user
function get_messages(Database $db, string $username, string $secret_key, array &$response): void {
    error_log("running get_messages for user: " . $username);

    $response['from'] = [];
    $response['to'] = [];
    $response['messages'] = [];
    $response['timestamps'] = [];

    try {
        $public_key_row = $db->fetchOne(
            "SELECT method FROM public_keys WHERE username = $1",
            [$username]
        );
        $encryption_method = $public_key_row['method'] ?? "ring_lwe";

        $valid_secret_key = valid_secret_key($secret_key, $encryption_method);

        $messages = $db->fetchAll(
            "SELECT from_user AS from_user, to_user AS to_user, message, timestamp FROM messages WHERE to_user = $1 ORDER BY id ASC",
            [$username]
        );

        if (empty($messages)) {
            $response['status'] = "success";
            return;
        }

        $response['status'] = "success";

        foreach ($messages as $row) {
            $response['from'][] = $row['from_user'];
            $response['to'][] = $row['to_user'];
            $response['timestamps'][] = $row['timestamp'];

            if ($valid_secret_key) {
                $decrypted = decrypt_message($secret_key, $row['message'], $encryption_method);
                $response['messages'][] = $decrypted;
            } else {
                $response['messages'][] = $row['message'];
            }
        }
    } catch (Exception $e) {
        $response['status'] = "failure";
        $response['error'] = "Exception: " . $e->getMessage();
        error_log("get_messages exception: " . $e->getMessage());
    }
}

// Retrieve the public key from the database for the given username
function get_public_key(Database $db, string $username, array &$response): ?string {
    error_log("running get_public_key for user: " . $username);
    try {
        if ($db->exists('public_keys', 'username', $username)) {
            $row = $db->fetchOne(
                "SELECT public_key FROM public_keys WHERE username = $1",
                [$username]
            );
            $response['public_key'] = $row['public_key'];
            $response['status'] = "success";
            $response['error'] = null;
            return $row['public_key'] ?? null;
        } else {
            error_log("No public key found for user: " . $username);
            $response['error'] = "No public key for $username";
            return null;
        }
    } catch (Exception $e) {
        $response['error'] = "Exception in get_public_key: " . $e->getMessage();
        error_log("Exception during get_public_key for username: $username: " . $e->getMessage());
        return null;
    }
}

// Generate keys using the Rust binary
function generate_keys(array &$response, string $encryption_method = "ring_lwe"): void {
    error_log("running generate_keys with method: " . $encryption_method);
    $binary_path = "/var/www/open-encrypt.com/html/bin/";
    $binary = $encryption_method === "ring_lwe" ? "ring-lwe-v0.1.8" : "module-lwe-v0.1.5";
    $command = escapeshellcmd($binary_path . $binary . " keygen");

    $json_string = shell_exec($command);
    try {
        $json_object = json_decode($json_string, true, 512, JSON_THROW_ON_ERROR);
    } catch (Exception $e) {
        $response['error'] = "Key generation failed: " . $e->getMessage();
        $response['status'] = "failure";
        error_log("Exception during key generation: " . $e->getMessage());
        return;
    }

    $response['secret_key'] = $json_object["secret"];
    $response['public_key'] = $json_object["public"];
    $response['status'] = "success";
}

// Save the public key to the database for the given username
function save_public_key(Database $db, string $username, string $public_key, ?string $encryption_method, array &$response): void {
    error_log("running save_public_key for user: " . $username . ", method: " . ($encryption_method ?? "NULL"));
    try {
        if ($db->exists('public_keys', 'username', $username)) {
            $ok = $db->execute(
                "UPDATE public_keys SET public_key = $1, method = $2 WHERE username = $3",
                [$public_key, $encryption_method, $username]
            );
        } else {
            $ok = $db->execute(
                "INSERT INTO public_keys (username, public_key, method) VALUES ($1, $2, $3)",
                [$username, $public_key, $encryption_method]
            );
        }

        $response['status'] = $ok ? "success" : "failure";
    } catch (Exception $e) {
        $response['status'] = "failure";
        $response['error'] = "Exception in save_public_key: " . $e->getMessage();
        error_log("Exception during save_public_key for username: $username: " . $e->getMessage());
    }
}

// Send message function
function send_message(Database $db, string $from_username, string $to_username, string $message, array &$response, string $encryption_method = "ring_lwe"): void {
    if (!valid_username($to_username, 14)) {
        $response['error'] = "Invalid recipient.";
        return;
    }
    if (!valid_message($message, 240)) {
        $response['error'] = "Invalid message.";
        return;
    }
    if (!$db->exists('login_info', 'username', $to_username)) {
        $response['error'] = "Recipient username does not exist.";
        return;
    }

    $public_key_row = $db->fetchOne(
        "SELECT public_key, method FROM public_keys WHERE username = $1",
        [$to_username]
    );

    if ($public_key_row === null || !valid_public_key($public_key_row['public_key'], $public_key_row['method'])) {
        error_log("Error: Recipient's public key is invalid or missing.");
        $response['error'] = "Recipient's public key is invalid or missing.";
        return;
    }

    $public_key = $public_key_row['public_key'];
    $encryption_method = $public_key_row['method'];

    $encrypted_message = encrypt_message($public_key, $message, $encryption_method);
    if (empty($encrypted_message)) {
        $response['error'] = "Encryption failed: empty result.";
        return;
    }

    try {
        $db->execute(
            "INSERT INTO messages (from_user, to_user, message, method) VALUES ($1, $2, $3, $4)",
            [$from_username, $to_username, $encrypted_message, $encryption_method]
        );
        $response['status'] = "success";
    } catch (Exception $e) {
        error_log("Exception during message insertion: " . $e->getMessage());
        $response['error'] = "Database exception: " . $e->getMessage();
    }
}

?>

<?php
if (isset($data['username'], $data['token'], $data['action'])) {
    $username = $data['username'];
    $token = $data['token'];
    $action = $data['action'];

    if (verify_token($db, $username, $token)) {
        if ($action === "get_messages") {
            error_log("begin getting messages for user: " . $username);
            $secret_key = $data['secret_key'] ?? '';
            get_messages($db, $username, $secret_key, $response);
            error_log("finished getting messages for user: " . $username);
        }
        if ($action === "get_public_key") {
            error_log("begin getting public key for user: " . $username);
            get_public_key($db, $username, $response);
            error_log("finished getting public key for user: " . $username);
        }
        if ($action === "generate_keys") {
            error_log("begin generating keys for user: " . $username);
            $encryption_method = $data['encryption_method'] ?? "ring_lwe";
            generate_keys($response, $encryption_method);
            error_log("finished generating keys for user: " . $username);
        }
        if ($action === "save_public_key") {
            error_log("begin saving public key for user: " . $username);
            $public_key = $data['public_key'] ?? '';
            $encryption_method = $data['encryption_method'] ?? "ring_lwe";
            error_log("encryption method is: " . $encryption_method);
            save_public_key($db, $username, $public_key, $encryption_method, $response);
            error_log("finished saving public key for user: " . $username);
        }
        if ($action === "send_message") {
            error_log("begin sending message for user: " . $username);
            $to_username = $data['recipient'] ?? '';
            $message = $data['message'] ?? '';
            send_message($db, $username, $to_username, $message, $response);
            error_log("finished sending message for user: " . $username);
        }
    }
}

echo json_encode($response);
