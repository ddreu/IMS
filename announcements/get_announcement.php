<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['error' => 'No announcement ID provided']);
    exit();
}

$id = intval($_GET['id']);

// Fetch announcement with department information
$sql = "SELECT a.*, d.department_name 
        FROM announcement a 
        LEFT JOIN departments d ON a.department_id = d.id 
        WHERE a.id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    // Format the response
    $response = [
        'id' => $row['id'],
        'title' => $row['title'],
        'message' => $row['message'],
        'image' => $row['image'],
        'department_id' => $row['department_id'],
        'department_name' => $row['department_name']
    ];
    echo json_encode($response);
} else {
    echo json_encode(['error' => 'Announcement not found']);
}

$stmt->close();
$conn->close();
?>
