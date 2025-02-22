<?php
include_once '../connection/conn.php';

header('Content-Type: application/json');

$game_id = isset($_GET['game_id']) ? intval($_GET['game_id']) : 0;

if (!$game_id) {
    echo json_encode(['error' => 'Invalid game ID']);
    exit;
}

$conn = con();
$query = "SELECT config_id, stat_name FROM game_stats_config WHERE game_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $game_id);
$stmt->execute();
$result = $stmt->get_result();

$stats = [];
while ($row = $result->fetch_assoc()) {
    $stats[] = $row;
}

echo json_encode($stats);
$stmt->close();
$conn->close();
