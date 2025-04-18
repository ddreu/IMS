<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
$offset = ($page - 1) * $limit;

// Get sorting parameters
$sort_column = isset($_GET['sort']) ? $_GET['sort'] : 'timestamp';
$sort_order = isset($_GET['order']) ? $_GET['order'] : 'DESC';

// Validate sort column to prevent SQL injection
$allowed_columns = ['timestamp', 'full_name', 'log_action', 'log_record_id', 'log_description'];
if (!in_array($sort_column, $allowed_columns)) {
    $sort_column = 'timestamp';
}

// Validate sort order
$sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';

// $query = "
//     SELECT 
//         logs.log_id,
//         logs.previous_data,
//         logs.new_data,
//         logs.timestamp AS log_time,
//         logs.table_name AS table_name,
//         logs.operation AS log_action,
//         logs.record_id AS log_record_id,
//         logs.description AS log_description,
//         CONCAT(users.firstname, ' ', users.middleinitial, ' ', users.lastname) AS full_name,
//         users.age,
//         users.gender,
//         users.email,
//         users.role,
//         departments.department_name AS department_name,
//         games.game_name AS game_name,
//         schools.school_name AS school_name
//     FROM 
//         logs
//     JOIN 
//         users ON logs.user_id = users.id
//     LEFT JOIN 
//         games ON users.game_id = games.game_id
//     LEFT JOIN 
//         schools ON users.school_id = schools.school_id
//     LEFT JOIN 
//         departments ON users.department = departments.id
//     ORDER BY 
//         $sort_column $sort_order
//     LIMIT $offset, $limit
// ";

$query = "
    SELECT 
        logs.log_id,
        logs.previous_data,
        logs.new_data,
        logs.timestamp AS log_time,
        logs.table_name AS table_name,
        logs.operation AS log_action,
        logs.record_id AS log_record_id,
        logs.description AS log_description,
        CASE 
            WHEN users.firstname IS NULL OR users.lastname IS NULL 
                 OR users.firstname = '' OR users.lastname = '' 
            THEN users.email
            ELSE CONCAT(users.firstname, ' ', users.middleinitial, ' ', users.lastname)
        END AS full_name,
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
        $sort_column $sort_order
    LIMIT $offset, $limit
";


$result = $conn->query($query);

if (!$result) {
    die(json_encode(['status' => 'error', 'message' => $conn->error]));
}

$logs = [];
while ($row = $result->fetch_assoc()) {
    // Force timezone conversion from UTC to Asia/Manila
    $date = new DateTime($row['log_time'], new DateTimeZone('UTC'));
    $date->setTimezone(new DateTimeZone('Asia/Manila'));

    // Format it correctly
    $row['log_time'] = $date->format('M j Y h:i A'); // Example: "Feb 4 2025 11:00 PM"

    $logs[] = $row;
}


// Get total count for pagination
$count_query = "
    SELECT COUNT(*) as total 
    FROM logs 
    JOIN users ON logs.user_id = users.id
";

$count_result = $conn->query($count_query);
$total_rows = $count_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $limit);

header('Content-Type: application/json');
echo json_encode([
    'status' => 'success',
    'data' => $logs,
    'pagination' => [
        'current_page' => $page,
        'total_pages' => $total_pages,
        'total_records' => $total_rows
    ]
]);
