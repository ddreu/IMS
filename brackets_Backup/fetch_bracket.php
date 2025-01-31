<?php
require_once '../connection/conn.php';
session_start();
$conn = con();
header('Content-Type: application/json');

try {
    // Get bracket_id from request (support both POST and GET)
    $bracketId = isset($_POST['bracket_id']) ? intval($_POST['bracket_id']) : 
                (isset($_GET['bracket_id']) ? intval($_GET['bracket_id']) : null);
    
    if (!$bracketId) {
        throw new Exception("Bracket ID is required");
    }

    // First fetch bracket info
    $bracketQuery = "SELECT b.*, g.game_name 
                    FROM brackets b 
                    JOIN games g ON b.game_id = g.game_id 
                    WHERE b.bracket_id = ?";
    $bracketStmt = $conn->prepare($bracketQuery);
    if (!$bracketStmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $bracketStmt->bind_param("i", $bracketId);
    if (!$bracketStmt->execute()) {
        throw new Exception("Execute failed: " . $bracketStmt->error);
    }
    
    $bracketResult = $bracketStmt->get_result();
    $bracket = $bracketResult->fetch_assoc();
    
    if (!$bracket) {
        throw new Exception("Bracket not found");
    }

    // Fetch matches with team names, results, and status
    $query = "SELECT m.*, 
        CASE 
            WHEN m.teamA_id = -1 THEN 'BYE'
            WHEN m.teamA_id = -2 THEN 'TBD'
            ELSE t1.team_name 
        END as teamA_name,
        CASE 
            WHEN m.teamB_id = -1 THEN 'BYE'
            WHEN m.teamB_id = -2 THEN 'TBD'
            ELSE t2.team_name 
        END as teamB_name,
        mr.score_teamA,
        mr.score_teamB,
        mr.winning_team_id,
        m.status
    FROM matches m 
    LEFT JOIN teams t1 ON m.teamA_id = t1.team_id AND m.teamA_id > 0
    LEFT JOIN teams t2 ON m.teamB_id = t2.team_id AND m.teamB_id > 0
    LEFT JOIN match_results mr ON m.match_id = mr.match_id
    WHERE m.bracket_id = ?
    ORDER BY m.round, m.match_number";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    
    $stmt->bind_param("i", $bracketId);
    if (!$stmt->execute()) {
        throw new Exception("Execute failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();
    $matches = array();
    
    // First, organize matches by round
    while ($match = $result->fetch_assoc()) {
        $round = $match['round'];
        
        // Convert team IDs and scores to integers
        $match['teamA_id'] = intval($match['teamA_id']);
        $match['teamB_id'] = intval($match['teamB_id']);
        $match['winning_team_id'] = isset($match['winning_team_id']) ? intval($match['winning_team_id']) : null;
        $match['score_teamA'] = isset($match['score_teamA']) ? intval($match['score_teamA']) : 0;
        $match['score_teamB'] = isset($match['score_teamB']) ? intval($match['score_teamB']) : 0;
        
        if ($round == -1) {
            // Third place match
            $matches['third-place'] = $match;
        } else {
            if (!isset($matches[$round])) {
                $matches[$round] = array();
            }
            $matches[$round][] = $match;
        }
    }
    
    // Debug log
    error_log("Matches data: " . json_encode($matches));
    
    // Ensure we have matches and they're properly structured
    if (empty($matches)) {
        throw new Exception("No matches found for this bracket");
    }
    
    // Ensure numeric rounds are sequential
    $numericRounds = array_filter(array_keys($matches), 'is_numeric');
    sort($numericRounds);
    $reindexed = array();
    foreach ($numericRounds as $index => $round) {
        $reindexed[$round] = $matches[$round]; // Keep original round numbers
    }
    
    // Add back the third-place match if it exists
    if (isset($matches['third-place'])) {
        $reindexed['third-place'] = $matches['third-place'];
    }
    
    echo json_encode([
        'success' => true,
        'matches' => $reindexed,
        'bracket' => $bracket
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
