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
        .form-group input, .form-group select { 
            width: 100%; padding: 12px; border: 1px solid #ddd; border-radius: 5px; font-family: 'Poppins', sans-serif; 
        }
        .order-summary-box { background: #f9f9f9; padding: 20px; border-radius: 8px; border: 1px solid #eee; }
        .summary-item { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 14px; }
        .payment-methods { display: flex; gap: 10px; margin-top: 10px; }
        .payment-option { 
            flex: 1; text-align: center; padding: 10px; border: 1px solid #ddd; border-radius: 5px; cursor: pointer; 
        }
        .payment-option.active { border-color: #7742cc; background: #f3ebff; color: #7742cc; font-weight: bold; }
        .place-order-btn { 
            width: 100%; background: #28a745; color: white; border: none; padding: 15px; 
            border-radius: 5px; font-weight: bold; font-size: 18px; cursor: pointer; margin-top: 20px;
        }
        @media (max-width: 768px) { .checkout-container { grid-template-columns: 1fr; } }
    </style>
</head>
<body>
    <navbar>
        <a href="index.php"><img src="assets/images/logo.png" alt="Logo" class="logo"></a>
        <span style="color: white;">Secure Checkout</span>
    </navbar>

    <div class="container">
        <div class="checkout-container">
            <div class="form-section">
                <h2 style="margin-bottom: 20px;">Shipping Details</h2>
                <form id="checkoutForm">
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" value="<?php echo htmlspecialchars($userName); ?>" required>
                    </div>
                    <div class="form-group">
                        <label>Shipping Address</label>
                        <input type="text" placeholder="Street Address, Hostel Name, Room No." required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number</label>
                        <input type="tel" placeholder="012-3456789" required>
                    </div>

                    <h2 style="margin: 30px 0 20px;">Payment Method</h2>
                    <div class="payment-methods">
                        <div class="payment-option active">Bank Transfer (FPX)</div>
                        <div class="payment-option">E-Wallet (TNG/Grab)</div>
                        <div class="payment-option">Card</div>
                    </div>
                    <button type="submit" class="place-order-btn">PLACE ORDER NOW</button>
                </form>
            </div>

            <div class="form-section" style="height: fit-content;">
                <h3 style="margin-bottom: 15px;">Review Order</h3>
                <div id="checkoutItems">
                    </div>
                <hr style="margin: 15px 0; border: 0; border-top: 1px solid #eee;">
                <div class="summary-item">
                    <span>Subtotal</span>
                    <span id="checkSubtotal">RM 0.00</span>
                </div>
                <div class="summary-item">
                    <span>Shipping</span>
                    <span style="color: green;">FREE</span>
                </div>
                <div class="summary-item" style="font-weight: bold; font-size: 18px; color: #7742cc; margin-top: 10px;">
                    <span>Total</span>
                    <span id="checkTotal">RM 0.00</span>
                </div>
            </div>
        </div>
    </div>

    <script>
        function displaySummary() {
            const cart = JSON.parse(localStorage.getItem('userCart')) || [];
            const container = document.getElementById('checkoutItems');
            let subtotal = 0;

            if (cart.length === 0) {
                window.location.href = 'index.php'; // Redirect if cart is somehow empty
                return;
            }

            container.innerHTML = cart.map(item => {
                const total = item.price * item.quantity;
                subtotal += total;
                return `
                    <div class="summary-item">
                        <span>${item.name} (x${item.quantity})</span>
                        <span>RM ${total.toFixed(2)}</span>
                    </div>
                `;
            }).join('');

            document.getElementById('checkSubtotal').innerText = `RM ${subtotal.toFixed(2)}`;
            document.getElementById('checkTotal').innerText = `RM ${subtotal.toFixed(2)}`;
        }

        document.getElementById('checkoutForm').addEventListener('submit', function(e) {
            e.preventDefault();
            alert("Thank you, <?php echo $userName; ?>! Your order has been placed successfully.");
            localStorage.removeItem('userCart'); // Clear cart
            window.location.href = 'index.php';
        });

        // Initialize
        window.onload = displaySummary;
    </script>
</body>
</html>