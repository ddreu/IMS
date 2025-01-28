<?php
session_start();
include_once '../connection/conn.php';
include '../user_logs/logger.php';
$conn = con();
$grade_section_course_id = isset($_GET['grade_section_course_id']) ? intval($_GET['grade_section_course_id']) : null;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Collect and sanitize player data
    $player_lastname = htmlspecialchars($_POST['player_lastname']);
    $player_firstname = htmlspecialchars($_POST['player_firstname']);
    $player_middlename = htmlspecialchars($_POST['player_middlename']);
    $team_id = intval($_POST['team_id']);
    $jersey_number = intval($_POST['jersey_number']);

    // Get game_id and current player count for the team
    $team_query = $conn->prepare("SELECT t.team_name, t.game_id, g.number_of_players, 
        (SELECT COUNT(*) FROM players WHERE team_id = t.team_id) as current_players
        FROM teams t
        INNER JOIN games g ON t.game_id = g.game_id
        WHERE t.team_id = ?");
    $team_query->bind_param("i", $team_id);
    $team_query->execute();
    $team_result = $team_query->get_result()->fetch_assoc();

    // Extract team name for later use in logging
    $team_name = $team_result['team_name'];

    if ($team_result['current_players'] >= $team_result['number_of_players']) {
        $_SESSION['error_message'] = "Team has reached the maximum number of players (" . $team_result['number_of_players'] . ")";
        header("Location: player_registration.php?team_id=" . $team_id . "&grade_section_course_id=" . urlencode($grade_section_course_id));
        exit();
    }

    // Additional info for players_info table
    $email = htmlspecialchars($_POST['email']);
    $phone_number = htmlspecialchars($_POST['phone_number']);

    // Ensure the phone number starts with +63
    $phone_number = preg_replace("/\D/", "", $phone_number);
    if (substr($phone_number, 0, 1) == '0') {
        $phone_number = '+63' . substr($phone_number, 1);
    } elseif (substr($phone_number, 0, 3) != '+63') {
        $phone_number = '+63' . $phone_number;
    }

    if (strlen($phone_number) != 13) {
        $_SESSION['error_message'] = "Invalid phone number format. Please enter a valid phone number.";
        header("Location: player_registration.php?team_id=" . $team_id . "&grade_section_course_id=" . urlencode($grade_section_course_id));
        exit();
    }

    $date_of_birth = $_POST['date_of_birth'];
    $height = htmlspecialchars_decode($_POST['height']);
    $weight = htmlspecialchars($_POST['weight']);
    $position = htmlspecialchars($_POST['position']);

    // Handle picture upload
    $picture = null;
    if (!empty($_FILES['picture']['name'])) {
        $target_dir = "../uploads/players/";
        $picture = $target_dir . basename($_FILES['picture']['name']);
        if (!move_uploaded_file($_FILES['picture']['tmp_name'], $picture)) {
            die("Error uploading the image.");
        }
    }

    // Check if player with the same jersey number already exists in the team
    $check_player_stmt = $conn->prepare("SELECT * FROM players WHERE team_id = ? AND jersey_number = ?");
    $check_player_stmt->bind_param("ii", $team_id, $jersey_number);
    $check_player_stmt->execute();
    $existing_player = $check_player_stmt->get_result();

    if ($existing_player->num_rows > 0) {
        $_SESSION['error_message'] = "Player with this jersey number already exists in the team!";
        header("Location: player_registration.php?team_id=" . $team_id . "&grade_section_course_id=" . urlencode($grade_section_course_id));
        exit();
    }

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Insert into players table
        $stmt = $conn->prepare("INSERT INTO players (player_lastname, player_firstname, player_middlename, team_id, jersey_number) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssii", $player_lastname, $player_firstname, $player_middlename, $team_id, $jersey_number);
        $stmt->execute();
        $player_id = $stmt->insert_id;
        $stmt->close();

        // Insert into players_info table
        $stmt_info = $conn->prepare("INSERT INTO players_info (player_id, email, phone_number, date_of_birth, picture, height, weight, position) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt_info->bind_param("isssssss", $player_id, $email, $phone_number, $date_of_birth, $picture, $height, $weight, $position);
        $stmt_info->execute();
        $stmt_info->close();

        // Commit transaction
        $conn->commit();

        // Log the user action
        $description = "Registered player " . $player_lastname . ", " . $player_firstname . " to team '" . $team_name . "'";
        logUserAction($conn, $_SESSION['user_id'], 'Players', 'Register', $team_id, $description);

        // Redirect back to roster page with success message
        $_SESSION['success_message'] = "Player successfully registered!";
        header("Location: player_registration.php?team_id=" . $team_id . "&grade_section_course_id=" . urlencode($grade_section_course_id));
        exit();
    } catch (Exception $e) {
        // Roll back transaction if any error occurs
        $conn->rollback();
        die("Error registering player: " . $e->getMessage());
    }
} else {
    // Redirect if not a POST request
    header("Location: teams.php");
    exit();
}
