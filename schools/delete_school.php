<?php
session_start();
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
    if (isset($_POST['school_id'])) {
        $school_id = intval($_POST['school_id']);

        try {
            $conn->begin_transaction();

            // Delete associated users first
            $delete_users = "DELETE FROM users WHERE school_id = ?";
            $stmt = $conn->prepare($delete_users);
            if (!$stmt) {
                throw new Exception("Failed to prepare user deletion statement");
            }
            $stmt->bind_param("i", $school_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to delete associated users");
            }

            // Delete associated departments
            $delete_departments = "DELETE FROM departments WHERE school_id = ?";
            $stmt = $conn->prepare($delete_departments);
            if (!$stmt) {
                throw new Exception("Failed to prepare department deletion statement");
            }
            $stmt->bind_param("i", $school_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to delete associated departments");
            }

            // Delete the logo if it exists in the uploads folder
            $sql = "SELECT logo FROM schools WHERE school_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $school_id);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $logo = $row['logo'];
                if ($logo && file_exists('../uploads/logos/' . $logo)) {
                    unlink('../uploads/logos/' . $logo); // Delete the logo from the server
                }
            }

            // Finally, delete the school
            $delete_school = "DELETE FROM schools WHERE school_id = ?";
            $stmt = $conn->prepare($delete_school);
            if (!$stmt) {
                throw new Exception("Failed to prepare school deletion statement");
            }
            $stmt->bind_param("i", $school_id);
            if (!$stmt->execute()) {
                throw new Exception("Failed to delete school");
            }

            $conn->commit();
            echo json_encode([
                "status" => "success",
                "message" => "School and all associated data deleted successfully."
            ]);

        } catch (Exception $e) {
            $conn->rollback();
            echo json_encode([
                "status" => "error",
                "message" => "Error: " . $e->getMessage()
            ]);
        }
    } else {
        echo json_encode([
            "status" => "error",
            "message" => "School ID not provided."
        ]);
    }
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Invalid request method."
    ]);
}
?>
