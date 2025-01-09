<?php
include_once '../connection/conn.php';
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
