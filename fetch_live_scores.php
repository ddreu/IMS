<?php
// Prevent any output before JSON
error_reporting(E_ALL);
ini_set('display_errors', 0);

header('Content-Type: application/json');

try {
    include_once 'connection/conn.php';
    $conn = con();

    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Check if specific schedule_id is requested
    if (isset($_GET['schedule_id'])) {
        $schedule_id = $_GET['schedule_id'];
        $stmt = $conn->prepare("
            SELECT 
                ls.*,
                s.schedule_date, 
                tA.team_name AS teamA_name, tB.team_name AS teamB_name, 
                g.game_name,
                gsr.duration_per_period,
                gsr.number_of_periods,
                CASE WHEN gsr.timeouts_per_period > 0 THEN TRUE ELSE FALSE END as has_timeouts,
                CASE WHEN gsr.max_fouls > 0 THEN TRUE ELSE FALSE END as has_fouls,
                gsr.timeouts_per_period as timeout_per_team,
                gsr.max_fouls as max_fouls_per_team,
                TIMESTAMPDIFF(SECOND, ls.last_timer_update, NOW()) as seconds_since_update
            FROM live_scores ls
            JOIN schedules s ON ls.schedule_id = s.schedule_id
            JOIN matches m ON s.match_id = m.match_id
            JOIN brackets b ON m.bracket_id = b.bracket_id
            JOIN games g ON b.game_id = g.game_id
            JOIN teams tA ON ls.teamA_id = tA.team_id
            JOIN teams tB ON ls.teamB_id = tB.team_id
            LEFT JOIN game_scoring_rules gsr ON g.game_id = gsr.game_id
            WHERE ls.schedule_id = ?
        ");
        
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }
        
        $stmt->bind_param('i', $schedule_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            // Calculate current time remaining if timer is running
            if ($row['timer_status'] === 'running') {
                $row['time_remaining'] = max(0, $row['time_remaining'] - $row['seconds_since_update']);
            }
            // Format time for display
            if ($row['time_remaining'] !== null) {
                $minutes = floor($row['time_remaining'] / 60);
                $seconds = $row['time_remaining'] % 60;
                $row['time_formatted'] = sprintf("%02d:%02d", $minutes, $seconds);
            }
            // Remove sensitive fields
            unset($row['seconds_since_update']);
            echo json_encode($row);
        } else {
            echo json_encode(['error' => 'No live score found']);
        }
    } else {
        // Fetch ongoing match results from live_scores table
        $stmtRecentMatches = $conn->prepare("
            SELECT 
                ls.live_score_id, ls.schedule_id, ls.game_id, 
                ls.teamA_id, ls.teamA_score, ls.timeout_teamA, ls.foul_teamA, 
                ls.teamB_id, ls.teamB_score, ls.timeout_teamB, ls.foul_teamB, 
                ls.period, ls.timestamp,
                ls.time_remaining, ls.timer_status,
                s.schedule_date, 
                tA.team_name AS teamA_name, tB.team_name AS teamB_name, 
                g.game_name,
                gsr.duration_per_period,
                CASE WHEN gsr.timeouts_per_period > 0 THEN TRUE ELSE FALSE END as has_timeouts,
                CASE WHEN gsr.max_fouls > 0 THEN TRUE ELSE FALSE END as has_fouls,
                gsr.timeouts_per_period as timeout_per_team,
                gsr.max_fouls as max_fouls_per_team,
                TIMESTAMPDIFF(SECOND, ls.last_timer_update, NOW()) as seconds_since_update
            FROM live_scores ls
            JOIN schedules s ON ls.schedule_id = s.schedule_id
            JOIN matches m ON s.match_id = m.match_id
            JOIN brackets b ON m.bracket_id = b.bracket_id
            JOIN games g ON b.game_id = g.game_id
            JOIN teams tA ON ls.teamA_id = tA.team_id
            JOIN teams tB ON ls.teamB_id = tB.team_id
            LEFT JOIN game_scoring_rules gsr ON g.game_id = gsr.game_id
            ORDER BY s.schedule_date DESC
            LIMIT 3
        ");

        if (!$stmtRecentMatches) {
            throw new Exception("Failed to prepare statement: " . $conn->error);
        }

        $stmtRecentMatches->execute();
        $resultRecentMatches = $stmtRecentMatches->get_result();

        $matches = [];
        while ($row = $resultRecentMatches->fetch_assoc()) {
            // Calculate current time remaining if timer is running
            if ($row['timer_status'] === 'running') {
                $row['time_remaining'] = max(0, $row['time_remaining'] - $row['seconds_since_update']);
            }
            // Format time for display
            if ($row['time_remaining'] !== null) {
                $minutes = floor($row['time_remaining'] / 60);
                $seconds = $row['time_remaining'] % 60;
                $row['time_formatted'] = sprintf("%02d:%02d", $minutes, $seconds);
            }
            // Remove sensitive fields
            unset($row['seconds_since_update']);
            $matches[] = $row;
        }

        echo json_encode($matches);
    }
} catch (Exception $e) {
    // Log the error to a file
    error_log("Error in fetch_live_scores.php: " . $e->getMessage());
    // Return error as JSON
    echo json_encode([
        'error' => true,
        'message' => 'An error occurred while fetching live scores',
        'debug' => $e->getMessage()
    ]);
}
