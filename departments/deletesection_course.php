<?php
include_once '../connection/conn.php';
include '../user_logs/logger.php';  // Include the logger file
$conn = con();
session_start();
$user_id = $_SESSION['user_id'];

// Ensure the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseId = $_POST['id'] ?? null;

    // Validate and sanitize course ID
    if (isset($courseId) && is_numeric($courseId)) {
        $courseId = intval($courseId);

        // Prepare the DELETE statement
        $query = "SELECT gsc.id, gsc.department_id, gsc.course_name, gsc.grade_level, gsc.section_name, gsc.strand, d.department_name
                  FROM grade_section_course gsc
                  JOIN departments d ON gsc.department_id = d.id
                  WHERE gsc.id = ?";
        $stmt = mysqli_prepare($conn, $query);

        // Check if the statement prepared successfully
        if ($stmt === false) {
            echo json_encode(['success' => false, 'message' => 'Database preparation failed.']);
            exit();
        }

        // Bind the course ID parameter and execute the statement
        mysqli_stmt_bind_param($stmt, 'i', $courseId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $course = mysqli_fetch_assoc($result);

        if ($course) {
            // Prepare the DELETE query
            $deleteQuery = "DELETE FROM grade_section_course WHERE id = ?";
            $deleteStmt = mysqli_prepare($conn, $deleteQuery);
            mysqli_stmt_bind_param($deleteStmt, 'i', $courseId);

            if (mysqli_stmt_execute($deleteStmt)) {
                if (mysqli_stmt_affected_rows($deleteStmt) > 0) {
                    // Log the final description after deletion
                    $log_description = "";

                    // Log details based on the department type
                    if ($course['department_name'] === 'College') {
                        $log_description = "Deleted College Department: Course Name = " . $course['course_name'];
                    } elseif ($course['department_name'] === 'SHS') {
                        $log_description = "Deleted SHS Department: Grade Level = " . $course['grade_level'] . ", Section = " . $course['section_name'] . ", Strand = " . $course['strand'];
                    } elseif ($course['department_name'] === 'JHS' || $course['department_name'] === 'Elementary') {
                        $log_description = "Deleted " . $course['department_name'] . " Department: Grade Level = " . $course['grade_level'] . ", Section = " . $course['section_name'];
                    }

                    // Log the deletion
                    logUserAction($conn, $_SESSION['user_id'], "departments", "DELETE", $courseId, $log_description);

                    echo json_encode(['success' => true, 'message' => 'Course/section deleted successfully.']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Course/section not found.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete the course/section.']);
            }

            mysqli_stmt_close($deleteStmt);
        } else {
            echo json_encode(['success' => false, 'message' => 'Course/section not found.']);
        }

        mysqli_stmt_close($stmt);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid course ID provided.']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

// Close the database connection
mysqli_close($conn);
