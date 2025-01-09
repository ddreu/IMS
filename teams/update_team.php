<?php
include_once '../connection/conn.php'; 
$conn = con();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $team_id = $_POST['team_id'];
    $team_name = $_POST['team_name'];
    $year_level = $_POST['year_level'] ?? null;
    $section = $_POST['section'] ?? null;
    $course = $_POST['course'] ?? null;

    // Update the team in the database
    $update_sql = "UPDATE teams SET team_name = ?, year_level = ?, section = ?, course = ? WHERE team_id = ?";
    $update_stmt = mysqli_prepare($conn, $update_sql);
    mysqli_stmt_bind_param($update_stmt, "ssssi", $team_name, $year_level, $section, $course, $team_id);

    if (mysqli_stmt_execute($update_stmt)) {
        echo "Team updated successfully!";
    } else {
        echo "Error updating team: " . mysqli_error($conn);
    }

    mysqli_stmt_close($update_stmt);
    mysqli_close($conn);
    // Redirect back to the teams page or refresh
    header("Location: teams.php");
    exit();
}
?>
