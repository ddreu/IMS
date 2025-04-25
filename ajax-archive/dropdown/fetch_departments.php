<?php
require_once '../../connection/conn.php';
$conn = con();

$school_id = $_GET['school_id'];

$query = $conn->query("SELECT * FROM departments WHERE school_id = '$school_id'");
echo '<option value="">Select Department</option>';
while ($row = $query->fetch_assoc()) {
    echo "<option value='{$row['id']}'>{$row['department_name']}</option>";
}
