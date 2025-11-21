<?php
// utility functions for user validation and token management

// Ring-LWE key lengths (base64-encoded)
define('RING_LWE_SECKEY_MAXLEN', 10936);  // max length of secret key
define('RING_LWE_PUBKEY_MAXLEN', 21856);  // max length of public key

// Module-LWE key lengths (base64-encoded)
define('MODULE_LWE_SECKEY_MAXLEN', 43704);  // max length of secret key
define('MODULE_LWE_PUBKEY_MAXLEN', 393228); // max length of public key

// User constraints
define('MAX_USERNAME_LEN', 14);  // max length of username
define('MAX_PASSWORD_LEN', 24);  // max length of password

// Message constraint
define('MAX_MESSAGE_LEN', 240);  // max length of message

function redirect($url) {
    header('Location: ' . $url);
    die();
}

// Define a function which logs out the user
function logout(){
    // Unset all of the session variables.
    $_SESSION = array();

    // If it's desired to kill the session, also delete the session cookie.
    // Note: This will destroy the session, and not just the session data!
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
                
    // Finally, destroy the session.
    session_destroy();
    redirect("login.php");
}

// Create a temporary file and return its name
function make_tempfile($prefix = 'oe_') {
    $tmp = sys_get_temp_dir();
    $name = tempnam($tmp, $prefix);
    if ($name === false) {
        throw new Exception("Unable to create temp file");
    }
    return $name;
}

// Generate a secure login token
function generate_token(): string {
    return bin2hex(random_bytes(16)); // 32 characters
}

// Fetch the public key for a given username
function fetch_public_key(Database $db, string $username): ?string {
    if (!username_exists($db, $username, "public_keys")) {
        return null;
    }

    $row = $db->fetchOne(
        "SELECT public_key FROM public_keys WHERE username = $1",
        [$username]
    );

    return $row['public_key'] ?? null;
}

// Fetch encryption method for a username
function fetch_encryption_method(Database $db, string $username): ?string {
    if (!username_exists($db, $username, "public_keys")) {
        return null;
    }

    $row = $db->fetchOne(
        "SELECT method FROM public_keys WHERE username = $1",
        [$username]
    );

    return $row['method'] ?? null;
}

// Check whether a username exists in the given table
function username_exists(Database $db, string $username, string $table = "login_info"): bool {
    $allowed_tables = ["login_info", "public_keys"];
    if (!in_array($table, $allowed_tables)) {
        throw new Exception("Invalid table name");
    }

    // Important: identifiers cannot be parameterized in pgSQL,
    // so you must sanitize or whitelist the table name as done above.

    $query = "SELECT COUNT(*) FROM $table WHERE username = $1";
    $count = $db->count($query, [$username]);
    return $count > 0;
}

// Store login token in database
function store_token(Database $db, string $username, string $token): bool {
    return $db->execute(
        "UPDATE login_info SET token = $1 WHERE username = $2",
        [$token, $username]
    );
}

/**
 * Validate a username.
 * Rules:
 *  - Non-empty
 *  - Only letters, numbers, underscores
 *  - Not longer than $max_len
 */
function valid_username(string $username, int $max_len = MAX_USERNAME_LEN): bool {
    if (empty($username)) {
        return false;
    }
    if (!preg_match("/^[a-zA-Z0-9_]*$/", $username)) {
        return false;
    }
    if (strlen($username) > $max_len) {
        return false;
    }
    return true;
}

/**
 * Validate a password.
 * Rules:
 * - Non-empty
 * - Only letters, numbers, underscores, hyphens
 * - Not longer than $max_len
 */
function valid_password(string $password, int $max_len = MAX_PASSWORD_LEN): bool {
    if (empty($password)) return false;
    if (!preg_match("/^[a-zA-Z0-9_-]*$/", $password)) return false;
    if (strlen($password) > $max_len) return false;
    return true;
}

/**
 * Validate a message.
 * Rules:
 *  - Non-empty
 *  - Only letters, numbers, underscores, spaces, and common punctuation
 *  - Not longer than $max_len
 */
function valid_message(string $message, int $max_len = MAX_MESSAGE_LEN): bool {
    if (empty($message)) return false;
    if (strlen($message) > $max_len) return false;
    // Allow all printable characters except control characters
    if (preg_match('/[[:cntrl:]&&[^\r\n\t]]/', $message)) return false;
    return true;
}

/**
 * Validate a secret key.
 * Rules:
 *  - Non-empty
 *  - Base64 format
 *  - Optional length restrictions depending on encryption method
 */
function valid_secret_key(string $secret_key, string $encryption_method = "ring_lwe"): bool {
    if (empty($secret_key)) {
        return false;
    }
    if (!preg_match("/^[A-Za-z0-9+\/]+={0,2}$/", $secret_key)) {
        return false;
    }
    if ($encryption_method === "ring_lwe" && strlen($secret_key) > RING_LWE_SECKEY_MAXLEN) {
        return false;
    }
    if ($encryption_method === "module_lwe" && strlen($secret_key) > MODULE_LWE_SECKEY_MAXLEN) {
        return false;
    }
    return true;
}

