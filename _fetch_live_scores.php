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

    // Get department_id and grade_level from URL parameters
    $department_id = isset($_GET['department_id']) ? $_GET['department_id'] : null;
    $grade_level = isset($_GET['grade_level']) ? $_GET['grade_level'] : null;

    // Base query to fetch live scores
    $query = "
        SELECT 
            ls.*,
            s.schedule_id,
            s.schedule_date,
            s.venue,
            m.match_id,
            m.teamA_id,
            m.teamB_id,
            tA.team_name AS teamA_name, 
            tB.team_name AS teamB_name,
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
        WHERE 1=1
    ";

    // Add filters for department_id and grade_level if provided
    if ($department_id) {
        $query .= " AND b.department_id = ?";
    }
    if ($grade_level) {
        $query .= " AND b.grade_level = ?";
    }

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    // Bind parameters
    $params = [];
    $types = ''; // Initialize types string
    if ($department_id) {
        $params[] = $department_id;
        $types .= 'i'; // Assuming department_id is an integer
    }
    if ($grade_level) {
        $params[] = $grade_level;
        $types .= 's'; // grade_level is a string
    }

    // Bind the parameters to the query
    if ($params) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $matches = [];
    while ($row = $result->fetch_assoc()) {
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
