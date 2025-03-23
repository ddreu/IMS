<?php

include_once 'connection/conn.php';
include_once 'user_logs/logger.php';
$conn = con();

session_start();

// Check if the user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    // Retrieve the school ID from the session
    $school_id = $_SESSION['school_id'] ?? null;

    // Fetch the most recent session to check if the login was via the WebView app
    $fetch_session_query = "SELECT user_agent FROM sessions WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
    $stmt = mysqli_prepare($conn, $fetch_session_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $user_agent);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // Check if the user logged in using the WebView app
    $is_mobile_app = false;
    if (strpos($user_agent, 'mobile-app') !== false) {
        $is_mobile_app = true;
    }

    // Delete the session record from the sessions table
    $delete_session_query = "DELETE FROM sessions WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $delete_session_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // Log the action before destroying the session
    $description = "User Logged out";
    logUserAction(
        $conn,
        $user_id,
        'sessions',
        'Logged out',
        $user_id,
        $description
    );
}

// **Clear session data and destroy session AFTER logging**
session_unset();
session_destroy();

// Redirect to different pages based on the login source (WebView or regular browser)
if ($is_mobile_app) {
    // Redirect to the mobile version of the page
    header('Location: mobile/index.php');
} else {
    // Redirect to the regular home page
    if ($school_id) {
        header('Location: home.php?school_id=' . urlencode($school_id));
    } else {
        header('Location: home.php');
    }
}

exit();
