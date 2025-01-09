<?php
include_once '../connection/conn.php';
$conn = con();
session_start();

header('Content-Type: application/json');

if (!isset($_GET['schedule_id'])) {
    echo json_encode(['error' => 'Schedule ID is required']);
    exit;
}

$schedule_id = $_GET['schedule_id'];

$query = "SELECT 
    ls.*,
    TIMESTAMPDIFF(SECOND, last_timer_update, NOW()) as seconds_since_update
FROM live_scores ls
WHERE ls.schedule_id = ?";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $schedule_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['error' => 'No live score found']);
    exit;
}

$score_data = $result->fetch_assoc();

// Calculate current time remaining if timer is running
if ($score_data['period_status'] === 'running') {
    $score_data['period_time_remaining'] = max(0, $score_data['period_time_remaining'] - $score_data['seconds_since_update']);
}
if ($score_data['game_status'] === 'running') {
    $score_data['game_time_remaining'] = max(0, $score_data['game_time_remaining'] - $score_data['seconds_since_update']);
}

// Remove sensitive or unnecessary fields
unset($score_data['seconds_since_update']);

echo json_encode($score_data);
