<?php
session_start();
header('Content-Type: application/json');

include_once '../connection/conn.php';
include_once '../user_logs/logger.php';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn->autocommit(FALSE);
    $transaction_successful = true;

    try {
        $match_id = isset($_POST['match_id']) ? $_POST['match_id'] : null;
        $schedule_date = isset($_POST['schedule_date']) ? $_POST['schedule_date'] : null;
        $schedule_time = isset($_POST['schedule_time']) ? $_POST['schedule_time'] : null;
        $venue = isset($_POST['venue']) ? trim($_POST['venue']) : null;

        if (!$match_id || !$schedule_date || !$schedule_time || !$venue) {
            throw new Exception('All fields are required.');
        }

        // Validate and format the date and time
        $date_time = new DateTime($schedule_date . ' ' . $schedule_time, new DateTimeZone('Asia/Manila'));
        $formatted_schedule_date = $date_time->format('Y-m-d');
        $formatted_schedule_time = $date_time->format('H:i:s');

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
        ";

        $check_stmt = $conn->prepare($check_schedule_query);
        $check_stmt->bind_param("isss", $school_id, $formatted_schedule_date, $formatted_schedule_time, $venue);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        /////////////////////////
        $schedule_conflict = false;

        while ($row = $result->fetch_assoc()) {
            $conflict_match_id = $row['match_id'];

            // Check the match status
            $status_stmt = $conn->prepare("SELECT status FROM matches WHERE match_id = ?");
            $status_stmt->bind_param("i", $conflict_match_id);
            $status_stmt->execute();
            $status_result = $status_stmt->get_result();
            $status_row = $status_result->fetch_assoc();
            $status_stmt->close();

            if (isset($status_row['status']) && strtolower($status_row['status']) !== 'finished') {
                $schedule_conflict = true;
                break;
            }
        }

        if ($schedule_conflict) {
            throw new Exception('A match is already scheduled at this venue for the selected date and time.');
        }

        // if ($result->num_rows > 0) {
        //     // Schedule conflict exists
        //     throw new Exception('A match is already scheduled at this venue for the selected date and time.');
        // }

        // Check for existing match schedule
        $check_stmt = $conn->prepare("SELECT schedule_id FROM schedules WHERE match_id = ?");
        $check_stmt->bind_param("s", $match_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            throw new Exception('This match is already scheduled.');
        }
        $check_stmt->close();

        // Insert the schedule
        $insert_schedule = $conn->prepare("INSERT INTO schedules (match_id, schedule_date, schedule_time, venue) VALUES (?, ?, ?, ?)");
        $insert_schedule->bind_param("isss", $match_id, $formatted_schedule_date, $formatted_schedule_time, $venue);
        if (!$insert_schedule->execute()) {
            throw new Exception('Failed to insert schedule: ' . $insert_schedule->error);
        }
        $insert_schedule->close();

        // Update match status to 'Upcoming'
        $update_match = $conn->prepare("UPDATE matches SET status = 'Upcoming' WHERE match_id = ?");
        $update_match->bind_param("i", $match_id);
        if (!$update_match->execute()) {
            throw new Exception('Failed to update match status: ' . $update_match->error);
        }
        $update_match->close();

        // Fetch match details for logging
        $match_stmt = $conn->prepare("SELECT teamA_id, teamB_id FROM matches WHERE match_id = ?");
        $match_stmt->bind_param("i", $match_id);
        $match_stmt->execute();
        $match_result = $match_stmt->get_result();
        $match = $match_result->fetch_assoc();
        $match_stmt->close();

        $teamA_id = $match['teamA_id'];
        $teamB_id = $match['teamB_id'];

        // Fetch team names and game_id
        $team_stmt = $conn->prepare("SELECT team_id, team_name, game_id FROM teams WHERE team_id IN (?, ?)");
        $team_stmt->bind_param("ii", $teamA_id, $teamB_id);
        $team_stmt->execute();
        $team_result = $team_stmt->get_result();

        $teams = [];
        $game_id = null;
        while ($row = $team_result->fetch_assoc()) {
            $teams[$row['team_id']] = $row['team_name'];
            $game_id = $row['game_id'];
        }
        $team_stmt->close();

        $teamA_name = $teams[$teamA_id];
        $teamB_name = $teams[$teamB_id];

        // Fetch game name
        $game_stmt = $conn->prepare("SELECT game_name FROM games WHERE game_id = ?");
        $game_stmt->bind_param("i", $game_id);
        $game_stmt->execute();
        $game_result = $game_stmt->get_result();
        $game = $game_result->fetch_assoc();
        $game_stmt->close();

        $game_name = $game['game_name'];

        // Format date and time for logging in 12-hour format
        $log_date_time = new DateTime($formatted_schedule_date . ' ' . $formatted_schedule_time);
        $formatted_log_date = $log_date_time->format('F d, Y'); // Month DD, YYYY
        $formatted_log_time = $log_date_time->format('g:i A'); // 12-hour format with AM/PM

        // Log the action
        $description = "$teamA_name vs $teamB_name - $game_name | Scheduled on $formatted_log_date at $formatted_log_time, Venue: $venue";
        logUserAction($conn, $user_id, 'schedules', 'CREATE', $match_id, $description, null, json_encode([
            'match_id' => $match_id,
            'schedule_date' => $formatted_schedule_date,
            'schedule_time' => $formatted_schedule_time,
            'venue' => $venue
        ]));

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'Schedule created successfully!']);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    } finally {
        $conn->autocommit(TRUE);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
}
