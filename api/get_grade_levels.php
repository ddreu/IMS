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
    echo json_encode([]);
    exit;
}

// Fetch grade levels for the specified department
$query = "SELECT DISTINCT grade_level 
          FROM grade_section_course 
          WHERE department_id = ? 
          AND grade_level IS NOT NULL 
          AND grade_level != ''
          ORDER BY CAST(grade_level AS UNSIGNED)";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$result = $stmt->get_result();

$grade_levels = [];
while ($row = $result->fetch_assoc()) {
    $grade_levels[] = $row['grade_level'];
}

$stmt->close();
$conn->close();

// Debug log
error_log("API Response - Grade Levels for Department $department_id: " . print_r($grade_levels, true));

echo json_encode($grade_levels);
