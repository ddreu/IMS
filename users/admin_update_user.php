<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id']);

    // Sanitize and format input data
    $firstname = ucwords(strtolower(trim($_POST['firstname'])));
    $lastname = ucwords(strtolower(trim($_POST['lastname'])));
    $middleinitial = strtoupper(trim($_POST['middleinitial']));
    if (strlen($middleinitial) > 1) {
        $middleinitial = strtoupper(substr($middleinitial, 0, 1));
    }
    $age = intval($_POST['age']);
    $gender = trim($_POST['gender']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    $department = !empty($_POST['department']) ? intval($_POST['department']) : null;
    $school_id = !empty($_POST['school_id']) ? intval($_POST['school_id']) : null;
    $games = ($role === "Department Admin") ? null : (!empty($_POST['game_id']) ? intval($_POST['game_id']) : null);

    // Validate required fields
    if (empty($firstname) || empty($lastname) || empty($email) || empty($school_id)) {
        echo json_encode([
            "status" => "error",
            "message" => "Please fill in all required fields."
        ]);
        exit;
    }

    // Check if email is already taken
    $check_email_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
    $stmt_check = $conn->prepare($check_email_sql);
    $stmt_check->bind_param("si", $email, $user_id);
    $stmt_check->execute();
    $result = $stmt_check->get_result();

    if ($result->num_rows > 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Email already exists!"
        ]);
        exit;
    }

    // Update query
    $sql_update = "UPDATE users 
                   SET firstname = ?, lastname = ?, middleinitial = ?, age = ?, gender = ?, 
                       email = ?, role = ?, department = ?, school_id = ?, game_id = ?
                   WHERE id = ?";

    $stmt_update = $conn->prepare($sql_update);
    $stmt_update->bind_param(
        "sssisssiiii",
        $firstname,
        $lastname,
        $middleinitial,
        $age,
        $gender,
        $email,
        $role,
        $department,
        $school_id,
        $games,
        $user_id
    );

    if ($stmt_update->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "User updated successfully."
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Error updating user: " . $stmt_update->error
        ]);
    }
}
