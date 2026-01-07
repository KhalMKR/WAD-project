<?php
// Start the session
session_start();

// Clear all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();
?>

<!DOCTYPE html>
<html>
<head>
    <?php include 'header.php'; ?>
    <title>Logging out...</title>
</head>
<body>
    <script>
        // Clear the cart from localStorage
        localStorage.removeItem('userCart');
        // Redirect to login page
        window.location.href = 'login.html';
    </script>
</body>
</html>
