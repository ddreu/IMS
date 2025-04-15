<?php
include_once '../connection/conn.php';
$conn = con();
$data = json_decode(file_get_contents("php://input"), true);

$logId = $data['log_id'];
$userId = $data['user_id'];

try {
    $stmt = $conn->prepare("UPDATE logs SET user_id = ? WHERE log_id = ?");
    $stmt->execute([$userId, $logId]);

    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
