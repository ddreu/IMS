<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

$school_id = $_SESSION['school_id']; // Assuming session stores school_id

$query = "SELECT id, department_name FROM departments WHERE school_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $school_id);
$stmt->execute();
$result = $stmt->get_result();

$departments = [];
while ($row = $result->fetch_assoc()) {
    $departments[] = $row;
}

echo json_encode($departments);
