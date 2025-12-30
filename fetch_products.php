<?php
// Chung's Fetcher Script
include 'includes/db.php'; // Use your connection

$sql = "SELECT * FROM products";
$result = $conn->query($sql);

$products_array = array();

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $products_array[] = $row;
    }
}

// Convert the database results into a format JavaScript understands (JSON)
echo json_encode($products_array);
?>