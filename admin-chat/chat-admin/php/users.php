<?php
session_start();
include_once "connection.php";
$outgoing_id = $_SESSION['user_id'];
// $sql = "SELECT * FROM tbl_end_users WHERE unique_id
//     IN (SELECT outgoing_msg_id FROM tbl_messages ORDER BY msg_id DESC)";
$sql = "SELECT * FROM tbl_end_users WHERE unique_id != {$outgoing_id} 
AND unique_id IN (
    SELECT outgoing_msg_id FROM tbl_messages WHERE incoming_msg_id = {$outgoing_id}
    UNION
    SELECT incoming_msg_id FROM tbl_messages WHERE outgoing_msg_id = {$outgoing_id}
)
ORDER BY userid DESC";


// $sql = "SELECT u.*
// FROM tbl_end_users u
// JOIN (
//     SELECT outgoing_msg_id, MAX(msg_id) AS latest_msg_id
//     FROM tbl_messages
//     GROUP BY outgoing_msg_id
// ) m ON u.unique_id = m.outgoing_msg_id
// ORDER BY m.latest_msg_id DESC";

$query = mysqli_query($conn, $sql);
$output = "";
if (mysqli_num_rows($query) == 0) {
    $output .= "No users are available to chat";
} elseif (mysqli_num_rows($query) > 0) {
    include_once "data.php";
}
echo $output;
