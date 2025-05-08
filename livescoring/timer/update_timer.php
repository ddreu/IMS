<?php
include_once '../../connection/conn.php';
$conn = con();

$data = json_decode(file_get_contents("php://input"));

$schedule_id = $data->schedule_id ?? null;
$time_remaining = $data->time_remaining ?? null;
$period = $data->period ?? null;
$timer_status = $data->timer_status ?? null;

if ($schedule_id && $time_remaining !== null && $period !== null && $timer_status !== null) {
    $stmt = $conn->prepare("UPDATE live_scores SET time_remaining = ?, period = ?, timer_status = ?, last_timer_update = CURRENT_TIMESTAMP WHERE schedule_id = ?");
    $stmt->bind_param("issi", $time_remaining, $period, $timer_status, $schedule_id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true]);
    } else {
        echo json_encode(["success" => false, "error" => $stmt->error]);
    }

    $stmt->close();
} else {
    echo json_encode(["success" => false, "error" => "Invalid parameters"]);
}

$conn->close();
