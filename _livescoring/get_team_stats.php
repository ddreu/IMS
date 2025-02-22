<?php
header('Content-Type: application/json');
include_once '../connection/conn.php';
$conn = con();
session_start();

// Check if required parameters are provided
if (!isset($_GET['game_id']) || !isset($_GET['schedule_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Game ID and Schedule ID are required']);
    exit;
}

$game_id = $_GET['game_id'];
$schedule_id = $_GET['schedule_id'];

try {
    // Get team stats from live_scores
    $sql = "SELECT 
        teamA_id,
        teamB_id,
        timeout_teamA as teamA_timeouts,
        timeout_teamB as teamB_timeouts,
        foul_teamA as teamA_fouls,
        foul_teamB as teamB_fouls
    FROM live_scores 
    WHERE game_id = ? AND schedule_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute([$game_id, $schedule_id]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result) {
        // Restructure the data to match the frontend's expectations
        $stats = [
            [
                'team_id' => $result['teamA_id'],
                'timeouts' => $result['teamA_timeouts'],
                'fouls' => $result['teamA_fouls']
            ],
            [
                'team_id' => $result['teamB_id'],
                'timeouts' => $result['teamB_timeouts'],
                'fouls' => $result['teamB_fouls']
            ]
        ];
        echo json_encode($stats);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'No stats found']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
    exit;
}
