<?php
include_once '../connection/conn.php';
include '../user_logs/logger.php';
$conn = con();
session_start();
$user_id = $_SESSION['user_id'];


// ClickSend API configuration
$username = 'andrewbucedeguzman@gmail.com'; // Replace with your ClickSend username
$apiKey = 'D738671F-D6A4-9B1F-D0C5-414F879FF2D7'; // Replace with your ClickSend API key

// Validate phone number
function isValidPhoneNumber($phoneNumber)
{
    return preg_match('/^\+?[1-9]\d{1,14}$/', $phoneNumber);
}

// Fetch game name using game_id
function getGameName($gameId, $conn)
{
    $sql = "SELECT game_name FROM games WHERE game_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $gameId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['game_name'] ?? "Unknown Game";
}

// Fetch team name using team_id
function getTeamName($teamId, $conn)
{
    $sql = "SELECT team_name FROM teams WHERE team_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $teamId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['team_name'] ?? "Unknown Team";
}

// Fetch player phone numbers using team_id
function getTeamPlayerNumbers($teamId, $conn)
{
    $sql = "SELECT 
                p.player_id,
                p.player_firstname,
                p.player_lastname,
                p.jersey_number,
                pi.phone_number as contact_number
            FROM players p
            INNER JOIN players_info pi ON p.player_id = pi.player_id
            WHERE p.team_id = ?";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $teamId);
    $stmt->execute();
    $result = $stmt->get_result();
    $phoneNumbers = [];
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['contact_number'])) {
            $phoneNumber = $row['contact_number'];
            if (substr($phoneNumber, 0, 1) === '0') {
                $phoneNumber = '+63' . substr($phoneNumber, 1);
            }
            $phoneNumbers[] = $phoneNumber;
        }
    }
    return $phoneNumbers;
}

// Function to send SMS using ClickSend
function sendClickSendSMS($number, $message, $username, $apiKey)
{
    // Prepare the ClickSend API request
    $url = 'https://rest.clicksend.com/v3/sms/send';

    $data = [
        'messages' => [
            [
                'source' => 'php',
                'from' => 'YourSenderID',  // Your sender ID (or phone number)
                'to' => $number,
                'body' => $message,
            ],
        ],
    ];

    $headers = [
        'Content-Type: application/json',
        'Authorization: Basic ' . base64_encode($username . ':' . $apiKey),
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode != 200) {
        return false;
    }

    return $response;
}

// Get POST data
$scheduleId = $_POST['schedule_id'];
$teamAId = $_POST['teamA_id'];
$teamBId = $_POST['teamB_id'];

try {
    // Set timezone to Philippines Time (Asia/Manila)
    date_default_timezone_set('Asia/Manila');

    // Fetch schedule details
    $sql = "SELECT b.game_id, s.schedule_date, s.schedule_time, s.venue 
            FROM schedules s
            INNER JOIN matches m ON s.match_id = m.match_id
            INNER JOIN brackets b ON m.bracket_id = b.bracket_id 
            WHERE s.schedule_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $scheduleId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if (!$result) {
        throw new Exception("Schedule not found");
    }

    $gameId = $result['game_id'];
    $scheduleDate = date("M d, Y", strtotime($result['schedule_date']));
    $scheduleTime = date("g:i A", strtotime($result['schedule_time']));
    $venue = $result['venue'];

    // Fetch game and team names
    $gameName = getGameName($gameId, $conn);
    $teamAName = getTeamName($teamAId, $conn);
    $teamBName = getTeamName($teamBId, $conn);

    // SMS body
    $smsBody = "Game: $gameName\n" .
        "$teamAName vs $teamBName\n" .
        "Date: $scheduleDate\n" .
        "Time: $scheduleTime\n" .
        "Venue: $venue\n" .
        "The match is starting soon, get ready!";

    // Send messages to Team A players
    $teamAPhoneNumbers = getTeamPlayerNumbers($teamAId, $conn);
    $totalNotifiedPlayers = 0;

    foreach ($teamAPhoneNumbers as $number) {
        if (isValidPhoneNumber($number)) {
            $result = sendClickSendSMS($number, $smsBody, $username, $apiKey);
            if ($result !== false) {
                $totalNotifiedPlayers++;
            }
        }
    }

    // Send messages to Team B players
    $teamBPhoneNumbers = getTeamPlayerNumbers($teamBId, $conn);

    foreach ($teamBPhoneNumbers as $number) {
        if (isValidPhoneNumber($number)) {
            $result = sendClickSendSMS($number, $smsBody, $username, $apiKey);
            if ($result !== false) {
                $totalNotifiedPlayers++;
            }
        }
    }

    // Log user action
    $description = "Notified players for match $teamAName vs $teamBName. Total number of players notified is $totalNotifiedPlayers.";

    // Log user action (custom operation like 'SEND_SMS')
    logUserAction($conn, $user_id, 'Notificaion', 'Player Notification', $scheduleId, $description);

    echo "success";
} catch (Exception $e) {
    echo "error: " . $e->getMessage();
}
