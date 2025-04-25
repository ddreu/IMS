<?php
include_once "../user-chat-utils/config.php";
if (!isset($_SESSION['unique_id'])) {
  include("index.php");
} else {
  $date = $datetime->format('Y-m-d H:i');
?>

  <div class="wrapper">
    <section class="chat-area" style="padding:10px;">
      <div class="float-end">
        <a href="includes/chat/php/end_chat.php?session_id=<?php echo $_SESSION['unique_id']; ?>" class="btn btn-danger btn-sm">End Chat</a>
      </div>
      <header>
        <?php
        $id = mysqli_real_escape_string($conn, $_SESSION['receiver']);
        $sql = mysqli_query($conn, "SELECT * FROM users WHERE user_id = '$id'");
        if (mysqli_num_rows($sql) > 0) {
          $row = mysqli_fetch_assoc($sql);
        }
        if ($row['status'] >= $date) {
          $online = '<i class="fas fa-circle" style="color:green;"></i>Online';
        } else {
          $online = '<i class="fas fa-circle" style="color:grey;"></i>Offline';
        }
        ?>

        <div class="details">
          <span><?php echo $row['username'] ?></span>
          <div class="status-dot">
            <p><?php echo $online ?></p>
          </div>
        </div>

      </header>
      <div class="chat-box">

      </div>
      <form action="#" class="typing-area">
        <input type="text" class="incoming_id" name="incoming_id" value="<?php echo $row['user_id']; ?>" hidden>
        <input type="text" name="message" class="input-field" placeholder="Type a message here..." autocomplete="off">
        <button><i class="fab fa-telegram-plane"></i></button>
      </form>
    </section>
  </div>

  <script src="../user-chat-utils/chatedited.js"></script>

<?php } ?>