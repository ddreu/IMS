<?php
session_start();
include_once '../connection/conn.php';
include '../user_logs/logger.php'; // Include the logger file
$conn = con();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $game_id = intval($_POST['game_id']);
    $game_name = trim($_POST['game_name']);
    $number_of_players = intval($_POST['number_of_players']);
    $category = trim($_POST['category']);
    $environment = trim($_POST['environment']);

    // Validate inputs
    if (empty($game_name) || empty($number_of_players) || empty($category) || empty($environment)) {
        $response = ['status' => 'error', 'message' => 'All fields are required.'];
    } else {
        // Fetch previous data for logging
        $fetch_sql = "SELECT * FROM games WHERE game_id = ?";
        $fetch_stmt = mysqli_prepare($conn, $fetch_sql);

        if ($fetch_stmt) {
            mysqli_stmt_bind_param($fetch_stmt, "i", $game_id);
            mysqli_stmt_execute($fetch_stmt);
            $fetch_result = mysqli_stmt_get_result($fetch_stmt);
            $previous_data = mysqli_fetch_assoc($fetch_result);
            $previous_game_name = $previous_data['game_name'];
            mysqli_stmt_close($fetch_stmt);
        } else {
            $response = ['status' => 'error', 'message' => 'Failed to fetch game details for logging.'];
            header('Content-Type: application/json');
            echo json_encode($response);
            exit;
        }

        // Check for duplicate game name
        $check_sql = "SELECT * FROM games WHERE game_name = ? AND game_id != ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);

        if ($check_stmt) {
            mysqli_stmt_bind_param($check_stmt, "si", $game_name, $game_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);

            if (mysqli_num_rows($check_result) > 0) {
                $response = ['status' => 'error', 'message' => 'Game name already exists!'];
            } else {
                // Update game data
                $update_sql = "UPDATE games SET game_name = ?, number_of_players = ?, category = ?, environment = ? WHERE game_id = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);

                if ($update_stmt) {
                    mysqli_stmt_bind_param($update_stmt, "sissi", $game_name, $number_of_players, $category, $environment, $game_id);
                    $update_result = mysqli_stmt_execute($update_stmt);

                    if ($update_result) {
                        // Log the changes
                        $changes = [];
                        if ($previous_data['game_name'] !== $game_name) {
                            $changes[] = "Game Name: '{$previous_data['game_name']}' → '{$game_name}'";
                        }
                        if ($previous_data['number_of_players'] != $number_of_players) {
                            $changes[] = "Number of Players: '{$previous_data['number_of_players']}' → '{$number_of_players}'";
                        }
                        if ($previous_data['category'] !== $category) {
                            $changes[] = "Category: '{$previous_data['category']}' → '{$category}'";
                        }
                        if ($previous_data['environment'] !== $environment) {
                            $changes[] = "Environment: '{$previous_data['environment']}' → '{$environment}'";
                        }
                        $log_description = "Updated game '{$previous_game_name}': " . implode(", ", $changes);
                        logUserAction($conn, $_SESSION['user_id'], "games", "UPDATE", $game_id, $log_description);

                        $response = ['status' => 'success', 'message' => 'Game updated successfully!'];
                    } else {
                        $response = ['status' => 'error', 'message' => 'Error updating game: ' . mysqli_error($conn)];
                    }

                    mysqli_stmt_close($update_stmt);
                } else {
                    $response = ['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)];
                }
            }
            mysqli_stmt_close($check_stmt);
        } else {
            $response = ['status' => 'error', 'message' => 'Database error: ' . mysqli_error($conn)];
        }
    }

    // Send JSON response
    header('Content-Type: application/json');
    echo json_encode($response);
    exit;
}
