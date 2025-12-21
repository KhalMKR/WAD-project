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
    <title>UniMerch Hub - Shop</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* New style for the Add to Cart button */
        .add-to-cart-btn {
            width: 100%;
            background-color: #7742cc; /* Match brand color */
            color: white;
            border: none;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 10px;
            font-weight: 600;
            transition: background 0.3s;
        }
        .add-to-cart-btn:hover {
            background-color: #5e33a3;
        }
        .cart-count-badge {
            background: #ff4757;
            color: white;
            padding: 2px 8px;
            border-radius: 50%;
            font-size: 12px;
            margin-left: 5px;
        }
    </style>
</head>
<body>
    <navbar>
        <img src="assets/images/logo.png" alt="Logo" class="logo">
        <div id="authArea">
            <a href="cart.php" style="color: white; text-decoration: none; margin-right: 20px;">
                Cart <span id="cartCount" class="cart-count-badge">0</span>
            </a>
            
            <?php if ($isLoggedIn): ?>
                <span style="color: white; margin-right: 20px;">
                    Hi, <strong><?php echo htmlspecialchars($userName); ?></strong>
                </span>
                <a href="logout.php" class="login-btn">Logout</a>
            <?php else: ?>
                <a href="login.html" class="login-btn">Login/Register</a>
            <?php endif; ?>
        </div>
    </navbar>

    <div class="grid">
    <div class="product-card">
        <div class="product-image">
            <img src="assets/images/hoodie.png" alt="UNIMAS Hoodie" style="width:100%; height:100%; object-fit:cover;">
        </div>
        <div class="product-info">
            <div class="product-name">UNIMAS Hoodie</div>
            <div class="product-price">RM 85.00</div>
            <button class="add-to-cart-btn" onclick="addToCart('UNIMAS Hoodie', 85.00, 'assets/images/hoodie.png')">Add to Cart</button>
        </div>
    </div>

    <div class="product-card">
        <div class="product-image">
            <img src="assets/images/lanyard.png" alt="FCSIT Lanyard" style="width:100%; height:100%; object-fit:cover;">
        </div>
        <div class="product-info">
            <div class="product-name">FCSIT Lanyard</div>
            <div class="product-price">RM 15.00</div>
            <button class="add-to-cart-btn" onclick="addToCart('FCSIT Lanyard', 15.00, 'assets/images/lanyard.png')">Add to Cart</button>
        </div>
    </div>
</div>

<script>
    // Initialize Cart Logic
    function addToCart(name, price, image) {
        // Get existing cart from localStorage or start empty
        let cart = JSON.parse(localStorage.getItem('userCart')) || [];
        
        // Check if item already exists
        const existingItemIndex = cart.findIndex(item => item.name === name);
        
        if (existingItemIndex > -1) {
            cart[existingItemIndex].quantity += 1;
        } else {
            cart.push({ name, price, image, quantity: 1 });
        }
        
        // Save back to localStorage
        localStorage.setItem('userCart', JSON.stringify(cart));
        
        // Update the UI counter
        updateCartCount();
        
        alert(name + " added to cart!");
    }

    function updateCartCount() {
        const cart = JSON.parse(localStorage.getItem('userCart')) || [];
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        document.getElementById('cartCount').innerText = totalItems;
    }

    // Run on page load
    window.onload = updateCartCount;
</script>
</body>
</html>