<?php
require '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

require_once '../../connection/conn.php';
$conn = con();

// Get filter values
$department = $_POST['department'] ?? '';
$gradeLevel = $_POST['gradeLevel'] ?? '';
$game = $_POST['game'] ?? '';

if (empty($department) && empty($gradeLevel) && empty($game)) {
    http_response_code(400);
    echo json_encode(['error' => 'Please select at least one filter.']);
    exit;
}

// Convert IDs to names
$departmentName = '';
$gameName = '';

// Get department name
if (!empty($department)) {
    $stmt = $conn->prepare("SELECT department_name FROM departments WHERE id = ?");
    $stmt->bind_param('i', $department);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $departmentName = $row['department_name'];
    }
}

// Get game name
if (!empty($game)) {
    $stmt = $conn->prepare("SELECT game_name FROM games WHERE game_id = ?");
    $stmt->bind_param('i', $game);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $gameName = $row['game_name'];
    }
}

// Base query
$query = "SELECT 
            s.schedule_date AS date, 
            s.schedule_time AS time, 
            g.game_name,
            CONCAT(t1.team_name, ' vs ', t2.team_name) AS `match_details`,
            s.venue AS location
          FROM schedules s
          JOIN matches m ON s.match_id = m.match_id
          JOIN brackets b ON m.bracket_id = b.bracket_id
          JOIN games g ON b.game_id = g.game_id
          JOIN teams t1 ON m.teamA_id = t1.team_id
          JOIN teams t2 ON m.teamB_id = t2.team_id
          WHERE 1";

$params = [];
$types = '';

if (!empty($department)) {
    $query .= " AND b.department_id = ?";
    $types .= 'i';
    $params[] = $department;
}
if (!empty($gradeLevel)) {
    $query .= " AND b.grade_level = ?";
    $types .= 's';
    $params[] = $gradeLevel;
}
if (!empty($game)) {
    $query .= " AND g.game_id = ?";
    $types .= 'i';
    $params[] = $game;
}

$stmt = $conn->prepare($query);

if ($types) {
    $stmt->bind_param($types, ...$params);
}

$stmt->execute();
$result = $stmt->get_result();

// ✅ Check if result set is empty
if ($result->num_rows === 0) {
    die('No schedules found for the selected criteria.');
}

// Create new spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Title based on filters
$titleParts = [];

if (!empty($departmentName)) {
    $titleParts[] = $departmentName;
}
if (!empty($gradeLevel)) {
    $titleParts[] = "Grade $gradeLevel";
}
if (!empty($gameName)) {
    $titleParts[] = $gameName;
}

$title = !empty($titleParts) ? implode(' - ', $titleParts) . ' Schedules' : 'Game Schedules';

$sheet->setCellValue('A1', $title);
$sheet->mergeCells('A1:E1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);

// Set headers
$headers = ['Date', 'Time', 'Game', 'Match', 'Location'];
$sheet->fromArray($headers, NULL, 'A2');

// Style headers
$headerStyle = [
    'font' => ['bold' => true],
    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
];
$sheet->getStyle('A2:E2')->applyFromArray($headerStyle);



$rowIndex = 3;
while ($row = $result->fetch_assoc()) {
    $sheet->setCellValue('A' . $rowIndex, $row['date']);
    $sheet->setCellValue('B' . $rowIndex, $row['time']);
    $sheet->setCellValue('C' . $rowIndex, $row['game_name']);
    $sheet->setCellValue('D' . $rowIndex, $row['match_details']);
    $sheet->setCellValue('E' . $rowIndex, $row['location']);
    $rowIndex++;
}

// ✅ Check if data was added
if ($rowIndex === 3) {
    die('No data to export.');
}


//additionals
// ✅ Set print area
$sheet->getPageSetup()->setPrintArea('A1:E' . ($rowIndex - 1));

// ✅ Fit to page
$sheet->getPageSetup()->setFitToWidth(1);
$sheet->getPageSetup()->setFitToHeight(0);

// ✅ Set orientation to landscape
// $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);

// ✅ Add gridlines
$sheet->setShowGridlines(true);

// ✅ Center on page
$sheet->getPageSetup()->setHorizontalCentered(true);
$sheet->getPageSetup()->setVerticalCentered(true);


// Auto size columns
foreach (range('A', 'E') as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// Style content
$contentStyle = [
    'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN]],
];
$sheet->getStyle('A3:E' . ($rowIndex - 1))->applyFromArray($contentStyle);

$filenameParts = [];

if (!empty($departmentName)) {
    $filenameParts[] = $departmentName;
}
if (!empty($gradeLevel)) {
    $filenameParts[] = "$gradeLevel";
}
if (!empty($gameName)) {
    $filenameParts[] = $gameName;
}

// Build the filename and sanitize it
$filename = !empty($filenameParts)
    ? implode(' - ', $filenameParts) . ' Schedules.xlsx'
    : 'Game Schedules.xlsx';

$filename = preg_replace('/[\/:*?"<>|]/', '', $filename);
if (ob_get_length()) {
    ob_end_clean();
}

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');
header('Content-Transfer-Encoding: binary');
header('Pragma: public');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
