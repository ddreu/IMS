<?php
include_once '../connection/conn.php';
$conn = con();

// Get JSON data from POST request
$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data received']);
    exit;
}

// Extract data
$schedule_id = $data['schedule_id'] ?? null;
$game_id = $data['game_id'] ?? null;
$teamA_id = $data['teamA_id'] ?? null;
$teamB_id = $data['teamB_id'] ?? null;
$teamA_score = $data['teamA_score'] ?? 0;
$teamB_score = $data['teamB_score'] ?? 0;
$current_period = $data['current_period'] ?? 1;
$time_remaining = $data['time_remaining'] ?? null;
$timer_status = $data['timer_status'] ?? 'paused';

// Validate required fields
if (!$schedule_id || !$game_id || !$teamA_id || !$teamB_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    // Check if a record exists
    $check_stmt = $conn->prepare("SELECT live_score_id FROM live_scores WHERE schedule_id = ?");
    $check_stmt->bind_param("i", $schedule_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing record
        $stmt = $conn->prepare("UPDATE live_scores 
                              SET teamA_score = ?, teamB_score = ?, period = ?, 
                                  time_remaining = ?, timer_status = ?,
                                  last_timer_update = CURRENT_TIMESTAMP 
                              WHERE schedule_id = ?");
        $stmt->bind_param("iisisi", $teamA_score, $teamB_score, $current_period, 
                         $time_remaining, $timer_status, $schedule_id);
    } else {
        // Insert new record
        $stmt = $conn->prepare("INSERT INTO live_scores 
                              (schedule_id, game_id, teamA_id, teamB_id, teamA_score, teamB_score, 
                               period, time_remaining, timer_status) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiiisisi", $schedule_id, $game_id, $teamA_id, $teamB_id, 
                         $teamA_score, $teamB_score, $current_period, $time_remaining, $timer_status);
    }

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} finally {
    if (isset($check_stmt)) $check_stmt->close();
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
