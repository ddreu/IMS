<?php

session_start();
require_once '../connection/conn.php';

class SchoolUnarchiver
{
    private $conn;
    private $school_id;
    private $year;

    public function __construct($school_id, $year)
    {
        $this->conn = con();
        if (!$this->conn) {
            throw new Exception("Database connection failed");
        }

        $this->school_id = $school_id;
        $this->year = $year;
    }

    public function unarchiveSchoolData()
    {
        try {
            $check_school = "SELECT school_id FROM schools WHERE school_id = ?";
            $stmt = $this->conn->prepare($check_school);
            $stmt->bind_param("i", $this->school_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                throw new Exception("Invalid school ID");
            }

            $this->conn->begin_transaction();
            $archived_counts = [];

            // First, restore original references in users and committee tables
            $this->restoreOriginalReferences();

            // Then delete the duplicate records
            $this->deleteDuplicateRecords();

            // Finally restore the original archived records
            $this->unarchiveDirectSchoolIdTables($archived_counts);
            $this->unarchiveDepartmentLinkedTables($archived_counts);
            $this->unarchiveGameLinkedTables($archived_counts);
            $this->unarchiveBracketLinkedTables($archived_counts);
            $this->unarchiveMatchLinkedTables($archived_counts);
            $this->unarchiveTeamAndPlayerTables($archived_counts);

            $this->deleteFromArchivesTable();

            $this->conn->commit();

            // Get accurate count of unarchived records for this year
            $archived_counts = $this->countUnarchived();
            $this->logUnarchiveAction($archived_counts);

            return ['success' => true, 'message' => 'School data unarchived successfully'];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Error unarchiving school data: ' . $e->getMessage()];
        }
    }

    private function restoreOriginalReferences()
    {
        // Get mappings of new to old IDs for departments and games
        $dept_sql = "SELECT d1.id as new_id, d2.id as old_id 
                    FROM departments d1 
                    JOIN departments d2 ON d1.department_name = d2.department_name 
                    WHERE d1.school_id = ? AND d1.is_archived = 0 
                    AND d2.school_id = ? AND d2.is_archived = 1 
                    AND YEAR(d2.archived_at) = ?";

        $game_sql = "SELECT g1.game_id as new_id, g2.game_id as old_id 
                    FROM games g1 
                    JOIN games g2 ON g1.game_name = g2.game_name 
                    WHERE g1.school_id = ? AND g1.is_archived = 0 
                    AND g2.school_id = ? AND g2.is_archived = 1 
                    AND YEAR(g2.archived_at) = ?";

        // Get department mappings
        $stmt = $this->conn->prepare($dept_sql);
        $stmt->bind_param("iii", $this->school_id, $this->school_id, $this->year);
        $stmt->execute();
        $dept_mappings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Get game mappings
        $stmt = $this->conn->prepare($game_sql);
        $stmt->bind_param("iii", $this->school_id, $this->school_id, $this->year);
        $stmt->execute();
        $game_mappings = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Restore department references in users table
        foreach ($dept_mappings as $mapping) {
            $sql = "UPDATE users 
                    SET department = ? 
                    WHERE school_id = ? AND department = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iii", $mapping['old_id'], $this->school_id, $mapping['new_id']);
            $stmt->execute();
            $stmt->close();
        }

        // Restore game references in users table
        foreach ($game_mappings as $mapping) {
            $sql = "UPDATE users 
                    SET game_id = ? 
                    WHERE school_id = ? AND game_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iii", $mapping['old_id'], $this->school_id, $mapping['new_id']);
            $stmt->execute();
            $stmt->close();
        }

        // Restore committee_games references
        foreach ($game_mappings as $mapping) {
            $sql = "UPDATE committee_games cg 
                    INNER JOIN users u ON cg.committee_id = u.id 
                    SET cg.game_id = ? 
                    WHERE u.school_id = ? AND cg.game_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iii", $mapping['old_id'], $this->school_id, $mapping['new_id']);
            $stmt->execute();
            $stmt->close();
        }

        // Restore committee_departments references
        foreach ($dept_mappings as $mapping) {
            $sql = "UPDATE committee_departments cd 
                    INNER JOIN users u ON cd.committee_id = u.id 
                    SET cd.department_id = ? 
                    WHERE u.school_id = ? AND cd.department_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iii", $mapping['old_id'], $this->school_id, $mapping['new_id']);
            $stmt->execute();
            $stmt->close();
        }
    }

