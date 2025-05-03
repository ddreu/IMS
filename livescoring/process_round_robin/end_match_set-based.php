<?php
require_once '../../connection/conn.php';
require_once '../../user_logs/logger.php';
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

    // Get match data from live_set_scores
    $retrieve_score_query = "
        SELECT 
            lss.schedule_id,
            lss.game_id,
            lss.teamA_id,
            lss.teamB_id,
            lss.teamA_score,
            lss.teamB_score,
            lss.teamA_sets_won,
            lss.teamB_sets_won,
            lss.current_set,
            s.match_id,
            m.bracket_id
        FROM live_set_scores lss
        JOIN schedules s ON lss.schedule_id = s.schedule_id
        JOIN matches m ON s.match_id = m.match_id
        WHERE lss.schedule_id = ?";

    $stmt = $conn->prepare($retrieve_score_query);
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();

    if ($row) {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Determine winning and losing teams
            $winning_team_id = ($row['teamA_sets_won'] > $row['teamB_sets_won']) ? $row['teamA_id'] : $row['teamB_id'];
            $losing_team_id = ($row['teamA_sets_won'] < $row['teamB_sets_won']) ? $row['teamA_id'] : $row['teamB_id'];

            // Use the actual sets won from the table
            $teamA_sets = $row['teamA_sets_won'];
            $teamB_sets = $row['teamB_sets_won'];

            error_log("Sets won - Team A: $teamA_sets, Team B: $teamB_sets");

            // Check if match can be ended
            if ($teamA_sets == $teamB_sets) {
                throw new Exception("Match cannot be ended - no team has won more sets. Team A: $teamA_sets, Team B: $teamB_sets");
            }

            // Add match period info logging
            error_log("Match ID: " . $row['match_id'] .
                ", TeamA Sets: " . $row['teamA_sets_won'] .
                ", TeamB Sets: " . $row['teamB_sets_won']);

            // Add validation for sets won matching
            if ($teamA_sets !== $row['teamA_sets_won'] || $teamB_sets !== $row['teamB_sets_won']) {
                error_log("Set count mismatch - Calculated: A=$teamA_sets,B=$teamB_sets, Stored: A={$row['teamA_sets_won']},B={$row['teamB_sets_won']}");
                throw new Exception("Set count validation failed");
            }

            // Modify insert match result query
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

            $stmt = $conn->prepare($insert_match_result_query);
            $stmt->bind_param(
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
            $stmt->execute();

            // Update match status
            $update_match_status = "UPDATE matches SET status = 'Finished' WHERE match_id = ?";
            $stmt = $conn->prepare($update_match_status);
            $stmt->bind_param("i", $row['match_id']);
            $stmt->execute();

            // Get and apply tournament scoring rules
            $scoring_query = "SELECT * FROM tournament_scoring WHERE bracket_id = ?";
            $stmt = $conn->prepare($scoring_query);
            $stmt->bind_param("i", $row['bracket_id']);
            $stmt->execute();
            $scoring_rules = $stmt->get_result()->fetch_assoc();

            if (!$scoring_rules) {
                throw new Exception("No scoring rules found for this tournament");
            }

            // Update tournament points
            if ($row['teamA_sets_won'] > $row['teamB_sets_won']) {
                updateTeamPoints($conn, $row['bracket_id'], $row['teamA_id'], $scoring_rules['win_points'], true);
                updateTeamPoints($conn, $row['bracket_id'], $row['teamB_id'], $scoring_rules['loss_points'], false);
            } else {
                updateTeamPoints($conn, $row['bracket_id'], $row['teamB_id'], $scoring_rules['win_points'], true);
                updateTeamPoints($conn, $row['bracket_id'], $row['teamA_id'], $scoring_rules['loss_points'], false);
            }

            // Check if bracket is completed and update status
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
                $update_bracket = "UPDATE brackets SET status = 'Completed' WHERE bracket_id = ?";
                $stmt = $conn->prepare($update_bracket);
                $stmt->bind_param("i", $row['bracket_id']);
                $stmt->execute();

                // Get pointing system
                if (!isset($_SESSION['school_id'])) {
                    error_log("Error: school_id not found in session");
                    throw new Exception('School ID not found');
                }

                $pointing_query = "
                    SELECT ps.first_place_points, ps.second_place_points, ps.third_place_points
                    FROM pointing_system ps
                    WHERE ps.school_id = ?";
                $pointing_stmt = $conn->prepare($pointing_query);
                $pointing_stmt->bind_param("i", $_SESSION['school_id']);
                $pointing_stmt->execute();
                $pointing_system = $pointing_stmt->get_result()->fetch_assoc();

                if (!$pointing_system) {
                    error_log("Error: No pointing system found for school_id: " . $_SESSION['school_id']);
                    throw new Exception('Pointing system not found');
                }


                //             $standings_query = "
                // SELECT 
                //     t.team_id, 
                //     t.grade_section_course_id, 
                //     COUNT(CASE WHEN mr.winning_team_id = t.team_id THEN 1 END) as wins
                // FROM match_results mr
                // JOIN matches m ON m.match_id = mr.match_id
                // JOIN teams t ON (mr.team_A_id = t.team_id OR mr.team_B_id = t.team_id)
                // WHERE m.bracket_id = ?
                // GROUP BY t.team_id
                // ORDER BY wins DESC
                // LIMIT 3";

                // Get final standings (top 3 teams) based on total points
                $standings_query = "SELECT t.team_id, t.grade_section_course_id, ttp.total_points,
      ROW_NUMBER() OVER (ORDER BY ttp.total_points DESC, t.wins DESC) as rank
      FROM team_tournament_points ttp
      JOIN teams t ON ttp.team_id = t.team_id
      WHERE ttp.bracket_id = ?
      ORDER BY ttp.total_points DESC, t.wins DESC
      LIMIT 3";


                $standings_stmt = $conn->prepare($standings_query);
                $standings_stmt->bind_param("i", $row['bracket_id']);
                $standings_stmt->execute();
                $standings = $standings_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

                // Award points based on standings
                foreach ($standings as $index => $team) {
                    $points = 0;
                    if ($index == 0) {
                        $points = $pointing_system['first_place_points'];
                    } else if ($index == 1) {
                        $points = $pointing_system['second_place_points'];
                    } else if ($index == 2) {
                        $points = $pointing_system['third_place_points'];
                    }

                    if ($points > 0) {
                        $update_points = "
                            UPDATE grade_section_course 
                            SET Points = COALESCE(Points, 0) + ? 
                            WHERE id = ?";
                        $update_stmt = $conn->prepare($update_points);
                        $update_stmt->bind_param("ii", $points, $team['grade_section_course_id']);
                        $update_stmt->execute();
                        error_log("Updated points for GSC ID: {$team['grade_section_course_id']} with $points points");
                    }
                }
            }

            // Delete from live_set_scores
            $delete_live_score_query = "DELETE FROM live_set_scores WHERE schedule_id = ?";
            $stmt = $conn->prepare($delete_live_score_query);
            $stmt->bind_param("i", $schedule_id);
            $stmt->execute();

            // Add user action logging
            // if (isset($_SESSION['user_id'])) {
            //     logUserAction(
            //         $conn,
            //         $_SESSION['user_id'],
            //         'match_end',
            //         "Ended set-based match {$row['match_id']} (Schedule: $schedule_id)"
            //     );
            // }

            $conn->commit();

            echo json_encode([
                'success' => true,
                'message' => 'Match ended successfully',
                'match_id' => $row['match_id'],
                'status' => 'success'
            ]);
        } catch (Exception $e) {
            $conn->rollback();
            throw $e;
        }
    } else {
        throw new Exception("Error retrieving match data.");
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'status' => 'error'
    ]);
}

