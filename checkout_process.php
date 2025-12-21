<?php
session_start();
include 'db.php'; // Using the connection you made

// Check if user is logged in
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_SESSION['email'];
    $total = $_POST['totalAmount'];

    // Chung's Security: Using Prepared Statements to prevent SQL Injection
    $stmt = $conn->prepare("INSERT INTO orders (userEmail, totalAmount) VALUES (?, ?)");
    $stmt->bind_param("sd", $email, $total);

    if ($stmt->execute()) {
        // Return success to the Javascript
        echo json_encode(['success' => true, 'orderID' => $conn->insert_id]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
}
?>