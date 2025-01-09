<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['school_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'School ID is required']);
    exit();
}

$school_id = intval($_GET['school_id']);

$query = "SELECT game_id, game_name FROM games WHERE school_id = ? ORDER BY game_name";
$stmt = mysqli_prepare($conn, $query);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $school_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $games = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $games[] = [
            'game_id' => $row['game_id'],
            'game_name' => $row['game_name']
        ];
    }
    
    echo json_encode($games);
    mysqli_stmt_close($stmt);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
}

mysqli_close($conn);
?>
