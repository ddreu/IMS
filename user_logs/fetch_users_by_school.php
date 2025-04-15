<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

$school_id = $_SESSION['school_id'];

$stmt = $conn->prepare("
    SELECT id, 
    CASE 
        WHEN firstname IS NULL OR lastname IS NULL OR firstname = '' OR lastname = ''
        THEN email
        ELSE CONCAT(firstname, ' ', middleinitial, ' ', lastname)
    END AS full_name
    FROM users
    WHERE school_id = ? AND is_archived = 0
");

$stmt->bind_param("i", $school_id);
$stmt->execute();
$result = $stmt->get_result();
$users = [];

while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

header('Content-Type: application/json');
echo json_encode(['status' => 'success', 'users' => $users]);
