<?php
include_once '../connection/conn.php';
$conn = con();
$data = json_decode(file_get_contents("php://input"), true);

$logId = $data['log_id'];
$timestamp = $data['timestamp']; // format: 2025-04-14T10:30

try {
    // Convert from local (Asia/Manila) to UTC for consistent DB storage
    $manila = new DateTimeZone('Asia/Manila');
    $utc = new DateTimeZone('UTC');

    $date = new DateTime($timestamp, $manila);
    $date->setTimezone($utc);
    $utcFormatted = $date->format('Y-m-d H:i:s'); // MySQL DATETIME format

    $stmt = $conn->prepare("UPDATE logs SET timestamp = ? WHERE log_id = ?");
    $stmt->execute([$utcFormatted, $logId]);

    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
