<?php
session_start();
include 'includes/db.php';

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['userID'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

$userID = $_SESSION['userID'];
$input = json_decode(file_get_contents('php://input'), true);

$productID = isset($input['productID']) ? intval($input['productID']) : 0;
$quantity = isset($input['quantity']) ? intval($input['quantity']) : 1;

if ($productID <= 0 || $quantity <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product or quantity']);
    exit;
}

// Check if item already exists in cart
$checkStmt = $conn->prepare("SELECT quantity FROM cart WHERE userID = ? AND productID = ?");
$checkStmt->bind_param('ii', $userID, $productID);
$checkStmt->execute();
$result = $checkStmt->get_result();

if ($result->num_rows > 0) {
    // Item exists, update quantity
    $row = $result->fetch_assoc();
    $newQuantity = $row['quantity'] + $quantity;
    
    $updateStmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE userID = ? AND productID = ?");
    $updateStmt->bind_param('iii', $newQuantity, $userID, $productID);
    
    if ($updateStmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Cart updated', 'action' => 'updated']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update cart']);
    }
    $updateStmt->close();
} else {
    // New item, insert
    $insertStmt = $conn->prepare("INSERT INTO cart (userID, productID, quantity) VALUES (?, ?, ?)");
    $insertStmt->bind_param('iii', $userID, $productID, $quantity);
    
    if ($insertStmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Added to cart', 'action' => 'inserted']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to add to cart']);
    }
    $insertStmt->close();
}

$checkStmt->close();
?>
