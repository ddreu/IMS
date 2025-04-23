<?php
session_start();
ob_start();
include '../connection/conn.php';
include '../includes/password_utils.php';
$conn = con();

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
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
    header('Content-Type: application/json');

    $firstname = ucwords(strtolower(trim($_POST['firstname'])));
    $lastname = ucwords(strtolower(trim($_POST['lastname'])));
    $middleinitial = ucwords(strtolower(trim($_POST['middleinitial'])));
    $age = intval($_POST['age']);
    $gender = trim($_POST['gender']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $school_id = $_POST['school_id'];

    // Set department and game_id to NULL by default
    // $department = null;
    $department_ids = [];

    // $game_id = null;
    $game_ids = [];

    // Only set values if they exist and are not empty
    // if (isset($_POST['department']) && !empty($_POST['department'])) {
    //     $department = $_POST['department'];
    // }


    // $department_ids = $_POST['department'];
    $department_ids = isset($_POST['department_ids']) ? $_POST['department_ids'] : [];

    if (!is_array($department_ids)) {
        $department_ids = [$department_ids]; // normalize single to array
    }

    $main_department_id = count($department_ids) > 0 ? $department_ids[0] : null;

    // if (isset($_POST['game_id']) && !empty($_POST['game_id'])) {
    //     $game_id = $_POST['game_id'];
    // }

    if (isset($_POST['game_ids']) && is_array($_POST['game_ids'])) {
        $game_ids = array_filter($_POST['game_ids']);
    }

    $main_game_id = count($game_ids) > 0 ? $game_ids[0] : null;


    // Validation checks
    if (empty($firstname) || empty($lastname) || empty($email) || empty($role) || empty($school_id)) {
        echo json_encode(["status" => "error", "message" => "Please fill in all required fields."]);
        exit();
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["status" => "error", "message" => "Invalid email format."]);
        exit();
    }

    // Role-specific validation
    // if ($role === 'Department Admin' && empty($department)) {
    if ($role === 'Department Admin' && count($department_ids) === 0) {

        echo json_encode(["status" => "error", "message" => "Please select a department for Department Admin."]);
        exit();
    }

    // if ($role === 'Committee' && (empty($department) || empty($game_id))) {
    //     echo json_encode(["status" => "error", "message" => "Please select both department and game for Committee member."]);
    //     exit();
    // }

    // if ($role === 'Committee' && (empty($department) || count($game_ids) === 0)) {
    if ($role === 'Committee' && (count($department_ids) === 0 || count($game_ids) === 0)) {

        echo json_encode(["status" => "error", "message" => "Please select both department and game for Committee member."]);
        exit();
    }


    try {
        // Check if email already exists
        $check_email_sql = "SELECT * FROM users WHERE email = ?";
        $stmt = executeQuery($conn, $check_email_sql, [$email], "s");
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            echo json_encode(["status" => "error", "message" => "User already exists!"]);
            exit();
        }

        // Get school code for password generation
        $fetch_school_sql = "SELECT school_code, school_name FROM schools WHERE school_id = ?";
        $stmt_fetch_school = executeQuery($conn, $fetch_school_sql, [$school_id], "i");
        $result_school = $stmt_fetch_school->get_result();

        if ($result_school->num_rows === 0) {
            echo json_encode(["status" => "error", "message" => "School not found."]);
            exit();
        }

        $school_data = $result_school->fetch_assoc();
        $school_code = $school_data['school_code'];
        $school_name = $school_data['school_name'];

        // Generate secure password using the utility function
        $password = generateSecurePassword($school_code, $role);
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        $conn->begin_transaction();

        // Insert user
        // $insert_user_sql = "INSERT INTO users (firstname, lastname, middleinitial, age, gender, email, password, role, department, game_id, school_id) 
        //                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        // $stmt_insert = executeQuery(
        //     $conn,
        //     $insert_user_sql,
        //     [$firstname, $lastname, $middleinitial, $age, $gender, $email, $hashed_password, $role, $department, $game_id, $school_id],
        //     "sssissssiis"
        // );

        $insert_user_sql = "INSERT INTO users (firstname, lastname, middleinitial, age, gender, email, password, role, department, game_id, school_id) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_insert = executeQuery(
            $conn,
            $insert_user_sql,
            [$firstname, $lastname, $middleinitial, $age, $gender, $email, $hashed_password, $role, $main_department_id, $main_game_id, $school_id],
            "sssissssiis"
        );
        $user_id = $stmt_insert->insert_id;

        if ($role === 'Committee' && count($game_ids) > 0) {
            // Exclude main game from insertion into committee_games
            $extra_game_ids = array_filter($game_ids, fn($gid) => intval($gid) !== intval($main_game_id));

            if (count($extra_game_ids) > 0) {
                $insert_committee_game = $conn->prepare("INSERT INTO committee_games (committee_id, game_id, assigned_at) VALUES (?, ?, NOW())");

                foreach ($extra_game_ids as $gid) {
                    $insert_committee_game->bind_param("ii", $user_id, $gid);
                    $insert_committee_game->execute();
                }

                $insert_committee_game->close();
            }
        }


        if ($role === 'Committee' && count($department_ids) > 0) {
            $extra_dept_ids = array_filter($department_ids, fn($id) => intval($id) !== intval($main_department_id));

            if (count($extra_dept_ids) > 0) {
                $insert_dept = $conn->prepare("INSERT INTO committee_departments (committee_id, department_id, assigned_at) VALUES (?, ?, NOW())");

                foreach ($extra_dept_ids as $did) {
                    $insert_dept->bind_param("ii", $user_id, $did);
                    $insert_dept->execute();
                }

                $insert_dept->close();
            }
        }

        $conn->commit();

        // Send registration email
        require_once('../send-registration-email.php');
        $emailResult = sendUserRegistrationEmail($email, $firstname, $password, $role, $school_name);

        if ($emailResult['success']) {
            echo json_encode([
                "status" => "success",
                "message" => "User added successfully and registration email sent!"
            ]);
        } else {
            echo json_encode([
                "status" => "warning",
                "message" => "User added successfully but there was an issue sending the email: " . $emailResult['message']
            ]);
        }
    } catch (Exception $e) {
        if ($conn->inTransaction()) {
            $conn->rollback();
        }
        echo json_encode([
            "status" => "error",
            "message" => "Error: " . $e->getMessage()
        ]);
    }
}
