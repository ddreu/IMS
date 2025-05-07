<?php
session_start();
require_once '../connection/conn.php';

function saveRoundRobinBracket($data)
{
    $conn = con();
    try {
        // Validate required data
        if (!isset($data['game_id'], $data['department_id'], $data['teams'], $data['matches'], $data['rounds'])) {
            throw new Exception('Missing required data');
        }

        // Start transaction
        $conn->begin_transaction();

        // Save bracket info
        $bracket_query = "INSERT INTO brackets (
            game_id, 
            department_id, 
            grade_level, 
            total_teams,
            rounds,
            status,
            bracket_type
        ) VALUES (?, ?, ?, ?, ?, 'ongoing', 'round_robin')";

        $stmt = $conn->prepare($bracket_query);
        $total_teams = count($data['teams']);
        $stmt->bind_param(
            "iisii",
            $data['game_id'],
            $data['department_id'],
            $data['grade_level'],
            $total_teams,
            $data['rounds']
        );
        $stmt->execute();
        $bracket_id = $conn->insert_id;

        // Save matches
        $match_query = "INSERT INTO matches (
            match_identifier,
            bracket_id, 
            teamA_id, 
            teamB_id, 
            round,
            match_number,
            status,
            match_type
        ) VALUES (?, ?, ?, ?, ?, ?, 'pending', 'regular')";

        $stmt = $conn->prepare($match_query);

        foreach ($data['matches'] as $match) {
            $match_identifier = "B{$bracket_id}R{$match['round']}M{$match['match_number']}";
            $stmt->bind_param(
                "siiiis",
                $match_identifier,
                $bracket_id,
                $match['teamA_id'],
                $match['teamB_id'],
                $match['round'],
                $match['match_number']
            );
            $stmt->execute();
        }

        // Commit transaction
        $conn->commit();

        return [
            'success' => true,
            'message' => 'Round Robin tournament saved successfully',
            'bracket_id' => $bracket_id
        ];
    } catch (Exception $e) {
        // Rollback on error
        if ($conn->connect_error === false) {
            $conn->rollback();
        }

        return [
            'success' => false,
            'message' => 'Failed to save tournament: ' . $e->getMessage()
        ];
    } finally {
        $conn->close();
    }
}

// Handle incoming request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty(file_get_contents('php://input'))) {
    $data = json_decode(file_get_contents('php://input'), true);
    $result = saveRoundRobinBracket($data);
    echo json_encode($result);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method or empty data'
    ]);
}
