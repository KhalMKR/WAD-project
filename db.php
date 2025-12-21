<?php
// Chung's Database Connection Script
$servername = "localhost";
$username = "root";     // Default XAMPP username
$password = "";         // Default XAMPP password is empty
$dbname = "unimerch_hub"; // The name you gave in phpMyAdmin

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    // If it fails, show the error
    die("Connection failed: " . $conn->connect_error);
} 

// This confirms it works - Khalish can remove this line once tested
// echo "Connected successfully to UniMerch Database";
?>