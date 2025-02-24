<?php
require_once '../../connection/conn.php';
session_start();
header('Content-Type: application/json');

try {
    $data = json_decode(file_get_contents('php://input'), true);

    if (!isset($data['schedule_id'])) {
        throw new Exception("Schedule ID is not set.");
    }

    $conn = con();

    // Get bracket type from schedule_id
    $query = "SELECT b.bracket_type 
              FROM schedules s
              JOIN matches m ON s.match_id = m.match_id
              JOIN brackets b ON m.bracket_id = b.bracket_id
              WHERE s.schedule_id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $data['schedule_id']);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if (!$result) {
        throw new Exception("Could not find bracket type for this schedule");
    }

    echo json_encode([
        'success' => true,
        'bracket_type' => $result['bracket_type']
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}
