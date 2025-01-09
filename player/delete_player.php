<?php
session_start();
include_once '../connection/conn.php';

header('Content-Type: application/json');

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    // Validate input data
    $player_id = isset($data['player_id']) ? intval($data['player_id']) : null;
    $team_id = isset($data['team_id']) ? intval($data['team_id']) : null;
    $grade_section_course_id = isset($data['grade_section_course_id']) ? intval($data['grade_section_course_id']) : null;

    if (!$player_id || !$team_id) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid player or team information.'
        ]);
        exit();
    }

    // Database connection
    $conn = con();

    // Prepare and execute delete statement
    $delete_sql = "DELETE FROM players WHERE player_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $player_id);

    if ($delete_stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Player deleted successfully.'
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to delete player.'
        ]);
    }

    $delete_stmt->close();
    $conn->close();
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
}
