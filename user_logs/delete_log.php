<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

$data = json_decode(file_get_contents("php://input"), true);
$logId = $data['log_id'];

try {
    $stmt = $conn->prepare("DELETE FROM logs WHERE log_id = ?");
    $stmt->bind_param("i", $logId);
    $stmt->execute();

    echo json_encode(['status' => 'success']);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