function updateTeamPoints($conn, $bracket_id, $team_id, $points, $is_win = null)
{
    try {
        error_log("Updating points for team_id: $team_id in bracket: $bracket_id");
        error_log("Points to award: $points, is_win: " . ($is_win ? 'win' : 'loss'));

        // First check if entry exists
        $check_query = "SELECT total_points FROM team_tournament_points 
                       WHERE bracket_id = ? AND team_id = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("ii", $bracket_id, $team_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            // Insert new entry
            error_log("Creating new points entry");
            $insert_query = "INSERT INTO team_tournament_points 
                           (bracket_id, team_id, total_points, bonus_points) 
                           VALUES (?, ?, ?, 0)";
            $stmt = $conn->prepare($insert_query);
            $stmt->bind_param("iii", $bracket_id, $team_id, $points);
        } else {
            // Update existing entry
            error_log("Updating existing points entry");
            $update_query = "UPDATE team_tournament_points 
                           SET total_points = total_points + ? 
                           WHERE bracket_id = ? AND team_id = ?";
            $stmt = $conn->prepare($update_query);
            $stmt->bind_param("iii", $points, $bracket_id, $team_id);
        }

        if (!$stmt->execute()) {
            throw new Exception("Error updating points: " . $stmt->error);
        }
        error_log("Points update successful. Affected rows: " . $stmt->affected_rows);

        // Update team wins/losses
        if ($is_win !== null) {
            $update_team = "UPDATE teams SET " .
                ($is_win ? "wins = wins + 1" : "losses = losses + 1") .
                " WHERE team_id = ?";
            $stmt = $conn->prepare($update_team);
            $stmt->bind_param("i", $team_id);
            if (!$stmt->execute()) {
                throw new Exception("Error updating team stats: " . $stmt->error);
            }
            error_log("Team stats updated. Affected rows: " . $stmt->affected_rows);
        }
    } catch (Exception $e) {
        error_log("Error in updateTeamPoints: " . $e->getMessage());
        throw $e;
    }
}
