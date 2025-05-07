<?php
session_start();
require_once '../../connection/conn.php';
$conn = con();

$role = $_SESSION['role'];

$school_id = ($role === 'superadmin')
    ? ($_GET['school_id'] ?? null)
    : ($_SESSION['school_id'] ?? null);

if (!$school_id) {
    exit("<option value=''>School ID missing</option>");
}

$query = $conn->query("SELECT YEAR(archived_at) AS year FROM archives WHERE school_id = '$school_id' ORDER BY year DESC");
echo '<option value="">Select Year</option>';
while ($row = $query->fetch_assoc()) {
    echo "<option value='{$row['year']}'>{$row['year']}</option>";
}
