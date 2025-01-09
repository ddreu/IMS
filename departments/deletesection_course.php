<?php
include_once '../connection/conn.php';
$conn = con();

// Ensure the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseId = $_POST['id'] ?? null;

    // Log the received course ID for debugging
    error_log('Received Course ID: ' . $courseId);

    // Validate and sanitize course ID
    if (isset($courseId) && is_numeric($courseId)) {
        $courseId = intval($courseId);

        // Prepare the DELETE statement
        $query = "DELETE FROM grade_section_course WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);

        // Check if the statement prepared successfully
        if ($stmt === false) {
            error_log('Statement Preparation Error: ' . mysqli_error($conn));
            echo json_encode(['success' => false, 'message' => 'Database preparation failed.']);
            exit();
        }

        // Bind the course ID parameter and execute the statement
        mysqli_stmt_bind_param($stmt, 'i', $courseId);
        if (mysqli_stmt_execute($stmt)) {
            if (mysqli_stmt_affected_rows($stmt) > 0) {
                echo json_encode(['success' => true, 'message' => 'Course/section deleted successfully.']);
            } else {
                // Course ID not found in the database
                echo json_encode(['success' => false, 'message' => 'Course/section not found.']);
            }
        } else {
            // Log SQL error details
            error_log('SQL Execution Error: ' . mysqli_error($conn));
            echo json_encode(['success' => false, 'message' => 'Failed to delete the course/section.']);
        }

        // Close the statement
        mysqli_stmt_close($stmt);
    } else {
        // Invalid or missing course ID
        error_log('Invalid Course ID: ' . $courseId);
        echo json_encode(['success' => false, 'message' => 'Invalid course ID provided.']);
    }
} else {
    // Log and return error for invalid request method
    error_log('Invalid request method: ' . $_SERVER['REQUEST_METHOD']);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

// Close the database connection
mysqli_close($conn);
