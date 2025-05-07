<?php
// session_start();
include_once '../connection/conn.php';
// include_once 'user_logs/logger.php';
// $conn = con();

$urlParams = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null;

// $error = []; // Initialize the error array

// if (isset($_POST['submit'])) {
//     $email = $_POST['email'];
//     $password = $_POST['password'];

//     // Prepare SQL statement to fetch user details and linked table data
//     $select = "
//         SELECT 
//             u.*, 
//             d.department_name, 
//             s.school_name, 
//             s.school_code
//         FROM users u
//         LEFT JOIN departments d ON u.department = d.id
//         LEFT JOIN schools s ON u.school_id = s.school_id
//         WHERE u.email = ?
//     ";

//     $stmt = mysqli_prepare($conn, $select);

//     if ($stmt === false) {
//         die('mysqli_prepare() failed: ' . htmlspecialchars(mysqli_error($conn)));
//     }

//     mysqli_stmt_bind_param($stmt, "s", $email);
//     mysqli_stmt_execute($stmt);
//     $result = mysqli_stmt_get_result($stmt);

//     if (mysqli_num_rows($result) > 0) {
//         $row = mysqli_fetch_assoc($result);
//         $user_id = $row['id'];

//         // Verify password
//         if (password_verify($password, $row['password'])) {
//             // Store user information in session
//             $_SESSION['user_id'] = $row['id'];
//             $_SESSION['email'] = $row['email'];
//             $_SESSION['role'] = $row['role'];
//             $_SESSION['lastname'] = $row['lastname'];
//             $_SESSION['firstname'] = $row['firstname'];
//             $_SESSION['middleinitial'] = $row['middleinitial'];
//             $_SESSION['age'] = $row['age'];
//             $_SESSION['gender'] = $row['gender'];
//             $_SESSION['department_id'] = $row['department'];
//             $_SESSION['department_name'] = $row['department_name'];
//             $_SESSION['school_id'] = $row['school_id'];
//             $_SESSION['school_name'] = $row['school_name'];
//             $_SESSION['school_code'] = $row['school_code'];

//             // Insert session log
//             $ip_address = $_SERVER['REMOTE_ADDR'];
//             $user_agent = $_SERVER['HTTP_USER_AGENT'];
// $user_agent .= ' mobile-app';
//             $insert_session = "INSERT INTO sessions (user_id, ip_address, user_agent) VALUES (?, ?, ?)";
//             $stmt_session = mysqli_prepare($conn, $insert_session);
//             mysqli_stmt_bind_param($stmt_session, "iss", $user_id, $ip_address, $user_agent);
//             mysqli_stmt_execute($stmt_session);

//             logUserAction($conn, $user_id, 'sessions', 'Logged in', $user_id, "User Logged in");

//             // Role-based redirection
//             switch ($_SESSION['role']) {
//                 case 'superadmin':
//                     $_SESSION['success_message'] = "Welcome Super Admin!";
//                     $_SESSION['success_type'] = 'superadmin';
//                     header('Location: super_admin/sa_dashboard.php');
//                     exit();

//                 case 'School Admin':
//                     $_SESSION['success_message'] = "Welcome School Admin!";
//                     $_SESSION['success_type'] = 'School Admin';
//                     header('Location: school_admin/schooladmindashboard.php');
//                     exit();

//                 case 'Department Admin':
//                     $_SESSION['success_message'] = "Welcome Department Admin!";
//                     $_SESSION['success_type'] = 'Department Admin';
//                     header('Location: department_admin/departmentadmindashboard.php');
//                     exit();

//                 case 'Committee':
//                     // Fetch all assigned games
//                     $gameQuery = "
//                     SELECT g.game_id, g.game_name 
//                     FROM games g
//                     WHERE g.game_id = (
//                         SELECT game_id FROM users WHERE id = ?
//                     )
//                     UNION
//                     SELECT g.game_id, g.game_name 
//                     FROM committee_games cg
//                     JOIN games g ON cg.game_id = g.game_id
//                     WHERE cg.committee_id = ?;
//                 ";

