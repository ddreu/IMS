<?php
session_start();
require_once '../connection/conn.php';

header('Content-Type: application/json');

$school_id = $_SESSION['school_id'] ?? null;
if (!$school_id) {
    echo json_encode(['success' => false, 'message' => 'No school ID found']);
    exit;
}

$conn = con();
$sql = "SELECT DISTINCT YEAR(archived_at) AS year FROM archives WHERE school_id = ? ORDER BY year DESC";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $school_id);
$stmt->execute();
$result = $stmt->get_result();

$years = [];
while ($row = $result->fetch_assoc()) {
    $years[] = $row['year'];
}

echo json_encode([
    'success' => true,
    'years' => $years
]);
