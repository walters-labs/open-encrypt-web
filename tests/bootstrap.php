<?php
// Load DB config from environment variables
$host = getenv('DB_HOST');
$user = getenv('DB_USER');
$pass = getenv('DB_PASS');
$name = getenv('DB_NAME');
$port = getenv('DB_PORT') ?: 5432;

$dsn = "pgsql:host=$host;port=$port;dbname=$name";
$pdo = new PDO($dsn, $user, $pass);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$GLOBALS['pdo'] = $pdo; // Make it accessible in tests

// Include your project's helper code
require_once __DIR__ . '/../include/Database.php';
require_once __DIR__ . '/../include/encryption.php';
require_once __DIR__ . '/../include/utils.php';
