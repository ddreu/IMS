<?php

include 'chat-admin/php/connection.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title></title>
  <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <link rel="stylesheet" href="css/chat.css">
  <link rel="stylesheet" href="../super_admin/sastyles.css">
  <style>
    .wrapper {
      height: 80vh;
      width: 85vw;
      margin-left: 130px;
      margin-right: 30px;
    }

    a {
      text-decoration: none;
    }
  </style>
</head>

<body>
  <main id="main" class="main">
    <?php
    $current_page = 'dashboard';
    include '../navbar/navbar.php';
    include '../super_admin/sa_sidebar.php';
    ?>

    <section class="section">
      <div class="container-fluid mt-5">
        <div class="row justify-content-center">
          <!-- <div class="col-xl-10 col-lg-11 col-md-11 col-sm-12"> -->
          <div class="wrapper">
            <section class="users">
              <header>
                <div class="content">
                  <?php
                  $date = $datetime->format('Y-m-d H:i');
                  $sql = mysqli_query($conn, "SELECT * FROM users WHERE id = {$_SESSION['user_id']}");
                  if (mysqli_num_rows($sql) > 0) {
                    $row = mysqli_fetch_assoc($sql);
                  }
                  ?>

                  <div class="details">
                    <span><?php echo htmlspecialchars($row['firstname'] . ' ' . $row['middleinitial'] . ' ' . $row['lastname']); ?></span>
                    <p>
                      <?php
                      if ($row['status'] <= $date) {
                        echo ' <i class="fas fa-circle text-secodary"></i> Offline';
                      } else {
                        echo ' <i class="fas fa-circle text-success"></i> Online';
                      }
                      ?></p>
                  </div>
                  <!-- <div class="float-end">
                    <button type="button" class="btn btn-danger delete" value="<?= $_SESSION['user_id'] ?>">Delete All</button>
                  </div> -->


              </header>
              <div class="search">
                <span class="text">Select a user to start chat</span>
                <input type="text" placeholder="Enter name to search...">
                <button><i class="fas fa-search"></i></button>
              </div>
              <div class="users-list">

              </div>
            </section>
          </div>
        </div>
      </div>
      </div>
    </section>
  </main>
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

  <script src="chat-admin/chatJS/users.js"></script>

  <script>
    $(".delete").click(function(e) {
      e.preventDefault()
      Swal.fire({
        title: "Are you sure?",
        text: "You won't be able to revert this!",
        icon: "warning",
        showCancelButton: true,
        confirmButtonColor: "#3085d6",
        cancelButtonColor: "#d33",
        confirmButtonText: "Yes, delete it!"
      }).then((result) => {
        if (result.isConfirmed) {
          $.ajax({
            type: "GET",
            url: "includes/deletemessage.php?session=" + $(this).val(),
            success: function(response) {
              var res = jQuery.parseJSON(response);
              if (res.status == 'success') {
                Swal.fire("Success", res.message, "success").then(function() {
                  window.location.reload();
                })
              } else {
                Swal.fire("Error", res.message, "error")
              }
            }
          })
        }
      })
    })
  </script>
</body>
<?php
// include 'includes/footer.php';
?>