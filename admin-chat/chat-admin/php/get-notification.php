<?php

include 'connection.php';

if (isset($_GET['user_id'])) {
    $session = $_GET['user_id'];

    $query = "SELECT  incoming_msg_id, notification FROM tbl_messages WHERE incoming_msg_id='$session' AND notification=0";
    $query_run = mysqli_query($conn, $query);
    $count = mysqli_num_rows($query_run);
    if ($count > 0) {
        $conn->query("UPDATE tbl_messages SET notification = 1 WHERE incoming_msg_id='$session'");
        $res = [
            'status' => 400,
            'message' => 'You Have ' . $count . ' Message/s'
        ];
        echo json_encode($res);
        return;
    } else {
        $res = [
            'status' => 500
        ];
        echo json_encode($res);
        return;
    }
}
