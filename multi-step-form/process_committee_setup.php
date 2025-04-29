<?php
session_start();
include_once '../connection/conn.php';
include '../user_logs/logger.php';
$conn = con();

header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit;
}

$user_id = $_SESSION['user_id'];
$school_id = $_SESSION['school_id'];
$department_id = $_SESSION['department_id'];

// 🛠 Fetch game_id from users table
$getGameQuery = $conn->prepare("SELECT game_id FROM users WHERE id = ?");
$getGameQuery->bind_param("i", $user_id);
$getGameQuery->execute();
$getGameQuery->bind_result($game_id);
$getGameQuery->fetch();
$getGameQuery->close();

if (empty($game_id)) {
    echo json_encode(['success' => false, 'message' => 'No assigned game found.']);
    exit;
}

// 🛠 Fetch game_name from games table
$getGameNameQuery = $conn->prepare("SELECT game_name FROM games WHERE game_id = ?");
$getGameNameQuery->bind_param("i", $game_id);
$getGameNameQuery->execute();
$getGameNameQuery->bind_result($game_name);
$getGameNameQuery->fetch();
$getGameNameQuery->close();

// 🛡️ Double-check game name
if (empty($game_name)) {
    echo json_encode(['success' => false, 'message' => 'Game name not found.']);
    exit;
}

// 🧠 Now store both game_id and game_name in session
$_SESSION['game_id'] = $game_id;
$_SESSION['game_name'] = $game_name;

$game_type = $data['game_type'] ?? '';
$stats = $data['stats'] ?? [];

if (empty($game_type)) {
    echo json_encode(['success' => false, 'message' => 'Game type is required.']);
    exit;
}

// 🛠 Insert or Update game_scoring_rules
$query = "
INSERT INTO game_scoring_rules (game_id, department_id, school_id, game_type, scoring_unit, score_increment_options, period_type, number_of_periods, duration_per_period, timeouts_per_period, time_limit, point_cap, max_fouls)
VALUES (?, ?, ?, ?, '', '', '', 0, 0, 0, 0, 0, 0)
ON DUPLICATE KEY UPDATE
game_type = VALUES(game_type)";
$stmt = $conn->prepare($query);
$stmt->bind_param("iiis", $game_id, $department_id, $school_id, $game_type);
$stmt->execute();
$stmt->close();

// 🛠 Insert game stats
if (!empty($stats)) {
    foreach ($stats as $stat_name) {
        $stat_name = trim($stat_name);
        if (!empty($stat_name)) {
            $insert_stat = $conn->prepare("INSERT INTO game_stats_config (game_id, stat_name) VALUES (?, ?)");
            $insert_stat->bind_param("is", $game_id, $stat_name);
            $insert_stat->execute();
            $insert_stat->close();
        }
    }
}

// 🛠 Update user first_login to 'no'
$update_user = $conn->prepare("UPDATE users SET first_login = 'no' WHERE id = ?");
$update_user->bind_param("i", $user_id);
$update_user->execute();
$update_user->close();

// ✨ Set success session message
$_SESSION['success_message'] = "Game Setup Completed Successfully! Welcome to your Dashboard!";
$_SESSION['success_type'] = "Committee";

// 📝 Log
logUserAction($conn, $user_id, 'Onboarding', 'Finished', null, 'Committee onboarding completed.');

$conn->close();

echo json_encode(['success' => true, 'redirect' => '../committee/committeedashboard.php']);
exit;