    private function deleteDuplicateRecords()
    {
        // Delete duplicated records in reverse order of creation to maintain referential integrity
        // We don't include game_scoring_rules and game_stats_config here since they were never duplicated
        $tables = [
            'teams',
            'grade_section_course',
            'games',
            'departments',
            'pointing_system'
        ];

        foreach ($tables as $table) {
            if ($table === 'teams') {
                $sql = "DELETE t FROM teams t 
                        INNER JOIN grade_section_course g ON t.grade_section_course_id = g.id 
                        INNER JOIN departments d ON g.department_id = d.id 
                        WHERE d.school_id = ? AND t.is_archived = 0";
            } else if ($table === 'grade_section_course') {
                $sql = "DELETE g FROM grade_section_course g 
                        INNER JOIN departments d ON g.department_id = d.id 
                        WHERE d.school_id = ? AND g.is_archived = 0";
            } else {
                $sql = "DELETE FROM $table WHERE school_id = ? AND is_archived = 0";
            }

            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("i", $this->school_id);
            $stmt->execute();
            $stmt->close();
        }
    }

    private function unarchiveDirectSchoolIdTables(&$archived_counts)
    {
        // Now just restore the original records
        // Keep game_scoring_rules since it was archived but not duplicated
        $tables = ['announcement', 'games', 'game_scoring_rules', 'pointing_system', 'departments'];
        foreach ($tables as $table) {
            $sql = "UPDATE $table 
                    SET is_archived = 0, archived_at = NULL 
                    WHERE school_id = ? AND YEAR(archived_at) = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $this->school_id, $this->year);
            $stmt->execute();
            $archived_counts[$table] = $stmt->affected_rows;
            $stmt->close();
        }
    }

    private function unarchiveGameLinkedTables(&$archived_counts)
    {
        // Restore game_stats_config since it was archived but not duplicated
        $sql = "UPDATE game_stats_config gc
                INNER JOIN games g ON gc.game_id = g.game_id
                SET gc.is_archived = 0, gc.archived_at = NULL
                WHERE g.school_id = ? AND YEAR(gc.archived_at) = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $this->school_id, $this->year);
        $stmt->execute();
        $archived_counts['game_stats_config'] = $stmt->affected_rows;
        $stmt->close();
    }

    private function unarchiveDepartmentLinkedTables(&$archived_counts)
    {
        // Restore grade_section_course with original Points value
        $sql = "UPDATE grade_section_course g
                INNER JOIN departments d ON g.department_id = d.id
                SET g.is_archived = 0, g.archived_at = NULL
                WHERE d.school_id = ? AND YEAR(g.archived_at) = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $this->school_id, $this->year);
        $stmt->execute();
        $archived_counts['grade_section_course'] = $stmt->affected_rows;
        $stmt->close();

        // Restore brackets
        $sql = "UPDATE brackets b
                INNER JOIN departments d ON b.department_id = d.id
                SET b.is_archived = 0, b.archived_at = NULL
                WHERE d.school_id = ? AND YEAR(b.archived_at) = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $this->school_id, $this->year);
        $stmt->execute();
        $archived_counts['brackets'] = $stmt->affected_rows;
        $stmt->close();
    }

    private function unarchiveBracketLinkedTables(&$archived_counts)
    {
        $tables = ['tournament_scoring', 'team_tournament_points'];
        foreach ($tables as $table) {
            $sql = "UPDATE $table t
                    INNER JOIN brackets b ON t.bracket_id = b.bracket_id
                    INNER JOIN departments d ON b.department_id = d.id
                    SET t.is_archived = 0, t.archived_at = NULL
                    WHERE d.school_id = ? AND YEAR(t.archived_at) = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $this->school_id, $this->year);
            $stmt->execute();
            $archived_counts[$table] = $stmt->affected_rows;
            $stmt->close();
        }
    }

    private function unarchiveMatchLinkedTables(&$archived_counts)
    {
        $sql = "UPDATE matches m
                INNER JOIN brackets b ON m.bracket_id = b.bracket_id
                INNER JOIN departments d ON b.department_id = d.id
                SET m.is_archived = 0, m.archived_at = NULL
                WHERE d.school_id = ? AND YEAR(m.archived_at) = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $this->school_id, $this->year);
        $stmt->execute();
        $archived_counts['matches'] = $stmt->affected_rows;
        $stmt->close();

        $related = ['double_match_info', 'match_periods_info', 'match_results', 'schedules'];
        foreach ($related as $table) {
            $sql = "UPDATE $table t
                    INNER JOIN matches m ON t.match_id = m.match_id
                    INNER JOIN brackets b ON m.bracket_id = b.bracket_id
                    INNER JOIN departments d ON b.department_id = d.id
                    SET t.is_archived = 0, t.archived_at = NULL
                    WHERE d.school_id = ? AND YEAR(t.archived_at) = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $this->school_id, $this->year);
            $stmt->execute();
            $archived_counts[$table] = $stmt->affected_rows;
            $stmt->close();
        }
    }

    private function unarchiveTeamAndPlayerTables(&$archived_counts)
    {
        // Restore teams with original wins and losses
        $sql = "UPDATE teams t
                INNER JOIN grade_section_course g ON t.grade_section_course_id = g.id
                INNER JOIN departments d ON g.department_id = d.id
                SET t.is_archived = 0, t.archived_at = NULL
                WHERE d.school_id = ? AND YEAR(t.archived_at) = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $this->school_id, $this->year);
        $stmt->execute();
        $archived_counts['teams'] = $stmt->affected_rows;
        $stmt->close();

        // Restore players
        $sql = "UPDATE players p
                INNER JOIN teams t ON p.team_id = t.team_id
                INNER JOIN grade_section_course g ON t.grade_section_course_id = g.id
                INNER JOIN departments d ON g.department_id = d.id
                SET p.is_archived = 0, p.archived_at = NULL
                WHERE d.school_id = ? AND YEAR(p.archived_at) = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $this->school_id, $this->year);
        $stmt->execute();
        $archived_counts['players'] = $stmt->affected_rows;
        $stmt->close();

        // Restore player-related tables
        $player_related_tables = ['players_info', 'player_match_stats'];
        foreach ($player_related_tables as $table) {
            $sql = "UPDATE $table pi
                    INNER JOIN players p ON pi.player_id = p.player_id
                    INNER JOIN teams t ON p.team_id = t.team_id
                    INNER JOIN grade_section_course g ON t.grade_section_course_id = g.id
                    INNER JOIN departments d ON g.department_id = d.id
                    SET pi.is_archived = 0, pi.archived_at = NULL
                    WHERE d.school_id = ? AND YEAR(pi.archived_at) = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("ii", $this->school_id, $this->year);
            $stmt->execute();
            $archived_counts[$table] = $stmt->affected_rows;
            $stmt->close();
        }
    }

    private function deleteFromArchivesTable()
    {
        $sql = "DELETE FROM archives WHERE school_id = ? AND YEAR(archived_at) = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $this->school_id, $this->year);
        $stmt->execute();
        $stmt->close();
    }

    private function logUnarchiveAction($archived_counts)
    {
        $total_unarchived = array_sum($archived_counts);
        $description = "Unarchived school data for year {$this->year}: $total_unarchived records across " . count($archived_counts) . " tables";

        $sql = "INSERT INTO logs (table_name, operation, user_id, description) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $table_name = "multiple_tables";
        $operation = "UNARCHIVE";
        $user_id = $_SESSION['user_id'] ?? null;

        $stmt->bind_param("ssis", $table_name, $operation, $user_id, $description);
        $stmt->execute();
        $stmt->close();
    }

    private function countUnarchived()
    {
        $report = [];

        // Direct school_id tables
        $direct_tables = [
            'announcement' => "SELECT COUNT(*) FROM announcement WHERE school_id = ? AND is_archived = 0 AND YEAR(archived_at) = ?",
            'games' => "SELECT COUNT(*) FROM games WHERE school_id = ? AND is_archived = 0 AND YEAR(archived_at) = ?",
            'game_scoring_rules' => "SELECT COUNT(*) FROM game_scoring_rules WHERE school_id = ? AND is_archived = 0 AND YEAR(archived_at) = ?",
            'pointing_system' => "SELECT COUNT(*) FROM pointing_system WHERE school_id = ? AND is_archived = 0 AND YEAR(archived_at) = ?",
            'departments' => "SELECT COUNT(*) FROM departments WHERE school_id = ? AND is_archived = 0 AND YEAR(archived_at) = ?"
        ];

        // Department-linked tables
        $dept_tables = [
            'grade_section_course' => "SELECT COUNT(*) FROM grade_section_course g 
                                     INNER JOIN departments d ON g.department_id = d.id 
                                     WHERE d.school_id = ? AND g.is_archived = 0 AND YEAR(g.archived_at) = ?",
            'brackets' => "SELECT COUNT(*) FROM brackets b 
                         INNER JOIN departments d ON b.department_id = d.id 
                         WHERE d.school_id = ? AND b.is_archived = 0 AND YEAR(b.archived_at) = ?"
        ];

        // Game-linked tables
        $game_tables = [
            'game_stats_config' => "SELECT COUNT(*) FROM game_stats_config gc 
                                  INNER JOIN games g ON gc.game_id = g.game_id 
                                  WHERE g.school_id = ? AND gc.is_archived = 0 AND YEAR(gc.archived_at) = ?"
        ];

        // Match-linked tables
        $match_tables = [
            'matches' => "SELECT COUNT(*) FROM matches m 
                         INNER JOIN brackets b ON m.bracket_id = b.bracket_id 
                         INNER JOIN departments d ON b.department_id = d.id 
                         WHERE d.school_id = ? AND m.is_archived = 0 AND YEAR(m.archived_at) = ?",
            'match_periods' => "SELECT COUNT(*) FROM match_periods_info mp 
                              INNER JOIN matches m ON mp.match_id = m.match_id 
                              INNER JOIN brackets b ON m.bracket_id = b.bracket_id 
                              INNER JOIN departments d ON b.department_id = d.id 
                              WHERE d.school_id = ? AND mp.is_archived = 0 AND YEAR(mp.archived_at) = ?"
        ];

        // Team and Player tables
        $player_tables = [
            'teams' => "SELECT COUNT(*) FROM teams t 
                       INNER JOIN grade_section_course g ON t.grade_section_course_id = g.id 
                       INNER JOIN departments d ON g.department_id = d.id 
                       WHERE d.school_id = ? AND t.is_archived = 0 AND YEAR(t.archived_at) = ?",
            'players' => "SELECT COUNT(*) FROM players p 
                         INNER JOIN teams t ON p.team_id = t.team_id 
                         INNER JOIN grade_section_course g ON t.grade_section_course_id = g.id 
                         INNER JOIN departments d ON g.department_id = d.id 
                         WHERE d.school_id = ? AND p.is_archived = 0 AND YEAR(p.archived_at) = ?"
        ];

        $all_queries = array_merge($direct_tables, $dept_tables, $game_tables, $match_tables, $player_tables);

        foreach ($all_queries as $table => $query) {
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("ii", $this->school_id, $this->year);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_array()[0];
            $report[$table] = $count;
            $stmt->close();
        }

        return $report;
    }

    public function __destruct()
    {
        $this->conn->close();
    }
}

// 🟩 Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'School Admin') {
        echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
        exit;
    }

    $school_id = $_SESSION['school_id'] ?? null;
    $year = $_POST['year'] ?? null;

    if (!$school_id || !$year || !is_numeric($year)) {
        echo json_encode(['success' => false, 'message' => 'Missing school ID or year']);
        exit;
    }

    try {
        $unarchiver = new SchoolUnarchiver($school_id, (int)$year);
        $result = $unarchiver->unarchiveSchoolData();
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
    }
}
