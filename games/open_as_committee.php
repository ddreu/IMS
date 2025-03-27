<?php
session_start();
require_once '../connection/conn.php';
$conn = con();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $game_id = intval($_POST['game_id']);

    if ($game_id) {
        // Fetch game details
        $stmt = $conn->prepare("SELECT * FROM games WHERE game_id = ?");
        $stmt->bind_param("i", $game_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($row = $result->fetch_assoc()) {
            // Set session data for committee view
            $_SESSION['game_id'] = $row['game_id'];
            $_SESSION['game_name'] = $row['game_name'];
            $_SESSION['success_message'] = "Welcome to " . $row['game_name'] . " Committee Dashboard!";
            $_SESSION['success_type'] = 'Committee';

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
