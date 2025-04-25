<?php
session_start();
include 'connection.php';
$active = $datetime->format('Y-m-d H:i');
if (isset($_GET['userid'])) {
    //  $open = $conn->query("UPDATE tbl_messages SET open=1 WHERE incoming_msg_id=".$_SESSION['login_id']);
    $id = $_GET['userid'];
    $query = "SELECT status FROM tbl_end_users WHERE unique_id='$id'";
    $run = mysqli_query($conn, $query);
    foreach ($run as $row) {
        $status = $row['status'];
    }
    if ($status < $active) {
        $res = ['status' => 101];
        echo json_encode($res);
        return;
    } else {
        $res = ['status' => 404];
        echo json_encode($res);
        return;
    }
}
