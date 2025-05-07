<?php
session_start();
require_once '../../connection/conn.php';
$conn = con();

$role = $_SESSION['role'] ?? null;
$year = $_GET['year'] ?? null;

$school_id = ($role === 'superadmin')
    ? ($_GET['school_id'] ?? null)
    : ($_SESSION['school_id'] ?? null);

if (!$school_id || !$year) {
    exit("<option value=''>Missing school ID or year</option>");
}

$stmt = $conn->prepare("SELECT id, department_name FROM departments WHERE school_id = ? AND is_archived = 1 AND YEAR(archived_at) = ?");
$stmt->bind_param("ii", $school_id, $year);
$stmt->execute();
$result = $stmt->get_result();

echo '<option value="">Select Department</option>';
while ($row = $result->fetch_assoc()) {
    echo "<option value='{$row['id']}'>{$row['department_name']}</option>";
}
