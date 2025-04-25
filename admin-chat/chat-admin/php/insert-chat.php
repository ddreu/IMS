<?php
session_start();
if (isset($_SESSION['user_id'])) {
    include_once "connection.php";
    $outgoing_id = $_SESSION['user_id'];
    $incoming_id = mysqli_real_escape_string($conn, $_POST['incoming_id']);
    $message = mysqli_real_escape_string($conn, $_POST['message']);
    if (!empty($message)) {
        $sql = mysqli_query($conn, "INSERT INTO tbl_messages (incoming_msg_id, outgoing_msg_id, msg, dateentry)
                                        VALUES ({$incoming_id}, {$outgoing_id}, '{$message}', '{$dateentry}')") or die();
    }
} else {
    header("location: ../index.php");
}
