<?php
session_start();
ob_start();
include '../connection/conn.php';
include '../includes/password_utils.php';
include '../user_logs/logger.php'; // Include the logger at the top
$conn = con();

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Initialize school_code and school_id
$school_code = null;
$school_id = $_SESSION['school_id'] ?? null;

// Fetch the school_code based on the school_id from the schools table
if ($school_id) {
    $fetch_school_sql = "SELECT school_code FROM schools WHERE school_id = ?";
    $stmt_fetch_school = executeQuery($conn, $fetch_school_sql, [$school_id], "i");
    $result_school = $stmt_fetch_school->get_result();

    if ($result_school->num_rows > 0) {
        $school_data = $result_school->fetch_assoc();
        $school_code = strtolower($school_data['school_code']); // Set the school_code to lowercase
    } else {
        $_SESSION['error_message'] = "School not found.";
        echo json_encode(["status" => "error", "message" => "School not found."]);
        exit();
    }
}

function executeQuery($conn, $sql, $params, $paramTypes)
{
    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        throw new Exception("Prepare failed: " . htmlspecialchars($conn->error));
    }
    $stmt->bind_param($paramTypes, ...$params);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . htmlspecialchars($stmt->error));
    }
    return $stmt;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $firstname = ucwords(strtolower(trim($_POST['firstname'])));
    $lastname = ucwords(strtolower(trim($_POST['lastname'])));
    $middleinitial = ucwords(strtolower(trim($_POST['middleinitial'])));
    $age = intval($_POST['age']);
    $gender = trim($_POST['gender']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $assign_game = ($role === 'Committee') ? ($_POST['assign_game'] ?? null) : null;
    $department = $_POST['department'];

    // Validation checks
    if (empty($firstname) || empty($lastname) || empty($email) || empty($role) || empty($department) || empty($school_id)) {
        echo json_encode(["status" => "error", "message" => "Please fill in all required fields."]);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => "error", "message" => "Invalid email format."]);
        exit();
    }

    // Additional check for committee role
    if ($role === 'committee' && empty($assign_game)) {
        echo json_encode(["status" => "error", "message" => "Please assign a game to the committee member."]);
        exit();
    }

    try {
        $check_email_sql = "SELECT * FROM users WHERE email = ?";
        $stmt = executeQuery($conn, $check_email_sql, [$email], "s");
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo json_encode(["status" => "error", "message" => "User already exists!"]);
            exit();
        }

        // Determine the password based on the role and school_code
        $password_to_use = generateSecurePassword($school_code, $role);
        $hashed_password = password_hash($password_to_use, PASSWORD_DEFAULT);

        $conn->begin_transaction();

        $insert_user_sql = "INSERT INTO users (firstname, lastname, middleinitial, age, gender, email, password, role, department, game_id, school_id) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = executeQuery(
            $conn,
            $insert_user_sql,
            [$firstname, $lastname, $middleinitial, $age, $gender, $email, $hashed_password, $role, $department, $assign_game, $school_id],
            "sssissssiis"
        );

        $conn->commit();

        // Fetch department name based on department ID
        $department_name = null;
        $fetch_department_sql = "SELECT department_name FROM departments WHERE id = ?";
        $stmt_fetch_department = executeQuery($conn, $fetch_department_sql, [$department], "i");
        $result_department = $stmt_fetch_department->get_result();

        if ($result_department->num_rows > 0) {
            $department_data = $result_department->fetch_assoc();
            $department_name = $department_data['department_name'];
        } else {
            echo json_encode(["status" => "error", "message" => "Department not found."]);
            exit();
        }

        // Fetch game name if the role is Committee and game_id is provided
        $game_name = null;
        if ($role === 'Committee' && !empty($assign_game)) {
            $fetch_game_sql = "SELECT game_name FROM games WHERE game_id = ?";
            $stmt_fetch_game = executeQuery($conn, $fetch_game_sql, [$assign_game], "i");
            $result_game = $stmt_fetch_game->get_result();

            if ($result_game->num_rows > 0) {
                $game_data = $result_game->fetch_assoc();
                $game_name = $game_data['game_name'];
            } else {
                echo json_encode(["status" => "error", "message" => "Game not found."]);
                exit();
            }
        }

        // Generate dynamic description for logging
        $fullName = $firstname . ' ' . $lastname;
        $description = "";

        if (strtolower($role) === 'committee') {
            $description = "Registered \"$fullName\" as a committee member for \"$game_name\" in the \"$department_name\" department.";
        } elseif (strtolower($role) === 'department admin') {
            $description = "Registered \"$fullName\" as a department admin for the \"$department_name\" department.";
        } else {
            $description = "Registered \"$fullName\" as a \"$role\".";
        }
        // Log user action
        logUserAction($conn, $_SESSION['user_id'], 'users', 'CREATE', null, $description);

        // Send registration email
        require_once __DIR__ . "/../send-registration-email.php";

        // Get school name for email
        $school_name_sql = "SELECT school_name FROM schools WHERE school_id = ?";
        $stmt_school = executeQuery($conn, $school_name_sql, [$school_id], "i");
        $school_result = $stmt_school->get_result();
        $school_name = $school_result->fetch_assoc()['school_name'];

        $emailResult = sendUserRegistrationEmail($email, $firstname, $password_to_use, $role, $school_name);

        if ($emailResult['success']) {
            echo json_encode([
                "status" => "success",
                "message" => $emailResult['message']
            ]);
        } else {
            echo json_encode([
                "status" => "warning",
                "message" => "User added successfully but " . $emailResult['message']
            ]);
        }
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(["status" => "error", "message" => "An error occurred: " . $e->getMessage()]);
        exit();
    }
}
