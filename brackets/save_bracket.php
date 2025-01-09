<?php
include_once '../connection/conn.php';
session_start();
$conn = con();
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }

    // Extract data from request and session
    $matches = $data['matches'];
    $departmentId = isset($_SESSION['department_id']) ? $_SESSION['department_id'] : $data['department_id'];
    $gameId = isset($_SESSION['game_id']) ? $_SESSION['game_id'] : $data['game_id'];
    $gradeLevel = isset($data['grade_level']) ? $data['grade_level'] : null;
    
    // System team IDs - using negative numbers for system teams
    $tbdTeamId = -2;  // "To Be Determined" team (team_id = -2)
    $byeTeamId = -1;  // "BYE" team (team_id = -1)

    // Calculate total teams from first round matches
    $totalTeams = count(array_filter($matches[0], function($match) {
        return $match['teamA_id'] !== null || $match['teamB_id'] !== null;
    })) * 2;
    
    // Calculate total rounds
    $rounds = count($matches);
    if (isset($matches['third-place'])) {
        $rounds--; // Don't count third place match in rounds
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // 1. Create bracket entry
        if ($gradeLevel) {
            $query = "INSERT INTO brackets (game_id, department_id, grade_level, total_teams, rounds, status, bracket_type) VALUES (?, ?, ?, ?, ?, 'ongoing', ?)";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error . " for query: " . $query);
            }
            $bracketType = $data['bracket_type'] ?? 'single';
            $stmt->bind_param("iisiis", $gameId, $departmentId, $gradeLevel, $totalTeams, $rounds, $bracketType);
        } else {
            $query = "INSERT INTO brackets (game_id, department_id, total_teams, rounds, status, bracket_type) VALUES (?, ?, ?, ?, 'ongoing', ?)";
            $stmt = $conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $conn->error . " for query: " . $query);
            }
            $bracketType = $data['bracket_type'] ?? 'single';
            $stmt->bind_param("iiiis", $gameId, $departmentId, $totalTeams, $rounds, $bracketType);
        }
        
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }
        $bracketId = $conn->insert_id;

        // Prepare statement for matches
        $stmt = $conn->prepare("INSERT INTO matches (match_identifier, bracket_id, teamA_id, teamB_id, round, match_number, next_match_number, status, match_type) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        // Process each round
        foreach ($matches as $round => $roundMatches) {
            // Skip the third-place match as it's handled separately
            if ($round === 'third-place') {
                continue;
            }

            foreach ($roundMatches as $matchNumber => $match) {
                // Set next_match_number based on match type
                if ($match['match_type'] === 'final') {
                    $nextMatchNumber = 0; // Finals have no next match
                } else if ($match['match_type'] === 'semifinal') {
                    $nextMatchNumber = 7; // Points to the final match (match 7)
                } else {
                    // Regular matches: pairs of matches feed into semifinals
                    $currentMatchNum = $matchNumber + 1;
                    $nextMatchNumber = 4 + ceil($currentMatchNum / 2); // 5 or 6 (semifinal matches)
                }

                // Handle team assignments with correct system team IDs
                $teamA = $match['teamA_id'];
                $teamB = $match['teamB_id'];
                
                // First, convert any string/null values to proper IDs
                if ($teamA === null || $teamA === '' || $teamA === 'TBD' || $teamA === 0 || $teamA === '0') {
                    // In first round (round index 0), empty slot means BYE, otherwise it's TBD
                    $teamA = ($round === 0 && $match['match_type'] === 'regular') ? $byeTeamId : $tbdTeamId;
                } else if (strtolower($teamA) === 'bye') {
                    $teamA = $byeTeamId;
                } else {
                    $teamA = intval($teamA);
                    if ($teamA === 0) {
                        $teamA = ($round === 0 && $match['match_type'] === 'regular') ? $byeTeamId : $tbdTeamId;
                    }
                }

                if ($teamB === null || $teamB === '' || $teamB === 'TBD' || $teamB === 0 || $teamB === '0') {
                    // In first round (round index 0), empty slot means BYE, otherwise it's TBD
                    $teamB = ($round === 0 && $match['match_type'] === 'regular') ? $byeTeamId : $tbdTeamId;
                } else if (strtolower($teamB) === 'bye') {
                    $teamB = $byeTeamId;
                } else {
                    $teamB = intval($teamB);
                    if ($teamB === 0) {
                        $teamB = ($round === 0 && $match['match_type'] === 'regular') ? $byeTeamId : $tbdTeamId;
                    }
                }
                
                // Handle BYE matches - set status to Finished and advance the non-BYE team
                $status = 'Pending';
                if ($teamA === $byeTeamId || $teamB === $byeTeamId) {
                    $status = 'Finished';
                    // Get the advancing team (the non-BYE team)
                    $advancingTeam = ($teamA === $byeTeamId) ? $teamB : $teamA;
                    
                    // Update the next match if it exists
                    if ($nextMatchNumber > 0) {
                        // Odd numbered matches advance to teamA position, even to teamB
                        $isTeamAPosition = ($matchNumber % 2) === 0;
                        $updateQuery = $isTeamAPosition ? 
                            "UPDATE matches SET teamA_id = ? WHERE bracket_id = ? AND match_number = ?" :
                            "UPDATE matches SET teamB_id = ? WHERE bracket_id = ? AND match_number = ?";
                        
                        $updateStmt = $conn->prepare($updateQuery);
                        $updateStmt->bind_param("iii", $advancingTeam, $bracketId, $nextMatchNumber);
                        $updateStmt->execute();
                    }
                }
                
                // Calculate continuous match number and actual round number
                if ($match['match_type'] === 'final') {
                    $continuousMatchNumber = 7; // Final match is always 7 in an 8-team bracket
                    $actualRound = 3;  // Finals are always round 3
                } else if ($match['match_type'] === 'semifinal') {
                    $continuousMatchNumber = $matchNumber + 5; // Semifinals are 5 and 6
                    $actualRound = 2;  // Semifinals are always round 2
                } else {
                    $continuousMatchNumber = $matchNumber + 1; // First round matches are 1,2,3,4
                    $actualRound = 1;  // First matches are round 1
                }
                
                $matchIdentifier = "bracket{$bracketId}-round{$actualRound}-match{$continuousMatchNumber}";
                $matchType = $match['match_type'];
                
                $stmt->bind_param("siiiiisss", 
                    $matchIdentifier,
                    $bracketId,
                    $teamA,
                    $teamB, 
                    $actualRound,
                    $continuousMatchNumber,
                    $nextMatchNumber,
                    $status,
                    $matchType
                );
                
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
            }
        }

        // Handle third place match separately
        $thirdPlaceMatch = $matches['third-place'];
        $matchIdentifier = "bracket{$bracketId}-third-place-match";
        
        // Use existing system teams for empty slots
        $teamA = $thirdPlaceMatch['teamA_id'] ?? $tbdTeamId;
        $teamB = $thirdPlaceMatch['teamB_id'] ?? $tbdTeamId;
        $roundNum = -1; // Special round number for third place
        $matchNum = -1;  // Special match number for third place
        $nextMatchNum = 0; // No next match to advance to
        $matchType = 'third_place';
        $status = 'Pending';
        
        $stmt->bind_param("siiiiisss", 
            $matchIdentifier,
            $bracketId,
            $teamA,
            $teamB,
            $roundNum,
            $matchNum,
            $nextMatchNum,
            $status,
            $matchType
        );
        if (!$stmt->execute()) {
            throw new Exception("Execute failed: " . $stmt->error);
        }

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Bracket saved successfully',
            'bracket_id' => $bracketId
        ]);

    } catch (Exception $e) {
        // Roll back the transaction if something failed
        if (isset($conn)) {
            $conn->rollback();
        }
        
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

if (isset($conn)) {
    $conn->close();
}
