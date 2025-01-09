<?php
session_start();
include_once '../connection/conn.php'; 
$conn = con();

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    
    $sql = "SELECT title, message, image FROM announcement WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);

    if ($stmt) {
        mysqli_stmt_bind_param($stmt, "i", $id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if ($row = mysqli_fetch_assoc($result)) {
            echo json_encode([
                'title' => $row['title'],
                'message' => $row['message'],
                'image_url' => $row['image'] // Ensure this matches your image column
            ]);
        } else {
            echo json_encode([]);
        }
        mysqli_stmt_close($stmt);
    } else {
        echo json_encode([]);
    }
} else {
    echo json_encode([]);
}
?>
