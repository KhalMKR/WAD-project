<?php
session_start();
// Security: Redirect to login if not authenticated
if (!isset($_SESSION['email'])) {
    header("Location: login.html");
    exit();
}
$userName = $_SESSION['fullName'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - UniMerch Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
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
        }

        /* Success Overlay Styles */
        #successOverlay {
            display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.8); z-index: 1000; justify-content: center; align-items: center;
        }
        .receipt-card {
            background: white; padding: 40px; border-radius: 15px; width: 90%; max-width: 500px;
            text-align: center; position: relative; font-family: 'Poppins', sans-serif;
        }
        .receipt-header { color: #28a745; font-size: 24px; font-weight: bold; margin-bottom: 20px; }
        .receipt-details { text-align: left; background: #f9f9f9; padding: 20px; border-radius: 8px; margin-bottom: 20px; }
        .receipt-line { display: flex; justify-content: space-between; margin-bottom: 10px; border-bottom: 1px dashed #ddd; padding-bottom: 5px; }

        @media (max-width: 768px) { .checkout-container { grid-template-columns: 1fr; } }

    </style>

    <style>
    /* ... (Keep your previous styles here) ... */

    /* New styles for the Print Button */
    .print-btn {
        width: 100%;
        background: #6c757d;
        color: white;
        border: none;
        padding: 10px;
        border-radius: 5px;
        cursor: pointer;
        margin-top: 10px;
        font-weight: 600;
    }

    /* Print logic: Hide everything except the receipt when printing */
    @media print {
        body * { visibility: hidden; }
        #successOverlay, #successOverlay * { visibility: visible; }
        #successOverlay { position: absolute; left: 0; top: 0; width: 100%; background: white; }
        .receipt-card { box-shadow: none; border: 1px solid #000; width: 100%; max-width: none; }
        .login-btn, .print-btn { display: none; } /* Don't print buttons */
    }
</style>

<div id="successOverlay">
    <div class="receipt-card">
        <div class="receipt-header">✔ Transaction Successful</div>
        <p style="margin-bottom:20px;">Thank you for your purchase!</p>
        
        <div class="receipt-details" id="printableReceipt">
            <div class="receipt-line"><span>Order ID:</span> <strong id="receiptID"></strong></div>
            <div class="receipt-line"><span>Date:</span> <span id="receiptDate"></span></div>
            <div class="receipt-line"><span>Customer:</span> <span><?php echo htmlspecialchars($userName); ?></span></div>
            <div class="receipt-line"><span>Payment:</span> <span id="receiptPayment"></span></div>
            <div id="receiptItemsList" style="margin-top:15px; border-top: 1px solid #eee; padding-top:10px;"></div>
            <div class="receipt-line" style="border:none; font-weight:bold; color:#7742cc; font-size:18px; margin-top:10px;">
                <span>Total Paid:</span> <span id="receiptTotal"></span>
            </div>
        </div>

        <button class="print-btn" onclick="window.print()">Print Receipt / Save as PDF</button>
        <button class="login-btn" onclick="finishOrder()" style="width:100%; margin-top:10px;">Return to Home</button>
    </div>
</div>
</head>
<body>
    <navbar>
        <a href="index.php"><img src="assets/images/logo.png" alt="Logo" class="logo"></a>
        <span style="color: white;">Secure Checkout</span>
    </navbar>

    <div class="container">
        <div class="checkout-container" id="mainCheckout">
            <div class="form-section">
                <h2>Shipping Details</h2>
                <form id="checkoutForm">
                    <div class="form-group"><label>Full Name</label><input type="text" value="<?php echo htmlspecialchars($userName); ?>" required></div>
                    <div class="form-group"><label>Shipping Address</label><input type="text" placeholder="Street Address / Hostel" required></div>
                    
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
                <h3>Review Order</h3>
                <div id="checkoutItems"></div>
                <hr style="margin: 15px 0; border: 0; border-top: 1px solid #eee;">
                <div style="display:flex; justify-content:space-between; font-weight:bold; color:#7742cc; font-size:20px;">
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
                <div class="receipt-line"><span>Customer:</span> <span><?php echo htmlspecialchars($userName); ?></span></div>
                <div class="receipt-line"><span>Payment:</span> <span id="receiptPayment">Bank Transfer</span></div>
                <div id="receiptItemsList" style="margin-top:15px; border-top: 1px solid #eee; padding-top:10px;"></div>
                <div class="receipt-line" style="border:none; font-weight:bold; color:#7742cc; font-size:18px; margin-top:10px;">
                    <span>Total Paid:</span> <span id="receiptTotal"></span>
                </div>
            </div>

            <button class="login-btn" onclick="finishOrder()" style="width:100%;">Return to Home</button>
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
                subtotal += (item.price * item.quantity);
                return `<div style="display:flex; justify-content:space-between; margin-bottom:10px; font-size:14px;">
                            <span>${item.name} (x${item.quantity})</span>
                            <span>RM ${(item.price * item.quantity).toFixed(2)}</span>
                        </div>`;
            }).join('');
            document.getElementById('checkTotal').innerText = `RM ${subtotal.toFixed(2)}`;
        }

        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Generate Receipt Data
            const orderID = "UMH-" + Math.floor(Math.random() * 900000 + 100000);
            const now = new Date();
            const dateStr = now.toLocaleDateString() + " " + now.toLocaleTimeString();
            
            // Fill Receipt
            document.getElementById('receiptID').innerText = orderID;
            document.getElementById('receiptDate').innerText = dateStr;
            document.getElementById('receiptPayment').innerText = selectedPayment;
            document.getElementById('receiptTotal').innerText = document.getElementById('checkTotal').innerText;
            
            // Clone items to receipt
            document.getElementById('receiptItemsList').innerHTML = document.getElementById('checkoutItems').innerHTML;

            // Show Overlay
            document.getElementById('successOverlay').style.display = 'flex';
        });

        function finishOrder() {
            localStorage.removeItem('userCart'); // Clear cart persistence
            window.location.href = 'index.php';
        }

        window.onload = displaySummary;
    </script>
</body>
</html>