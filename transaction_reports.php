<?php
session_start();
// Admin-only access
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'admin') {
    header('Location: login.html');
    exit();
}
// admin flag
$isAdmin = isset($_SESSION['userType']) && $_SESSION['userType'] === 'admin';
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
    <style>
        .container{max-width:1200px;margin:30px auto;padding:20px}
        .header{display:flex;align-items:center;gap:16px;margin-bottom:18px}
        .btn{padding:8px 12px;border-radius:6px;text-decoration:none;display:inline-block}
        .btn-primary{background:#3498db;color:#fff}
        .section{background:#fff;padding:18px;border-radius:8px;box-shadow:0 2px 6px rgba(0,0,0,0.06)}
        table{width:100%;border-collapse:collapse;margin-top:12px}
        th,td{padding:12px;border-bottom:1px solid #eef2f5;text-align:left}
        input,button,label{padding:8px;border-radius:6px;border:1px solid #ddd}
        form.controls{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <a href="backend_8sp/index.php" class="btn btn-primary">← Back to Dashboard</a>
            <h1>Transaction Reports</h1>
        </div>

        <div class="section">
            <form method="get" class="controls">
        <label><input type="radio" name="filter" value="daily" <?php echo $filter==='daily' ? 'checked' : ''; ?>> Daily</label>
        <label><input type="radio" name="filter" value="monthly" <?php echo $filter==='monthly' ? 'checked' : ''; ?>> Monthly</label>
        <label><input type="radio" name="filter" value="all" <?php echo $filter==='all' ? 'checked' : ''; ?>> All</label>
            <button type="submit">Apply</button>
        </form>

    <form method="post" action="transaction_reports_pdf.php">
        <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
        <?php if ($isAdmin): ?>
            <button type="submit" name="format" value="pdf">Export PDF</button>
            <button type="submit" name="format" value="csv">Export CSV</button>
        <?php else: ?>
            <button type="button" disabled title="Only admins may export reports">Export (admin only)</button>
        <?php endif; ?>
    </form>

    <h2>Results (<?php echo count($orders); ?>)</h2>
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
        </div>
    </div>

</body>
</html>
