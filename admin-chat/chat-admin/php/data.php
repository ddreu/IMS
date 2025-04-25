<?php
$active = $datetime->format('Y-m-d H:i');

while ($row = mysqli_fetch_assoc($query)) {
    $sql2 = "SELECT * FROM tbl_messages WHERE (incoming_msg_id = {$row['unique_id']}
                OR outgoing_msg_id = {$row['unique_id']}) AND (outgoing_msg_id = {$outgoing_id} 
                OR incoming_msg_id = {$outgoing_id}) ORDER BY msg_id DESC LIMIT 1";
    $query2 = mysqli_query($conn, $sql2);
    $row2 = mysqli_fetch_assoc($query2);
    (mysqli_num_rows($query2) > 0) ? $result = $row2['msg'] : $result = "No message available";
    (strlen($result) > 28) ? $msg =  substr($result, 0, 28) . '...' : $msg = $result;
    if (isset($row2['outgoing_msg_id'])) {
        ($outgoing_id == $row2['outgoing_msg_id']) ? $you = "You: " : $you = '';
    } else {
        $you = "";
    }
    if ($row['status'] < $active) {
        $offline = "offline";
    } else {
        $offline = "";
    }
    if ($row2['open'] == 0 && mysqli_num_rows($query2) > 0 && $row2['incoming_msg_id'] == $outgoing_id) {
        $open = 'background-color:#d3d3d3; font-weight: bold;';
        $test = 'font-weight: bold;';
    } else {
        $open = 'background-color:#FFFFFF;';
        $test = "";
    }

    if (!empty($row2['dateentry'])) {
        $dateentry = date('M d, Y h:i:a', strtotime($row2['dateentry']));
    } else {
        $dateentry = '';
    }

    ($outgoing_id == $row['unique_id']) ? $hid_me = "hide" : $hid_me = "";

    $output .= '<a href="chat.php?user_id=' . $row['unique_id'] . '" style=" ' . $open . ' padding:15px; height:70px; border-radius:13px;">
                    <div class="content">
                     <div class="status-dot ' . $offline . ' " id="status"><i class="fas fa-circle"></i></div>
                    <div class="details">
                        <span style="' . $test . '">' . $row['fname'] . " " . $row['lname'] . '</span>
                        <p class="text-black">' . $you . $msg . '</p>
                    </div>
                    </div>
                     <div style="color:#171717;">' . $dateentry . '</div>
                </a>';
}
