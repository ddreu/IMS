<?php
session_start();
include_once '../connection/conn.php';
include '../user_logs/logger.php';
$conn = con();

header('Content-Type: application/json'); // Ensure the response is JSON

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);

    if (!isset($_SESSION['school_id'])) {
        echo json_encode(['status' => 'error', 'message' => 'School ID not found in session.']);
        exit;
    }

    $school_id = $_SESSION['school_id'];
    $department_id = $data['department'] ?? null;
    $grade_level = $data['gradeLevel'] ?? null;
    $game_id = $data['game'] ?? null;
    $user_id = $_SESSION['user_id']; // Assuming user ID is stored in session

    try {
        $conn->begin_transaction();

        // 1. Find only completed brackets for points deduction
        $stmt = $conn->prepare(
            "SELECT bracket_id 
             FROM brackets 
             WHERE game_id = ? 
             AND department_id = ? 
             AND grade_level = ? 
             AND status = 'Completed'"
        );
        $stmt->bind_param("iis", $game_id, $department_id, $grade_level);
        $stmt->execute();
        $bracketResult = $stmt->get_result();

        // Process points deduction only for completed brackets
        while ($bracket = $bracketResult->fetch_assoc()) {
            $bracket_id = $bracket['bracket_id'];

            // Get third place and final matches
            $stmt = $conn->prepare(
                "SELECT match_id, match_type 
                 FROM matches 
                 WHERE bracket_id = ? 
                 AND match_type IN ('third_place', 'final')"
            );
            $stmt->bind_param("i", $bracket_id);
            $stmt->execute();
            $matchesResult = $stmt->get_result();

            $matchIds = [];
            while ($match = $matchesResult->fetch_assoc()) {
                $matchIds[$match['match_type']] = $match['match_id'];
            }

            // Get match results and winning teams
            $winners = [];
            foreach ($matchIds as $match_type => $match_id) {
                $stmt = $conn->prepare(
                    "SELECT winning_team_id, losing_team_id 
                     FROM match_results 
                     WHERE match_id = ?"
                );
                $stmt->bind_param("i", $match_id);
                $stmt->execute();
                $resultData = $stmt->get_result()->fetch_assoc();

                if ($match_type === 'final') {
                    $winners['first'] = $resultData['winning_team_id'];
                    $winners['second'] = $resultData['losing_team_id'];
                } else {
                    $winners['third'] = $resultData['winning_team_id'];
                }
            }

            // Get grade_section_course_ids for winning teams
            $teamGscIds = [];
            foreach ($winners as $place => $team_id) {
                $stmt = $conn->prepare(
                    "SELECT grade_section_course_id 
                     FROM teams 
                     WHERE team_id = ?"
                );
                $stmt->bind_param("i", $team_id);
                $stmt->execute();
                $teamData = $stmt->get_result()->fetch_assoc();
                $teamGscIds[$place] = $teamData['grade_section_course_id'];
            }

            // Get pointing system
            $stmt = $conn->prepare(
                "SELECT first_place_points, second_place_points, third_place_points 
                 FROM pointing_system 
                 WHERE school_id = ?"
            );
            $stmt->bind_param("i", $school_id);
            $stmt->execute();
            $pointSystem = $stmt->get_result()->fetch_assoc();

            // Deduct points from grade_section_course
            $pointsMap = [
                'first' => $pointSystem['first_place_points'],
                'second' => $pointSystem['second_place_points'],
                'third' => $pointSystem['third_place_points']
            ];

            foreach ($teamGscIds as $place => $gsc_id) {
                $points = $pointsMap[$place];
                $stmt = $conn->prepare(
                    "UPDATE grade_section_course 
                     SET Points = Points - ? 
                     WHERE id = ?"
                );
                $stmt->bind_param("ii", $points, $gsc_id);
                $stmt->execute();
            }
        }

        // 2. Reset all team stats regardless of bracket status
        $stmt = $conn->prepare(
            "UPDATE teams t
             INNER JOIN grade_section_course gsc ON t.grade_section_course_id = gsc.id
             INNER JOIN departments d ON gsc.department_id = d.id
             SET t.wins = 0, 
                 t.losses = 0
             WHERE t.game_id = ? 
             AND d.id = ?  -- Corrected here to use d.id, not d.department_id
             AND gsc.grade_level = ?"
        );
        $stmt->bind_param("iis", $game_id, $department_id, $grade_level);

        if (!$stmt->execute()) {
            throw new Exception("Failed to reset team statistics.");
        }

        // 3. Delete only completed brackets
        $stmt = $conn->prepare(
            "DELETE FROM brackets 
             WHERE game_id = ? 
             AND department_id = ? 
             AND grade_level = ? 
             AND status = 'Completed'"
        );
        $stmt->bind_param("iis", $game_id, $department_id, $grade_level);

        if (!$stmt->execute()) {
            throw new Exception("Failed to delete brackets.");
        }

        // Log the action with a dynamic description
        $log_description = "Resets the ";

        // Add game to the description if present
        if ($game_id) {
            $stmt = $conn->prepare("SELECT game_name FROM games WHERE game_id = ?");
            $stmt->bind_param("i", $game_id);
            $stmt->execute();
            $gameResult = $stmt->get_result()->fetch_assoc();
            $game_name = $gameResult['game_name'];
            $log_description .= $game_name . " ";
        }

        // Add department to the description if present
        if ($department_id) {
            $stmt = $conn->prepare("SELECT department_name FROM departments WHERE id = ?");
            $stmt->bind_param("i", $department_id);
            $stmt->execute();
            $departmentResult = $stmt->get_result()->fetch_assoc();
            $department_name = $departmentResult['department_name'];
            $log_description .= $department_name . " department";
        }

        // If grade level is present, add it to the description
        if ($grade_level) {
            $log_description .= " at " . $grade_level;
        }

        // Log the action using your custom function
        logUserAction($conn, $user_id, "brackets", "RESET", null, $log_description);

        $conn->commit();

        echo json_encode([ // Final JSON response
            'status' => 'success',
            'message' => "Successfully reset leaderboards and team statistics for the selected game and division."
        ]);
    } catch (Exception $e) {
        $conn->rollBack();
        echo json_encode([ // Error handling in JSON format
            'status' => 'error',
            'message' => 'Failed to reset leaderboards: ' . $e->getMessage()
        ]);
    } finally {
        if (isset($stmt)) $stmt->close();
        $conn->close();
    }
} else {
    echo json_encode([ // Invalid request method response
        'status' => 'error',
        'message' => 'Invalid request method.'
    ]);
}
