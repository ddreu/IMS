<?php
require_once '../connection/conn.php';
session_start();

header('Content-Type: application/json');

try {
    $conn = con();
    
    // Get POST data
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        throw new Exception("No data received");
    }

    $matchId = $data['match_id'] ?? null;
    $scoreTeamA = $data['score_teamA'] ?? 0;
    $scoreTeamB = $data['score_teamB'] ?? 0;
    
    if (!$matchId) {
        throw new Exception("Match ID is required");
    }

    mysqli_query($conn, "START TRANSACTION");

    // Get current match info
    $matchQuery = "SELECT m.*, b.rounds as total_rounds 
                  FROM matches m 
                  JOIN brackets b ON m.bracket_id = b.bracket_id 
                  WHERE m.match_id = ?";
    $matchStmt = $conn->prepare($matchQuery);
    $matchStmt->bind_param("i", $matchId);
    $matchStmt->execute();
    $matchResult = $matchStmt->get_result();
    $match = $matchResult->fetch_assoc();

    if (!$match) {
        throw new Exception("Match not found");
    }

    // Update current match
    $updateMatchStmt = $conn->prepare("UPDATE matches SET 
        score_teamA = ?,
        score_teamB = ?,
        status = 'finished'
        WHERE match_id = ?");
    $updateMatchStmt->bind_param("iii", $scoreTeamA, $scoreTeamB, $matchId);
    $updateMatchStmt->execute();

    // If this isn't the final round and there's a next match, handle advancement
    if ($match['round'] < $match['total_rounds'] && $match['next_match_number'] !== null) {
        // Determine winning team
        $winningTeamId = ($scoreTeamA > $scoreTeamB) ? $match['teamA_id'] : $match['teamB_id'];
        $losingTeamId = ($scoreTeamA > $scoreTeamB) ? $match['teamB_id'] : $match['teamA_id'];

        // Get next match
        $nextMatchQuery = "SELECT * FROM matches 
                          WHERE bracket_id = ? 
                          AND round = ? 
                          AND match_number = ?";
        $nextMatchStmt = $conn->prepare($nextMatchQuery);
        $nextRound = $match['round'] + 1;
        $nextMatchStmt->bind_param("iii", $match['bracket_id'], $nextRound, $match['next_match_number']);
        $nextMatchStmt->execute();
        $nextMatchResult = $nextMatchStmt->get_result();
        $nextMatch = $nextMatchResult->fetch_assoc();

        if ($nextMatch) {
            // Determine if winner goes to teamA or teamB slot
            $isTeamASlot = ($match['match_number'] % 2) !== 0; // Odd match numbers go to teamA slot
            
            // Update next match with winning team
            $updateNextMatchStmt = $conn->prepare("UPDATE matches SET 
                teamA_id = CASE WHEN ? THEN ? ELSE teamA_id END,
                teamB_id = CASE WHEN ? THEN ? ELSE teamB_id END
                WHERE match_id = ?");
            $isTeamA = $isTeamASlot ? 1 : 0;
            $isTeamB = $isTeamASlot ? 0 : 1;
            $updateNextMatchStmt->bind_param("iiiii", 
                $isTeamA, $winningTeamId,
                $isTeamB, $winningTeamId,
                $nextMatch['match_id']
            );
            $updateNextMatchStmt->execute();

            // Special handling for semifinals (setting up third place match)
            if ($match['match_type'] === 'semifinals') {
                // Find the third place match
                $thirdPlaceStmt = $conn->prepare("SELECT * FROM matches 
                    WHERE bracket_id = ? 
                    AND round = ? 
                    AND match_type = 'third_place'");
                $finalRound = $match['total_rounds'];
                $thirdPlaceStmt->bind_param("ii", $match['bracket_id'], $finalRound);
                $thirdPlaceStmt->execute();
                $thirdPlaceResult = $thirdPlaceStmt->get_result();
                $thirdPlaceMatch = $thirdPlaceResult->fetch_assoc();

                if ($thirdPlaceMatch) {
                    // Update third place match with losing team
                    $updateThirdPlaceStmt = $conn->prepare("UPDATE matches SET 
                        teamA_id = CASE WHEN ? THEN ? ELSE teamA_id END,
                        teamB_id = CASE WHEN ? THEN ? ELSE teamB_id END
                        WHERE match_id = ?");
                    $isTeamA = ($match['match_number'] === 1) ? 1 : 0; // First semifinal loser goes to teamA
                    $isTeamB = ($match['match_number'] === 1) ? 0 : 1;
                    $updateThirdPlaceStmt->bind_param("iiiii",
                        $isTeamA, $losingTeamId,
                        $isTeamB, $losingTeamId,
                        $thirdPlaceMatch['match_id']
                    );
                    $updateThirdPlaceStmt->execute();
                }
            }
        }
    }

    mysqli_query($conn, "COMMIT");
    
    echo json_encode([
        'success' => true,
        'message' => 'Match updated successfully'
    ]);

} catch (Exception $e) {
    if (isset($conn)) {
        mysqli_query($conn, "ROLLBACK");
    }
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

if (isset($conn)) {
    $conn->close();
}
