<?php
include_once '../connection/conn.php';
header('Content-Type: application/json');

try {
    $conn = con();
    
    if (!isset($_GET['schedule_id'])) {
        throw new Exception('Schedule ID is required');
    }
    
    $schedule_id = $_GET['schedule_id'];
    
    // Check if the match still exists in live_scores
    $check_query = "SELECT COUNT(*) as count FROM live_scores WHERE schedule_id = ?";
    $stmt = $conn->prepare($check_query);
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    // Return true if the match is deleted (count = 0)
    echo json_encode([
        'deleted' => ($row['count'] == 0)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}
