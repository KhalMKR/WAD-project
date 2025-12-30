<?php
session_start();
include 'db.php'; // Include DB connection

// --- NEW: Handle AJAX Request to Sync Cart to Database ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_SESSION['userID'])) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['action']) && $input['action'] === 'sync_cart' && isset($input['cart'])) {
        $userID = $_SESSION['userID'];
        
        // 1. Clear current database cart for this user (to replace with new data)
        $clearStmt = $conn->prepare("DELETE FROM cart WHERE userID = ?");
        $clearStmt->bind_param("i", $userID);
        $clearStmt->execute();
        $clearStmt->close();

        // 2. Insert items from LocalStorage into Database
        $insertStmt = $conn->prepare("INSERT INTO cart (userID, productID, quantity) VALUES (?, ?, ?)");
        foreach ($input['cart'] as $item) {
            $pid = isset($item['id']) ? intval($item['id']) : 0;
            $qty = isset($item['quantity']) ? intval($item['quantity']) : 1;
            
            if ($pid > 0 && $qty > 0) {
                $insertStmt->bind_param("iii", $userID, $pid, $qty);
                $insertStmt->execute();
            }
        }
        $insertStmt->close();

        echo json_encode(['success' => true]);
        exit; // Stop script execution here for AJAX requests
    }
}
// ---------------------------------------------------------

// Redirect admin users back to admin dashboard
if (isset($_SESSION['userType']) && $_SESSION['userType'] === 'admin') {
    header('Location: ./backend_8sp/index.php');
    exit();
}

