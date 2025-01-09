<?php
include_once '../connection/conn.php';
$conn = con();
require_once __DIR__ . '/../vendor/autoload.php'; // Adjust path for Twilio library

use Twilio\Rest\Client;

// Twilio credentials
$sid = 'AC754665dcfe78d5d33e861e0401f46dd8';
$token = 'de48ada0cd0e9548fe50e2f313072e5a';
$twilio_number = '+14142061316';

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
    return $result['game_name'] ?? "Unknown Game"; // Default if not found
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
            // Format the phone number to include country code if needed
            $phoneNumber = $row['contact_number'];
            if (substr($phoneNumber, 0, 1) === '0') {
                $phoneNumber = '+63' . substr($phoneNumber, 1);
            }
            $phoneNumbers[] = $phoneNumber;
        }
    }
    return $phoneNumbers;
}

// Get POST data
$scheduleId = $_POST['schedule_id'];
$teamAId = $_POST['teamA_id'];
$teamBId = $_POST['teamB_id'];

try {
    // Set timezone to Philippines Time (Asia/Manila)
    date_default_timezone_set('Asia/Manila');

    // Fetch schedule details, including game_id through joins
    $sql = "SELECT b.game_id, s.schedule_date, s.schedule_time, s.venue 
            FROM schedules s
            INNER JOIN matches m ON s.match_id = m.match_id
            INNER JOIN brackets b ON m.bracket_id = b.bracket_id 
            WHERE s.schedule_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $scheduleId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    $gameId = $result['game_id'];
    $scheduleDate = date("M d, Y", strtotime($result['schedule_date']));
    $scheduleTime = date("g:i A", strtotime($result['schedule_time']));
    $venue = $result['venue'];

    // Fetch game name
    $gameName = getGameName($gameId, $conn);

    // Fetch team names
    $teamAName = getTeamName($teamAId, $conn);
    $teamBName = getTeamName($teamBId, $conn);

    // SMS body
    $smsBody = "Game: $gameName\n" .
        "$teamAName vs $teamBName\n" .
        "Date: $scheduleDate\n" .
        "Time: $scheduleTime\n" .
        "Venue: $venue\n" .
        "The match is starting soon, get ready!";

    // Initialize Twilio client
    $client = new Client($sid, $token);

    // Send messages to Team A players
    $teamAPhoneNumbers = getTeamPlayerNumbers($teamAId, $conn);
    foreach ($teamAPhoneNumbers as $number) {
        try {
            if (isValidPhoneNumber($number)) {
                $client->messages->create($number, [
                    'from' => $twilio_number,
                    'body' => $smsBody,
                ]);
            }
        } catch (Exception $e) {
            // Skip errors and log for debugging in a custom error log file
            error_log("Error sending SMS to $number: " . $e->getMessage(), 3, __DIR__ . '/../logs/error_logs.txt');
        }
    }

    // Send messages to Team B players
    $teamBPhoneNumbers = getTeamPlayerNumbers($teamBId, $conn);
    foreach ($teamBPhoneNumbers as $number) {
        try {
            if (isValidPhoneNumber($number)) {
                $client->messages->create($number, [
                    'from' => $twilio_number,
                    'body' => $smsBody,
                ]);
            }
        } catch (Exception $e) {
            // Skip errors and log for debugging in a custom error log file
            error_log("Error sending SMS to $number: " . $e->getMessage(), 3, __DIR__ . '/../logs/error_logs.txt');
        }
    }

    // Respond with success
    echo "success";
} catch (Exception $e) {
    // Respond with error
    echo "error: " . $e->getMessage();
}
