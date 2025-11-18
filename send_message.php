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
    <title>Open Encrypt - Send Message</title>
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

<h2>Send Message: <?php echo htmlspecialchars($username); ?></h2>

<!-- Send Message Form -->
<form method="post" class="message-form">
    <label for="to">To:</label>
    <input type="text" id="to" name="to" required>

    <label for="message">Message:</label>
    <input type="text" id="message" name="message" required>

    <input type="submit" value="Send">
</form>

<?php
if (isset($_POST['to'], $_POST['message'])) {
    $to_username = $_POST['to'];
    $message = $_POST['message'];
    $result = send_message($db, $username, $to_username, $message);
    echo '<div class="message-box">' . htmlspecialchars($result['message']) . '</div>';
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