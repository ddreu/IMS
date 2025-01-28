<?php
include_once '../connection/conn.php';
include '../user_logs/logger.php';
$conn = con();
session_start();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $player_id = intval($_POST['player_id']);
    $lastname = htmlspecialchars($_POST['player_lastname']);
    $firstname = htmlspecialchars($_POST['player_firstname']);
    $middlename = htmlspecialchars($_POST['player_middlename']);
    $jersey_number = intval($_POST['jersey_number']);
    $email = htmlspecialchars($_POST['email']);

    // Format phone number
    $phone_number = htmlspecialchars($_POST['phone_number']);
    $phone_number = preg_replace('/[^0-9]/', '', $phone_number);
    if (strlen($phone_number) >= 10) {
        if (substr($phone_number, 0, 2) === '63') {
            $phone_number = '+' . $phone_number;
        } else if (substr($phone_number, 0, 1) === '0') {
            $phone_number = '+63' . substr($phone_number, 1);
        } else if (strlen($phone_number) === 10) {
            $phone_number = '+63' . $phone_number;
        }
    }

    $date_of_birth = $_POST['date_of_birth'];
    $height = htmlspecialchars($_POST['height']);
    $weight = htmlspecialchars($_POST['weight']);
    $position = htmlspecialchars($_POST['position']);

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Get current player data for logging
        $sql_old_data = "SELECT * FROM players p JOIN players_info pi ON p.player_id = pi.player_id WHERE p.player_id = ?";
        $stmt_old = $conn->prepare($sql_old_data);
        $stmt_old->bind_param("i", $player_id);
        $stmt_old->execute();
        $old_data_result = $stmt_old->get_result();
        $old_data = $old_data_result->fetch_assoc();
        $stmt_old->close();

        // Check if the jersey number is already taken
        $sql_check = "SELECT p.player_id, p.player_firstname, p.player_lastname 
                      FROM players p 
                      WHERE p.jersey_number = ? 
                      AND p.team_id = ? 
                      AND p.player_id != ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("iii", $jersey_number, $old_data['team_id'], $player_id);
        $stmt_check->execute();
        $result_check = $stmt_check->get_result();

        if ($existing_player = $result_check->fetch_assoc()) {
            $stmt_check->close();
            throw new Exception("Jersey number #$jersey_number is already taken by {$existing_player['player_firstname']} {$existing_player['player_lastname']} in this team.");
        }
        $stmt_check->close();

        // Update players table
        $sql = "UPDATE players SET 
                player_lastname = ?, 
                player_firstname = ?, 
                player_middlename = ?, 
                jersey_number = ? 
                WHERE player_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssii", $lastname, $firstname, $middlename, $jersey_number, $player_id);

        if (!$stmt->execute()) {
            throw new Exception("Error updating player details: " . $stmt->error);
        }

        // Handle the image update
        $image_path = null;
        if (isset($_FILES['picture']) && $_FILES['picture']['error'] == UPLOAD_ERR_OK) {
            $file_tmp = $_FILES['picture']['tmp_name'];
            $file_name = time() . "_" . basename($_FILES['picture']['name']);
            $file_name = preg_replace("/[^a-zA-Z0-9_\-\.]/", "", $file_name);
            $target_dir = "../uploads/players/";
            $target_file = $target_dir . $file_name;

            if (!is_dir($target_dir)) {
                mkdir($target_dir, 0777, true);
            }

            if (move_uploaded_file($file_tmp, $target_file)) {
                $image_path = $target_file;
            } else {
                throw new Exception("Error uploading image.");
            }
        }

        // Update players_info table
        $sql_info = "UPDATE players_info SET 
                    email = ?, 
                    phone_number = ?, 
                    date_of_birth = ?, 
                    height = ?, 
                    weight = ?, 
                    position = ?"
            . ($image_path ? ", picture = ?" : "") .
            " WHERE player_id = ?";

        $stmt_info = $conn->prepare($sql_info);

        if ($image_path) {
            $stmt_info->bind_param("sssssssi", $email, $phone_number, $date_of_birth, $height, $weight, $position, $image_path, $player_id);
        } else {
            $stmt_info->bind_param("ssssssi", $email, $phone_number, $date_of_birth, $height, $weight, $position, $player_id);
        }

        if (!$stmt_info->execute()) {
            throw new Exception("Error updating player info: " . $stmt_info->error);
        }

        // Log only changes
        $changes = [];
        $fields_to_check = [
            "player_lastname" => $lastname,
            "player_firstname" => $firstname,
            "player_middlename" => $middlename,
            "jersey_number" => $jersey_number,
            "email" => $email,
            "phone_number" => $phone_number,
            "date_of_birth" => $date_of_birth,
            "height" => $height,
            "weight" => $weight,
            "position" => $position,
            "picture" => $image_path ?? $old_data['picture']
        ];

        foreach ($fields_to_check as $field => $new_value) {
            if ($old_data[$field] != $new_value) {
                $changes[$field] = ["old" => $old_data[$field], "new" => $new_value];
            }
        }

        if (!empty($changes)) {
            $description = "Updated player details for " . $old_data['player_lastname'] . ", " . $old_data['player_firstname'] .
                ". Changes: " . json_encode($changes);
            logUserAction($conn, $_SESSION['user_id'], 'Players', 'Update', $player_id, $description);
        }

        // Commit transaction
        $conn->commit();
        echo json_encode(["success" => true, "message" => "Player updated successfully"]);
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    } finally {
        if (isset($stmt)) $stmt->close();
        if (isset($stmt_info)) $stmt_info->close();
        $conn->close();
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method"]);
}
