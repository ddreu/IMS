<?php
include_once '../connection/conn.php';
$conn = con();

// Create logs directory if it doesn't exist
$logsDir = __DIR__ . '/../logs';
if (!file_exists($logsDir)) {
    mkdir($logsDir, 0777, true);
}

// Define log file path
$logFile = $logsDir . '/error_logs_notify.txt';

// Function to write to log file
function writeToLog($message, $type = 'INFO') {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp][$type] $message\n";
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

// ISMS.XYZ API configuration
$apiKey = 'YOUR_ISMS_API_KEY'; // Replace with your actual ISMS.XYZ API key

writeToLog("Script started with API Key: " . substr($apiKey, 0, 5) . '...');

// Function to send SMS using ISMS.XYZ
function sendSMS($number, $message) {
    // Remove any '+' from the number and ensure it starts with '60' for Malaysia or '63' for Philippines
    $number = ltrim($number, '+');
    
    $apiKey = 'YOUR_ISMS_API_KEY'; // Replace with your ISMS.XYZ API key
    $url = 'https://isms.xyz/api/send';
    
    $params = [
        'key' => $apiKey,
        'phone' => $number,
        'message' => $message,
        'type' => 'text'  // Can be 'text' or 'unicode'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    writeToLog("API Response (HTTP $httpCode): " . $response);
    
    return $httpCode == 200;
}

// Function to send game notification
function sendGameNotification($phoneNumber, $message) {
    return sendSMS($phoneNumber, $message);
}

// Validate phone number
function isValidPhoneNumber($phoneNumber) {
    return preg_match('/^\+?[1-9]\d{1,14}$/', $phoneNumber);
}

// Fetch game name using game_id
function getGameName($gameId, $conn) {
    $sql = "SELECT game_name FROM games WHERE game_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $gameId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['game_name'] ?? "Unknown Game";
}

// Fetch team name using team_id
function getTeamName($teamId, $conn) {
    $sql = "SELECT team_name FROM teams WHERE team_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $teamId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    return $result['team_name'] ?? "Unknown Team";
}

// Fetch player phone numbers using team_id
function getTeamPlayerNumbers($teamId, $conn) {
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
            writeToLog("Found player number: {$row['player_firstname']} {$row['player_lastname']} - $phoneNumber");
            $phoneNumbers[] = $phoneNumber;
        }
    }
    return $phoneNumbers;
}

// Get POST data
$scheduleId = $_POST['schedule_id'];
$teamAId = $_POST['teamA_id'];
$teamBId = $_POST['teamB_id'];

writeToLog("Received POST data - Schedule ID: $scheduleId, Team A: $teamAId, Team B: $teamBId");

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
    
    writeToLog("Retrieved schedule details: " . json_encode($result));

    $gameId = $result['game_id'];
    $scheduleDate = date("M d, Y", strtotime($result['schedule_date']));
    $scheduleTime = date("g:i A", strtotime($result['schedule_time']));
    $venue = $result['venue'];

    // Fetch game and team names
    $gameName = getGameName($gameId, $conn);
    $teamAName = getTeamName($teamAId, $conn);
    $teamBName = getTeamName($teamBId, $conn);
    
    writeToLog("Game: $gameName, Team A: $teamAName, Team B: $teamBName");

    // SMS body
    $smsBody = "Game: $gameName\n" .
        "$teamAName vs $teamBName\n" .
        "Date: $scheduleDate\n" .
        "Time: $scheduleTime\n" .
        "Venue: $venue\n" .
        "The match is starting soon, get ready!";
    
    writeToLog("SMS Body prepared: $smsBody");

    // Send messages to Team A players
    $teamAPhoneNumbers = getTeamPlayerNumbers($teamAId, $conn);
    writeToLog("Found " . count($teamAPhoneNumbers) . " players for Team A");
    
    foreach ($teamAPhoneNumbers as $number) {
        if (isValidPhoneNumber($number)) {
            $result = sendGameNotification($number, $smsBody);
            if ($result === false) {
                writeToLog("Failed to send SMS to Team A player: $number", 'ERROR');
            }
        } else {
            writeToLog("Invalid phone number format for Team A player: $number", 'WARNING');
        }
    }

    // Send messages to Team B players
    $teamBPhoneNumbers = getTeamPlayerNumbers($teamBId, $conn);
    writeToLog("Found " . count($teamBPhoneNumbers) . " players for Team B");
    
    foreach ($teamBPhoneNumbers as $number) {
        if (isValidPhoneNumber($number)) {
            $result = sendGameNotification($number, $smsBody);
            if ($result === false) {
                writeToLog("Failed to send SMS to Team B player: $number", 'ERROR');
            }
        } else {
            writeToLog("Invalid phone number format for Team B player: $number", 'WARNING');
        }
    }

    writeToLog("Script completed successfully");
    echo "success";
    
} catch (Exception $e) {
    writeToLog("Error: " . $e->getMessage(), 'ERROR');
    echo "error: " . $e->getMessage();
}
