<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include_once '../connection/conn.php';
include_once '../includes/password_utils.php';

$conn = con();

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    if (!$conn) {
        $_SESSION['error_message'] = "Database connection failed";
        header("Location: register_school.php");
        exit();
    }

    // Retrieve form data and sanitize input
    $school_name = $conn->real_escape_string($_POST['school_name']);
    $school_code = $conn->real_escape_string($_POST['school_code']);
    $address = $conn->real_escape_string($_POST['address']);
    $email = $conn->real_escape_string($_POST['email']);

    // Generate secure password
    $password_to_use = generateSecurePassword($school_code, 'School Admin');
    $hashed_password = password_hash($password_to_use, PASSWORD_DEFAULT);

    // Check if school name or email already exists
    $checkSchool = "SELECT * FROM schools WHERE school_name = ?";
    $stmt = $conn->prepare($checkSchool);
    $stmt->bind_param("s", $school_name);
    $stmt->execute();
    $schoolResult = $stmt->get_result();

    $checkEmail = "SELECT * FROM users WHERE email = ?";
    $stmt = $conn->prepare($checkEmail);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $emailResult = $stmt->get_result();

    if ($schoolResult->num_rows > 0) {
        $_SESSION['error_message'] = "School name already exists.";
        header("Location: register_school.php");
        exit();
    } elseif ($emailResult->num_rows > 0) {
        $_SESSION['error_message'] = "Email is already registered.";
        header("Location: register_school.php");
        exit();
    }

    // Handle Image Upload
    $new_file_name = null;
    if (isset($_FILES['school_logo']) && $_FILES['school_logo']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../uploads/logos/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_tmp = $_FILES['school_logo']['tmp_name'];
        $file_name = basename($_FILES['school_logo']['name']);
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        // Validate file size and type
        $max_file_size = 2 * 1024 * 1024;
        $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

        if ($_FILES['school_logo']['size'] > $max_file_size) {
            $_SESSION['error_message'] = "File size exceeds the maximum allowed size of 2MB.";
            header("Location: register_school.php");
            exit();
        }

        if (!in_array($file_ext, $allowed_ext)) {
            $_SESSION['error_message'] = "Invalid file type. Only JPG, JPEG, PNG, and GIF files are allowed.";
            header("Location: register_school.php");
            exit();
        }

        $new_file_name = uniqid('school_', true) . '.' . $file_ext;
        $file_path = $upload_dir . $new_file_name;

        if (!move_uploaded_file($file_tmp, $file_path)) {
            $_SESSION['error_message'] = "Failed to upload logo file.";
            header("Location: register_school.php");
            exit();
        }
    }

    try {
        $conn->begin_transaction();

        // Insert school data
        $insertSchool = "INSERT INTO schools (school_name, school_code, address, logo) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($insertSchool);
        $stmt->bind_param("ssss", $school_name, $school_code, $address, $new_file_name);

        if (!$stmt->execute()) {
            throw new Exception("Failed to insert school data");
        }

        $school_id = $conn->insert_id;

        // Insert departments for the school
        if (isset($_POST['departments']) && is_array($_POST['departments'])) {
            $sql_dept = "INSERT INTO departments (department_name, school_id) VALUES (?, ?)";
            $stmt_dept = $conn->prepare($sql_dept);
            if (!$stmt_dept) {
                throw new Exception("Failed to prepare department statement");
            }

            foreach ($_POST['departments'] as $department) {
                $department = trim($department);
                if (!empty($department)) {
                    $stmt_dept->bind_param("si", $department, $school_id);
                    if (!$stmt_dept->execute()) {
                        throw new Exception("Failed to insert department: " . $stmt_dept->error);
                    }
                }
            }
        }

        // Insert school admin user
        $insertUser = "INSERT INTO users (firstname, lastname, email, password, role, school_id, first_login) VALUES (?, ?, ?, ?, 'School Admin', ?, 'yes')";
        $stmt = $conn->prepare($insertUser);
        $name_parts = explode(' ', $school_name, 2);
        $firstname = $name_parts[0];
        $lastname = isset($name_parts[1]) ? $name_parts[1] : 'Admin';

        $stmt->bind_param("ssssi", $firstname, $lastname, $email, $hashed_password, $school_id);

        if (!$stmt->execute()) {
            throw new Exception("Failed to insert user data");
        }

        $conn->commit();

        // Send registration email
        require_once('../send-registration-email.php');
        $emailResult = sendRegistrationEmail($email, $password_to_use, $school_name);

        if ($emailResult['success']) {
            $_SESSION['success_message'] = "School registered successfully! Check your email for login credentials.";
        } else {
            $_SESSION['warning_message'] = "School registered successfully but there was an issue sending the email. Please contact support.";
        }

        header("Location: register_school.php");
        exit();
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error_message'] = "Registration failed: " . $e->getMessage();
        header("Location: register_school.php");
        exit();
    }
} else {
    header("Location: register_school.php");
    exit();
}
