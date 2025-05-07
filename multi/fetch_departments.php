<?php
session_start();
header('Content-Type: application/json');
include_once '../connection/conn.php';

$conn = con();
$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? null; // Add role check
$school_id = $_SESSION['school_id'] ?? null; // Add school_id check
$game_id = $_SESSION['game_id'] ?? null; // Add game_id check

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

$departments = [];

// Step 1: Fetch departments based on the user's role
if ($role === 'superadmin' || $role === 'School Admin') {
    if ($school_id) {
        // Fetch all departments for the given school_id
        $sql = "
            SELECT d.id AS department_id, d.department_name
            FROM departments d
            WHERE d.school_id = ? AND d.is_archived = 0
        ";

        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $school_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        while ($row = mysqli_fetch_assoc($result)) {
            $departments[] = [
                'id' => $row['department_id'],
                'name' => $row['department_name']
            ];
        }
    }
} else {
    // Step 2: Fetch the user's own department (for non-admin users)
    $sql = "
        SELECT d.id AS department_id, d.department_name
        FROM users u
        LEFT JOIN departments d ON u.department = d.id
        WHERE u.id = ?
    ";

    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        if ($row['department_id'] && $row['department_name']) {
            $departments[] = [
                'id' => $row['department_id'],
                'name' => $row['department_name']
            ];
        }
    }

    // Step 3: Fetch committee-assigned departments for the user
    $sql2 = "
        SELECT d.id AS department_id, d.department_name
        FROM committee_departments cd
        JOIN departments d ON cd.department_id = d.id
        WHERE cd.committee_id = ?
    ";

    $stmt2 = mysqli_prepare($conn, $sql2);
    mysqli_stmt_bind_param($stmt2, "i", $user_id); // assuming user_id is the committee_id
    mysqli_stmt_execute($stmt2);
    $result2 = mysqli_stmt_get_result($stmt2);

    while ($row = mysqli_fetch_assoc($result2)) {
        $departments[] = [
            'id' => $row['department_id'],
            'name' => $row['department_name']
        ];
    }
}

echo json_encode(['success' => true, 'departments' => $departments]);
