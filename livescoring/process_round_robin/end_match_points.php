<?php
require_once '../../connection/conn.php';
require_once '../../user_logs/logger.php';
session_start();
header('Content-Type: application/json');

class RoundRobinMatchProcessor
{
    private $conn;
    private $schedule_id;
    private $match_data;
    private $scoring_rules;

    public function __construct($schedule_id)
    {
        $this->conn = con();
        $this->schedule_id = $schedule_id;
    }

    // This function can be used by other files
    public static function updateTournamentPoints($conn, $bracket_id, $teamA_id, $teamB_id, $teamA_score, $teamB_score)
    {
        try {
            // Get tournament scoring rules
            $scoring_query = "SELECT * FROM tournament_scoring WHERE bracket_id = ?";
            $stmt = $conn->prepare($scoring_query);
            $stmt->bind_param("i", $bracket_id);
            $stmt->execute();
            $scoring_rules = $stmt->get_result()->fetch_assoc();

            if (!$scoring_rules) {
                throw new Exception("No scoring rules found for this tournament");
            }

            error_log("Tournament scoring rules: " . json_encode($scoring_rules));
            error_log("Match result - Team A: $teamA_score, Team B: $teamB_score");

            // Determine match outcome and award points
            if ($teamA_score > $teamB_score) {
                error_log("Team A wins - Awarding {$scoring_rules['win_points']} points");
                error_log("Team B loses - Awarding {$scoring_rules['loss_points']} points");
                self::updateTeamPoints($conn, $bracket_id, $teamA_id, $scoring_rules['win_points'], true);
                self::updateTeamPoints($conn, $bracket_id, $teamB_id, $scoring_rules['loss_points'], false);
            } elseif ($teamB_score > $teamA_score) {
                error_log("Team B wins - Awarding {$scoring_rules['win_points']} points");
                error_log("Team A loses - Awarding {$scoring_rules['loss_points']} points");
                self::updateTeamPoints($conn, $bracket_id, $teamB_id, $scoring_rules['win_points'], true);
                self::updateTeamPoints($conn, $bracket_id, $teamA_id, $scoring_rules['loss_points'], false);
            } else {
                error_log("Match draw - Both teams awarded {$scoring_rules['draw_points']} points");
                self::updateTeamPoints($conn, $bracket_id, $teamA_id, $scoring_rules['draw_points'], null);
                self::updateTeamPoints($conn, $bracket_id, $teamB_id, $scoring_rules['draw_points'], null);
            }

            return true;
        } catch (Exception $e) {
            error_log("Error updating tournament points: " . $e->getMessage());
            throw $e;
        }
    }

    public static function updateTeamPoints($conn, $bracket_id, $team_id, $points, $is_win = null)
    {
        try {
            error_log("Updating points for team_id: $team_id in bracket: $bracket_id");
            error_log("Points to award: $points, is_win: " . ($is_win === null ? 'draw' : ($is_win ? 'win' : 'loss')));

            // First check if entry exists
            $check_query = "SELECT total_points FROM team_tournament_points 
                           WHERE bracket_id = ? AND team_id = ?";
            $stmt = $conn->prepare($check_query);
            $stmt->bind_param("ii", $bracket_id, $team_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                // Insert new entry
                error_log("Creating new points entry");
                $insert_query = "INSERT INTO team_tournament_points 
                               (bracket_id, team_id, total_points, bonus_points) 
                               VALUES (?, ?, ?, 0)";
                $stmt = $conn->prepare($insert_query);
                $stmt->bind_param("iii", $bracket_id, $team_id, $points);
            } else {
                // Update existing entry
                error_log("Updating existing points entry");
                $update_query = "UPDATE team_tournament_points 
                               SET total_points = total_points + ? 
                               WHERE bracket_id = ? AND team_id = ?";
                $stmt = $conn->prepare($update_query);
                $stmt->bind_param("iii", $points, $bracket_id, $team_id);
            }

            if (!$stmt->execute()) {
                throw new Exception("Error updating points: " . $stmt->error);
            }
            error_log("Points update successful. Affected rows: " . $stmt->affected_rows);

            // Update team wins/losses if not a draw
            if ($is_win !== null) {
                $update_team = "UPDATE teams SET " .
                    ($is_win ? "wins = wins + 1" : "losses = losses + 1") .
                    " WHERE team_id = ?";
                $stmt = $conn->prepare($update_team);
                $stmt->bind_param("i", $team_id);
                if (!$stmt->execute()) {
                    throw new Exception("Error updating team stats: " . $stmt->error);
                }
                error_log("Team stats updated. Affected rows: " . $stmt->affected_rows);
            }
        } catch (Exception $e) {
            error_log("Error in updateTeamPoints: " . $e->getMessage());
            throw $e;
        }
    }

