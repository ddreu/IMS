<?php
session_start();
require_once '../connection/conn.php';

function saveRoundRobinPoints($data)
{
    $conn = con();
    try {
        // Check action type
        if (isset($data['action']) && $data['action'] === 'add_bonus') {
            if (!isset($data['bracket_id'], $data['team_id'], $data['bonus_points'])) {
                throw new Exception('Missing required bonus points data');
            }

            // Check if record exists
            $check_query = "SELECT id FROM team_tournament_points 
                            WHERE bracket_id = ? AND team_id = ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("ii", $data['bracket_id'], $data['team_id']);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                // Update existing record
                $query = "UPDATE team_tournament_points 
                         SET bonus_points = bonus_points + ?,
                             total_points = total_points + ?
                         WHERE bracket_id = ? AND team_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param(
                    "iiii",
                    $data['bonus_points'],
                    $data['bonus_points'], // Add to total points too
                    $data['bracket_id'],
                    $data['team_id']
                );
            } else {
                // Insert new record
                $query = "INSERT INTO team_tournament_points 
                         (bracket_id, team_id, total_points, bonus_points) 
                         VALUES (?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->bind_param(
                    "iiii",
                    $data['bracket_id'],
                    $data['team_id'],
                    $data['bonus_points'], // Initial total points = bonus points
                    $data['bonus_points']
                );
            }
            $stmt->execute();

            return [
                'success' => true,
                'message' => 'Bonus points added successfully'
            ];
        } else {
            // Validate required data
            if (!isset($data['bracket_id'], $data['win_points'], $data['draw_points'], $data['loss_points'], $data['bonus_points'])) {
                throw new Exception('Missing required scoring data');
            }

            // Check if record exists for this bracket_id
            $check_query = "SELECT scoring_id FROM tournament_scoring WHERE bracket_id = ?";
            $check_stmt = $conn->prepare($check_query);
            $check_stmt->bind_param("i", $data['bracket_id']);
            $check_stmt->execute();
            $result = $check_stmt->get_result();

            if ($result->num_rows > 0) {
                // Update existing record
                $query = "UPDATE tournament_scoring SET 
                    win_points = ?,
                    draw_points = ?,
                    loss_points = ?,
                    bonus_points = ?,
                    updated_at = CURRENT_TIMESTAMP
                    WHERE bracket_id = ?";

                $stmt = $conn->prepare($query);
                $stmt->bind_param(
                    "iiiii",
                    $data['win_points'],
                    $data['draw_points'],
                    $data['loss_points'],
                    $data['bonus_points'],
                    $data['bracket_id']
                );
            } else {
                // Insert new record
                $query = "INSERT INTO tournament_scoring (
                    bracket_id,
                    win_points,
                    draw_points,
                    loss_points,
                    bonus_points
                ) VALUES (?, ?, ?, ?, ?)";

                $stmt = $conn->prepare($query);
                $stmt->bind_param(
                    "iiiii",
                    $data['bracket_id'],
                    $data['win_points'],
                    $data['draw_points'],
                    $data['loss_points'],
                    $data['bonus_points']
                );
            }

            $stmt->execute();

            return [
                'success' => true,
                'message' => 'Scoring settings saved successfully'
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Failed to save: ' . $e->getMessage()
        ];
    } finally {
        if (isset($check_stmt)) $check_stmt->close();
        if (isset($stmt)) $stmt->close();
        $conn->close();
    }
}

// Handle incoming request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty(file_get_contents('php://input'))) {
    $data = json_decode(file_get_contents('php://input'), true);
    $result = saveRoundRobinPoints($data);
    echo json_encode($result);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method or empty data'
    ]);
}
