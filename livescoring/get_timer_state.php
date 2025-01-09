<?php
include_once '../connection/conn.php';
$conn = con();

if (!isset($_GET['schedule_id'])) {
    echo json_encode(['error' => 'Schedule ID is required']);
    exit;
}

$schedule_id = $_GET['schedule_id'];

$query = $conn->prepare("
    SELECT time_remaining, timer_status, last_timer_update
    FROM live_scores
    WHERE schedule_id = ?
");

$query->bind_param("i", $schedule_id);
$query->execute();
$result = $query->get_result();

if ($row = $result->fetch_assoc()) {
    // Calculate current time remaining if timer is running
    if ($row['timer_status'] === 'running') {
        $seconds_since_update = strtotime('now') - strtotime($row['last_timer_update']);
        $row['time_remaining'] = max(0, $row['time_remaining'] - $seconds_since_update);
    }
    echo json_encode([
        'time_remaining' => (int)$row['time_remaining'],
        'timer_status' => $row['timer_status']
    ]);
} else {
    echo json_encode(['error' => 'No timer data found']);
}
