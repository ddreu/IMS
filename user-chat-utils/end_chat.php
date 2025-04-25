<?php
    session_start();
    if(isset($_SESSION['unique_id'])){
        include_once "config.php";
        $session_id = mysqli_real_escape_string($conn, $_GET['session_id']);
        if(isset($session_id)){
           // $status = "Offline";
          //  $sql = mysqli_query($conn, "UPDATE tbl_end_users SET status = '{$status}' WHERE unique_id={$_GET['session_id']}");
          //  if($sql){
                session_unset();
                session_destroy();
                header("location:".$_SERVER['HTTP_REFERER']);
          //  }
        }
    }
?>