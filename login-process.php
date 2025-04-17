<?php
session_start();
include_once 'connection/conn.php';
include_once 'user_logs/logger.php';
$conn = con();

header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'Invalid login.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    $select = "
        SELECT u.*, d.department_name, s.school_name, s.school_code
        FROM users u
        LEFT JOIN departments d ON u.department = d.id
        LEFT JOIN schools s ON u.school_id = s.school_id
        WHERE u.email = ?
    ";

    $stmt = mysqli_prepare($conn, $select);
    if (!$stmt) {
        echo json_encode(['success' => false, 'message' => 'Server error.']);
        exit;
    }

    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($row = mysqli_fetch_assoc($result)) {
        if (password_verify($password, $row['password'])) {
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
            $insert_session = "INSERT INTO sessions (user_id, ip_address, user_agent) VALUES (?, ?, ?)";
            $stmt_session = mysqli_prepare($conn, $insert_session);
            mysqli_stmt_bind_param($stmt_session, "iss", $row['id'], $ip_address, $user_agent);
            mysqli_stmt_execute($stmt_session);

            logUserAction($conn, $row['id'], 'sessions', 'Logged in', $row['id'], "User Logged in");

            // Redirect logic
            $redirect = '';

            switch ($row['role']) {
                case 'superadmin':
                    $redirect = 'super_admin/sa_dashboard.php';
                    break;
                case 'School Admin':
                    $redirect = 'school_admin/schooladmindashboard.php';
                    break;
                case 'Department Admin':
                    $redirect = 'department_admin/departmentadmindashboard.php';
                    break;
                case 'Committee':
                    $user_id = $row['id'];
                    $gameQuery = "SELECT g.game_id, g.game_name 
                        FROM games g WHERE g.game_id = (SELECT game_id FROM users WHERE id = ?) 
                        UNION 
                        SELECT g.game_id, g.game_name FROM committee_games cg 
                        JOIN games g ON cg.game_id = g.game_id 
                        WHERE cg.committee_id = ?";

                    $stmtGame = mysqli_prepare($conn, $gameQuery);
                    mysqli_stmt_bind_param($stmtGame, "ii", $user_id, $user_id);
                    mysqli_stmt_execute($stmtGame);
                    $gameResult = mysqli_stmt_get_result($stmtGame);

                    if (mysqli_num_rows($gameResult) > 1) {
                        $_SESSION['success_message'] = "Please select your game.";
                        $redirect = 'select_dashboard.php';
                    } elseif ($rowGame = mysqli_fetch_assoc($gameResult)) {
                        $_SESSION['game_id'] = $rowGame['game_id'];
                        $_SESSION['game_name'] = $rowGame['game_name'];
                        $redirect = 'committee/committeedashboard.php';
                    } else {
                        $_SESSION['error_message'] = "No game assigned.";
                        $redirect = 'committee/no_game_assigned.php';
                    }
                    break;
                default:
                    $redirect = 'userdashboard.php';
                    break;
            }

            echo json_encode(['success' => true, 'redirect' => $redirect]);
            exit;
        } else {
            $response['message'] = 'Incorrect password.';
        }
    } else {
        $response['message'] = 'User not found.';
    }
}

echo json_encode($response);
exit;
