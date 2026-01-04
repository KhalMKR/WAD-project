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

    // Insert specifying orderID explicitly including customer details and orderNumber
    $stmt = $conn->prepare("INSERT INTO orders (orderID, userID, totalAmount, fullName, phone, address, paymentMethod, orderNumber) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Prepare failed: ' . $conn->error]);
        exit;
    }
    $stmt->bind_param("iidsssss", $nextId, $userID, $total, $custName, $custPhone, $custAddress, $paymentMethod, $orderNumber);

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
        // SEND EMAIL NOTIFICATION USING PHPMAILER
        // ---------------------------------------------------------
        $mail->SMTPDebug = 2;
        try {
            $mail = new PHPMailer(true);
            
            // Server settings - Brevo SMTP
            $mail->isSMTP();
            $mail->Host       = SMTP_HOST;
            $mail->SMTPAuth   = true;
            $mail->Username   = SMTP_USERNAME;
            $mail->Password   = SMTP_PASSWORD;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = SMTP_PORT;
            
            // Recipients
            $mail->setFrom(EMAIL_FROM, EMAIL_FROM_NAME);
            $mail->addAddress($email, $custName ?: 'Customer');
            $mail->addReplyTo(EMAIL_REPLY_TO, 'Support');
            
            // Content
            $mail->isHTML(false);
            $mail->Subject = "Order Confirmation - Order " . $orderNumber;
            
            $message = "Dear " . ($custName ?: "Customer") . ",\n\n";
            $message .= "Thank you for shopping with UniMerch Hub! Your order has been placed successfully.\n\n";
            $message .= "Order Details:\n";
            $message .= "--------------------------------\n";
            $message .= "Order Number: " . $orderNumber . "\n";
            $message .= "Total Amount: RM " . number_format($total, 2) . "\n";
            $message .= "Payment Method: " . $paymentMethod . "\n";
            $message .= "--------------------------------\n\n";
            $message .= "We will process your order shortly.\n\n";
            $message .= "Best Regards,\nUniMerch Hub Team";
            
            $mail->Body = $message;
            
            // Send email (suppress errors to prevent breaking JSON response)
            @$mail->send();
        } catch (Exception $e) {
            // Silently fail - order still processes
            // You can log the error: error_log("Email error: {$mail->ErrorInfo}");
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
