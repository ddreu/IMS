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

            $this->unarchiveDirectSchoolIdTables($archived_counts);
            $this->unarchiveDepartmentLinkedTables($archived_counts);
            $this->unarchiveGameLinkedTables($archived_counts);
            $this->unarchiveBracketLinkedTables($archived_counts);
            $this->unarchiveMatchLinkedTables($archived_counts);
            $this->unarchiveTeamAndPlayerTables($archived_counts);

            $this->deleteFromArchivesTable();

            $this->conn->commit();
            $this->logUnarchiveAction($archived_counts);

            return ['success' => true, 'message' => 'School data unarchived successfully'];
        } catch (Exception $e) {
            $this->conn->rollback();
            return ['success' => false, 'message' => 'Error unarchiving school data: ' . $e->getMessage()];
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

    private function unarchiveDirectSchoolIdTables(&$archived_counts)
    {
        $tables = ['announcement', 'games', 'game_scoring_rules', 'pointing_system'];
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

    private function unarchiveDepartmentLinkedTables(&$archived_counts)
    {
        $sql = "UPDATE grade_section_course g
                INNER JOIN departments d ON g.department_id = d.id
                SET g.is_archived = 0, g.archived_at = NULL
                WHERE d.school_id = ? AND YEAR(g.archived_at) = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("ii", $this->school_id, $this->year);
        $stmt->execute();
        $archived_counts['grade_section_course'] = $stmt->affected_rows;
        $stmt->close();

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

    private function unarchiveGameLinkedTables(&$archived_counts)
    {
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

// session_start();
// require_once '../connection/conn.php';

// class SchoolUnarchiver
// {
//     private $conn;
//     private $school_id;

//     public function __construct($school_id)
//     {
//         // Use the existing connection function
//         $this->conn = con();
//         if (!$this->conn) {
//             throw new Exception("Database connection failed");
//         }
//         $this->school_id = $school_id;
//     }

//     public function unarchiveSchoolData()
//     {
//         try {
//             // Verify school_id exists
//             $check_school = "SELECT school_id FROM schools WHERE school_id = ?";
//             $stmt = $this->conn->prepare($check_school);
//             $stmt->bind_param("i", $this->school_id);
//             $stmt->execute();
//             $result = $stmt->get_result();

//             if ($result->num_rows === 0) {
//                 throw new Exception("Invalid school ID");
//             }

//             // Start transaction
//             $this->conn->begin_transaction();

//             $archived_counts = [];

//             // 1. Direct school_id tables
//             $this->unarchiveDirectSchoolIdTables($archived_counts);

//             // 2. Department-linked tables
//             $this->unarchiveDepartmentLinkedTables($archived_counts);

//             // 3. Game-linked tables
//             $this->unarchiveGameLinkedTables($archived_counts);

//             // 4. Bracket-linked tables
//             $this->unarchiveBracketLinkedTables($archived_counts);

//             // 5. Match-linked tables
//             $this->unarchiveMatchLinkedTables($archived_counts);

//             // 6. Team and Player-linked tables
//             $this->unarchiveTeamAndPlayerTables($archived_counts);

//             // Commit transaction
//             $this->conn->commit();

//             // Log the unarchiving action
//             $this->logUnarchiveAction($archived_counts);

//             return [
//                 'success' => true,
//                 'message' => 'School data unarchived successfully'
//             ];
//         } catch (Exception $e) {
//             // Rollback transaction on error
//             $this->conn->rollback();
//             return [
//                 'success' => false,
//                 'message' => 'Error unarchiving school data: ' . $e->getMessage()
//             ];
//         }
//     }

//     private function unarchiveDirectSchoolIdTables(&$archived_counts)
//     {
//         $tables = ['announcement', 'games', 'game_scoring_rules', 'pointing_system'];
//         foreach ($tables as $table) {
//             $sql = "UPDATE $table SET is_archived = 0, archived_at = NULL WHERE school_id = ?";
//             $stmt = $this->conn->prepare($sql);
//             $stmt->bind_param("i", $this->school_id);
//             $stmt->execute();
//             $archived_counts[$table] = $stmt->affected_rows;
//             $stmt->close();
//         }
//     }

//     private function unarchiveDepartmentLinkedTables(&$archived_counts)
//     {
//         $sql = "UPDATE grade_section_course g 
//                 INNER JOIN departments d ON g.department_id = d.id 
//                 SET g.is_archived = 0, g.archived_at = NULL 
//                 WHERE d.school_id = ?";
//         $stmt = $this->conn->prepare($sql);
//         $stmt->bind_param("i", $this->school_id);
//         $stmt->execute();
//         $archived_counts['grade_section_course'] = $stmt->affected_rows;
//         $stmt->close();

//         $sql = "UPDATE brackets b 
//                 INNER JOIN departments d ON b.department_id = d.id 
//                 SET b.is_archived = 0, b.archived_at = NULL 
//                 WHERE d.school_id = ?";
//         $stmt = $this->conn->prepare($sql);
//         $stmt->bind_param("i", $this->school_id);
//         $stmt->execute();
//         $archived_counts['brackets'] = $stmt->affected_rows;
//         $stmt->close();
//     }

//     private function unarchiveGameLinkedTables(&$archived_counts)
//     {
//         $sql = "UPDATE game_stats_config gc 
//                 INNER JOIN games g ON gc.game_id = g.game_id 
//                 SET gc.is_archived = 0, gc.archived_at = NULL 
//                 WHERE g.school_id = ?";
//         $stmt = $this->conn->prepare($sql);
//         $stmt->bind_param("i", $this->school_id);
//         $stmt->execute();
//         $archived_counts['game_stats_config'] = $stmt->affected_rows;
//         $stmt->close();
//     }

//     private function unarchiveBracketLinkedTables(&$archived_counts)
//     {
//         $tables = ['tournament_scoring', 'team_tournament_points'];
//         foreach ($tables as $table) {
//             $sql = "UPDATE $table t 
//                     INNER JOIN brackets b ON t.bracket_id = b.bracket_id 
//                     INNER JOIN departments d ON b.department_id = d.id 
//                     SET t.is_archived = 0, t.archived_at = NULL 
//                     WHERE d.school_id = ?";
//             $stmt = $this->conn->prepare($sql);
//             $stmt->bind_param("i", $this->school_id);
//             $stmt->execute();
//             $archived_counts[$table] = $stmt->affected_rows;
//             $stmt->close();
//         }
//     }

//     private function unarchiveMatchLinkedTables(&$archived_counts)
//     {
//         $sql = "UPDATE matches m 
//                 INNER JOIN brackets b ON m.bracket_id = b.bracket_id 
//                 INNER JOIN departments d ON b.department_id = d.id 
//                 SET m.is_archived = 0, m.archived_at = NULL 
//                 WHERE d.school_id = ?";
//         $stmt = $this->conn->prepare($sql);
//         $stmt->bind_param("i", $this->school_id);
//         $stmt->execute();
//         $archived_counts['matches'] = $stmt->affected_rows;
//         $stmt->close();

//         $match_related_tables = [
//             'double_match_info',
//             'match_periods_info',
//             'match_results',
//             'schedules'
//         ];

//         foreach ($match_related_tables as $table) {
//             $sql = "UPDATE $table t 
//                     INNER JOIN matches m ON t.match_id = m.match_id 
//                     INNER JOIN brackets b ON m.bracket_id = b.bracket_id 
//                     INNER JOIN departments d ON b.department_id = d.id 
//                     SET t.is_archived = 0, t.archived_at = NULL 
//                     WHERE d.school_id = ?";
//             $stmt = $this->conn->prepare($sql);
//             $stmt->bind_param("i", $this->school_id);
//             $stmt->execute();
//             $archived_counts[$table] = $stmt->affected_rows;
//             $stmt->close();
//         }
//     }

//     private function unarchiveTeamAndPlayerTables(&$archived_counts)
//     {
//         $sql = "UPDATE teams t 
//                 INNER JOIN grade_section_course g ON t.grade_section_course_id = g.id 
//                 INNER JOIN departments d ON g.department_id = d.id 
//                 SET t.is_archived = 0, t.archived_at = NULL 
//                 WHERE d.school_id = ?";
//         $stmt = $this->conn->prepare($sql);
//         $stmt->bind_param("i", $this->school_id);
//         $stmt->execute();
//         $archived_counts['teams'] = $stmt->affected_rows;
//         $stmt->close();

//         $sql = "UPDATE players p 
//                 INNER JOIN teams t ON p.team_id = t.team_id 
//                 INNER JOIN grade_section_course g ON t.grade_section_course_id = g.id 
//                 INNER JOIN departments d ON g.department_id = d.id 
//                 SET p.is_archived = 0, p.archived_at = NULL 
//                 WHERE d.school_id = ?";
//         $stmt = $this->conn->prepare($sql);
//         $stmt->bind_param("i", $this->school_id);
//         $stmt->execute();
//         $archived_counts['players'] = $stmt->affected_rows;
//         $stmt->close();

//         $player_related_tables = ['players_info', 'player_match_stats'];
//         foreach ($player_related_tables as $table) {
//             $sql = "UPDATE $table pi 
//                     INNER JOIN players p ON pi.player_id = p.player_id 
//                     INNER JOIN teams t ON p.team_id = t.team_id 
//                     INNER JOIN grade_section_course g ON t.grade_section_course_id = g.id 
//                     INNER JOIN departments d ON g.department_id = d.id 
//                     SET pi.is_archived = 0, pi.archived_at = NULL 
//                     WHERE d.school_id = ?";
//             $stmt = $this->conn->prepare($sql);
//             $stmt->bind_param("i", $this->school_id);
//             $stmt->execute();
//             $archived_counts[$table] = $stmt->affected_rows;
//             $stmt->close();
//         }
//     }

//     private function logUnarchiveAction($archived_counts)
//     {
//         $total_unarchived = array_sum($archived_counts);
//         $description = "Unarchived school data: $total_unarchived records across " . count($archived_counts) . " tables";

//         $sql = "INSERT INTO logs (table_name, operation, user_id, description) VALUES (?, ?, ?, ?)";
//         $stmt = $this->conn->prepare($sql);
//         $table_name = "multiple_tables";
//         $operation = "UNARCHIVE";
//         $user_id = $_SESSION['user_id'] ?? null;

//         $stmt->bind_param("ssis", $table_name, $operation, $user_id, $description);
//         $stmt->execute();
//         $stmt->close();
//     }

//     public function __destruct()
//     {
//         $this->conn->close();
//     }
// }

// // Handle incoming request
// if ($_SERVER['REQUEST_METHOD'] === 'POST') {
//     header('Content-Type: application/json');

//     // Verify user is logged in and has appropriate permissions
//     if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'School Admin') {
//         echo json_encode([
//             'success' => false,
//             'message' => 'Unauthorized access'
//         ]);
//         exit;
//     }

//     // Get school_id from session instead of POST for security
//     $school_id = $_SESSION['school_id'] ?? null;

//     if (!$school_id) {
//         echo json_encode([
//             'success' => false,
//             'message' => 'School ID not found in session'
//         ]);
//         exit;
//     }

//     try {
//         $unarchiver = new SchoolUnarchiver($school_id);
//         $result = $unarchiver->unarchiveSchoolData();
//         echo json_encode($result);
//     } catch (Exception $e) {
//         echo json_encode([
//             'success' => false,
//             'message' => 'Error: ' . $e->getMessage()
//         ]);
//     }
// }
