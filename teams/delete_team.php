<?php
session_start();
include_once '../connection/conn.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$conn = con();

try {
    // Get JSON data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);
    
    if (!isset($data['team_id'])) {
        throw new Exception('Team ID is required');
    }
    
    $team_id = intval($data['team_id']);

    // Start transaction
    $conn->begin_transaction();

    // Delete team players first (if any)
    $delete_players_sql = "DELETE FROM players WHERE team_id = ?";
    $delete_players_stmt = $conn->prepare($delete_players_sql);
    $delete_players_stmt->bind_param("i", $team_id);
    $delete_players_stmt->execute();

    // Delete the team
    $delete_team_sql = "DELETE FROM teams WHERE team_id = ?";
    $delete_team_stmt = $conn->prepare($delete_team_sql);
    $delete_team_stmt->bind_param("i", $team_id);
    
    if ($delete_team_stmt->execute()) {
        $conn->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Team deleted successfully!'
        ]);
    } else {
        throw new Exception('Failed to delete team');
    }

} catch (Exception $e) {
    if ($conn->connect_errno) {
        $conn->rollback();
    }
    error_log("Error in delete_team.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>
