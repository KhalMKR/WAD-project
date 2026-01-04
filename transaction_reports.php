<?php
session_start();
// Admin-only access
if (!isset($_SESSION['userType']) || $_SESSION['userType'] !== 'admin') {
    header('Location: login.html');
    exit();
}
// admin flag
$isAdmin = isset($_SESSION['userType']) && $_SESSION['userType'] === 'admin';
include 'includes/db.php';

// Sanitize and validate filter input
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'daily';
$allowed_filters = ['daily', 'monthly', 'all'];
if (!in_array($filter, $allowed_filters)) {
    $filter = 'daily'; // Default to safe value
}

// Build query
if ($filter === 'daily') {
    $sql = "SELECT orderID, orderNumber, userID, fullName, phone, address, paymentMethod, totalAmount, orderDate FROM orders WHERE DATE(orderDate) = CURDATE() ORDER BY orderDate DESC";
} elseif ($filter === 'monthly') {
    $sql = "SELECT orderID, orderNumber, userID, fullName, phone, address, paymentMethod, totalAmount, orderDate FROM orders WHERE MONTH(orderDate)=MONTH(CURDATE()) AND YEAR(orderDate)=YEAR(CURDATE()) ORDER BY orderDate DESC";
} else {
    $sql = "SELECT orderID, orderNumber, userID, fullName, phone, address, paymentMethod, totalAmount, orderDate FROM orders ORDER BY orderDate DESC";
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
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f5f5f5; color: #333; }
        .container { display: flex; min-height: 100vh; }
        .sidebar { width: 250px; background-color: #2c3e50; color: white; padding: 20px; position: fixed; height: 100vh; overflow-y: auto; }
        .sidebar h1 { font-size: 24px; margin-bottom: 30px; text-align: center; }
        .nav-menu { list-style: none; }
        .nav-menu li { margin-bottom: 15px; }
        .nav-menu a { color: #ecf0f1; text-decoration: none; display: block; padding: 12px 15px; border-radius: 5px; transition: background-color 0.3s; }
        .nav-menu a:hover { background-color: #34495e; }
        .nav-menu a.active { background-color: #3498db; }
        .main-content { margin-left: 250px; flex: 1; padding: 30px; }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .section { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 30px; }
        .controls { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #ecf0f1; }
        .controls label { display: flex; align-items: center; gap: 6px; cursor: pointer; }
        .btn { padding: 10px 16px; border: none; border-radius: 5px; cursor: pointer; font-weight: 600; transition: all 0.3s; }
        .btn-primary { background-color: #3498db; color: white; }
        .btn-primary:hover { background-color: #2980b9; }
        .btn-success { background-color: #27ae60; color: white; }
        .btn-success:hover { background-color: #229954; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { padding: 15px; text-align: left; border-bottom: 1px solid #ecf0f1; }
        th { background-color: #f8f9fa; font-weight: 600; color: #2c3e50; }
        tr:hover { background-color: #f8f9fa; }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <h1>Dashboard</h1>
            <nav>
                <ul class="nav-menu">
                    <li><a href="backend_8sp/index.php">🏠 Home</a></li>
                    <li><a href="backend_8sp/manage_members.php">👥 Members</a></li>
                    <li><a href="manage_products.php">📦 Products & Prices</a></li>
                    <li><a href="transaction_reports.php" class="active">📊 Transaction Reports</a></li>
                    <li><a href="backend_8sp/logout.php">🚪 Logout</a></li>
                </ul>
            </nav>
        </aside>

        <div class="main-content">
            <div class="header">
                <h1>Transaction Reports</h1>
            </div>

            <div class="section">
                <form method="get" class="controls">
                    <label><input type="radio" name="filter" value="daily" <?php echo $filter==='daily' ? 'checked' : ''; ?>> Daily</label>
                    <label><input type="radio" name="filter" value="monthly" <?php echo $filter==='monthly' ? 'checked' : ''; ?>> Monthly</label>
                    <label><input type="radio" name="filter" value="all" <?php echo $filter==='all' ? 'checked' : ''; ?>> All</label>
                    <button type="submit" class="btn btn-primary">Apply Filter</button>
                </form>

                <form method="post" action="transaction_reports_pdf.php" style="margin-bottom: 20px;">
                    <input type="hidden" name="filter" value="<?php echo htmlspecialchars($filter); ?>">
                    <?php if ($isAdmin): ?>
                        <button type="submit" name="format" value="pdf" class="btn btn-success">📄 Export PDF</button>
                        <button type="submit" name="format" value="csv" class="btn btn-success">📊 Export CSV</button>
                    <?php else: ?>
                        <button type="button" class="btn" disabled title="Only admins may export reports">Export (admin only)</button>
                    <?php endif; ?>
                </form>

                <h2 style="margin-bottom: 15px;">Results (<?php echo count($orders); ?> orders)</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Order Number</th>
                            <th>Customer Name</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Payment Method</th>
                            <th>Total Amount</th>
                            <th>Order Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $o): 
                            $orderNum = $o['orderNumber'] ?? 'UMH-' . str_pad($o['orderID'], 6, '0', STR_PAD_LEFT);
                        ?>
                        <tr>
                            <td><?php echo htmlspecialchars($orderNum); ?></td>
                            <td><?php echo htmlspecialchars($o['fullName'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($o['phone'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($o['address'] ?? 'N/A'); ?></td>
                            <td><?php echo htmlspecialchars($o['paymentMethod'] ?? 'N/A'); ?></td>
                            <td>RM <?php echo number_format((float)$o['totalAmount'],2); ?></td>
                            <td><?php echo htmlspecialchars($o['orderDate']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
