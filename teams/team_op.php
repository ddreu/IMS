<?php
session_start();
include_once '../connection/conn.php';
include_once '../user_logs/logger.php';
$conn = con();

// Get the logged-in committee details
$role = $_SESSION['role'];
$game_id = $_SESSION['game_id'];
$department_id = $_SESSION['department_id'];
$school_id = $_SESSION['school_id'];
$game_name = $_SESSION['game_name'];
$department_name = $_SESSION['department_name'];

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to add a team
function addTeam($teamName, $gameId, $gradeSectionCourseId, $conn)
{
    $wins = 0;
    $losses = 0;
    $createdAt = date('Y-m-d H:i:s');

    $sql = "INSERT INTO teams (team_name, game_id, grade_section_course_id, wins, losses, created_at) 
            VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);

    if ($stmt === false) {
        return ['success' => false, 'message' => 'Error preparing the statement.'];
    }

    $stmt->bind_param("siisis", $teamName, $gameId, $gradeSectionCourseId, $wins, $losses, $createdAt);

    if ($stmt->execute()) {
        // Log user action before returning
        $description = "Registered team '$teamName'";
        logUserAction($conn, $_SESSION['user_id'], 'Team', 'CREATE', $gradeSectionCourseId, $description);

        $stmt->close(); // Close statement here after execution
        return ['success' => true, 'message' => 'Team added successfully!'];
    } else {
        $stmt->close(); // Close statement here after error
        return ['success' => false, 'message' => 'Error adding team: ' . $stmt->error];
    }
}

// Handle AJAX request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['team_name'])) {
    $teamName = $_POST['team_name'];
    $gameId = $_SESSION['game_id'];
    $gradeSectionCourseId = $_POST['grade_section_course_id'];

    $response = addTeam($teamName, $gameId, $gradeSectionCourseId, $conn);
    echo json_encode($response); // Send JSON response
    exit();
}

$conn->close();
