<?php
session_start();

// Redirect admin users back to admin dashboard
if (isset($_SESSION['userType']) && $_SESSION['userType'] === 'admin') {
    header('Location: ./backend_8sp/index.php');
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['email']) || !isset($_SESSION['fullName'])) {
    header("Location: login.html");
    exit();
}

$email = htmlspecialchars($_SESSION['email']);
$fullName = htmlspecialchars($_SESSION['fullName']);
$userType = htmlspecialchars($_SESSION['userType']);
$userID = isset($_SESSION['userID']) ? $_SESSION['userID'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile - UniMerch Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .profile-layout {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }
        .profile-card {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        .profile-header {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        .profile-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #7742cc;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            font-weight: bold;
        }
        .profile-title {
            flex: 1;
        }
        .profile-title h1 {
            margin: 0;
            color: #333;
            font-size: 24px;
        }
        .user-badge {
            display: inline-block;
            background: #7742cc;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-top: 8px;
        }
        .info-group {
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }
        .info-group:last-child {
            border-bottom: none;
        }
        .info-label {
            font-weight: 600;
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 5px;
        }
        .info-value {
            color: #333;
            font-size: 16px;
            word-break: break-word;
        }
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .btn {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            text-align: center;
            transition: background 0.3s;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
        }
        .btn-primary {
            background: #7742cc;
            color: white;
        }
        .btn-primary:hover {
            background: #5e33a3;
        }
        .btn-secondary {
            background: #e0e0e0;
            color: #333;
        }
        .btn-secondary:hover {
            background: #d0d0d0;
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
        .cart-count-badge {
            background: #ff4757;
            color: white;
            padding: 2px 8px;
            border-radius: 50%;
            font-size: 12px;
            margin-left: 5px;
            font-weight: bold;
        }
        .transactions-section h2 {
            color: #333;
            margin-top: 0;
            margin-bottom: 20px;
        }
        .transaction-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }
        .transaction-item:last-child {
            border-bottom: none;
        }
        .transaction-info h4 {
            margin: 0 0 5px 0;
            color: #333;
        }
        .transaction-date {
            font-size: 12px;
            color: #999;
        }
        .transaction-amount {
            font-weight: bold;
            color: #7742cc;
            font-size: 18px;
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }
        .empty-state p {
            margin: 10px 0;
        }
        /* Action Cards */
        .action-card {
            background: #f9f9f9;
            border: 1px solid #eee;
            border-radius: 8px;
            padding: 15px;
            text-decoration: none;
            display: block;
            transition: all 0.3s;
        }
        .action-card:hover {
            background: white;
            border-color: #7742cc;
            box-shadow: 0 2px 8px rgba(119, 66, 204, 0.1);
            transform: translateX(5px);
        }
        .action-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .action-icon .material-symbols-outlined {
            font-size: 26px;
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
            .profile-layout {
                grid-template-columns: 1fr;
            }
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
        <div class="nav-links">
            <a href="cart.php" style="display: flex; align-items: center; gap: 5px;">
                <span class="material-symbols-outlined">shopping_cart</span>
                Cart <span id="cartCount" class="cart-count-badge">0</span>
            </a>
            <a href="profile.php"><span style="color: white;">
                Welcome, <strong><?php echo $fullName; ?></strong>
            </span></a>
            <a href="logout.php" class="login-btn">Logout</a>
        </div>
    </navbar>
    <navbar class="profile-nav">
        <a href="profile.php" class="active">
            <span class="material-symbols-outlined" style="font-size: 20px;">person</span>
            My Profile
        </a>
        <a href="orderhistory.php">
            <span class="material-symbols-outlined" style="font-size: 20px;">receipt_long</span>
            Orders
        </a>
    </navbar>
    <div class="container">
    <div class="profile-layout">
        <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar"><?php echo strtoupper(substr($fullName, 0, 1)); ?></div>
                    <div class="profile-title">
                        <h1><?php echo $fullName; ?></h1>
                        <span class="user-badge"><?php echo ucfirst($userType); ?></span>
                    </div>
                </div>

                <div class="info-group">
                    <div class="info-label">Email Address</div>
                    <div class="info-value"><?php echo $email; ?></div>
                </div>

                <div class="info-group">
                    <div class="info-label">Account Type</div>
                    <div class="info-value"><?php echo ucfirst($userType); ?></div>
                </div>

                <?php if (!empty($userID)): ?>
                <div class="info-group">
                    <div class="info-label">Member ID</div>
                    <div class="info-value"><?php echo $userID; ?></div>
                </div>
                <?php endif; ?>
            </div>

            <!-- Quick Actions Card -->
            <div class="profile-card">
                <h2 style="color: #333; margin-top: 0; margin-bottom: 20px;">Quick Actions</h2>
                
                <div style="display: flex; flex-direction: column; gap: 15px;">
                    <a href="orderhistory.php" class="action-card">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div class="action-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                                <span class="material-symbols-outlined">receipt_long</span>
                            </div>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; color: #333; margin-bottom: 3px;">View Orders</div>
                                <div style="font-size: 13px; color: #666;">Track your purchase history</div>
                            </div>
                            <span class="material-symbols-outlined" style="color: #ccc;">chevron_right</span>
                        </div>
                    </a>

                    <a href="cart.php" class="action-card">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div class="action-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                                <span class="material-symbols-outlined">shopping_cart</span>
                            </div>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; color: #333; margin-bottom: 3px;">Shopping Cart</div>
                                <div style="font-size: 13px; color: #666;">View and manage your cart</div>
                            </div>
                            <span class="material-symbols-outlined" style="color: #ccc;">chevron_right</span>
                        </div>
                    </a>

                    <a href="support.php" class="action-card">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div class="action-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                                <span class="material-symbols-outlined">support_agent</span>
                            </div>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; color: #333; margin-bottom: 3px;">Contact Support</div>
                                <div style="font-size: 13px; color: #666;">Get help with your orders</div>
                            </div>
                            <span class="material-symbols-outlined" style="color: #ccc;">chevron_right</span>
                        </div>
                    </a>

                    <a href="index.php" class="action-card">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div class="action-icon" style="background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%);">
                                <span class="material-symbols-outlined">storefront</span>
                            </div>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; color: #333; margin-bottom: 3px;">Continue Shopping</div>
                                <div style="font-size: 13px; color: #666;">Browse products and shop</div>
                            </div>
                            <span class="material-symbols-outlined" style="color: #ccc;">chevron_right</span>
                        </div>
                    </a>

                    <a href="logout.php" class="action-card">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div class="action-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                                <span class="material-symbols-outlined">logout</span>
                            </div>
                            <div style="flex: 1;">
                                <div style="font-weight: 600; color: #333; margin-bottom: 3px;">Logout</div>
                                <div style="font-size: 13px; color: #666;">Sign out of your account</div>
                            </div>
                            <span class="material-symbols-outlined" style="color: #ccc;">chevron_right</span>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </div>
    </div>

    <script src="assets/js/data.js"></script>
    <script src="assets/js/utils.js"></script>
    <script>
        // Update cart count in navbar
        updateCartCount();
    </script>
</body>
</html>
