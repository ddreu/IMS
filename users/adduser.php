<?php
session_start();
ob_start();
include '../connection/conn.php';
include '../includes/password_utils.php';
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
        $check_email_sql = "SELECT * FROM Users WHERE email = ?";
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

        // After user is successfully added to the database
        require_once __DIR__ . "/../send-registration-email.php";

        // Get school name for email
        $school_name_sql = "SELECT school_name FROM schools WHERE school_id = ?";
        $stmt_school = executeQuery($conn, $school_name_sql, [$school_id], "i");
        $school_result = $stmt_school->get_result();
        $school_name = $school_result->fetch_assoc()['school_name'];

        // Send registration email with credentials
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
