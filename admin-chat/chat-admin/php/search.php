<?php
session_start();
include_once "connection.php";

$outgoing_id = $_SESSION['user_id'];
$searchTerm = mysqli_real_escape_string($conn, $_POST['searchTerm']);

$sql = "SELECT * FROM tbl_end_users WHERE NOT unique_id = {$outgoing_id} AND (fname LIKE '%{$searchTerm}%' OR lname LIKE '%{$searchTerm}%') ";
$output = "";
$query = mysqli_query($conn, $sql);
if (mysqli_num_rows($query) > 0) {
    include_once "data.php";
} else {
    $output .= 'No Users';
}
echo $output;
