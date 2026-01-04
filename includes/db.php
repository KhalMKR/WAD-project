<?php
// Database Connection Script
require_once __DIR__ . '/../config.php';

$servername = DB_HOST;
$username = DB_USERNAME;
$password = DB_PASSWORD;
$dbname = DB_NAME;

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // If it fails, show the error
    die("Connection failed: " . $conn->connect_error);
}
?>
