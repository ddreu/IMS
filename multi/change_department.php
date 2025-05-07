<?php
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

session_start();
header('Content-Type: application/json');
include_once '../connection/conn.php';

$conn = con();

// Ensure the request is a POST with JSON content
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$department_id = $input['department_id'] ?? null;

if (!$department_id || !is_numeric($department_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid department ID']);
    exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? null; // Add the role check
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// If the user is superadmin or School Admin, skip the validation check
if ($role !== 'superadmin' && $role !== 'School Admin') {
    // Validate the department change request for non-admin users
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
        session_write_close();

        echo json_encode([
            'success' => true,
            'message' => 'Department changed successfully',
            'log' => 'Session write close called after department change.',
            'session' => $_SESSION // Optional: helpful for debugging
        ]);
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'Unauthorized department change']);
        exit();
    }
} else {
    // For superadmin and School Admin, skip the department validation check
    $sql = "
        SELECT d.id, d.department_name 
        FROM departments d
        WHERE d.id = ?
        LIMIT 1
    ";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $department_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        $_SESSION['department_id'] = $row['id'];
        $_SESSION['department_name'] = $row['department_name'];
        session_write_close();

        echo json_encode([
            'success' => true,
            'message' => 'Department changed successfully',
            'log' => 'Session write close called after department change.',
            'session' => $_SESSION // Optional: helpful for debugging
        ]);
        exit();
    } else {
        echo json_encode(['success' => false, 'message' => 'Unauthorized department change']);
        exit();
    }
}
?>

// header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
// header("Pragma: no-cache");

// session_start();
// header('Content-Type: application/json');
// include_once '../connection/conn.php';

// $conn = con();

// // Ensure the request is a POST with JSON content
// if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
// echo json_encode(['success' => false, 'message' => 'Invalid request method']);
// exit;
// }

// $input = json_decode(file_get_contents('php://input'), true);
// $department_id = $input['department_id'] ?? null;

// if (!$department_id || !is_numeric($department_id)) {
// echo json_encode(['success' => false, 'message' => 'Invalid department ID']);
// exit;
// }

// $user_id = $_SESSION['user_id'] ?? null;
// if (!$user_id) {
// echo json_encode(['success' => false, 'message' => 'User not logged in']);
// exit;
// }

// // Fix the precedence issue in SQL
// $sql = "
// SELECT d.id, d.department_name
// FROM departments d
// WHERE (
// d.id = (SELECT u.department FROM users u WHERE u.id = ?)
// OR d.id IN (SELECT cd.department_id FROM committee_departments cd WHERE cd.committee_id = ?)
// )
// AND d.id = ?
// LIMIT 1
// ";

// $stmt = mysqli_prepare($conn, $sql);
// mysqli_stmt_bind_param($stmt, "iii", $user_id, $user_id, $department_id);
// mysqli_stmt_execute($stmt);
// $result = mysqli_stmt_get_result($stmt);

// if ($row = mysqli_fetch_assoc($result)) {
// $_SESSION['department_id'] = $row['id'];
// $_SESSION['department_name'] = $row['department_name'];
// session_write_close();

// echo json_encode([
// 'success' => true,
// 'message' => 'Department changed successfully',
// 'log' => 'Session write close called after department change.',
// 'session' => $_SESSION // Optional: helpful for debugging
// ]);
// } else {
// echo json_encode(['success' => false, 'message' => 'Unauthorized department change']);
// }