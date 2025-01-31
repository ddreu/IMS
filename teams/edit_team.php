<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Check database connection
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $team_id = $_POST['team_id'];
    $team_name = $_POST['team_name'];

    $sql = "UPDATE teams SET team_name=? WHERE team_id=?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("si", $team_name, $team_id);

    if ($stmt->execute()) {
        echo json_encode(["success" => true, "message" => "Team name updated successfully!"]);
    } else {
        echo json_encode(["success" => false, "message" => "Failed to update team name."]);
    }

    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["success" => false, "message" => "Invalid request."]);
}
?>
