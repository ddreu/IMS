<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();
include_once '../connection/conn.php';
$conn = con();

// Set the header to indicate JSON response
header('Content-Type: application/json');

// Check if the session is valid
if (!isset($_SESSION['school_id'])) {
    echo json_encode(["error" => "Missing school_id in session"]);
    exit();
}

$school_id = $_SESSION['school_id'];

// Check if both department_id and game_id are provided
if (isset($_GET['department_id']) && isset($_GET['game_id'])) {
    $department_id = $_GET['department_id'];
    $game_id = $_GET['game_id'];

    // Prepare query to fetch teams
    $query = "
    SELECT t.team_id, t.team_name 
    FROM teams t
    INNER JOIN grade_section_course gsc ON t.grade_section_course_id = gsc.id
    INNER JOIN departments d ON gsc.department_id = d.id 
    INNER JOIN schools s ON d.school_id = s.school_id
    WHERE gsc.department_id = ? AND t.game_id = ? AND s.school_id = ?
";


    $stmt = $conn->prepare($query);
    $stmt->bind_param("iii", $department_id, $game_id, $school_id);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch the teams
    $teams = [];
    while ($row = $result->fetch_assoc()) {
        $teams[] = $row;
    }

    // If no teams are found, return an empty array
    if (empty($teams)) {
        error_log("No teams found for department_id=$department_id, game_id=$game_id, school_id=$school_id");
    } else {
        // Return teams as JSON
        echo json_encode($teams);
    }

    $stmt->close();
} else {
    // Return error if parameters are missing
    echo json_encode(["error" => "Invalid or missing department_id or game_id"]);
}

$conn->close();
exit();
