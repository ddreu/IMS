<?php
session_start();
header('Content-Type: application/json');

include_once '../connection/conn.php';
$conn = con();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Get department_id from query parameter
$department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;

if (!$department_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Department ID is required']);
    exit();
}

// Fetch grade levels for the specified department
$query = "SELECT DISTINCT gsc.grade_level 
          FROM grade_section_course gsc 
          WHERE gsc.department_id = ? 
          ORDER BY gsc.grade_level";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$result = $stmt->get_result();

$grade_levels = [];
while ($row = $result->fetch_assoc()) {
    if (!empty($row['grade_level'])) {
        $grade_levels[] = $row['grade_level'];
    }
}

$stmt->close();
$conn->close();

// Debug log
error_log("API Response - Grade Levels for Department $department_id: " . print_r($grade_levels, true));

echo json_encode($grade_levels);