    public function process()
    {
        try {
            $this->conn->begin_transaction();

            // Get match data
            $this->match_data = $this->getMatchData();
            if (!$this->match_data) {
                throw new Exception("Error retrieving match data.");
            }

            // Check for draw/tie
            if ($this->match_data['teamA_score'] === $this->match_data['teamB_score']) {
                $this->conn->rollback();
                return [
                    'success' => false,
                    'overtime_required' => true,
                    'error' => 'Match is tied. Please proceed to overtime.'
                ];
            }

            // Process match end
            $this->processMatchEnd();

            $this->conn->commit();
            return [
                'success' => true,
                'message' => 'Match ended successfully',
                'match_id' => $this->match_data['match_id'],
                'status' => 'success'
            ];
        } catch (Exception $e) {
            $this->conn->rollback();
            throw $e;
        } finally {
            $this->conn->close();
        }
    }

    private function getMatchData()
    {
        $query = "SELECT 
            ls.schedule_id,
            ls.game_id,
            ls.teamA_id,
            ls.teamB_id,
            ls.teamA_score,
            ls.teamB_score,
            ls.period,
            s.match_id,
            m.bracket_id
        FROM live_scores ls
        JOIN schedules s ON ls.schedule_id = s.schedule_id
        JOIN matches m ON s.match_id = m.match_id
        WHERE ls.schedule_id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->schedule_id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    private function processMatchEnd()
    {
        try {
            error_log("Processing match end for schedule_id: " . $this->schedule_id);

            // Determine winning and losing teams
            $winning_team_id = ($this->match_data['teamA_score'] > $this->match_data['teamB_score'])
                ? $this->match_data['teamA_id']
                : $this->match_data['teamB_id'];
            $losing_team_id = ($this->match_data['teamA_score'] < $this->match_data['teamB_score'])
                ? $this->match_data['teamA_id']
                : $this->match_data['teamB_id'];

            error_log("Winner: $winning_team_id, Loser: $losing_team_id");
            error_log("Scores - Team A: {$this->match_data['teamA_score']}, Team B: {$this->match_data['teamB_score']}");

            // Insert match result
            $this->insertMatchResult($winning_team_id, $losing_team_id);

            // Update match status
            $this->updateMatchStatus();

            // Update tournament points
            self::updateTournamentPoints(
                $this->conn,
                $this->match_data['bracket_id'],
                $this->match_data['teamA_id'],
                $this->match_data['teamB_id'],
                $this->match_data['teamA_score'],
                $this->match_data['teamB_score']
            );

            // Check if bracket is completed
            $this->checkBracketCompletion();

            // Clean up live scores
            $this->deleteLiveScore();
        } catch (Exception $e) {
            error_log("Error in processMatchEnd: " . $e->getMessage());
            throw $e;
        }
    }

    private function insertMatchResult($winning_team_id, $losing_team_id)
    {
        $query = "INSERT INTO match_results (
            match_id, 
            game_id, 
            team_A_id, 
            team_B_id, 
            score_teamA, 
            score_teamB, 
            winning_team_id, 
            losing_team_id
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param(
            "iiiiiiii",
            $this->match_data['match_id'],
            $this->match_data['game_id'],
            $this->match_data['teamA_id'],
            $this->match_data['teamB_id'],
            $this->match_data['teamA_score'],
            $this->match_data['teamB_score'],
            $winning_team_id,
            $losing_team_id
        );

        if (!$stmt->execute()) {
            throw new Exception("Error inserting match result: " . $stmt->error);
        }
    }

    private function updateMatchStatus()
    {
        $query = "UPDATE matches SET status = 'Finished' WHERE match_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->match_data['match_id']);

        if (!$stmt->execute()) {
            throw new Exception("Error updating match status: " . $stmt->error);
        }
    }

    private function checkBracketCompletion()
    {
        // Check if all matches are finished
        $query = "SELECT COUNT(*) as total_matches, 
                  SUM(CASE WHEN status = 'Finished' THEN 1 ELSE 0 END) as finished_matches
                  FROM matches 
                  WHERE bracket_id = ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->match_data['bracket_id']);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();

        if ($result['total_matches'] == $result['finished_matches']) {
            // Update bracket status
            $query = "UPDATE brackets SET status = 'Completed' WHERE bracket_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $this->match_data['bracket_id']);
            $stmt->execute();

            // Award points to grade sections
            $this->awardGradeSectionPoints();
        }
    }

