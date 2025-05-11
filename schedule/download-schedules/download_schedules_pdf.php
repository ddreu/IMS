<?php
require_once '../../vendor/autoload.php';
require_once '../../connection/conn.php';

$conn = con();

use TCPDF;

$department = $_POST['department'] ?? '';
$gradeLevel = $_POST['gradeLevel'] ?? '';
$game = $_POST['game'] ?? '';

if (empty($department) && empty($gradeLevel) && empty($game)) {
    http_response_code(400);
    echo json_encode(['error' => 'Please select at least one filter.']);
    exit;
}

// Get department and game names
$departmentName = '';
$gameName = '';

if (!empty($department)) {
    $stmt = $conn->prepare("SELECT department_name FROM departments WHERE id = ?");
    $stmt->bind_param('i', $department);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $departmentName = $row['department_name'];
    }
}

if (!empty($game)) {
    $stmt = $conn->prepare("SELECT game_name FROM games WHERE game_id = ?");
    $stmt->bind_param('i', $game);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $gameName = $row['game_name'];
    }
}

// Build query
$query = "SELECT 
            s.schedule_date AS date, 
            s.schedule_time AS time, 
            g.game_name,
            CONCAT(t1.team_name, ' vs ', t2.team_name) AS match_details,
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

if ($result->num_rows === 0) {
    die('No schedules found for the selected criteria.');
}

// Setup TCPDF
$pdf = new TCPDF();
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Event Manager');
$pdf->SetTitle('Game Schedules');
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(TRUE, 10);
$pdf->AddPage();
$pdf->SetFont('helvetica', '', 10);

// Title
$titleParts = [];
if (!empty($departmentName)) $titleParts[] = $departmentName;
if (!empty($gradeLevel)) $titleParts[] = "Grade $gradeLevel";
if (!empty($gameName)) $titleParts[] = $gameName;

$title = !empty($titleParts) ? implode(' - ', $titleParts) . ' Schedules' : 'Game Schedules';
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, $title, 0, 1, 'C');
$pdf->Ln(5);

// Table headers
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(30, 7, 'Date', 1);
$pdf->Cell(25, 7, 'Time', 1);
$pdf->Cell(40, 7, 'Game', 1);
$pdf->Cell(60, 7, 'Match', 1);
$pdf->Cell(30, 7, 'Location', 1);
$pdf->Ln();

// Data rows
$pdf->SetFont('helvetica', '', 9);
while ($row = $result->fetch_assoc()) {
    $pdf->Cell(30, 6, $row['date'], 1);
    $pdf->Cell(25, 6, $row['time'], 1);
    $pdf->Cell(40, 6, $row['game_name'], 1);
    $pdf->Cell(60, 6, $row['match_details'], 1);
    $pdf->Cell(30, 6, $row['location'], 1);
    $pdf->Ln();
}

// Output file
$filenameParts = [];
if (!empty($departmentName)) $filenameParts[] = $departmentName;
if (!empty($gradeLevel)) $filenameParts[] = $gradeLevel;
if (!empty($gameName)) $filenameParts[] = $gameName;

$filename = !empty($filenameParts)
    ? implode(' - ', $filenameParts) . ' Schedules.pdf'
    : 'Game Schedules.pdf';

$filename = preg_replace('/[\/:*?"<>|]/', '', $filename);
if (ob_get_length()) {
    ob_end_clean();
}

header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$pdf->Output($filename, 'D');
exit;
