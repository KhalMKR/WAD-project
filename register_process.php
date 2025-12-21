<?php
include 'db.php'; // This uses your connection script 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect data from the form
    $name = $_POST['fullName'];
    $email = $_POST['email'];
    $pass = $_POST['password']; 
    $type = "Registered Member";

    // Use prepared statement
    $stmt = $conn->prepare("INSERT INTO users (fullName, email, password, userType) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $pass, $type);

    if ($stmt->execute()) {
        // Show JS popup then redirect to login page
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Redirecting</title></head><body>
              <script>
                alert("Registration successful!");
                window.location.href = "login.html";
              </script>
              </body></html>';
        exit;
    } else {
        echo "Error: " . $conn->error;
    }
}
?>