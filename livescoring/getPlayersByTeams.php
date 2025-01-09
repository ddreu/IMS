<?php
include_once '../connection/conn.php';
$conn = con();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Validate the incoming schedule_id
    if (!isset($_GET['schedule_id']) || empty($_GET['schedule_id'])) {
        echo json_encode(['error' => 'schedule_id is required']);
        exit;
    }

    $schedule_id = $_GET['schedule_id'];

    // Fetch team IDs based on the schedule
    $query = "SELECT m.teamA_id, m.teamB_id 
              FROM schedules s
              JOIN matches m ON s.match_id = m.match_id 
              WHERE s.schedule_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        echo json_encode(['error' => 'No schedule found for the provided schedule_id']);
        exit;
    }

    $teams = $result->fetch_assoc();
    $teamA_id = $teams['teamA_id'];
    $teamB_id = $teams['teamB_id'];

    // Fetch players for both teams with additional information
    $players_query = "
    SELECT 
        p.player_id,
        p.player_lastname,
        p.player_firstname,
        p.player_middlename,
        p.jersey_number,
        p.team_id,
        CONCAT(
            p.player_lastname, ', ', 
            p.player_firstname, ' ',
            COALESCE(LEFT(p.player_middlename, 1), ''), 
            CASE WHEN p.player_middlename != '' THEN '.' ELSE '' END
        ) AS player_name
    FROM players p
    WHERE p.team_id IN (?, ?)
    ORDER BY p.team_id, p.jersey_number";

    $players_stmt = $conn->prepare($players_query);
    $players_stmt->bind_param("ii", $teamA_id, $teamB_id);
    $players_stmt->execute();
    $players_result = $players_stmt->get_result();

    $players = [];
    while ($player = $players_result->fetch_assoc()) {
        $players[] = [
            'player_id' => (int)$player['player_id'],
            'player_name' => $player['player_name'],
            'jersey_number' => $player['jersey_number'],
            'team_id' => (int)$player['team_id']
        ];
    }

    echo json_encode($players);

    $players_stmt->close();
    $stmt->close();
    $conn->close();
}
