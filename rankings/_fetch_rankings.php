<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

header('Content-Type: application/json');

// Use URL parameters as a fallback
$school_id = $_GET['school_id'] ?? $_SESSION['school_id'];
$department_id = $_POST['department_id'] ?? $_GET['department_id'];
$grade_level = $_POST['grade_level'] ?? $_GET['grade_level'] ?? null;
$game_id = $_GET['game_id'] ?? $_POST['game_id'] ?? null;

// Validate the department ID
if (!$department_id) {
    echo json_encode([
        'error' => 'Please select a department to view rankings.'
    ]);
    exit;
}

// Fetch the department name
$query = "SELECT department_name FROM departments WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $department_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $department = $result->fetch_assoc();
    $department_name = $department['department_name'];
    $rankings = [];

    if ($game_id) {
        // Rankings by Game (for all departments)
        $query = "
            SELECT 
                t.team_name, 
                t.wins, 
                t.losses,
                (t.wins + t.losses) as total_matches
            FROM teams t
            JOIN grade_section_course gsc ON t.grade_section_course_id = gsc.id
            JOIN departments d ON gsc.department_id = d.id
            WHERE d.school_id = ? AND gsc.department_id = ? 
                " . ($grade_level && $department_name !== 'College' ? "AND gsc.grade_level = ?" : "") . "
                AND t.game_id = ?
            ORDER BY t.wins DESC, t.losses ASC";

        if ($grade_level && $department_name !== 'College') {
            $stmt = $conn->prepare($query);
            $stmt->bind_param('iisi', $school_id, $department_id, $grade_level, $game_id);
        } else {
            $stmt = $conn->prepare($query);
            $stmt->bind_param('iii', $school_id, $department_id, $game_id);
        }
    } else {
        // Overall Rankings (using Points)
        if ($department_name == 'College') {
            $query = "
                SELECT 
                    gsc.course_name as team_name, 
                    gsc.Points as points,
                    0 as wins,
                    0 as losses,
                    0 as total_matches
                FROM grade_section_course gsc
                JOIN departments d ON gsc.department_id = d.id
                WHERE d.school_id = ? AND gsc.department_id = ?
                ORDER BY gsc.Points DESC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ii', $school_id, $department_id);
        } else {
            $query = "
                SELECT 
                    CONCAT(gsc.grade_level, ' - ', COALESCE(gsc.strand, ''), ' ', gsc.section_name) as team_name,
                    gsc.Points as points,
                    0 as wins,
                    0 as losses,
                    0 as total_matches
                FROM grade_section_course gsc
                JOIN departments d ON gsc.department_id = d.id
                WHERE d.school_id = ? AND gsc.department_id = ?
                " . ($grade_level ? "AND gsc.grade_level = ?" : "") . "
                GROUP BY gsc.grade_level, gsc.strand, gsc.section_name
                ORDER BY gsc.Points DESC";

            if ($grade_level) {
                $stmt = $conn->prepare($query);
                $stmt->bind_param('iis', $school_id, $department_id, $grade_level);
            } else {
                $stmt = $conn->prepare($query);
                $stmt->bind_param('ii', $school_id, $department_id);
            }
        }
    }

    // Execute and fetch results
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $rankings[] = [
            'team_name' => htmlspecialchars($row['team_name']),
            'wins' => isset($row['points']) ? $row['points'] : (int)$row['wins'],
            'losses' => (int)$row['losses'],
            'total_matches' => (int)$row['total_matches'],
            'is_points' => isset($row['points'])
        ];
    }

    echo json_encode($rankings);
} else {
    echo json_encode([
        'error' => 'Department not found.'
    ]);
}

$conn->close();
