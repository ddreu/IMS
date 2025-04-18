<?php
session_start();
include_once '../../connection/conn.php';
include_once '../../user_logs/logger.php';
$conn = con();

header('Content-Type: application/json');

$token = $_GET['token'] ?? '';
if (empty($token)) {
    echo json_encode(['success' => false]);
    exit;
}

// Check if token is marked as used
$stmt = $conn->prepare("SELECT * FROM qr_tokens WHERE token = ? AND used = 1 LIMIT 1");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $user_id = $row['user_id'];

    // Fetch full user info
    $userQuery = "
        SELECT u.*, d.department_name, s.school_name, s.school_code
        FROM users u
        LEFT JOIN departments d ON u.department = d.id
        LEFT JOIN schools s ON u.school_id = s.school_id
        WHERE u.id = ?
    ";
    $stmtUser = $conn->prepare($userQuery);
    $stmtUser->bind_param("i", $user_id);
    $stmtUser->execute();
    $userResult = $stmtUser->get_result();

    if ($user = $userResult->fetch_assoc()) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['lastname'] = $user['lastname'];
        $_SESSION['firstname'] = $user['firstname'];
        $_SESSION['middleinitial'] = $user['middleinitial'];
        $_SESSION['age'] = $user['age'];
        $_SESSION['gender'] = $user['gender'];
        $_SESSION['department_id'] = $user['department'];
        $_SESSION['department_name'] = $user['department_name'];
        $_SESSION['school_id'] = $user['school_id'];
        $_SESSION['school_name'] = $user['school_name'];
        $_SESSION['school_code'] = $user['school_code'];

        // Log session
        $ip = $_SERVER['REMOTE_ADDR'];
        $agent = $_SERVER['HTTP_USER_AGENT'];
        $user_agent .= ' mobile-app';

        $logStmt = $conn->prepare("INSERT INTO sessions (user_id, ip_address, user_agent) VALUES (?, ?, ?)");
        $logStmt->bind_param("iss", $user_id, $ip, $agent);
        $logStmt->execute();

        logUserAction($conn, $user_id, 'QR Login', 'Logged in', $user_id, 'Logged in via QR');

        // Redirect based on role
        $redirect = '';
        switch ($user['role']) {
            case 'superadmin':
                $_SESSION['success_message'] = "Welcome Super Admin!";
                $_SESSION['success_type'] = "superadmin";
                $redirect = '../../super_admin/sa_dashboard.php';
                break;

            case 'School Admin':
                $_SESSION['success_message'] = "Welcome School Admin!";
                $_SESSION['success_type'] = "School Admin";
                $redirect = '../../school_admin/schooladmindashboard.php';
                break;

            case 'Department Admin':
                $_SESSION['success_message'] = "Welcome Department Admin!";
                $_SESSION['success_type'] = "Department Admin";
                $redirect = '../../department_admin/departmentadmindashboard.php';
                break;

            case 'Committee':
                $gameQuery = "
                    SELECT g.game_id, g.game_name 
                    FROM games g WHERE g.game_id = (SELECT game_id FROM users WHERE id = ?) 
                    UNION 
                    SELECT g.game_id, g.game_name FROM committee_games cg 
                    JOIN games g ON cg.game_id = g.game_id 
                    WHERE cg.committee_id = ?
                ";
                $stmtGame = $conn->prepare($gameQuery);
                $stmtGame->bind_param("ii", $user_id, $user_id);
                $stmtGame->execute();
                $gameResult = $stmtGame->get_result();

                if ($gameResult->num_rows > 1) {
                    $_SESSION['success_message'] = "Please select your game.";
                    $_SESSION['success_type'] = "login";
                    $redirect = 'select_dashboard.php';
                } elseif ($rowGame = $gameResult->fetch_assoc()) {
                    $_SESSION['game_id'] = $rowGame['game_id'];
                    $_SESSION['game_name'] = $rowGame['game_name'];
                    $_SESSION['success_message'] = "Welcome to Committee Dashboard!";
                    $_SESSION['success_type'] = "login";
                    $redirect = '../../committee/committeedashboard.php';
                } else {
                    $_SESSION['error_message'] = "No game assigned.";
                    $redirect = '../../committee/no_game_assigned.php';
                }
                break;

            default:
                $_SESSION['success_message'] = "Welcome to User Dashboard!";
                $_SESSION['success_type'] = "login";
                $redirect = '../../userdashboard.php';
                break;
        }

        echo json_encode(['success' => true, 'redirect' => $redirect]);
        exit;
    }
}

echo json_encode(['success' => false]);
