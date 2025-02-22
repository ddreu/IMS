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

    // Add comprehensive debugging
    error_log("DEBUG: Entering fetch_live_scores.php");
    error_log("DEBUG: Department ID: " . ($department_id ?? 'NULL'));
    error_log("DEBUG: Grade Level: " . ($grade_level ?? 'NULL'));

    // Prepare WHERE conditions
    $whereConditions = [];
    if (!empty($department_id)) {
        $whereConditions[] = "department_id = '" . mysqli_real_escape_string($conn, $department_id) . "'";
    }
    if (!empty($grade_level)) {
        $whereConditions[] = "grade_level = '" . mysqli_real_escape_string($conn, $grade_level) . "'";
    }
    $whereClause = !empty($whereConditions) ? " WHERE " . implode(" AND ", $whereConditions) : "";

    // Base query to fetch live scores along with set scores and default scores
    $query = "
        SELECT 
            source_table,
            schedule_id,
            teamA_id,
            teamB_id,
            teamA_score,
            teamB_score,
            teamA_sets_won,
            teamB_sets_won,
            current_set,
            timeout_teamA,
            timeout_teamB,
            timestamp,
            teamA_name, 
            teamB_name,
            game_name,
            department_id,
            grade_level,
            timeout_per_team,
            schedule_date,
            time_remaining
        FROM (
            (SELECT 
                'live_set_scores' as source_table,
                lss.schedule_id,
                lss.teamA_id,
                lss.teamB_id,
                lss.teamA_score,
                lss.teamB_score,
                lss.teamA_sets_won,
                lss.teamB_sets_won,
                lss.current_set,
                lss.timeout_teamA,
                lss.timeout_teamB,
                lss.timestamp,
                tA.team_name AS teamA_name, 
                tB.team_name AS teamB_name,
                g.game_name,
                b.department_id,
                b.grade_level,
                COALESCE(gsr.timeouts_per_period, 4) as timeout_per_team,
                s.schedule_date,
                NULL as time_remaining
            FROM live_set_scores lss
            JOIN schedules s ON lss.schedule_id = s.schedule_id
            JOIN matches m ON s.match_id = m.match_id
            JOIN brackets b ON m.bracket_id = b.bracket_id
            JOIN games g ON b.game_id = g.game_id
            JOIN teams tA ON lss.teamA_id = tA.team_id
            JOIN teams tB ON lss.teamB_id = tB.team_id
            LEFT JOIN game_scoring_rules gsr ON g.game_id = gsr.game_id)

            UNION ALL

            (SELECT 
                'live_scores' as source_table,
                ls.schedule_id,
                ls.teamA_id,
                ls.teamB_id,
                ls.teamA_score,
                ls.teamB_score,
                NULL as teamA_sets_won,
                NULL as teamB_sets_won,
                NULL as current_set,
                ls.timeout_teamA,
                ls.timeout_teamB,
                ls.last_timer_update as timestamp,
                tA.team_name AS teamA_name, 
                tB.team_name AS teamB_name,
                g.game_name,
                b.department_id,
                b.grade_level,
                COALESCE(gsr.timeouts_per_period, 4) as timeout_per_team,
                s.schedule_date,
                ls.time_remaining
            FROM live_scores ls
            JOIN schedules s ON ls.schedule_id = s.schedule_id
            JOIN matches m ON s.match_id = m.match_id
            JOIN brackets b ON m.bracket_id = b.bracket_id
            JOIN games g ON b.game_id = g.game_id
            JOIN teams tA ON ls.teamA_id = tA.team_id
            JOIN teams tB ON ls.teamB_id = tB.team_id
            LEFT JOIN game_scoring_rules gsr ON g.game_id = gsr.game_id)

            UNION ALL

            (SELECT 
                'live_default_scores' as source_table,
                lds.schedule_id,
                lds.teamA_id,
                lds.teamB_id,
                lds.teamA_score,
                lds.teamB_score,
                NULL as teamA_sets_won,
                NULL as teamB_sets_won,
                NULL as current_set,
                NULL as timeout_teamA,
                NULL as timeout_teamB,
                lds.timestamp,
                tA.team_name AS teamA_name, 
                tB.team_name AS teamB_name,
                g.game_name,
                b.department_id,
                b.grade_level,
                COALESCE(gsr.timeouts_per_period, 4) as timeout_per_team,
                s.schedule_date,
                NULL as time_remaining
            FROM live_default_scores lds
            JOIN schedules s ON lds.schedule_id = s.schedule_id
            JOIN matches m ON s.match_id = m.match_id
            JOIN brackets b ON m.bracket_id = b.bracket_id
            JOIN games g ON b.game_id = g.game_id
            JOIN teams tA ON lds.teamA_id = tA.team_id
            JOIN teams tB ON lds.teamB_id = tB.team_id
            LEFT JOIN game_scoring_rules gsr ON g.game_id = gsr.game_id)
        ) as combined_scores
        " . $whereClause . "
    ";

    // Add logging to help diagnose the issue
    error_log("DEBUG: Final Query: " . $query);

    $stmt = $conn->prepare($query);

    if (!$stmt) {
        error_log("DEBUG: Prepare Statement Failed: " . $conn->error);
        throw new Exception("Failed to prepare statement: " . $conn->error);
    }

    $stmt->execute();
    $result = $stmt->get_result();

    error_log("DEBUG: Number of Rows: " . $result->num_rows);

    $matches = [];
    while ($row = $result->fetch_assoc()) {
        // Log each row as it's fetched
        error_log("DEBUG: Fetched Row: " . print_r($row, true));
        $matches[] = $row;
    }

    // Log matches count before normalization
    error_log("DEBUG: Matches Count Before Normalization: " . count($matches));

    // If no matches found, log additional diagnostic information
    if (empty($matches)) {
        error_log("DEBUG: No matches found. Performing additional diagnostics:");
        
        // Check live_set_scores table
        $lssQuery = "SELECT * FROM live_set_scores LIMIT 5";
        $lssResult = $conn->query($lssQuery);
        error_log("DEBUG: Sample live_set_scores rows: " . print_r($lssResult->fetch_all(MYSQLI_ASSOC), true));
        
        // Check schedules table
        $schedQuery = "SELECT * FROM schedules LIMIT 5";
        $schedResult = $conn->query($schedQuery);
        error_log("DEBUG: Sample schedules rows: " . print_r($schedResult->fetch_all(MYSQLI_ASSOC), true));
    }

    // Function to normalize live score data
    function normalizeLiveScoreData($rawData) {
        $normalizedData = [];

        foreach ($rawData as $match) {
            // Base match information
            $normalizedMatch = [
                'schedule_id' => $match['schedule_id'],
                'game_name' => $match['game_name'],
                'source_table' => $match['source_table'],
                'schedule_date' => $match['schedule_date'] ?? null,
                'formatted_date' => isset($match['schedule_date']) 
                    ? date('l, F j, Y', strtotime($match['schedule_date'])) 
                    : null,
                
                // Team A Information
                'teamA' => [
                    'id' => $match['teamA_id'],
                    'name' => $match['teamA_name'],
                    'score' => $match['teamA_score'] ?? 0,
                    
                    // Table-specific additional information
                    'additional_info' => []
                ],
                
                // Team B Information
                'teamB' => [
                    'id' => $match['teamB_id'],
                    'name' => $match['teamB_name'],
                    'score' => $match['teamB_score'] ?? 0,
                    
                    // Table-specific additional information
                    'additional_info' => []
                ]
            ];

            // Populate additional information based on source table
            switch ($match['source_table']) {
                case 'live_scores':
                    // Add period, timer, fouls, and timeouts for live_scores
                    $normalizedMatch['teamA']['additional_info'] = [
                        'period' => $match['current_set'], // Reusing current_set as period
                        'timer' => $match['time_remaining'] ? 
                            sprintf("%02d:%02d", 
                                floor($match['time_remaining'] / 60), 
                                $match['time_remaining'] % 60
                            ) : null,
                        'fouls' => null, // Add foul tracking if available
                        'timeouts' => $match['timeout_teamA']
                    ];
                    
                    $normalizedMatch['teamB']['additional_info'] = [
                        'period' => $match['current_set'],
                        'timer' => $match['time_remaining'] ? 
                            sprintf("%02d:%02d", 
                                floor($match['time_remaining'] / 60), 
                                $match['time_remaining'] % 60
                            ) : null,
                        'fouls' => null,
                        'timeouts' => $match['timeout_teamB']
                    ];
                    break;

                case 'live_set_scores':
                    // Add set-specific information for live_set_scores
                    $normalizedMatch['teamA']['additional_info'] = [
                        'sets_won' => $match['teamA_sets_won'] ?? 0,
                        'current_set' => $match['current_set'] ?? 1,
                        'timeouts' => $match['timeout_teamA']
                    ];
                    
                    $normalizedMatch['teamB']['additional_info'] = [
                        'sets_won' => $match['teamB_sets_won'] ?? 0,
                        'current_set' => $match['current_set'] ?? 1,
                        'timeouts' => $match['timeout_teamB']
                    ];
                    break;

                case 'live_default_scores':
                    // Minimal information for live_default_scores
                    // No additional info needed
                    break;
            }

            $normalizedData[] = $normalizedMatch;
        }

        return $normalizedData;
    }

    // Normalize the matches before encoding
    $normalizedMatches = normalizeLiveScoreData($matches);

    // Ensure JSON encoding doesn't fail
    $jsonOutput = json_encode($normalizedMatches);
    if ($jsonOutput === false) {
        error_log("DEBUG: JSON Encoding Error: " . json_last_error_msg());
        echo json_encode(['error' => true, 'message' => 'Failed to encode matches']);
    } else {
        echo $jsonOutput;
    }

} catch (Exception $e) {
    // Log the full error details
    error_log("FULL ERROR DETAILS in fetch_live_scores.php:");
    error_log("Error Message: " . $e->getMessage());
    error_log("Error Trace: " . $e->getTraceAsString());

    // If a database error occurred, log the specific database error
    if ($stmt) {
        error_log("MySQL Error: " . $stmt->error);
    }

    // Return comprehensive error as JSON
    echo json_encode([
        'error' => true,
        'message' => 'An error occurred while fetching live scores',
        'debug' => [
            'error_message' => $e->getMessage(),
            'department_id' => $department_id,
            'grade_level' => $grade_level
        ]
    ]);
}