//                     $stmtGame = mysqli_prepare($conn, $gameQuery);
//                     mysqli_stmt_bind_param($stmtGame, "ii", $user_id, $user_id);
//                     mysqli_stmt_execute($stmtGame);
//                     $gameResult = mysqli_stmt_get_result($stmtGame);


//                     if (mysqli_num_rows($gameResult) > 1) {
//                         // Multiple games assigned, redirect to selection page
//                         $_SESSION['success_message'] = "We found multiple games assigned to your account. Please select your game.";
//                         header('Location: select_dashboard.php');
//                         exit();
//                     } elseif ($rowGame = mysqli_fetch_assoc($gameResult)) {
//                         // Only one game assigned, store it in session and go to dashboard
//                         $_SESSION['game_id'] = $rowGame['game_id'];
//                         $_SESSION['game_name'] = $rowGame['game_name'];
//                         $_SESSION['success_message'] = "Welcome to Committee Dashboard!";
//                         header('Location: committee/committeedashboard.php');
//                         exit();
//                     } else {
//                         // No game assigned
//                         $_SESSION['error_message'] = 'No game assigned to your account!';
//                         header('Location: committee/no_game_assigned.php');
//                         exit();
//                     }

//                 default:
//                     $_SESSION['success_message'] = "Welcome to User Dashboard!";
//                     header('Location: userdashboard.php');
//                     exit();
//             }
//         } else {
//             $error[] = 'Incorrect email or password!';
//         }
//     } else {
//         $error[] = 'Incorrect email or password!';
//     }
// }


// // Display error message
// if (!empty($error)) {
//     $errorMessage = implode("<br>", $error);
//     echo '<script>alert("Error: ' . htmlspecialchars($errorMessage) . '");</script>';
// }
?>




<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="../loginstyle.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://fonts.googleapis.com/css?family=Poppins:100,200,300,400,500,600,700,800,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <title>Login</title>
    <style>
        .navbar-bottom {
            position: fixed;
            bottom: 0;
            left: 20px;
            /* Add some margin to the left */
            right: 20px;
            /* Add some margin to the right */
            bottom: 20px;
            background-color: #fff;
            border-top: 1px solid #ddd;
            z-index: 10;
            border-radius: 20px;
            /* Add border radius for rounded corners */
            box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
            /* Stronger shadow */
            /* Add a soft shadow for floating effect */
        }

        .navbar-bottom .nav-link {
            text-align: center;
            padding: 12px;
            font-size: 16px;
        }

        .navbar-bottom .nav-link i {
            font-size: 20px;
            margin-bottom: 5px;
        }


        .navbar-bottom .nav-item {
            flex: 1;
        }

        @media (max-width: 800px) and (min-width: 400px) {
            .navbar-nav {
                display: flex;
                flex-direction: row;
                flex-wrap: nowrap;
                justify-content: space-between;
                width: 100vw;
            }

            .nav-item {
                text-align: center;
            }
        }

        .navbar-bottom .nav-link.active {
            color: rgb(180, 34, 8);
        }

        .navbar-bottom .nav-link:hover {
            color: #007bff;
        }
    </style>
</head>

