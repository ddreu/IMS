<?php
session_start();
include_once '../connection/conn.php';
include '../user_logs/logger.php';

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

    // Fetch player and team details for logging
    $fetch_details_sql = "SELECT 
        p.player_lastname, p.player_firstname, t.team_name 
        FROM players p 
        INNER JOIN teams t ON p.team_id = t.team_id 
        WHERE p.player_id = ?";
    $fetch_stmt = $conn->prepare($fetch_details_sql);
    $fetch_stmt->bind_param("i", $player_id);
    $fetch_stmt->execute();
    $fetch_result = $fetch_stmt->get_result();

    if ($fetch_result->num_rows > 0) {
        $details = $fetch_result->fetch_assoc();
        $player_lastname = $details['player_lastname'];
        $player_firstname = $details['player_firstname'];
        $team_name = $details['team_name'];
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'Player or team details not found.'
        ]);
        exit();
    }

    $fetch_stmt->close();

    // Prepare and execute delete statement
    $delete_sql = "DELETE FROM players WHERE player_id = ?";
    $delete_stmt = $conn->prepare($delete_sql);
    $delete_stmt->bind_param("i", $player_id);

    if ($delete_stmt->execute()) {
        // Log the user action
        $description = "Removed player " . $player_lastname . ", " . $player_firstname . " from team '" . $team_name . "'";
        logUserAction($conn, $_SESSION['user_id'], 'Players', 'Delete', $team_id, $description);

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
