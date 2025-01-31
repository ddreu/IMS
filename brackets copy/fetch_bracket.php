<?php
require_once '../connection/conn.php';
session_start();
$conn = con();
header('Content-Type: application/json');

try {
    // Get bracket_id from request
    $bracketId = isset($_POST['bracket_id']) ? intval($_POST['bracket_id']) : 
                (isset($_GET['bracket_id']) ? intval($_GET['bracket_id']) : null);
    
    if (!$bracketId) {
        throw new Exception("Bracket ID is required");
    }

    // Fetch bracket info
    $bracketQuery = "SELECT b.*, g.game_name 
                    FROM brackets b 
                    JOIN games g ON b.game_id = g.game_id 
                    WHERE b.bracket_id = ?";
    $bracketStmt = $conn->prepare($bracketQuery);
    $bracketStmt->bind_param("i", $bracketId);
    $bracketStmt->execute();
    $bracketResult = $bracketStmt->get_result();
    $bracket = $bracketResult->fetch_assoc();
    
    if (!$bracket) {
        throw new Exception("Bracket not found");
    }

    // Fetch all teams for this game
    $teamsQuery = "SELECT t.* 
                  FROM teams t 
                  WHERE t.game_id = ?";
    $teamsStmt = $conn->prepare($teamsQuery);
    $teamsStmt->bind_param("i", $bracket['game_id']);
    $teamsStmt->execute();
    $teamsResult = $teamsStmt->get_result();
    $teams = array();
    while ($team = $teamsResult->fetch_assoc()) {
        $teams[] = $team;
    }

    // Fetch matches with team names
    $query = "SELECT m.*, 
              t1.team_name as teamA_name,
              t2.team_name as teamB_name,
              m.status
              FROM matches m 
              LEFT JOIN teams t1 ON m.teamA_id = t1.team_id
              LEFT JOIN teams t2 ON m.teamB_id = t2.team_id
              WHERE m.bracket_id = ?
              ORDER BY m.round, m.match_number";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $bracketId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Organize matches by round
    $matches = array();
    while ($match = $result->fetch_assoc()) {
        $round = $match['round'];
        
        // Handle null teams (BYEs)
        if ($match['teamA_id'] === null) {
            $match['teamA_name'] = 'BYE';
        }
        if ($match['teamB_id'] === null) {
            $match['teamB_name'] = 'BYE';
        }
        
        if (!isset($matches[$round])) {
            $matches[$round] = array();
        }
        $matches[$round][] = $match;
    }
    
    // Sort matches within each round by match_number
    foreach ($matches as &$roundMatches) {
        usort($roundMatches, function($a, $b) {
            return $a['match_number'] - $b['match_number'];
        });
    }
    
    echo json_encode([
        'success' => true,
        'matches' => $matches,
        'bracket' => $bracket,
        'teams' => $teams
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
