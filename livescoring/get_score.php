<?php
include_once '../connection/conn.php';
$conn = con();

header('Content-Type: application/json');

// Get parameters
$schedule_id = $_GET['schedule_id'] ?? null;
$game_id = $_GET['game_id'] ?? null;

// Validate required parameters
if (!$schedule_id || !$game_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
    exit;
}

try {
    // Get the current score and stats
    $stmt = $conn->prepare("SELECT * FROM live_scores WHERE schedule_id = ? AND game_id = ?");
    $stmt->bind_param("ii", $schedule_id, $game_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        echo json_encode(['status' => 'success', 'data' => $data]);
    } else {
        // Return default values if no record exists
        $defaultData = [
            'schedule_id' => $schedule_id,
            'game_id' => $game_id,
            'teamA_score' => 0,
            'teamB_score' => 0,
            'timeout_teamA' => 0,
            'timeout_teamB' => 0,
            'foul_teamA' => 0,
            'foul_teamB' => 0,
            'period' => 1,
            'time_remaining' => null,
            'timer_status' => 'paused'
        ];
        echo json_encode(['status' => 'success', 'data' => $defaultData]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
