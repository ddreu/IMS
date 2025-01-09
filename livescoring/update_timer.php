<?php
include_once '../connection/conn.php';
$conn = con();
session_start();

header('Content-Type: application/json');

// Read JSON data from the request
$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid JSON data']);
    exit;
}

// Required fields
$schedule_id = $data['schedule_id'] ?? null;
$action = $data['action'] ?? null; // start, pause, resume, end
$timer_type = $data['timer_type'] ?? null; // period, game

if (!$schedule_id || !$action || !$timer_type) {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Get current timer state
    $query = "SELECT period_time_remaining, game_time_remaining, period_status, game_status, last_timer_update 
              FROM live_scores WHERE schedule_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $current_state = $result->fetch_assoc();

    if (!$current_state) {
        throw new Exception('No live score record found');
    }

    $now = time();
    $last_update = strtotime($current_state['last_timer_update']);
    $elapsed_time = $now - $last_update;

    // Update time remaining if timer was running
    if ($current_state[$timer_type . '_status'] === 'running') {
        $time_remaining = $current_state[$timer_type . '_time_remaining'] - $elapsed_time;
        $time_remaining = max(0, $time_remaining); // Don't go below 0
    } else {
        $time_remaining = $current_state[$timer_type . '_time_remaining'];
    }

    // Handle different actions
    switch ($action) {
        case 'start':
        case 'resume':
            $new_status = 'running';
            break;
        case 'pause':
            $new_status = 'paused';
            break;
        case 'end':
            $new_status = 'ended';
            $time_remaining = 0;
            break;
        default:
            throw new Exception('Invalid action');
    }

    // Update the timer state
    $update_query = "UPDATE live_scores SET 
                    {$timer_type}_time_remaining = ?,
                    {$timer_type}_status = ?,
                    last_timer_update = CURRENT_TIMESTAMP
                    WHERE schedule_id = ?";
    
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param('isi', $time_remaining, $new_status, $schedule_id);
    $stmt->execute();

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'status' => 'success',
        'time_remaining' => $time_remaining,
        'timer_status' => $new_status
    ]);

} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
