<?php
require_once('../connection/conn.php');
include_once '../user_logs/logger.php';
header('Content-Type: application/json');
$conn = con();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        throw new Exception('Invalid data format');
    }

    // Extract and validate required data
    $game_id = isset($data['game_id']) ? intval($data['game_id']) : null;
    $department_id = isset($data['department_id']) ? intval($data['department_id']) : null;
    $grade_level = isset($data['grade_level']) ? $data['grade_level'] : null;
    $teams = isset($data['teams']) ? $data['teams'] : null;
    $matches = isset($data['matches']) ? $data['matches'] : null;
    $rounds = isset($data['rounds']) ? intval($data['rounds']) : null;

    if (!$game_id || !$department_id || !$teams || !$matches || !$rounds) {
        throw new Exception('Missing required fields');
    }

    // Start transaction
    mysqli_query($conn, "START TRANSACTION");

    // Insert into brackets table
    $stmt = $conn->prepare("INSERT INTO brackets (game_id, department_id, grade_level, total_teams, rounds, status, created_at, bracket_type) 
                          VALUES (?, ?, ?, ?, ?, 'ongoing', NOW(), 'single')");

    $total_teams = count($teams);
    $stmt->bind_param("iisii", $game_id, $department_id, $grade_level, $total_teams, $rounds);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to create bracket: ' . $stmt->error);
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
                    $advancedTeams[$nextMatch] = array();
                }
                $advancedTeams[$nextMatch][] = $teamB;
            }
        } else if ($teamB == -1 && $teamA != -1) {
            // Team A advances
            $status = 'finished';
            if ($nextMatch > 0) {
                if (!isset($advancedTeams[$nextMatch])) {
                    $advancedTeams[$nextMatch] = array();
                }
                $advancedTeams[$nextMatch][] = $teamA;
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
            if (count($advancedTeams[$matchNum]) > 0) {
                $teamA = array_shift($advancedTeams[$matchNum]);
                $teamB = -2; // TBD
                if (count($advancedTeams[$matchNum]) > 0) {
                    $teamB = array_shift($advancedTeams[$matchNum]);
                }
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

    echo json_encode([
        'success' => true,
        'message' => 'Bracket saved successfully',
        'bracket_id' => $bracket_id
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

$conn->close();
