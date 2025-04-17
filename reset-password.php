<?php
// reset-password.php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session to use session variables for messages
session_start();

date_default_timezone_set('Asia/Manila'); // Set timezone to Philippine Standard Time

include_once 'connection/conn.php';
$conn = con();

$message = '';
$message_class = '';
$show_form = false; // Flag to control form display

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    if (!$token) {
        $message = "No token provided.";
        $message_class = "error";
    } else {
        // Hash the token
        $hashed_token = hash("sha256", $token);

        // Debugging: Log the token received
        error_log("Token from URL: $token");
        error_log("Hashed Token: $hashed_token");

        // Validate the token from the database
        $sql = "SELECT email FROM users WHERE reset_token_hash = ? AND reset_token_expires_at > NOW()";
        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            $message = "Error preparing statement: " . $conn->error;
            $message_class = "error";
            error_log($message);
        } else {
            $stmt->bind_param("s", $hashed_token);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 1) {
                // Token is valid; proceed to show the form
                error_log("Valid token for user.");
                $show_form = true;
            } else {
                // Invalid or expired token
                $message = "Invalid or expired token. Please request a new reset link.";
                $message_class = "error";
                error_log($message);
            }
        }
    }
} else {
    // No token in the URL
    $message = "No token provided.";
    $message_class = "error";
    error_log($message);
}

// Check if there are any messages to display from the session
if (isset($_SESSION['message'])) {
    $message = $_SESSION['message']['text'];
    $message_class = $_SESSION['message']['class'];
    unset($_SESSION['message']); // Clear the message after displaying it

    // If there's a session message related to form errors, show the form
    if ($message_class === 'error' && strpos($message, 'password') !== false) {
        $show_form = true;
    }

    // If the message is a success, do not show the form
    if ($message_class === 'success') {
        $show_form = false;
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/water.css@2/out/water.css">
    <style>
        .success {
            color: green;
        }

        .error {
            color: red;
        }

        .login-link {
            margin-top: 20px;
        }
    </style>
</head>

<body>

    <div class="container">
        <h1>Reset Password</h1>

        <?php if ($message): ?>
            <p class="<?php echo $message_class; ?>"><?php echo htmlspecialchars($message); ?></p>
        <?php endif; ?>

        <?php if ($show_form): ?>
            <form method="post" action="update-password.php">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <label for="new_password">New Password:</label>
                <input type="password" name="new_password" id="new_password" required>

                <label for="confirm_password">Confirm Password:</label>
                <input type="password" name="confirm_password" id="confirm_password" required>

                <button type="submit">Reset Password</button>
            </form>
        <?php endif; ?>

        <div class="login-link">
            <a href="login.php">Go to Login Page</a>
        </div>
    </div>

</body>

</html>