<?php
include_once '../connection/conn.php';
include '../user_logs/logger.php';
session_start();
$conn = con();
header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $bracketId = isset($_POST['bracket_id']) ? intval($_POST['bracket_id']) : null;

    if (!$bracketId) {
        throw new Exception('Bracket ID is required');
    }

    // Start transaction
    $conn->begin_transaction();

    // Fetch the game_id, department_id, and grade_level for the bracket
    $fetchBracketQuery = "SELECT game_id, department_id, grade_level FROM brackets WHERE bracket_id = ?";
    $stmt = $conn->prepare($fetchBracketQuery);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $bracketId);
    if (!$stmt->execute()) {
        throw new Exception("Failed to fetch bracket data: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        throw new Exception("No bracket found with ID: $bracketId");
    }

    $bracket = $result->fetch_assoc();
    $gameId = $bracket['game_id'];
    $departmentId = $bracket['department_id'];
    $gradeLevel = $bracket['grade_level'];

    // Delete matches first (due to foreign key constraint)
    $deleteMatchesQuery = "DELETE FROM matches WHERE bracket_id = ?";
    $stmt = $conn->prepare($deleteMatchesQuery);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $bracketId);
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete matches: " . $stmt->error);
    }

    // Then delete the bracket
    $deleteBracketQuery = "DELETE FROM brackets WHERE bracket_id = ?";
    $stmt = $conn->prepare($deleteBracketQuery);
    if (!$stmt) {
        throw new Exception("Prepare failed: " . $conn->error);
    }
    $stmt->bind_param("i", $bracketId);
    if (!$stmt->execute()) {
        throw new Exception("Failed to delete bracket: " . $stmt->error);
    }

    // Commit transaction
    $conn->commit();

    // Log the deletion with game and department names
    $gameName = getGameName($gameId, $conn);  // Assuming getGameName function fetches the game name
    $departmentName = getDepartmentName($departmentId, $conn); // Assuming getDepartmentName fetches department name

    $description = "Deleted a bracket for Game: $gameName, Department: $departmentName" .
        ($gradeLevel ? ", Grade Level: $gradeLevel" : "");

    logUserAction($conn, $_SESSION['user_id'], 'Brackets', 'DELETE', $bracketId, $description);

    echo json_encode([
        'success' => true,
        'message' => 'Bracket deleted successfully'
    ]);
} catch (Exception $e) {
    // Roll back the transaction if something failed
    if (isset($conn)) {
        $conn->rollback();
    }

    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

if (isset($conn)) {
    $conn->close();
}

function getGameName($gameId, $conn)
{
    $query = "SELECT game_name FROM games WHERE game_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $gameId);
    $stmt->execute();
    $result = $stmt->get_result();
    $game = $result->fetch_assoc();
    return $game ? $game['game_name'] : 'Unknown Game';
}

function getDepartmentName($departmentId, $conn)
{
    $query = "SELECT department_name FROM departments WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $departmentId);
    $stmt->execute();
    $result = $stmt->get_result();
    $department = $result->fetch_assoc();
    return $department ? $department['department_name'] : 'Unknown Department';
}