// Check if user is logged in
$isLoggedIn = isset($_SESSION['email']) && isset($_SESSION['fullName']) && isset($_SESSION['userID']);
$userName = $isLoggedIn ? $_SESSION['fullName'] : '';
$userType = $isLoggedIn ? $_SESSION['userType'] : '';
$userID = $isLoggedIn ? $_SESSION['userID'] : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Your Cart - UniMerch Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .cart-layout { display: grid; grid-template-columns: 2fr 1fr; gap: 30px; margin-top: 30px; }
        .cart-items { background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .cart-header { border-bottom: 2px solid #f5f5f5; padding-bottom: 10px; margin-bottom: 20px; font-size: 24px; font-weight: 600; }
        .cart-item { display: flex; align-items: center; padding: 15px 0; border-bottom: 1px solid #eee; }
        .cart-item-img { width: 80px; height: 80px; border-radius: 4px; margin-right: 20px; object-fit: cover; background: #eee; }
        .cart-item-details { flex-grow: 1; }
        .cart-item-name { font-weight: 600; font-size: 18px; color: #333; }
        .cart-item-price { color: #007bff; font-weight: bold; }
        .quantity-controls { display: flex; align-items: center; gap: 10px; }
        .qty-btn { background: #f0f0f0; border: none; width: 30px; height: 30px; cursor: pointer; border-radius: 4px; }
        .remove-btn { color: #ff4757; cursor: pointer; font-size: 14px; margin-top: 5px; background: none; border: none; padding: 0; }
        .summary-card { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); height: fit-content; }
        .summary-row { display: flex; justify-content: space-between; margin-bottom: 15px; }
        .total-row { border-top: 2px solid #eee; padding-top: 15px; font-weight: bold; font-size: 20px; color: #7742cc; }
        .checkout-btn { width: 100%; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; padding: 15px; border-radius: 5px; font-weight: bold; cursor: pointer; }
        @media (max-width: 768px) { .cart-layout { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <navbar>
        <a href="index.php"><img src="assets/images/logo.png" alt="Logo" class="logo"></a>
        <div id="authArea">
            <?php if ($isLoggedIn): ?>
                <a href="orderhistory.php" style="margin-right:12px; color: white;">Orders</a>
                <span style="color: white; margin-right: 15px;">Welcome, <strong><?php echo htmlspecialchars($userName); ?></strong></span>
                <a href="logout.php" class="login-btn">Logout</a>
            <?php else: ?>
                <a href="login.html" class="login-btn">Login/Register</a>
            <?php endif; ?>
        </div>
    </navbar>

    <div class="container">
        <div class="cart-layout">
            <div class="cart-items" id="cartList">
                <div class="cart-header">Shopping Cart</div>
                </div>

            <div class="summary-card">
                <h3>Order Summary</h3>
                <div class="summary-row" style="margin-top:20px;">
                    <span>Subtotal</span>
                    <span id="subtotal">RM 0.00</span>
                </div>
                <div class="summary-row">
                    <span>Shipping</span>
                    <span style="color:green;">FREE</span>
                </div>
                <div class="total-row summary-row">
                    <span>Total</span>
                    <span id="grandTotal">RM 0.00</span>
                </div>
                <button class="checkout-btn" onclick="proceedToCheckout()">PROCEED TO CHECKOUT</button>
            </div>
        </div>
    </div>

    <script>
        const isLoggedIn = <?php echo $isLoggedIn ? 'true' : 'false'; ?>;

        function loadCart() {
            // All users use localStorage
            const cart = JSON.parse(localStorage.getItem('userCart')) || [];
            const cartList = document.getElementById('cartList');
            const header = '<div class="cart-header">Shopping Cart</div>';
            
            if (cart.length === 0) {
                cartList.innerHTML = header + '<p style="padding:20px;">Your cart is empty.</p>';
                updateTotals(0);
                return;
            }

            let html = header;
            let subtotal = 0;

            cart.forEach((item, index) => {
                const itemTotal = item.price * item.quantity;
                subtotal += itemTotal;
                html += `
                <div class="cart-item">
                    <img src="${item.image}" alt="${item.name}" class="cart-item-img" onerror="this.src='https://placehold.co/80x80?text=No+Image'">
                    <div class="cart-item-details">
                        <div class="cart-item-name">${item.name}</div>
                        <div class="cart-item-price">RM ${item.price.toFixed(2)}</div>
                        <button class="remove-btn" onclick="removeItem(${index})">Remove</button>
                    </div>
                    <div class="quantity-controls">
                        <button class="qty-btn" onclick="changeQty(${index}, -1)">-</button>
                        <span>${item.quantity}</span>
                        <button class="qty-btn" onclick="changeQty(${index}, 1)">+</button>
                    </div>
                </div>`;
            });

            cartList.innerHTML = html;
            updateTotals(subtotal);
        }

        function changeQty(index, delta) {
            let cart = JSON.parse(localStorage.getItem('userCart'));
            cart[index].quantity += delta;
            if (cart[index].quantity < 1) cart[index].quantity = 1;
            localStorage.setItem('userCart', JSON.stringify(cart));
            loadCart();
        }

        function removeItem(index) {
            let cart = JSON.parse(localStorage.getItem('userCart'));
            cart.splice(index, 1);
            localStorage.setItem('userCart', JSON.stringify(cart));
            loadCart();
        }

        function updateTotals(subtotal) {
            document.getElementById('subtotal').innerText = `RM ${subtotal.toFixed(2)}`;
            document.getElementById('grandTotal').innerText = `RM ${subtotal.toFixed(2)}`;
        }

        function proceedToCheckout() {
            if (!isLoggedIn) {
                alert("Please login to proceed with checkout.");
                window.location.href = 'login.html';
                return;
            }

            const cart = JSON.parse(localStorage.getItem('userCart')) || [];
            if (cart.length === 0) {
                alert("Your cart is empty!");
                return;
            }

            // --- CHANGED: Sync localStorage to Database before redirecting ---
            fetch('cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'sync_cart', cart: cart })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Once database is updated, we can safely go to checkout.php
                    window.location.href = 'checkout.php';
                } else {
                    alert("Error preparing checkout. Please try again.");
                    console.error('Server response:', data);
                }
            })
            .catch(err => {
                console.error("Fetch error:", err);
                alert("Could not connect to server. Please check your connection.");
            });
        }

        // Initialize page
        window.onload = loadCart;
    </script>
</body>
</html>