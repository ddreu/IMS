<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

// Check if 'school_id' exists in the URL query parameters
if (isset($_GET['school_id']) && !empty($_GET['school_id'])) {
    $school_id = $_GET['school_id']; // Use the school_id from the URL
} else {
    if (!isset($_SESSION['school_id'])) {
        die(json_encode(['error' => 'School ID not set in session or URL']));
    }
    $school_id = $_SESSION['school_id']; // Fall back to the school_id in session
}

// Debugging: Display the school_id
// echo "School ID being used: " . $school_id; exit;

// Check database connection
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

$query = "SELECT game_id, game_name FROM games WHERE school_id = ? AND is_archived = 0";
$stmt = $conn->prepare($query);

if ($stmt === false) {
    die("Failed to prepare statement: " . $conn->error);
}

$stmt->bind_param('i', $school_id);

if (!$stmt->execute()) {
    die("Failed to execute statement: " . $stmt->error);
}

$result = $stmt->get_result();
if ($result === false) {
    die("Failed to fetch result: " . $stmt->error);
}

$games = [];
while ($row = $result->fetch_assoc()) {
    $games[] = $row;
}

echo json_encode($games);
