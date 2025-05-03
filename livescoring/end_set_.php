<?php
include_once '../connection/conn.php';
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

    // Get match_id and other data from schedules and live_set_scores
    $retrieve_score_query = "
        SELECT 
            lss.schedule_id,
            lss.game_id,
            lss.teamA_id,
            lss.teamB_id,
            lss.teamA_sets_won,
            lss.teamB_sets_won,
            s.match_id,
            m.bracket_id
        FROM live_set_scores lss
        JOIN schedules s ON lss.schedule_id = s.schedule_id
        JOIN matches m ON s.match_id = m.match_id
        WHERE lss.schedule_id = ?";

    $stmt = $conn->prepare($retrieve_score_query);
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $row = $result->fetch_assoc()) {
        // Determine winning and losing teams based on sets won
        $winning_team_id = ($row['teamA_sets_won'] > $row['teamB_sets_won']) ? $row['teamA_id'] : $row['teamB_id'];
        $losing_team_id = ($row['teamA_sets_won'] < $row['teamB_sets_won']) ? $row['teamA_id'] : $row['teamB_id'];

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
                'score_teamA' => $row['teamA_sets_won'],
                'score_teamB' => $row['teamB_sets_won'],
                'winning_team_id' => $winning_team_id,
                'losing_team_id' => $losing_team_id
            ]));

            $insert_stmt = $conn->prepare($insert_match_result_query);
            $insert_stmt->bind_param(
                "iiiiiiii",
                $row['match_id'],
                $row['game_id'],
                $row['teamA_id'],
                $row['teamB_id'],
                $row['teamA_sets_won'],
                $row['teamB_sets_won'],
                $winning_team_id,
                $losing_team_id
            );

            if (!$insert_stmt->execute()) {
                throw new Exception("Error inserting match result: " . $insert_stmt->error);
            }

            // Update match status to finished
            $update_match_status = "UPDATE matches SET status = 'Finished' WHERE match_id = ?";
            $update_match_stmt = $conn->prepare($update_match_status);
            $update_match_stmt->bind_param("i", $row['match_id']);
            if (!$update_match_stmt->execute()) {
                throw new Exception("Error updating match status: " . $update_match_stmt->error);
            }

            // Update team stats
            // Update wins for the winning team
            $update_team_wins_query = "UPDATE teams SET wins = wins + 1 WHERE team_id = ?";
            $update_team_wins_stmt = $conn->prepare($update_team_wins_query);
            $update_team_wins_stmt->bind_param("i", $winning_team_id);
            if (!$update_team_wins_stmt->execute()) {
                throw new Exception("Error updating winning team stats: " . $update_team_wins_stmt->error);
            }

            // Update losses for the losing team
            $update_team_losses_query = "UPDATE teams SET losses = losses + 1 WHERE team_id = ?";
            $update_team_losses_stmt = $conn->prepare($update_team_losses_query);
            $update_team_losses_stmt->bind_param("i", $losing_team_id);
            if (!$update_team_losses_stmt->execute()) {
                throw new Exception("Error updating losing team stats: " . $update_team_losses_stmt->error);
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

                $log_stmt = $conn->prepare($log_query);
                $log_stmt->bind_param("iiss", $match_id, $team_id, $type, $additional_info);
                $log_stmt->execute();
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

            $match_result_stmt = $conn->prepare($match_result_query);
            $match_result_stmt->bind_param("i", $row['match_id']);
            $match_result_stmt->execute();
            $match_result = $match_result_stmt->get_result()->fetch_assoc();

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
                $total_matches_stmt = $conn->prepare($get_total_matches);
                $total_matches_stmt->bind_param("i", $match_result['bracket_id']);
                $total_matches_stmt->execute();
                $total_matches = $total_matches_stmt->get_result()->fetch_assoc()['total_matches'];
                $total_matches_stmt->close();

                // Identify the final match (highest match_number in bracket)
                //         $get_final_match = "
                // SELECT match_id, teamA_id, teamB_id 
                // FROM matches 
                // WHERE bracket_id = ? 
                // ORDER BY match_number DESC 
                // LIMIT 1";

                $get_final_match = "
    SELECT match_id, teamA_id, teamB_id 
    FROM matches 
    WHERE bracket_id = ? AND match_type = 'final'";

                $final_match_stmt = $conn->prepare($get_final_match);
                $final_match_stmt->bind_param("i", $match_result['bracket_id']);
                $final_match_stmt->execute();
                $final_match = $final_match_stmt->get_result()->fetch_assoc();
                $final_match_stmt->close();

                // Identify Semifinals (Last 2 matches before the final)
                //         $get_semifinals = "
                // SELECT match_id, match_number 
                // FROM matches 
                // WHERE bracket_id = ? 
                // ORDER BY match_number DESC 
                // LIMIT 2";
                $get_semifinals = "
    SELECT match_id, match_number 
    FROM matches 
    WHERE bracket_id = ? AND match_type = 'semifinal'";

                $semifinals_stmt = $conn->prepare($get_semifinals);
                $semifinals_stmt->bind_param("i", $match_result['bracket_id']);
                $semifinals_stmt->execute();
                $semifinals = $semifinals_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                $semifinals_stmt->close();

                // Identify Third Place Match (if it exists)
                //         $get_third_place = "
                // SELECT match_id, teamA_id, teamB_id 
                // FROM matches 
                // WHERE bracket_id = ? 
                // AND match_number = ?";
                //         $third_place_match_number = $total_matches - 1; 
                //         $third_place_stmt = $conn->prepare($get_third_place);
                //         $third_place_stmt->bind_param("ii", $match_result['bracket_id'], $third_place_match_number);
                //         $third_place_stmt->execute();
                //         $third_place_match = $third_place_stmt->get_result()->fetch_assoc();
                //         $third_place_stmt->close();

                // Identify Third Place Match (if it exists)
                $get_third_place = "
SELECT match_id, teamA_id, teamB_id 
FROM matches 
WHERE bracket_id = ? AND match_type = 'third_place'";
                $third_place_stmt = $conn->prepare($get_third_place);
                $third_place_stmt->bind_param("i", $match_result['bracket_id']);
                $third_place_stmt->execute();
                $third_place_match = $third_place_stmt->get_result()->fetch_assoc();
                $third_place_stmt->close();


                // ✅ **Update Final Match with Semifinal Winners**
                if ($final_match && in_array($match_result['match_id'], array_column($semifinals, 'match_id'))) {
                    if ($final_match['teamA_id'] == -2) {
                        $update_final_match = "UPDATE matches SET teamA_id = ? WHERE match_id = ?";
                        $update_final_stmt = $conn->prepare($update_final_match);
                        $update_final_stmt->bind_param("ii", $winning_team_id, $final_match['match_id']);
                        $update_final_stmt->execute();
                        error_log("Updated Final Match: TeamA with Winner $winning_team_id");
                    } elseif ($final_match['teamB_id'] == -2) {
                        $update_final_match = "UPDATE matches SET teamB_id = ? WHERE match_id = ?";
                        $update_final_stmt = $conn->prepare($update_final_match);
                        $update_final_stmt->bind_param("ii", $winning_team_id, $final_match['match_id']);
                        $update_final_stmt->execute();
                        error_log("Updated Final Match: TeamB with Winner $winning_team_id");
                    }
                }

                // ✅ **Update Third Place Match with Losing Teams**
                if ($third_place_match && in_array($match_result['match_id'], array_column($semifinals, 'match_id'))) {
                    if ($third_place_match['teamA_id'] == -2) {
                        $update_third_place = "UPDATE matches SET teamA_id = ? WHERE match_id = ?";
                        $update_third_stmt = $conn->prepare($update_third_place);
                        $update_third_stmt->bind_param("ii", $losing_team_id, $third_place_match['match_id']);
                        $update_third_stmt->execute();
                        error_log("Updated Third Place Match: TeamA with Loser $losing_team_id");
                    } elseif ($third_place_match['teamB_id'] == -2) {
                        $update_third_place = "UPDATE matches SET teamB_id = ? WHERE match_id = ?";
                        $update_third_stmt = $conn->prepare($update_third_place);
                        $update_third_stmt->bind_param("ii", $losing_team_id, $third_place_match['match_id']);
                        $update_third_stmt->execute();
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
                $next_match_stmt = $conn->prepare($get_next_match);
                $next_match_stmt->bind_param("ii", $match_result['bracket_id'], $match_result['next_match_number']);
                $next_match_stmt->execute();
                $next_match = $next_match_stmt->get_result()->fetch_assoc();
                $next_match_stmt->close();

                if ($next_match) {
                    if ($match_result['match_number'] % 2 == 1 && $next_match['teamA_id'] == -2) {
                        // Odd match number updates teamA if it's TBD (-2)
                        $update_next_match = "UPDATE matches SET teamA_id = ? WHERE match_id = ?";
                        $update_next_stmt = $conn->prepare($update_next_match);
                        $update_next_stmt->bind_param("ii", $winning_team_id, $next_match['match_id']);
                        $update_next_stmt->execute();
                        $update_next_stmt->close();
                        error_log("Updated Next Match TeamA with Team $winning_team_id");
                    } elseif ($match_result['match_number'] % 2 == 0 && $next_match['teamB_id'] == -2) {
                        // Even match number updates teamB if it's TBD (-2)
                        $update_next_match = "UPDATE matches SET teamB_id = ? WHERE match_id = ?";
                        $update_next_stmt = $conn->prepare($update_next_match);
                        $update_next_stmt->bind_param("ii", $winning_team_id, $next_match['match_id']);
                        $update_next_stmt->execute();
                        $update_next_stmt->close();
                        error_log("Updated Next Match TeamB with Team $winning_team_id");
                    }
                }
            }

            // Function to insert match period information
            function insertMatchPeriodInfo($conn, $match_id, $period_number, $teamA_id, $teamB_id, $scoreA, $scoreB)
            {
                $insert_period_query = "
                    INSERT INTO match_periods_info (
                        match_id, 
                        period_number, 
                        teamA_id, 
                        teamB_id, 
                        score_teamA, 
                        score_teamB, 
                        timestamp
                    ) VALUES (?, ?, ?, ?, ?, ?, NOW())";

                $period_stmt = $conn->prepare($insert_period_query);
                $period_stmt->bind_param(
                    "iiiidd",
                    $match_id,
                    $period_number,
                    $teamA_id,
                    $teamB_id,
                    $scoreA,
                    $scoreB
                );

                if (!$period_stmt->execute()) {
                    throw new Exception("Error inserting match period info: " . $period_stmt->error);
                }

                error_log("Inserted match period info for match_id: $match_id, period: $period_number");
                return true;
            }

            // Insert match period information after successful match result
            try {
                // Insert period info for the current set/period
                insertMatchPeriodInfo(
                    $conn,
                    $row['match_id'],
                    $row['teamA_sets_won'] + $row['teamB_sets_won'],
                    $row['teamA_id'],
                    $row['teamB_id'],
                    $row['teamA_sets_won'],
                    $row['teamB_sets_won']
                );
            } catch (Exception $e) {
                error_log("Failed to insert match period info: " . $e->getMessage());
                // Optionally rethrow or handle the error as needed
            }

            // Check if all matches in the bracket are finished
            $check_bracket_matches = "
                SELECT COUNT(*) as total_matches, 
                       SUM(CASE WHEN status = 'Finished' THEN 1 ELSE 0 END) as finished_matches
                FROM matches 
                WHERE bracket_id = ?";
            $bracket_stmt = $conn->prepare($check_bracket_matches);

            $bracket_stmt->bind_param("i", $row['bracket_id']);
            $bracket_stmt->execute();
            $bracket_status = $bracket_stmt->get_result()->fetch_assoc();
            $bracket_stmt->close();

            if ($bracket_status['total_matches'] == $bracket_status['finished_matches']) {
                // Update bracket status to Completed
                $update_bracket = "UPDATE brackets SET status = 'Completed' WHERE bracket_id = ?";
                $update_bracket_stmt = $conn->prepare($update_bracket);
                $update_bracket_stmt->bind_param("i", $row['bracket_id']);
                $update_bracket_stmt->execute();
                $update_bracket_stmt->close();

                // Get final match result
                $final_match_query = "
                    SELECT mr.*, m.match_type, t1.grade_section_course_id as winner_gsc_id, t2.grade_section_course_id as loser_gsc_id
                    FROM matches m
                    JOIN match_results mr ON m.match_id = mr.match_id
                    JOIN teams t1 ON mr.winning_team_id = t1.team_id
                    JOIN teams t2 ON mr.losing_team_id = t2.team_id
                    WHERE m.bracket_id = ? AND m.match_type = 'final'";
                $final_match_stmt = $conn->prepare($final_match_query);
                $final_match_stmt->bind_param("i", $row['bracket_id']);
                $final_match_stmt->execute();
                $final_match = $final_match_stmt->get_result()->fetch_assoc();
                $final_match_stmt->close();

                // Get third place match result
                $third_place_query = "
                    SELECT mr.*, m.match_type, t1.grade_section_course_id as winner_gsc_id
                    FROM matches m
                    JOIN match_results mr ON m.match_id = mr.match_id
                    JOIN teams t1 ON mr.winning_team_id = t1.team_id
                    WHERE m.bracket_id = ? AND m.match_type = 'third_place'";
                $third_place_stmt = $conn->prepare($third_place_query);
                $third_place_stmt->bind_param("i", $row['bracket_id']);
                $third_place_stmt->execute();
                $third_place_match = $third_place_stmt->get_result()->fetch_assoc();
                $third_place_stmt->close();

                // Get pointing system
                if (!isset($_SESSION['school_id'])) {
                    error_log("Error: school_id not found in session");
                    die(json_encode(['error' => 'School ID not found']));
                }

                $pointing_query = "
                    SELECT ps.first_place_points, ps.second_place_points, ps.third_place_points
                    FROM pointing_system ps
                    WHERE ps.school_id = ?";
                $pointing_stmt = $conn->prepare($pointing_query);
                $pointing_stmt->bind_param("i", $_SESSION['school_id']);
                $pointing_stmt->execute();
                $pointing_system = $pointing_stmt->get_result()->fetch_assoc();
                $pointing_stmt->close();

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
                        $update_points_stmt = $conn->prepare($update_points);
                        if (!$update_points_stmt) {
                            error_log("Error preparing first place points update: " . $conn->error);
                        } else {
                            $update_points_stmt->bind_param("ii", $pointing_system['first_place_points'], $final_match['winner_gsc_id']);
                            $result = $update_points_stmt->execute();
                            error_log("First place points update result: " . ($result ? "Success" : "Failed") .
                                ". Points: " . $pointing_system['first_place_points'] .
                                ", GSC ID: " . $final_match['winner_gsc_id'] .
                                ", Affected rows: " . $update_points_stmt->affected_rows);
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
                        $update_points_stmt = $conn->prepare($update_points);
                        if (!$update_points_stmt) {
                            error_log("Error preparing second place points update: " . $conn->error);
                        } else {
                            $update_points_stmt->bind_param("ii", $pointing_system['second_place_points'], $final_match['loser_gsc_id']);
                            $result = $update_points_stmt->execute();
                            error_log("Second place points update result: " . ($result ? "Success" : "Failed") .
                                ". Points: " . $pointing_system['second_place_points'] .
                                ", GSC ID: " . $final_match['loser_gsc_id'] .
                                ", Affected rows: " . $update_points_stmt->affected_rows);
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
                        $update_points_stmt = $conn->prepare($update_points);
                        if (!$update_points_stmt) {
                            error_log("Error preparing third place points update: " . $conn->error);
                        } else {
                            $update_points_stmt->bind_param("ii", $pointing_system['third_place_points'], $third_place_match['winner_gsc_id']);
                            $result = $update_points_stmt->execute();
                            error_log("Third place points update result: " . ($result ? "Success" : "Failed") .
                                ". Points: " . $pointing_system['third_place_points'] .
                                ", GSC ID: " . $third_place_match['winner_gsc_id'] .
                                ", Affected rows: " . $update_points_stmt->affected_rows);
                        }
                    }
                }
            }

            // Delete from live_set_scores after successful insertion
            error_log("Attempting to delete live set score for schedule_id: " . $schedule_id);
            $delete_live_score_query = "DELETE FROM live_set_scores WHERE schedule_id = ?";
            error_log("Delete query: " . $delete_live_score_query);
            $delete_stmt = $conn->prepare($delete_live_score_query);
            if (!$delete_stmt) {
                error_log("Error preparing delete statement: " . $conn->error);
                throw new Exception("Error preparing delete statement: " . $conn->error);
            }
            error_log("Statement prepared successfully");

            $delete_stmt->bind_param("i", $schedule_id);
            error_log("Parameters bound successfully. schedule_id: " . $schedule_id);

            if (!$delete_stmt->execute()) {
                error_log("Error deleting live set score: " . $delete_stmt->error);
                throw new Exception("Error deleting live set score: " . $delete_stmt->error);
            }
            error_log("Successfully deleted live set score. Affected rows: " . $delete_stmt->affected_rows);

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
