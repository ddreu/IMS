<?php
session_start();
include_once '../connection/conn.php';
include '../user_logs/logger.php'; // Include the logger file
$conn = con();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['game_id'])) {
    $game_id = intval($_POST['game_id']);

    // Retrieve the game details before deletion for logging purposes
    $game_details_query = "SELECT * FROM games WHERE game_id = ?";
    $stmt_details = $conn->prepare($game_details_query);

    if ($stmt_details) {
        $stmt_details->bind_param("i", $game_id);
        $stmt_details->execute();
        $result = $stmt_details->get_result();
        $game_details = $result->fetch_assoc(); // Fetch the game details
        $stmt_details->close();
    } else {
        $_SESSION['error_message'] = "Failed to retrieve game details: " . $conn->error;
        header("Location: games.php");
        exit();
    }

    // Proceed to delete the game
    $sql = "DELETE FROM games WHERE game_id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("i", $game_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Game deleted successfully!";

            // Prepare a detailed log description
            $log_description = "Deleted game: Name = '{$game_details['game_name']}', Number of Players = {$game_details['number_of_players']}, Category = '{$game_details['category']}', Environment = '{$game_details['environment']}'.";

            // Call the logUserAction function to log the action
            logUserAction($conn, $_SESSION['user_id'], "games", "DELETE", $game_id, $log_description);
        } else {
            $_SESSION['error_message'] = "Failed to delete game: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Database error: " . $conn->error;
    }

    header("Location: games.php");
    exit();
}

$_SESSION['error_message'] = "Invalid request.";
header("Location: games.php");
exit();
