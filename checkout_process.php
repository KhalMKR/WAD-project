<?php
session_start();
include 'db.php'; // Using the connection you made

// Check if user is logged in
if (!isset($_SESSION['email']) || !isset($_SESSION['userID'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userID = $_SESSION['userID'];
    $email = $_SESSION['email'];
    $total = $_POST['totalAmount'];

    // Chung's Security: Using Prepared Statements to prevent SQL Injection
    $stmt = $conn->prepare("INSERT INTO orders (userID, totalAmount) VALUES (?, ?)");
    $stmt->bind_param("id", $userID, $total);

    if ($stmt->execute()) {
        // Return success to the Javascript
        echo json_encode(['success' => true, 'orderID' => $conn->insert_id]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
}
?>