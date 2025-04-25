<?php
require_once '../../connection/conn.php';
session_start();
$conn = con();
header('Content-Type: application/json');

try {
    // Get bracket_id from request
    $bracketId = isset($_POST['bracket_id']) ? intval($_POST['bracket_id']) : (isset($_GET['bracket_id']) ? intval($_GET['bracket_id']) : null);

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
    $stmt->bind_param("i", $bracketId);
    $stmt->execute();
    $result = $stmt->get_result();

    // Organize matches by round
    $matches = array();
    while ($match = $result->fetch_assoc()) {
        $round = $match['round'];

        // Convert team IDs and scores to integers
        $match['teamA_id'] = intval($match['teamA_id']);
        $match['teamB_id'] = intval($match['teamB_id']);
        $match['winning_team_id'] = isset($match['winning_team_id']) ? intval($match['winning_team_id']) : null;
        $match['score_teamA'] = isset($match['score_teamA']) ? intval($match['score_teamA']) : null;
        $match['score_teamB'] = isset($match['score_teamB']) ? intval($match['score_teamB']) : null;

        // Explicitly map winner and loser
        $match['winner_id'] = $match['winning_team_id'];
        $match['loser_id'] = ($match['winning_team_id'] == $match['teamA_id']) ? $match['teamB_id'] : (($match['winning_team_id'] == $match['teamB_id']) ? $match['teamA_id'] : null);

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

    // Sort matches within each round by match_number
    foreach ($matches as $key => &$roundMatches) {
        if ($key !== 'third-place') {
            usort($roundMatches, function ($a, $b) {
                return $a['match_number'] - $b['match_number'];
            });
        }
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
