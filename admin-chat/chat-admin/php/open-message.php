<?php
session_start();
include 'connection.php';

if (isset($_SESSION['user_id'])) {
    //admin / staff session
    $session = $_SESSION['user_id'];
    //end user id
    $id = $_GET['userid'];
    $query = "SELECT incoming_msg_id, open, outgoing_msg_id FROM tbl_messages WHERE  outgoing_msg_id='$id' AND open = 0 AND incoming_msg_id='$session'";
    $query_run = mysqli_query($conn, $query);
    if (mysqli_num_rows($query_run)) {
        $open = $conn->query("UPDATE tbl_messages SET open=1 WHERE outgoing_msg_id='$id'AND incoming_msg_id='$session'");
        if ($open) {
            $res = ['status' => 100];
            echo json_encode($res);
            return;
        }
    } else {
        $res = ['status' => 0];
        echo json_encode($res);
        return;
    }
}
