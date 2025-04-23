<?php
session_start();
header('Content-Type: application/json');
include_once '../../connection/conn.php';

$conn = con();
$user_id = $_SESSION['user_id'] ?? null;
$role = $_SESSION['role'] ?? null;

if (!$user_id || $role !== 'Committee') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$input = json_decode(file_get_contents("php://input"), true);
$game_id = $input['game_id'] ?? null;

if (!$game_id || !is_numeric($game_id)) {
    echo json_encode(['success' => false, 'message' => 'Invalid game ID']);
    exit;
}

// Check if the game is assigned to the user (either main or committee)
$sql = "SELECT game_name FROM games WHERE game_id = ?
        AND (game_id = (SELECT game_id FROM users WHERE id = ?)
             OR game_id IN (SELECT game_id FROM committee_games WHERE committee_id = ?))";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iii", $game_id, $user_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $_SESSION['game_id'] = $game_id;
    $_SESSION['game_name'] = $row['game_name'];
    session_write_close();

    echo json_encode(['success' => true, 'message' => 'Game changed successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Unauthorized game change']);
}
