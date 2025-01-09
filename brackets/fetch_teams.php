<?php
session_start();
require_once '../connection/conn.php';

header('Content-Type: application/json');

// Check if department_id is provided
$department_id = isset($_POST['department_id']) ? intval($_POST['department_id']) : 0;
$grade_level = isset($_POST['grade_level']) ? $_POST['grade_level'] : null;
$game_id = isset($_SESSION['game_id']) ? intval($_SESSION['game_id']) : 0;

if (!$department_id) {
    echo json_encode(['error' => 'Department ID is required']);
    exit;
}

if (!$game_id) {
    echo json_encode(['error' => 'Game ID is required']);
    exit;
}

$conn = con();

// Build the query based on whether grade_level is provided
$query = "SELECT t.team_id, t.team_name 
          FROM teams t 
          INNER JOIN grade_section_course gsc ON t.grade_section_course_id = gsc.id 
          WHERE gsc.department_id = ? AND t.game_id = ?";
$params = [$department_id, $game_id];

if ($grade_level) {
    $query .= " AND gsc.grade_level = ?";
    $params[] = $grade_level;
}

$query .= " ORDER BY t.team_name";

// Prepare and execute the query
$stmt = $conn->prepare($query);
if (!$stmt) {
    echo json_encode(['error' => 'Failed to prepare query: ' . $conn->error]);
    exit;
}

// Bind parameters
$types = str_repeat('s', count($params));
$stmt->bind_param($types, ...$params);

// Execute the query
if (!$stmt->execute()) {
    echo json_encode(['error' => 'Failed to execute query: ' . $stmt->error]);
    exit;
}

// Get results
$result = $stmt->get_result();
$teams = [];

while ($row = $result->fetch_assoc()) {
    $teams[] = [
        'team_id' => $row['team_id'],
        'team_name' => $row['team_name']
    ];
}

// Close statement and connection
$stmt->close();
$conn->close();

// Return the teams
echo json_encode(['teams' => $teams]);
?>
