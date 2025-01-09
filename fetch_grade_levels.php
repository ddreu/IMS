<?php
error_reporting(0); // Disable error reporting for production
header('Content-Type: application/json'); // Set JSON header first

include_once 'connection/conn.php';
$conn = con();

try {
    // Get and validate parameters
    $department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : 0;

    if (!$department_id) {
        throw new Exception('Missing department_id parameter');
    }

    // Check if the department is college
    $query = "SELECT department_name FROM departments WHERE id = ?";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }

    $stmt->bind_param('i', $department_id);
    if (!$stmt->execute()) {
        throw new Exception('Database error: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    $department = $result->fetch_assoc();

    if (!$department) {
        throw new Exception('Department not found');
    }

    if ($department['department_name'] == 'College') {
        echo json_encode([]);
        exit;
    }

    // Fetch grade levels for other departments
    $query = "SELECT DISTINCT grade_level FROM grade_section_course WHERE department_id = ? ORDER BY grade_level";
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }

    $stmt->bind_param('i', $department_id);
    if (!$stmt->execute()) {
        throw new Exception('Database error: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    $grade_levels = [];
    
    while ($row = $result->fetch_assoc()) {
        $grade_levels[] = $row['grade_level'];
    }

    echo json_encode($grade_levels);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode(['error' => $e->getMessage()]);
}
