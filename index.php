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
    <title>Product Grid</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0&icon_names=search" />
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <navbar>
        <img src="assets/images/logo.png" alt="Logo" class="logo">
        <div id="authArea">
            <?php if ($isLoggedIn): ?>
                <span style="color: white; margin-right: 20px;">
                    Welcome, <strong><?php echo htmlspecialchars($userName); ?></strong>
                </span>
                <?php if ($userType === 'admin'): ?>
                    <a href="backend_8sp/index.php" class="login-btn" style="margin-right: 10px;">Admin Dashboard</a>
                <?php endif; ?>
                <a href="logout.php" class="login-btn">Logout</a>
            <?php else: ?>
                <a href="login.html" class="login-btn">Login/Register</a>
            <?php endif; ?>
        </div>
    </navbar>

    <div class="container">
        <form>
            <span class="search-bar">
            <input type="text" placeholder="Search products...">
            <button class="search-btn"><span class="material-symbols-outlined">search</span></button>
            </span>
        </form>
        

        <div class="grid">
            <div class="product-card">
                <div class="product-image">Product Image</div>
                <div class="product-info">
                    <div class="product-name">Product 1</div>
                    <div class="product-price">$29.99</div>
                </div>
            </div>

            <div class="product-card">
                <div class="product-image">Product Image</div>
                <div class="product-info">
                    <div class="product-name">Product 2</div>
                    <div class="product-price">$39.99</div>
                </div>
            </div>

            <div class="product-card">
                <div class="product-image">Product Image</div>
                <div class="product-info">
                    <div class="product-name">Product 3</div>
                    <div class="product-price">$49.99</div>
                </div>
            </div>

            <div class="product-card">
                <div class="product-image">Product Image</div>
                <div class="product-info">
                    <div class="product-name">Product 4</div>
                    <div class="product-price">$59.99</div>
                </div>
            </div>

            <div class="product-card">
                <div class="product-image">Product Image</div>
                <div class="product-info">
                    <div class="product-name">Product 5</div>
                    <div class="product-price">$69.99</div>
                </div>
            </div>

            <div class="product-card">
                <div class="product-image">Product Image</div>
                <div class="product-info">
                    <div class="product-name">Product 6</div>
                    <div class="product-price">$79.99</div>
                </div>
            </div>
        </div>
    </div>

<script src="data.js"></script>
<script src="utils.js"></script>
<script src="admin.js"></script>

<script>
(function(){
  const authArea = document.getElementById('authArea');
  const logged = localStorage.getItem('isLoggedIn') === 'true';
  if (logged) {
    const name = (localStorage.getItem('userName') || 'User');
    authArea.innerHTML = `
      <div id="userMenu" style="display:flex;align-items:center;gap:10px;">
        <img src="assets/images/user-icon.png" alt="User" style="width:36px;height:36px;border-radius:50%;">
        <span style="color:#fff;font-weight:600;">${name}</span>
        <button id="logoutBtn" style="background:transparent;border:none;color:#fff;cursor:pointer;">Logout</button>
      </div>`;
    document.getElementById('logoutBtn').addEventListener('click', function(){
      localStorage.removeItem('isLoggedIn');
      localStorage.removeItem('userName');
      location.reload();
    });
  }
})();
</script>

</body>
</html>