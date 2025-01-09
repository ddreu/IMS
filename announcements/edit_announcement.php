<?php
session_start();
include_once '../connection/conn.php'; 
$conn = con();

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
    
    // Check if a new image was uploaded
    if (!empty($_FILES['image']['name'])) {
        $image = $_FILES['image']['name'];
        $target_dir = "../uploads/";
        $target_file = $target_dir . basename($image);
        $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
        
        // Check if image file is a actual image or fake image
        $check = getimagesize($_FILES["image"]["tmp_name"]);
        if($check === false) {
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
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif" ) {
            $_SESSION['error_message'] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            header('Location: adminannouncement.php');
            exit();
        }
        
        // Delete old image if exists
        $old_image_query = "SELECT image FROM announcement WHERE id = ?";
        $stmt_old = mysqli_prepare($conn, $old_image_query);
        mysqli_stmt_bind_param($stmt_old, "i", $announcement_id);
        mysqli_stmt_execute($stmt_old);
        $old_result = mysqli_stmt_get_result($stmt_old);
        if($old_row = mysqli_fetch_assoc($old_result)) {
            if(!empty($old_row['image']) && file_exists($old_row['image'])) {
                unlink($old_row['image']);
            }
        }
        
        // Upload new image
        if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
            // Update announcement with new image
            $update_sql = "UPDATE announcement SET title=?, message=?, department_id=?, image=? WHERE id=?";
            $stmt_update = mysqli_prepare($conn, $update_sql);
            mysqli_stmt_bind_param($stmt_update, "ssssi", $title, $message, $department_id, $target_file, $announcement_id);
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
    
    if (mysqli_stmt_execute($stmt_update)) {
        $_SESSION['success_message'] = "Announcement updated successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to update announcement: " . mysqli_error($conn);
    }
    
    header('Location: adminannouncement.php');
    exit();
}
?>
