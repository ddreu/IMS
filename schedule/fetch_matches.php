<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../connection/conn.php';
$conn = con();
header('Content-Type: application/json');

// Get department_id and game_id from request
$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : null;
$game_id = isset($_GET['game_id']) ? (int)$_GET['game_id'] : null;
$grade_level = isset($_GET['grade_level']) ? $_GET['grade_level'] : null;

if (!$department_id || !$game_id) {
    echo json_encode(['error' => 'Missing required parameters']);
    exit;
}

try {
    // Debug queries to check data at each step
    $debug_data = [];

    // 1. Check brackets for the department and game
    $bracket_query = "SELECT bracket_id, department_id, game_id, grade_level FROM brackets WHERE department_id = ? AND game_id = ?";
    $params = [$department_id, $game_id];
    $types = "ii";
    
    if ($grade_level) {
        $bracket_query .= " AND grade_level = ?";
        $params[] = $grade_level;
        $types .= "s";
    }
    
    $stmt = $conn->prepare($bracket_query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $bracket_result = $stmt->get_result();
    
    $debug_data['brackets_found'] = $bracket_result->num_rows;
    $brackets = [];
    while($row = $bracket_result->fetch_assoc()) {
        $brackets[] = $row;
    }
    $debug_data['brackets'] = $brackets;
    $debug_data['input_params'] = [
        'department_id' => $department_id,
        'game_id' => $game_id,
        'grade_level' => $grade_level
    ];

    // Main query
    $query = "
        SELECT 
            m.match_id,
            m.teamA_id,
            m.teamB_id,
            m.round,
            m.match_number,
            m.match_type,
            m.status,
            b.bracket_id,
            b.grade_level,
            ta.team_name as team1_name,
            tb.team_name as team2_name
        FROM matches m
        INNER JOIN brackets b ON m.bracket_id = b.bracket_id
        LEFT JOIN teams ta ON m.teamA_id = ta.team_id
        LEFT JOIN teams tb ON m.teamB_id = tb.team_id
        WHERE b.department_id = ?
        AND b.game_id = ?
        AND m.status = 'pending'
        AND m.match_id NOT IN (
            SELECT match_id 
            FROM schedules
        )";

    $params = [$department_id, $game_id];
    $types = "ii";
    
    if ($grade_level) {
        $query .= " AND b.grade_level = ?";
        $params[] = $grade_level;
        $types .= "s";
    }

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $debug_data['matches_found'] = $result->num_rows;

    $matches = [];
    while ($row = $result->fetch_assoc()) {
        $display = "{$row['team1_name']} vs {$row['team2_name']}";
        if ($row['grade_level']) {
            $display .= " ({$row['grade_level']})";
        }
        
        $matches[] = [
            'match_id' => $row['match_id'],
            'team1_id' => $row['teamA_id'],
            'team2_id' => $row['teamB_id'],
            'match_type' => $row['match_type'],
            'display' => $display
        ];
    }

    echo json_encode([
        'matches' => $matches,
        'debug' => $debug_data
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage(),
        'debug' => $debug_data
    ]);
}
?>
