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
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?php echo htmlspecialchars($product['name']); ?> - UniMerch Hub</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .product-page { display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 30px; }
        .product-image img { width:100%; border-radius:8px; max-height:480px; object-fit:cover; }
        .product-info { background:white; padding:20px; border-radius:8px; }
        .price { color:#7742cc; font-size:24px; font-weight:700; margin-top:8px; }
        .add-btn { margin-top:20px; background:#7742cc; color:white; border:none; padding:12px 20px; border-radius:6px; cursor:pointer; font-weight:700; }
        @media(max-width:800px){ .product-page{grid-template-columns:1fr} }
    </style>
</head>
<body>
    <navbar>
        <a href="index.php"><img src="assets/images/logo.png" alt="Logo" class="logo"></a>
        <div id="authArea"></div>
    </navbar>

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
            alert(productName + ' added to cart!');
            
            // Update cart count if it exists
            const cartBadge = document.getElementById('cartCount');
            if (cartBadge) {
                cartBadge.innerText = cart.reduce((sum, item) => sum + item.quantity, 0);
            }
        }
    </script>
</body>
</html>
