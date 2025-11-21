<?php
ini_set('display_errors', 0);  // Display errors in the browser (for debugging purposes)
ini_set('log_errors', 1);      // Enable error logging
ini_set('error_log', '/var/www/open-encrypt.com/html/error.log');  // Absolute path to the error log file
error_reporting(E_ALL);         // Report all types of errors

session_start();
require_once 'include/utils.php';
require_once 'include/db_config.php';
require_once 'include/Database.php';
require_once 'include/encryption.php';

$db = new Database($conn);

// Handle logout
if (isset($_POST['logout'])) {
    logout();
}

// Make sure user is logged in
if (!isset($_SESSION['user'])) {
    redirect("login.php");
}
$username = $_SESSION['user'];
?>

<html>
<head>
    <title>Open Encrypt - Key Management</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<header>
    <h1><a href="index.html">Open Encrypt</a></h1>
    <h2>Status: Development (11/18/2025)</h2>
    <h3>Source: <a href="https://github.com/walters-labs/open-encrypt-web">https://github.com/walters-labs/open-encrypt-web</a></h3>
    <h3>API Docs: <a href="https://docs.open-encrypt.com">https://docs.open-encrypt.com</a></h3>
    <nav>
        <a href="inbox.php" class="nav-link">Home</a>
        <a href="send_message.php" class="nav-link">Send Message</a>
        <a href="view_messages.php" class="nav-link">View Messages</a>
        <a href="key_management.php" class="nav-link">Key Management</a>
        <form method="post" style="display:inline;">
            <input type="submit" name="logout" value="Logout">
        </form>
    </nav>
</header>

<hr>

<h2>Key Management: <?php echo htmlspecialchars($username); ?></h2>

<!-- Key Generation Form -->
<form method="post" style="display:inline-block; padding:5px; border:1px solid #000; background:#fff;">
    <input type="radio" id="ring_lwe" name="encryption_method" value="ring_lwe" checked>
    <label for="ring_lwe">ring-LWE</label>
    <input type="radio" id="module_lwe" name="encryption_method" value="module_lwe">
    <label for="module_lwe">module-LWE</label>
    <br><br>
    <input type="submit" name="key_gen" value="Generate Keys" style="padding:5px;">
</form>

<form method="post" style="display:inline-block; padding:5px;">
    <input type="submit" name="save_keys" value="Save Public Key to Server" style="padding:5px;">
</form>

<form method="post" style="display:inline-block; padding:5px;">
    <input type="submit" name="view_keys" value="View Public Key" style="padding:5px;">
</form>

<script>
function copyKey(divId) {
    const keyDiv = document.getElementById(divId);
    if (!keyDiv) return;
    let keyText = keyDiv.textContent.replace(/\r?\n/g, '');
    navigator.clipboard.writeText(keyText).then(() => alert("Key copied!"));
}

function downloadKey(divId, filename) {
    const keyDiv = document.getElementById(divId);
    if (!keyDiv) return;
    let text = keyDiv.textContent.replace(/\r?\n/g, '');
    const blob = new Blob([text], { type: 'text/plain' });
    const url = URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url; a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}
</script>

<?php
// Generate Keys
if (isset($_POST['key_gen'], $_POST['encryption_method'])) {
    $encryption_method = $_POST['encryption_method'];
    $json_keys = generate_keys($encryption_method);

    $secret_key = trim($json_keys['secret']);
    $public_key = trim($json_keys['public']);

    echo "<h3>Secret Key ($encryption_method)</h3>";
    echo '<div id="secret_key_box" class="key-box">';
    echo chunk_split($secret_key, 64, "\n");
    echo '</div><br>';
    echo '<button onclick="copyKey(\'secret_key_box\')">Copy</button> ';
    echo '<button onclick="downloadKey(\'secret_key_box\', \'' . $username . '_secret_key.txt\')">Save</button>';

    echo "<h3>Public Key ($encryption_method)</h3>";
    echo '<div id="public_key_box" class="key-box">';
    echo chunk_split($public_key, 64, "\n");
    echo '</div><br>';
    echo '<button onclick="copyKey(\'public_key_box\')">Copy</button> ';
    echo '<button onclick="downloadKey(\'public_key_box\', \'' . $username . '_public_key.txt\')">Save</button>';

    $_SESSION['public_key'] = $public_key;
    $_SESSION['encryption_method'] = $encryption_method;
}

// Save Public Key
if (isset($_POST['save_keys'], $_SESSION['public_key'], $_SESSION['encryption_method'])) {
    $public_key = $_SESSION['public_key'];
    $encryption_method = $_SESSION['encryption_method'];

    if (!valid_public_key($public_key, $encryption_method)) {
        error_log("Error: Invalid public key for user " . htmlspecialchars($username));
        echo "<p>Error: Invalid public key.</p>";
    } else {
        $existing = $db->fetchOne("SELECT username FROM public_keys WHERE username = $1", [$username]);
        if ($existing === null) {
            $success = $db->execute(
                "INSERT INTO public_keys (username, public_key, method) VALUES ($1, $2, $3)",
                [$username, $public_key, $encryption_method]
            );
        } else {
            $success = $db->execute(
                "UPDATE public_keys SET public_key = $1, method = $2 WHERE username = $3",
                [$public_key, $encryption_method, $username]
            );
        }

        if ($success) {
            echo "<p>Public key saved successfully.</p>";
            unset($_SESSION['public_key'], $_SESSION['encryption_method']);
        } else {
            error_log("Error: Failed to save public key for user " . htmlspecialchars($username));
            echo "<p>Error saving public key.</p>";
        }
    }
}

// View Public Key
if (isset($_POST['view_keys'])) {
    $public_key = fetch_public_key($db, $username);
    $encryption_method = fetch_encryption_method($db, $username);

    if ($public_key && $encryption_method && valid_public_key($public_key, $encryption_method)) {
        echo "<h3>Public Key ($encryption_method)</h3>";
        echo '<div id="public_key_box" class="key-box">';
        echo chunk_split($public_key, 64, "\n");
        echo '</div><br>';
        // Add copy and download buttons
        echo '<button onclick="copyKey(\'public_key_box\')">Copy</button> ';
        echo '<button onclick="downloadKey(\'public_key_box\', \'' . $username .'_public_key.txt\')">Save</button>';

        $_SESSION['public_key'] = $public_key;
        $_SESSION['encryption_method'] = $encryption_method;
    } else {
        error_log("Error: No valid public key found for user " . htmlspecialchars($username));
        echo "<p>No valid public key found.</p>";
    }
}

?>

<footer class="footer">
    <p>
        &copy; 2025 Open Encrypt. All rights reserved. | 
        <a href="/privacy">Privacy Policy</a> | 
        <a href="/terms">Terms of Service</a> | 
        <a href="/contact">Contact</a>
    </p>
</footer>

</body>
</html>
