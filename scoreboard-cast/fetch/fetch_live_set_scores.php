<?php
include_once '../../connection/conn.php';
$conn = con();

$schedule_id = $_GET['schedule_id'] ?? null;
if (!$schedule_id) {
    echo json_encode(['error' => 'Missing schedule_id']);
    exit;
}

$stmt = $conn->prepare("SELECT 
    lss.teamA_score, 
    lss.teamB_score, 
    lss.teamA_sets_won, 
    lss.teamB_sets_won, 
    lss.current_set, 
    lss.timeout_teamA, 
    lss.timeout_teamB, 
    tA.team_name AS teamA_name, 
    tB.team_name AS teamB_name
FROM live_set_scores lss
JOIN teams tA ON lss.teamA_id = tA.team_id
JOIN teams tB ON lss.teamB_id = tB.team_id
WHERE lss.schedule_id = ?");
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo json_encode($result);
