<?php
session_start();
require_once '../connection/conn.php';

header('Content-Type: application/json');

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['Committee', 'Department Admin', 'School Admin'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Get JSON data
$data = json_decode(file_get_contents('php://input'), true);

if (!$data || !isset($data['schedule_id']) || !isset($data['stream_url']) || !isset($data['stream_type'])) {
    echo json_encode(['success' => false, 'message' => 'Missing required data']);
    exit;
}

try {
    $pdo = con(); // Initialize connection using conn()

    // Use INSERT ... ON DUPLICATE KEY UPDATE to handle both insert and update
    $stmt = $pdo->prepare("
        INSERT INTO stream_urls (schedule_id, stream_url, stream_type, created_at, updated_at)
        VALUES (?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE 
            stream_url = VALUES(stream_url),
            stream_type = VALUES(stream_type),
            updated_at = NOW()
    ");

    $success = $stmt->execute([$data['schedule_id'], $data['stream_url'], $data['stream_type']]);

    if ($success) {
        echo json_encode(['success' => true, 'message' => 'Stream URL saved successfully']);
    } else {
        throw new Exception('Failed to save stream URL');
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
