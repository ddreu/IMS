<?php
include_once "chat-admin/php/connection.php";
session_start();
?>

<?php
$user_id = mysqli_real_escape_string($conn, $_GET['user_id']);
// $sql = mysqli_query($conn, "SELECT * FROM tbl_end_users WHERE unique_id = {$user_id}");
$sql = mysqli_query($conn, "
    SELECT A.*, B.image 
    FROM tbl_end_users AS A
    LEFT JOIN users AS B ON A.user_reciever = B.id
    WHERE A.unique_id = {$user_id}
");

if (mysqli_num_rows($sql) > 0) {
    $row = mysqli_fetch_assoc($sql);
} else {
    header("location: ../index.php");
}

$image_path = (!empty($row['image']) && file_exists("../uploads/users/" . $row['image']))
    ? "../uploads/users/" . $row['image']
    : "../assets/defaults/default-profile.jpg";
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
    <link rel="stylesheet" href="css/chat.css">
    <link rel="stylesheet" href="../super_admin/sastyles.css">
    <style>
        .wrapper {
            height: 80vh;
            width: 85vw;
            margin-left: 130px;
            margin-right: 30px;
        }

        .chat-box .chat p {
            word-wrap: break-word;
            padding: 8px 16px;
            box-shadow: 0 0 32px rgb(0 0 0 / 8%), 0rem 16px 16px -16px rgb(0 0 0 / 10%);
        }

        a {
            text-decoration: none;
        }
    </style>
</head>

<body>
    <?php
    $current_page = 'dashboard';
    include '../navbar/navbar.php';
    include '../super_admin/sa_sidebar.php';
    ?>

    <div class="container-fluid mt-5">
        <div class="row justify-content-center">
            <main id="main" class="main">
                <section class="section">
                    <div class="wrapper">
                        <section class="chat-area" style="padding:10px;">
                            <div class="float-end">
                                <!-- Example single danger button -->
                                <div class="btn-group">
                                    <button type="button" class="btn btn-white btn-lg" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="fa fa-ellipsis-v" aria-hidden="true"></i>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="mailto:<?= $row['email'] ?>"><i class="fa fa-envelope text-success"></i> <?= $row['email'] ?></a></li>

                                        <li>
                                            <hr class="dropdown-divider">
                                        </li>
                                        <li><a class="dropdown-item delete" data-id="<?= $row['userid'] ?>" href="javascript.void(0)"><i class="fa fa-trash text-danger"></i> Delete</a></li>
                                    </ul>
                                </div>
                            </div>
                            <header>
                                <a href="messages.php" class="back-icon"><i class="fas fa-arrow-left"></i></a>
                                <!-- <img src="includes/uploads/users/default-profile.png"> -->
                                <img src="<?php echo $image_path; ?>" alt="Profile Picture" class="rounded-circle" width="45" height="45">

                                <div class="details">
                                    <span class="fw-bold"><?php echo $row['fname'] ?> <?php echo $row['lname'] ?></span>
                                    <p><i id="test"> </i><i id="dot"></i> </p>
                                </div>

                            </header>
                            <div class="chat-box">

                            </div>
                            <form action="#" class="typing-area">
                                <input type="text" class="incoming_id" name="incoming_id" value="<?php echo $row['unique_id']; ?>" hidden>
                                <input type="text" name="message" class="input-field" placeholder="Type a message here..." autocomplete="off">
                                <button><i class="fab fa-telegram-plane"></i></button>
                            </form>
                        </section>
                    </div>
                </section>
            </main>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script src="chat-admin/chatJS/chat1.js"></script>
    <script>
        var session = function() {

            $.ajax({
                type: "GET",
                url: "chat-admin/php/session.php?userid=<?php echo $_GET['user_id'] ?>",
                success: function(response) {
                    var res = jQuery.parseJSON(response);
                    if (res.status == 404) {
                        $('#test').addClass('fas fa-circle text-success');
                        $('#test').html('Active now');
                    } else {
                        $('#test').addClass('fas fa-circle');
                        $('#test').html('Offline');
                    }
                }
            })
        }
        setInterval(session, 1000);


        //if chat is opened 
        var openchat = function() {

            $.ajax({
                type: "GET",
                url: "chat-admin/php/open-message.php?userid=<?php echo $user_id ?>",
                success: function(response) {
                    var res = jQuery.parseJSON(response);
                    if (res.status == 100) {

                    } else {

                    }
                }
            })
        }
        setInterval(openchat, 1000);
    </script>

    <script>
        //Delete messages
        $('.delete').click(function(e) {
            e.preventDefault();
            var del = $(this).data('id');
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
                        type: 'GET',
                        url: 'chat-admin/php/delete-message.php',
                        data: {
                            del: del
                        },
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === "success") {
                                Swal.fire("Deleted!", "Successfuly Deleted.", "success").then(function() {
                                    location.href = 'messages.php';
                                })
                            } else {
                                Swal.fire("Error!", "An Error occured.", "error");
                            }
                        },
                        error: function(xhr, status, error) {
                            console.error(xhr.responseText);
                            Swal.fire("Error!", "An error occurred.", "error");
                        }
                    });
                }
            });
        });
    </script>
</body>

</html>