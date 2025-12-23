<?php
session_start();
include 'db.php';

if (!isset($_SESSION['userID'])) {
    header('Location: login.html');
    exit();
}

$userID = $_SESSION['userID'];

function pickFirst($row, $candidates) {
    foreach ($candidates as $c) {
        if (isset($row[$c])) return $row[$c];
    }
    return null;
}

// Try a set of queries to be resilient to schema differences
$queries = [
    "SELECT * FROM orders WHERE userID = ? ORDER BY orderID DESC",
    "SELECT * FROM orders WHERE user_id = ? ORDER BY id DESC",
    "SELECT * FROM orders WHERE userid = ? ORDER BY id DESC",
    "SELECT * FROM orders WHERE userID = ? ORDER BY id DESC",
    "SELECT * FROM orders WHERE customer_id = ? ORDER BY id DESC"
];

$stmt = null;
foreach ($queries as $q) {
    $p = $conn->prepare($q);
    if ($p) {
        $stmt = $p;
        break;
    }
}

if (!$stmt) {
    echo "<p>Unable to prepare order query. Please check the database schema.</p>";
    exit();
}

$stmt->bind_param('i', $userID);
$stmt->execute();
$res = $stmt->get_result();

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Order History</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <h1>Order History</h1>
    <?php if ($res && $res->num_rows > 0): ?>
        <ul>
            <?php while ($order = $res->fetch_assoc()):
                $orderId = pickFirst($order, ['orderID','id','order_id','orderId']);
                $total = pickFirst($order, ['totalAmount','total','total_amount','amount','grand_total','total_price','price']);
                $date = pickFirst($order, ['created_at','order_date','date','created']);
                if ($total !== null) $totalFmt = '$' . number_format((float)$total, 2);
                else $totalFmt = 'Total unavailable';
            ?>
            <li>
                <strong>Order #<?php echo htmlspecialchars($orderId ?: 'N/A'); ?></strong>
                - <?php echo htmlspecialchars($totalFmt); ?>
                <?php if ($date): ?> - <?php echo htmlspecialchars($date); ?><?php endif; ?>
                - <a href="orderdetails.php?order=<?php echo urlencode($orderId); ?>">View details</a>
            </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p>No orders found.</p>
    <?php endif; ?>

</body>
</html>
