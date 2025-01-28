<?php
session_start();
include_once '../connection/conn.php';
include '../user_logs/logger.php';
$conn = con();

// Validate and sanitize input
$game_id = filter_input(INPUT_POST, 'game_id', FILTER_VALIDATE_INT);
$department_id = filter_input(INPUT_POST, 'department_id', FILTER_VALIDATE_INT);
$school_id = filter_input(INPUT_POST, 'school_id', FILTER_VALIDATE_INT);
$stat_name = isset($_POST['stat_name']) ? trim(htmlspecialchars($_POST['stat_name'])) : null;

// Add a new stat
if (isset($_POST['stat_name'])) {
    $stat_name = trim($_POST['stat_name']); // Ensure stat name is sanitized

    if (isset($game_id, $department_id, $school_id) && !empty($stat_name)) {
        // Check if the stat already exists
        $check_query = "SELECT COUNT(*) as count FROM game_stats_config WHERE game_id = ? AND stat_name = ?";
        $check_stmt = $conn->prepare($check_query);

        if ($check_stmt) {
            $check_stmt->bind_param("is", $game_id, $stat_name);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result()->fetch_assoc();
            $check_stmt->close();

            if ($check_result['count'] > 0) {
                $_SESSION['message'] = ['type' => 'warning', 'text' => 'This stat already exists for the game.'];
                header("Location: scoring_rules_form.php");
                exit();
            }
        }

        // Fetch the game name for logging
        $fetch_game_query = "SELECT game_name FROM games WHERE game_id = ?";
        $fetch_game_stmt = $conn->prepare($fetch_game_query);

        if ($fetch_game_stmt) {
            $fetch_game_stmt->bind_param("i", $game_id);
            $fetch_game_stmt->execute();
            $fetch_game_result = $fetch_game_stmt->get_result()->fetch_assoc();
            $fetch_game_stmt->close();

            $game_name = $fetch_game_result['game_name'] ?? 'Unknown Game';

            // Insert the new stat
            $stat_query = "INSERT INTO game_stats_config (game_id, stat_name) VALUES (?, ?)";
            $stat_stmt = $conn->prepare($stat_query);

            if ($stat_stmt) {
                $stat_stmt->bind_param("is", $game_id, $stat_name);

                if ($stat_stmt->execute()) {
                    // Log the action
                    $stat_id = $conn->insert_id; // Get the ID of the inserted stat
                    $description = "Added Game Stat '$stat_name' for Game: '$game_name'";
                    logUserAction($conn, $_SESSION['user_id'], 'Game Stats', 'CREATE', $stat_id, $description);

                    $_SESSION['message'] = ['type' => 'success', 'text' => 'Game stat added successfully.'];
                } else {
                    $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to add game stat. Please try again.'];
                }
                $stat_stmt->close();
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to prepare the insert query.'];
            }
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to fetch game details.'];
        }
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid input. Stat name cannot be empty.'];
    }
}

// Delete a stat
if (isset($_POST['delete_stat'])) {
    $stat_id = filter_input(INPUT_POST, 'stat_id', FILTER_VALIDATE_INT);

    if ($stat_id) {
        // Fetch stat name and game name for logging
        $fetch_stat_query = "
            SELECT gsc.stat_name, g.game_name, gsc.game_id
            FROM game_stats_config gsc
            JOIN games g ON gsc.game_id = g.game_id
            WHERE gsc.config_id = ?
        ";
        $fetch_stmt = $conn->prepare($fetch_stat_query);
        $fetch_stmt->bind_param("i", $stat_id);
        $fetch_stmt->execute();
        $fetch_result = $fetch_stmt->get_result()->fetch_assoc();
        $fetch_stmt->close();

        if ($fetch_result) {
            $stat_name = $fetch_result['stat_name'];
            $game_name = $fetch_result['game_name'];
            $game_id = $fetch_result['game_id'];

            // Delete the stat
            $delete_query = "DELETE FROM game_stats_config WHERE config_id = ?";
            $delete_stmt = $conn->prepare($delete_query);
            $delete_stmt->bind_param("i", $stat_id);

            if ($delete_stmt->execute()) {
                // Log the action
                $description = "Removed Game Stat '$stat_name' for Game: '$game_name'";
                logUserAction($conn, $_SESSION['user_id'], 'Game Stats', 'DELETE', $stat_id, $description);

                $_SESSION['message'] = ['type' => 'success', 'text' => 'Game stat deleted successfully.'];
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to delete game stat. Please try again.'];
            }
            $delete_stmt->close();
        } else {
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid stat ID or the stat does not exist.'];
        }
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid input.'];
    }
}

// Close the connection
$conn->close();

// Redirect back
header("Location: scoring_rules_form.php");
exit();
