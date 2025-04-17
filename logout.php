<?php
include_once 'connection/conn.php';
include_once 'user_logs/logger.php';
$conn = con();

session_start();

header('Content-Type: application/json');

$response = ['redirect' => 'home.php'];

$is_mobile_app = false;
$school_id = $_SESSION['school_id'] ?? null;

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $fetch_session_query = "SELECT user_agent FROM sessions WHERE user_id = ? ORDER BY created_at DESC LIMIT 1";
    $stmt = mysqli_prepare($conn, $fetch_session_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $user_agent);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    if (strpos($user_agent, 'mobile-app') !== false) {
        $is_mobile_app = true;
    }

    $delete_session_query = "DELETE FROM sessions WHERE user_id = ?";
    $stmt = mysqli_prepare($conn, $delete_session_query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    logUserAction($conn, $user_id, 'sessions', 'Logged out', $user_id, "User Logged out");
}

session_unset();
session_destroy();

if ($is_mobile_app) {
    $response['redirect'] = '../mobile/index.php';
} elseif ($school_id) {
    $response['redirect'] = '../home.php?school_id=' . urlencode($school_id);
} else {
    $response['redirect'] = '../home.php';
}

echo json_encode($response);
exit;
