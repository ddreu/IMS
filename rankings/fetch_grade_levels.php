<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

$department_id = isset($_POST['department_id']) ? $_POST['department_id'] : (isset($_GET['department_id']) ? $_GET['department_id'] : null);

if (!isset($department_id) || empty($department_id)) {
    echo json_encode(['error' => 'Invalid department ID']);
    exit;
}

$query = "SELECT DISTINCT grade_level FROM grade_section_course WHERE department_id = ? AND is_archived = 0";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $department_id);

if (!$stmt->execute()) {
    echo json_encode(['error' => 'Database query failed']);
    exit;
}

$result = $stmt->get_result();
$grade_levels = [];
while ($row = $result->fetch_assoc()) {
    $grade_levels[] = $row['grade_level'];
}

echo json_encode($grade_levels);
