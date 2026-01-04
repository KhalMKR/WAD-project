<?php
session_start();
include 'includes/db.php';
include 'config.php';


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
    // First try to match by orderNumber
    $stmt = $conn->prepare("SELECT * FROM orders WHERE orderNumber = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param('s', $orderId);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($res && $res->num_rows === 1) return $res->fetch_assoc();
    }
    
    // Fallback to orderID for backward compatibility
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

// Get the actual numeric orderID from the order record
$actualOrderID = pickFirst($order, ['orderID','id','order_id','orderId']);

// DEBUG: Uncomment to see what's being queried
echo "<!-- DEBUG: Order Param: $orderParam, Actual OrderID: $actualOrderID -->";

// Fetch items from dedicated `order_items` table if present
$items = [];
$q = $conn->prepare("SELECT * FROM order_items WHERE orderID = ?");
if ($q) {
    $q->bind_param('i', $actualOrderID);
    $q->execute();
    $res = $q->get_result();
    if ($res && $res->num_rows > 0) {
        while ($row = $res->fetch_assoc()) {
            $items[] = $row;
        }
    }
    $q->close();
}

$isLoggedIn = isset($_SESSION['email']) && isset($_SESSION['fullName']) && isset($_SESSION['userID']);
$userName = $isLoggedIn ? $_SESSION['fullName'] : '';
$userType = $isLoggedIn ? $_SESSION['userType'] : '';

$orderIdDisplay = pickFirst($order, ['orderID','id','order_id','orderId']) ?: $orderParam;
$orderNumber = $order['orderNumber'] ?? 'UMH-' . str_pad($orderIdDisplay, 6, '0', STR_PAD_LEFT);
$orderDate = pickFirst($order, ['created_at','order_date','date','created']);
$orderTotal = pickFirst($order, ['totalAmount','total','total_amount','amount','grand_total','total_price','price']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - UniMerch Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .order-details-container {
            max-width: 900px;
            margin: 30px auto;
        }
        .order-header-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }
        .order-header-card h1 {
            margin: 0 0 10px 0;
            font-size: 28px;
            font-weight: 700;
        }
        .order-meta {
            display: flex;
            gap: 30px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        .order-meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .card {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .card h2 {
            margin: 0 0 20px 0;
            color: #333;
            font-size: 20px;
            font-weight: 700;
            border-bottom: 2px solid #f5f5f5;
            padding-bottom: 10px;
        }
        .info-row {
            display: grid;
            grid-template-columns: 140px 1fr;
            padding: 12px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        .info-row:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #666;
        }
        .info-value {
            color: #333;
        }
        .items-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .item-card {
            background: #f9f9f9;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .item-info {
            flex: 1;
        }
        .item-name {
            font-weight: 600;
            color: #333;
            font-size: 16px;
            margin-bottom: 5px;
        }
        .item-details {
            color: #666;
            font-size: 14px;
        }
        .item-price {
            font-size: 18px;
            font-weight: 700;
            color: #7742cc;
            margin-left: 20px;
        }
        .no-items {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }
        .back-btn {
            background: white;
            border: 2px solid #7742cc;
            color: #7742cc;
            padding: 12px 24px;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
        }
        .back-btn:hover {
            background: #7742cc;
            color: white;
        }
        .total-summary {
            background: linear-gradient(135deg, #f5f5f5 0%, #e9e9e9 100%);
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 22px;
            font-weight: 700;
            color: #333;
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
        @media (max-width: 768px) {
            .order-meta {
                flex-direction: column;
                gap: 15px;
            }
            .info-row {
                grid-template-columns: 1fr;
                gap: 5px;
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
                <a href="orderhistory.php">Orders</a>
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

    <div class="container">
        <div class="order-details-container">
            <div class="order-header-card">
                <h1>
                    <span class="material-symbols-outlined" style="font-size: 32px; vertical-align: middle;">receipt_long</span>
                    Order <?php echo htmlspecialchars($orderNumber); ?>
                </h1>
                <div class="order-meta">
                    <?php if ($orderDate): ?>
                    <div class="order-meta-item">
                        <span class="material-symbols-outlined">calendar_today</span>
                        <span><?php echo htmlspecialchars($orderDate); ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($orderTotal !== null): ?>
                    <div class="order-meta-item">
                        <span class="material-symbols-outlined">payments</span>
                        <span>Total: RM <?php echo number_format((float)$orderTotal, 2); ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <h2>
                    <span class="material-symbols-outlined" style="font-size: 24px; vertical-align: middle;">person</span>
                    Customer Information
                </h2>
                <div class="info-row">
                    <div class="info-label">Name:</div>
                    <div class="info-value"><?php echo htmlspecialchars($custName ?: ($_SESSION['fullName'] ?? 'N/A')); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Phone:</div>
                    <div class="info-value"><?php echo htmlspecialchars($custPhone ?: 'N/A'); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Address:</div>
                    <div class="info-value"><?php echo nl2br(htmlspecialchars($custAddress ?: 'N/A')); ?></div>
                </div>
                <div class="info-row">
                    <div class="info-label">Payment Method:</div>
                    <div class="info-value"><?php echo htmlspecialchars($custPayment ?: 'N/A'); ?></div>
                </div>
            </div>

            <div class="card">
                <h2>
                    <span class="material-symbols-outlined" style="font-size: 24px; vertical-align: middle;">shopping_bag</span>
                    Order Items
                </h2>
                
                <?php if (count($items) === 0): ?>
                    <div class="no-items">
                        <span class="material-symbols-outlined" style="font-size: 48px; color: #ddd;">inventory_2</span>
                        <p>No itemized details found for this order.</p>
                        <p style="font-size: 14px; color: #bbb;">If your database uses a different table or column names, please update the code accordingly.</p>
                    </div>
                <?php else: ?>
                    <ul class="items-list">
                        <?php 
                        $subtotal = 0;
                        foreach ($items as $it):
                            $name = pickFirst($it, ['name','product_name','title','item_name']) ?: 'Product';
                            $qty = pickFirst($it, ['quantity','qty','amount']) ?? $it['quantity'] ?? 1;
                            $price = pickFirst($it, ['price','unit_price','cost','item_price']) ?? $it['price'] ?? null;
                            $category = resolveCategory($conn, $it);
                            if ($price !== null) {
                                $lineTotal = (float)$price * (int)$qty;
                                $subtotal += $lineTotal;
                            }
                        ?>
                        <li class="item-card">
                            <div class="item-info">
                                <div class="item-name"><?php echo htmlspecialchars($name); ?></div>
                                <div class="item-details">
                                    Category: <?php echo htmlspecialchars($category); ?> • 
                                    Quantity: <?php echo htmlspecialchars($qty); ?>
                                    <?php if ($price !== null): ?>
                                        • Unit Price: RM <?php echo number_format((float)$price, 2); ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if ($price !== null): ?>
                            <div class="item-price">
                                RM <?php echo number_format($lineTotal, 2); ?>
                            </div>
                            <?php endif; ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    
                    <?php if ($subtotal > 0): ?>
                    <div class="total-summary">
                        <div class="total-row">
                            <span>Total Amount:</span>
                            <span>RM <?php echo number_format($subtotal, 2); ?></span>
                        </div>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <a href="orderhistory.php" class="back-btn">
                <span class="material-symbols-outlined">arrow_back</span>
                Back to Order History
            </a>
        </div>
    </div>

    <script src="assets/js/utils.js"></script>
    <script>
        // Update cart count in navbar
        updateCartCount();
    </script>
</body>
</html>
