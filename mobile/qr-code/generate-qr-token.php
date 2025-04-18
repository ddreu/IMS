<?php
include_once '../../connection/conn.php';
$conn = con();

header('Content-Type: application/json');
date_default_timezone_set('Asia/Manila');

// Read fingerprint from request
$input = json_decode(file_get_contents('php://input'), true);
$fingerprint = $input['fingerprint'] ?? null;

// Validate fingerprint
if (!$fingerprint) {
    echo json_encode(['success' => false, 'message' => 'Fingerprint is required.']);
    exit;
}

// Delete expired or previous tokens from same fingerprint
$conn->query("DELETE FROM qr_tokens WHERE expires_at < NOW() OR fingerprint = '" . $conn->real_escape_string($fingerprint) . "'");

// Generate secure token
$token = bin2hex(random_bytes(32));
$expires_at = date('Y-m-d H:i:s', strtotime('+5 minutes'));
$user_id = null;

$stmt = $conn->prepare("INSERT INTO qr_tokens (user_id, token, expires_at, fingerprint) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $user_id, $token, $expires_at, $fingerprint);

if ($stmt->execute()) {
    echo json_encode(['success' => true, 'token' => $token]);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to generate token.']);
}

$stmt->close();
$conn->close();
