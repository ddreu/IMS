<?php
session_start();
require_once '../connection/conn.php';
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class SchoolArchiver
{
    private $conn;
    private $school_id;

    public function __construct($school_id)
    {
        // Use the existing connection function
        $this->conn = con();
        if (!$this->conn) {
            throw new Exception("Database connection failed");
        }
        $this->school_id = $school_id;
    }

    public function archiveSchoolData()
    {
        try {
            $current_timestamp = date('Y-m-d H:i:s');
            // $current_timestamp = '2029-01-01 20:00:00';

            // Verify school_id exists
            $check_school = "SELECT school_id FROM schools WHERE school_id = ?";
            $stmt = $this->conn->prepare($check_school);
            $stmt->bind_param("i", $this->school_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                throw new Exception("Invalid school ID");
            }

            // Check if there's already an archive for this year
            $year = date('Y', strtotime($current_timestamp));
            $check_archive = "SELECT * FROM archives WHERE school_id = ? AND YEAR(archived_at) = ?";
            $stmt = $this->conn->prepare($check_archive);
            $stmt->bind_param("ii", $this->school_id, $year);
            $stmt->execute();
            if ($stmt->get_result()->num_rows > 0) {
                throw new Exception("Data for year " . $year . " has already been archived");
            }
            $stmt->close();

            // Start transaction
            $this->conn->begin_transaction();

            $archived_counts = [];

            // First archive all the data
            // 1. Direct school_id tables
            $this->archiveDirectSchoolIdTables($current_timestamp, $archived_counts);

            // 2. Department-linked tables
            $this->archiveDepartmentLinkedTables($current_timestamp, $archived_counts);

            // 3. Game-linked tables
            $this->archiveGameLinkedTables($current_timestamp, $archived_counts);

            // 4. Bracket-linked tables
            $this->archiveBracketLinkedTables($current_timestamp, $archived_counts);

            // 5. Match-linked tables
            $this->archiveMatchLinkedTables($current_timestamp, $archived_counts);

            // 6. Team and Player-linked tables
            $this->archiveTeamAndPlayerTables($current_timestamp, $archived_counts);

            // After archiving, replicate the necessary data
            $this->replicateRequiredData($current_timestamp);

            // Update committee references to point to new IDs
            $this->updateCommitteeReferences($current_timestamp);

            // Generate archive report
            $report = $this->generateDetailedReport($current_timestamp);

            // Generate Excel file
            $excel_file = $this->generateExcelReport($report);

            // Get the full URL for the Excel file
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'];

            // Add the correct path to the file
            $base_path = '/ims/archive/';
            $excel_url = $protocol . $host . $base_path . ltrim($excel_file, '/');

            // Commit transaction
            $sql = "INSERT INTO archives (school_id, archived_at) VALUES (?, ?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("is", $this->school_id, $current_timestamp);
            $stmt->execute();
            $stmt->close();

            $this->conn->commit();

            // Log the archiving action
            $this->logArchiveAction($archived_counts);

            return [
                'success' => true,
                'message' => 'School data archived successfully',
                'report' => $report,
                'excel_file' => $excel_file,
                'excel_url' => $excel_url
            ];
        } catch (Exception $e) {
            // Rollback transaction on error
            $this->conn->rollback();
            return [
                'success' => false,
                'message' => 'Error archiving school data: ' . $e->getMessage()
            ];
        }
    }

    private function logArchiveAction($archived_counts)
    {
        $total_archived = array_sum($archived_counts);
        $description = "Archived school data: $total_archived records across " . count($archived_counts) . " tables";

        $sql = "INSERT INTO logs (table_name, operation, user_id, description) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($sql);
        $table_name = "multiple_tables";
        $operation = "ARCHIVE";
        $user_id = $_SESSION['user_id'] ?? null;

        $stmt->bind_param("ssis", $table_name, $operation, $user_id, $description);
        $stmt->execute();
        $stmt->close();
    }

    private function replicateRequiredData($timestamp)
    {
        // Store the IDs of newly inserted records
        $new_department_ids = [];
        $new_game_ids = [];
        $new_gsc_ids = [];

        // 1. Replicate departments and capture new IDs
        $sql = "INSERT INTO departments (department_name, school_id, is_archived, archived_at)
                SELECT department_name, school_id, 0, NULL
                FROM departments
                WHERE school_id = ? AND is_archived = 1 AND DATE(archived_at) = DATE(?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("is", $this->school_id, $timestamp);
        $stmt->execute();
        $last_insert_id = $stmt->insert_id;
        $affected_rows = $stmt->affected_rows;
        $stmt->close();

        // Get the mapping of old department IDs to new ones - only from this specific archive operation
        $sql = "SELECT d1.id as old_id, d2.id as new_id 
                FROM departments d1 
                JOIN departments d2 ON d1.department_name = d2.department_name 
                WHERE d1.school_id = ? 
                AND d1.is_archived = 1 
                AND DATE(d1.archived_at) = DATE(?) 
                AND d2.school_id = ? 
                AND d2.is_archived = 0 
                AND d2.id >= ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isis", $this->school_id, $timestamp, $this->school_id, $last_insert_id);
        $stmt->execute();
        $dept_id_mapping = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // 2. Replicate games and capture new IDs
        $sql = "INSERT INTO games (game_name, number_of_players, category, environment, school_id, is_archived, archived_at)
                SELECT game_name, number_of_players, category, environment, school_id, 0, NULL
                FROM games
                WHERE school_id = ? AND is_archived = 1 AND DATE(archived_at) = DATE(?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("is", $this->school_id, $timestamp);
        $stmt->execute();
        $last_game_insert_id = $stmt->insert_id;
        $stmt->close();

        // Get the mapping of old game IDs to new ones - only from this specific archive operation
        $sql = "SELECT g1.game_id as old_id, g2.game_id as new_id 
                FROM games g1 
                JOIN games g2 ON g1.game_name = g2.game_name 
                WHERE g1.school_id = ? 
                AND g1.is_archived = 1 
                AND DATE(g1.archived_at) = DATE(?) 
                AND g2.school_id = ? 
                AND g2.is_archived = 0 
                AND g2.game_id >= ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isis", $this->school_id, $timestamp, $this->school_id, $last_game_insert_id);
        $stmt->execute();
        $game_id_mapping = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // 3. Replicate pointing system
        $sql = "INSERT INTO pointing_system (school_id, first_place_points, second_place_points, third_place_points, is_archived, archived_at)
                SELECT school_id, first_place_points, second_place_points, third_place_points, 0, NULL
                FROM pointing_system
                WHERE school_id = ? AND is_archived = 1 AND DATE(archived_at) = DATE(?)";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("is", $this->school_id, $timestamp);
        $stmt->execute();
        $stmt->close();

        // 4. Replicate grade_section_course and capture new IDs
        foreach ($dept_id_mapping as $dept_map) {
            $sql = "INSERT INTO grade_section_course (
                    department_id, grade_level, strand, section_name, course_name, Points, is_archived, archived_at
                )
                SELECT ?, grade_level, strand, section_name, course_name, 0, 0, NULL
                FROM grade_section_course
                WHERE department_id = ? AND is_archived = 1 AND DATE(archived_at) = DATE(?)";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iis", $dept_map['new_id'], $dept_map['old_id'], $timestamp);
            $stmt->execute();
            if (!isset($last_gsc_insert_id)) {
                $last_gsc_insert_id = $stmt->insert_id;
            }
            $stmt->close();
        }

        // Get the mapping of old grade_section_course IDs to new ones
        $sql = "SELECT gsc1.id as old_id, gsc2.id as new_id 
                FROM grade_section_course gsc1 
                JOIN grade_section_course gsc2 
                ON gsc1.section_name = gsc2.section_name 
                AND gsc1.grade_level = gsc2.grade_level
                WHERE gsc1.is_archived = 1 
                AND DATE(gsc1.archived_at) = DATE(?) 
                AND gsc2.is_archived = 0
                AND gsc2.id >= ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $timestamp, $last_gsc_insert_id);
        $stmt->execute();
        $gsc_id_mapping = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // 5. Replicate teams
        foreach ($game_id_mapping as $game_map) {
            foreach ($gsc_id_mapping as $gsc_map) {
                $sql = "INSERT INTO teams (
                    team_name, game_id, grade_section_course_id, wins, losses, is_archived, archived_at
                )
                SELECT team_name, ?, ?, 0, 0, 0, NULL
                FROM teams
                WHERE game_id = ? AND grade_section_course_id = ? 
                AND is_archived = 1 AND DATE(archived_at) = DATE(?)";
                $stmt = $this->conn->prepare($sql);
                $stmt->bind_param(
                    "iiiis",
                    $game_map['new_id'],
                    $gsc_map['new_id'],
                    $game_map['old_id'],
                    $gsc_map['old_id'],
                    $timestamp
                );
                $stmt->execute();
                $stmt->close();
            }
        }

        // 6. Update users table to point to new department and game IDs
        // First, update department references
        foreach ($dept_id_mapping as $dept_map) {
            $sql = "UPDATE users 
                    SET department = ? 
                    WHERE school_id = ? 
                    AND department = ? 
                    AND is_archived = 0";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iii", $dept_map['new_id'], $this->school_id, $dept_map['old_id']);
            $stmt->execute();
            $stmt->close();
        }

        // Then, update game references
        foreach ($game_id_mapping as $game_map) {
            $sql = "UPDATE users 
                    SET game_id = ? 
                    WHERE school_id = ? 
                    AND game_id = ? 
                    AND is_archived = 0";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iii", $game_map['new_id'], $this->school_id, $game_map['old_id']);
            $stmt->execute();
            $stmt->close();
        }
    }

    private function updateCommitteeReferences($timestamp)
    {
        // Get the mapping of old game IDs to new game IDs
        $sql = "SELECT g1.game_id as old_id, g2.game_id as new_id 
                FROM games g1 
                JOIN games g2 ON g1.game_name = g2.game_name 
                WHERE g1.school_id = ? AND g1.is_archived = 1 AND g1.archived_at = ? 
                AND g2.school_id = ? AND g2.is_archived = 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isi", $this->school_id, $timestamp, $this->school_id);
        $stmt->execute();
        $game_id_mapping = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Get the mapping of old department IDs to new department IDs
        $sql = "SELECT d1.id as old_id, d2.id as new_id 
                FROM departments d1 
                JOIN departments d2 ON d1.department_name = d2.department_name 
                WHERE d1.school_id = ? AND d1.is_archived = 1 AND d1.archived_at = ? 
                AND d2.school_id = ? AND d2.is_archived = 0";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("isi", $this->school_id, $timestamp, $this->school_id);
        $stmt->execute();
        $dept_id_mapping = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        // Update committee_games to point to new game IDs
        foreach ($game_id_mapping as $game_map) {
            $sql = "UPDATE committee_games cg 
                    INNER JOIN users u ON cg.committee_id = u.id 
                    SET cg.game_id = ? 
                    WHERE u.school_id = ? AND cg.game_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iii", $game_map['new_id'], $this->school_id, $game_map['old_id']);
            $stmt->execute();
            $stmt->close();
        }

        // Update committee_departments to point to new department IDs
        foreach ($dept_id_mapping as $dept_map) {
            $sql = "UPDATE committee_departments cd 
                    INNER JOIN users u ON cd.committee_id = u.id 
                    SET cd.department_id = ? 
                    WHERE u.school_id = ? AND cd.department_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("iii", $dept_map['new_id'], $this->school_id, $dept_map['old_id']);
            $stmt->execute();
            $stmt->close();
        }
    }

    private function archiveDirectSchoolIdTables($timestamp, &$archived_counts)
    {
        $tables = ['announcement', 'games', 'game_scoring_rules', 'pointing_system', 'departments'];
        foreach ($tables as $table) {
            $sql = "UPDATE $table SET is_archived = 1, archived_at = ? WHERE school_id = ? AND is_archived = 0";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $timestamp, $this->school_id);
            $stmt->execute();
            $archived_counts[$table] = $stmt->affected_rows;
            $stmt->close();
        }
    }

    private function archiveDepartmentLinkedTables($timestamp, &$archived_counts)
    {
        // Archive grade_section_course
        $sql = "UPDATE grade_section_course g 
                INNER JOIN departments d ON g.department_id = d.id 
                SET g.is_archived = 1, g.archived_at = ? 
                WHERE d.school_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $timestamp, $this->school_id);
        $stmt->execute();
        $archived_counts['grade_section_course'] = $stmt->affected_rows;
        $stmt->close();

        // Archive brackets
        $sql = "UPDATE brackets b 
                INNER JOIN departments d ON b.department_id = d.id 
                SET b.is_archived = 1, b.archived_at = ? 
                WHERE d.school_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $timestamp, $this->school_id);
        $stmt->execute();
        $archived_counts['brackets'] = $stmt->affected_rows;
        $stmt->close();
    }

    private function archiveGameLinkedTables($timestamp, &$archived_counts)
    {
        // Archive game_stats_config
        $sql = "UPDATE game_stats_config gc 
                INNER JOIN games g ON gc.game_id = g.game_id 
                SET gc.is_archived = 1, gc.archived_at = ? 
                WHERE g.school_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $timestamp, $this->school_id);
        $stmt->execute();
        $archived_counts['game_stats_config'] = $stmt->affected_rows;
        $stmt->close();
    }

    private function archiveBracketLinkedTables($timestamp, &$archived_counts)
    {
        // Archive tournament_scoring and team_tournament_points
        $tables = ['tournament_scoring', 'team_tournament_points'];
        foreach ($tables as $table) {
            $sql = "UPDATE $table t 
                    INNER JOIN brackets b ON t.bracket_id = b.bracket_id 
                    INNER JOIN departments d ON b.department_id = d.id 
                    SET t.is_archived = 1, t.archived_at = ? 
                    WHERE d.school_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $timestamp, $this->school_id);
            $stmt->execute();
            $archived_counts[$table] = $stmt->affected_rows;
            $stmt->close();
        }
    }

    private function archiveMatchLinkedTables($timestamp, &$archived_counts)
    {
        // Archive matches first
        $sql = "UPDATE matches m 
                INNER JOIN brackets b ON m.bracket_id = b.bracket_id 
                INNER JOIN departments d ON b.department_id = d.id 
                SET m.is_archived = 1, m.archived_at = ? 
                WHERE d.school_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $timestamp, $this->school_id);
        $stmt->execute();
        $archived_counts['matches'] = $stmt->affected_rows;
        $stmt->close();

        // Archive match-related tables
        $match_related_tables = [
            'double_match_info',
            'match_periods_info',
            'match_results',
            'schedules'
        ];

        foreach ($match_related_tables as $table) {
            $sql = "UPDATE $table t 
                    INNER JOIN matches m ON t.match_id = m.match_id 
                    INNER JOIN brackets b ON m.bracket_id = b.bracket_id 
                    INNER JOIN departments d ON b.department_id = d.id 
                    SET t.is_archived = 1, t.archived_at = ? 
                    WHERE d.school_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $timestamp, $this->school_id);
            $stmt->execute();
            $archived_counts[$table] = $stmt->affected_rows;
            $stmt->close();
        }
    }

    private function archiveTeamAndPlayerTables($timestamp, &$archived_counts)
    {
        // Archive teams
        $sql = "UPDATE teams t 
                INNER JOIN grade_section_course g ON t.grade_section_course_id = g.id 
                INNER JOIN departments d ON g.department_id = d.id 
                SET t.is_archived = 1, t.archived_at = ? 
                WHERE d.school_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $timestamp, $this->school_id);
        $stmt->execute();
        $archived_counts['teams'] = $stmt->affected_rows;
        $stmt->close();

        // Archive players
        $sql = "UPDATE players p 
                INNER JOIN teams t ON p.team_id = t.team_id 
                INNER JOIN grade_section_course g ON t.grade_section_course_id = g.id 
                INNER JOIN departments d ON g.department_id = d.id 
                SET p.is_archived = 1, p.archived_at = ? 
                WHERE d.school_id = ?";
        $stmt = $this->conn->prepare($sql);
        $stmt->bind_param("si", $timestamp, $this->school_id);
        $stmt->execute();
        $archived_counts['players'] = $stmt->affected_rows;
        $stmt->close();

        // Archive players_info and player_match_stats
        $player_related_tables = ['players_info', 'player_match_stats'];
        foreach ($player_related_tables as $table) {
            $sql = "UPDATE $table pi 
                    INNER JOIN players p ON pi.player_id = p.player_id 
                    INNER JOIN teams t ON p.team_id = t.team_id 
                    INNER JOIN grade_section_course g ON t.grade_section_course_id = g.id 
                    INNER JOIN departments d ON g.department_id = d.id 
                    SET pi.is_archived = 1, pi.archived_at = ? 
                    WHERE d.school_id = ?";
            $stmt = $this->conn->prepare($sql);
            $stmt->bind_param("si", $timestamp, $this->school_id);
            $stmt->execute();
            $archived_counts[$table] = $stmt->affected_rows;
            $stmt->close();
        }
    }

    private function generateDetailedReport($timestamp)
    {
        $report = [];

        // Direct school_id tables
        $direct_tables = [
            'Announcements' => "SELECT COUNT(*) FROM announcement WHERE school_id = ? AND is_archived = 1 AND DATE(archived_at) = DATE(?)",
            'Games' => "SELECT COUNT(*) FROM games WHERE school_id = ? AND is_archived = 1 AND DATE(archived_at) = DATE(?)",
            'Game Rules' => "SELECT COUNT(*) FROM game_scoring_rules WHERE school_id = ? AND is_archived = 1 AND DATE(archived_at) = DATE(?)",
            'Pointing System' => "SELECT COUNT(*) FROM pointing_system WHERE school_id = ? AND is_archived = 1 AND DATE(archived_at) = DATE(?)"
        ];

        // Department-linked tables
        $dept_tables = [
            'Grade Sections/Courses' => "SELECT COUNT(*) FROM grade_section_course g 
                                       INNER JOIN departments d ON g.department_id = d.id 
                                       WHERE d.school_id = ? AND g.is_archived = 1 AND DATE(g.archived_at) = DATE(?)",
            'Brackets' => "SELECT COUNT(*) FROM brackets b 
                          INNER JOIN departments d ON b.department_id = d.id 
                          WHERE d.school_id = ? AND b.is_archived = 1 AND DATE(b.archived_at) = DATE(?)"
        ];

        // Game-linked tables
        $game_tables = [
            'Game Stats Config' => "SELECT COUNT(*) FROM game_stats_config gc 
                                  INNER JOIN games g ON gc.game_id = g.game_id 
                                  WHERE g.school_id = ? AND gc.is_archived = 1 AND DATE(gc.archived_at) = DATE(?)"
        ];

        // Match-linked tables
        $match_tables = [
            'Matches' => "SELECT COUNT(*) FROM matches m 
                         INNER JOIN brackets b ON m.bracket_id = b.bracket_id 
                         INNER JOIN departments d ON b.department_id = d.id 
                         WHERE d.school_id = ? AND m.is_archived = 1 AND DATE(m.archived_at) = DATE(?)",
            'Match Periods' => "SELECT COUNT(*) FROM match_periods_info mp 
                              INNER JOIN matches m ON mp.match_id = m.match_id 
                              INNER JOIN brackets b ON m.bracket_id = b.bracket_id 
                              INNER JOIN departments d ON b.department_id = d.id 
                              WHERE d.school_id = ? AND mp.is_archived = 1 AND DATE(mp.archived_at) = DATE(?)"
        ];

        // Team and Player tables
        $player_tables = [
            'Teams' => "SELECT COUNT(*) FROM teams t 
                       INNER JOIN grade_section_course g ON t.grade_section_course_id = g.id 
                       INNER JOIN departments d ON g.department_id = d.id 
                       WHERE d.school_id = ? AND t.is_archived = 1 AND DATE(t.archived_at) = DATE(?)",
            'Players' => "SELECT COUNT(*) FROM players p 
                         INNER JOIN teams t ON p.team_id = t.team_id 
                         INNER JOIN grade_section_course g ON t.grade_section_course_id = g.id 
                         INNER JOIN departments d ON g.department_id = d.id 
                         WHERE d.school_id = ? AND p.is_archived = 1 AND DATE(p.archived_at) = DATE(?)"
        ];

        $all_queries = array_merge($direct_tables, $dept_tables, $game_tables, $match_tables, $player_tables);

        foreach ($all_queries as $label => $query) {
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("is", $this->school_id, $timestamp);
            $stmt->execute();
            $result = $stmt->get_result();
            $count = $result->fetch_array()[0];
            $report[$label] = $count;
            $stmt->close();
        }

        return $report;
    }

    private function generateExcelReport($report)
    {
        // Create new Spreadsheet object
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $sheet->setCellValue('A1', 'Category');
        $sheet->setCellValue('B1', 'Records Archived');
        $sheet->getStyle('A1:B1')->getFont()->setBold(true);

        // Add data
        $row = 2;
        foreach ($report as $category => $count) {
            $sheet->setCellValue('A' . $row, $category);
            $sheet->setCellValue('B' . $row, $count);
            $row++;
        }

        // Auto-size columns
        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);

        // Create Excel file
        $filename = 'archive_report_' . $this->school_id . '_' . date('Y-m-d_His') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $filepath = 'reports/' . $filename;

        // Create reports directory if it doesn't exist
        if (!file_exists('reports')) {
            mkdir('reports', 0777, true);
        }

        $writer->save($filepath);
        return $filepath;
    }

    public function __destruct()
    {
        $this->conn->close();
    }
}

// Handle incoming request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    // Verify user is logged in and has appropriate permissions
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'School Admin') {
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized access'
        ]);
        exit;
    }

    // Get school_id from session instead of POST for security
    $school_id = $_SESSION['school_id'] ?? null;

    if (!$school_id) {
        echo json_encode([
            'success' => false,
            'message' => 'School ID not found in session'
        ]);
        exit;
    }

    try {
        $archiver = new SchoolArchiver($school_id);
        $result = $archiver->archiveSchoolData();
        echo json_encode($result);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ]);
    }
}
