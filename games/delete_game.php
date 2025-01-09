<?php
session_start();
include_once '../connection/conn.php'; 
$conn = con();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['game_id'])) {
    $game_id = intval($_POST['game_id']);
    
    $sql = "DELETE FROM games WHERE game_id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $game_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Game deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to delete game: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Database error: " . $conn->error;
    }
    
    header("Location: games.php");
    exit();
}

$_SESSION['error_message'] = "Invalid request.";
header("Location: games.php");
exit();
?>
