<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Debug log
$log_file = __DIR__ . '/form_debug.log';
file_put_contents($log_file, date('Y-m-d H:i:s') . " - Page loaded\n", FILE_APPEND);

// Check for messages
if (isset($_SESSION['error_message'])) {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Error message found: " . $_SESSION['error_message'] . "\n", FILE_APPEND);
}
if (isset($_SESSION['success_message'])) {
    file_put_contents($log_file, date('Y-m-d H:i:s') . " - Success message found: " . $_SESSION['success_message'] . "\n", FILE_APPEND);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register School</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://kit.fontawesome.com/ad90023682.js" crossorigin="anonymous"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../super_admin/sastyles.css">
</head>

<body>

    <?php
    $current_page = 'schools';
    include '../navbar/navbar.php';
    include '../super_admin/sa_sidebar.php';
    ?>
    <div class="main-content">
        <div class="container mt-5">
            <div class="d-flex align-items-center mb-4">
                <a href="schools.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i>
                </a>
                <h1 class="mx-auto mb-0">Register School</h1>
            </div>
        </div>

        <form method="POST" action="register.php" enctype="multipart/form-data" id="registerForm">
            <!-- Logo Upload and Preview -->
            <div class="text-center mb-4">
                <label for="school-logo" class="form-label">School Logo:</label>
                <input type="file" id="school-logo" name="school_logo" class="form-control" accept="image/*" onchange="previewImage(event)">
                <div class="mt-3">
                    <img id="image-preview" src="#" alt="Logo Preview" class="img-thumbnail d-none" style="max-width: 150px;">
                </div>
            </div>

            <!-- School Name and Code -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="school-name" class="form-label">School Name:</label>
                    <input type="text" id="school-name" name="school_name" class="form-control" placeholder="School Name" required>
                </div>
                <div class="col-md-6">
                    <label for="school-code" class="form-label">School Code:</label>
                    <input type="text" id="school-code" name="school_code" class="form-control" placeholder="School Code" required>
                </div>
            </div>

            <!-- Email and Address -->
            <div class="row mb-3">
                <div class="col-md-6">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" id="email" name="email" class="form-control" placeholder="Email" required>
                </div>
                <div class="col-md-6">
                    <label for="address" class="form-label">Address:</label>
                    <input type="text" id="address" name="address" class="form-control" placeholder="Address" required>
                </div>
            </div>

            <!-- Departments Offered -->
            <div class="mb-3">
                <label class="form-label">Departments Offered:</label>
                <div class="row">
                    <div class="col-md-3">
                        <div class="form-check">
                            <input type="checkbox" id="elementary" name="departments[]" value="Elementary" class="form-check-input">
                            <label for="elementary" class="form-check-label">Elementary</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input type="checkbox" id="jhs" name="departments[]" value="JHS" class="form-check-input">
                            <label for="jhs" class="form-check-label">Junior High School</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input type="checkbox" id="shs" name="departments[]" value="SHS" class="form-check-input">
                            <label for="shs" class="form-check-label">Senior High School</label>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-check">
                            <input type="checkbox" id="college" name="departments[]" value="College" class="form-check-input">
                            <label for="college" class="form-check-label">College</label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Password and Confirm Password -->
           <!-- <div class="row mb-3">
                <div class="col-md-6 position-relative">
                    <label for="password" class="form-label">Password:</label>
                    <div class="input-group">
                        <input type="password" id="password" name="password" class="form-control" placeholder="Password" required>
                        <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                </div>
                <div class="col-md-6 position-relative">
                    <label for="confirm-password" class="form-label">Confirm Password:</label>
                    <div class="input-group">
                        <input type="password" id="confirm-password" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
                        <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('confirm-password')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    <small id="password-match" class="text-danger d-none">Passwords do not match</small>
                </div>
            </div>-->

            <button type="submit" class="btn btn-primary w-100">Register</button>
        </form>
    </div>
    </div>
    <!-- Bootstrap JS -->
    <!-- JavaScript -->
    <?php
    if (isset($_SESSION['success_message'])) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Success!',
                    text: '" . addslashes($_SESSION['success_message']) . "',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then((result) => {
                    if (result.isConfirmed) {
                        window.location.href = 'schools.php';
                    }
                });
            });
          </script>";
        unset($_SESSION['success_message']);
    } elseif (isset($_SESSION['error_message'])) {
        echo "<script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Error!',
                    text: '" . addslashes($_SESSION['error_message']) . "',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
            });
          </script>";
        unset($_SESSION['error_message']);
    }
    ?>

    <script>
        // Debug function
        function debugLog(message) {
            console.log(message);
        }

        // Form validation before submission
        document.getElementById('registerForm').addEventListener('submit', function(e) {
            debugLog('Form submission attempted');
            
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm-password').value;
            
            if (password !== confirmPassword) {
                e.preventDefault();
                debugLog('Password mismatch');
                Swal.fire({
                    title: 'Error!',
                    text: 'Passwords do not match!',
                    icon: 'error',
                    confirmButtonText: 'OK'
                });
                return;
            }

            // Check if at least one department is selected
            const departments = document.querySelectorAll('input[name="departments[]"]:checked');
            if (departments.length === 0) {
                e.preventDefault();
                debugLog('No departments selected');
                Swal.fire({
                    title: 'Warning!',
                    text: 'Please select at least one department.',
                    icon: 'warning',
                    confirmButtonText: 'OK'
                });
                return;
            }

            debugLog('Form validation passed, submitting form');
        });

        function previewImage(event) {
            debugLog('Image preview triggered');
            const imagePreview = document.getElementById('image-preview');
            const file = event.target.files[0];
            if (file) {
                // Check file size (max 2MB)
                if (file.size > 2 * 1024 * 1024) {
                    debugLog('File size too large');
                    Swal.fire({
                        title: 'Error!',
                        text: 'File size exceeds 2MB limit!',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                    event.target.value = ''; // Clear the file input
                    return;
                }

                // Check file type
                const allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!allowedTypes.includes(file.type)) {
                    debugLog('Invalid file type');
                    Swal.fire({
                        title: 'Error!',
                        text: 'Invalid file type. Please upload a JPG, PNG, or GIF image.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                    event.target.value = ''; // Clear the file input
                    return;
                }

                debugLog('Reading image file');
                const reader = new FileReader();
                reader.onload = function(e) {
                    imagePreview.src = e.target.result;
                    imagePreview.classList.remove('d-none');
                };
                reader.readAsDataURL(file);
            } else {
                imagePreview.src = '#';
                imagePreview.classList.add('d-none');
            }
        }

        function togglePasswordVisibility(fieldId) {
            const field = document.getElementById(fieldId);
            const icon = field.nextElementSibling.querySelector('i');
            if (field.type === 'password') {
                field.type = 'text';
                icon.classList.replace('fa-eye', 'fa-eye-slash');
            } else {
                field.type = 'password';
                icon.classList.replace('fa-eye-slash', 'fa-eye');
            }
        }

        // Password match validation
        const passwordField = document.getElementById('password');
        const confirmPasswordField = document.getElementById('confirm-password');
        const passwordMatchIndicator = document.getElementById('password-match');

        function validatePasswords() {
            if (confirmPasswordField.value && passwordField.value !== confirmPasswordField.value) {
                passwordMatchIndicator.classList.remove('d-none');
                return false;
            } else {
                passwordMatchIndicator.classList.add('d-none');
                return true;
            }
        }

        passwordField.addEventListener('input', validatePasswords);
        confirmPasswordField.addEventListener('input', validatePasswords);

        // Add this at the end of your script
        debugLog('JavaScript initialized');
    </script>
</body>

</html>