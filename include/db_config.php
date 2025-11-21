<?php

$host     = 'localhost';
$db       = 'open_encrypt';
$user     = 'your-username-here';
$password = 'your-password-here';
$port     = 5432;  // Default PostgreSQL port

$conn_string = sprintf(
    "host=%s port=%d dbname=%s user=%s password=%s",
    $host,
    $port,
    $db,
    $user,
    $password
);

$conn = pg_connect($conn_string);

if (!$conn) {
    echo "Not connected to PostgreSQL database.";
    error_log("PostgreSQL connection failed: " . pg_last_error());
    exit;  // stop execution if no connection
}
?>

