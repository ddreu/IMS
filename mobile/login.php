<?php
session_start();
include_once '../connection/conn.php';
include_once '../user_logs/logger.php';
$conn = con();

$urlParams = isset($_SERVER['QUERY_STRING']) ? $_SERVER['QUERY_STRING'] : null;

$error = []; // Initialize the error array

if (isset($_POST['submit'])) {
    $email = $_POST['email'];
    $password = $_POST['password'];

    // Prepare SQL statement to fetch user details and linked table data
    $select = "
        SELECT 
            u.*, 
            d.department_name, 
            s.school_name, 
            s.school_code
        FROM users u
        LEFT JOIN departments d ON u.department = d.id
        LEFT JOIN schools s ON u.school_id = s.school_id
        WHERE u.email = ?
    ";

    $stmt = mysqli_prepare($conn, $select);

    if ($stmt === false) {
        die('mysqli_prepare() failed: ' . htmlspecialchars(mysqli_error($conn)));
    }

    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        $user_id = $row['id'];

        // Verify password
        if (password_verify($password, $row['password'])) {
            // Store user information in session
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['role'] = $row['role'];
            $_SESSION['lastname'] = $row['lastname'];
            $_SESSION['firstname'] = $row['firstname'];
            $_SESSION['middleinitial'] = $row['middleinitial'];
            $_SESSION['age'] = $row['age'];
            $_SESSION['gender'] = $row['gender'];
            $_SESSION['department_id'] = $row['department'];
            $_SESSION['department_name'] = $row['department_name'];
            $_SESSION['school_id'] = $row['school_id'];
            $_SESSION['school_name'] = $row['school_name'];
            $_SESSION['school_code'] = $row['school_code'];

            // Insert session log
            $ip_address = $_SERVER['REMOTE_ADDR'];
            $user_agent = $_SERVER['HTTP_USER_AGENT'];

            $user_agent .= ' mobile-app';


            $insert_session = "INSERT INTO sessions (user_id, ip_address, user_agent) VALUES (?, ?, ?)";
            $stmt_session = mysqli_prepare($conn, $insert_session);
            mysqli_stmt_bind_param($stmt_session, "iss", $user_id, $ip_address, $user_agent);
            mysqli_stmt_execute($stmt_session);

            logUserAction($conn, $user_id, 'sessions', 'Logged in', $user_id, "User Logged in");

            // Role-based redirection
            switch ($_SESSION['role']) {
                case 'superadmin':
                    $_SESSION['success_message'] = "Welcome Super Admin!";
                    $_SESSION['success_type'] = 'superadmin';
                    header('Location: super_admin/sa_dashboard.php');
                    exit();

                case 'School Admin':
                    $_SESSION['success_message'] = "Welcome School Admin!";
                    $_SESSION['success_type'] = 'School Admin';
                    header('Location: school_admin/schooladmindashboard.php');
                    exit();

                case 'Department Admin':
                    $_SESSION['success_message'] = "Welcome Department Admin!";
                    $_SESSION['success_type'] = 'Department Admin';
                    header('Location: department_admin/departmentadmindashboard.php');
                    exit();

                case 'Committee':
                    // Fetch all assigned games
                    $gameQuery = "
                    SELECT g.game_id, g.game_name 
                    FROM games g
                    WHERE g.game_id = (
                        SELECT game_id FROM users WHERE id = ?
                    )
                    UNION
                    SELECT g.game_id, g.game_name 
                    FROM committee_games cg
                    JOIN games g ON cg.game_id = g.game_id
                    WHERE cg.committee_id = ?;
                ";

                    $stmtGame = mysqli_prepare($conn, $gameQuery);
                    mysqli_stmt_bind_param($stmtGame, "ii", $user_id, $user_id);
                    mysqli_stmt_execute($stmtGame);
                    $gameResult = mysqli_stmt_get_result($stmtGame);


                    if (mysqli_num_rows($gameResult) > 1) {
                        // Multiple games assigned, redirect to selection page
                        $_SESSION['success_message'] = "We found multiple games assigned to your account. Please select your game.";
                        header('Location: ../select_dashboard.php');
                        exit();
                    } elseif ($rowGame = mysqli_fetch_assoc($gameResult)) {
                        // Only one game assigned, store it in session and go to dashboard
                        $_SESSION['game_id'] = $rowGame['game_id'];
                        $_SESSION['game_name'] = $rowGame['game_name'];
                        $_SESSION['success_message'] = "Welcome to Committee Dashboard!";
                        header('Location: ../committee/committeedashboard.php');
                        exit();
                    } else {
                        // No game assigned
                        $_SESSION['error_message'] = 'No game assigned to your account!';
                        header('Location: ../committee/no_game_assigned.php');
                        exit();
                    }

                default:
                    $_SESSION['success_message'] = "Welcome to User Dashboard!";
                    header('Location: userdashboard.php');
                    exit();
            }
        } else {
            $error[] = 'Incorrect email or password!';
        }
    } else {
        $error[] = 'Incorrect email or password!';
    }
}


// Display error message
if (!empty($error)) {
    $errorMessage = implode("<br>", $error);
    echo '<script>alert("Error: ' . htmlspecialchars($errorMessage) . '");</script>';
}
?>




<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="../loginstyle.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css?family=Poppins:100,200,300,400,500,600,700,800,900" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <title>Login</title>
</head>

<body>
    <div class="login-container">
        <div class="login-left"></div>
        <div class="login-right">
            <a href="index.php<?php echo $urlParams ? '?' . htmlspecialchars($urlParams) : ''; ?>" class="back-button">Back</a>
            <h1>Hello, welcome!</h1>
            <form method="POST" action="">
                <label for="email">Email:</label>
                <input type="email" name="email" placeholder="Email address" required>
                <label for="email">Password:</label>
                <div class="password-field-container">
                    <input type="password" id="passwordField" name="password" placeholder="Password" required>
                    <button type="button" id="togglePassword" aria-label="Toggle password visibility">
                        <i class="fa fa-eye"></i>
                    </button>
                </div>
                <button type="submit" name="submit">Login</button>
            </form>
            <div class="extra-options">
                <!-- <p>Don't have an account? <a href="register.php" style="color: #007bff;">Sign up</a></p>-->
                <a href="forgot-password.php">Forgot password?</a>
            </div>
        </div>
    </div>

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
    </script>
</body>

</html>