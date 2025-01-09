<?php
// delete_user.php
session_start(); // Start the session to use session variables
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include your database connection file
include_once '../connection/conn.php'; 
$conn = con();

header('Content-Type: application/json');

if ($conn->connect_error) {
    echo json_encode([
        "status" => "error",
        "message" => "Connection failed: " . $conn->connect_error
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['user_id'])) {
        $user_id = intval($_POST['user_id']);
        
        $stmt = $conn->prepare("DELETE FROM Users WHERE id = ?"); // Ensure the table name is correct
        if ($stmt) {
            $stmt->bind_param("i", $user_id);
            if ($stmt->execute()) {
                echo json_encode([
                    "status" => "success",
                    "message" => "User deleted successfully."
                ]);
            } else {
                echo json_encode([
                    "status" => "error",
                    "message" => "Error deleting user: " . $stmt->error
                ]);
            }
            $stmt->close();
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Error preparing statement: " . $conn->error
            ]);
        }
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "User ID not provided."
        ]);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid request method."
    ]);
}
?>
