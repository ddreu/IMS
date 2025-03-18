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

    // Delete the session record from the sessions table
    $delete_session_query = "DELETE FROM sessions WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $delete_session_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    // **Log the action BEFORE destroying session**
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

// Redirect to home page with the school ID as a URL parameter
if ($school_id) {
    header('Location: home.php?school_id=' . urlencode($school_id));
} else {
    header('Location: home.php');
}
exit();
