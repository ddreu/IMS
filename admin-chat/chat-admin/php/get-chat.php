<?php
session_start();
if (isset($_SESSION['user_id'])) {
    include_once "connection.php";
    $outgoing_id = $_SESSION['user_id'];
    $incoming_id = mysqli_real_escape_string($conn, $_POST['incoming_id']);
    $output = "";
    $sql = "SELECT * FROM tbl_messages AS A LEFT JOIN tbl_end_users AS B ON B.unique_id = A.outgoing_msg_id
                WHERE (A.outgoing_msg_id = {$outgoing_id} AND A.incoming_msg_id = {$incoming_id})
                OR (A.outgoing_msg_id = {$incoming_id} AND A.incoming_msg_id = {$outgoing_id}) ORDER BY msg_id";
    $query = mysqli_query($conn, $sql);
    if (mysqli_num_rows($query) > 0) {
        while ($row = mysqli_fetch_assoc($query)) {
            if ($row['incoming_msg_id'] === $incoming_id) {
                $output .= '<div class="chat outgoing">
                                <div class="details">
                                    <p>' . $row['msg'] . '</p>
                                </div>
                                </div>';
            } else {
                $output .= '<div class="chat incoming">
                                <div class="details">
                                    <p>' . $row['msg'] . '</p>
                                </div>
                                </div>';
            }
        }
    } else {
        $output .= '<div class="text">No messages are available.</div>';
    }
    echo $output;
}
