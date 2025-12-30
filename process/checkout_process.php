<?php
session_start();
include '../includes/db.php'; // Using the connection you made

// Check if user is logged in
if (!isset($_SESSION['email']) || !isset($_SESSION['userID'])) {
    echo json_encode(['success' => false, 'message' => 'Please login first']);
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userID = $_SESSION['userID'];
    $email = $_SESSION['email'];
    $total = $_POST['totalAmount'];
    $custName = $_POST['fullName'] ?? '';
    $custPhone = $_POST['phone'] ?? '';
    $custAddress = $_POST['address'] ?? '';
    $paymentMethod = $_POST['paymentMethod'] ?? '';
    $itemsJson = $_POST['items'] ?? '[]';
    $items = json_decode($itemsJson, true);

    // Ensure orders table has columns for storing customer details
    $colsNeeded = [
        'fullName' => "ALTER TABLE orders ADD COLUMN fullName varchar(200) NOT NULL DEFAULT ''",
        'phone' => "ALTER TABLE orders ADD COLUMN phone varchar(50) NOT NULL DEFAULT ''",
        'address' => "ALTER TABLE orders ADD COLUMN address varchar(400) NOT NULL DEFAULT ''",
        'paymentMethod' => "ALTER TABLE orders ADD COLUMN paymentMethod varchar(100) NOT NULL DEFAULT ''"
    ];
    foreach ($colsNeeded as $col => $alterSql) {
        $check = $conn->prepare("SELECT COUNT(*) as cnt FROM information_schema.columns WHERE table_schema = ? AND table_name = 'orders' AND column_name = ?");
        if ($check) {
            $dbn = $dbname;
            $check->bind_param('ss', $dbn, $col);
            $check->execute();
            $cr = $check->get_result();
            if ($cr && ($r = $cr->fetch_assoc()) && intval($r['cnt']) === 0) {
                $conn->query($alterSql);
            }
            $check->close();
        }
    }

    // Ensure order_items table exists
    $createItems = "CREATE TABLE IF NOT EXISTS order_items (
        itemID int NOT NULL AUTO_INCREMENT PRIMARY KEY,
        orderID int NOT NULL,
        productID int DEFAULT NULL,
        name varchar(200) NOT NULL,
        price decimal(10,2) NOT NULL,
        quantity int NOT NULL,
        INDEX (orderID)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $conn->query($createItems);

    // Ensure a proper orderID is created even if the DB table lacks AUTO_INCREMENT
    // Get next orderID by using MAX(orderID)+1 (works with current schema)
    $nextId = 1;
    $res = $conn->query("SELECT MAX(orderID) as maxid FROM orders");
    if ($res) {
        $row = $res->fetch_assoc();
        if ($row && $row['maxid'] !== null) $nextId = intval($row['maxid']) + 1;
    }

    // Insert specifying orderID explicitly including customer details
    $stmt = $conn->prepare("INSERT INTO orders (orderID, userID, totalAmount, fullName, phone, address, paymentMethod) VALUES (?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param("iidssss", $nextId, $userID, $total, $custName, $custPhone, $custAddress, $paymentMethod);

    if ($stmt->execute()) {
        // Insert items into order_items table if provided
        if (is_array($items) && count($items) > 0) {
            // Insert per item (prepared inside loop)
            foreach ($items as $it) {
                $pid = isset($it['id']) ? intval($it['id']) : null;
                $iname = isset($it['name']) ? $it['name'] : '';
                $iprice = isset($it['price']) ? floatval($it['price']) : 0.0;
                $iqty = isset($it['quantity']) ? intval($it['quantity']) : 1;
                $ins = $conn->prepare("INSERT INTO order_items (orderID, productID, name, price, quantity) VALUES (?, ?, ?, ?, ?)");
                if ($ins) {
                    $ins->bind_param('iisdi', $nextId, $pid, $iname, $iprice, $iqty);
                    $ins->execute();
                    $ins->close();
                }
            }
        }

        // ---------------------------------------------------------
        // SEND EMAIL NOTIFICATION
        // ---------------------------------------------------------
        $to = $email; 
        $subject = "Order Confirmation - Order #" . $nextId;
        
        $message = "Dear " . ($custName ?: "Customer") . ",\n\n";
        $message .= "Thank you for shopping with UniMerch Hub! Your order has been placed successfully.\n\n";
        $message .= "Order Details:\n";
        $message .= "--------------------------------\n";
        $message .= "Order ID: " . $nextId . "\n";
        $message .= "Total Amount: RM " . number_format($total, 2) . "\n";
        $message .= "Payment Method: " . $paymentMethod . "\n";
        $message .= "--------------------------------\n\n";
        $message .= "We will process your order shortly.\n\n";
        $message .= "Best Regards,\nUniMerch Hub Team";

        // Headers
        $headers = "From: no-reply@unimerchhub.com\r\n";
        $headers .= "Reply-To: support@unimerchhub.com\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        // Send the email (suppress errors with @ to prevent breaking JSON response)
        @mail($to, $subject, $message, $headers);
        // ---------------------------------------------------------

        // Clear the user's cart from database
        $clearCart = $conn->prepare("DELETE FROM cart WHERE userID = ?");
        if ($clearCart) {
            $clearCart->bind_param('i', $userID);
            $clearCart->execute();
            $clearCart->close();
        }

        echo json_encode(['success' => true, 'orderID' => $nextId]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
}
?>
