<?php
session_start();

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
    <link rel="stylesheet" href="style.css">
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
        @media (max-width: 768px) {
            .profile-layout {
                grid-template-columns: 1fr;
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
                Cart
            </a>
            <a href="profile.php"><span style="color: white;">
                Welcome, <strong><?php echo $fullName; ?></strong>
            </span></a>
            <a href="logout.php" class="login-btn">Logout</a>
        </div>
    </navbar>
    <navbar>
        <a href="profile.php"><span style="color: white;">
            My Profile
        </span></a>
        <a href="orderhistory.php" style="margin-left:12px; color: white;">Orders</a>
    </navbar>
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

                <div class="button-group">
                    <a href="index.php" class="btn btn-secondary">Continue Shopping</a>
                    <a href="logout.php" class="btn btn-primary">Logout</a>
                </div>
            </div>

            <!-- Orders & Transactions Card -->
            <div class="profile-card transactions-section">
                <h2>Order History</h2>
                <div id="transactionsList">
                    <div class="empty-state">
                        <p style="font-size: 14px;">📦</p>
                        <p>No orders yet</p>
                        <p style="font-size: 12px;">Your past orders and transactions will appear here.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="data.js"></script>
</body>
</html>
