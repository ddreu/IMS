<?php
session_start();
header('Content-Type: application/json');

include_once '../connection/conn.php';
$conn = con();
date_default_timezone_set('Asia/Manila');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$user_school_id = $_SESSION['school_id'];

// Check if the form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Start transaction
    $conn->autocommit(FALSE);
    $transaction_successful = true;

    try {
        // Debug POST data
        error_log("POST data received: " . print_r($_POST, true));
        
        // Get and validate input
        $match_id = isset($_POST['match_id']) ? $_POST['match_id'] : null;
        $schedule_date = isset($_POST['schedule_date']) ? $_POST['schedule_date'] : null;
        $schedule_time = isset($_POST['schedule_time']) ? $_POST['schedule_time'] : null;
        $venue = isset($_POST['venue']) ? trim($_POST['venue']) : null;

        // Debug extracted values
        error_log("Extracted values - match_id: $match_id, date: $schedule_date, time: $schedule_time, venue: $venue");

        // Basic validation
        if (!$match_id || !$schedule_date || !$schedule_time || !$venue) {
            throw new Exception('All fields are required.');
        }

        try {
            // Create DateTime object from the input (which is in Y-m-d and H:i formats)
            $date_time = new DateTime($schedule_date . ' ' . $schedule_time, new DateTimeZone('Asia/Manila'));
            $now = new DateTime('now', new DateTimeZone('Asia/Manila'));
            
            // Check if date is in the past
            $today = clone $now;
            $today->setTime(0, 0, 0);
            if ($date_time < $today) {
                throw new Exception('Please select a future date.');
            }

            // Check if time is between 7 AM and 7 PM
            $hour = (int)$date_time->format('H');
            if ($hour < 7 || $hour >= 19) {
                throw new Exception('Please select a time between 7:00 AM and 7:00 PM.');
            }
            
            // Format for database
            $formatted_schedule_date = $date_time->format('Y-m-d');
            $formatted_schedule_time = $date_time->format('H:i:s');
        } catch (Exception $e) {
            throw new Exception('Invalid date or time format. Date: ' . $schedule_date . ', Time: ' . $schedule_time);
        }

        // Check if match is already scheduled
        $check_stmt = $conn->prepare("SELECT schedule_id FROM schedules WHERE match_id = ?");
        if (!$check_stmt) {
            throw new Exception('Database error: ' . $conn->error);
        }
        
        $check_stmt->bind_param("s", $match_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            throw new Exception('This match is already scheduled.');
        }
        $check_stmt->close();

        // Check for schedule conflicts (same date, time and venue)
        $conflict_check = $conn->prepare("SELECT schedule_id 
            FROM schedules 
            WHERE schedule_date = ? 
            AND schedule_time = ? 
            AND venue = ?");
        if (!$conflict_check) {
            throw new Exception('Failed to prepare conflict check statement: ' . $conn->error);
        }

        $conflict_check->bind_param("sss", $formatted_schedule_date, $formatted_schedule_time, $venue);
        $conflict_check->execute();
        $conflict_result = $conflict_check->get_result();
        
        if ($conflict_result->num_rows > 0) {
            throw new Exception('There is already a match scheduled at this date, time, and venue. Please choose a different schedule.');
        }
        $conflict_check->close();

        // Insert the schedule
        $insert_schedule = $conn->prepare("INSERT INTO schedules (match_id, schedule_date, schedule_time, venue) VALUES (?, ?, ?, ?)");
        if (!$insert_schedule) {
            throw new Exception('Failed to prepare schedule insert statement: ' . $conn->error);
        }

        $insert_schedule->bind_param("isss", $match_id, $formatted_schedule_date, $formatted_schedule_time, $venue);
        if (!$insert_schedule->execute()) {
            throw new Exception('Failed to insert schedule: ' . $insert_schedule->error);
        }

        // Update match status to 'Upcoming'
        $update_match = $conn->prepare("UPDATE matches SET status = 'Upcoming' WHERE match_id = ?");
        if (!$update_match) {
            throw new Exception('Failed to prepare match update statement: ' . $conn->error);
        }

        $update_match->bind_param("i", $match_id);
        if (!$update_match->execute()) {
            throw new Exception('Failed to update match status: ' . $update_match->error);
        }

        $insert_schedule->close();
        $update_match->close();

        if ($transaction_successful) {
            $conn->commit();
            echo json_encode([
                'success' => true,
                'message' => 'Schedule created successfully!'
            ]);
        } else {
            $conn->rollback();
            echo json_encode([
                'success' => false,
                'message' => 'Failed to create schedule. Please try again.'
            ]);
        }
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    } finally {
        // Restore autocommit mode
        $conn->autocommit(TRUE);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>
