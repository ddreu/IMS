<?php
include_once '../../connection/conn.php';
$conn = con();

$schedule_id = $_GET['schedule_id'] ?? null;
if (!$schedule_id) {
    echo json_encode(['error' => 'Missing schedule_id']);
    exit;
}

$stmt = $conn->prepare("SELECT 
    lds.teamA_score, 
    lds.teamB_score, 
    tA.team_name AS teamA_name, 
    tB.team_name AS teamB_name
FROM live_default_scores lds
JOIN teams tA ON lds.teamA_id = tA.team_id
JOIN teams tB ON lds.teamB_id = tB.team_id
WHERE lds.schedule_id = ?");
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo json_encode($result);
