<?php
session_start();
include_once '../connection/conn.php';
$conn = con();
$assigned_game_id = $_GET['game_id'] ?? $_SESSION['game_id'] ?? 1; // Retrieve game_id from request or session

// Fetch scoring rules for the assigned game
$query = "SELECT scoring_unit, score_increment_options, period_type, number_of_periods, duration_per_period, time_limit, point_cap, max_fouls FROM game_scoring_rules WHERE game_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $assigned_game_id);
$stmt->execute();
$result = $stmt->get_result();
$scoring_rules = $result->fetch_assoc();
$stmt->close();
$conn->close();

// Return JSON response
header('Content-Type: application/json');
echo json_encode(['rules' => $scoring_rules]);
