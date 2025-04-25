<?php
session_start();
include_once "config.php";
$date = $datetime->format('Y-m-d H:i');
$fname = mysqli_real_escape_string($conn, $_POST['fname']);
$lname = !empty($_POST['lname']) ? mysqli_real_escape_string($conn, $_POST['lname']) : null;
$email = !empty($_POST['email']) ? mysqli_real_escape_string($conn, $_POST['email']) : null;

if (!empty($fname)) {
  // Validate email if provided
  if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo "Invalid Email!";
    exit;
  }

  $active = $datetime->format('Y-m-d H:i');
  $ran_id = rand(time(), 100000000);

  while (true) {
    $check = $conn->query("SELECT unique_id FROM tbl_end_users WHERE unique_id ='$ran_id'")->num_rows;
    if ($check > 0) {
      $ran_id = rand(time(), 100000000);
    } else {
      break;
    }
  }

  $query_checking = "SELECT status, user_id FROM users WHERE status >= '$date' ORDER BY rand()";
  $run = mysqli_query($conn, $query_checking);

  if (mysqli_num_rows($run) > 0) {
    $row = mysqli_fetch_array($run);
    $id = $row['user_id'];

    // Use NULL for lname and email if they are empty
    $lname = $lname ? "'$lname'" : "NULL";
    $email = $email ? "'$email'" : "NULL";

    $insert_query = mysqli_query(
      $conn,
      "INSERT INTO tbl_end_users (user_reciever, unique_id, fname, lname, email, status, dateentry)
            VALUES ({$id}, {$ran_id}, '{$fname}', $lname, $email, '{$active}', '{$date}')"
    );

    if ($insert_query) {
      $select_sql2 = mysqli_query($conn, "SELECT * FROM tbl_end_users WHERE unique_id = '{$ran_id}'");
      if (mysqli_num_rows($select_sql2) > 0) {
        $result = mysqli_fetch_assoc($select_sql2);
        $_SESSION['unique_id'] = $result['unique_id'];
        $_SESSION['receiver'] = $id;
        echo "success";
      }
    } else {
      echo "Something went wrong. Please try again!";
    }
  } else {
    echo "Sorry, no online staff for now. Try again later or fill up our contact form.";
  }
} else {
  echo "First name is required!";
}
