<?php
session_start();
include_once '../connection/conn.php';
$conn = con();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_SESSION['user_id']; // Assuming the user ID is stored in session
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_new_password = $_POST['confirm_new_password'];

    // Fetch the user's current password from the database
    $query = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $query->bind_param("i", $user_id);
    $query->execute();
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        // Verify the current password
        if (password_verify($current_password, $row['password'])) {
            // Check if new passwords match
            if ($new_password === $confirm_new_password) {
                // Hash the new password
                $hashed_new_password = password_hash($new_password, PASSWORD_DEFAULT);

                // Update the password in the database
                $update_query = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_query->bind_param("si", $hashed_new_password, $user_id);
                if ($update_query->execute()) {
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to update password.']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'New passwords do not match.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'User not found.']);
    }

    $query->close();
    $conn->close();
}
