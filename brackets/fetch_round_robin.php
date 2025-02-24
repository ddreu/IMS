<?php
session_start();
require_once '../connection/conn.php';

// Add error reporting at the top to catch any PHP errors
error_reporting(E_ALL);
ini_set('display_errors', 0); // Don't display errors to browser
ini_set('log_errors', 1);     // Log errors instead

header('Content-Type: application/json'); // Add this before any output

function fetchRoundRobinData($bracket_id)
{
    $conn = con();
    try {
        error_log("Fetching round robin data for bracket_id: " . $bracket_id);

        // Get tournament info
        $tournament_query = "SELECT b.*, g.game_name 
                           FROM brackets b 
                           JOIN games g ON b.game_id = g.game_id 
                           WHERE b.bracket_id = ?";
        $stmt = $conn->prepare($tournament_query);
        $stmt->bind_param("i", $bracket_id);
        $stmt->execute();
        $tournament_info = $stmt->get_result()->fetch_assoc();

        error_log("Tournament info: " . json_encode($tournament_info));

        if (!$tournament_info) {
            throw new Exception("Tournament not found");
        }

        // Get matches with actual status and scores
        $matches_query = "SELECT 
            m.match_id,
            m.round,
            m.match_number,
            m.status,
            t1.team_name as teamA_name, 
            t1.team_id as teamA_id,
            t2.team_name as teamB_name, 
            t2.team_id as teamB_id,
            CASE 
                WHEN mr.team_A_id = t1.team_id THEN mr.score_teamA
                WHEN mr.team_B_id = t1.team_id THEN mr.score_teamB
            END as score_teamA,
            CASE 
                WHEN mr.team_A_id = t2.team_id THEN mr.score_teamA
                WHEN mr.team_B_id = t2.team_id THEN mr.score_teamB
            END as score_teamB,
            mr.winning_team_id
        FROM matches m 
        LEFT JOIN teams t1 ON m.teamA_id = t1.team_id 
        LEFT JOIN teams t2 ON m.teamB_id = t2.team_id 
        LEFT JOIN match_results mr ON m.match_id = mr.match_id
        WHERE m.bracket_id = ?
        ORDER BY m.round, m.match_number";
        $stmt = $conn->prepare($matches_query);
        $stmt->bind_param("i", $bracket_id);
        $stmt->execute();
        $matches = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Get scoring rules
        $scoring_query = "SELECT * FROM tournament_scoring WHERE bracket_id = ?";
        $stmt = $conn->prepare($scoring_query);
        $stmt->bind_param("i", $bracket_id);
        $stmt->execute();
        $scoring_rules = $stmt->get_result()->fetch_assoc();

        // If no scoring rules exist, provide defaults
        if (!$scoring_rules) {
            $scoring_rules = [
                'win_points' => 3,
                'draw_points' => 1,
                'loss_points' => 0,
                'bonus_points' => 0
            ];
        }

        // Calculate standings
        $teams = [];
        foreach ($matches as $match) {
            // Initialize teams if not exists
            if (!isset($teams[$match['teamA_id']])) {
                $teams[$match['teamA_id']] = [
                    'team_name' => $match['teamA_name'],
                    'played' => 0,
                    'won' => 0,
                    'draw' => 0,
                    'losses' => $match['teamA_losses'],
                    'bonus_points' => 0,
                    'total_points' => 0
                ];
            }
            if (!isset($teams[$match['teamB_id']])) {
                $teams[$match['teamB_id']] = [
                    'team_name' => $match['teamB_name'],
                    'played' => 0,
                    'won' => 0,
                    'draw' => 0,
                    'losses' => $match['teamB_losses'],
                    'bonus_points' => 0,
                    'total_points' => 0
                ];
            }

            // Only count completed matches
            if ($match['status'] === 'completed') {
                $teams[$match['teamA_id']]['played']++;
                $teams[$match['teamB_id']]['played']++;

                if ($match['winning_team_id'] === $match['teamA_id']) {
                    $teams[$match['teamA_id']]['won']++;
                    $teams[$match['teamB_id']]['losses']++;
                } elseif ($match['winning_team_id'] === $match['teamB_id']) {
                    $teams[$match['teamB_id']]['won']++;
                    $teams[$match['teamA_id']]['losses']++;
                } else {
                    $teams[$match['teamA_id']]['draw']++;
                    $teams[$match['teamB_id']]['draw']++;
                }
            }
        }

        // Fetch bonus points for each team
        $points_query = "SELECT team_id, total_points, bonus_points 
                        FROM team_tournament_points 
                        WHERE bracket_id = ?";
        $stmt = $conn->prepare($points_query);
        $stmt->bind_param("i", $bracket_id);
        $stmt->execute();
        $points_result = $stmt->get_result();

        while ($points = $points_result->fetch_assoc()) {
            if (isset($teams[$points['team_id']])) {
                $teams[$points['team_id']]['bonus_points'] = $points['bonus_points'];
                // Use stored total points instead of calculating
                $teams[$points['team_id']]['total_points'] = $points['total_points'];
            }
        }

        // Update total points for teams without records
        foreach ($teams as &$team) {
            if (!isset($team['total_points'])) {
                $team['total_points'] = $team['won'];
                $team['bonus_points'] = 0;
            }
        }

        // Sort teams by total points
        uasort($teams, function ($a, $b) {
            return $b['total_points'] - $a['total_points'];
        });

        // Get final standings based on total points
        $standings_query = "SELECT 
            t.team_id,
            t.team_name,
            t.wins,
            t.losses,
            COALESCE(ttp.total_points, 0) as total_points,
            COALESCE(ttp.bonus_points, 0) as bonus_points,
            (SELECT COUNT(*) 
             FROM match_results mr 
             JOIN matches m ON mr.match_id = m.match_id 
             WHERE (m.teamA_id = t.team_id OR m.teamB_id = t.team_id)
             AND m.bracket_id = ? AND m.status = 'finished') as played,
            (SELECT COUNT(*) 
             FROM match_results mr 
             JOIN matches m ON mr.match_id = m.match_id 
             WHERE m.bracket_id = ? AND mr.winning_team_id IS NULL 
             AND (m.teamA_id = t.team_id OR m.teamB_id = t.team_id)
             AND m.status = 'finished') as draw
        FROM teams t
        LEFT JOIN team_tournament_points ttp ON t.team_id = ttp.team_id AND ttp.bracket_id = ?
        WHERE t.team_id IN (
            SELECT teamA_id FROM matches WHERE bracket_id = ?
            UNION 
            SELECT teamB_id FROM matches WHERE bracket_id = ?
        )
        ORDER BY COALESCE(ttp.total_points, 0) DESC, t.wins DESC, t.team_name";

        $stmt = $conn->prepare($standings_query);
        $stmt->bind_param("iiiii", $bracket_id, $bracket_id, $bracket_id, $bracket_id, $bracket_id);
        $stmt->execute();
        $standings_result = $stmt->get_result();

        $standings = [];
        while ($standing = $standings_result->fetch_assoc()) {
            $standings[] = $standing;
        }

        $result = [
            'success' => true,
            'tournament_info' => $tournament_info,
            'matches' => $matches,
            'scoring_rules' => $scoring_rules,
            'standings' => $standings
        ];

        error_log("Returning data: " . json_encode($result));
        return $result;
    } catch (Exception $e) {
        error_log("Error in fetchRoundRobinData: " . $e->getMessage());
        return [
            'success' => false,
            'message' => 'Failed to fetch tournament data: ' . $e->getMessage()
        ];
    } finally {
        $conn->close();
    }
}

// Handle request
if (isset($_GET['bracket_id'])) {
    try {
        $result = fetchRoundRobinData($_GET['bracket_id']);
        echo json_encode($result);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
} else {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => 'Bracket ID not provided'
    ]);
}
