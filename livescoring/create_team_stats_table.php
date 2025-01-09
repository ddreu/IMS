<?php
include_once '../connection/conn.php';
$conn = con();

try {
    // Create team_stats table if it doesn't exist
    $sql = "CREATE TABLE IF NOT EXISTS team_stats (
        id INT AUTO_INCREMENT PRIMARY KEY,
        game_id INT NOT NULL,
        team_id INT NOT NULL,
        timeouts INT DEFAULT 0,
        fouls INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY unique_game_team (game_id, team_id)
    )";
    
    $conn->exec($sql);
    echo "Team stats table created successfully";
} catch(PDOException $e) {
    echo "Error creating table: " . $e->getMessage();
}
