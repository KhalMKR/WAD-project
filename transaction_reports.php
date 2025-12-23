<?php
session_start();
// Admin-only access
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'admin') {
    header('Location: login.html');
    exit();
}
// finer-grained
$isSuperAdmin = isset($_SESSION['isSuperAdmin']) && $_SESSION['isSuperAdmin'];
include 'db.php';

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'daily';

// Build query
if ($filter === 'daily') {
    $sql = "SELECT orderID, userID, totalAmount, orderDate FROM orders WHERE DATE(orderDate) = CURDATE() ORDER BY orderDate DESC";
} elseif ($filter === 'monthly') {
    $sql = "SELECT orderID, userID, totalAmount, orderDate FROM orders WHERE MONTH(orderDate)=MONTH(CURDATE()) AND YEAR(orderDate)=YEAR(CURDATE()) ORDER BY orderDate DESC";
} else {
    $sql = "SELECT orderID, userID, totalAmount, orderDate FROM orders ORDER BY orderDate DESC";
}

$orders = [];
$res = $conn->query($sql);
if ($res) {
    while ($r = $res->fetch_assoc()) $orders[] = $r;
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Transaction Reports</title>
    <link rel="stylesheet" href="style.css">
    <style>table{width:100%;border-collapse:collapse}th,td{padding:8px;border:1px solid #ddd}</style>
</head>
<body>
    <h1>Transaction Reports</h1>

    <form method="get">
        <label><input type="radio" name="filter" value="daily" <?php echo $filter==='daily' ? 'checked' : ''; ?>> Daily</label>
        <label><input type="radio" name="filter" value="monthly" <?php echo $filter==='monthly' ? 'checked' : ''; ?>> Monthly</label>
        <label><input type="radio" name="filter" value="all" <?php echo $filter==='all' ? 'checked' : ''; ?>> All</label>
        <button type="submit">Apply</button>
    </form>

    <form method="post" action="transaction_reports_pdf.php">
        <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
        <?php if ($isSuperAdmin): ?>
            <button type="submit">Export to PDF / CSV</button>
        <?php else: ?>
            <button type="button" disabled title="Only super admins may export reports">Export (super admin only)</button>
        <?php endif; ?>
    </form>

    <h2>Results (<?php echo count($orders); ?>)</h2>
    <table>
        <thead><tr><th>Order ID</th><th>User ID</th><th>Total</th><th>Order Date</th></tr></thead>
        <tbody>
        <?php foreach ($orders as $o): ?>
            <tr>
                <td><?php echo (int)$o['orderID']; ?></td>
                <td><?php echo (int)$o['userID']; ?></td>
                <td>RM <?php echo number_format((float)$o['totalAmount'],2); ?></td>
                <td><?php echo htmlspecialchars($o['orderDate']); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>
