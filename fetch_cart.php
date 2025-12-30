<?php
session_start();
include 'includes/db.php';

header('Content-Type: application/json');

// Return empty cart if not logged in
if (!isset($_SESSION['userID'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in', 'cart' => []]);
    exit;
}

$userID = $_SESSION['userID'];

// Fetch cart items with product details
$stmt = $conn->prepare("
    SELECT 
        c.cartItemID,
        c.productID as id,
        c.quantity,
        p.name,
        p.price,
        p.imagePath as image
    FROM cart c
    JOIN products p ON c.productID = p.productID
    WHERE c.userID = ?
    ORDER BY c.dateAdded DESC
");

$stmt->bind_param('i', $userID);
$stmt->execute();
$result = $stmt->get_result();

$cartItems = [];
while ($row = $result->fetch_assoc()) {
    // Convert price to float for JavaScript
    $row['price'] = floatval($row['price']);
    $row['quantity'] = intval($row['quantity']);
    $row['id'] = intval($row['id']);
    $cartItems[] = $row;
}

$stmt->close();

echo json_encode([
    'success' => true,
    'cart' => $cartItems
]);
?>
