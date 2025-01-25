<?php
session_start();
include_once '../connection/conn.php';
$conn = con();
$role = $_SESSION['role'];

// Fetch logs with the correct table structure
$query = "
    SELECT 
        logs.log_id,
        logs.previous_data,
        logs.new_data,
        logs.timestamp AS log_time,            -- Log timestamp
        logs.table_name AS table_name,          -- Name of the table affected
        logs.operation AS log_action,          -- CRUD operation
        logs.record_id AS log_record_id,       -- ID of the affected record
        logs.description AS log_description,   -- Description of the action
        CONCAT(users.firstname, ' ', users.middleinitial, ' ', users.lastname) AS full_name, -- User's full name
        users.age,
        users.gender,
        users.email,
        users.role,
        departments.department_name AS department_name,  
        games.game_name AS game_name,
        schools.school_name AS school_name
    FROM 
        logs
    JOIN 
        users ON logs.user_id = users.id
    LEFT JOIN 
        games ON users.game_id = games.game_id
    LEFT JOIN 
        schools ON users.school_id = schools.school_id
    LEFT JOIN 
        departments ON users.department = departments.id
    ORDER BY 
        logs.timestamp DESC
";

$result = $conn->query($query);

// Check for query execution errors
if (!$result) {
    die('Query failed: ' . htmlspecialchars($conn->error));
}
