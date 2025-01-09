<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ensure the school_id exists in the session
    if (!isset($_SESSION['school_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'School ID not found in session.']);
        exit;
    }

    $school_id = $_SESSION['school_id'];

    try {
        // Begin transaction
        $conn->begin_transaction();

        // Reset Points in grade_section_course filtered by school_id
        $stmt1 = $conn->prepare(
            "UPDATE grade_section_course gsc
             JOIN departments d ON gsc.department_id = d.id
             JOIN schools s ON d.school_id = s.school_id
             SET gsc.Points = 0
             WHERE s.school_id = ?"
        );
        if (!$stmt1->execute([$school_id])) {
            throw new Exception("Failed to reset grade section course points.");
        }

        // Reset wins and losses in teams filtered by school_id
        $stmt2 = $conn->prepare(
            "UPDATE teams t
             JOIN grade_section_course gsc ON t.grade_section_course_id = gsc.id
             JOIN departments d ON gsc.department_id = d.id
             JOIN schools s ON d.school_id = s.school_id
             SET t.wins = 0, 
                 t.losses = 0
             WHERE s.school_id = ?"
        );
        if (!$stmt2->execute([$school_id])) {
            throw new Exception("Failed to reset team statistics.");
        }

        // Delete brackets and associated data filtered by school_id
        $stmt3 = $conn->prepare(
            "DELETE b
             FROM brackets b
             JOIN departments d ON b.department_id = d.id
             JOIN schools s ON d.school_id = s.school_id
             WHERE s.school_id = ?"
        );
        if (!$stmt3->execute([$school_id])) {
            throw new Exception("Failed to delete brackets and associated data.");
        }

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'status' => 'success', 
            'message' => 'Leaderboards and related data have been reset successfully.'
        ]);
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        echo json_encode([
            'status' => 'error', 
            'message' => 'Failed to reset leaderboards: ' . $e->getMessage()
        ]);
    } finally {
        if (isset($stmt1)) $stmt1->close();
        if (isset($stmt2)) $stmt2->close();
        if (isset($stmt3)) $stmt3->close();
        $conn->close();
    }
} else {
    echo json_encode([
        'status' => 'error', 
        'message' => 'Invalid request method.'
    ]);
}
?>
