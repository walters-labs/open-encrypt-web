<?php
ini_set('display_errors', '1');
ini_set('display_startup_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/var/www/open-encrypt.com/html/error.log');
error_reporting(E_ALL);

require_once 'include/db_config.php';
require_once 'include/Database.php';
require_once 'include/utils.php';
require_once 'include/encryption.php';

session_start();
$db = new Database($conn);

// Handle logout
if (isset($_POST['logout'])) logout();

// Ensure user is logged in
if (!isset($_SESSION['user'])) redirect("login.php");
$username = $_SESSION['user'];
?>

<html>
<head>
    <title>Open Encrypt - View Messages</title>
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

<h2>View Messages: <?php echo htmlspecialchars($username); ?></h2>

<!-- View Encrypted Messages -->
<form method="post" class="message-form">
    <input type="submit" name="view_messages" value="View Encrypted Messages">
</form>

<!-- Decrypt Messages -->
<form method="post" enctype="multipart/form-data" class="message-form">
    <label for="secret_key_file">Upload Secret Key File:</label>
    <input type="file" id="secret_key_file" name="secret_key_file" accept=".txt,.key" required>

    <div class="encryption-methods">
        <input type="radio" id="ring_lwe" name="encryption_method" value="ring_lwe" checked>
        <label for="ring_lwe">ring-LWE</label>
        <input type="radio" id="module_lwe" name="encryption_method" value="module_lwe">
        <label for="module_lwe">module-LWE</label>
    </div>

    <input type="submit" name="decrypt_messages" value="Decrypt Messages">
</form>

<?php
// Handle viewing encrypted messages
if (isset($_POST['view_messages'])) {
    display_messages($db, $username);
}

// Handle decrypting messages
if (isset($_POST['decrypt_messages'], $_POST['encryption_method'])) {
    $encryption_method = $_POST['encryption_method'];

    if (!isset($_FILES['secret_key_file']) || $_FILES['secret_key_file']['error'] !== UPLOAD_ERR_OK) {
        error_log("Error: Secret key file upload error for user " . htmlspecialchars($username));
        echo '<div class="message-box">Error: Secret key file is required.</div>';
        return;
    }

    $tmp_name = $_FILES['secret_key_file']['tmp_name'];
    $seckey_tempfile = make_tempfile('seckey_');

    if (!move_uploaded_file($tmp_name, $seckey_tempfile) && !copy($tmp_name, $seckey_tempfile)) {
        error_log("Error: Failed to store uploaded secret key for user " . htmlspecialchars($username));
        echo '<div class="message-box">Error: Failed to store uploaded secret key.</div>';
        return;
    }

    $secret_key_contents = trim(file_get_contents($seckey_tempfile));
    if ($secret_key_contents === false || !valid_secret_key($secret_key_contents, $encryption_method)) {
        error_log("Error: Invalid secret key for user " . htmlspecialchars($username));
        echo '<div class="message-box">Error: Invalid secret key.</div>';
        return;
    }

    display_messages($db, $username, $seckey_tempfile, $encryption_method);

    if (file_exists($seckey_tempfile)) @unlink($seckey_tempfile);
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