<?php
session_start();
include_once '../connection/conn.php';
$conn = con();
include "../user_logs/logger.php"; // Include the logger at the top

if (!isset($_SESSION['user_id'])) {
    header('Location: /t1/login.php'); // Redirect to login if not logged in
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $announcement_id = intval($_POST['announcement_id']);
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);

    // Set department_id based on user role
    if ($_SESSION['role'] === 'School Admin') {
        $department_id = isset($_POST['department_id']) ? intval($_POST['department_id']) : null;
    } else {
        // For Department Admin, use their department ID from session
        $department_id = $_SESSION['department_id'];
    }

    // Step 1: Fetch the old title, message, department, and image before the update
    $sql_select = "SELECT title, message, department_id, image FROM announcement WHERE id = ?";
    $stmt_select = $conn->prepare($sql_select);

    if ($stmt_select) {
        $stmt_select->bind_param("i", $announcement_id);
        $stmt_select->execute();
        $stmt_select->bind_result($old_title, $old_message, $old_department_id, $old_image); // Bind the result
        $stmt_select->fetch(); // Fetch the result (the old values)
        $stmt_select->close();
    } else {
        $_SESSION['error_message'] = "Failed to fetch announcement details: " . $conn->error;
        header('Location: adminannouncement.php');
        exit();
    }

    // Fetch the old department name
    $sql_department = "SELECT department_name FROM departments WHERE id = ?";
    $stmt_department = $conn->prepare($sql_department);
    if ($stmt_department) {
        $stmt_department->bind_param("i", $old_department_id);
        $stmt_department->execute();
        $stmt_department->bind_result($old_department_name);
        $stmt_department->fetch();
        $stmt_department->close();
    }

    // Fetch the new department name (if changed)
    $new_department_name = null;
    if ($department_id !== $old_department_id) {
        $stmt_department = $conn->prepare($sql_department);
        if ($stmt_department) {
            $stmt_department->bind_param("i", $department_id);
            $stmt_department->execute();
            $stmt_department->bind_result($new_department_name);
            $stmt_department->fetch();
            $stmt_department->close();
        }
    }

    // Initialize the log description
    $log_description = '';

    // Check if the title was edited
    if ($title !== $old_title) {
        $log_description .= 'Edited title from "' . $old_title . '" to "' . $title . '". ';
    }

    // Check if the content was edited
    if ($message !== $old_message) {
        $log_description .= 'Edited content. ';
    }

    // Check if the department was changed
    if ($department_id !== $old_department_id) {
        $log_description .= 'Changed department from "' . $old_department_name . '" to "' . $new_department_name . '". ';
    }

    // Check if a new image was uploaded
    if (!empty($_FILES['image']['name'])) {
        $image = $_FILES['image']['name'];
        $target_dir = "../uploads/announcements/";
        $target_file = $target_dir . basename($image);
        $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

        // Check if image file is an actual image or fake image
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if ($check === false) {
            $_SESSION['error_message'] = "File is not an image.";
            header('Location: adminannouncement.php');
            exit();
        }

        // Check file size (5MB max)
        if ($_FILES["image"]["size"] > 5000000) {
            $_SESSION['error_message'] = "Sorry, your file is too large. Max size is 5MB.";
            header('Location: adminannouncement.php');
            exit();
        }

        // Allow certain file formats
        if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
            $_SESSION['error_message'] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            header('Location: adminannouncement.php');
            exit();
        }

        // Delete old image if exists
        if (!empty($old_image) && file_exists($old_image)) {
            unlink($old_image);
        }

        // Upload new image
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            // Update announcement with new image
            $update_sql = "UPDATE announcement SET title=?, message=?, department_id=?, image=? WHERE id=?";
            $stmt_update = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($stmt_update, "ssssi", $title, $message, $department_id, $target_file, $announcement_id);

            $log_description .= 'Changed image. ';
        } else {
            $_SESSION['error_message'] = "Sorry, there was an error uploading your file.";
            header('Location: adminannouncement.php');
            exit();
        }
    } else {
        // Update announcement without changing the image
        $update_sql = "UPDATE announcement SET title=?, message=?, department_id=? WHERE id=?";
        $stmt_update = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt_update, "sssi", $title, $message, $department_id, $announcement_id);
    }

    // Execute the update and log the action
    if (mysqli_stmt_execute($stmt_update)) {
        $_SESSION['success_message'] = "Announcement updated successfully!";

        // Log the action
        logUserAction(
            $conn,
            $_SESSION['user_id'],                            // ID of the user performing the action
            'announcements',                                 // Table name
            'UPDATE',                                        // Operation type
            $announcement_id,                                // Record ID (ID of the updated announcement)
            $log_description,                                // Description (what was changed)
            json_encode(['title' => $old_title, 'message' => $old_message, 'department_id' => $old_department_id, 'image' => $old_image]), // Previous data
            json_encode(['title' => $title, 'message' => $message, 'department_id' => $department_id, 'image' => $target_file]) // New data
        );
    } else {
        $_SESSION['error_message'] = "Failed to update announcement: " . mysqli_error($conn);
    }

    header('Location: adminannouncement.php');
    exit();
}
