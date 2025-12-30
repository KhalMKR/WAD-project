<?php
session_start();
include '../includes/db.php';

header('Content-Type: application/json');

// Return 0 if user not logged in
if (!isset($_SESSION['userID'])) {
    echo json_encode(['count' => 0]);
    exit;
}

$userID = $_SESSION['userID'];

// Get total quantity from cart
$stmt = $conn->prepare("SELECT SUM(quantity) as total FROM cart WHERE userID = ?");
$stmt->bind_param('i', $userID);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

$count = $row['total'] ? intval($row['total']) : 0;

echo json_encode(['count' => $count]);
$stmt->close();
?>
