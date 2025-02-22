<?php
header('Content-Type: application/json');
include_once '../connection/conn.php';
$conn = con();
session_start();

// Check if the request is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);

if (!isset($data['game_id']) || !isset($data['team_id']) || !isset($data['stat_type']) || !isset($data['value']) || !isset($data['teamA_id']) || !isset($data['schedule_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

$game_id = $data['game_id'];
$team_id = $data['team_id'];
$stat_type = $data['stat_type'];
$value = intval($data['value']);

// Validate stat_type
if (!in_array($stat_type, ['timeouts', 'fouls'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid stat type']);
    exit;
}

try {
    // Map stat_type to database column
    $column = '';
    if ($stat_type === 'timeouts') {
        $column = $team_id === $data['teamA_id'] ? 'timeout_teamA' : 'timeout_teamB';
    } else if ($stat_type === 'fouls') {
        $column = $team_id === $data['teamA_id'] ? 'foul_teamA' : 'foul_teamB';
    }

    // Update the live_scores table
    $sql = "UPDATE live_scores SET $column = ? WHERE game_id = ? AND schedule_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$value, $game_id, $data['schedule_id']]);
    
    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Team stats updated successfully']);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'No record found to update']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;

