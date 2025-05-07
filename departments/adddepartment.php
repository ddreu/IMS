<?php
include_once '../connection/conn.php';
include '../user_logs/logger.php';
$conn = con();

session_start();

// Check if user ID is set in the session
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error_message'] = "Error: User ID is not set in the session.";
    header("Location: departments.php");
    exit();
}

// Get the logged-in admin's user ID
$admin_user_id = $_SESSION['user_id'];

// Check if the form was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input
    $department_id = htmlspecialchars(trim($_POST['department'])); // Get the department from the form

    // Check if the selected department ID exists in the departments table
    $checkDepartmentStmt = $conn->prepare("SELECT department_name, school_id FROM departments WHERE id = ?");
    $checkDepartmentStmt->bind_param("i", $department_id);
    $checkDepartmentStmt->execute();
    $checkDepartmentStmt->bind_result($department_name, $school_id);
    $checkDepartmentStmt->fetch();
    $checkDepartmentStmt->close();

    if ($department_name === null) {
        $_SESSION['error_message'] = "Error: Department ID not found for department ID '$department_id'.";
        header("Location: departments.php");
        exit();
    }

    // Define variables to check for duplicates
    $is_duplicate = false;

    // Handle input based on department type
    if ($department_name === 'College') {
        $course_name = htmlspecialchars(trim($_POST['course_name']));

        // Check if course_name is not empty
        if (empty($course_name)) {
            $_SESSION['error_message'] = "Error: Course name cannot be empty.";
            header("Location: departments.php");
            exit();
        }

        // Check for duplicate entry in College
        $checkDuplicateStmt = $conn->prepare("SELECT 1 FROM grade_section_course WHERE course_name = ? AND department_id = ? AND is_archived = 0 LIMIT 1");
        $checkDuplicateStmt->bind_param("si", $course_name, $department_id);
        $checkDuplicateStmt->execute();
        $checkDuplicateStmt->store_result();

        if ($checkDuplicateStmt->num_rows > 0) {
            $is_duplicate = true;
            $_SESSION['error_message'] = "Error: Course '$course_name' already exists in the College department.";
        }
        $checkDuplicateStmt->close();

        // If no duplicate, proceed to insert
        if (!$is_duplicate) {
            $stmt = $conn->prepare("INSERT INTO grade_section_course (course_name, department_id) VALUES (?, ?)");
            $stmt->bind_param("si", $course_name, $department_id);
        }
    } else {
        // For other levels, we expect grade level, section name, and possibly strand for SHS
        $grade_level = htmlspecialchars(trim($_POST['grade_level']));
        $section_name = htmlspecialchars(trim($_POST['section_name']));
        $strand = ($department_name === 'SHS') ? htmlspecialchars(trim($_POST['strand'])) : null;

        // Check if grade_level and section_name are not empty
        if (empty($grade_level) || empty($section_name)) {
            $_SESSION['error_message'] = "Error: Grade level and section name cannot be empty.";
            header("Location: departments.php");
            exit();
        }

        // Check for duplicate entry for SHS, JHS, and Elementary
        $checkDuplicateStmt = $conn->prepare("
            SELECT 1
            FROM grade_section_course gsc
            WHERE gsc.grade_level = ? 
              AND gsc.section_name = ? 
              AND gsc.department_id = ? 
              AND (gsc.strand = ? OR gsc.strand IS NULL)
              AND gsc.is_archived = 0
        ");
        $checkDuplicateStmt->bind_param("ssis", $grade_level, $section_name, $department_id, $strand);
        $checkDuplicateStmt->execute();
        $checkDuplicateStmt->store_result();

        // Check if a duplicate entry was found
        if ($checkDuplicateStmt->num_rows > 0) {
            $_SESSION['error_message'] = "Error: Section or course for this grade and strand already exists.";
            header("Location: departments.php");
            exit();
        }
        $checkDuplicateStmt->close();

        // If no duplicate, proceed to insert
        if (!$is_duplicate) {
            $stmt = $conn->prepare("INSERT INTO grade_section_course (grade_level, section_name, strand, department_id) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sssi", $grade_level, $section_name, $strand, $department_id);
        }
    }

    // Proceed with team creation if no duplicate found
    if (!$is_duplicate && isset($stmt) && $stmt->execute()) {
        $grade_section_course_id = $stmt->insert_id;

        // Log the department creation action
        $log_description = "";
        if ($department_name === 'College') {
            // For College, log the course name
            $log_description = "Added College Department: Course Name = $course_name";
        } elseif ($department_name === 'SHS' || $department_name === 'JHS' || $department_name === 'Elementary') {
            // For other departments, log the grade level, section name, and strand (if applicable)
            $log_description = "Added $department_name Department: Grade Level = $grade_level, Section = $section_name";
            if ($strand) {
                $log_description .= ", Strand = $strand";
            }
        }

        // Fetch associated game IDs and names for the logged-in user's school
        $game_ids_query = "SELECT game_id, game_name FROM games WHERE school_id = ?";
        $game_ids_stmt = $conn->prepare($game_ids_query);
        $game_ids_stmt->bind_param("i", $school_id);
        $game_ids_stmt->execute();
        $game_ids_stmt->bind_result($game_id, $game_name);
        $game_ids = [];

        while ($game_ids_stmt->fetch()) {
            $game_ids[] = ['id' => $game_id, 'name' => $game_name];
        }
        $game_ids_stmt->close();

        // Create teams for all existing games
        foreach ($game_ids as $game) {
            // Create the team name based on department type
            if ($department_name === 'College') {
                $team_name = "$course_name - $game[name]";
            } elseif ($department_name === 'SHS') {
                $team_name = "$section_name - $strand - $game[name]";
            } else {
                $team_name = "$section_name - $game[name]";
            }

            $insert_team_stmt = $conn->prepare("INSERT INTO teams (team_name, game_id, grade_section_course_id) VALUES (?, ?, ?)");
            $insert_team_stmt->bind_param("sii", $team_name, $game['id'], $grade_section_course_id);

            if (!$insert_team_stmt->execute()) {
                $_SESSION['error_message'] = "Error creating team: " . htmlspecialchars($insert_team_stmt->error);
            }
            $insert_team_stmt->close();
        }

        // Update the log description to reflect team creation for all games
        $log_description .= " - Teams have been automatically created for all existing games.";

        // Log user action for department and team creation
        logUserAction($conn, $admin_user_id, "departments", "CREATE", $grade_section_course_id, $log_description);

        $_SESSION['success_message'] = "Success: Teams have been created for the section!";
        header("Location: departments.php?alert=sweetalert&selected_department_id=" . $department_id);
        exit();
    } elseif (!$is_duplicate) {
        $_SESSION['error_message'] = "Error executing insert: " . htmlspecialchars($stmt->error);
        header("Location: departments.php");
        exit();
    }

    if (isset($stmt)) {
        $stmt->close();
    }
}

$conn->close();
