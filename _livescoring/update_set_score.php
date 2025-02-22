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

// Extract data with the new `live_set_scores` structure
$schedule_id = $data['schedule_id'] ?? null;
$game_id = $data['game_id'] ?? null;
$teamA_id = $data['teamA_id'] ?? null;
$teamB_id = $data['teamB_id'] ?? null;
$teamA_score = $data['teamA_score'] ?? 0;
$teamB_score = $data['teamB_score'] ?? 0;
$teamA_sets_won = $data['teamA_sets_won'] ?? 0;
$teamB_sets_won = $data['teamB_sets_won'] ?? 0;
$current_set = $data['current_set'] ?? 1;
$timeout_teamA = $data['timeout_teamA'] ?? 0;
$timeout_teamB = $data['timeout_teamB'] ?? 0;

// Validate required fields
if (!$schedule_id || !$game_id || !$teamA_id || !$teamB_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing required fields']);
    exit;
}

try {
    // Check if a record exists
    $check_stmt = $conn->prepare("SELECT live_set_score_id FROM live_set_scores WHERE schedule_id = ?");
    $check_stmt->bind_param("i", $schedule_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing record
        $stmt = $conn->prepare("UPDATE live_set_scores 
                              SET teamA_score = ?, teamB_score = ?, 
                                  teamA_sets_won = ?, teamB_sets_won = ?, 
                                  current_set = ?, timeout_teamA = ?, timeout_teamB = ?, 
                                  timestamp = NOW()
                              WHERE schedule_id = ?");
        $stmt->bind_param("iiiiiiii", 
            $teamA_score, $teamB_score, 
            $teamA_sets_won, $teamB_sets_won, 
            $current_set, $timeout_teamA, $timeout_teamB,
            $schedule_id
        );
    } else {
        // Insert new record
        $stmt = $conn->prepare("INSERT INTO live_set_scores 
                              (schedule_id, game_id, teamA_id, teamB_id, 
                               teamA_score, teamB_score, teamA_sets_won, teamB_sets_won, 
                               current_set, timeout_teamA, timeout_teamB, timestamp) 
                              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("iiiiiiiiiii", 
            $schedule_id, $game_id, $teamA_id, $teamB_id, 
            $teamA_score, $teamB_score, 
            $teamA_sets_won, $teamB_sets_won, 
            $current_set, $timeout_teamA, $timeout_teamB
        );
    }

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    // Log the received data for debugging
    error_log(json_encode([
        'schedule_id' => $schedule_id,
        'game_id' => $game_id,
        'teamA_id' => $teamA_id,
        'teamB_id' => $teamB_id,
        'teamA_score' => $teamA_score,
        'teamB_score' => $teamB_score,
        'teamA_sets_won' => $teamA_sets_won,
        'teamB_sets_won' => $teamB_sets_won,
        'current_set' => $current_set,
        'timeout_teamA' => $timeout_teamA,
        'timeout_teamB' => $timeout_teamB
    ]));

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} finally {
    if (isset($check_stmt)) $check_stmt->close();
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
