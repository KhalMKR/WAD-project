<?php
session_start();
include '../includes/db.php'; // Using the connection you made
include '../config.php'; // Include configuration

// Include PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../phpmailer/Exception.php';
require '../phpmailer/PHPMailer.php';
require '../phpmailer/SMTP.php';

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
    $orderDateTime = $_POST['orderDateTime'] ?? date('Y-m-d H:i:s'); // Use client time or fallback to server time
    $itemsJson = $_POST['items'] ?? '[]';
    $items = json_decode($itemsJson, true);

    // Function to generate unique order number
    function generateOrderNumber($conn) {
        do {
            // Generate random 6-character code: UMH-XXXXXX
            $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789'; // Exclude confusing chars like 0, O, I, 1
            $code = '';
            for ($i = 0; $i < 6; $i++) {
                $code .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $orderNumber = 'UMH-' . $code;
            
            // Check if this code already exists
            $check = $conn->prepare("SELECT COUNT(*) as cnt FROM orders WHERE orderNumber = ?");
            if ($check) {
                $check->bind_param('s', $orderNumber);
                $check->execute();
                $result = $check->get_result();
                $row = $result->fetch_assoc();
                $check->close();
                if ($row['cnt'] == 0) {
                    return $orderNumber; // Unique code found
                }
            }
        } while (true);
    }

    // Ensure orders table has columns for storing customer details
    $colsNeeded = [
        'fullName' => "ALTER TABLE orders ADD COLUMN fullName varchar(200) NOT NULL DEFAULT ''",
        'phone' => "ALTER TABLE orders ADD COLUMN phone varchar(50) NOT NULL DEFAULT ''",
        'address' => "ALTER TABLE orders ADD COLUMN address varchar(400) NOT NULL DEFAULT ''",
        'paymentMethod' => "ALTER TABLE orders ADD COLUMN paymentMethod varchar(100) NOT NULL DEFAULT ''",
        'orderNumber' => "ALTER TABLE orders ADD COLUMN orderNumber varchar(20) UNIQUE DEFAULT NULL"
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

    // Generate unique order number
    $orderNumber = generateOrderNumber($conn);

    // Insert specifying orderID explicitly including customer details, orderNumber, and client timestamp
    $stmt = $conn->prepare("INSERT INTO orders (orderID, userID, totalAmount, fullName, phone, address, paymentMethod, orderNumber, orderDate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param("iidssssss", $nextId, $userID, $total, $custName, $custPhone, $custAddress, $paymentMethod, $orderNumber, $orderDateTime);

    if ($stmt->execute()) {
        $stmt->close();
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
        // SEND EMAIL NOTIFICATION USING BREVO REST API
        // ---------------------------------------------------------
        try {
            $emailData = array(
                'sender' => array(
                    'name' => EMAIL_FROM_NAME,
                    'email' => EMAIL_FROM
                ),
                'to' => array(
                    array(
                        'email' => $email,
                        'name' => $custName ?: 'Customer'
                    )
                ),
                'replyTo' => array(
                    'email' => EMAIL_REPLY_TO,
                    'name' => EMAIL_FROM_NAME
                ),
                'subject' => "Order Confirmation - Order " . $orderNumber,
                'htmlContent' => "
                    <h2>Order Confirmation</h2>
                    <p>Dear " . ($custName ?: "Customer") . ",</p>
                    <p>Thank you for shopping with UniMerch Hub! Your order has been placed successfully.</p>
                    <h3>Order Details:</h3>
                    <table style='border-collapse: collapse; width: 100%;'>
                        <tr><td style='border: 1px solid #ddd; padding: 8px;'><strong>Order Number:</strong></td><td style='border: 1px solid #ddd; padding: 8px;'>" . $orderNumber . "</td></tr>
                        <tr><td style='border: 1px solid #ddd; padding: 8px;'><strong>Total Amount:</strong></td><td style='border: 1px solid #ddd; padding: 8px;'>RM " . number_format($total, 2) . "</td></tr>
                        <tr><td style='border: 1px solid #ddd; padding: 8px;'><strong>Payment Method:</strong></td><td style='border: 1px solid #ddd; padding: 8px;'>" . $paymentMethod . "</td></tr>
                    </table>
                    <p style='margin-top: 20px;'>We will process your order shortly.</p>
                    <p>Best Regards,<br>UniMerch Hub Team</p>
                "
            );
            
            // Send via Brevo REST API
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => BREVO_API_URL,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode($emailData),
                CURLOPT_HTTPHEADER => array(
                    "api-key: " . BREVO_API_KEY,
                    "Content-Type: application/json",
                    "Accept: application/json"
                ),
            ));
            
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            
            if ($err) {
                error_log("Order {$orderNumber} email failed: {$err}");
            } else {
                $responseData = json_decode($response, true);
                if (!isset($responseData['messageId']) && !isset($responseData['id'])) {
                    error_log("Brevo API error for order {$orderNumber}: {$response}");
                }
            }
        } catch (Exception $e) {
            error_log("Order {$orderNumber} email exception: " . $e->getMessage());
        }
        // ---------------------------------------------------------

        // Clear the user's cart from database
        $clearCart = $conn->prepare("DELETE FROM cart WHERE userID = ?");
        if ($clearCart) {
            $clearCart->bind_param('i', $userID);
            $clearCart->execute();
            $clearCart->close();
        }

        echo json_encode(['success' => true, 'orderID' => $nextId, 'orderNumber' => $orderNumber]);
    } else {
        echo json_encode(['success' => false, 'message' => $conn->error]);
    }
}
?>
