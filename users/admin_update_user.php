<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = intval($_POST['user_id']);

    $firstname = ucwords(strtolower(trim($_POST['firstname'])));
    $lastname = ucwords(strtolower(trim($_POST['lastname'])));
    $middleinitial = strtoupper(substr(trim($_POST['middleinitial']), 0, 1));
    $age = intval($_POST['age']);
    $gender = trim($_POST['gender']);
    $email = trim($_POST['email']);
    $role = trim($_POST['role']);
    $school_id = intval($_POST['school_id'] ?? 0);

    $game_ids = isset($_POST['game_ids']) && is_array($_POST['game_ids']) ? array_filter($_POST['game_ids']) : [];
    $main_game_id = count($game_ids) > 0 ? intval($game_ids[0]) : null;

    $department_ids = isset($_POST['department_ids']) && is_array($_POST['department_ids']) ? array_filter($_POST['department_ids']) : [];
    $main_department_id = count($department_ids) > 0 ? intval($department_ids[0]) : null;

    if (!$firstname || !$lastname || !$email || !$school_id) {
        echo json_encode(["status" => "error", "message" => "Please fill in all required fields."]);
        exit;
    }

    // Check duplicate email
    $stmt_check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt_check->bind_param("si", $email, $user_id);
    $stmt_check->execute();
    if ($stmt_check->get_result()->num_rows > 0) {
        echo json_encode(["status" => "error", "message" => "Email already exists!"]);
        exit;
    }

    // Update base user info
    $sql_update = "UPDATE users SET firstname = ?, lastname = ?, middleinitial = ?, age = ?, gender = ?, email = ?, role = ?, department = ?, school_id = ?, game_id = ? WHERE id = ?";
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
        $main_department_id,
        $school_id,
        $main_game_id,
        $user_id
    );

    if ($stmt_update->execute()) {

        // === COMMITTEE-SPECIFIC LOGIC ===
        if ($role === 'Committee') {
            // Reset committee games
            $conn->query("DELETE FROM committee_games WHERE committee_id = $user_id");
            $extra_game_ids = array_filter($game_ids, fn($id) => intval($id) !== $main_game_id);

            if ($extra_game_ids) {
                $stmt_game = $conn->prepare("INSERT INTO committee_games (committee_id, game_id, assigned_at) VALUES (?, ?, NOW())");
                foreach ($extra_game_ids as $gid) {
                    $stmt_game->bind_param("ii", $user_id, $gid);
                    $stmt_game->execute();
                }
                $stmt_game->close();
            }

            // Reset committee departments
            $conn->query("DELETE FROM committee_departments WHERE committee_id = $user_id");
            $extra_department_ids = array_filter($department_ids, fn($id) => intval($id) !== $main_department_id);

            if ($extra_department_ids) {
                $stmt_dept = $conn->prepare("INSERT INTO committee_departments (committee_id, department_id, assigned_at) VALUES (?, ?, NOW())");
                foreach ($extra_department_ids as $did) {
                    $stmt_dept->bind_param("ii", $user_id, $did);
                    $stmt_dept->execute();
                }
                $stmt_dept->close();
            }
        } else {
            // Clean committee tables if user is no longer a committee
            $conn->query("DELETE FROM committee_games WHERE committee_id = $user_id");
            $conn->query("DELETE FROM committee_departments WHERE committee_id = $user_id");
        }

        echo json_encode(["status" => "success", "message" => "User updated successfully."]);
    } else {
        echo json_encode(["status" => "error", "message" => "Error updating user: " . $stmt_update->error]);
    }
}
