<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

try {
    include_once '../connection/conn.php';
    $conn = con();
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Read JSON data
    $input = file_get_contents("php://input");
    $data = json_decode($input, true);
    
    // Basic validation
    if (!isset($data['schedule_id'], $data['game_id'], $data['teamA_id'], $data['teamB_id'])) {
        throw new Exception("Missing required fields");
    }

    // Extract data with type casting
    $schedule_id = (int)$data['schedule_id'];
    $game_id = (int)$data['game_id'];
    $teamA_id = (int)$data['teamA_id'];
    $teamB_id = (int)$data['teamB_id'];
    $teamA_score = isset($data['teamA_score']) ? (int)$data['teamA_score'] : 0;
    $teamB_score = isset($data['teamB_score']) ? (int)$data['teamB_score'] : 0;
    $timeout_teamA = isset($data['timeout_teamA']) ? (int)$data['timeout_teamA'] : 0;
    $timeout_teamB = isset($data['timeout_teamB']) ? (int)$data['timeout_teamB'] : 0;
    $foul_teamA = isset($data['foul_teamA']) ? (int)$data['foul_teamA'] : 0;
    $foul_teamB = isset($data['foul_teamB']) ? (int)$data['foul_teamB'] : 0;
    $period = isset($data['periods']) ? $data['periods'] : '1';
    
    // Get timer values
    $time_remaining = isset($data['time_remaining']) ? $data['time_remaining'] : NULL;
    $timer_status = isset($data['timer_status']) ? $data['timer_status'] : 'paused';

    // Insert or update query with timer fields
    $sql = "INSERT INTO live_scores 
            (schedule_id, game_id, teamA_id, teamB_id, teamA_score, teamB_score, 
            timeout_teamA, timeout_teamB, foul_teamA, foul_teamB, period, 
            time_remaining, timer_status, timestamp, last_timer_update) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), CURRENT_TIMESTAMP)
            ON DUPLICATE KEY UPDATE
            teamA_score = VALUES(teamA_score),
            teamB_score = VALUES(teamB_score),
            timeout_teamA = VALUES(timeout_teamA),
            timeout_teamB = VALUES(timeout_teamB),
            foul_teamA = VALUES(foul_teamA),
            foul_teamB = VALUES(foul_teamB),
            period = VALUES(period),
            time_remaining = VALUES(time_remaining),
            timer_status = VALUES(timer_status),
            timestamp = NOW(),
            last_timer_update = CURRENT_TIMESTAMP";

    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }

    $stmt->bind_param("iiiiiiiiiisss", 
        $schedule_id, $game_id, $teamA_id, $teamB_id,
        $teamA_score, $teamB_score,
        $timeout_teamA, $timeout_teamB,
        $foul_teamA, $foul_teamB,
        $period,
        $time_remaining, $timer_status
    );

    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }

    echo json_encode([
        "status" => "success",
        "message" => "Score updated successfully",
        "affected_rows" => $stmt->affected_rows
    ]);

} catch (Exception $e) {
    error_log("Score update error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}
