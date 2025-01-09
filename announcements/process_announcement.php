<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

// Helper function for redirection
function redirect_with_message($url, $status, $message)
{
    $_SESSION[$status . '_message'] = $message;
    header("Location: $url?status=$status");
    exit();
}

// Deletion Logic
if (isset($_GET['delete'])) {
    $announcement_id = $_GET['delete'];

    // Ensure the announcement ID is valid
    if (empty($announcement_id) || !is_numeric($announcement_id)) {
        redirect_with_message('sa_announcement.php', 'error', 'Invalid announcement ID.');
    }

    // Sanitize the announcement ID (even though it's numeric, it's a good habit)
    $announcement_id = intval($announcement_id);  // Ensures that $announcement_id is an integer

    // Delete from `announcement` table
    $sql_delete = "DELETE FROM announcement WHERE id = ?";
    $stmt_delete = mysqli_prepare($conn, $sql_delete);
    mysqli_stmt_bind_param($stmt_delete, 'i', $announcement_id);

    if (mysqli_stmt_execute($stmt_delete)) {
        redirect_with_message('sa_announcement.php', 'success', 'Announcement deleted successfully.');
    } else {
        // Log the error for debugging or show more details if needed
        error_log("Deletion failed: " . mysqli_error($conn)); // Log the error in case of failure
        redirect_with_message('sa_announcement.php', 'error', 'Failed to delete the announcement. Please try again.');
    }
    exit();
}


// Check if the form is submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate input
    $announcement_id = $_POST['announcement_id'] ?? null;
    $title = trim($_POST['title'] ?? '');
    $message = trim($_POST['message'] ?? '');
    $department_id = isset($_POST['department_id']) ? intval($_POST['department_id']) : 0;
    $school_id = isset($_POST['school_id']) ? intval($_POST['school_id']) : 0;

    // Validate required fields
    if (empty($title) || empty($message)) {
        redirect_with_message('announcement_details.php?id=' . $announcement_id, 'error', 'Title and message are required.');
    }

    // Handle file upload
    $image = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileName = $_FILES['image']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        // Allowed file extensions
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif'];
        if (!in_array($fileExtension, $allowedExtensions)) {
            redirect_with_message('announcement_details.php?id=' . $announcement_id, 'error', 'Invalid file type. Allowed types: ' . implode(', ', $allowedExtensions));
        }

        // Set upload directory and generate unique file name
        $uploadDir = '../uploads/';
        $image = $uploadDir . uniqid('img_', true) . '.' . $fileExtension;

        // Move uploaded file
        if (!move_uploaded_file($fileTmpPath, $image)) {
            redirect_with_message('announcement_details.php?id=' . $announcement_id, 'error', 'Failed to upload the file.');
        }
    }

    // Determine SQL query and parameters
    if ($announcement_id) {
        // Update existing announcement
        $sql = empty($image)
            ? "UPDATE announcement SET title = ?, message = ? WHERE id = ?"
            : "UPDATE announcement SET title = ?, message = ?, image = ? WHERE id = ?";
        $params = empty($image)
            ? [$title, $message, $announcement_id]
            : [$title, $message, $image, $announcement_id];
    } else {
        // Insert new announcement
        $sql = "INSERT INTO announcement (title, message, image, department_id, school_id) VALUES (?, ?, ?, ?, ?)";
        $params = [$title, $message, $image, $department_id, $school_id];
    }

    // Prepare and execute SQL query
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        redirect_with_message('announcement_details.php?id=' . $announcement_id, 'error', 'Database error: ' . mysqli_error($conn));
    }

    $types = str_repeat('s', count($params) - 1) . 'i'; // Adjust types based on parameters
    mysqli_stmt_bind_param($stmt, $types, ...$params);

    if (mysqli_stmt_execute($stmt)) {
        redirect_with_message('sa_announcement.php', 'success', 'Announcement saved successfully.');
    } else {
        redirect_with_message('announcement_details.php?id=' . $announcement_id, 'error', 'Database error: ' . mysqli_error($conn));
    }
} else {
    // Invalid request method
    redirect_with_message('sa_announcement.php', 'error', 'Invalid request method.');
}