    private function awardGradeSectionPoints()
    {
        // Get pointing system
        if (!isset($_SESSION['school_id'])) {
            throw new Exception("School ID not found in session");
        }

        $query = "SELECT first_place_points, second_place_points, third_place_points 
                  FROM pointing_system 
                  WHERE school_id = ? AND is_archived = 0";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $_SESSION['school_id']);
        $stmt->execute();
        $pointing_system = $stmt->get_result()->fetch_assoc();

        if (!$pointing_system) {
            throw new Exception("Pointing system not found");
        }

        // Get final standings (top 3 teams) based on total points
        $standings_query = "SELECT t.team_id, t.grade_section_course_id, ttp.total_points,
                           ROW_NUMBER() OVER (ORDER BY ttp.total_points DESC, t.wins DESC) as rank
                           FROM team_tournament_points ttp
                           JOIN teams t ON ttp.team_id = t.team_id
                           WHERE ttp.bracket_id = ?
                           ORDER BY ttp.total_points DESC, t.wins DESC
                           LIMIT 3";

        $stmt = $this->conn->prepare($standings_query);
        $stmt->bind_param("i", $this->match_data['bracket_id']);
        $stmt->execute();
        $standings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

        // Award points to grade sections based on final standings
        foreach ($standings as $team) {
            $points = 0;
            switch ($team['rank']) {
                case 1:
                    $points = $pointing_system['first_place_points'];
                    break;
                case 2:
                    $points = $pointing_system['second_place_points'];
                    break;
                case 3:
                    $points = $pointing_system['third_place_points'];
                    break;
            }

            if ($points > 0) {
                $update_points = "UPDATE grade_section_course 
                                SET Points = COALESCE(Points, 0) + ? 
                                WHERE id = ?";
                $stmt = $this->conn->prepare($update_points);
                $stmt->bind_param("ii", $points, $team['grade_section_course_id']);
                if (!$stmt->execute()) {
                    error_log("Error awarding points to GSC ID: " . $team['grade_section_course_id']);
                }
            }
        }
    }

    private function deleteLiveScore()
    {
        $query = "DELETE FROM live_scores WHERE schedule_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->schedule_id);

        if (!$stmt->execute()) {
            throw new Exception("Error deleting live score: " . $stmt->error);
        }
    }
}

try {
    // 1. Retrieve JSON data from POST request
    $data = json_decode(file_get_contents('php://input'), true);

    // 2. Check if schedule_id is set in the received data
    if (!isset($data['schedule_id'])) {
        throw new Exception("Schedule ID is not set.");
    }

    // 3. Create processor and process match
    $processor = new RoundRobinMatchProcessor($data['schedule_id']);
    $result = $processor->process();

    echo json_encode($result);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'status' => 'error'
    ]);
}
