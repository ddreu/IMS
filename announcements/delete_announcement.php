<?php
session_start();
include_once '../connection/conn.php'; 
$conn = con();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['announcement_id'])) {
    $announcement_id = intval($_POST['announcement_id']);
    
    $sql = "DELETE FROM announcement WHERE id = ?";
    $stmt = $conn->prepare($sql);
    
    if ($stmt) {
        $stmt->bind_param("i", $announcement_id);
        if ($stmt->execute()) {
            $_SESSION['success_message'] = "Announcement deleted successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to delete announcement: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error_message'] = "Database error: " . $conn->error;
    }
    
    header("Location: adminannouncement.php");
    exit();
}

$_SESSION['error_message'] = "Invalid request.";
header("Location: adminannouncement.php");
exit();
?>
