<?php
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', '/var/www/open-encrypt.com/html/error.log');
error_reporting(E_ALL);

include_once 'include/db_config.php';
include_once 'include/Database.php';
require_once 'include/utils.php';
$db = new Database($conn);

session_start();

// redirect if user is already logged in
if (isset($_SESSION['user'])) {
    redirect("inbox.php");
}

$username = $_POST['username'] ?? '';
$password = $_POST['password'] ?? '';
$error_message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $valid_username = valid_username($username) && username_exists($db, $username);
    $valid_password = valid_password($password);

    if ($valid_username && $valid_password) {
        $row = $db->fetchOne("SELECT password FROM login_info WHERE username = ?", [$username], "s");

        if ($row && password_verify($password, $row['password'])) {
            $login_token = generate_token();
            store_token($db, $username, $login_token);

            $_SESSION['user'] = $username;
            redirect("inbox.php");
        } else {
            $error_message = "Incorrect password.";
            error_log("Error: Incorrect password for user $username.");
        }
    } else {
        $error_message = "Incorrect username or password.";
        error_log("Invalid username or password: $username");
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Open Encrypt - Login</title>
    <link rel="stylesheet" href="css/style.css">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Open Encrypt is a secure messaging app in development.">
    <meta name="robots" content="index, follow">
    <!-- Structured Data -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "Open Encrypt",
        "url": "https://open-encrypt.com/"
    }
    </script>
</head>
<body>

<header>
    <h1><a href="index.html">Open Encrypt</a></h1>
    <h2>Status: Development (11/18/2025)</h2>
    <h3>Source: <a href="https://github.com/walters-labs/open-encrypt-web">https://github.com/walters-labs/open-encrypt-web</a></h3>
    <h3>API Docs: <a href="https://docs.open-encrypt.com">https://docs.open-encrypt.com</a></h3>
</header>

<main class="centered">

    <form action="login.php" method="POST" class="auth-form">
        <label for="username">Username:</label><br>
        <input type="text" id="username" name="username" value="<?= htmlspecialchars($username) ?>"><br>

        <label for="password">Password:</label><br>
        <input type="password" id="password" name="password"><br>

        <input type="submit" value="Login">
    </form>

    <div class="button-container">
        <form action="create_account.php" method="get">
            <input type="submit" value="Create Account">
        </form>
    </div>

    <?php if ($error_message): ?>
        <p class="error"><?= htmlspecialchars($error_message) ?></p>
    <?php endif; ?>

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
