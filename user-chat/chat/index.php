<?php
if (isset($_SESSION['unique_id'])) {
  include("chat.php");
  echo $_SESSION['unique_id'];
} else {
  // echo "Session not initialized";

  // if (isset($_SESSION['unique_id'])) {
  //   include("includes/chat/chat.php");
  //   echo $_SESSION['unique_id'];
  // } else {
  // echo $_SESSION['unique_id'];
?>

  <div class="wrapper">
    <section class="form signup">
      <header>Fill Up this Form to Continue</header>
      <form action="#" method="POST" enctype="multipart/form-data" autocomplete="off">
        <div class="error-text"></div>
        <div class="name-details">
          <div class="field input">
            <label>First Name</label>
            <input type="text" name="fname" placeholder="First name" required>
          </div>
          <!-- <div class="field input">
            <label>Last Name</label>
            <input type="text" name="lname" placeholder="Last name" required>
          </div> -->
        </div>
        <!-- <div class="field input">
          <label>Email Address</label>
          <input type="text" name="email" placeholder="Enter your email" required>
        </div> -->

        <div class="field button">
          <input type="submit" name="submit" id="submit" value="Continue to Chat">
        </div>
      </form>
    </section>
  </div>

  <script src="../user-chat-utils/signup5.js"></script>

<?php }
?>