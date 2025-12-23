<?php
/**
 * Password Hashing Utility
 * Use this script to generate secure password hashes
 * 
 * Usage:
 * 1. Enter your password in the form below
 * 2. Click "Hash Password"
 * 3. Copy the hashed value
 * 4. Paste it in the database password field
 */

$hashedPassword = null;
$plainPassword = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $plainPassword = $_POST['password'] ?? '';
    
    if (!empty($plainPassword)) {
        // Generate secure hash using bcrypt (PASSWORD_BCRYPT)
        $hashedPassword = password_hash($plainPassword, PASSWORD_BCRYPT);
    } else {
        $error = "Please enter a password";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Hashing Utility</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: Arial, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            padding: 40px;
            border-radius: 8px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            max-width: 500px;
            width: 100%;
        }
        
        h1 {
            text-align: center;
            margin-bottom: 10px;
            color: #333;
        }
        
        .info {
            background: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 25px;
            font-size: 14px;
            color: #1565c0;
            border-left: 4px solid #1565c0;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: bold;
        }
        
        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 14px;
            font-family: monospace;
        }
        
        input[type="password"]:focus,
        input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 5px rgba(102, 126, 234, 0.5);
        }
        
        button {
            width: 100%;
            padding: 12px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        button:hover {
            background: #764ba2;
        }
        
        .result {
            margin-top: 30px;
            padding: 20px;
            background: #f5f5f5;
            border-radius: 5px;
            border: 2px solid #27ae60;
        }
        
        .result h3 {
            color: #27ae60;
            margin-bottom: 10px;
            font-size: 16px;
        }
        
        .hash-output {
            background: white;
            padding: 15px;
            border-radius: 4px;
            word-break: break-all;
            font-family: monospace;
            font-size: 12px;
            color: #333;
            border: 1px solid #ddd;
        }
        
        .copy-btn {
            background: #27ae60;
            margin-top: 10px;
            padding: 10px;
            font-size: 14px;
        }
        
        .copy-btn:hover {
            background: #229954;
        }
        
        .error {
            background: #ffebee;
            padding: 15px;
            border-radius: 5px;
            color: #c62828;
            margin-bottom: 20px;
            border-left: 4px solid #c62828;
        }
        
        .instructions {
            background: #fff3e0;
            padding: 15px;
            border-radius: 5px;
            margin-top: 20px;
            font-size: 14px;
            color: #e65100;
            border-left: 4px solid #e65100;
        }
        
        .instructions h4 {
            margin-bottom: 10px;
        }
        
        .instructions ol {
            margin-left: 20px;
        }
        
        .instructions li {
            margin-bottom: 8px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Password Hashing Utility</h1>
        <div class="info">
            Use this tool to generate secure password hashes for your database
        </div>
        
        <?php if (isset($error)): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="password">Enter Password to Hash:</label>
                <input type="password" id="password" name="password" placeholder="Enter password" required>
            </div>
            <button type="submit">Generate Hash</button>
        </form>
        
        <?php if ($hashedPassword): ?>
            <div class="result">
                <h3>✓ Hash Generated Successfully</h3>
                <p style="margin-bottom: 10px; color: #555;">Copy this hash and paste it in your database:</p>
                <div class="hash-output" id="hashOutput"><?php echo htmlspecialchars($hashedPassword); ?></div>
                <button type="button" class="copy-btn" onclick="copyToClipboard()">📋 Copy Hash</button>
            </div>
            
            <div class="instructions">
                <h4>How to use this hash:</h4>
                <ol>
                    <li>Copy the hash above</li>
                    <li>Go to phpMyAdmin</li>
                    <li>Open your <strong>users</strong> table</li>
                    <li>Insert/Update a user row</li>
                    <li>Paste the hash in the <strong>password</strong> column</li>
                    <li>Save</li>
                </ol>
                <p style="margin-top: 10px;"><strong>SQL Example:</strong></p>
                <code style="display: block; background: white; padding: 10px; border-radius: 4px; margin-top: 8px;">
                    INSERT INTO users (fullName, email, password, userType)<br>
                    VALUES ('Admin Name', 'admin@example.com', '<?php echo htmlspecialchars($hashedPassword); ?>', 'admin');
                </code>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function copyToClipboard() {
            const hashOutput = document.getElementById('hashOutput');
            const text = hashOutput.textContent;
            
            navigator.clipboard.writeText(text).then(() => {
                alert('Hash copied to clipboard!');
            }).catch(() => {
                // Fallback for older browsers
                const textarea = document.createElement('textarea');
                textarea.value = text;
                document.body.appendChild(textarea);
                textarea.select();
                document.execCommand('copy');
                document.body.removeChild(textarea);
                alert('Hash copied to clipboard!');
            });
        }
    </script>
</body>
</html>
