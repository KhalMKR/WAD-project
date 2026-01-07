<?php
session_start();
include 'includes/db.php';

// Debug: Check session
error_log("Product page session data: " . json_encode($_SESSION));

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id <= 0) {
    header('Location: index.php');
    exit();
}

$stmt = $conn->prepare("SELECT productID, name, price, category, imagePath, stockQuantity FROM products WHERE productID = ? LIMIT 1");
if (!$stmt) {
    echo "Database error: " . htmlspecialchars($conn->error);
    exit();
}
$stmt->bind_param('i', $id);
$stmt->execute();
$res = $stmt->get_result();
if (!$res || $res->num_rows === 0) {
    echo "<p>Product not found.</p>";
    exit();
}
$product = $res->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'header.php'; ?>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo htmlspecialchars($product['name']); ?> - UniMerch Hub</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/animejs/3.2.1/anime.min.js"></script>
    <style>
        .product-page { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 30px; }
        .product-image img { width:100%; border-radius:8px; max-height:480px; object-fit:cover; }
        .product-info { background:white; padding:20px; border-radius:8px; }
        .price { color:#7742cc; font-size:24px; font-weight:700; margin-top:8px; }
        .add-btn { margin-top:20px; background:#7742cc; color:white; border:none; padding:12px 20px; border-radius:6px; cursor:pointer; font-weight:700; transition: all 0.3s; }
        .add-btn:active { transform: scale(0.95); }
        .cart-notification { 
            position:fixed; top:80px; right:-400px; 
            background:linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color:white; padding:20px 30px; border-radius:10px; 
            box-shadow:0 8px 24px rgba(0,0,0,0.3); z-index:10000; 
            min-width:300px; display:flex; align-items:center; gap:15px; font-weight:600;
        }
        .cart-notification .checkmark {
            width:30px; height:30px; background:white; border-radius:50%;
            display:flex; align-items:center; justify-content:center;
            color:#28a745; font-size:20px; flex-shrink:0;
        }
        @media(max-width:800px){ .product-page{grid-template-columns:1fr} }
    </style>
</head>
<body>
    <navbar>
        <a href="index.php"><img src="assets/images/logo.png" alt="Logo" class="logo"></a>
        <div id="authArea"></div>
    </navbar>

    <div id="cartNotification" class="cart-notification">
        <div class="checkmark">✓</div>
        <div>Added to cart!</div>
    </div>

    <div class="container">
        <div class="product-page">
            <div class="product-image">
                <img src="<?php echo htmlspecialchars($product['imagePath']); ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" onerror="this.src='https://placehold.co/600x400?text=No+Image'">
            </div>
            <div class="product-info">
                <h1><?php echo htmlspecialchars($product['name']); ?></h1>
                <div class="price">RM <?php echo number_format((float)$product['price'], 2); ?></div>
                <?php
                // Normalize categories for certain product names
                $displayCategory = $product['category'];
                $lowerName = strtolower($product['name']);
                if (strpos($lowerName, 'lanyard') !== false || strpos($lowerName, 'bracelet') !== false) {
                    $displayCategory = 'Accessory';
                }
                ?>
                <p><strong>Category:</strong> <?php echo htmlspecialchars($displayCategory); ?></p>

                <div style="margin-top:18px;">
                    <label>Quantity: <input type="number" id="qty" value="1" min="1" style="width:80px; padding:6px; border-radius:4px; border:1px solid #ddd;"></label>
                </div>

                <button class="add-btn" onclick="addToCart()">Add to Cart</button>
                <p style="margin-top:12px;"><a href="cart.php">View Cart</a> · <a href="orderhistory.php">My Orders</a></p>
                <p style="margin-top:12px;"><button onclick="history.back()" style="background:#eee;border:1px solid #ddd;padding:8px 12px;border-radius:6px;cursor:pointer;">← Back</button></p>
            </div>
        </div>
    </div>

    <script>
        function addToCart(){
            const productID = <?php echo (int)$product['productID']; ?>;
            const productName = <?php echo json_encode($product['name']); ?>;
            const price = <?php echo (float)$product['price']; ?>;
            const image = <?php echo json_encode($product['imagePath']); ?>;
            const quantity = Math.max(1, parseInt(document.getElementById('qty').value) || 1);

            // Use localStorage for cart (same for all users)
            let cart = JSON.parse(localStorage.getItem('userCart') || '[]');
            const existing = cart.find(i => i.id === productID);
            
            if (existing) {
                existing.quantity += quantity;
            } else {
                cart.push({ id: productID, name: productName, price: price, image: image, quantity: quantity });
            }
            
            localStorage.setItem('userCart', JSON.stringify(cart));
            
            // Update cart count if it exists
            const cartBadge = document.getElementById('cartCount');
            if (cartBadge) {
                const newCount = cart.reduce((sum, item) => sum + item.quantity, 0);
                cartBadge.innerText = newCount;
                
                // Animate cart badge
                anime({
                    targets: '#cartCount',
                    scale: [1, 1.8, 1],
                    rotate: [0, 10, -10, 0],
                    duration: 700,
                    easing: 'easeInOutQuad'
                });
            }
            
            // Animate add to cart button
            anime({
                targets: '.add-btn',
                scale: [1, 0.95, 1],
                backgroundColor: ['#7742cc', '#28a745', '#7742cc'],
                duration: 500,
                easing: 'easeInOutQuad'
            });
            
            // Show animated notification
            const notification = document.getElementById('cartNotification');
            
            anime.timeline()
                .add({
                    targets: '#cartNotification',
                    right: [-400, 30],
                    opacity: [0, 1],
                    duration: 600,
                    easing: 'easeOutElastic(1, .8)'
                })
                .add({
                    targets: '#cartNotification',
                    scale: [1, 1.05, 1],
                    duration: 300,
                    easing: 'easeInOutQuad'
                })
                .add({
                    targets: '#cartNotification',
                    right: [30, -400],
                    opacity: [1, 0],
                    duration: 500,
                    delay: 2000,
                    easing: 'easeInExpo'
                });
        }
    </script>
</body>
</html>
