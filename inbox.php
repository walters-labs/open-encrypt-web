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

// If not logged in, redirect to home
if (!isset($_SESSION['user'])) {
    header("Location: index.html");
    exit();
}

if (array_key_exists('logout', $_POST)) {
    logout();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Open Encrypt - Inbox</title>
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

<main>
    <p>Welcome to your inbox, <?php echo htmlspecialchars($_SESSION['user']); ?>.</p>

    <h3>Instructions:</h3>
    <ul>
        <li>Use "Key Generation" to generate public and secret keys.</li>
        <li>Save your secret key to a file in a safe place. Do not share this file.</li>
        <li>Save your public key to the server so others can send you messages. You can view it after saving.</li>
        <li>Optionally, copy or download your public key locally.</li>
        <li>Once keys are saved, send another user a message using their username.</li>
        <li>To view encrypted messages, go to "View Messages" and click "View Encrypted Messages".</li>
        <li>To decrypt messages, upload your secret key file, select the encryption method, and click "Decrypt Messages".</li>
    </ul>
</main>

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