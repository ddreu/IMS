<?php
// send-password-reset.php

session_start();
date_default_timezone_set('Asia/Manila');
include_once 'connection/conn.php';
$conn = con();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST["email"]);

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Please enter a valid email address.'
        ]);
        exit;
    }

    require_once __DIR__ . "/email_handler.php";

    $token = generateToken();
    $tokenResult = storeToken($conn, $email, $token);

    if ($tokenResult['success']) {
        $emailResult = sendPasswordResetEmail($email, $token);
        echo json_encode([
            'status' => $emailResult['success'] ? 'success' : 'error',
            'message' => $emailResult['message']
        ]);
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => $tokenResult['message']
        ]);
    }
}
