<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Check database connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Ensure team_id is provided
if (!isset($_GET['team_id'])) {
    $_SESSION['error_message'] = "No team ID provided.";
    header("Location: teams.php"); // Redirect to the teams page
    exit();
}

$team_id = $_GET['team_id'];

$conn->begin_transaction(); // Start transaction

try {
    // Step 1: Check if the team is linked in matches
    $check_sql = "SELECT COUNT(*) FROM matches WHERE teamB_id = ? OR teamA_id = ?";
    $check_stmt = $conn->prepare($check_sql);
    $check_stmt->bind_param("ii", $team_id, $team_id);
    $check_stmt->execute();
    $check_stmt->bind_result($count);
    $check_stmt->fetch();
    $check_stmt->close();

    if ($count > 0) {
        // Optionally delete related matches
        $delete_matches_sql = "DELETE FROM matches WHERE teamB_id = ? OR teamA_id = ?";
        $delete_matches_stmt = $conn->prepare($delete_matches_sql);
        $delete_matches_stmt->bind_param("ii", $team_id, $team_id);
        $delete_matches_stmt->execute();
        $delete_matches_stmt->close();
    }

    // Step 2: Delete the team from the teams table
    $delete_team_sql = "DELETE FROM teams WHERE team_id = ?";
    $delete_team_stmt = $conn->prepare($delete_team_sql);

    if (!$delete_team_stmt) {
        throw new Exception("Failed to prepare delete query.");
    }

    $delete_team_stmt->bind_param("i", $team_id);
    
    if ($delete_team_stmt->execute()) {
        $conn->commit(); // Commit transaction
        $_SESSION['success_message'] = "Team deleted successfully!";
    } else {
        throw new Exception("Failed to delete team.");
    }

    $delete_team_stmt->close();
} catch (Exception $e) {
    $conn->rollback(); // Rollback on error
    $_SESSION['error_message'] = "Error: " . $e->getMessage();
}

// Redirect to the teams page
header("Location: teams.php"); // Change this to your actual teams page
exit();
?>
