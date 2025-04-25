<?php
require_once '../../connection/conn.php';
$conn = con();

$school_id = $_GET['school_id'];

$query = $conn->query("SELECT YEAR(archived_at) AS year FROM archives WHERE school_id = '$school_id' ORDER BY year DESC");
echo '<option value="">Select Year</option>';
while ($row = $query->fetch_assoc()) {
    echo "<option value='{$row['year']}'>{$row['year']}</option>";
}
