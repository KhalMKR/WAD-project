<?php
session_start();
include 'db.php';

if (!isset($_SESSION['userID'])) {
    header('Location: login.html');
    exit();
}

$userID = $_SESSION['userID'];
$orderParam = $_GET['order'] ?? null;
if (!$orderParam) {
    echo "<p>No order specified.</p>";
    exit();
}

function tryFetchOrder($conn, $orderId) {
    $idCols = ['orderID','id','order_id','orderId'];
    foreach ($idCols as $col) {
        $sql = "SELECT * FROM orders WHERE $col = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('s', $orderId);
            $stmt->execute();
            $res = $stmt->get_result();
            if ($res && $res->num_rows === 1) return $res->fetch_assoc();
        }
    }
    return null;
}

function pickFirst($row, $candidates) {
    foreach ($candidates as $c) {
        if (isset($row[$c])) return $row[$c];
    }
    return null;
}

function resolveCategory($conn, $item) {
    // If productID exists try to read from products table
    $pid = null;
    if (isset($item['productID'])) $pid = $item['productID'];
    if (!$pid && isset($item['productId'])) $pid = $item['productId'];
    if ($pid) {
        $q = $conn->prepare("SELECT category FROM products WHERE productID = ? LIMIT 1");
        if ($q) {
            $q->bind_param('i', $pid);
            $q->execute();
            $r = $q->get_result();
            if ($r && $r->num_rows === 1) {
                $row = $r->fetch_assoc();
                $q->close();
                return $row['category'] ?: 'Unspecified';
            }
            $q->close();
        }
    }

    // Fallback: infer from item name
    $name = '';
    if (isset($item['name'])) $name = $item['name'];
    $ln = strtolower($name);
    if (strpos($ln, 'lanyard') !== false || strpos($ln, 'bracelet') !== false) return 'Accessory';

    // Try candidate field
    $c = pickFirst($item, ['category','cat']);
    return $c ?: 'Unspecified';
}

$order = tryFetchOrder($conn, $orderParam);
if (!$order) {
    echo "<p>Order not found.</p>";
    exit();
}

$orderUser = pickFirst($order, ['userID','user_id','userid','customer_id']);
if ($orderUser && (int)$orderUser !== (int)$userID) {
    echo "<p>You do not have permission to view this order.</p>";
    exit();
}
// Pull stored customer meta if present
$custName = pickFirst($order, ['fullName','full_name','name']);
$custPhone = pickFirst($order, ['phone','telephone']);
$custAddress = pickFirst($order, ['address']);
$custPayment = pickFirst($order, ['paymentMethod','payment_method','payment']);

// Fetch items from dedicated `order_items` table if present
$items = [];
$checkItems = $conn->prepare("SELECT COUNT(*) as cnt FROM information_schema.tables WHERE table_schema = ? AND table_name = 'order_items'");
if ($checkItems) {
    $dbn = $dbname;
    $checkItems->bind_param('s', $dbn);
    $checkItems->execute();
    $cr = $checkItems->get_result();
    if ($cr && ($r = $cr->fetch_assoc()) && intval($r['cnt']) > 0) {
        $q = $conn->prepare("SELECT * FROM order_items WHERE orderID = ?");
        if ($q) {
            $q->bind_param('s', $orderParam);
            $q->execute();
            $res = $q->get_result();
            if ($res && $res->num_rows > 0) {
                while ($row = $res->fetch_assoc()) $items[] = $row;
            }
            $q->close();
        }
    }
    $checkItems->close();
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order Details</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Order Details for Order #<?php echo htmlspecialchars(pickFirst($order, ['orderID','id','order_id','orderId']) ?: $orderParam); ?></h1>

    <section style="margin-bottom:18px;">
        <h3>Customer</h3>
        <div><strong>Name:</strong> <?php echo htmlspecialchars($custName ?: ($_SESSION['fullName'] ?? '')); ?></div>
        <div><strong>Phone:</strong> <?php echo htmlspecialchars($custPhone ?: ''); ?></div>
        <div><strong>Address:</strong> <?php echo nl2br(htmlspecialchars($custAddress ?: '')); ?></div>
        <div><strong>Payment:</strong> <?php echo htmlspecialchars($custPayment ?: ''); ?></div>
    </section>

    <?php if (count($items) === 0): ?>
        <p>No itemized details found for this order.</p>
        <p>If your database uses a different table or column names, please update the code accordingly.</p>
    <?php else: ?>
        <ul>
            <?php foreach ($items as $it):
                $name = pickFirst($it, ['name','product_name','title','item_name']) ?: 'Product';
                $qty = pickFirst($it, ['quantity','qty','amount']) ?? $it['quantity'] ?? 1;
                $price = pickFirst($it, ['price','unit_price','cost','item_price']) ?? $it['price'] ?? null;
                $category = resolveCategory($conn, $it);
                $line = htmlspecialchars($qty . ' x ' . ($name) . ' (' . $category . ')' );
                if ($price !== null) $line .= ' @ RM ' . number_format((float)$price,2);
            ?>
            <li><?php echo $line; ?></li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>

    <p><a href="orderhistory.php">Back to Order History</a></p>
</body>
</html>
