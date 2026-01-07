<?php
session_start();
include 'includes/db.php';
include 'config.php';

// Redirect admin users back to admin dashboard
if (isset($_SESSION['userType']) && $_SESSION['userType'] === 'admin') {
    header('Location: ./backend_8sp/index.php');
    exit();
}

// Security: Redirect to login if not authenticated
if (!isset($_SESSION['email']) || !isset($_SESSION['userID'])) {
    header("Location: login.html");
    exit();
}
$userName = $_SESSION['fullName'];
$userID = $_SESSION['userID'];
$userEmail = $_SESSION['email'];

// Get cart items from database
$cartQuery = $conn->prepare("
    SELECT c.productID, c.quantity, p.name, p.price, p.imagePath 
    FROM cart c 
    JOIN products p ON c.productID = p.productID 
    WHERE c.userID = ?
");
$cartQuery->bind_param('i', $userID);
$cartQuery->execute();
$cartResult = $cartQuery->get_result();

$cartItems = [];
$totalAmount = 0;
while ($row = $cartResult->fetch_assoc()) {
    $cartItems[] = $row;
    $totalAmount += $row['price'] * $row['quantity'];
}

// If cart is empty, redirect back
if (empty($cartItems)) {
    header("Location: cart.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'header.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - UniMerch Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
    <style>
        .checkout-container { display: grid; grid-template-columns: 1.5fr 1fr; gap: 30px; margin-top: 30px; }
        .form-section { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: 600; color: #333; }
        .form-group input { width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-family: 'Poppins', sans-serif; }
        
        /* Clickable Payment Options */
        .payment-methods { display: flex; gap: 10px; margin-top: 10px; }
        .payment-option { 
            flex: 1; text-align: center; padding: 15px; border: 2px solid #eee; 
            border-radius: 8px; cursor: pointer; transition: 0.3s; font-size: 14px;
        }
        .payment-option:hover { border-color: #7742cc; }
        .payment-option.active { border-color: #7742cc; background: #f3ebff; color: #7742cc; font-weight: bold; }

        .place-order-btn { 
            width: 100%; background: #28a745; color: white; border: none; padding: 15px; 
            border-radius: 5px; font-weight: bold; font-size: 18px; cursor: pointer; margin-top: 20px;
            transition: all 0.3s;
        }
        .place-order-btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(40,167,69,0.3); }

        /* Success Overlay Styles */
        #successOverlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.8); z-index: 1000; justify-content: center; align-items: center;
        }
        .receipt-card {
            background: white; padding: 40px; border-radius: 15px; width: 90%; max-width: 500px;
            text-align: center; position: relative; font-family: 'Poppins', sans-serif;
            transform: scale(0); opacity: 0;
        }
        .checkmark-circle {
            width: 80px; height: 80px; border-radius: 50%; background: #28a745;
            margin: 0 auto 20px; display: flex; align-items: center; justify-content: center;
            transform: scale(0);
        }
        .checkmark {
            width: 40px; height: 20px; border-left: 4px solid white; border-bottom: 4px solid white;
            transform: rotate(-45deg) scale(0);
        }
        .receipt-header { color: #28a745; font-size: 24px; font-weight: bold; margin-bottom: 20px; }
        .receipt-details { text-align: left; background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .receipt-line { display: flex; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px dashed #ddd; padding-bottom: 5px; }

        /* Print Logic */
        @media print {
            body * { visibility: hidden; }
            #successOverlay, #successOverlay * { visibility: visible; }
            #successOverlay { position: absolute; left: 0; top: 0; width: 100%; background: white !important; display: flex !important; }
            .receipt-card { box-shadow: none; border: 1px solid #000; width: 100%; max-width: none; }
            .login-btn, .print-btn { display: none; }
        }

        @media (max-width: 768px) { .checkout-container { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <navbar>
        <a href="index.php"><img src="assets/images/logo.png" alt="Logo" class="logo"></a>
        <span style="color: white;">Secure Checkout</span>
        <a href="orderhistory.php" style="margin-left:16px; color: white;">Orders</a>
    </navbar>

    <div class="container">
        <div class="checkout-container">
            <div class="form-section">
                <h2>Shipping Details</h2>
                <form id="checkoutForm">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" id="custName" value="<?php echo htmlspecialchars($userName); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" id="custPhone" placeholder="01X-XXXXXXX" required>
                    </div>
                    <div class="form-group">
                        <label>Shipping Address</label>
                        <input type="text" id="custAddress" placeholder="Street Address / Hostel" required>
                    </div>
                    
                    <h2 style="margin: 30px 0 10px;">Payment Method</h2>
                    <div class="payment-methods" id="paymentMethods">
                        <div class="payment-option active" onclick="selectPayment(this)">Bank Transfer</div>
                        <div class="payment-option" onclick="selectPayment(this)">E-Wallet</div>
                        <div class="payment-option" onclick="selectPayment(this)">Card</div>
                    </div>
                    <button type="submit" class="place-order-btn">PLACE ORDER NOW</button>
                </form>
            </div>

            <div class="form-section" style="height: fit-content;">
                <h3 style="margin-bottom: 15px;">Review Order</h3>
                <div id="checkoutItems"></div>
                <hr style="margin: 15px 0; border: 0; border-top: 1px solid #eee;">
                
                <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                    <span>Subtotal</span>
                    <span id="checkSubtotal">RM 0.00</span>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom:10px;">
                    <span>Shipping</span>
                    <span style="color: #28a745; font-weight: 600;">FREE</span>
                </div>
                
                <div style="display:flex; justify-content:space-between; font-weight:bold; color:#7742cc; font-size:20px; margin-top:10px; border-top: 2px solid #eee; padding-top: 10px;">
                    <span>Total</span>
                    <span id="checkTotal">RM 0.00</span>
                </div>
            </div>
        </div>
    </div>

    <div id="successOverlay">
        <div class="receipt-card">
            <div class="receipt-header">✔ Transaction Successful</div>
            <p style="margin-bottom:20px;">Thank you for your purchase!</p>
            <div class="receipt-details">
                <div class="receipt-line"><span>Order ID:</span> <strong id="receiptID"></strong></div>
                <div class="receipt-line"><span>Date:</span> <span id="receiptDate"></span></div>
                <div class="receipt-line"><span>Customer:</span> <span id="resName"></span></div>
                <div class="receipt-line"><span>Phone:</span> <span id="resPhone"></span></div>
                <div class="receipt-line"><span>Payment:</span> <span id="receiptPayment"></span></div>
                <div id="receiptItemsList" style="margin-top:15px; border-top: 1px solid #eee; padding-top:10px;"></div>
                <div class="receipt-line" style="border:none; font-weight:bold; color:#7742cc; font-size:18px; margin-top:10px;">
                    <span>Total Paid:</span> <span id="receiptTotal"></span>
                </div>
            </div>
            <button class="print-btn" onclick="window.print()" style="width:100%; background:#6c757d; color:white; border:none; padding:10px; border-radius:5px; cursor:pointer; font-weight:600;">Print Receipt</button>
            <button class="login-btn" onclick="finishOrder()" style="width:100%; margin-top:10px;">Return to Home</button>
        </div>
    </div>

    <script>
        let selectedPayment = "Bank Transfer";

        function selectPayment(element) {
            document.querySelectorAll('.payment-option').forEach(opt => opt.classList.remove('active'));
            element.classList.add('active');
            selectedPayment = element.innerText;
        }

        function displaySummary() {
            const cart = JSON.parse(localStorage.getItem('userCart')) || [];
            if (cart.length === 0) { window.location.href = 'index.php'; return; }

            let subtotal = 0;
            const container = document.getElementById('checkoutItems');
            container.innerHTML = cart.map(item => {
                const itemTotal = (item.price * item.quantity);
                subtotal += itemTotal;
                return `<div style="display:flex; justify-content:space-between; margin-bottom:10px; font-size:14px;">
                            <span>${item.name} (x${item.quantity})</span>
                            <span>RM ${itemTotal.toFixed(2)}</span>
                        </div>`;
            }).join('');
            
            document.getElementById('checkSubtotal').innerText = `RM ${subtotal.toFixed(2)}`;
            document.getElementById('checkTotal').innerText = `RM ${subtotal.toFixed(2)}`;
        }

        let isSubmitting = false; // Flag to prevent double submission

        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Prevent double submission
            if (isSubmitting) {
                return false;
            }
            isSubmitting = true;

            // Disable the submit button
            const submitBtn = this.querySelector('.place-order-btn');
            submitBtn.disabled = true;
            submitBtn.style.opacity = '0.6';
            submitBtn.innerText = 'Processing...';
            
                // Send order to backend including customer info and cart items
                const totalText = document.getElementById('checkTotal').innerText || 'RM 0.00';
                const totalAmount = parseFloat(totalText.replace(/[^0-9.]/g, '')) || 0.00;
                const payload = new FormData();
                payload.append('totalAmount', totalAmount);
                payload.append('fullName', document.getElementById('custName').value);
                payload.append('phone', document.getElementById('custPhone').value);
                payload.append('address', document.getElementById('custAddress').value);
                payload.append('paymentMethod', selectedPayment);

                // Add user's device timestamp in their local timezone
                const now = new Date();
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');
                const orderDateTime = `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
                payload.append('orderDateTime', orderDateTime);

                const items = JSON.parse(localStorage.getItem('userCart') || '[]');
                payload.append('items', JSON.stringify(items));

                fetch('process/checkout_process.php', {
                    method: 'POST',
                    credentials: 'same-origin',
                    body: payload
                }).then(r => r.json())
                .then(response => {
                    if (response.success) {
                        const orderNumber = response.orderNumber;
                        const now = new Date();
                        const dateStr = now.toLocaleDateString() + " " + now.toLocaleTimeString();

                        // Populate Receipt
                        document.getElementById('receiptID').innerText = orderNumber;
                        document.getElementById('receiptDate').innerText = dateStr;
                        document.getElementById('resName').innerText = document.getElementById('custName').value;
                        document.getElementById('resPhone').innerText = document.getElementById('custPhone').value;
                        document.getElementById('receiptPayment').innerText = selectedPayment;
                        document.getElementById('receiptTotal').innerText = document.getElementById('checkTotal').innerText;
                        document.getElementById('receiptItemsList').innerHTML = document.getElementById('checkoutItems').innerHTML;

                        // Show and animate success overlay
                        document.getElementById('successOverlay').style.display = 'flex';
                        
                        // Animate success overlay
                        anime.timeline()
                            .add({
                                targets: '#successOverlay',
                                opacity: [0, 1],
                                duration: 300,
                                easing: 'easeOutQuad'
                            })
                            .add({
                                targets: '.receipt-card',
                                scale: [0, 1],
                                opacity: [0, 1],
                                duration: 600,
                                easing: 'easeOutElastic(1, .8)'
                            }, '-=100')
                            .add({
                                targets: '.checkmark-circle',
                                scale: [0, 1],
                                duration: 400,
                                easing: 'easeOutBack'
                            }, '-=400')
                            .add({
                                targets: '.checkmark',
                                scale: [0, 1],
                                duration: 300,
                                easing: 'easeOutBack'
                            }, '-=200')
                            .add({
                                targets: '.receipt-header',
                                translateY: [-20, 0],
                                opacity: [0, 1],
                                duration: 400,
                                easing: 'easeOutQuad'
                            }, '-=200')
                            .add({
                                targets: '.receipt-details',
                                translateY: [20, 0],
                                opacity: [0, 1],
                                duration: 400,
                                easing: 'easeOutQuad'
                            }, '-=300');
                    } else {
                        alert('Failed to place order: ' + (response.message || 'Unknown error'));
                        // Re-enable button on failure
                        isSubmitting = false;
                        submitBtn.disabled = false;
                        submitBtn.style.opacity = '1';
                        submitBtn.innerText = 'PLACE ORDER NOW';
                    }
                }).catch(err => {
                    console.error(err);
                    alert('Error communicating with server.');
                    // Re-enable button on error
                    isSubmitting = false;
                    submitBtn.disabled = false;
                    submitBtn.style.opacity = '1';
                    submitBtn.innerText = 'PLACE ORDER NOW';
                });
        });

        function finishOrder() {
            localStorage.removeItem('userCart');
            // After finishing, redirect to profile where orders are shown
            window.location.href = 'profile.php';
        }

        window.onload = displaySummary;
    </script>
</body>
</html>