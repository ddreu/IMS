<?php
include_once '../connection/conn.php';
include_once '../user_logs/logger.php';
session_start();
header('Content-Type: application/json');

try {
    $conn = con();

    // 1. Retrieve JSON data from POST request
    $data = json_decode(file_get_contents('php://input'), true);

    // 2. Check if schedule_id is set in the received data
    if (!isset($data['schedule_id'])) {
        throw new Exception("Schedule ID is not set.");
    }

    // Retrieve the schedule_id from the decoded data
    $schedule_id = $data['schedule_id'];

    // Get match_id and other data from schedules and live_default_scores
    $retrieve_score_query = "
        SELECT 
            lds.schedule_id,
            lds.game_id,
            lds.teamA_id,
            lds.teamB_id,
            lds.teamA_score,
            lds.teamB_score,
            s.match_id,
            m.bracket_id
        FROM live_default_scores lds
        JOIN schedules s ON lds.schedule_id = s.schedule_id
        JOIN matches m ON s.match_id = m.match_id
        WHERE lds.schedule_id = ?";

    $stmt = $conn->prepare($retrieve_score_query);
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        // Get the game scoring rules
        /*$rules_query = "SELECT number_of_periods, period_type FROM game_scoring_rules WHERE game_id = ?";
        $rules_stmt = $conn->prepare($rules_query);
        $rules_stmt->bind_param("i", $row['game_id']);
        $rules_stmt->execute();
        $rules_result = $rules_stmt->get_result();
        $rules = $rules_result->fetch_assoc();
        $rules_stmt->close();

        // Check if scores are tied at the end of regulation time
        if ($row['period'] == $rules['number_of_periods'] && $row['teamA_score'] == $row['teamB_score']) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Match is tied at the end of regulation time. Please proceed to overtime.',
                'overtime_required' => true
            ]);
            exit();
        }

        // Check if current period matches max periods or if it's overtime
        if ($row['period'] < $rules['number_of_periods'] && !str_starts_with($row['period'], 'OT')) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Cannot end match - Game is still in progress. Current period: ' . $row['period'] . ' of ' . $rules['number_of_periods']
            ]);
            exit();
        }

        // If in overtime and still tied, require another overtime period
        if (str_starts_with($row['period'], 'OT') && $row['teamA_score'] == $row['teamB_score']) {
            $current_ot = intval(substr($row['period'], 2)) ?: 1;
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Match is still tied after OT' . ($current_ot > 1 ? $current_ot : '') . '. Please proceed to ' . ($current_ot > 1 ? 'OT' . ($current_ot + 1) : 'next overtime period') . '.',
                'overtime_required' => true,
                'next_overtime' => $current_ot + 1
            ]);
            exit();
        }*/

        // Determine winning and losing teams
        $winning_team_id = ($row['teamA_score'] > $row['teamB_score']) ? $row['teamA_id'] : $row['teamB_id'];
        $losing_team_id = ($row['teamA_score'] < $row['teamB_score']) ? $row['teamA_id'] : $row['teamB_id'];

        // Debug logging for winning and losing teams
        error_log("Winning Team ID: " . $winning_team_id);
        error_log("Losing Team ID: " . $losing_team_id);

        // Start transaction
        $conn->begin_transaction();

        try {
            error_log("Starting match end process for schedule_id: " . $schedule_id);

            // Insert match result into match_results table
            $insert_match_result_query = "
                INSERT INTO match_results (
                    match_id, 
                    game_id, 
                    team_A_id, 
                    team_B_id, 
                    score_teamA, 
                    score_teamB, 
                    winning_team_id, 
                    losing_team_id
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            error_log("Inserting match result - Data: " . json_encode([
                'match_id' => $row['match_id'],
                'game_id' => $row['game_id'],
                'teamA_id' => $row['teamA_id'],
                'teamB_id' => $row['teamB_id'],
                'teamA_score' => $row['teamA_score'],
                'teamB_score' => $row['teamB_score'],
                'winning_team_id' => $winning_team_id,
                'losing_team_id' => $losing_team_id
            ]));

            $stmt = $conn->prepare($insert_match_result_query);
            $stmt->bind_param(
                "iiiiiiii",
                $row['match_id'],
                $row['game_id'],
                $row['teamA_id'],
                $row['teamB_id'],
                $row['teamA_score'],
                $row['teamB_score'],
                $winning_team_id,
                $losing_team_id
            );

            if (!$stmt->execute()) {
                throw new Exception("Error inserting match result: " . $stmt->error);
            }

            // Update match status to finished
            $update_match_status = "UPDATE matches SET status = 'Finished' WHERE match_id = ?";
            $stmt = $conn->prepare($update_match_status);
            $stmt->bind_param("i", $row['match_id']);
            if (!$stmt->execute()) {
                throw new Exception("Error updating match status: " . $stmt->error);
            }

            // Update team stats
            // Update wins for the winning team
            $update_team_wins_query = "UPDATE teams SET wins = wins + 1 WHERE team_id = ?";
            $stmt = $conn->prepare($update_team_wins_query);
            $stmt->bind_param("i", $winning_team_id);
            if (!$stmt->execute()) {
                throw new Exception("Error updating winning team stats: " . $stmt->error);
            }

            // Update losses for the losing team
            $update_team_losses_query = "UPDATE teams SET losses = losses + 1 WHERE team_id = ?";
            $stmt = $conn->prepare($update_team_losses_query);
            $stmt->bind_param("i", $losing_team_id);
            if (!$stmt->execute()) {
                throw new Exception("Error updating losing team stats: " . $stmt->error);
            }

            // Logging function to track match updates
            function logMatchUpdate($conn, $type, $match_id, $team_id, $additional_info = '')
            {
                $log_query = "
                    INSERT INTO match_progression_logs (
                        match_id, 
                        team_id, 
                        update_type, 
                        additional_info, 
                        log_timestamp
                    ) VALUES (?, ?, ?, ?, NOW())";

                $stmt = $conn->prepare($log_query);
                $stmt->bind_param("iiss", $match_id, $team_id, $type, $additional_info);
                $stmt->execute();
            }

            // Retrieve match result details
            $match_result_query = "
                SELECT 
                    mr.match_id,
                    mr.winning_team_id,
                    mr.losing_team_id,
                    m.match_type,
                    m.bracket_id,
                    m.next_match_number,
                    m.match_number
                FROM match_results mr
                JOIN matches m ON mr.match_id = m.match_id
                WHERE mr.match_id = ?";

            $stmt = $conn->prepare($match_result_query);
            $stmt->bind_param("i", $row['match_id']);
            $stmt->execute();
            $match_result = $stmt->get_result()->fetch_assoc();

            // Debug logging for match result
            error_log("Match Result Details: " . json_encode($match_result));

            // Check if this is a semifinal match
            if ($match_result['match_type'] == 'semifinal') {
                error_log("Processing semifinal match: " . $match_result['match_id']);

                // Get total number of matches in the bracket
                $get_total_matches = "
        SELECT COUNT(*) as total_matches 
        FROM matches 
        WHERE bracket_id = ?";
                $stmt = $conn->prepare($get_total_matches);
                $stmt->bind_param("i", $match_result['bracket_id']);
                $stmt->execute();
                $total_matches = $stmt->get_result()->fetch_assoc()['total_matches'];
                $stmt->close();

                // Identify the final match (highest match_number in bracket)
                $get_final_match = "
                   SELECT match_id, teamA_id, teamB_id 
                   FROM matches 
                   WHERE bracket_id = ? AND match_type = 'final'";
                $stmt = $conn->prepare($get_final_match);
                $stmt->bind_param("i", $match_result['bracket_id']);
                $stmt->execute();
                $final_match = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                // Identify Semifinals (Last 2 matches before the final)
                $get_semifinals = "
                   SELECT match_id, match_number 
                   FROM matches 
                   WHERE bracket_id = ? AND match_type = 'semifinal'";
                $stmt = $conn->prepare($get_semifinals);
                $stmt->bind_param("i", $match_result['bracket_id']);
                $stmt->execute();
                $semifinals = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $stmt->close();

                // Identify Third Place Match (if it exists)
                $get_third_place = "
                   SELECT match_id, teamA_id, teamB_id 
                   FROM matches 
                   WHERE bracket_id = ? AND match_type = 'third_place'";
                $stmt = $conn->prepare($get_third_place);
                $stmt->bind_param("i", $match_result['bracket_id']);
                $stmt->execute();
                $third_place_match = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                // ✅ **Update Final Match with Semifinal Winners**
                if ($final_match && in_array($match_result['match_id'], array_column($semifinals, 'match_id'))) {
                    if ($final_match['teamA_id'] == -2) {
                        $update_final_match = "UPDATE matches SET teamA_id = ? WHERE match_id = ?";
                        $stmt = $conn->prepare($update_final_match);
                        $stmt->bind_param("ii", $winning_team_id, $final_match['match_id']);
                        $stmt->execute();
                        error_log("Updated Final Match: TeamA with Winner $winning_team_id");
                    } elseif ($final_match['teamB_id'] == -2) {
                        $update_final_match = "UPDATE matches SET teamB_id = ? WHERE match_id = ?";
                        $stmt = $conn->prepare($update_final_match);
                        $stmt->bind_param("ii", $winning_team_id, $final_match['match_id']);
                        $stmt->execute();
                        error_log("Updated Final Match: TeamB with Winner $winning_team_id");
                    }
                }

                // ✅ **Update Third Place Match with Losing Teams**
                if ($third_place_match && in_array($match_result['match_id'], array_column($semifinals, 'match_id'))) {
                    // if ($third_place_match && in_array((int)$match_result['match_id'], array_map('intval', array_column($semifinals, 'match_id')))) {

                    if ($third_place_match['teamA_id'] == -2) {
                        $update_third_place = "UPDATE matches SET teamA_id = ? WHERE match_id = ?";
                        $stmt = $conn->prepare($update_third_place);
                        $stmt->bind_param("ii", $losing_team_id, $third_place_match['match_id']);
                        $stmt->execute();
                        error_log("Updated Third Place Match: TeamA with Loser $losing_team_id");
                    } elseif ($third_place_match['teamB_id'] == -2) {
                        $update_third_place = "UPDATE matches SET teamB_id = ? WHERE match_id = ?";
                        $stmt = $conn->prepare($update_third_place);
                        $stmt->bind_param("ii", $losing_team_id, $third_place_match['match_id']);
                        $stmt->execute();
                        error_log("Updated Third Place Match: TeamB with Loser $losing_team_id");
                    }
                }
            }

            // ✅ **Handle Other Matches & Move Winners to Next Rounds**
            else if ($match_result['next_match_number'] > 0) {
                $get_next_match = "
        SELECT match_id, teamA_id, teamB_id 
        FROM matches 
        WHERE bracket_id = ? 
        AND match_number = ?";
                $stmt = $conn->prepare($get_next_match);
                $stmt->bind_param("ii", $match_result['bracket_id'], $match_result['next_match_number']);
                $stmt->execute();
                $next_match = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($next_match) {
                    if ($match_result['match_number'] % 2 == 1 && $next_match['teamA_id'] == -2) {
                        // Odd match number updates teamA if it's TBD (-2)
                        $update_next_match = "UPDATE matches SET teamA_id = ? WHERE match_id = ?";
                        $stmt = $conn->prepare($update_next_match);
                        $stmt->bind_param("ii", $winning_team_id, $next_match['match_id']);
                        $stmt->execute();
                        error_log("Updated Next Match TeamA with Team $winning_team_id");
                    } elseif ($match_result['match_number'] % 2 == 0 && $next_match['teamB_id'] == -2) {
                        // Even match number updates teamB if it's TBD (-2)
                        $update_next_match = "UPDATE matches SET teamB_id = ? WHERE match_id = ?";
                        $stmt = $conn->prepare($update_next_match);
                        $stmt->bind_param("ii", $winning_team_id, $next_match['match_id']);
                        $stmt->execute();
                        error_log("Updated Next Match TeamB with Team $winning_team_id");
                    }
                }
            }


            // Check if all matches in the bracket are finished
            $check_bracket_matches = "
                SELECT COUNT(*) as total_matches, 
                       SUM(CASE WHEN status = 'Finished' THEN 1 ELSE 0 END) as finished_matches
                FROM matches 
                WHERE bracket_id = ?";
            $stmt = $conn->prepare($check_bracket_matches);
            $stmt->bind_param("i", $row['bracket_id']);
            $stmt->execute();
            $bracket_status = $stmt->get_result()->fetch_assoc();

            if ($bracket_status['total_matches'] == $bracket_status['finished_matches']) {
                // Update bracket status to Completed
                $update_bracket = "UPDATE brackets SET status = 'Completed' WHERE bracket_id = ?";
                $stmt = $conn->prepare($update_bracket);
                $stmt->bind_param("i", $row['bracket_id']);
                $stmt->execute();

                // Get final match result
                $final_match_query = "
                    SELECT mr.*, m.match_type, t1.grade_section_course_id as winner_gsc_id, t2.grade_section_course_id as loser_gsc_id
                    FROM matches m
                    JOIN match_results mr ON m.match_id = mr.match_id
                    JOIN teams t1 ON mr.winning_team_id = t1.team_id
                    JOIN teams t2 ON mr.losing_team_id = t2.team_id
                    WHERE m.bracket_id = ? AND m.match_type = 'final'";
                $stmt = $conn->prepare($final_match_query);
                $stmt->bind_param("i", $row['bracket_id']);
                $stmt->execute();
                $final_match = $stmt->get_result()->fetch_assoc();

                // Get third place match result
                $third_place_query = "
                    SELECT mr.*, m.match_type, t1.grade_section_course_id as winner_gsc_id
                    FROM matches m
                    JOIN match_results mr ON m.match_id = mr.match_id
                    JOIN teams t1 ON mr.winning_team_id = t1.team_id
                    WHERE m.bracket_id = ? AND m.match_type = 'third_place'";
                $stmt = $conn->prepare($third_place_query);
                $stmt->bind_param("i", $row['bracket_id']);
                $stmt->execute();
                $third_place_match = $stmt->get_result()->fetch_assoc();

                // Get pointing system
                if (!isset($_SESSION['school_id'])) {
                    error_log("Error: school_id not found in session");
                    die(json_encode(['error' => 'School ID not found']));
                }

                $pointing_query = "
                    SELECT ps.first_place_points, ps.second_place_points, ps.third_place_points
                    FROM pointing_system ps
                    WHERE ps.school_id = ? AND ps.is_archived = 0";
                $stmt = $conn->prepare($pointing_query);
                $stmt->bind_param("i", $_SESSION['school_id']);
                $stmt->execute();
                $pointing_system = $stmt->get_result()->fetch_assoc();

                if (!$pointing_system) {
                    error_log("Error: No pointing system found for school_id: " . $_SESSION['school_id']);
                    die(json_encode(['error' => 'Pointing system not found']));
                }

                error_log("Pointing system fetched: " . json_encode($pointing_system));

                // Award points to winners
                if ($final_match) {
                    error_log("Processing final match points. Final match data: " . json_encode($final_match));

                    // First place (Champion)
                    if (!isset($pointing_system['first_place_points']) || !is_numeric($pointing_system['first_place_points'])) {
                        error_log("Error: Invalid first place points value: " . var_export($pointing_system['first_place_points'], true));
                    } else {
                        $update_points = "
                            UPDATE grade_section_course 
                            SET Points = COALESCE(Points, 0) + ? 
                            WHERE id = ?";
                        $stmt = $conn->prepare($update_points);
                        if (!$stmt) {
                            error_log("Error preparing first place points update: " . $conn->error);
                        } else {
                            $stmt->bind_param("ii", $pointing_system['first_place_points'], $final_match['winner_gsc_id']);
                            $result = $stmt->execute();
                            error_log("First place points update result: " . ($result ? "Success" : "Failed") .
                                ". Points: " . $pointing_system['first_place_points'] .
                                ", GSC ID: " . $final_match['winner_gsc_id'] .
                                ", Affected rows: " . $stmt->affected_rows);
                        }
                    }

                    // Second place
                    if (!isset($pointing_system['second_place_points']) || !is_numeric($pointing_system['second_place_points'])) {
                        error_log("Error: Invalid second place points value: " . var_export($pointing_system['second_place_points'], true));
                    } else {
                        $update_points = "
                            UPDATE grade_section_course 
                            SET Points = COALESCE(Points, 0) + ? 
                            WHERE id = ?";
                        $stmt = $conn->prepare($update_points);
                        if (!$stmt) {
                            error_log("Error preparing second place points update: " . $conn->error);
                        } else {
                            $stmt->bind_param("ii", $pointing_system['second_place_points'], $final_match['loser_gsc_id']);
                            $result = $stmt->execute();
                            error_log("Second place points update result: " . ($result ? "Success" : "Failed") .
                                ". Points: " . $pointing_system['second_place_points'] .
                                ", GSC ID: " . $final_match['loser_gsc_id'] .
                                ", Affected rows: " . $stmt->affected_rows);
                        }
                    }
                }

                // Third place
                if ($third_place_match) {
                    error_log("Processing third place points. Third place match data: " . json_encode($third_place_match));

                    if (!isset($pointing_system['third_place_points']) || !is_numeric($pointing_system['third_place_points'])) {
                        error_log("Error: Invalid third place points value: " . var_export($pointing_system['third_place_points'], true));
                    } else {
                        $update_points = "
                            UPDATE grade_section_course 
                            SET Points = COALESCE(Points, 0) + ? 
                            WHERE id = ?";
                        $stmt = $conn->prepare($update_points);
                        if (!$stmt) {
                            error_log("Error preparing third place points update: " . $conn->error);
                        } else {
                            $stmt->bind_param("ii", $pointing_system['third_place_points'], $third_place_match['winner_gsc_id']);
                            $result = $stmt->execute();
                            error_log("Third place points update result: " . ($result ? "Success" : "Failed") .
                                ". Points: " . $pointing_system['third_place_points'] .
                                ", GSC ID: " . $third_place_match['winner_gsc_id'] .
                                ", Affected rows: " . $stmt->affected_rows);
                        }
                    }
                }
            }

            // Delete from live_scores after successful insertion
            error_log("Attempting to delete live score for schedule_id: " . $schedule_id);
            $delete_live_score_query = "DELETE FROM live_default_scores WHERE schedule_id = ?";
            error_log("Delete query: " . $delete_live_score_query);

            $stmt = $conn->prepare($delete_live_score_query);
            if (!$stmt) {
                error_log("Error preparing delete statement: " . $conn->error);
                throw new Exception("Error preparing delete statement: " . $conn->error);
            }
            error_log("Statement prepared successfully");

            $stmt->bind_param("i", $schedule_id);
            error_log("Parameters bound successfully. schedule_id: " . $schedule_id);

            if (!$stmt->execute()) {
                error_log("Error deleting live score: " . $stmt->error);
                throw new Exception("Error deleting live score: " . $stmt->error);
            }
            error_log("Successfully deleted live score. Affected rows: " . $stmt->affected_rows);

            // If everything is successful, commit the transaction
            $conn->commit();
            error_log("Transaction committed successfully");

            echo json_encode([
                'success' => true,
                'message' => 'Match ended successfully',
                'match_id' => $row['match_id'],
                'status' => 'success'
            ]);
        } catch (Exception $e) {
            // If there's an error, rollback the transaction
            $conn->rollback();
            throw $e;
        }
    } else {
        throw new Exception("Error retrieving match data.");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage(), 'status' => 'error']);
}
