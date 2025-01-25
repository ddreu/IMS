<?php
session_start();
include_once '../connection/conn.php';
$conn = con();
include "../user_logs/logger.php"; // Include the logger at the top

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['announcement_id'])) {
    $announcement_id = intval($_POST['announcement_id']);

    // Step 1: Fetch the title of the announcement that is about to be deleted
    $sql_select = "SELECT title FROM announcement WHERE id = ?";
    $stmt_select = $conn->prepare($sql_select);

    if ($stmt_select) {
        $stmt_select->bind_param("i", $announcement_id);
        $stmt_select->execute();
        $stmt_select->bind_result($title); // Bind the result to the $title variable
        $stmt_select->fetch(); // Fetch the result (the title of the announcement)
        $stmt_select->close();
    } else {
        $_SESSION['error_message'] = "Failed to fetch announcement title: " . $conn->error;
        header("Location: adminannouncement.php");
        exit();
    }

    // Step 2: Proceed to delete the announcement
    $sql_delete = "DELETE FROM announcement WHERE id = ?";
    $stmt_delete = $conn->prepare($sql_delete);

    if ($stmt_delete) {
        $stmt_delete->bind_param("i", $announcement_id);
        if ($stmt_delete->execute()) {
            $_SESSION['success_message'] = "Announcement deleted successfully!";

            // Log the action (Step 3)
            $operation = 'DELETE'; // Operation type


            // Log the action
            logUserAction(
                $conn,
                $_SESSION['user_id'],                            // ID of the user performing the action
                'announcements',                                 // Table name
                $operation,                                      // Operation type
                $announcement_id,                                // Record ID (ID of the deleted announcement)
                'Deleted Announcement titled "' . $title . '"'
            );
        } else {
            $_SESSION['error_message'] = "Failed to delete announcement: " . $stmt_delete->error;
        }
        $stmt_delete->close();
    } else {
        $_SESSION['error_message'] = "Database error: " . $conn->error;
    }

    // Redirect after successful deletion
    header("Location: adminannouncement.php");
    exit();
}

$_SESSION['error_message'] = "Invalid request.";
header("Location: adminannouncement.php");
exit();
