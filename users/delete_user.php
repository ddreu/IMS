<?php
session_start(); // Start the session to use session variables
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include your database connection file and logger
include_once '../connection/conn.php';
include "../user_logs/logger.php";
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

        // Fetch user details for logging before deletion
        $stmt_fetch = $conn->prepare("
            SELECT u.firstname, u.lastname, u.role, u.department, u.game_id, 
                   d.department_name, g.game_name 
            FROM users u
            LEFT JOIN departments d ON u.department = d.id
            LEFT JOIN games g ON u.game_id = g.game_id
            WHERE u.id = ?
        ");
        if ($stmt_fetch) {
            $stmt_fetch->bind_param("i", $user_id);
            $stmt_fetch->execute();
            $result = $stmt_fetch->get_result();
            $user_details = $result->fetch_assoc();
            $stmt_fetch->close();

            if ($user_details) {
                $fullName = $user_details['firstname'] . ' ' . $user_details['lastname'];
                $role = $user_details['role'];
                $departmentName = $user_details['department_name'] ? $user_details['department_name'] : 'N/A';
                $gameName = $user_details['game_name'] ? $user_details['game_name'] : 'N/A';

                // Proceed with deletion
                $stmt_delete = $conn->prepare("DELETE FROM users WHERE id = ?");
                if ($stmt_delete) {
                    $stmt_delete->bind_param("i", $user_id);
                    if ($stmt_delete->execute()) {
                        // Log the action using logUserAction
                        $description = "Deleted \"$fullName\" ($role)";
                        if ($departmentName !== 'N/A') {
                            $description .= " from the \"$departmentName\" department";
                        }
                        if ($gameName !== 'N/A' && $role === 'Committee') {
                            $description .= " for the \"$gameName\" game";
                        }
                        $description .= ".";

                        logUserAction(
                            $conn,
                            $_SESSION['user_id'], // Logged-in user performing the action
                            'Users',              // Table name
                            'DELETE',             // Operation type
                            $user_id,             // Record ID of deleted user
                            $description,         // Description of the operation
                            json_encode($user_details), // Previous data (before deletion)
                            null                  // No new data for delete
                        );

                        echo json_encode([
                            "status" => "success",
                            "message" => "User deleted successfully."
                        ]);
                    } else {
                        echo json_encode([
                            "status" => "error",
                            "message" => "Error deleting user: " . $stmt_delete->error
                        ]);
                    }
                    $stmt_delete->close();
                } else {
                    echo json_encode([
                        "status" => "error",
                        "message" => "Error preparing delete statement: " . $conn->error
                    ]);
                }
            } else {
                echo json_encode([
                    "status" => "error",
                    "message" => "User not found."
                ]);
            }
        } else {
            echo json_encode([
                "status" => "error",
                "message" => "Error preparing fetch statement: " . $conn->error
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
