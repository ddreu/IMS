<?php
include_once '../connection/conn.php';
$conn = con();
session_start();

// Set response headers
header('Content-Type: application/json');

// Get raw POST data
$input = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($input['schedule_id']) || !isset($input['stats']) || !is_array($input['stats'])) {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid input data'
    ]);
    exit;
}

$schedule_id = $input['schedule_id'];
$stats = $input['stats'];

// Get match_id and game_id from schedule with joins
$match_query = $conn->prepare("
    SELECT 
        m.match_id, 
        b.game_id 
    FROM schedules s
    JOIN matches m ON s.match_id = m.match_id
    JOIN brackets b ON m.bracket_id = b.bracket_id
    WHERE s.schedule_id = ?
");
$match_query->bind_param("i", $schedule_id);
$match_query->execute();
$match_result = $match_query->get_result()->fetch_assoc();

if (!$match_result) {
    echo json_encode([
        'success' => false, 
        'message' => 'Invalid schedule or missing match/game information'
    ]);
    exit;
}

$match_id = $match_result['match_id'];
$game_id = $match_result['game_id'];

// Start transaction for data integrity
$conn->begin_transaction();

try {
    // Prepare statement for inserting player stats
    $stmt = $conn->prepare("INSERT INTO player_match_stats 
        (player_id, match_id, game_id, stat_name, stat_value, created_at) 
        VALUES (?, ?, ?, ?, ?, NOW())");

    // Fetch stat names from game_stats_config
    $stat_names = [];
    $stat_name_query = $conn->prepare("SELECT config_id, stat_name FROM game_stats_config WHERE game_id = ?");
    $stat_name_query->bind_param("i", $game_id);
    $stat_name_query->execute();
    $stat_name_result = $stat_name_query->get_result();
    while ($row = $stat_name_result->fetch_assoc()) {
        $stat_names[$row['config_id']] = $row['stat_name'];
    }

    // Insert each stat
    foreach ($stats as $stat) {
        // Get stat name from config
        $stat_name = $stat_names[$stat['stat_config_id']] ?? 'Unknown Stat';

        $stmt->bind_param(
            "iiisi", 
            $stat['player_id'], 
            $match_id, 
            $game_id, 
            $stat_name,
            $stat['stat_value']
        );
        $stmt->execute();
    }

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true, 
        'message' => 'Player statistics recorded successfully',
        'match_id' => $match_id
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();

    echo json_encode([
        'success' => false, 
        'message' => 'Error saving statistics: ' . $e->getMessage()
    ]);
}

$stmt->close();
$conn->close();