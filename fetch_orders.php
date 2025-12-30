<?php
session_start();
include 'includes/db.php';

header('Content-Type: application/json');

$userID = null;
if (isset($_SESSION['userID'])) {
    $userID = intval($_SESSION['userID']);
} elseif (isset($_GET['userID'])) {
    $userID = intval($_GET['userID']);
} else {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT orderID, userID, totalAmount, orderDate FROM orders WHERE userID = ? ORDER BY orderDate DESC");
if (!$stmt) {
    echo json_encode(['error' => 'Prepare failed', 'details' => $conn->error]);
    exit;
}

$stmt->bind_param('i', $userID);
$stmt->execute();
$result = $stmt->get_result();
$orders = [];
while ($row = $result->fetch_assoc()) {
    // Normalize keys to match frontend expectations
    $orders[] = [
        'orderID' => (int)$row['orderID'],
        'userID' => (int)$row['userID'],
        'totalAmount' => (float)$row['totalAmount'],
        'orderDate' => $row['orderDate']
    ];
}

echo json_encode($orders);

?>
