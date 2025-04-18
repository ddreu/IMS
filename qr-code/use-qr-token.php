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

// Check token existence and status
$stmt = $conn->prepare("SELECT used, expires_at FROM qr_tokens WHERE token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!$row) {
    echo json_encode(['success' => false, 'message' => 'Token not found.']);
    exit;
} elseif ($row['used']) {
    echo json_encode(['success' => false, 'message' => 'Token already used.']);
    exit;
} elseif (strtotime($row['expires_at']) <= time()) {
    echo json_encode(['success' => false, 'message' => 'Token expired.']);
    exit;
}

// Safe to update token
$stmt = $conn->prepare("UPDATE qr_tokens SET user_id = ?, used = 1 WHERE token = ?");
$stmt->bind_param("is", $scannerUserId, $token);
$stmt->execute();

if ($stmt->affected_rows > 0) {
    echo json_encode(['success' => true, 'message' => 'Token updated successfully.']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to update token.']);
}
