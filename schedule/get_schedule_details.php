<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1); // Enable error display
header('Content-Type: application/json'); // Set header to JSON

include_once '../connection/conn.php'; 
$conn = con();
// Log the raw input data
$postData = json_decode(file_get_contents('php://input'), true);
error_log(print_r($postData, true)); // Log incoming JSON data


if (!$conn) {
    error_log("Database connection failed: " . mysqli_connect_error());
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated.']);
    exit();
}

// Get the raw POST data
$postData = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($postData['schedule_id'])) {
    echo json_encode(['success' => false, 'message' => 'Schedule ID is required.']);
    exit();
}

$schedule_id = intval($postData['schedule_id']);

// Fetch schedule details
$stmt = $conn->prepare("SELECT s.schedule_id, s.match_id, s.schedule_date, s.schedule_time, s.venue,
                              m.status as match_status, m.match_type,
                              t1.team_name AS team1_name, t2.team_name AS team2_name,
                              g.game_name, d.department_name
                       FROM schedules s
                       INNER JOIN matches m ON s.match_id = m.match_id
                       INNER JOIN teams t1 ON m.teamA_id = t1.team_id
                       INNER JOIN teams t2 ON m.teamB_id = t2.team_id
                       INNER JOIN brackets b ON m.bracket_id = b.bracket_id
                       INNER JOIN games g ON b.game_id = g.game_id
                       INNER JOIN departments d ON b.department_id = d.id
                       WHERE s.schedule_id = ?");
if (!$stmt) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
    exit();
}
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Schedule not found.']);
    exit();
}

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'Schedule not found.']);
    exit();
} else {
    error_log("Schedule details retrieved successfully for schedule ID: $schedule_id");
}

$schedule = $result->fetch_assoc();
$stmt->close();

// Return the schedule details
echo json_encode(['success' => true, 'schedule' => $schedule]);
exit();
?>
