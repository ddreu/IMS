<?php
// Database connection
include '../connection/conn.php'; // Adjust path as needed

if (!isset($_GET['schedule_id'])) {
    die("No schedule ID provided.");
}

$conn = con();
$schedule_id = $_GET['schedule_id'];

// Sanitize input (optional but recommended)
$schedule_id = intval($schedule_id);

// Check live_default_scores
$query1 = $conn->prepare("SELECT 1 FROM live_default_scores WHERE schedule_id = ?");
$query1->bind_param("i", $schedule_id);
$query1->execute();
$result1 = $query1->get_result();
if ($result1->num_rows > 0) {
    header("Location: ../scoreboard-cast/default-based-cast.php?schedule_id=$schedule_id");
    exit;
}

// Check live_scores
$query2 = $conn->prepare("SELECT 1 FROM live_scores WHERE schedule_id = ?");
$query2->bind_param("i", $schedule_id);
$query2->execute();
$result2 = $query2->get_result();
if ($result2->num_rows > 0) {
    header("Location: ../scoreboard-cast/point-based-cast.php?schedule_id=$schedule_id");
    exit;
}

// Check live_set_scores
$query3 = $conn->prepare("SELECT 1 FROM live_set_scores WHERE schedule_id = ?");
$query3->bind_param("i", $schedule_id);
$query3->execute();
$result3 = $query3->get_result();
if ($result3->num_rows > 0) {
    header("Location: ../scoreboard-cast/set-based-cast.php?schedule_id=$schedule_id");
    exit;
}

// If none found
die("No scoreboard data found for this schedule.");