<body>
    <div class="login-container">
        <div class="login-left"></div>
        <div class="login-right">
            <a href="index.php" class="back-button">Back to Homepage</a>
            <!-- Login Form -->
            <div id="loginFormContainer">
                <h1>Hello, welcome!</h1>
                <form id="loginForm" method="POST">
                    <label class="email-label" for="email">Email:</label>
                    <input type="email" name="email" placeholder="Email address" required>

                    <label class="password-label" for="password">Password:</label>
                    <div class="password-field-container">
                        <input type="password" id="passwordField" name="password" placeholder="Password" required>
                        <button type="button" id="togglePassword">
                            <i class="fa fa-eye"></i>
                        </button>
                    </div>

                    <div id="loginError" class="login-error"></div>
                    <button class="login-btn" type="submit">Login</button>
                </form>

                <div class="extra-options">
                    <a href="#" id="showForgotPassword">Forgot password?</a>
                    <span>|</span>
                    <a href="qr-login.php">Log in using QR</a>
                </div>
            </div>

            <!-- Forgot Password Form -->
            <div id="forgotPasswordFormContainer" style="display: none;">
                <h1>Forgot Password</h1>
                <p>Enter your email to reset your password</p>
                <form id="forgotPasswordForm" method="post">
                    <label for="email">Email:</label>
                    <input type="email" name="email" placeholder="Enter your email" required>
                    <button type="submit" class="login-btn">Send</button>
                </form>
                <div class="extra-options">
                    <a href="#" id="backToLogin">Back to Login</a>
                </div>
            </div>

            <div id="qrLoginFormContainer" style="display: none;">
                <h1>Scan to Login</h1>
                <p>Scan this QR code</p>
                <div id="qrCodeDisplay" style="margin: 20px auto; width: 200px; height: 200px;"></div>
                <div class="extra-options">
                    <a href="#" id="backToLoginFromQR">Back to Login</a>
                </div>
            </div>


        </div>

    </div>
    <div id="loader" class="loader-container loader-in-transition">
        <svg class="basketball-svg" xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 400 400" fill="none">
            <path class="basketball-line"
                d="M386.546,128.301c-19.183-49.906-56.579-89.324-105.302-110.99C255.513,5.868,228.272,0.065,200.28,0.065 
      c-79.087,0-150.907,46.592-182.972,118.693c-21.668,48.723-23.036,103.041-3.854,152.944 
      c19.181,49.905,56.578,89.324,105.299,110.992c25.726,11.438,52.958,17.238,80.949,17.24c0.008,0,0.008,0,0.016,0 
      c64.187,0,124.602-30.795,162.104-82.505l3.967-5.719c6.559-9.743,12.238-19.979,16.9-30.469 
      C404.359,232.521,405.728,178.206,386.546,128.301z M306.656,67.229c29.342,23.576,50.05,56.346,58.89,93.178 
      c-26.182,14.254-115.898,58.574-227.678,63.936c-0.22-6.556-0.188-13.204,0.095-19.894c3.054,0.258,6.046,0.392,8.957,0.392 
      c48.011,0,72.144-34.739,95.479-68.341C258.911,112.729,277.523,85.931,306.656,67.229z M200.322,29.683 
      c23.826,0,47.004,4.939,68.891,14.682c3.611,1.607,7.234,3.381,10.836,5.309c-27.852,20.82-45.873,46.773-61.961,69.941 
      c-22.418,32.272-38.612,55.592-71.058,55.592c-2.009,0-4.09-0.088-6.231-0.264c10.624-71.404,45.938-128.484,57.204-145.242 
      C198.778,29.688,199.552,29.683,200.322,29.683z M83.571,75.701c21.39-19.967,48.144-34.277,76.704-41.215 
      c-16.465,28.652-38.163,74.389-47.548,128.982C90.537,147.617,65.38,118.793,83.571,75.701z M44.354,130.786 
      c1.519-3.414,3.15-6.779,4.895-10.094c0.915,4.799,2.234,9.52,3.96,14.139c12.088,32.377,40.379,52.406,55.591,61.219 
      c-0.654,9.672-0.84,19.303-0.548,28.762c-26.46-0.441-52.557-3.223-77.752-8.283C27.604,187.29,32.359,157.756,44.354,130.786z 
      M69.818,288.907c-2.943,3.579-5.339,7.495-7.178,11.717c-11.635-15.948-20.479-33.894-26.052-52.862 
      c24.227,4.182,49.111,6.424,74.187,6.678c0.554,3.955,1.199,7.906,1.931,11.828C99.568,268.702,81.578,274.605,69.818,288.907z 
      M130.784,355.646c-15.528-6.904-29.876-16.063-42.687-27.244c-1.059-8.738,0.472-15.68,4.558-20.658 
      c6.582-8.028,18.771-11.321,27.153-12.666c7.324,23.808,18.148,46.728,32.287,68.381 
      C144.818,361.331,137.693,358.722,130.784,355.646z M193.648,370.185c-19.319-23.783-33.777-49.438-43.082-76.426 
      c22.608,1.221,42.078,8.045,62.571,15.227c25.484,8.926,51.84,18.158,85.997,18.158c4.938,0,9.874-0.189,14.856-0.574 
      C281.376,355.896,238.354,371.788,193.648,370.185z M355.648,269.22c-3.43,7.703-7.519,15.278-12.173,22.555 
      c-15.463,3.785-29.923,5.625-44.119,5.625c-29.753,0-53.479-8.311-76.427-16.35c-23.997-8.41-48.813-17.107-79.65-17.107 
      c-0.267,0-0.534,0-0.802,0.002c-0.686-3.381-1.293-6.764-1.823-10.137c49.176-2.496,99.361-12.211,149.312-28.91 
      c35.29-11.799,62.965-24.643,80.103-33.42C371.438,218.101,366.516,244.771,355.648,269.22z" />
        </svg>
    </div>
    <nav class="navbar navbar-expand-lg navbar-light fixed-bottom navbar-bottom">
        <div class="container-fluid">
            <ul class="navbar-nav d-flex justify-content-between w-100">
                <!-- Live Scores Link -->
                <li class="nav-item">
                    <a class="nav-link" href="pages/livescores.php">
                        <i class="fas fa-basketball-ball"></i>
                        <br>Live Scores
                    </a>
                </li>

                <!-- Login Link -->
                <li class="nav-item">
                    <a class="nav-link active" href="login.php">
                        <i class="fas fa-sign-in-alt"></i>
                        <br>Login
                    </a>
                </li>
            </ul>
        </div>
    </nav>

    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordField = document.getElementById('passwordField');

        togglePassword.addEventListener('click', function() {
            const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordField.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye-slash');
            this.querySelector('i').classList.toggle('fa-eye');
        });

        function showMessage(message, messageType) {
            alert(message); // Simplified for demonstration
        }

        // Display error message if exists
        if (typeof errorMessage !== 'undefined') {
            showMessage(errorMessage, 'error-message');
        }


        document.getElementById('loginForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const form = e.target;
            const loader = document.getElementById('loader');
            const emailInput = form.querySelector('input[name="email"]');
            const passwordInput = form.querySelector('input[name="password"]');

            // Clear previous error styles
            emailInput.classList.remove('input-error');
            passwordInput.classList.remove('input-error');

            const formData = new FormData(form);

            fetch('login-process.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        // Show loader only on success
                        loader.classList.add('show');
                        const line = loader.querySelector('.basketball-line');
                        if (line) {
                            line.classList.add('animate');
                        }

                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 1500);
                    } else {
                        // Don't show loader
                        // Highlight fields
                        emailInput.classList.add('input-error');
                        passwordInput.classList.add('input-error');

                        // Show toast error
                        Swal.fire({
                            toast: true,
                            position: 'top',
                            icon: 'error',
                            title: data.message || 'Login failed. Please try again.',
                            showConfirmButton: false,
                            timer: 2500,
                            timerProgressBar: true
                        });

                        // Clear input error styles after timeout
                        setTimeout(() => {
                            emailInput.classList.remove('input-error');
                            passwordInput.classList.remove('input-error');
                        }, 2300);
                    }
                })
                .catch(err => {
                    console.error(err);
                    Swal.fire({
                        toast: true,
                        position: 'top',
                        icon: 'error',
                        title: 'Something went wrong!',
                        showConfirmButton: false,
                        timer: 2500,
                        timerProgressBar: true
                    });
                });
        });



        // Remove input-error on field change
        const emailInput = document.querySelector('input[name="email"]');
        const passwordInput = document.querySelector('input[name="password"]');
        const errorDiv = document.getElementById('loginError');

        emailInput.addEventListener('input', () => {
            emailInput.classList.remove('input-error');
            errorDiv.style.display = 'none';
        });

        passwordInput.addEventListener('input', () => {
            passwordInput.classList.remove('input-error');
            errorDiv.style.display = 'none';
        });

        // log in - forgot password

        const loginFormContainer = document.getElementById('loginFormContainer');
        const forgotPasswordFormContainer = document.getElementById('forgotPasswordFormContainer');
        const showForgotPassword = document.getElementById('showForgotPassword');
        const backToLogin = document.getElementById('backToLogin');

        // Show forgot password form
        showForgotPassword.addEventListener('click', (e) => {
            e.preventDefault();
            loginFormContainer.classList.add('fade-slide-out');

            setTimeout(() => {
                loginFormContainer.style.display = 'none';
                loginFormContainer.classList.remove('fade-slide-out');

                forgotPasswordFormContainer.style.display = 'block';
                forgotPasswordFormContainer.classList.add('fade-slide-in');
            }, 300);
        });

        // Back to login form
        backToLogin.addEventListener('click', (e) => {
            e.preventDefault();
            forgotPasswordFormContainer.classList.add('fade-slide-out');

            setTimeout(() => {
                forgotPasswordFormContainer.style.display = 'none';
                forgotPasswordFormContainer.classList.remove('fade-slide-out');

                loginFormContainer.style.display = 'block';
                loginFormContainer.classList.add('fade-slide-in');
            }, 300);
        });

        document.getElementById('forgotPasswordForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const form = e.target;
            const email = form.email.value.trim();
            const sendBtn = form.querySelector('button');

            if (!email) return;

            sendBtn.disabled = true;
            sendBtn.textContent = 'Sending...';

            fetch('../send-password-reset.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: new URLSearchParams({
                        email
                    })
                })
                .then(res => res.json())
                .then(data => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: data.status,
                        title: data.message,
                        showConfirmButton: false,
                        timer: 2500,
                        timerProgressBar: true
                    });

                    if (data.status === 'success') {
                        form.reset();
                    }
                })
                .catch(() => {
                    Swal.fire({
                        toast: true,
                        position: 'top-end',
                        icon: 'error',
                        title: 'Something went wrong. Please try again.',
                        showConfirmButton: false,
                        timer: 2500,
                        timerProgressBar: true
                    });
                })
                .finally(() => {
                    sendBtn.disabled = false;
                    sendBtn.textContent = 'Send';
                });
        });

        const qrLoginFormContainer = document.getElementById('qrLoginFormContainer');
        const showQRLogin = document.querySelector('a[href="qr-login.php"]'); // Update if button ID changes
        const backToLoginFromQR = document.getElementById('backToLoginFromQR');

        showQRLogin.addEventListener('click', (e) => {
            e.preventDefault();
            loginFormContainer.classList.add('fade-slide-out');
            setTimeout(() => {
                loginFormContainer.style.display = 'none';
                loginFormContainer.classList.remove('fade-slide-out');

                qrLoginFormContainer.style.display = 'block';
                qrLoginFormContainer.classList.add('fade-slide-in');

                generateQRCode();
            }, 300);
        });

        backToLoginFromQR.addEventListener('click', (e) => {
            e.preventDefault();
            qrLoginFormContainer.classList.add('fade-slide-out');
            setTimeout(() => {
                qrLoginFormContainer.style.display = 'none';
                qrLoginFormContainer.classList.remove('fade-slide-out');

                loginFormContainer.style.display = 'block';
                loginFormContainer.classList.add('fade-slide-in');
            }, 300);
        });


        window.addEventListener('DOMContentLoaded', () => {
            loginFormContainer.classList.add('animate-on-load');
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
    <script src="qr-code/login.js"></script>

</body>

</html>