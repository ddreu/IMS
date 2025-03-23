<?php
session_start();
require_once '../connection/conn.php';
$conn = con();

// Set the content type to JSON
header('Content-Type: application/json');

// Check if the user is logged in
if (!isset($_SESSION['user_id']) || !isset($_SESSION['school_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

// Retrieve the user's school ID from the session
$school_id = $_SESSION['school_id'];

// Get filter parameters
$department_id = isset($_GET['department_id']) ? $_GET['department_id'] : null;
$game_id = isset($_GET['game_id']) ? $_GET['game_id'] : null;
$grade_level = isset($_GET['grade_level']) ? $_GET['grade_level'] : null;

try {
    // Build the query with school_id filtering
    $query = "SELECT 
        b.bracket_id,
        b.game_id,
        b.department_id,
        b.grade_level,
        b.total_teams,
        b.rounds,
        b.status,
        b.bracket_type,
        b.created_at,
        b.is_archived,
        d.department_name,
        g.game_name
    FROM brackets b
    JOIN departments d ON b.department_id = d.id
    JOIN games g ON b.game_id = g.game_id
    JOIN schools s ON d.school_id = s.school_id
    WHERE s.school_id = ?
    AND g.is_archived = 0 AND d.is_archived = 0";

    $params = [$school_id];
    $types = "i";

    // Add filters if provided
    if ($department_id) {
        $query .= " AND b.department_id = ?";
        $params[] = $department_id;
        $types .= "i";
    }

    if ($game_id) {
        $query .= " AND b.game_id = ?";
        $params[] = $game_id;
        $types .= "i";
    }

    if ($grade_level) {
        $query .= " AND b.grade_level = ?";
        $params[] = $grade_level;
        $types .= "s";
    }

    $query .= " ORDER BY b.created_at DESC";

    // Prepare and execute the query
    $stmt = $conn->prepare($query);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch all brackets
    $brackets = [];
    while ($row = $result->fetch_assoc()) {
        $brackets[] = [
            'is_archived' => $row['is_archived'], // Ensure it's integer type
            'bracket_id' => $row['bracket_id'],
            'game_name' => htmlspecialchars($row['game_name']),
            'department_name' => htmlspecialchars($row['department_name']),
            'grade_level' => $row['grade_level'],
            'total_teams' => $row['total_teams'],
            'status' => ucfirst($row['status']),
            'bracket_type' => htmlspecialchars($row['bracket_type']),
            'created_at' => $row['created_at']
        ];
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $brackets
    ]);
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Error fetching brackets: ' . $e->getMessage()
    ]);
}

$conn->close();
