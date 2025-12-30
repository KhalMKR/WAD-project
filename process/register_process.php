<?php
include '../includes/db.php'; // This uses your connection script 

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect data from the form
    $name = $_POST['fullName'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $userType = "user"; // Default role for new registrations

    // Validation
    $errors = [];

    // Check if email already exists
    $stmt_check = $conn->prepare("SELECT email FROM users WHERE email = ?");
    $stmt_check->bind_param("s", $email);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows > 0) {
        $errors[] = "Email already registered";
    }

    // Validate password length
    if (strlen($password) < 6) {
        $errors[] = "Password must be at least 6 characters";
    }

    // Check if passwords match
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }

    // Check if name is empty
    if (empty($name)) {
        $errors[] = "Full name is required";
    }

    // If there are errors, display them
    if (!empty($errors)) {
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Registration Error</title></head><body>';
        echo '<h2 style="color: red; text-align: center;">Registration Failed</h2>';
        echo '<div style="max-width: 400px; margin: 20px auto; background: #ffebee; padding: 20px; border-radius: 5px;">';
        foreach ($errors as $error) {
            echo '<p style="color: #c62828; margin: 10px 0;">❌ ' . htmlspecialchars($error) . '</p>';
        }
        echo '<p style="margin-top: 20px;"><a href="../register.html" style="color: #667eea; text-decoration: none;">← Back to Registration</a></p>';
        echo '</div></body></html>';
        exit;
    }

    // Hash the password securely
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Use prepared statement to insert
    $stmt = $conn->prepare("INSERT INTO users (fullName, email, password, userType) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("ssss", $name, $email, $hashedPassword, $userType);

    if ($stmt->execute()) {
        // Show JS popup then redirect to login page
        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>Redirecting</title></head><body>
              <script>
                alert("Registration successful! Please login with your credentials.");
                window.location.href = "../login.html";
              </script>
              </body></html>';
        exit;
    } else {
        echo "Error: " . htmlspecialchars($conn->error);
    }

    $stmt->close();
    $stmt_check->close();
}
?>
