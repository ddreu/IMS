<?php
session_start();
require_once '../connection/conn.php';
$conn = con();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $game_id = intval($_POST['game_id']);
    $department_id = isset($_POST['department_id']) ? intval($_POST['department_id']) : null;
    $department_name = isset($_POST['department_name']) ? trim($_POST['department_name']) : null;

    if ($game_id) {
        $stmt = $conn->prepare("SELECT * FROM games WHERE game_id = ?");
        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            $_SESSION['game_id'] = $row['game_id'];
            $_SESSION['game_name'] = $row['game_name'];

            if ($department_id && $department_name) {
                $_SESSION['department_id'] = $department_id;
                $_SESSION['department_name'] = $department_name;
            }

            echo json_encode(['success' => true]);
            exit;
        } else {
            echo json_encode(['success' => false, 'message' => 'Game not found.']);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid game ID.']);
        exit;
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}
