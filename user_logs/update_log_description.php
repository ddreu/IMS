<?php
include_once '../connection/conn.php';
$conn = con();

$data = json_decode(file_get_contents("php://input"), true);
$log_id = $data['log_id'] ?? null;
$description = $data['description'] ?? '';

if (!$log_id || trim($description) === '') {
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields.']);
    exit;
}

$stmt = $conn->prepare("UPDATE logs SET description = ? WHERE log_id = ?");
$stmt->bind_param("si", $description, $log_id);

if ($stmt->execute()) {
    echo json_encode(['status' => 'success']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Update failed.']);
}
$stmt->close();
