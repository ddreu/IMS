<?php
include_once "../user-chat-utils/config.php";
if (!isset($_SESSION['unique_id'])) {
  include("index.php");
} else {
  $date = $datetime->format('Y-m-d H:i');
?>
  <style>
    .chat-box .chat p {
      display: inline-block;
      padding: 8px 16px;
      font-size: 14px;
      line-height: 1.4;
      word-break: break-word;
      white-space: normal;
      min-width: 50px;
      max-width: 75%;
      border-radius: 18px;
      /* background-color: #f1f1f1; */
    }

    .chat-box .chat .details {
      display: flex;
      flex-direction: column;
      max-width: 100%;
    }

    .chat-box .incoming .details,
    .chat-box .outgoing .details {
      max-width: 100%;
    }

    .chat-box .chat p {
      word-break: break-word;
      white-space: normal;
      display: block;
      max-width: 100%;
      font-size: 14px;
      line-height: 1.4;
      padding: 8px 16px;
      border-radius: 18px;
    }

    .chat-box .chat .details {
      display: flex;
      flex-direction: column;
      max-width: 80%;
    }

    .outgoing .details,
    .incoming .details {
      max-width: 80%;
      word-wrap: break-word;
    }
  </style>

  </style>
  <div class="chat-wrapper">
    <section class="chat-area" style="padding:10px;">
      <div class="float-end">
        <!-- <a href="../user-chat-utils/end_chat.php?session_id=<?php echo $_SESSION['unique_id']; ?>" class="btn btn-danger btn-sm">End Chat</a> -->
      </div>
      <header>
        <?php
        $id = mysqli_real_escape_string($conn, $_SESSION['receiver']);
        $sql = mysqli_query($conn, "SELECT * FROM users WHERE id = '$id'");
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
          <span><?php echo $row['firstname'] ?></span>
          <div class="status-dot">
            <!-- <p><?php echo $online ?></p> -->
          </div>
        </div>

      </header>
      <div class="chat-box">

      </div>
      <form action="#" class="typing-area">
        <input type="text" class="incoming_id" name="incoming_id" value="<?php echo $row['id']; ?>" hidden>
        <input type="text" name="message" class="input-field" placeholder="Type a message here..." autocomplete="off">
        <button><i class="fab fa-telegram-plane"></i></button>
      </form>
    </section>
  </div>

  <script src="../user-chat-utils/chatedited.js"></script>
  <script src="../user-chat-utils/chat-utils.js"></script>
<?php } ?>