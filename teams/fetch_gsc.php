<?php
session_start();
include_once '../connection/conn.php';
$conn = con();
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get department_id from the request
$department_id = intval($_GET['department_id']);

// Fetch GSC entries
$sql = "SELECT id, grade_level, strand, section_name, course_name FROM grade_section_course WHERE department_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$result = $stmt->get_result();

$entries = [];
while ($row = $result->fetch_assoc()) {
    $entries[] = $row;
}

// Return JSON data
header('Content-Type: application/json');
echo json_encode($entries);

// Close connection
$stmt->close();
$conn->close();

?>