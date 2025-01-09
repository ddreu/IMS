<?php
session_start();
header('Content-Type: application/json');

include_once '../connection/conn.php';
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
    // Get the match_id from the schedule before deleting it
    $stmt = $conn->prepare("SELECT match_id FROM schedules WHERE schedule_id = ?");
    if (!$stmt) {
        throw new Exception('Database error: ' . $conn->error);
    }
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Schedule not found.');
    }

    $match_id = $result->fetch_assoc()['match_id'];
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
