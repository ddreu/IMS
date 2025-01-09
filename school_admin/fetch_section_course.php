<?php
include_once '../connection/conn.php';
$conn = con();

header('Content-Type: application/json'); // Ensure JSON response

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['id'])) {
    $section_id = $_POST['id'];

    // First, fetch the section/course details, including the department_id
    $query = "SELECT * FROM grade_section_course WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $section_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $department_id = $data['department_id']; // Assuming department_id is a field in grade_section_course table

        // Fetch unique grade levels registered in the same department
        $grade_level_query = "SELECT DISTINCT grade_level FROM grade_section_course WHERE department_id = ?";
        $grade_stmt = $conn->prepare($grade_level_query);
        $grade_stmt->bind_param("i", $department_id);
        $grade_stmt->execute();
        $grade_result = $grade_stmt->get_result();

        $grade_levels = [];
        while ($row = $grade_result->fetch_assoc()) {
            $grade_levels[] = $row['grade_level'];
        }

        // Respond with the fetched section/course data and grade levels
        echo json_encode([
            'success' => true,
            'data' => $data,
            'grade_levels' => $grade_levels
        ]);

        $grade_stmt->close();
    } else {
        echo json_encode(['success' => false, 'message' => 'Section/Course not found']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
