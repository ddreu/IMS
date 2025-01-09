<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

include_once '../connection/conn.php';
$conn = con();

// Check database connection
if (!$conn) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

// Set timezone
date_default_timezone_set('Asia/Manila');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit();
}

// Check if the form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate and sanitize input
    $schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : null;
    $schedule_date = isset($_POST['schedule_date']) ? $_POST['schedule_date'] : null;
    $schedule_time = isset($_POST['schedule_time']) ? $_POST['schedule_time'] : null;
    $venue = isset($_POST['venue']) ? trim($_POST['venue']) : null;

    // Basic validation
    if (!$schedule_id || !$schedule_date || !$schedule_time || !$venue) {
        echo json_encode(['success' => false, 'message' => 'All fields are required.']);
        exit();
    }

    // Update the schedule
    $query = "UPDATE schedules SET 
              schedule_date = ?, 
              schedule_time = ?, 
              venue = ? 
              WHERE schedule_id = ?";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Failed to prepare statement.']);
        exit();
    }

    $stmt->bind_param("sssi", $schedule_date, $schedule_time, $venue, $schedule_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Schedule updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes were made to the schedule.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Error updating schedule: ' . $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();
