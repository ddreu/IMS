<?php
// update-password.php

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start the session to use session variables for messages
session_start();

date_default_timezone_set('Asia/Manila'); // Set timezone to Philippine Standard Time

include_once 'connection/conn.php'; 
$conn = con();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize POST data
    $token = $_POST['token'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Basic validation
    if (empty($token) || empty($new_password) || empty($confirm_password)) {
        $_SESSION['message'] = [
            'text' => 'All fields are required.',
            'class' => 'error'
        ];
        header("Location: reset-password.php?token=" . urlencode($token));
        exit;
    }

    // Check if passwords match
    if ($new_password !== $confirm_password) {
        $_SESSION['message'] = [
            'text' => 'Passwords do not match.',
            'class' => 'error'
        ];
        header("Location: reset-password.php?token=" . urlencode($token));
        exit;
    }

    // Optional: Implement stronger password validation on the server-side
    /*
    if (!preg_match('/^(?=.*\d)(?=.*[a-z])(?=.*[A-Z]).{8,}$/', $new_password)) {
        $_SESSION['message'] = [
            'text' => 'Password does not meet the required criteria.',
            'class' => 'error'
        ];
        header("Location: reset-password.php?token=" . urlencode($token));
        exit;
    }
    */

    // Hash the token
    $hashed_token = hash("sha256", $token);

    // Retrieve the user with the matching token
    $sql = "SELECT email FROM users WHERE reset_token_hash = ? AND reset_token_expires_at > NOW()";
    $stmt = $conn->prepare($sql);

    if (!$stmt) {
        $_SESSION['message'] = [
            'text' => "Error preparing statement: " . $conn->error,
            'class' => 'error'
        ];
        header("Location: reset-password.php?token=" . urlencode($token));
        exit;
    }

    $stmt->bind_param("s", $hashed_token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
        // Invalid or expired token
        $_SESSION['message'] = [
            'text' => "Invalid or expired token. Please request a new reset link.",
            'class' => 'error'
        ];
        header("Location: reset-password.php?token=" . urlencode($token));
        exit;
    }

    // Token is valid; proceed to update the password
    $user = $result->fetch_assoc();
    $email = $user['email'];

    // Hash the new password
    $password_hash = password_hash($new_password, PASSWORD_DEFAULT);

    // Update the user's password and clear the reset token
    $update_sql = "UPDATE users SET password = ?, reset_token_hash = NULL, reset_token_expires_at = NULL WHERE email = ?";
    $update_stmt = $conn->prepare($update_sql);

    if (!$update_stmt) {
        $_SESSION['message'] = [
            'text' => "Error preparing update statement: " . $conn->error,
            'class' => 'error'
        ];
        header("Location: reset-password.php?token=" . urlencode($token));
        exit;
    }

    $update_stmt->bind_param("ss", $password_hash, $email);
    $update_success = $update_stmt->execute();

    if ($update_success) {
        $_SESSION['message'] = [
            'text' => "Your password has been successfully updated.",
            'class' => 'success'
        ];
        header("Location: reset-password.php?token=" . urlencode($token));
        exit;
    } else {
        $_SESSION['message'] = [
            'text' => "Error updating password: " . $update_stmt->error,
            'class' => 'error'
        ];
        header("Location: reset-password.php?token=" . urlencode($token));
        exit;
    }
} else {
    // If the script is accessed without POST data, redirect to reset-password.php
    header("Location: reset-password.php");
    exit;
}
?>
