<?php
session_start();
require_once '../connection/conn.php'; // Ensure you include your database connection

$conn = con();
// Check if school_id is passed
if (isset($_GET['school_id']) && !empty($_GET['school_id'])) {
    $school_id = $_GET['school_id'];

    // Fetch the school name from the database
    $stmt = $conn->prepare("SELECT school_name FROM schools WHERE school_id = ? AND is_archived = 0");
    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Fetch school name and set session variables
        $row = $result->fetch_assoc();
        $_SESSION['school_id'] = $school_id;  // Set school_id in session
        $_SESSION['school_name'] = $row['school_name'];  // Set school_name in session
    }

    // Redirect back to the referring page
    $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'default_page.php'; // Use 'default_page.php' if no referrer is available
    header('Location: ' . $redirect_url);
    exit();
} else {
    // Handle error if no school_id is selected
    echo "No school selected.";
    exit();
}
