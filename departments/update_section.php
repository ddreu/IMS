<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Get JSON data from the request
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid data received']);
    exit();
}

// Extract data
$grade_section_course_id = $data['id'];
$department = $data['department'];

try {
    // Start transaction
    $conn->begin_transaction();

    // First get the current data and department_id
    $get_current = $conn->prepare("SELECT department_id, grade_level, section_name, course_name, strand FROM grade_section_course WHERE id = ?");
    $get_current->bind_param("i", $grade_section_course_id);
    $get_current->execute();
    $result = $get_current->get_result();
    $current_data = $result->fetch_assoc();
    
    if (!$current_data) {
        throw new Exception("Record not found");
    }

    $department_id = $current_data['department_id'];

    // Update grade_section_course table
    if ($department === 'College') {
        $course_name = $data['course_name'];
        
        // Check for duplicate course name
        $check_stmt = $conn->prepare("SELECT id FROM grade_section_course WHERE course_name = ? AND id != ? AND department_id = ?");
        $check_stmt->bind_param("sis", $course_name, $grade_section_course_id, $department_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            throw new Exception("A course with this name already exists.");
        }
        
        $stmt = $conn->prepare("UPDATE grade_section_course SET course_name = ? WHERE id = ?");
        $stmt->bind_param("si", $course_name, $grade_section_course_id);
    } else {
        $section_name = $data['section_name'];
        $grade_level = $data['grade_level'];
        $strand = isset($data['strand']) ? $data['strand'] : null;
        
        // Check for duplicate section
        $check_stmt = $conn->prepare("SELECT id FROM grade_section_course WHERE grade_level = ? AND section_name = ? AND id != ? AND department_id = ? AND (strand = ? OR (strand IS NULL AND ? IS NULL))");
        $check_stmt->bind_param("ssiiss", $grade_level, $section_name, $grade_section_course_id, $department_id, $strand, $strand);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            throw new Exception("A section with this name and grade level already exists.");
        }
        
        if ($department === 'SHS') {
            $stmt = $conn->prepare("UPDATE grade_section_course SET section_name = ?, strand = ? WHERE id = ?");
            $stmt->bind_param("ssi", $section_name, $strand, $grade_section_course_id);
        } else {
            $stmt = $conn->prepare("UPDATE grade_section_course SET section_name = ? WHERE id = ?");
            $stmt->bind_param("si", $section_name, $grade_section_course_id);
        }
    }

    if (!$stmt->execute()) {
        throw new Exception("Failed to update section/course details.");
    }

    // Get games for the school
    $get_games = $conn->prepare("SELECT game_id, game_name FROM games WHERE school_id = (SELECT school_id FROM departments WHERE id = ?)");
    $get_games->bind_param("i", $department_id);
    $get_games->execute();
    $games_result = $get_games->get_result();
    $games = [];
    while ($game = $games_result->fetch_assoc()) {
        $games[$game['game_id']] = $game['game_name'];
    }

    // Update team names
    $get_teams = $conn->prepare("SELECT team_id, game_id FROM teams WHERE grade_section_course_id = ?");
    $get_teams->bind_param("i", $grade_section_course_id);
    $get_teams->execute();
    $teams_result = $get_teams->get_result();

    while ($team = $teams_result->fetch_assoc()) {
        $game_name = $games[$team['game_id']];
        
        // Create the team name based on department type
        if ($department === 'College') {
            $new_team_name = "$course_name - $game_name";
        } elseif ($department === 'SHS') {
            $new_team_name = "$section_name - $strand - $game_name";
        } else {
            $new_team_name = "$section_name - $game_name";
        }

        $update_team = $conn->prepare("UPDATE teams SET team_name = ? WHERE team_id = ?");
        $update_team->bind_param("si", $new_team_name, $team['team_id']);
        if (!$update_team->execute()) {
            throw new Exception("Failed to update team name.");
        }
    }

    // Commit transaction
    $conn->commit();
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

// Close connection
$conn->close();
