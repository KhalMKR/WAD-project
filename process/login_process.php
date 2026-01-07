<?php
session_start();

include '../includes/db.php'; // Include your database connection script

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    // Query database for user
    $stmt = $conn->prepare("SELECT userID, fullName, email, password, userType FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        
        // Verify password using password_verify (secure hashing)
        if (password_verify($password, $user['password'])) {
            $_SESSION['userID'] = $user['userID'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['fullName'] = $user['fullName'];
            $_SESSION['userType'] = $user['userType'];
            
            // Return success with redirect URL
            if ($user['userType'] === 'admin') {
                echo 'SUCCESS ADMIN';
            } else {
                echo 'SUCCESS USER';
            }
            exit();
        } else {
            // Wrong password
            echo "ERROR: Invalid email or password. Please try again.";
            exit();
        }
    } else {
        // No email found
        echo "ERROR: Invalid email or password. Please try again.";
        exit();
    }
    
    $stmt->close();
}
?>
