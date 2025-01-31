<?php
session_start();
include_once '../connection/conn.php';
include '../user_logs/logger.php';
$conn = con();

if (isset($_POST['schedule_id'], $_POST['teamA_id'], $_POST['teamB_id'], $_POST['game_id'])) {
    $schedule_id = $_POST['schedule_id'];
    $game_id = $_POST['game_id'];
    $teamA_id = $_POST['teamA_id'];
    $teamB_id = $_POST['teamB_id'];
    $department_id = $_SESSION['department_id']; // Get department_id from session

    try {
        // Check if game stats are configured for this game
        $stats_stmt = $conn->prepare("SELECT COUNT(*) as count FROM game_stats_config WHERE game_id = ?");
        $stats_stmt->bind_param("i", $game_id);
        $stats_stmt->execute();
        $stats_result = $stats_stmt->get_result();
        $stats_count = $stats_result->fetch_assoc()['count'];
        $stats_stmt->close();

        if ($stats_count === 0) {
            echo json_encode(['success' => false, 'message' => 'Game stats are not configured for this game.']);
            exit();
        }

        // Check if scoring rules are configured for this game and department
        $rules_stmt = $conn->prepare("SELECT COUNT(*) as count FROM game_scoring_rules WHERE game_id = ? AND department_id = ?");
        $rules_stmt->bind_param("ii", $game_id, $department_id);
        $rules_stmt->execute();
        $rules_result = $rules_stmt->get_result();
        $rules_count = $rules_result->fetch_assoc()['count'];
        $rules_stmt->close();

        if ($rules_count === 0) {
            echo json_encode(['success' => false, 'message' => 'Scoring rules are not configured for this game and department.']);
            exit();
        }

        // Get match_id from schedule_id
        $match_stmt = $conn->prepare("SELECT match_id FROM schedules WHERE schedule_id = ?");
        $match_stmt->bind_param("i", $schedule_id);
        $match_stmt->execute();
        $match_result = $match_stmt->get_result();
        $match_data = $match_result->fetch_assoc();
        $match_stmt->close();

        if (!$match_data) {
            echo json_encode(['success' => false, 'message' => 'Schedule not found.']);
            exit();
        }

        // Fetch team names for logging
        $team_names_stmt = $conn->prepare("SELECT team_name FROM teams WHERE team_id IN (?, ?)");
        $team_names_stmt->bind_param("ii", $teamA_id, $teamB_id);
        $team_names_stmt->execute();
        $team_names_result = $team_names_stmt->get_result();
        $team_names = [];
        while ($row = $team_names_result->fetch_assoc()) {
            $team_names[] = $row['team_name'];
        }
        $team_names_stmt->close();

        // Ensure both team names were fetched
        if (count($team_names) < 2) {
            echo json_encode(['success' => false, 'message' => 'Failed to fetch team names.']);
            exit();
        }

        // Check for existing entry in the live_scores table
        $check_live_scores_stmt = $conn->prepare("SELECT COUNT(*) as count FROM live_scores WHERE schedule_id = ?");
        $check_live_scores_stmt->bind_param("i", $schedule_id);
        $check_live_scores_stmt->execute();
        $check_live_scores_result = $check_live_scores_stmt->get_result();
        $live_scores_count = $check_live_scores_result->fetch_assoc()['count'];
        $check_live_scores_stmt->close();

        if ($live_scores_count > 0) {
            // Delete existing entry from live_scores
            $delete_stmt = $conn->prepare("DELETE FROM live_scores WHERE schedule_id = ?");
            $delete_stmt->bind_param("i", $schedule_id);
            $delete_stmt->execute();
            $delete_stmt->close();
        }

        // Update the match status to "Ongoing"
        $update_stmt = $conn->prepare("UPDATE matches SET status = 'Ongoing' WHERE match_id = ?");
        $update_stmt->bind_param("i", $match_data['match_id']);
        $update_stmt->execute();
        $update_stmt->close();

        // Insert the match data into the live_scores table
        $insert_stmt = $conn->prepare("INSERT INTO live_scores (schedule_id, game_id, teamA_id, teamB_id, teamA_score, teamB_score, timeout_teamA, timeout_teamB, foul_teamA, foul_teamB, period, timestamp) VALUES (?, ?, ?, ?, 0, 0, 0, 0, 0, 0, 1, NOW())");
        $insert_stmt->bind_param("iiii", $schedule_id, $game_id, $teamA_id, $teamB_id);

        if ($insert_stmt->execute()) {
            // Log the action
            $teamA_name = $team_names[0];
            $teamB_name = $team_names[1];
            $description = "Started the match between $teamA_name vs $teamB_name";
            logUserAction($conn, $_SESSION['user_id'], 'Matches', 'Match Start', $schedule_id, $description);

            echo json_encode(['success' => true]);
        } else {
            throw new Exception('Failed to insert live scores data');
        }
        $insert_stmt->close();

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
}

$conn->close();
