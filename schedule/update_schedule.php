<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json');

include_once '../connection/conn.php';
include_once '../user_logs/logger.php'; // Include logger
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->autocommit(FALSE);

    try {
        // Validate and sanitize input
        $schedule_id = isset($_POST['schedule_id']) ? intval($_POST['schedule_id']) : null;
        $schedule_date = isset($_POST['schedule_date']) ? $_POST['schedule_date'] : null;
        $schedule_time = isset($_POST['schedule_time']) ? $_POST['schedule_time'] : null;
        $venue = isset($_POST['venue']) ? trim($_POST['venue']) : null;

        if (!$schedule_id || !$schedule_date || !$schedule_time || !$venue) {
            throw new Exception('All fields are required.');
        }

        // Get school_id from session
        $school_id = $_SESSION['school_id'];

        // Format venue (trim whitespace, capitalize words)
        $venue = ucwords(trim($_POST['venue']));

        // First, check for existing schedule with same date, time, and venue for the same school
        $check_schedule_query = "
            SELECT s.* 
            FROM schedules s
            JOIN matches m ON s.match_id = m.match_id
            JOIN brackets b ON m.bracket_id = b.bracket_id
            JOIN departments d ON b.department_id = d.id
            WHERE d.school_id = ?
            AND s.schedule_date = ?
            AND s.schedule_time = ?
            AND s.venue = ?
            AND s.schedule_id != ?
        ";

        $check_stmt = $conn->prepare($check_schedule_query);
        $check_stmt->bind_param("isssi", $school_id, $_POST['schedule_date'], $_POST['schedule_time'], $venue, $schedule_id);
        $check_stmt->execute();
        $result = $check_stmt->get_result();

        if ($result->num_rows > 0) {
            // Schedule conflict exists
            echo json_encode(['success' => false, 'message' => 'A match is already scheduled at this venue for the selected date and time.']);
            exit();
        }

        // Fetch the current schedule details
        $select_stmt = $conn->prepare("SELECT s.match_id, s.schedule_date, s.schedule_time, s.venue, 
                                              m.teamA_id, m.teamB_id, t1.team_name AS teamA_name, t2.team_name AS teamB_name, 
                                              g.game_name
                                       FROM schedules s
                                       JOIN matches m ON s.match_id = m.match_id
                                       JOIN teams t1 ON m.teamA_id = t1.team_id
                                       JOIN teams t2 ON m.teamB_id = t2.team_id
                                       JOIN games g ON t1.game_id = g.game_id
                                       WHERE s.schedule_id = ?");
        $select_stmt->bind_param("i", $schedule_id);
        $select_stmt->execute();
        $result = $select_stmt->get_result();

        if ($result->num_rows === 0) {
            throw new Exception('Schedule not found.');
        }

        $old_schedule = $result->fetch_assoc();
        $select_stmt->close();

        // Backup old schedule details
        $old_date = $old_schedule['schedule_date'];
        $old_time = $old_schedule['schedule_time'];
        $old_venue = $old_schedule['venue'];
        $teamA_name = $old_schedule['teamA_name'];
        $teamB_name = $old_schedule['teamB_name'];
        $game_name = $old_schedule['game_name'];

        // Update the schedule
        $update_stmt = $conn->prepare("UPDATE schedules SET 
                                       schedule_date = ?, 
                                       schedule_time = ?, 
                                       venue = ? 
                                       WHERE schedule_id = ?");
        $update_stmt->bind_param("sssi", $schedule_date, $schedule_time, $venue, $schedule_id);

        if (!$update_stmt->execute()) {
            throw new Exception('Error updating schedule: ' . $update_stmt->error);
        }

        if ($update_stmt->affected_rows > 0) {
            // Format date and time for logging in 12-hour format
            $log_date_time = new DateTime($_POST['schedule_date'] . ' ' . $_POST['schedule_time']);
            $formatted_log_date = $log_date_time->format('F d, Y'); // Month DD, YYYY
            $formatted_log_time = $log_date_time->format('g:i A'); // 12-hour format with AM/PM

            $description = "Modified the schedule for $teamA_name vs $teamB_name - $game_name 
                            from $old_date at " . date('g:i A', strtotime($old_time)) . ", Venue: $old_venue 
                            to $formatted_log_date at $formatted_log_time, Venue: $venue";
            logUserAction($conn, $_SESSION['user_id'], 'schedules', 'UPDATE', $schedule_id, $description, json_encode([
                'old_date' => $old_date,
                'old_time' => date('g:i A', strtotime($old_time)),
                'old_venue' => $old_venue
            ]), json_encode([
                'new_date' => $formatted_log_date,
                'new_time' => $formatted_log_time,
                'new_venue' => $venue
            ]));

            $conn->commit();
            echo json_encode(['success' => true, 'message' => 'Schedule updated successfully.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'No changes were made to the schedule.']);
        }

        $update_stmt->close();
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } finally {
        $conn->autocommit(TRUE);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}

$conn->close();
