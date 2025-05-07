<?php
session_start();
require_once '../../connection/conn.php';
$conn = con();

$school_id = $_GET['school_id'] ?? ($_SESSION['school_id'] ?? null);
$year = $_GET['year'] ?? null;

if (!$school_id || !$year) {
    die("<option value=''>Missing school ID or year</option>");
}

$query = "SELECT game_id, game_name FROM games WHERE school_id = ? AND is_archived = 1 AND YEAR(archived_at) = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('ii', $school_id, $year);

if (!$stmt->execute()) {
    die("<option value=''>Query failed</option>");
}

$result = $stmt->get_result();
$options = '';

while ($row = $result->fetch_assoc()) {
    $options .= "<option value='{$row['game_id']}'>{$row['game_name']}</option>";
}

echo $options;
