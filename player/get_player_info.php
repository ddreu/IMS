<?php
header('Content-Type: application/json');
include_once '../connection/conn.php';
$conn = con();


if (isset($_GET['player_id'])) {
    // Ensure player_id is an integer to prevent SQL injection
    $player_id = intval($_GET['player_id']);

    $sql = "
        SELECT p.player_id, p.jersey_number, pi.email, pi.phone_number, pi.date_of_birth, 
               pi.picture, pi.height, pi.weight, pi.position, 
               p.player_firstname AS firstname, p.player_middlename AS middlename, 
               p.player_lastname AS lastname
        FROM players AS p
        LEFT JOIN players_info AS pi ON p.player_id = pi.player_id
        WHERE p.player_id = ?
    ";

    $stmt = $conn->prepare($sql);

    if ($stmt) {
        // Bind parameters and execute
        $stmt->bind_param("i", $player_id);
        $stmt->execute();
        $result = $stmt->get_result();

        // Fetch the player data
        if ($result->num_rows > 0) {
            $player_data = $result->fetch_assoc();
            echo json_encode($player_data);
        } else {
            echo json_encode(["error" => "Player not found"]);
        }

        $stmt->close();
    } else {
        echo json_encode(["error" => "Database query error"]);
    }
} else {
    echo json_encode(["error" => "Invalid player ID"]);
}

// Close the database connection
$conn->close();
