<?php
session_start();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['email']) && isset($_SESSION['fullName']);
$userName = $isLoggedIn ? $_SESSION['fullName'] : '';
$userType = $isLoggedIn ? $_SESSION['userType'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - UniMerch Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* Cart Specific Layout */
        .cart-layout {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 30px;
            margin-top: 30px;
        }

        .cart-items {
            background: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .cart-header {
            border-bottom: 2px solid #f5f5f5;
            padding-bottom: 10px;
            margin-bottom: 20px;
            font-size: 24px;
            font-weight: 600;
        }

        .cart-item {
            display: flex;
            align-items: center;
            padding: 15px 0;
            border-bottom: 1px solid #eee;
        }

        .cart-item-img {
            width: 80px;
            height: 80px;
            background: #eee;
            border-radius: 4px;
            margin-right: 20px;
            object-fit: cover;
        }

        .cart-item-details {
            flex-grow: 1;
        }

        .cart-item-name {
            font-weight: 600;
            font-size: 18px;
            color: #333;
        }

        .cart-item-price {
            color: #007bff;
            font-weight: bold;
        }

        .quantity-controls {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .qty-btn {
            background: #f0f0f0;
            border: none;
            width: 30px;
            height: 30px;
            cursor: pointer;
            border-radius: 4px;
            transition: background 0.2s;
        }

        .qty-btn:hover {
            background: #ddd;
        }

        .summary-card {
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            height: fit-content;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 16px;
        }

        .total-row {
            border-top: 2px solid #eee;
            padding-top: 15px;
            font-weight: bold;
            font-size: 20px;
            color: #7742cc;
        }

        .checkout-btn {
            width: 100%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px;
            border-radius: 5px;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            margin-top: 20px;
            transition: opacity 0.3s;
        }

        .checkout-btn:hover {
            opacity: 0.9;
        }

        @media (max-width: 768px) {
            .cart-layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <navbar>
        <a href="index.php"><img src="assets/images/logo.png" alt="Logo" class="logo"></a>
        <div id="authArea">
            <?php if ($isLoggedIn): ?>
                <div style="display:flex; align-items:center; gap:15px;">
                    <span style="color: white;">Welcome, <strong><?php echo htmlspecialchars($userName); ?></strong></span>
                    <a href="logout.php" class="login-btn">Logout</a>
                </div>
            <?php else: ?>
                <a href="login.html" class="login-btn">Login/Register</a>
            <?php endif; ?>
        </div>
    </navbar>

    <div class="container">
        <div class="cart-layout">
            <div class="cart-items">
                <div class="cart-header">Shopping Cart</div>
                
                <div class="cart-item">
                    <img src="assets/images/hoodie.png" alt="Hoodie" class="cart-item-img">
                    <div class="cart-item-details">
                        <div class="cart-item-name">UNIMAS Hoodie</div>
                        <div class="cart-item-price">RM 85.00</div>
                    </div>
                    <div class="quantity-controls">
                        <button class="qty-btn">-</button>
                        <span>1</span>
                        <button class="qty-btn">+</button>
                    </div>
                </div>

                <div class="cart-item">
                    <img src="assets/images/lanyard.png" alt="Lanyard" class="cart-item-img">
                    <div class="cart-item-details">
                        <div class="cart-item-name">FCSIT Lanyard</div>
                        <div class="cart-item-price">RM 15.00</div>
                    </div>
                    <div class="quantity-controls">
                        <button class="qty-btn">-</button>
                        <span>2</span>
                        <button class="qty-btn">+</button>
                    </div>
                </div>
            </div>

            <div class="summary-card">
                <h3 style="margin-bottom: 20px; font-family: 'Poppins', sans-serif;">Order Summary</h3>
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>RM 115.00</span>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <span style="color: #28a745; font-weight: 600;">FREE</span>
                </div>
                <div class="total-row summary-row">
                    <span>Total</span>
                    <span>RM 115.00</span>
                </div>
                
                <button class="checkout-btn" onclick="handleCheckout()">PROCEED TO CHECKOUT</button>
                <a href="index.php" style="display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none; font-size: 14px;">Continue Shopping</a>
            </div>
        </div>
    </div>

    <script>
        function handleCheckout() {
            // Check if user is logged in via PHP variable converted to JS
            const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;
            if (!isLoggedIn) {
                alert("Please login first to proceed with checkout.");
                window.location.href = "login.html";
            } else {
                alert("Proceeding to payment gateway...");
                // Redirect to payment page here
            }
        }
    </script>
</body>
</html>