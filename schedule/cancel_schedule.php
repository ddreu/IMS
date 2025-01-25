<?php
session_start();
header('Content-Type: application/json');

include_once '../connection/conn.php';
include_once '../user_logs/logger.php'; // Include the logging function
$conn = con();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit();
}

// Get the raw POST data
$postData = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($postData['schedule_id'])) {
    echo json_encode(['success' => false, 'message' => 'Schedule ID is required.']);
    exit();
}

$schedule_id = intval($postData['schedule_id']);

// Start transaction
$conn->begin_transaction();

try {
    // Get the match, team, game, and schedule details
    $stmt = $conn->prepare("
        SELECT 
            s.match_id, 
            s.schedule_date, 
            s.schedule_time, 
            s.venue, 
            m.teamA_id, 
            m.teamB_id, 
            tA.team_name AS teamA_name, 
            tB.team_name AS teamB_name, 
            g.game_name 
        FROM schedules s
        INNER JOIN matches m ON s.match_id = m.match_id
        INNER JOIN teams tA ON m.teamA_id = tA.team_id
        INNER JOIN teams tB ON m.teamB_id = tB.team_id
        INNER JOIN games g ON tA.game_id = g.game_id
        WHERE s.schedule_id = ?
    ");
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Schedule not found.');
    }

    $schedule = $result->fetch_assoc();
    $match_id = $schedule['match_id'];
    $teamA_name = $schedule['teamA_name'];
    $teamB_name = $schedule['teamB_name'];
    $game_name = $schedule['game_name'];
    $schedule_date = $schedule['schedule_date'];
    $schedule_time = $schedule['schedule_time'];
    $venue = $schedule['venue'];
    $stmt->close();

    // Delete the schedule
    $delete_stmt = $conn->prepare("DELETE FROM schedules WHERE schedule_id = ?");
    if (!$delete_stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    $delete_stmt->bind_param("i", $schedule_id);
    if (!$delete_stmt->execute()) {
        throw new Exception('Failed to delete schedule.');
    }
    $delete_stmt->close();

    // Update match status to Pending
    $update_stmt = $conn->prepare("UPDATE matches SET status = 'Pending' WHERE match_id = ?");
    if (!$update_stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    $update_stmt->bind_param("i", $match_id);
    if (!$update_stmt->execute()) {
        throw new Exception('Failed to update match status.');
    }
    $update_stmt->close();

    // Log the action
    $user_id = $_SESSION['user_id'];
    $description = sprintf(
        'Canceled schedule for Match #%d: %s vs %s (%s) scheduled on %s at %s, Venue: %s',
        $match_id,
        $teamA_name,
        $teamB_name,
        $game_name,
        $schedule_date,
        date("g:i A", strtotime($schedule_time)), // Convert to 12-hour format
        $venue
    );
    logUserAction($conn, $user_id, 'schedules', 'DELETE', $schedule_id, $description);

    // Commit transaction
    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Schedule canceled successfully.']);
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

$conn->close();
exit();
