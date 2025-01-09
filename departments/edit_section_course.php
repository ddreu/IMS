<?php
include_once '../connection/conn.php';
$conn = con();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve data from the form submission
    $section_id = $_POST['section_id'];
    $department_name = $_POST['department_level'];

    if ($department_name === 'College') {
        $course_name = $_POST['course_name'];

        // Prepare and execute the update query for college course
        $query = "UPDATE grade_section_course SET course_name = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("si", $course_name, $section_id);
    } else {
        $grade_level = $_POST['grade_level'];
        $section_name = $_POST['section_name'];

        // Prepare and execute the update query for other departments (elementary, JHS, SHS)
        $query = "UPDATE grade_section_course SET grade_level = ?, section_name = ? WHERE id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssi", $grade_level, $section_name, $section_id);
    }

    // Execute and handle success/failure
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update section/course.']);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
}
