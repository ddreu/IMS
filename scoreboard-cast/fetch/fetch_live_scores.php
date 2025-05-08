<?php
include_once '../../connection/conn.php';
$conn = con();

$schedule_id = $_GET['schedule_id'] ?? null;
if (!$schedule_id) {
    echo json_encode(['error' => 'Missing schedule_id']);
    exit;
}

// Add all relevant fields you want to sync
$stmt = $conn->prepare("SELECT 
    ls.teamA_score, 
    ls.teamB_score, 
    ls.period as current_period, 
    ls.time_remaining, 
    ls.foul_teamA as teamAFouls,  -- Corrected field alias
    ls.foul_teamB as teamBFouls,  -- Corrected field alias
    ls.timeout_teamA as teamATimeouts,  -- Corrected field alias
    ls.timeout_teamB as teamBTimeouts,  -- Corrected field alias
    tA.team_name AS teamA_name,
    tB.team_name AS teamB_name
FROM live_scores ls
JOIN teams tA ON ls.teamA_id = tA.team_id
JOIN teams tB ON ls.teamB_id = tB.team_id
WHERE ls.schedule_id = ?");
$stmt->bind_param("i", $schedule_id);
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo json_encode($result);
