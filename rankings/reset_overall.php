<?php
session_start();
include_once '../connection/conn.php';
include_once '../user_logs/logger.php';

$conn = con();
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_SESSION['school_id'], $_SESSION['user_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'Session data missing.']);
        exit;
    }

    $school_id = $_SESSION['school_id'];
    $user_id = $_SESSION['user_id'];

    // Get posted data
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);

    $department_id = $data['department_id'] ?? null;
    $grade_level = $data['grade_level'] ?? null; // Will be null for College

    if (!$department_id) {
        echo json_encode(['status' => 'error', 'message' => 'Department ID is required.']);
        exit;
    }

    try {
        $conn->begin_transaction();

        // Build query based on grade level existence
        if (!empty($grade_level)) {
            $stmt = $conn->prepare(
                "UPDATE grade_section_course 
                 SET Points = 0 
                 WHERE department_id = ? AND grade_level = ?"
            );
            $stmt->bind_param("is", $department_id, $grade_level);
        } else {
            $stmt = $conn->prepare(
                "UPDATE grade_section_course 
                 SET Points = 0 
                 WHERE department_id = ?"
            );
            $stmt->bind_param("i", $department_id);
        }

        if (!$stmt->execute()) {
            throw new Exception("Failed to reset Points.");
        }

        // Logging action
        $description = "Reset overall leaderboard points for department ID $department_id";
        if (!empty($grade_level)) {
            $description .= " and grade level $grade_level";
        }

        logUserAction($conn, $user_id, 'leaderboard', 'RESET_OVERALL', null, $description);

        $conn->commit();

        echo json_encode([
            'status' => 'success',
            'message' => 'Overall leaderboard points reset successfully.'
        ]);
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode([
            'status' => 'error',
            'message' => 'Reset failed: ' . $e->getMessage()
        ]);
    } finally {
        if (isset($stmt)) $stmt->close();
        $conn->close();
    }
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid request method.'
    ]);
}
