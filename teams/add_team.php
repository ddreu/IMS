<?php
session_start();
include_once '../connection/conn.php';
include_once '../user_logs/logger.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit();
}

$conn = con();

try {
    $grade_section_course_id = isset($_POST['grade_section_course_id']) ? intval($_POST['grade_section_course_id']) : null;
    $team_name = isset($_POST['team_name']) ? trim($_POST['team_name']) : '';

    if (!$grade_section_course_id || empty($team_name)) {
        throw new Exception('Missing required fields');
    }

    // Check if team name already exists for this section
    $check_sql = "SELECT COUNT(*) as count FROM teams WHERE grade_section_course_id = ? AND team_name = ?";
    $check_stmt = $conn->prepare($check_sql);
    if (!$check_stmt) {
        throw new Exception("Failed to prepare check statement: " . $conn->error);
    }
    $check_stmt->bind_param("is", $grade_section_course_id, $team_name);
    if (!$check_stmt->execute()) {
        throw new Exception("Failed to execute check statement: " . $check_stmt->error);
    }
    $result = $check_stmt->get_result()->fetch_assoc();

    if ($result['count'] > 0) {
        throw new Exception('A team with this name already exists in this section');
    }

    // Insert new team
    $sql = "INSERT INTO teams (team_name, grade_section_course_id) VALUES (?, ?)";
    $stmt = $conn->prepare($sql);
    if (!$stmt) {
        throw new Exception("Failed to prepare insert statement: " . $conn->error);
    }
    $stmt->bind_param("si", $team_name, $grade_section_course_id);

    // Log user action
    $description = "Registered team '$team_name'";
    logUserAction($conn, $_SESSION['user_id'], 'Team', 'CREATE', $grade_section_course_id, $description);

    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => 'Team added successfully!',
            'team_id' => $stmt->insert_id
        ]);
    } else {
        throw new Exception('Failed to add team');
    }
} catch (Exception $e) {
    error_log("Error in add_team.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
