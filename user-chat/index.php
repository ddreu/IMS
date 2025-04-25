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

  <?php
  if (isset($_SESSION['unique_id'])) {
    include("chat.php");
  } else {
    $fname = '';

    if (!empty($_SESSION['firstname'])) {
      $fname = $_SESSION['firstname'];
    } elseif (!empty($_SESSION['email'])) {
      $fname = $_SESSION['email'];
    }  ?>
    <div class="wrapper">
      <section class="form signup">
        <header>Live Chat Support</header>
        <form action="#" method="POST" enctype="multipart/form-data" autocomplete="off">
          <div class="error-text"></div>

          <input type="hidden" name="fname" value="<?php echo htmlspecialchars($fname); ?>">

          <div class="field button">
            <input type="submit" name="submit" id="submit" value="Connect to Support">
          </div>
        </form>
      </section>
    </div>

    <script src="../user-chat-utils/signup5.js"></script>
    <script src="../user-chat-utils/chat-utils.js"></script>
  <?php } ?>


<?php }
?>