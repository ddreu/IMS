<?php
session_start();
require_once '../connection/conn.php';

header('Content-Type: application/json');

try {
    // Get parameters from either POST or GET
    $department_id = isset($_REQUEST['department_id']) ? intval($_REQUEST['department_id']) : 
                    (isset($_SESSION['department_id']) ? intval($_SESSION['department_id']) : 0);
    $grade_level = isset($_REQUEST['grade_level']) ? $_REQUEST['grade_level'] : null;
    $game_id = isset($_REQUEST['game_id']) ? intval($_REQUEST['game_id']) : 
               (isset($_SESSION['game_id']) ? intval($_SESSION['game_id']) : 0);

    // Debug information
    error_log("Fetching teams with: department_id=$department_id, game_id=$game_id, grade_level=" . ($grade_level ?? 'null'));

    if (!$department_id) {
        throw new Exception('Department ID is required');
    }

    if (!$game_id) {
        throw new Exception('Game ID is required');
    }

    $conn = con();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Build the query based on whether grade_level is provided
    $query = "SELECT t.team_id, t.team_name 
              FROM teams t 
              INNER JOIN grade_section_course gsc ON t.grade_section_course_id = gsc.id 
              WHERE gsc.department_id = ? AND t.game_id = ?";
    $params = [$department_id, $game_id];
    $types = "ii"; // integer for department_id and game_id

    if ($grade_level !== null && $grade_level !== '') {
        $query .= " AND gsc.grade_level = ?";
        $params[] = $grade_level;
        $types .= "s"; // string for grade_level
    }

    $query .= " ORDER BY t.team_name";

    // Debug query
    error_log("Query: $query");
    error_log("Params: " . json_encode($params));

    // Prepare and execute the query
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Failed to prepare query: ' . $conn->error);
    }

    // Bind parameters
    $stmt->bind_param($types, ...$params);

    // Execute the query
    if (!$stmt->execute()) {
        throw new Exception('Failed to execute query: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    $teams = [];

    while ($row = $result->fetch_assoc()) {
        $teams[] = [
            'team_id' => intval($row['team_id']),
            'team_name' => $row['team_name']
        ];
    }

    if (empty($teams)) {
        error_log("No teams found for department_id=$department_id, game_id=$game_id, grade_level=" . ($grade_level ?? 'null'));
        throw new Exception('No teams found for the selected criteria');
    }

    // Return success response with teams
    echo json_encode([
        'success' => true,
        'teams' => $teams,
        'count' => count($teams)
    ]);

} catch (Exception $e) {
    error_log("Error in fetch_teams.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'department_id' => $department_id ?? null,
            'game_id' => $game_id ?? null,
            'grade_level' => $grade_level ?? null
        ]
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}