/**
 * Validate a public key.
 * Rules:
 *  - Non-empty
 *  - Base64 format
 *  - Optional length restrictions depending on encryption method
 */
function valid_public_key(string $public_key, string $encryption_method = "ring_lwe"): bool {
    if (empty($public_key)) {
        return false;
    }
    if (!preg_match("/^[A-Za-z0-9+\/]+={0,2}$/", $public_key)) {
        return false;
    }
    if ($encryption_method === "ring_lwe" && strlen($public_key) > RING_LWE_PUBKEY_MAXLEN) {
        return false;
    }
    if ($encryption_method === "module_lwe" && strlen($public_key) > MODULE_LWE_PUBKEY_MAXLEN) {
        return false;
    }
    return true;
}

function display_messages(Database $db, string $username, ?string $seckey_tempfile = null, ?string $encryption_method = null) {
    try {
        $messages = $db->fetchAll(
            "SELECT id, sender, recipient, message, method, timestamp
            FROM messages
            WHERE recipient = $1
            ORDER BY id DESC",
            [$username]
        );

        if (empty($messages)) {
            echo "<p>No messages found.</p>";
            return;
        }

        echo $seckey_tempfile
            ? "<p>Retrieved messages successfully... Decrypting messages...</p>"
            : "<p>Retrieved messages successfully.</p>";

        foreach ($messages as $row) {
            echo "<p>[id=" . htmlspecialchars($row['id']) . "] ";
            echo htmlspecialchars($row['sender']) . " --> " . htmlspecialchars($row['recipient']);
            if (!$seckey_tempfile) echo " (" . htmlspecialchars($row['method']) . ")";

            if (!empty($row['timestamp'])) {
                $dt = new DateTime($row['timestamp'], new DateTimeZone('UTC'));
                $dt->setTimezone(new DateTimeZone('America/New_York'));
                $formatted_time = $dt->format('Y-m-d H:i:s');
                echo " <em>[" . htmlspecialchars($formatted_time) . " EST]</em>";
            }

            echo ": ";

            if ($seckey_tempfile && $encryption_method) {
                if ($row['method'] !== $encryption_method) {
                    echo "[different encryption method]</p>";
                    continue;
                }

                $ct_tempfile = make_tempfile('ct_');
                file_put_contents($ct_tempfile, $row['message']);

                try {
                    $out = run_decrypt_with_files($seckey_tempfile, $ct_tempfile, $encryption_method);
                    echo '<div class="message-box">' . htmlspecialchars($out) . '</div>';
                } catch (Exception $e) {
                    echo '<div class="decryption-error">[Decryption failed: ' . htmlspecialchars($e->getMessage()) . ']</div>';
                }

                @unlink($ct_tempfile);
            } else {
                echo '<div class="message-box">';
                echo chunk_split(htmlspecialchars($row['message']), 64, "\n");
                echo '</div></p>';
            }
        }
    } catch (Exception $e) {
        echo "<p>Error fetching messages: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
}

// Send an encrypted message from one user to another
function send_message(Database $db, string $username, string $to_username, string $message): array {
    // Will return: ['success' => bool, 'message' => string]

    if (!valid_username($to_username, MAX_USERNAME_LEN)) {
        error_log("Error: Invalid recipient username '$to_username' by '$username'");
        return ['success' => false, 'message' => "Invalid recipient username."];
    }

    if (!valid_message($message, MAX_MESSAGE_LEN)) { // allow long messages
        error_log("Error: Invalid message content by '$username'");
        return ['success' => false, 'message' => "Invalid message content."];
    }

    $recipient = $db->fetchOne(
        "SELECT username FROM login_info WHERE username = $1",
        [$to_username]
    );
    if ($recipient === null) {
        error_log("Error: Non-existent recipient '$to_username' by '$username'");
        return ['success' => false, 'message' => "Recipient does not exist."];
    }

    $pub_row = $db->fetchOne(
        "SELECT public_key, method FROM public_keys WHERE username = $1",
        [$to_username]
    );
    if ($pub_row === null || !valid_public_key($pub_row['public_key'], $pub_row['method'])) {
        error_log("Error: Invalid/missing public key for '$to_username' (sent by '$username')");
        return ['success' => false, 'message' => "Recipient’s public key is invalid or missing."];
    }

    try {
        $encrypted = encrypt_message($pub_row['public_key'], $message, $pub_row['method']);
    } catch (Exception $e) {
        error_log("Encryption failed: " . $e->getMessage());
        return ['success' => false, 'message' => "Encryption failed." . $e->getMessage()];
    }

    $success = $db->execute(
        "INSERT INTO messages (sender, recipient, message, method) VALUES ($1, $2, $3, $4)",
        [$username, $to_username, $encrypted, $pub_row['method']]
    );

    if (!$success) {
        error_log("Database insert failed for message from '$username' to '$to_username'");
        return ['success' => false, 'message' => "Database error when storing message."];
    }

    return ['success' => true, 'message' => "Message sent successfully using {$pub_row['method']}."];
}

?>