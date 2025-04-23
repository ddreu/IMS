<?php
session_start();
include '../connection/conn.php';

$conn = con();

$user_id = $_SESSION['user_id'] ?? null;
$department_id = $_GET['department_id'] ?? null;

// Fallback redirect
$redirect_url = $_SERVER['HTTP_REFERER'] ?? '../home.php';

if (!$user_id || !$department_id || !is_numeric($department_id)) {
    header("Location: $redirect_url");
    exit();
}

// Validate department access
$sql = "
    SELECT d.id, d.department_name 
    FROM departments d
    WHERE (
        d.id = (SELECT u.department FROM users u WHERE u.id = ?)
        OR d.id IN (SELECT cd.department_id FROM committee_departments cd WHERE cd.committee_id = ?)
    )
    AND d.id = ?
    LIMIT 1
";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "iii", $user_id, $user_id, $department_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($row = mysqli_fetch_assoc($result)) {
    $_SESSION['department_id'] = $row['id'];
    $_SESSION['department_name'] = $row['department_name'];
    $_SESSION['success_message'] = "Department changed successfully.";

    header("Location: $redirect_url");
    exit();
} else {
    $_SESSION['error_message'] = "Unauthorized department selection.";
    header("Location: $redirect_url");
    exit();
}
