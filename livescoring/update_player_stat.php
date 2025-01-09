<?php
include_once '../connection/conn.php';

header('Content-Type: application/json');

// Get POST data
$data = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    $data = $_POST; // Fallback to $_POST if JSON parsing fails
}

$playerId = isset($data['player_id']) ? intval($data['player_id']) : 0;
$statId = isset($data['stat_id']) ? intval($data['stat_id']) : 0;
$value = isset($data['value']) ? intval($data['value']) : 0;
$scheduleId = isset($_GET['schedule_id']) ? intval($_GET['schedule_id']) : 0;

if (!$playerId || !$statId) {
    echo json_encode(['error' => 'Invalid parameters']);
    exit;
}

try {
    $conn = con();

    // First, get the match_id and game_id from the schedule
    $match_query = "SELECT s.match_id, b.game_id 
                   FROM schedules s 
                   JOIN matches m ON s.match_id = m.match_id 
                   JOIN brackets b ON m.bracket_id = b.bracket_id 
                   WHERE s.schedule_id = ?";
    $match_stmt = $conn->prepare($match_query);
    $match_stmt->bind_param("i", $scheduleId);
    $match_stmt->execute();
    $match_result = $match_stmt->get_result();
    
    if ($match_result->num_rows === 0) {
        echo json_encode(['error' => 'Match not found']);
        exit;
    }
    
    $match_data = $match_result->fetch_assoc();
    $match_id = $match_data['match_id'];
    $game_id = $match_data['game_id'];

    // Get the stat name from game_stats_config
    $stat_query = "SELECT stat_name FROM game_stats_config WHERE config_id = ? AND game_id = ?";
    $stat_stmt = $conn->prepare($stat_query);
    $stat_stmt->bind_param("ii", $statId, $game_id);
    $stat_stmt->execute();
    $stat_result = $stat_stmt->get_result();
    
    if ($stat_result->num_rows === 0) {
        echo json_encode(['error' => 'Invalid stat configuration']);
        exit;
    }
    
    $stat_data = $stat_result->fetch_assoc();
    $stat_name = $stat_data['stat_name'];

    // Check if stat record exists
    $check_query = "SELECT stat_record_id FROM player_match_stats 
                   WHERE player_id = ? AND match_id = ? AND stat_name = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("iis", $playerId, $match_id, $stat_name);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing stat
        $query = "UPDATE player_match_stats 
                 SET stat_value = ? 
                 WHERE player_id = ? AND match_id = ? AND stat_name = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("siis", $value, $playerId, $match_id, $stat_name);
    } else {
        // Insert new stat
        $query = "INSERT INTO player_match_stats 
                 (player_id, match_id, game_id, stat_name, stat_value) 
                 VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iiiss", $playerId, $match_id, $game_id, $stat_name, $value);
    }

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['error' => 'Failed to update stat: ' . $conn->error]);
    }

    $stmt->close();
    $check_stmt->close();
    $match_stmt->close();
    $stat_stmt->close();
    $conn->close();

} catch (Exception $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
