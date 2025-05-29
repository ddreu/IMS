<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

header('Content-Type: application/json');

// Inputs
$school_id = $_GET['school_id'] ?? $_SESSION['school_id'];
$department_id = $_GET['department_id'] ?? null;
$grade_level = $_GET['grade_level'] ?? null;
$game_id = $_GET['game_id'] ?? null;

if (!$department_id || !$school_id) {
    echo json_encode(['error' => 'Missing parameters.']);
    exit;
}

// Fetch all stat columns from game configuration
$statColumns = [];
$statNamesQuery = "SELECT LOWER(TRIM(stat_name)) AS stat_name FROM game_stats_config WHERE game_id = ? AND is_archived = 0";
$stmt = $conn->prepare($statNamesQuery);
$stmt->bind_param('i', $game_id);
$stmt->execute();
$statNamesResult = $stmt->get_result();
while ($row = $statNamesResult->fetch_assoc()) {
    $statColumns[] = $row['stat_name'];
}

// Initialize player list
$players = [];

$playersQuery = "
    SELECT 
        p.player_id,
        CONCAT(TRIM(p.player_firstname), ' ', TRIM(p.player_lastname)) AS player_name
    FROM players p
    JOIN teams t ON p.team_id = t.team_id
    JOIN grade_section_course gsc ON t.grade_section_course_id = gsc.id
    JOIN departments d ON gsc.department_id = d.id
    WHERE d.id = ? 
      AND d.school_id = ? " .
    ($grade_level ? "AND gsc.grade_level = ?" : "") . "
    GROUP BY p.player_id, player_name
";

if ($grade_level) {
    $stmt = $conn->prepare($playersQuery);
    $stmt->bind_param('iis', $department_id, $school_id, $grade_level);
} else {
    $stmt = $conn->prepare($playersQuery);
    $stmt->bind_param('ii', $department_id, $school_id);
}

$stmt->execute();
$playersResult = $stmt->get_result();
while ($row = $playersResult->fetch_assoc()) {
    $playerId = (int)$row['player_id'];

    if (!isset($players[$playerId])) {
        $players[$playerId] = [
            'player_name' => $row['player_name'],
            'stats' => array_fill_keys($statColumns, 0),
        ];
    }
}


// Fetch actual player stats
$statsQuery = "
    SELECT 
        player_id,
        LOWER(TRIM(stat_name)) AS stat_name,
        SUM(stat_value) AS total_stat
    FROM player_match_stats
    WHERE game_id = ? AND is_archived = 0
    GROUP BY player_id, LOWER(TRIM(stat_name))
";

$stmt = $conn->prepare($statsQuery);
$stmt->bind_param('i', $game_id);
$stmt->execute();
$statsResult = $stmt->get_result();

while ($row = $statsResult->fetch_assoc()) {
    $playerId = (int)$row['player_id'];
    $statName = $row['stat_name'];
    $statValue = (int)$row['total_stat'];

    if (isset($players[$playerId]) && in_array($statName, $statColumns)) {
        $players[$playerId]['stats'][$statName] = $statValue;
    }
}

// Output
$response = [
    'stat_columns' => $statColumns,
    'players' => array_values($players),
];

echo json_encode($response);
$conn->close();
