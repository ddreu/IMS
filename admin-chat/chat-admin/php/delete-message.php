<?php
session_start();
include 'connection.php';

if(isset($_GET['del'])){
    $id = mysqli_real_escape_string($conn, $_GET['del']);
    $session = $_SESSION['login_id'];
    $query = "DELETE A.*, B.* FROM tbl_end_users AS A LEFT JOIN tbl_messages AS B ON (A.unique_id=B.incoming_msg_id AND B.outgoing_msg_id='$session') OR (B.incoming_msg_id='$session' AND A.unique_id=B.outgoing_msg_id) WHERE A.userid='$id'";
    
    $query_run = mysqli_query($conn, $query);
    
    if($query_run){
        echo json_encode(array("status" => "success"));
    }else{
        echo json_encode(array("status" => "error"));
    }
}