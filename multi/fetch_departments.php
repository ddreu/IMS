<?php
session_start();
header('Content-Type: application/json');
include_once '../connection/conn.php';

$conn = con();
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit;
}

// Step 1: Fetch the user's own department
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

$departments = [];
while ($row = mysqli_fetch_assoc($result)) {
    if ($row['department_id'] && $row['department_name']) {
        $departments[] = [
            'id' => $row['department_id'],
            'name' => $row['department_name']
        ];
    }
}

// Step 2: Fetch committee-assigned departments
$sql2 = "
    SELECT d.id AS department_id, d.department_name
    FROM committee_departments cd
    JOIN departments d ON cd.department_id = d.id
    WHERE cd.committee_id = ?
";

$stmt2 = mysqli_prepare($conn, $sql2);
mysqli_stmt_bind_param($stmt2, "i", $user_id); // if user_id is committee_id
mysqli_stmt_execute($stmt2);
$result2 = mysqli_stmt_get_result($stmt2);

while ($row = mysqli_fetch_assoc($result2)) {
    $departments[] = [
        'id' => $row['department_id'],
        'name' => $row['department_name']
    ];
}

echo json_encode(['success' => true, 'departments' => $departments]);
