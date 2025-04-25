<?php

require_once '../../connection/conn.php';
$conn = con();

if (isset($_GET['school_id']) && !empty($_GET['school_id'])) {
    $school_id = $_GET['school_id'];
} else {
    if (!isset($_SESSION['school_id'])) {
        die("<option value=''>School ID not set</option>");
    }
    $school_id = $_SESSION['school_id'];
}

if (!$conn) {
    die("<option value=''>Database connection failed</option>");
}

$query = "SELECT game_id, game_name FROM games WHERE school_id = ?";
$stmt = $conn->prepare($query);

if (!$stmt) {
    die("<option value=''>Failed to prepare statement</option>");
}

$stmt->bind_param('i', $school_id);

if (!$stmt->execute()) {
    die("<option value=''>Failed to execute statement</option>");
}

$result = $stmt->get_result();

$options = "<option value=''>Select Game</option>"; // Default option

while ($row = $result->fetch_assoc()) {
    $options .= "<option value='{$row['game_id']}'>{$row['game_name']}</option>";
}

echo $options;
