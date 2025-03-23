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
            // Verify school_id exists
            $check_school = "SELECT school_id FROM schools WHERE school_id = ?";
            $stmt = $this->conn->prepare($check_school);
            $stmt->bind_param("i", $this->school_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                throw new Exception("Invalid school ID");
            }

            // Start transaction
            $this->conn->begin_transaction();

            $current_timestamp = date('Y-m-d H:i:s');
            $archived_counts = [];

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

            // Generate archive report
            $report = $this->generateDetailedReport();

            // Generate Excel file
            $excel_file = $this->generateExcelReport($report);

            // Get the full URL for the Excel file
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
            $host = $_SERVER['HTTP_HOST'];

            // Add the correct path to the file
            $base_path = '/ims/archive/';
            $excel_url = $protocol . $host . $base_path . ltrim($excel_file, '/');

            // Commit transaction
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

    private function archiveDirectSchoolIdTables($timestamp, &$archived_counts)
    {
        $tables = ['announcement', 'games', 'game_scoring_rules', 'pointing_system'];
        foreach ($tables as $table) {
            $sql = "UPDATE $table SET is_archived = 1, archived_at = ? WHERE school_id = ?";
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

    private function generateDetailedReport()
    {
        $report = [];

        // Direct school_id tables
        $direct_tables = [
            'Announcements' => "SELECT COUNT(*) FROM announcement WHERE school_id = ? AND is_archived = 1",
            'Games' => "SELECT COUNT(*) FROM games WHERE school_id = ? AND is_archived = 1",
            'Game Rules' => "SELECT COUNT(*) FROM game_scoring_rules WHERE school_id = ? AND is_archived = 1",
            'Pointing System' => "SELECT COUNT(*) FROM pointing_system WHERE school_id = ? AND is_archived = 1"
        ];

        // Department-linked tables
        $dept_tables = [
            'Grade Sections/Courses' => "SELECT COUNT(*) FROM grade_section_course g 
                                       INNER JOIN departments d ON g.department_id = d.id 
                                       WHERE d.school_id = ? AND g.is_archived = 1",
            'Brackets' => "SELECT COUNT(*) FROM brackets b 
                          INNER JOIN departments d ON b.department_id = d.id 
                          WHERE d.school_id = ? AND b.is_archived = 1"
        ];

        // Game-linked tables
        $game_tables = [
            'Game Stats Config' => "SELECT COUNT(*) FROM game_stats_config gc 
                                  INNER JOIN games g ON gc.game_id = g.game_id 
                                  WHERE g.school_id = ? AND gc.is_archived = 1"
        ];

        // Match-linked tables
        $match_tables = [
            'Matches' => "SELECT COUNT(*) FROM matches m 
                         INNER JOIN brackets b ON m.bracket_id = b.bracket_id 
                         INNER JOIN departments d ON b.department_id = d.id 
                         WHERE d.school_id = ? AND m.is_archived = 1",
            'Match Periods' => "SELECT COUNT(*) FROM match_periods_info mp 
                              INNER JOIN matches m ON mp.match_id = m.match_id 
                              INNER JOIN brackets b ON m.bracket_id = b.bracket_id 
                              INNER JOIN departments d ON b.department_id = d.id 
                              WHERE d.school_id = ? AND mp.is_archived = 1"
        ];

        // Team and Player tables
        $player_tables = [
            'Teams' => "SELECT COUNT(*) FROM teams t 
                       INNER JOIN grade_section_course g ON t.grade_section_course_id = g.id 
                       INNER JOIN departments d ON g.department_id = d.id 
                       WHERE d.school_id = ? AND t.is_archived = 1",
            'Players' => "SELECT COUNT(*) FROM players p 
                         INNER JOIN teams t ON p.team_id = t.team_id 
                         INNER JOIN grade_section_course g ON t.grade_section_course_id = g.id 
                         INNER JOIN departments d ON g.department_id = d.id 
                         WHERE d.school_id = ? AND p.is_archived = 1"
        ];

        $all_queries = array_merge($direct_tables, $dept_tables, $game_tables, $match_tables, $player_tables);

        foreach ($all_queries as $label => $query) {
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $this->school_id);
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
