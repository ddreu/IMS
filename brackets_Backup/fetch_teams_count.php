<?php
require_once '../config/database.php';
header('Content-Type: application/json');

try {
    $conn = new mysqli($hostname, $username, $password, $database);
    
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Get parameters
    $gameId = isset($_POST['game_id']) ? intval($_POST['game_id']) : null;
    $departmentId = isset($_POST['department_id']) ? intval($_POST['department_id']) : null;
    $gradeLevel = isset($_POST['grade_level']) ? $_POST['grade_level'] : null;
    
    if (!$gameId || !$departmentId) {
        throw new Exception("Game ID and Department ID are required");
    }

    // Build query
    $query = "SELECT COUNT(DISTINCT t.team_id) as team_count
              FROM teams t
              JOIN grade_section_course gsc ON t.grade_section_course_id = gsc.grade_section_course_id
              WHERE t.game_id = ? AND gsc.department_id = ? AND t.team_name != 'TBD'";
    
    $params = [$gameId, $departmentId];
    $types = "ii";
    
    if ($gradeLevel) {
        $query .= " AND gsc.grade_level = ?";
        $params[] = $gradeLevel;
        $types .= "s";
    }
              
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param($types, ...$params);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'team_count' => intval($row['team_count'])
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

$conn->close();
?>
