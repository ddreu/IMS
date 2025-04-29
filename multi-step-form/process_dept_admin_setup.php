<?php
session_start();

$_SESSION['success_message'] = "Department Setup Completed Successfully! Welcome to your Dashboard!";
$_SESSION['success_type'] = "Department Admin";

echo json_encode(['success' => true]);
exit;
