<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reportType = $_POST['report_type'] ?? '';
    $schoolId = $_POST['school_id'] ?? '';

    if (!$reportType || !$schoolId) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid request']);
        exit;
    }

    // Initialize spreadsheet
    $spreadsheet = new Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();

    switch ($reportType) {
        case 'match_results':
            generateMatchResultsReport($sheet, $schoolId);
            break;

        case 'team_performance':
            generateTeamPerformanceReport($sheet, $schoolId);
            break;

        case 'leaderboard':
            generateLeaderboardReport($sheet, $schoolId);
            break;

        case 'match_schedule':
            generateMatchScheduleReport($sheet, $schoolId);
            break;

        case 'event_summary':
            generateEventSummaryReport($sheet, $schoolId);
            break;

        case 'player_performance':
            generatePlayerPerformanceReport($sheet, $schoolId);
            break;

        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid report type']);
            exit;
    }

    // Output to browser as Excel file
    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="' . $reportType . '_report.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = new Xlsx($spreadsheet);
    $writer->save('php://output');
}

function generateMatchResultsReport($sheet, $schoolId)
{
    $sheet->setCellValue('A1', 'Match ID');
    $sheet->setCellValue('B1', 'Team 1');
    $sheet->setCellValue('C1', 'Team 2');
    $sheet->setCellValue('D1', 'Score');
    $sheet->setCellValue('E1', 'Winner');

    // Sample data — Replace with actual SQL query
    $data = [
        [1, 'Team A', 'Team B', '2 - 1', 'Team A'],
        [2, 'Team C', 'Team D', '3 - 0', 'Team C']
    ];

    $row = 2;
    foreach ($data as $match) {
        $sheet->setCellValue("A{$row}", $match[0]);
        $sheet->setCellValue("B{$row}", $match[1]);
        $sheet->setCellValue("C{$row}", $match[2]);
        $sheet->setCellValue("D{$row}", $match[3]);
        $sheet->setCellValue("E{$row}", $match[4]);
        $row++;
    }
}

function generateTeamPerformanceReport($sheet, $schoolId)
{
    $sheet->setCellValue('A1', 'Team Name');
    $sheet->setCellValue('B1', 'Matches Played');
    $sheet->setCellValue('C1', 'Wins');
    $sheet->setCellValue('D1', 'Losses');
    $sheet->setCellValue('E1', 'Points');

    // Sample data — Replace with actual SQL query
    $data = [
        ['Team A', 5, 4, 1, 12],
        ['Team B', 5, 3, 2, 9]
    ];

    $row = 2;
    foreach ($data as $team) {
        $sheet->setCellValue("A{$row}", $team[0]);
        $sheet->setCellValue("B{$row}", $team[1]);
        $sheet->setCellValue("C{$row}", $team[2]);
        $sheet->setCellValue("D{$row}", $team[3]);
        $sheet->setCellValue("E{$row}", $team[4]);
        $row++;
    }
}

// ➡️ Define other report functions here (we’ll fill them in next)
