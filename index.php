<?php
session_start();

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
    <title>UniMerch Hub - Home</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=search" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
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
    <a href="cart.php" style="display: flex; align-items: center; gap: 5px;">
        <span class="material-symbols-outlined">shopping_cart</span>
        Cart <span id="cartCount" class="cart-count-badge">0</span>
    </a>

            <?php if ($isLoggedIn): ?>
                <a href="profile.php"><span style="color: white;">
                    Welcome, <strong><?php echo htmlspecialchars($userName); ?></strong>
                </span></a>
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
    function renderProducts(items) {
    const grid = document.getElementById('productGrid');
    if (items.length === 0) {
        grid.innerHTML = '<p style="grid-column: 1/-1; text-align: center; padding: 50px;">No products found.</p>';
        return;
    }

    grid.innerHTML = items.map(product => {
        // Convert the string price from DB to a real number
        const priceNum = Number(product.price); 
        
        return `
            <div class="product-card">
                <div class="product-image">
                    <img src="${product.imagePath}" alt="${product.name}" 
                         style="width:100%; height:200px; object-fit:cover;"
                         onerror="this.src='https://placehold.co/250x200?text=No+Image'">
                </div>
                <div class="product-info">
                    <div class="product-name">${product.name}</div>
                    <div class="product-price">RM ${priceNum.toFixed(2)}</div>
                    <p style="font-size: 12px; color: #666; margin-top: 5px;">Category: ${product.category}</p>
                    <button class="add-to-cart-btn" 
                        onclick="addToCart(${product.productID}, '${product.name}', ${priceNum}, '${product.imagePath}')">
                        Add to Cart
                    </button>
                </div>
            </div>
        `;
    }).join('');
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

    function searchProducts() {
    const query = document.getElementById('searchInput').value.toLowerCase();
    // Ensure window.products exists before filtering
    if (!window.products) return; 

    const filtered = window.products.filter(p => 
        p.name.toLowerCase().includes(query) || 
        p.category.toLowerCase().includes(query)
    );
    renderProducts(filtered);
}

    // Chung's Database-Ready Initialization
window.onload = async () => {
    try {
        // 1. Fetch the real data from your XAMPP products table
        const response = await fetch('fetch_products.php');
        const dbProducts = await response.json();
        
        // 2. Save it to a global variable so searchProducts() still works
        window.products = dbProducts; 
        
        // 3. Show them on the screen
        renderProducts(window.products); 
        updateCartCount();
    } catch (error) {
        console.error("Database connection failed:", error);
        // If database fails, show empty grid
        renderProducts([]); 
    }
};
</script>

</body>
</html>