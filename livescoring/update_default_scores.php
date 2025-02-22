<?php
include_once '../connection/conn.php';
$conn = con();
session_start();

// Detect content type
$content_type = $_SERVER["CONTENT_TYPE"] ?? '';

// Check if request is JSON or Form Data
if (strpos($content_type, "application/json") !== false) {
    // Get JSON data from POST request
    $data = json_decode(file_get_contents('php://input'), true);
} else {
    // Get Form Data if JSON is not sent
    $data = $_POST;
}

// Validate received data
if (!$data || !isset($data['schedule_id']) || !isset($data['game_id']) || !isset($data['teamA_id']) || !isset($data['teamB_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid data received or missing required fields']);
    exit;
}

// Extract data
$schedule_id = $data['schedule_id'];
$game_id = $data['game_id'];
$teamA_id = $data['teamA_id'];
$teamB_id = $data['teamB_id'];
$teamA_score = $data['teamA_score'] ?? 0;
$teamB_score = $data['teamB_score'] ?? 0;
$timestamp = date('Y-m-d H:i:s');

try {
    // Check if a record exists
    $check_stmt = $conn->prepare("SELECT live_default_score_id FROM live_default_scores WHERE schedule_id = ?");
    $check_stmt->bind_param("i", $schedule_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing record
        $stmt = $conn->prepare("UPDATE live_default_scores 
                                SET teamA_score = ?, teamB_score = ?, timestamp = ? 
                                WHERE schedule_id = ?");
        $stmt->bind_param("iisi", $teamA_score, $teamB_score, $timestamp, $schedule_id);
    } else {
        // Insert new record
        $stmt = $conn->prepare("INSERT INTO live_default_scores 
                                (schedule_id, game_id, teamA_id, teamB_id, teamA_score, teamB_score, timestamp) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iiiiiis", $schedule_id, $game_id, $teamA_id, $teamB_id, $teamA_score, $teamB_score, $timestamp);
    }

    if (!$stmt->execute()) {
        throw new Exception($stmt->error);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
} finally {
    if (isset($check_stmt)) $check_stmt->close();
    if (isset($stmt)) $stmt->close();
    if (isset($conn)) $conn->close();
}
