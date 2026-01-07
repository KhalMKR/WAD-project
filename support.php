<?php
session_start();

require 'config.php'; // Include configuration

// Redirect admin users back to admin dashboard
if (isset($_SESSION['userType']) && $_SESSION['userType'] === 'admin') {
    header('Location: ./backend_8sp/index.php');
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['email']) || !isset($_SESSION['fullName'])) {
    header("Location: login.html");
    exit();
}

$email = htmlspecialchars($_SESSION['email']);
$fullName = htmlspecialchars($_SESSION['fullName']);
$userID = isset($_SESSION['userID']) ? $_SESSION['userID'] : '';

// Handle form submission
$messageSent = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject = htmlspecialchars(trim($_POST['subject'] ?? ''));
    $message = htmlspecialchars(trim($_POST['message'] ?? ''));
    
    if (!empty($subject) && !empty($message)) {
        // Send email using Brevo REST API
        try {
            $emailData = array(
                'sender' => array(
                    'name' => 'UniMerch Hub',
                    'email' => 'muhdkhalishreeza@gmail.com'
                ),
                'to' => array(
                    array(
                        'email' => 'afiqzharfan24@gmail.com',
                        'name' => 'UniMerch Support'
                    )
                ),
                'replyTo' => array(
                    'email' => $email,
                    'name' => $fullName
                ),
                'subject' => "Support Request: " . $subject,
                'htmlContent' => "<p><strong>From:</strong> " . $fullName . " (" . $email . ")</p><p><strong>Message:</strong></p><p>" . nl2br($message) . "</p>"
            );
            
            // Initialize cURL request
            $curl = curl_init();
            curl_setopt_array($curl, array(
                CURLOPT_URL => BREVO_API_URL,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => "",
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => "POST",
                CURLOPT_POSTFIELDS => json_encode($emailData),
                CURLOPT_HTTPHEADER => array(
                    "api-key: " . BREVO_API_KEY,
                    "Content-Type: application/json",
                    "Accept: application/json"
                ),
            ));
            
            $response = curl_exec($curl);
            $err = curl_error($curl);
            curl_close($curl);
            
            if ($err) {
                $error = 'Failed to send message. Please try again later.';
                error_log("Email error: $err");
            } else {
                $responseData = json_decode($response, true);
                if (isset($responseData['messageId']) || isset($responseData['id'])) {
                    $messageSent = true;
                } else {
                    $error = 'Failed to send message. Please try again later.';
                    error_log("Brevo API error: $response");
                }
            }
        } catch (Exception $e) {
            $error = 'Failed to send message. Please try again later.';
            error_log("Email exception: " . $e->getMessage());
        }
    } else {
        $error = 'Please fill in all fields.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'header.php'; ?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact Support - UniMerch Hub</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@24,400,0,0" />
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .support-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 40px 20px;
        }

        .support-header {
            text-align: center;
            margin-bottom: 40px;
        }

        .support-header h1 {
            font-size: 36px;
            color: #333;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .support-header p {
            font-size: 16px;
            color: #666;
        }

        .support-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        .support-card {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .support-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.2);
        }

        .support-card h2 {
            font-size: 24px;
            color: #333;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .support-card .material-symbols-outlined {
            color: #667eea;
            font-size: 32px;
        }

        .support-card p {
            color: #666;
            line-height: 1.6;
            margin-bottom: 15px;
        }

        .contact-info {
            list-style: none;
            padding: 0;
        }

        .contact-info li {
            padding: 12px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            align-items: center;
            gap: 10px;
            color: #555;
        }

        .contact-info li:last-child {
            border-bottom: none;
        }

        .contact-info .material-symbols-outlined {
            font-size: 20px;
            color: #667eea;
        }

        .form-card {
            grid-column: 1 / -1;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .form-card h2 {
            color: white;
        }

        .form-card .material-symbols-outlined {
            color: white;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: white;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            background: rgba(255, 255, 255, 0.9);
            transition: all 0.3s ease;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: white;
            background: white;
        }

        .form-group textarea {
            min-height: 150px;
            resize: vertical;
        }

        .submit-btn {
            background: white;
            color: #667eea;
            padding: 14px 40px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .submit-btn:hover {
            background: #f0f0f0;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .submit-btn .material-symbols-outlined {
            font-size: 20px;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert-success {
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.4);
            color: white;
        }

        .alert-error {
            background: rgba(255, 100, 100, 0.2);
            border: 2px solid rgba(255, 100, 100, 0.4);
            color: white;
        }

        .faq-section {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .faq-section h2 {
            font-size: 28px;
            color: #333;
            margin-bottom: 25px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .faq-item {
            border-bottom: 1px solid #eee;
            padding: 20px 0;
        }

        .faq-item:last-child {
            border-bottom: none;
        }

        .faq-question {
            font-weight: 600;
            color: #333;
            font-size: 16px;
            margin-bottom: 10px;
            display: flex;
            align-items: flex-start;
            gap: 10px;
        }

        .faq-question .material-symbols-outlined {
            color: #667eea;
            font-size: 20px;
            margin-top: 2px;
        }

        .faq-answer {
            color: #666;
            line-height: 1.6;
            padding-left: 30px;
        }

        @media (max-width: 768px) {
            .support-grid {
                grid-template-columns: 1fr;
            }

            .support-header h1 {
                font-size: 28px;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar">
        <div class="container">
            <a href="index.php" class="logo">
                <img src="assets/images/logo.png" alt="UniMerch Hub Logo">
            </a>
            <ul class="nav-links">
                <li><a href="index.php">Home</a></li>
                <li><a href="product.php">Products</a></li>
                <li><a href="cart.php" class="cart-link">
                    <span class="material-symbols-outlined">shopping_cart</span>
                    Cart <span id="cart-count" class="cart-badge">0</span>
                </a></li>
                <li><a href="profile.php">Profile</a></li>
                <li><a href="support.php" class="active">Support</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
        </div>
    </nav>

    <div class="support-container">
        <div class="support-header">
            <h1>Contact Support</h1>
            <p>We're here to help! Get in touch with us for any questions or concerns.</p>
        </div>

        <div class="support-grid">
            <!-- Contact Information Card -->
            <div class="support-card">
                <h2>
                    <span class="material-symbols-outlined">contact_mail</span>
                    Contact Information
                </h2>
                <p>Reach out to us through any of these channels:</p>
                <ul class="contact-info">
                    <li>
                        <span class="material-symbols-outlined">mail</span>
                        <span>support@unimerchhub.com</span>
                    </li>
                    <li>
                        <span class="material-symbols-outlined">phone</span>
                        <span>+60 12-345 6789</span>
                    </li>
                    <li>
                        <span class="material-symbols-outlined">location_on</span>
                        <span>UNIMAS, Kota Samarahan, Sarawak</span>
                    </li>
                    <li>
                        <span class="material-symbols-outlined">schedule</span>
                        <span>Mon - Fri: 9:00 AM - 5:00 PM</span>
                    </li>
                </ul>
            </div>

            <!-- Quick Links Card -->
            <div class="support-card">
                <h2>
                    <span class="material-symbols-outlined">link</span>
                    Quick Links
                </h2>
                <p>Find quick answers and resources:</p>
                <ul class="contact-info">
                    <li>
                        <span class="material-symbols-outlined">help</span>
                        <a href="#faq" style="color: #667eea; text-decoration: none;">Frequently Asked Questions</a>
                    </li>
                    <li>
                        <span class="material-symbols-outlined">shopping_bag</span>
                        <a href="orderhistory.php" style="color: #667eea; text-decoration: none;">Order History</a>
                    </li>
                    <li>
                        <span class="material-symbols-outlined">policy</span>
                        <a href="#" style="color: #667eea; text-decoration: none;">Return Policy</a>
                    </li>
                    <li>
                        <span class="material-symbols-outlined">local_shipping</span>
                        <a href="#" style="color: #667eea; text-decoration: none;">Shipping Information</a>
                    </li>
                </ul>
            </div>

            <!-- Contact Form Card -->
            <div class="support-card form-card">
                <h2>
                    <span class="material-symbols-outlined">send</span>
                    Send us a Message
                </h2>
                
                <?php if ($messageSent): ?>
                    <div class="alert alert-success">
                        <span class="material-symbols-outlined">check_circle</span>
                        <span>Your message has been sent successfully! We'll get back to you soon.</span>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-error">
                        <span class="material-symbols-outlined">error</span>
                        <span><?php echo $error; ?></span>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="name">Your Name</label>
                        <input type="text" id="name" name="name" value="<?php echo $fullName; ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="email">Your Email</label>
                        <input type="email" id="email" name="email" value="<?php echo $email; ?>" readonly>
                    </div>

                    <div class="form-group">
                        <label for="subject">Subject</label>
                        <select id="subject" name="subject" required>
                            <option value="">Select a subject</option>
                            <option value="Order Issue">Order Issue</option>
                            <option value="Product Inquiry">Product Inquiry</option>
                            <option value="Payment Issue">Payment Issue</option>
                            <option value="Shipping Question">Shipping Question</option>
                            <option value="Account Help">Account Help</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="message">Message</label>
                        <textarea id="message" name="message" required placeholder="Please describe your issue or question in detail..."></textarea>
                    </div>

                    <button type="submit" class="submit-btn">
                        <span class="material-symbols-outlined">send</span>
                        Send Message
                    </button>
                </form>
            </div>
        </div>

        <!-- FAQ Section -->
        <div class="faq-section" id="faq">
            <h2>
                <span class="material-symbols-outlined">quiz</span>
                Frequently Asked Questions
            </h2>

            <div class="faq-item">
                <div class="faq-question">
                    <span class="material-symbols-outlined">help_outline</span>
                    <span>How long does shipping take?</span>
                </div>
                <div class="faq-answer">
                    Standard shipping typically takes 3-5 business days within Malaysia. Express shipping options are available for faster delivery.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span class="material-symbols-outlined">help_outline</span>
                    <span>What payment methods do you accept?</span>
                </div>
                <div class="faq-answer">
                    We accept Credit Card, Debit Card, Online Banking, and Cash on Delivery (COD) for your convenience.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span class="material-symbols-outlined">help_outline</span>
                    <span>Can I return or exchange items?</span>
                </div>
                <div class="faq-answer">
                    Yes! We offer a 14-day return/exchange policy for unused items in original condition. Please contact support to initiate a return.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span class="material-symbols-outlined">help_outline</span>
                    <span>How can I track my order?</span>
                </div>
                <div class="faq-answer">
                    You can track your order by visiting the Order History page in your profile. You'll receive a tracking number once your order ships.
                </div>
            </div>

            <div class="faq-item">
                <div class="faq-question">
                    <span class="material-symbols-outlined">help_outline</span>
                    <span>Do you offer bulk discounts?</span>
                </div>
                <div class="faq-answer">
                    Yes! For bulk orders (10+ items), please contact our support team for special pricing and arrangements.
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/data.js"></script>
    <script>
        // Update cart count on page load
        updateCartCount();
    </script>
</body>
</html>
