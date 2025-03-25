<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['school_id'])) {
    $response['message'] = 'Session expired. Please log in again.';
    echo json_encode($response);
    exit;
}

$school_id = $_SESSION['school_id'];
$title = $_POST['title'] ?? null;
$description = $_POST['description'] ?? null;
$cover_image = null;

// Check if there's an existing record
$query = "SELECT * FROM school_profile WHERE school_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $school_id);
$stmt->execute();
$result = $stmt->get_result();
$existing = $result->fetch_assoc();

// Handle image upload if provided
if (!empty($_FILES['cover_image']['name'])) {
    $upload_dir = '../uploads/school-profile/';
    $file_name = time() . '_' . basename($_FILES['cover_image']['name']);
    $file_path = $upload_dir . $file_name;

    // Check file type (only allow JPG, JPEG, PNG)
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png'];
    if (!in_array($_FILES['cover_image']['type'], $allowed_types)) {
        $response['message'] = 'Invalid file type. Only JPG, JPEG, and PNG are allowed.';
        echo json_encode($response);
        exit;
    }

    if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $file_path)) {
        $cover_image = $file_path;
    } else {
        $response['message'] = 'Failed to upload image.';
        echo json_encode($response);
        exit;
    }
}

if ($existing) {
    // Update existing record
    $query = "UPDATE school_profile SET";
    $params = [];
    $types = '';

    if ($title !== null) {
        $query .= " title = ?,";
        $params[] = $title;
        $types .= 's';
    }

    if ($description !== null) {
        $query .= " description = ?,";
        $params[] = $description;
        $types .= 's';
    }

    if ($cover_image !== null) {
        // Delete old image if it exists
        if (!empty($existing['cover_image']) && file_exists($existing['cover_image'])) {
            unlink($existing['cover_image']);
        }
        $query .= " image = ?,";
        $params[] = $cover_image;
        $types .= 's';
    }

    // Remove trailing comma
    $query = rtrim($query, ',');

    $query .= " WHERE school_id = ?";
    $params[] = $school_id;
    $types .= 'i';

    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Profile updated successfully!';
    } else {
        $response['message'] = 'Failed to update profile.';
    }
} else {
    // Insert new record
    $query = "INSERT INTO school_profile (school_id, title, description, image) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "isss",
        $school_id,
        $title,
        $description,
        $cover_image
    );

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Profile created successfully!';
    } else {
        $response['message'] = 'Failed to create profile.';
    }
}

echo json_encode($response);
