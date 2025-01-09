<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

// Validate and sanitize input
$game_id = isset($_POST['game_id']) ? intval($_POST['game_id']) : null;
$department_id = isset($_POST['department_id']) ? intval($_POST['department_id']) : null;
$school_id = isset($_POST['school_id']) ? intval($_POST['school_id']) : null;
$stat_name = isset($_POST['stat_name']) ? trim($_POST['stat_name']) : null;

// Debug line
error_log('Received game_id: ' . $game_id);
error_log('Received stat_name: ' . $stat_name);

// Check if adding a new stat
if (isset($_POST['stat_name'])) {
    // Validate inputs
    if ($game_id && $department_id && $school_id && !empty($stat_name)) {
        // Check if stat already exists for this game
        $check_query = "SELECT COUNT(*) as count FROM game_stats_config WHERE game_id = ? AND stat_name = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param("is", $game_id, $stat_name);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result()->fetch_assoc();
        $check_stmt->close();

        if ($check_result['count'] > 0) {
            // Stat already exists
            $_SESSION['message'] = ['type' => 'warning', 'text' => 'This stat already exists for the game.'];
            
            // Debug line
            error_log('Duplicate stat message set: ' . print_r($_SESSION['message'], true));
        } else {
            // Prepare and execute the insert query
            $stat_query = "
                INSERT INTO game_stats_config 
                (game_id, stat_name)
                VALUES (?, ?)
            ";

            $stat_stmt = $conn->prepare($stat_query);
            $stat_stmt->bind_param("is", $game_id, $stat_name);

            if ($stat_stmt->execute()) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'Game stat added successfully.'];
                
                // Debug line
                error_log('Success message set: ' . print_r($_SESSION['message'], true));
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to add game stat. Please try again.'];
                
                // Debug line
                error_log('Error message set: ' . print_r($_SESSION['message'], true));
            }
            $stat_stmt->close();
        }
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid input. Stat name cannot be empty.'];
        
        // Debug line
        error_log('Invalid input message set: ' . print_r($_SESSION['message'], true));
        header("Location: scoring_rules_form.php");
        exit();
    }
}

// Handle deletion of a stat
if (isset($_POST['delete_stat'])) {
    $stat_id = intval($_POST['stat_id']); // Sanitize input

    // Prepare and execute the delete query
    $delete_query = "DELETE FROM game_stats_config WHERE config_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("i", $stat_id);

    if ($delete_stmt->execute()) {
        $_SESSION['message'] = ['type' => 'success', 'text' => 'Game stat deleted successfully.'];
    } else {
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Failed to delete game stat. Please try again.'];
    }

    $delete_stmt->close();
    header("Location: scoring_rules_form.php");
    exit();
}

// Close database connection
$conn->close();

// Redirect back to the scoring rules form
header("Location: scoring_rules_form.php");
exit();
?>