<?php
include_once '../connection/conn.php';
$conn = con();

// Get JSON data from POST request
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($data['schedule_id']) || !isset($data['match_id'])) {
    http_response_code(400);
    echo json_encode([
        'success' => false, 
        'error' => 'Missing schedule_id or match_id'
    ]);
    exit;
}

$schedule_id = $data['schedule_id'];
$match_id = $data['match_id'];

try {
    // Fetch sets won from live_set_scores
    $query = $conn->prepare("
        SELECT teamA_sets_won, teamB_sets_won 
        FROM live_set_scores 
        WHERE schedule_id = ?
    ");
    $query->bind_param("i", $schedule_id);
    $query->execute();
    $result = $query->get_result()->fetch_assoc();

    if ($result) {
        echo json_encode([
            'success' => true,
            'teamA_sets_won' => $result['teamA_sets_won'],
            'teamB_sets_won' => $result['teamB_sets_won']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'No sets won data found'
        ]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error: ' . $e->getMessage()
    ]);
}

$conn->close();
?>