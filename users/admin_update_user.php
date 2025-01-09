<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize input data
    $user_id = intval($_POST['user_id']);
// Sanitize and format input data
$firstname = ucwords(strtolower(trim($_POST['firstname'])));
$lastname = ucwords(strtolower(trim($_POST['lastname'])));

// Ensure middle initial is a single uppercase letter
$middleinitial = strtoupper(trim($_POST['middleinitial'])); // Convert to uppercase
if (strlen($middleinitial) > 1) {
    $middleinitial = strtoupper(substr($middleinitial, 0, 1)); // Take only the first character
}
    $age = intval($_POST['age']);
    $gender = trim($_POST['gender']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    $department = intval($_POST['department']);
    
    // Determine assigned game based on role
    $games = ($role === "Department Admin") ? null : (isset($_POST['game_id']) ? intval($_POST['game_id']) : null);

    // Check required fields
    if (empty($firstname) || empty($lastname) || empty($email) || empty($department)) {
        echo json_encode([
            "status" => "error",
            "message" => "Please fill in all required fields."
        ]);
        exit;
    }

    // Email validation
    $check_email_sql = "SELECT * FROM users WHERE email = ? AND id != ?";
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

    // Prepare the SQL update statement
    if (strtolower($role) === "department admin") {
        // If the role is Department Admin, we set game_id to NULL
        $sql_update = "UPDATE users SET firstname = ?, lastname = ?, middleinitial = ?, age = ?, gender = ?, email = ?, role = ?, department = ?, game_id = NULL WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("sssissssi", $firstname, $lastname, $middleinitial, $age, $gender, $email, $role, $department, $user_id);
    } else {
        // For other roles, we update game_id with the provided value
        $sql_update = "UPDATE users SET firstname = ?, lastname = ?, middleinitial = ?, age = ?, gender = ?, email = ?, role = ?, game_id = ?, department = ? WHERE id = ?";
        $stmt_update = $conn->prepare($sql_update);
        $stmt_update->bind_param("sssisssiii", $firstname, $lastname, $middleinitial, $age, $gender, $email, $role, $games, $department, $user_id);
    }

    // Execute the prepared statement
    if ($stmt_update->execute()) {
        echo json_encode([
            "status" => "success",
            "message" => "User  updated successfully."
        ]);
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "Error updating user: " . $stmt_update->error
        ]);
    }
}
?>