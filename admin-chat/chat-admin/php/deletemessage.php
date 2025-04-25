<?php

// include 'conn.php';
include 'connection.php';

if (isset($_GET['session'])) {
    $session = $_GET['session'];
    $query = "DELETE A.*, B.* FROM tbl_end_users AS A LEFT JOIN tbl_messages AS B ON (A.unique_id=B.incoming_msg_id AND B.outgoing_msg_id='$session') OR (B.incoming_msg_id='$session' AND A.unique_id=B.outgoing_msg_id) WHERE A.user_reciever='$session'";

    $query_run = mysqli_query($conn, $query);

    if ($query_run) {
        echo json_encode(array("status" => "success"));
    } else {
        echo json_encode(array("status" => "error"));
    }
}
