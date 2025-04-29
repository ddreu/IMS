<?php
session_start();
require '../connection/conn.php';
$conn = con();

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$school_id = $_SESSION['school_id'];
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

try {
    // Insert games
    foreach ($data['gameConfigs'] as $game) {
        $stmt = $conn->prepare("INSERT INTO games (game_name, number_of_players, category, environment, created_at, school_id, is_archived) VALUES (?, ?, ?, ?, NOW(), ?, 0)");
        $stmt->bind_param("sissi", $game['gameName'], $game['players'], $game['category'], $game['environment'], $school_id);
        $stmt->execute();
    }

    // Insert pointing system
    $stmt = $conn->prepare("INSERT INTO pointing_system (school_id, first_place_points, second_place_points, third_place_points, created_at, is_archived) VALUES (?, ?, ?, ?, NOW(), 0)");
    $stmt->bind_param("iiii", $school_id, $data['points']['first'], $data['points']['second'], $data['points']['third']);
    $stmt->execute();

    // Update user(s) first_login to 'no'
    if ($role == 'School Admin') {
        $stmt = $conn->prepare("UPDATE users SET first_login = 'no' WHERE school_id = ? AND role = 'School Admin'");
        $stmt->bind_param("i", $school_id);
        $stmt->execute();


        $_SESSION['success_message'] = "School Setup Completed Successfully! Welcome to your Dashboard!";
        $_SESSION['success_type'] = "School Admin";
        $redirect = '../school_admin/schooladmindashboard.php';

        // ✅ FINAL RESPONSE
        echo json_encode(['success' => true, 'redirect' => $redirect]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Unauthorized role.']);
        exit;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error.']);
    exit;
}
