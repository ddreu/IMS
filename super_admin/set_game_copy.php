<?php
session_start();
require_once '../connection/conn.php'; // Ensure you include your database connection

$conn = con();

// Check if game_id is passed
if (isset($_GET['game_id']) && !empty($_GET['game_id'])) {
    $game_id = $_GET['game_id'];

    // Fetch the game name from the database
    $stmtGame = $conn->prepare("SELECT game_name FROM games WHERE game_id = ? AND is_archived = 0");
    $stmtGame->bind_param("i", $game_id);
    $stmtGame->execute();
    $resultGame = $stmtGame->get_result();

    if ($resultGame->num_rows > 0) {
        // Fetch game name and set session variables
        $rowGame = $resultGame->fetch_assoc();
        $_SESSION['game_id'] = $game_id;  // Set game_id in session
        $_SESSION['game_name'] = $rowGame['game_name'];  // Set game_name in session

        // Fetch a random department from the selected school
        if (isset($_SESSION['school_id']) && !empty($_SESSION['school_id'])) {
            $school_id = $_SESSION['school_id'];

            // Fetch a random department for the school
            $stmtDepartment = $conn->prepare("SELECT id, department_name FROM departments WHERE school_id = ? AND is_archived = 0 ORDER BY RAND() LIMIT 1");
            $stmtDepartment->bind_param("i", $school_id);
            $stmtDepartment->execute();
            $resultDepartment = $stmtDepartment->get_result();

            if ($resultDepartment->num_rows > 0) {
                // Set the department session variables
                $rowDepartment = $resultDepartment->fetch_assoc();
                $_SESSION['department_id'] = $rowDepartment['id'];  // Set department_id in session
                $_SESSION['department_name'] = $rowDepartment['department_name'];  // Set department_name in session
            } else {
                echo "No active department found for the selected school.";
                exit();
            }
        } else {
            echo "School ID not found in session.";
            exit();
        }
    } else {
        echo "No game selected or the game is archived.";
        exit();
    }

    // Redirect back to the referring page
    $redirect_url = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'default_page.php'; // Use 'default_page.php' if no referrer is available
    header('Location: ' . $redirect_url);
    exit();
} else {
    // Handle error if no game_id is selected
    echo "No game selected.";
    exit();
}
