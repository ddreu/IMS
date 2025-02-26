<?php
require_once('../connection/conn.php');
include_once '../user_logs/logger.php';
header('Content-Type: application/json');
$conn = con();

function saveSingleBracket($data, $conn)
{
    // Extract and validate required data
    $game_id = isset($data['game_id']) ? intval($data['game_id']) : null;
    $department_id = isset($data['department_id']) ? intval($data['department_id']) : null;
    $grade_level = isset($data['grade_level']) ? $data['grade_level'] : null;
    $teams = isset($data['teams']) ? $data['teams'] : null;
    $matches = isset($data['matches']) ? $data['matches'] : null;
    $rounds = isset($data['rounds']) ? intval($data['rounds']) : null;

    if (!$game_id || !$department_id || !$teams || !$matches || !$rounds) {
        throw new Exception('Missing required fields for single bracket');
    }

    // Start transaction
    mysqli_query($conn, "START TRANSACTION");

    // Insert into brackets table
    $stmt = $conn->prepare("INSERT INTO brackets (game_id, department_id, grade_level, total_teams, rounds, status, created_at, bracket_type) 
                          VALUES (?, ?, ?, ?, ?, 'ongoing', NOW(), 'single')");

    $total_teams = count($teams);
    $stmt->bind_param("iisii", $game_id, $department_id, $grade_level, $total_teams, $rounds);

    if (!$stmt->execute()) {
        throw new Exception('Failed to create single bracket: ' . $stmt->error);
    }

    $bracket_id = $conn->insert_id;

    // Prepare match insert statement
    $matchStmt = $conn->prepare("INSERT INTO matches (match_identifier, bracket_id, teamA_id, teamB_id, round, match_number, next_match_number, status, match_type) 
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // Insert matches
    $advancedTeams = array(); // Track teams that advance due to BYE
    $matchTeams = array(); // Track teams for each match

    // First pass: Process BYE matches and track advancements
    foreach ($matches as $match) {
        $matchNum = $match['match_number'];
        $teamA = $match['teamA_id'];
        $teamB = $match['teamB_id'];
        $nextMatch = $match['next_match_number'];
        $status = strtolower($match['status']);

        // Handle BYE matches and auto-advancement
        if ($teamA == -1 && $teamB != -1) {
            // Team B advances
            $status = 'finished';
            if ($nextMatch > 0) {
                if (!isset($advancedTeams[$nextMatch])) {
                    $advancedTeams[$nextMatch] = array(
                        'teamA_placement' => null,
                        'teamB_placement' => null
                    );
                }

                // Determine placement based on current match number
                if ($matchNum % 2 == 1) {
                    // Odd match number: prefer teamA placement
                    $advancedTeams[$nextMatch]['teamA_placement'] = $teamB;
                } else {
                    // Even match number: prefer teamB placement
                    $advancedTeams[$nextMatch]['teamB_placement'] = $teamB;
                }
            }
        } else if ($teamB == -1 && $teamA != -1) {
            // Team A advances
            $status = 'finished';
            if ($nextMatch > 0) {
                if (!isset($advancedTeams[$nextMatch])) {
                    $advancedTeams[$nextMatch] = array(
                        'teamA_placement' => null,
                        'teamB_placement' => null
                    );
                }

                // Determine placement based on current match number
                if ($matchNum % 2 == 1) {
                    // Odd match number: prefer teamA placement
                    $advancedTeams[$nextMatch]['teamA_placement'] = $teamA;
                } else {
                    // Even match number: prefer teamB placement
                    $advancedTeams[$nextMatch]['teamB_placement'] = $teamA;
                }
            }
        }

        $matchTeams[$matchNum] = array(
            'teamA' => $teamA,
            'teamB' => $teamB,
            'status' => $status,
            'next' => $nextMatch
        );
    }

    // Second pass: Update matches with advanced teams
    foreach ($matches as $match) {
        $matchNum = $match['match_number'];
        $teamA = $match['teamA_id'];
        $teamB = $match['teamB_id'];
        $nextMatch = $match['next_match_number'];
        $status = strtolower($match['status']);

        // If this match has advanced teams, update teamA and teamB
        if (isset($advancedTeams[$matchNum])) {
            // Prioritize placement based on match number pattern
            if ($advancedTeams[$matchNum]['teamA_placement'] !== null) {
                $teamA = $advancedTeams[$matchNum]['teamA_placement'];
                $teamB = $advancedTeams[$matchNum]['teamB_placement'] ?? -2;
            } elseif ($advancedTeams[$matchNum]['teamB_placement'] !== null) {
                $teamB = $advancedTeams[$matchNum]['teamB_placement'];
                $teamA = $advancedTeams[$matchNum]['teamA_placement'] ?? -2;
            }
        }

        $params = array(
            'identifier' => $match['match_identifier'],
            'bracket' => $bracket_id,
            'teamA' => $teamA,
            'teamB' => $teamB,
            'round' => $match['round'],
            'match' => $matchNum,
            'next' => $nextMatch,
            'status' => $status,
            'type' => strtolower($match['match_type'])
        );

        $matchStmt->bind_param(
            "siiiiiiss",
            $params['identifier'],
            $params['bracket'],
            $params['teamA'],
            $params['teamB'],
            $params['round'],
            $params['match'],
            $params['next'],
            $params['status'],
            $params['type']
        );

        if (!$matchStmt->execute()) {
            throw new Exception('Failed to create match: ' . $matchStmt->error);
        }
    }

    // Commit transaction
    mysqli_query($conn, "COMMIT");

    return $bracket_id;
}

function saveDoubleBracket($data, $conn)
{
    // Double bracket specific validation
    $game_id = isset($data['game_id']) ? intval($data['game_id']) : null;
    $department_id = isset($data['department_id']) ? intval($data['department_id']) : null;
    $grade_level = isset($data['grade_level']) ? $data['grade_level'] : null;
    $teams = isset($data['teams']) ? $data['teams'] : null;
    $matches = isset($data['matches']) ? $data['matches'] : null;
    $rounds = isset($data['rounds']) ? $data['rounds'] : null;

    if (
        !$game_id || !$department_id || !$teams || !$matches ||
        !isset($rounds['winners']) || !isset($rounds['losers'])
    ) {
        throw new Exception('Missing required fields for double bracket');
    }

    // Start transaction
    mysqli_query($conn, "START TRANSACTION");

    // Insert into brackets table - use total rounds for the rounds column
    $stmt = $conn->prepare("INSERT INTO brackets (
        game_id, department_id, grade_level, total_teams,
        rounds, status, created_at, bracket_type
    ) VALUES (?, ?, ?, ?, ?, 'ongoing', NOW(), 'double')");

    $total_teams = count($teams);
    $total_rounds = $rounds['winners'] + $rounds['losers'] + 2; // Include finals

    $stmt->bind_param(
        "iisii",
        $game_id,
        $department_id,
        $grade_level,
        $total_teams,
        $total_rounds
    );

    if (!$stmt->execute()) {
        throw new Exception('Failed to create double bracket: ' . $stmt->error);
    }

    $bracket_id = $conn->insert_id;

    // First insert into matches table (similar to single bracket)
    $matchStmt = $conn->prepare("INSERT INTO matches (
        match_identifier, bracket_id, teamA_id, teamB_id,
        round, match_number, next_match_number, status, match_type
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    // Then insert additional double info
    $doubleInfoStmt = $conn->prepare("INSERT INTO double_match_info (
        match_id, next_winner_match, next_loser_match, bracket_position
    ) VALUES (?, ?, ?, ?)");

    // Process and save matches
    foreach ($matches as $match) {
        // Handle BYE matches
        $status = $match['status'];
        $teamA = $match['teamA_id'];
        $teamB = $match['teamB_id'];

        // Auto-advance teams if this is a BYE match
        if ($teamA === -1 || $teamB === -1) {
            $status = 'finished';

            // Find the winning team
            $winningTeamId = $teamA === -1 ? $teamB : $teamA;

            // Update next match if it exists
            if ($match['next_winner_match'] > 0) {
                $nextMatchNum = $match['next_winner_match'];
                // Find the next match and update its team
                foreach ($matches as &$nextMatch) {
                    if ($nextMatch['match_number'] === $nextMatchNum) {
                        if ($nextMatch['teamA_id'] === -2) {
                            $nextMatch['teamA_id'] = $winningTeamId;
                        } else {
                            $nextMatch['teamB_id'] = $winningTeamId;
                        }
                        break;
                    }
                }
            }
        }

        // Insert match with updated status
        $matchStmt->bind_param(
            "siiiiisss",
            $match['match_identifier'],
            $bracket_id,
            $teamA,
            $teamB,
            $match['round'],
            $match['match_number'],
            $match['next_winner_match'],
            $status,
            $match['match_type']
        );

        if (!$matchStmt->execute()) {
            throw new Exception('Failed to create match: ' . $matchStmt->error);
        }

        $match_id = $conn->insert_id;

        // Store double elimination specific progression
        $doubleInfoStmt->bind_param(
            "iiis",
            $match_id,
            $match['next_winner_match'], // Where winner goes
            $match['next_loser_match'],  // Where loser goes (if in winners bracket)
            $match['bracket']
        );

        if (!$doubleInfoStmt->execute()) {
            throw new Exception('Failed to create double match info: ' . $doubleInfoStmt->error);
        }
    }

    mysqli_query($conn, "COMMIT");
    return $bracket_id;
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        throw new Exception('Invalid data format');
    }

    // Validate required fields
    if (!isset($data['game_id'], $data['department_id'], $data['bracket_type'])) {
        throw new Exception('Missing required fields');
    }

    // Route to appropriate saving function based on bracket type
    $bracket_id = $data['bracket_type'] === 'double' ?
        saveDoubleBracket($data, $conn) :
        saveSingleBracket($data, $conn);

    mysqli_query($conn, "COMMIT");

    echo json_encode([
        'success' => true,
        'message' => 'Bracket saved successfully',
        'bracket_id' => $bracket_id
    ]);
} catch (Exception $e) {
    mysqli_query($conn, "ROLLBACK");
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

$conn->close();
