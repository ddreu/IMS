<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$token = $data['token'] ?? '';

if (!$token) {
    echo json_encode(['success' => false, 'message' => 'No token provided.']);
    exit;
}

// Replace with actual scanning user logic (e.g., admin scanning)
$scannerUserId = $_SESSION['user_id'] ?? null;

if (!$scannerUserId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized scanner.']);
    exit;
}

// Update token as used
$stmt = $conn->prepare("UPDATE qr_tokens SET user_id = ?, used = 1 WHERE token = ? AND used = 0 AND expires_at > NOW()");
$stmt->bind_param("is", $scannerUserId, $token);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Token updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Token invalid or already used.']);
}
