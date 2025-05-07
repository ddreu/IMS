<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

header('Content-Type: application/json');

$school_id = $_GET['school_id'] ?? $_SESSION['school_id'];
$department_id = $_POST['department_id'] ?? $_GET['department_id'];
$grade_level = $_POST['grade_level'] ?? $_GET['grade_level'] ?? null;
$game_id = $_GET['game_id'] ?? $_POST['game_id'] ?? null;

if (!$department_id) {
    echo json_encode(['error' => 'Please select a department to view rankings.']);
    exit;
}

// Fetch department name
$query = "SELECT department_name FROM departments WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param('i', $department_id);
$stmt->execute();
$departmentResult = $stmt->get_result();

if ($departmentResult->num_rows === 0) {
    echo json_encode(['error' => 'Department not found.']);
    exit;
}

$department = $departmentResult->fetch_assoc();
$department_name = $department['department_name'];
$rankings = [];

if ($game_id) {
    // --- Rankings by specific Game ---
    $query = "
        SELECT 
            t.team_name,
            t.wins, 
            t.losses,
            (t.wins + t.losses) as total_matches
        FROM teams t
        JOIN grade_section_course gsc ON t.grade_section_course_id = gsc.id
        JOIN departments d ON gsc.department_id = d.id
        JOIN games g ON t.game_id = g.game_id
        WHERE d.school_id = ? 
            AND gsc.department_id = ? 
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

    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $rankings[] = [
            'team_name' => htmlspecialchars($row['team_name']),
            'wins' => (int)$row['wins'],
            'losses' => (int)$row['losses'],
            'total_matches' => (int)$row['total_matches'],
            'is_points' => false
        ];
    }
} else {
    // --- Overall Rankings (Points + Medals) ---
    if ($department_name == 'College') {
        $query = "
            SELECT 
                gsc.id,
                gsc.course_name as team_name,
                gsc.Points as points
            FROM grade_section_course gsc
            JOIN departments d ON gsc.department_id = d.id
            WHERE d.school_id = ? AND gsc.department_id = ?
            ORDER BY gsc.Points DESC";
        $stmt = $conn->prepare($query);
        $stmt->bind_param('ii', $school_id, $department_id);
    } else {
        $query = "
            SELECT 
                gsc.id,
                CONCAT(gsc.grade_level, ' - ', COALESCE(gsc.strand, ''), ' ', gsc.section_name) as team_name,
                gsc.Points as points
            FROM grade_section_course gsc
            JOIN departments d ON gsc.department_id = d.id
            WHERE d.school_id = ? AND gsc.department_id = ?
            " . ($grade_level ? "AND gsc.grade_level = ?" : "") . "
            GROUP BY gsc.grade_level, gsc.strand, gsc.section_name, gsc.id
            ORDER BY gsc.Points DESC";

        if ($grade_level) {
            $stmt = $conn->prepare($query);
            $stmt->bind_param('iis', $school_id, $department_id, $grade_level);
        } else {
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ii', $school_id, $department_id);
        }
    }

    $stmt->execute();
    $result = $stmt->get_result();

    $teams = [];
    while ($row = $result->fetch_assoc()) {
        $teams[$row['id']] = [
            'team_name' => htmlspecialchars($row['team_name']),
            'points' => (int)$row['points'],
            'gold' => 0,
            'silver' => 0,
            'bronze' => 0
        ];
    }

    // Fetch completed brackets
    $bracketQuery = "
        SELECT b.bracket_id, b.game_id, b.grade_level, b.bracket_type
        FROM brackets b
        JOIN games g ON b.game_id = g.game_id
        WHERE b.department_id = ? 
          AND b.status = 'Completed' 
          AND g.school_id = ?
          AND b.is_archived = 0";

    if ($grade_level && $department_name !== 'College') {
        $bracketQuery .= " AND b.grade_level = ?";
        $stmt = $conn->prepare($bracketQuery);
        $stmt->bind_param('iis', $department_id, $school_id, $grade_level);
    } else {
        $stmt = $conn->prepare($bracketQuery);
        $stmt->bind_param('ii', $department_id, $school_id);
    }

    $stmt->execute();
    $brackets = $stmt->get_result();

    // Process each completed bracket
    while ($bracket = $brackets->fetch_assoc()) {
        $bracketId = $bracket['bracket_id'];
        $gameId = $bracket['game_id'];
        $bracketType = $bracket['bracket_type'];

        if ($bracketType === 'round_robin') {
            // For round robin, use team_tournament_points
            $rankingQuery = "
                SELECT 
                    t.team_name,
                    t.grade_section_course_id,
                    ttp.total_points + COALESCE(ttp.bonus_points, 0) as total_tournament_points
                FROM team_tournament_points ttp
                JOIN teams t ON ttp.team_id = t.team_id
                JOIN grade_section_course gsc ON t.grade_section_course_id = gsc.id
                WHERE ttp.bracket_id = ? 
                AND ttp.is_archived = 0
                AND gsc.department_id = ?
                " . ($grade_level && $department_name !== 'College' ? "AND gsc.grade_level = ?" : "") . "
                ORDER BY total_tournament_points DESC
                LIMIT 3";

            if ($grade_level && $department_name !== 'College') {
                $stmt = $conn->prepare($rankingQuery);
                $stmt->bind_param('iis', $bracketId, $department_id, $grade_level);
            } else {
                $stmt = $conn->prepare($rankingQuery);
                $stmt->bind_param('ii', $bracketId, $department_id);
            }
        } else {
            // For single/elimination brackets, use wins/losses
            $rankingQuery = "
                SELECT 
                    t.team_name,
                    t.wins,
                    t.losses,
                    t.grade_section_course_id,
                    (t.wins + t.losses) as total_matches
                FROM teams t
                JOIN grade_section_course gsc ON t.grade_section_course_id = gsc.id
                WHERE t.game_id = ? 
                AND gsc.department_id = ?
                " . ($grade_level && $department_name !== 'College' ? "AND gsc.grade_level = ?" : "") . "
                ORDER BY t.wins DESC, t.losses ASC
                LIMIT 3";

            if ($grade_level && $department_name !== 'College') {
                $stmt = $conn->prepare($rankingQuery);
                $stmt->bind_param('iis', $gameId, $department_id, $grade_level);
            } else {
                $stmt = $conn->prepare($rankingQuery);
                $stmt->bind_param('ii', $gameId, $department_id);
            }
        }

        $stmt->execute();
        $gameRankings = $stmt->get_result();

        $rank = 1;
        while ($team = $gameRankings->fetch_assoc()) {
            $gscId = $team['grade_section_course_id'];

            // Only process if this GSC is in our teams array
            if (isset($teams[$gscId])) {
                switch ($rank) {
                    case 1:
                        $teams[$gscId]['gold']++;
                        break;
                    case 2:
                        $teams[$gscId]['silver']++;
                        break;
                    case 3:
                        $teams[$gscId]['bronze']++;
                        break;
                }
            }
            $rank++;
        }
    }

    // Convert teams to rankings array
    $rankings = [];
    foreach ($teams as $team) {
        $rankings[] = [
            'team_name' => $team['team_name'],
            'wins' => $team['points'],
            'losses' => 0,
            'total_matches' => 0,
            'gold' => $team['gold'],
            'silver' => $team['silver'],
            'bronze' => $team['bronze'],
            'is_points' => true
        ];
    }

    // Sort rankings by points and medals
    usort($rankings, function ($a, $b) {
        // First compare by points
        if ($a['wins'] !== $b['wins']) {
            return $b['wins'] - $a['wins'];
        }
        // Then by gold medals
        if ($a['gold'] !== $b['gold']) {
            return $b['gold'] - $a['gold'];
        }
        // Then by silver medals
        if ($a['silver'] !== $b['silver']) {
            return $b['silver'] - $a['silver'];
        }
        // Finally by bronze medals
        return $b['bronze'] - $a['bronze'];
    });
}

echo json_encode($rankings);
$conn->close();
