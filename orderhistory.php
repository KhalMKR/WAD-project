<?php
session_start();
include 'db.php';

// Redirect admin users back to admin dashboard
if (isset($_SESSION['userType']) && $_SESSION['userType'] === 'admin') {
    header('Location: ./backend_8sp/index.php');
    exit();
}

if (!isset($_SESSION['userID'])) {
    header('Location: login.html');
    exit();
}

$userID = $_SESSION['userID'];
$isLoggedIn = isset($_SESSION['email']) && isset($_SESSION['fullName']) && isset($_SESSION['userID']);
$userName = $isLoggedIn ? $_SESSION['fullName'] : '';
$userType = $isLoggedIn ? $_SESSION['userType'] : '';

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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - UniMerch Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .order-history-container {
            max-width: 900px;
            margin: 30px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .page-header {
            border-bottom: 2px solid #f5f5f5;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .page-header h1 {
            margin: 0;
            color: #333;
            font-size: 28px;
            font-weight: 700;
        }
        .order-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .order-item {
            background: #f9f9f9;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 15px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .order-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
        }
        .order-id {
            font-size: 18px;
            font-weight: 700;
            color: #7742cc;
        }
        .order-total {
            font-size: 20px;
            font-weight: 700;
            color: #333;
        }
        .order-date {
            color: #666;
            font-size: 14px;
            margin-bottom: 15px;
        }
        .view-details-btn {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: opacity 0.2s;
        }
        .view-details-btn:hover {
            opacity: 0.9;
        }
        .no-orders {
            text-align: center;
            padding: 60px 20px;
            color: #999;
        }
        .no-orders-icon {
            font-size: 64px;
            color: #ddd;
            margin-bottom: 20px;
        }
        .cart-count-badge {
            background: #ff4757;
            color: white;
            padding: 2px 8px;
            border-radius: 50%;
            font-size: 12px;
            margin-left: 5px;
            font-weight: bold;
        }
        .nav-links {
            display: flex;
            align-items: center;
            gap: 20px;
        }
        .nav-links a {
            color: white;
            text-decoration: none;
            font-weight: 500;
        }
        /* Profile Navigation Tabs */
        .profile-nav {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 0;
            display: flex;
            justify-content: center;
            gap: 0;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }
        .profile-nav a {
            color: white;
            text-decoration: none;
            padding: 15px 40px;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s;
            position: relative;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .profile-nav a:hover {
            background: rgba(255, 255, 255, 0.15);
        }
        .profile-nav a.active {
            background: rgba(255, 255, 255, 0.25);
            border-bottom: 3px solid white;
        }
        @media (max-width: 768px) {
            .profile-nav a {
                padding: 12px 20px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>
    <navbar>
        <a href="index.php"><img src="assets/images/logo.png" alt="Logo" class="logo"></a>
        <div id="authArea" class="nav-links">
            <a href="cart.php" style="display: flex; align-items: center; gap: 5px;">
                <span class="material-symbols-outlined">shopping_cart</span>
                Cart <span id="cartCount" class="cart-count-badge">0</span>
            </a>

            <?php if ($isLoggedIn): ?>
                <a href="profile.php"><span style="color: white;">
                    Welcome, <strong><?php echo htmlspecialchars($userName); ?></strong>
                </span></a>
                <?php if ($userType === 'admin'): ?>
                    <a href="backend_8sp/index.php" class="login-btn">Admin</a>
                <?php endif; ?>
                <a href="logout.php" class="login-btn">Logout</a>
            <?php else: ?>
                <a href="login.html" class="login-btn">Login/Register</a>
            <?php endif; ?>
        </div>
    </navbar>
    <navbar class="profile-nav">
        <a href="profile.php">
            <span class="material-symbols-outlined" style="font-size: 20px;">person</span>
            My Profile
        </a>
        <a href="orderhistory.php" class="active">
            <span class="material-symbols-outlined" style="font-size: 20px;">receipt_long</span>
            Orders
        </a>
    </navbar>

    <div class="container">
        <div class="order-history-container">
            <div class="page-header">
                <h1>My Order History</h1>
            </div>

            <?php if ($res && $res->num_rows > 0): ?>
                <ul class="order-list">
                    <?php while ($order = $res->fetch_assoc()):
                        $orderId = pickFirst($order, ['orderID','id','order_id','orderId']);
                        $total = pickFirst($order, ['totalAmount','total','total_amount','amount','grand_total','total_price','price']);
                        $date = pickFirst($order, ['created_at','order_date','date','created']);
                        if ($total !== null) $totalFmt = 'RM ' . number_format((float)$total, 2);
                        else $totalFmt = 'Total unavailable';
                    ?>
                    <li class="order-item">
                        <div class="order-header">
                            <span class="order-id">Order #<?php echo htmlspecialchars($orderId ?: 'N/A'); ?></span>
                            <span class="order-total"><?php echo htmlspecialchars($totalFmt); ?></span>
                        </div>
                        <?php if ($date): ?>
                            <div class="order-date">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">calendar_today</span>
                                <?php echo htmlspecialchars($date); ?>
                            </div>
                        <?php endif; ?>
                        <a href="orderdetails.php?order=<?php echo urlencode($orderId); ?>" class="view-details-btn">
                            View Details
                        </a>
                    </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <div class="no-orders">
                    <div class="no-orders-icon">
                        <span class="material-symbols-outlined" style="font-size: 64px;">receipt_long</span>
                    </div>
                    <h2>No Orders Yet</h2>
                    <p>You haven't placed any orders yet. Start shopping to see your order history here!</p>
                    <a href="index.php" class="view-details-btn" style="margin-top: 20px;">Browse Products</a>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="utils.js"></script>
    <script>
        // Update cart count in navbar
        updateCartCount();
    </script>
</body>
</html>
