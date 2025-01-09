<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Use session variables directly
$user_id = $_SESSION['user_id'];
$department_id = $_SESSION['department_id'];
$school_id = $_SESSION['school_id'];

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $message = trim($_POST['message']);
    $school_id = $_SESSION['school_id'];
    
    // Set department_id based on user role
    if ($_SESSION['role'] === 'School Admin') {
        $department_id = isset($_POST['department_id']) ? intval($_POST['department_id']) : null;
    } else {
        // For Department Admin, use their department ID from session
        $department_id = $_SESSION['department_id'];
    }

    // Check if image was uploaded
    if (!empty($_FILES['image']['name'])) {
        $image = $_FILES['image']['name'];
        $target_dir = "../uploads/";
        $target_file = $target_dir . basename($image);
        $imageFileType = strtolower(pathinfo($target_file,PATHINFO_EXTENSION));
        $uploadOk = 1;

        // Check if image file is a valid image
        if (getimagesize($_FILES['image']['tmp_name']) === false) {
            $error[] = "File is not an image.";
            $uploadOk = 0;
        }

        // Check file size (5MB limit)
        if ($_FILES['image']['size'] > 5000000) {
            $error[] = "Sorry, your file is too large.";
            $uploadOk = 0;
        }

        // Allow certain file formats
        if (!in_array($imageFileType, ['jpg', 'jpeg', 'png', 'gif'])) {
            $error[] = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            $uploadOk = 0;
        }

        // Upload if no errors
        if ($uploadOk == 1 && move_uploaded_file($_FILES['image']['tmp_name'], $target_file)) {
            // File uploaded successfully
        } else {
            $error[] = "Sorry, there was an error uploading your file.";
        }
    }

    // Check if the announcement already exists
    $select = "SELECT * FROM announcement WHERE title = ? AND school_id = ?";
    $stmt_check = mysqli_prepare($conn, $select);
    mysqli_stmt_bind_param($stmt_check, "si", $title, $school_id);
    mysqli_stmt_execute($stmt_check);
    mysqli_stmt_store_result($stmt_check);

    if (mysqli_stmt_num_rows($stmt_check) > 0) {
        $error[] = 'Announcement already exists for this school!';
    } else {
        // Insert new announcement
        if (isset($target_file)) {
            $insert = "INSERT INTO announcement (title, message, image, school_id, department_id, created_at) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
            $stmt_insert = mysqli_prepare($conn, $insert);
            mysqli_stmt_bind_param($stmt_insert, "ssssi", $title, $message, $target_file, $school_id, $department_id);
        } else {
            $insert = "INSERT INTO announcement (title, message, school_id, department_id, created_at) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP)";
            $stmt_insert = mysqli_prepare($conn, $insert);
            mysqli_stmt_bind_param($stmt_insert, "sssi", $title, $message, $school_id, $department_id);
        }

        if ($stmt_insert === false) {
            die('mysqli_prepare() failed: ' . htmlspecialchars(mysqli_error($conn)));
        }

        if (mysqli_stmt_execute($stmt_insert)) {
            $_SESSION['success_message'] = "Announcement added successfully!";
            header('Location: adminannouncement.php'); // Redirect after success
            exit();
        } else {
            $error[] = 'Failed to add announcement.';
        }
    }
}

// Show success or error messages
if (isset($_SESSION['success_message'])) {
    echo '<script>var successMessage = "' . $_SESSION['success_message'] . '";</script>';
    unset($_SESSION['success_message']);
}

if (isset($error)) {
    $errorMessage = implode("<br>", $error);
    echo '<script>console.log("Error Message:", ' . json_encode($errorMessage) . ');</script>';
    echo '<script>var errorMessage = "' . $errorMessage . '";</script>';
}
