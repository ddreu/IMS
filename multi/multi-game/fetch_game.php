<?php
session_start();
header('Content-Type: application/json');
include_once '../../connection/conn.php';

$conn = con();
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id || $_SESSION['role'] !== 'Committee') {
    echo json_encode(['success' => false, 'message' => 'User not logged in or not committee']);
    exit;
}

$games = [];

// Main assigned game
$sql = "SELECT g.game_id, g.game_name 
        FROM users u
        JOIN games g ON u.game_id = g.game_id
        WHERE u.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $games[] = [
        'id' => $row['game_id'],
        'name' => $row['game_name']
    ];
}

// Additional committee games
$sql2 = "SELECT g.game_id, g.game_name 
         FROM committee_games cg
         JOIN games g ON cg.game_id = g.game_id
         WHERE cg.committee_id = ?";
$stmt2 = $conn->prepare($sql2);
$stmt2->bind_param("i", $user_id);
$stmt2->execute();
$result2 = $stmt2->get_result();

while ($row = $result2->fetch_assoc()) {
    $games[] = [
        'id' => $row['game_id'],
        'name' => $row['game_name']
    ];
}

echo json_encode(['success' => true, 'games' => $games]);
