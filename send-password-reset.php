<?php
// send-password-reset.php

session_start(); // Start session for message handling

date_default_timezone_set('Asia/Manila'); // Set timezone to Philippine Standard Time


include_once 'connection/conn.php'; 
$conn = con();

$message = '';
$message_class = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST["email"]); // Trim to remove any extra spaces

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Please enter a valid email address.";
        $message_class = "error";
    } else {
        require_once __DIR__ . "/email_handler.php";

        // Generate and store token
        $token = generateToken();
        $tokenResult = storeToken($conn, $email, $token);

        if ($tokenResult['success']) {
            // Send password reset email
            $emailResult = sendPasswordResetEmail($email, $token);
            if ($emailResult['success']) {
                $message = $emailResult['message'];
                $message_class = 'success';
            } else {
                $message = $emailResult['message'];
                $message_class = 'error';
            }
        } else {
            $message = $tokenResult['message'];
            $message_class = 'error';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Password Reset Request</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/water.css@2/out/water.css">
    <style>
        .success {
            color: green;
        }
        .error {
            color: red;
        }
    </style>
</head>
<body>

    <div class="container">
        <h1>Password Reset</h1>

        <?php if ($message): ?>
            <p class="<?php echo $message_class; ?>"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <form method="post" action="send-password-reset.php">
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" required>
            <button type="submit">Send Reset Link</button>
        </form>

        <a href="login.php" style="display: block; margin-top: 20px;">Back to Login</a>
    </div>

</body>
</html>
