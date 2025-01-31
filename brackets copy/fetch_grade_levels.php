<?php
include_once '../connection/conn.php';
$conn = con();
session_start();

// Validate session variables
$school_id = $_SESSION['school_id'] ?? null;
$department_id = $_SESSION['department_id'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;

$response = [];

if ($school_id && $department_id) {
    // Fetch department name
    $stmt = $conn->prepare("SELECT department FROM users WHERE id = ? AND school_id = ?");
    $stmt->bind_param("ii", $user_id, $school_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $department = $result->fetch_assoc()['department'];

        // Query based on specific departments
        if (in_array($department, ['Elementary', 'JHS', 'SHS'])) {
            $grade_stmt = $conn->prepare("SELECT id, grade_level FROM grade_section_course WHERE department_id = ?");
            $grade_stmt->bind_param("i", $department_id);
            $grade_stmt->execute();
            $grade_result = $grade_stmt->get_result();

            $gradeLevels = [];
            while ($grade = $grade_result->fetch_assoc()) {
                $gradeLevels[] = ['id' => $grade['id'], 'name' => $grade['grade_level']];
            }

            $response['gradeLevels'] = $gradeLevels;
        } else {
            $response['gradeLevels'] = []; // College or other departments without grade levels
        }
    } else {
        $response['error'] = "Department not found for this user.";
    }
} else {
    $response['error'] = "User session data missing.";
}

echo json_encode($response);
