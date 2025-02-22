<?php
include_once '../connection/conn.php';
$conn = con();

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Capture raw input for debugging
$raw_input = file_get_contents('php://input');
$data = json_decode($raw_input, true);

// Validate and sanitize input
$schedule_id = isset($data['schedule_id']) ? intval($data['schedule_id']) : null;
$match_id = isset($data['match_id']) ? intval($data['match_id']) : null;
$teamA_id = isset($data['teamA_id']) ? intval($data['teamA_id']) : null;
$teamB_id = isset($data['teamB_id']) ? intval($data['teamB_id']) : null;
$teamA_score = isset($data['teamA_score']) ? intval($data['teamA_score']) : 0;
$teamB_score = isset($data['teamB_score']) ? intval($data['teamB_score']) : 0;

// Fetch game_id from brackets linked to matches
$game_id_query = $conn->prepare("
    SELECT b.game_id 
    FROM matches m
    JOIN brackets b ON m.bracket_id = b.bracket_id
    WHERE m.match_id = ?
");

$game_id_query->bind_param("i", $match_id);
$game_id_query->execute();
$game_id_result = $game_id_query->get_result();

if ($game_id_result->num_rows == 0) {
    $response['error'] = 'Could not find game_id for the match';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$game_id = $game_id_result->fetch_assoc()['game_id'];
$game_id_query->close();

// Validate required parameters
$response = ['success' => false];

if (!$schedule_id || !$game_id || !$teamA_id || !$teamB_id) {
    $response['error'] = 'Missing required parameters';
    $response['raw_input'] = $raw_input;
    $response['decoded_data'] = $data;
    
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Verify match exists
$match_check = $conn->prepare("SELECT * FROM matches WHERE match_id = ?");
$match_check->bind_param("i", $match_id);
$match_check->execute();
$match_result = $match_check->get_result();

if ($match_result->num_rows == 0) {
    $response['error'] = 'Invalid match ID';
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Determine current set number
$set_query = $conn->prepare("
    SELECT COALESCE(MAX(period_number), 0) + 1 as next_set 
    FROM match_periods_info 
    WHERE match_id = ?
");
$set_query->bind_param("i", $match_id);
$set_query->execute();
$set_result = $set_query->get_result()->fetch_assoc();
$current_set = $set_result['next_set'];
$set_query->close();

// Determine set winner
$set_winner_id = $teamA_score > $teamB_score ? $teamA_id : 
                 ($teamB_score > $teamA_score ? $teamB_id : null);

// Insert set information into match_periods_info
$periods_query = $conn->prepare("
    INSERT INTO match_periods_info 
    (match_id, period_number, teamA_id, teamB_id, score_teamA, score_teamB, timestamp) 
    VALUES (?, ?, ?, ?, ?, ?, NOW())
");
$periods_query->bind_param("iiiiii", 
    $match_id, $current_set, $teamA_id, $teamB_id, 
    $teamA_score, $teamB_score
);
$periods_insert_result = $periods_query->execute();
$periods_query->close();

// Retrieve match_id from schedules table
$match_id_query = $conn->prepare("SELECT match_id FROM schedules WHERE schedule_id = ?");
$match_id_query->bind_param("i", $schedule_id);
$match_id_query->execute();
$match_id_result = $match_id_query->get_result()->fetch_assoc();
$match_id_from_schedule = $match_id_result['match_id'];
$match_id_query->close();

// Retrieve match details from matches table
$match_query = $conn->prepare("
    SELECT teamA_id, teamB_id 
    FROM matches 
    WHERE match_id = ?
");
$match_query->bind_param("i", $match_id_from_schedule);
$match_query->execute();
$match_result = $match_query->get_result()->fetch_assoc();
$teamA_id_from_match = $match_result['teamA_id'];
$teamB_id_from_match = $match_result['teamB_id'];
$match_query->close();

// Debug logging
error_log("Schedule ID: " . $schedule_id);
error_log("Match ID from schedules: " . $match_id_from_schedule);
error_log("Team A ID from matches: " . $teamA_id_from_match);
error_log("Team B ID from matches: " . $teamB_id_from_match);
error_log("Set Winner ID: " . $set_winner_id);

// Debug: Fetch current record before update
$pre_update_query = $conn->prepare("
    SELECT 
        live_set_score_id, 
        schedule_id, 
        teamA_id, 
        teamB_id, 
        teamA_sets_won, 
        teamB_sets_won, 
        current_set 
    FROM live_set_scores 
    WHERE schedule_id = ?
");
$pre_update_query->bind_param("i", $schedule_id);
$pre_update_query->execute();
$pre_update_result = $pre_update_query->get_result()->fetch_assoc();
$pre_update_query->close();

// Extensive pre-update logging
error_log("PRE-UPDATE Record Details:");
error_log("Live Set Score ID: " . $pre_update_result['live_set_score_id']);
error_log("Schedule ID: " . $pre_update_result['schedule_id']);
error_log("Current Team A ID: " . $pre_update_result['teamA_id']);
error_log("Current Team B ID: " . $pre_update_result['teamB_id']);
error_log("Current Team A Sets Won: " . $pre_update_result['teamA_sets_won']);
error_log("Current Team B Sets Won: " . $pre_update_result['teamB_sets_won']);
error_log("Current Set: " . $pre_update_result['current_set']);

// Additional debug info
error_log("Incoming Set Winner ID: " . $set_winner_id);
error_log("Incoming Team A ID: " . $teamA_id_from_match);
error_log("Incoming Team B ID: " . $teamB_id_from_match);

// Prepare update query with explicit logging
$live_set_query = $conn->prepare("
    UPDATE live_set_scores 
    SET 
        teamA_score = 0, 
        teamB_score = 0, 
        current_set = ?,
        teamA_sets_won = CASE 
            WHEN ? = teamA_id THEN teamA_sets_won + 1 
            ELSE teamA_sets_won 
        END,
        teamB_sets_won = CASE 
            WHEN ? = teamB_id THEN teamB_sets_won + 1 
            ELSE teamB_sets_won 
        END,
        teamA_id = ?,
        teamB_id = ?,
        timestamp = NOW()
    WHERE schedule_id = ?
");
$live_set_query->bind_param("iiiiii", 
    $current_set, 
    $set_winner_id, $set_winner_id, 
    $teamA_id_from_match, 
    $teamB_id_from_match, 
    $schedule_id
);
$live_set_result = $live_set_query->execute();

// Fetch record after update for comparison
$post_update_query = $conn->prepare("
    SELECT 
        live_set_score_id, 
        schedule_id, 
        teamA_id, 
        teamB_id, 
        teamA_sets_won, 
        teamB_sets_won, 
        current_set 
    FROM live_set_scores 
    WHERE schedule_id = ?
");
$post_update_query->bind_param("i", $schedule_id);
$post_update_query->execute();
$post_update_result = $post_update_query->get_result()->fetch_assoc();
$post_update_query->close();

// Extensive post-update logging
error_log("POST-UPDATE Record Details:");
error_log("Live Set Score ID: " . $post_update_result['live_set_score_id']);
error_log("Schedule ID: " . $post_update_result['schedule_id']);
error_log("Updated Team A ID: " . $post_update_result['teamA_id']);
error_log("Updated Team B ID: " . $post_update_result['teamB_id']);
error_log("Updated Team A Sets Won: " . $post_update_result['teamA_sets_won']);
error_log("Updated Team B Sets Won: " . $post_update_result['teamB_sets_won']);
error_log("Updated Current Set: " . $post_update_result['current_set']);

// Log any potential errors
if (!$live_set_result) {
    error_log("Update Error: " . $live_set_query->error);
}
$affected_rows = $live_set_query->affected_rows;
error_log("Affected Rows: " . $affected_rows);
$live_set_query->close();

// Comprehensive column debugging
$columns_query = $conn->query("DESCRIBE live_set_scores");
if (!$columns_query) {
    $response['error'] = 'Could not query table columns: ' . $conn->error;
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

$columns = [];
$column_details = [];
while ($column = $columns_query->fetch_assoc()) {
    $columns[] = $column['Field'];
    $column_details[] = $column;
}

// Log full column details
error_log("Columns in live_set_scores: " . print_r($column_details, true));

// Check if specific columns exist
$required_columns = ['schedule_id', 'game_id', 'teamA_score', 'teamB_score', 'teamA_sets_won', 'teamB_sets_won', 'current_set', 'teamA_id', 'teamB_id', 'timestamp'];

$missing_columns = array_diff($required_columns, $columns);
if (!empty($missing_columns)) {
    $response['error'] = 'Missing columns: ' . implode(', ', $missing_columns);
    $response['existing_columns'] = $columns;
    $response['column_details'] = $column_details;
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}

// Prepare response with detailed logging
$response = [
    'success' => $live_set_result,
    'current_set' => $current_set,
    'set_winner' => $set_winner_id,
    'pre_update' => $pre_update_result,
    'post_update' => $post_update_result,
    'debug_logs' => [
        'schedule_id' => $schedule_id,
        'match_id_from_schedule' => $match_id_from_schedule,
        'team_a_id_from_match' => $teamA_id_from_match,
        'team_b_id_from_match' => $teamB_id_from_match,
        'set_winner_id' => $set_winner_id
    ]
];

// If update failed, add error information
if (!$live_set_result) {
    $response['error'] = $live_set_query->error;
}

header('Content-Type: application/json');
echo json_encode($response);

$conn->close();
?>