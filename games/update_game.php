<?php
session_start();
include_once '../connection/conn.php'; 
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
        $check_sql = "SELECT * FROM games WHERE game_name = ? AND game_id != ?";
        $check_stmt = mysqli_prepare($conn, $check_sql);
        
        if ($check_stmt) {
            mysqli_stmt_bind_param($check_stmt, "si", $game_name, $game_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);

            if (mysqli_num_rows($check_result) > 0) {
                $response = ['status' => 'error', 'message' => 'Game name already exists!'];
            } else {
                $update_sql = "UPDATE games SET game_name = ?, number_of_players = ?, category = ?, environment = ? WHERE game_id = ?";
                $update_stmt = mysqli_prepare($conn, $update_sql);
                
                if ($update_stmt) {
                    mysqli_stmt_bind_param($update_stmt, "sissi", $game_name, $number_of_players, $category, $environment, $game_id);
                    $update_result = mysqli_stmt_execute($update_stmt);

                    if ($update_result) {
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
