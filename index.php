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
    <title>UniMerch Hub - Home</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=search" />
    <link rel="stylesheet" href="style.css">
    <style>
        /* Add to Cart button styling */
        .add-to-cart-btn {
            width: 100%;
            background-color: #7742cc;
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
            font-weight: bold;
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
    </style>
</head>
<body>
    <navbar>
        <a href="index.php"><img src="assets/images/logo.png" alt="Logo" class="logo"></a>
        <div id="authArea" class="nav-links">
            <a href="cart.php">
                Cart <span id="cartCount" class="cart-count-badge">0</span>
            </a>

            <?php if ($isLoggedIn): ?>
                <span style="color: white;">
                    Welcome, <strong><?php echo htmlspecialchars($userName); ?></strong>
                </span>
                <?php if ($userType === 'admin'): ?>
                    <a href="backend_8sp/index.php" class="login-btn">Admin</a>
                <?php endif; ?>
                <a href="logout.php" class="login-btn">Logout</a>
            <?php else: ?>
                <a href="login.html" class="login-btn">Login/Register</a>
            <?php endif; ?>
        </div>
    </navbar>

    <div class="container">
        <form onsubmit="return false;">
            <span class="search-bar">
                <input type="text" id="searchInput" placeholder="Search products..." onkeyup="searchProducts()">
                <button class="search-btn"><span class="material-symbols-outlined">search</span></button>
            </span>
        </form>
        
        <div class="grid" id="productGrid">
            </div>
    </div>

<script src="data.js"></script>

<script>
    /**
     * Renders products to the grid based on an array
     * @param {Array} items - The product list to display
     */
    function renderProducts(items) {
        const grid = document.getElementById('productGrid');
        if (items.length === 0) {
            grid.innerHTML = '<p style="grid-column: 1/-1; text-align: center; padding: 50px;">No products found.</p>';
            return;
        }

        grid.innerHTML = items.map(product => `
            <div class="product-card">
                <div class="product-image">
                    <img src="${product.imagePath}" alt="${product.name}" 
                         style="width:100%; height:200px; object-fit:cover;"
                         onerror="this.src='https://placehold.co/250x200?text=No+Image'">
                </div>
                <div class="product-info">
                    <div class="product-name">${product.name}</div>
                    <div class="product-price">RM ${product.price.toFixed(2)}</div>
                    <p style="font-size: 12px; color: #666; margin-top: 5px;">Category: ${product.category}</p>
                    <button class="add-to-cart-btn" 
                        onclick="addToCart(${product.productID}, '${product.name}', ${product.price}, '${product.imagePath}')">
                        Add to Cart
                    </button>
                </div>
            </div>
        `).join('');
    }

    /**
     * Logic to add product to LocalStorage cart
     */
    function addToCart(id, name, price, image) {
        let cart = JSON.parse(localStorage.getItem('userCart')) || [];
        
        // Find if item already exists
        const existingItem = cart.find(item => item.id === id);

        if (existingItem) {
            existingItem.quantity += 1;
        } else {
            cart.push({
                id: id,
                name: name,
                price: price,
                image: image,
                quantity: 1
            });
        }

        // Persist to local storage
        localStorage.setItem('userCart', JSON.stringify(cart));
        updateCartCount();
        alert(name + " has been added to your cart!");
    }

    /**
     * Updates the navbar cart badge count
     */
    function updateCartCount() {
        const cart = JSON.parse(localStorage.getItem('userCart')) || [];
        const totalItems = cart.reduce((sum, item) => sum + item.quantity, 0);
        document.getElementById('cartCount').innerText = totalItems;
    }

    /**
     * Simple search functionality
     */
    function searchProducts() {
        const query = document.getElementById('searchInput').value.toLowerCase();
        const filtered = products.filter(p => 
            p.name.toLowerCase().includes(query) || 
            p.category.toLowerCase().includes(query)
        );
        renderProducts(filtered);
    }

    // Initialize page components on load
    window.onload = () => {
        renderProducts(products); // 'products' is defined in data.js
        updateCartCount();
    };
</script>

</body>
</html>