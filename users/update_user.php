<?php
session_start();
include_once '../connection/conn.php';
include "../user_logs/logger.php"; // Include the logger function
$conn = con();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id']);
    $firstname = ucwords(strtolower(trim($_POST['firstname'])));
    $lastname = ucwords(strtolower(trim($_POST['lastname'])));
    $middleinitial = ucwords(strtolower(trim($_POST['middleinitial'])));
    $age = intval($_POST['age']);
    $gender = trim($_POST['gender']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    $games = ($role === "Department Admin") ? null : (isset($_POST['assign_game']) ? intval($_POST['assign_game']) : null); // Updated to use 'assign_game'
    $department = intval($_POST['department']);

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
    } else {
        // Fetch current user details for comparison
        $stmt_fetch = $conn->prepare("SELECT * FROM users WHERE id = ?");
        $stmt_fetch->bind_param("i", $user_id);
        $stmt_fetch->execute();
        $result_fetch = $stmt_fetch->get_result();
        $current_user = $result_fetch->fetch_assoc();
        $stmt_fetch->close();

        // Fetch the department name for the new department
        $stmt_department = $conn->prepare("SELECT department_name FROM departments WHERE id = ?");
        $stmt_department->bind_param("i", $department);
        $stmt_department->execute();
        $result_department = $stmt_department->get_result();
        $department_name = $result_department->num_rows > 0 ? $result_department->fetch_assoc()['department_name'] : null;
        $stmt_department->close();

        // Fetch the old department name (for comparison)
        $stmt_old_department = $conn->prepare("SELECT department_name FROM departments WHERE id = ?");
        $stmt_old_department->bind_param("i", $current_user['department']);
        $stmt_old_department->execute();
        $result_old_department = $stmt_old_department->get_result();
        $old_department_name = $result_old_department->num_rows > 0 ? $result_old_department->fetch_assoc()['department_name'] : null;
        $stmt_old_department->close();

        // Fetch the current game_name from the users table if assigned
        $current_game_id = $current_user['game_id'];
        $game_name = null;
        if ($current_game_id) {
            $stmt_game = $conn->prepare("SELECT game_name FROM games WHERE game_id = ?");
            $stmt_game->bind_param("i", $current_game_id);
            $stmt_game->execute();
            $result_game = $stmt_game->get_result();
            if ($result_game->num_rows > 0) {
                $game_name = $result_game->fetch_assoc()['game_name'];
            }
            $stmt_game->close();
        }

        // Determine the new game_id
        $new_game_id = ($role !== "Department Admin" && isset($_POST['assign_game'])) ? intval($_POST['assign_game']) : null;

        // If the new game_id is different from the current one, fetch the new game_name
        $new_game_name = null;
        if ($new_game_id && $new_game_id !== $current_game_id) {
            $stmt_game = $conn->prepare("SELECT game_name FROM games WHERE game_id = ?");
            $stmt_game->bind_param("i", $new_game_id);
            $stmt_game->execute();
            $result_game = $stmt_game->get_result();
            if ($result_game->num_rows > 0) {
                $new_game_name = $result_game->fetch_assoc()['game_name'];
            }
            $stmt_game->close();
        }

        // Prepare the SQL update statement
        if ($role === "departmentadmin") {
            $sql_update = "UPDATE users SET firstname = ?, lastname = ?, middleinitial = ?, age = ?, gender = ?, email = ?, role = ?, department = NULL, game_id = NULL WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("sssissssi", $firstname, $lastname, $middleinitial, $age, $gender, $email, $role, $user_id);
        } else {
            $sql_update = "UPDATE users SET firstname = ?, lastname = ?, middleinitial = ?, age = ?, gender = ?, email = ?, role = ?, game_id = ?, department = ? WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("sssisssiii", $firstname, $lastname, $middleinitial, $age, $gender, $email, $role, $new_game_id, $department, $user_id);
        }

        // Execute the prepared statement
        if ($stmt_update->execute()) {
            // Prepare description for logging based on the changes
            $description = "Updated user details for \"$firstname $lastname\" ($role)";
            $changes = [];

            // Compare current and new values to detect changes
            if ($firstname !== $current_user['firstname']) $changes[] = "First Name: {$current_user['firstname']} → $firstname";
            if ($lastname !== $current_user['lastname']) $changes[] = "Last Name: {$current_user['lastname']} → $lastname";
            if ($middleinitial !== $current_user['middleinitial']) $changes[] = "Middle Initial: {$current_user['middleinitial']} → $middleinitial";
            if ($age !== $current_user['age']) $changes[] = "Age: {$current_user['age']} → $age";
            if ($gender !== $current_user['gender']) $changes[] = "Gender: {$current_user['gender']} → $gender";
            if ($email !== $current_user['email']) $changes[] = "Email: {$current_user['email']} → $email";
            if ($role !== $current_user['role']) $changes[] = "Role: {$current_user['role']} → $role";
            if ($new_game_id !== $current_game_id) {
                $changes[] = "Game: " . ($game_name ?? "None") . " → " . ($new_game_name ?? "None");
            }
            if ($old_department_name !== $department_name) {
                $changes[] = "Department: $old_department_name → $department_name";
            }

            // Add changes to description
            if (count($changes) > 0) {
                $description .= ": " . implode(", ", $changes);
            } else {
                $description .= " (No changes made)";
            }

            // Log the action using logUserAction
            logUserAction(
                $conn,
                $_SESSION['user_id'], // Logged-in user performing the action
                'Users',              // Table name
                'UPDATE',             // Operation type
                $user_id,             // Record ID of updated user
                $description,         // Description of the operation
                json_encode($current_user), // Previous data (before update)
                json_encode($_POST)  // New data (after update)
            );

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
        $stmt_update->close();
    }
}
