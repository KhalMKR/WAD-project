<?php
// Chung's Database Connection Script
$servername = "sql109.infinityfree.com";
$username = "if0_40821930";     
$password = "ZKkI2j1Y5bIwk";         
$dbname = "if0_40821930_unimerch_hub "; 

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
