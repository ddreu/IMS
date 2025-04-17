<?php
session_start();
include_once '../connection/conn.php';
include '../user_logs/logger.php';
$conn = con();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Regenerate session ID for security
session_regenerate_id(true);

// Check if session variables are set
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Store user session variables
$user_id = $_SESSION['user_id'];
$email = $_SESSION['email'];
$role = $_SESSION['role'];
$lastname = $_SESSION['lastname'];
$firstname = $_SESSION['firstname'];
$middleinitial = $_SESSION['middleinitial'];
$age = $_SESSION['age'];
$gender = $_SESSION['gender'];
$department_id = $_SESSION['department_id'];
$department_name = $_SESSION['department_name'];
$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'];
$school_code = $_SESSION['school_code'];
$game_id = $_SESSION['game_id'] ?? null;
$game_name = $_SESSION['game_name'] ?? null;

// Fetch the current hashed password
$sql = "SELECT password, image FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $hashed_password = $row['password'];
    $user_image = $row['image'] ?? null;
} else {
    header('Location: error.php?message=User not found');
    exit();
}



// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    updateUserProfile($conn, $user_id);
}

// Function to update user profile
function updateUserProfile($conn, $user_id)
{
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname = trim($_POST['lastname'] ?? '');
    $middleinitial = trim($_POST['middleinitial'] ?? '');
    $age = trim($_POST['age'] ?? '');
    $gender = trim($_POST['gender'] ?? '');
    $email = trim($_POST['email'] ?? '');

    // Validate input
    if (empty($firstname) || empty($lastname) || empty($age) || empty($gender) || empty($email)) {
        $_SESSION['error_message'] = "All required fields must be filled out.";
        return;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Invalid email format.";
        return;
    }

    if (!is_numeric($age) || $age < 0) {
        $_SESSION['error_message'] = "Age must be a valid number.";
        return;
    }

    // Handle profile picture upload
    $image_filename = null;

    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/users/';
        $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $image_filename = 'user_' . $user_id . '_' . time() . '.' . $ext;

        // Validate file type and size
        $allowed_types = ['jpg', 'jpeg', 'png'];
        $max_size = 2 * 1024 * 1024; // 2MB

        if (!in_array(strtolower($ext), $allowed_types)) {
            $_SESSION['error_message'] = "Invalid file type. Only JPG and PNG are allowed.";
            return;
        }

        if ($_FILES['profile_picture']['size'] > $max_size) {
            $_SESSION['error_message'] = "Image exceeds 2MB size limit.";
            return;
        }

        // Move file to uploads
        if (!move_uploaded_file($_FILES['profile_picture']['tmp_name'], $upload_dir . $image_filename)) {
            $_SESSION['error_message'] = "Failed to upload image.";
            return;
        }
    }

    // SQL update with or without image
    if ($image_filename) {
        $sql = "UPDATE users SET firstname = ?, lastname = ?, middleinitial = ?, age = ?, gender = ?, email = ?, image = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssissi", $firstname, $lastname, $middleinitial, $age, $gender, $email, $image_filename, $user_id);

        // Delete old image if exists and not default
        $get_old = $conn->prepare("SELECT image FROM users WHERE id = ?");
        $get_old->bind_param("i", $user_id);
        $get_old->execute();
        $old_result = $get_old->get_result();
        if ($old_result && $old_data = $old_result->fetch_assoc()) {
            $old_image = $old_data['image'];
            if (!empty($old_image) && $old_image !== 'default-profile.jpg' && file_exists("../uploads/users/$old_image")) {
                unlink("../uploads/users/$old_image");
            }
        }
        $get_old->close();
    } else {
        $sql = "UPDATE users SET firstname = ?, lastname = ?, middleinitial = ?, age = ?, gender = ?, email = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssisi", $firstname, $lastname, $middleinitial, $age, $gender, $email, $user_id);
    }

    if ($stmt->execute()) {
        $_SESSION['firstname'] = $firstname;
        $_SESSION['lastname'] = $lastname;
        $_SESSION['middleinitial'] = $middleinitial;
        $_SESSION['age'] = $age;
        $_SESSION['gender'] = $gender;
        $_SESSION['email'] = $email;

        if ($image_filename) {
            $_SESSION['image'] = $image_filename;
        }

        $_SESSION['success_message'] = "Profile updated successfully.";

        // Log the update
        $description = "Updated user profile details.";
        logUserAction(
            $conn,
            $user_id,
            'Profile',
            'UPDATE',
            $user_id,
            $description
        );
    } else {
        $_SESSION['error_message'] = "Error updating profile: " . $stmt->error;
    }

    $stmt->close();
}

// Fetch departments only if school_id is available
$departments = [];
if ($school_id) {
    $department_result = mysqli_query($conn, "SELECT id, department_name FROM departments WHERE school_id = $school_id");

    // If the query is successful, fetch departments
    if ($department_result) {
        $departments = mysqli_fetch_all($department_result, MYSQLI_ASSOC);
    }
}

$profile_picture_path = !empty($user_image) && file_exists("../uploads/users/$user_image")
    ? "../uploads/users/$user_image"
    : "../assets/defaults/default-profile.jpg";


$stmt->close();
$conn->close();
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../styles/committee.css">
    <link rel="stylesheet" href="../styles/dashboard.css">
    <style>
        html,
        body {
            height: 100%;
            overflow-x: hidden;
        }

        .main {
            min-height: 100vh;
        }


        .navbar {
            position: sticky !important;
            top: 0;
            z-index: 1050;
        }
    </style>
</head>


<body>
    <?php include '../navbar/navbar.php'; ?>
    <nav>
        <?php
        // include '../navbar/navbar.php';

        $current_page = 'user settings';
        if ($role == 'Committee') {
            include '../committee/csidebar.php';
        } elseif ($role == 'superadmin') {
            include '../super_admin/sa_sidebar.php'; // fallback for other roles
        } else {
            include '../department_admin/sidebar.php'; // fallback for other roles
        }
        ?>
    </nav>

    <div class="main">
        <div class="container mt-4">
            <h1 class="mb-4"><?php echo htmlspecialchars($role); ?> Profile</h1>
            <div class="card mx-auto mt-5 shadow-sm" style="max-width: 960px; width: 100%;">
                <div class="card-body p-4">
                    <div class="d-flex flex-column align-items-center text-center mb-4">
                        <div style="position: relative;">
                            <img id="profileImagePreview" src="<?php echo $profile_picture_path; ?>" alt="Profile Picture" class="rounded-circle border border-3 border-white shadow" style="width: 140px; height: 140px; object-fit: cover;">
                        </div>
                        <!-- <div class="mt-3">
                            <input type="file" class="form-control form-control-sm mx-auto" style="max-width: 300px;" disabled>
                            <small class="text-muted">Profile picture upload coming soon</small>
                        </div> -->
                    </div>

                    <form id="updateUserInfoForm" method="POST" action="profile_settings.php" enctype="multipart/form-data" onsubmit="event.preventDefault(); confirmUpdate('updateUserInfoForm', 'update your profile');">
                        <input type="hidden" name="update_profile" value="1">
                        <div class="mt-3 mb-3">
                            <input type="file" name="profile_picture" id="profilePictureInput" accept="image/*" onchange="previewImage(event)" class="form-control form-control-sm mx-auto" style="max-width: 300px;">
                            <!-- <small class="text-muted">Accepted formats: JPG, PNG. Max size: 2MB.</small> -->
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="firstname" class="form-label">First Name</label>
                                <input type="text" name="firstname" class="form-control" value="<?php echo htmlspecialchars($firstname); ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="lastname" class="form-label">Last Name</label>
                                <input type="text" name="lastname" class="form-control" value="<?php echo htmlspecialchars($lastname); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="middleinitial" class="form-label">M.I.</label>
                                <input type="text" name="middleinitial" class="form-control" value="<?php echo htmlspecialchars($middleinitial); ?>" maxlength="2">
                            </div>
                            <div class="col-md-4">
                                <label for="age" class="form-label">Age</label>
                                <input type="number" name="age" class="form-control" value="<?php echo htmlspecialchars($age); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="gender" class="form-label">Gender</label>
                                <select name="gender" class="form-select" required>
                                    <option value="Male" <?php if ($gender == 'Male') echo 'selected'; ?>>Male</option>
                                    <option value="Female" <?php if ($gender == 'Female') echo 'selected'; ?>>Female</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Role</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($role); ?>" disabled>
                            </div>
                            <?php if (!empty($school_name)): ?>
                                <div class="col-md-6">
                                    <label class="form-label">School</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($school_name); ?>" disabled>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="row mb-3">
                            <?php if (!empty($department_name)): ?>
                                <div class="col-md-6">
                                    <label class="form-label">Department</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($department_name); ?>" disabled>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($game_name)): ?>
                                <div class="col-md-6">
                                    <label class="form-label">Game</label>
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($game_name); ?>" disabled>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label for="current_password" class="form-label">Current Password</label>
                            <div class="input-group">
                                <input type="password" name="current_password" class="form-control" required id="currentPassword">
                                <span class="input-group-text">
                                    <i class="fas fa-eye" id="toggleCurrentPassword" onclick="togglePasswordVisibility('currentPassword', 'toggleCurrentPassword')"></i>
                                </span>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#changePasswordModal">Change Password</button>
                            <button type="submit" class="btn btn-success">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>


            <!-- Modal for Change Password -->
            <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="changePasswordForm" method="POST" action="change_password.php" onsubmit="event.preventDefault(); confirmUpdate('changePasswordForm', 'change your password');">
                                <div class="mb-3">
                                    <label for="current_password" class="form-label">Current Password:</label>
                                    <div class="input-group">
                                        <input type="password" name="current_password" class="form-control" id="currentPassword" required>
                                        <span class="input-group-text">
                                            <i class="fas fa-eye" id="toggleCurrentPassword" onclick="togglePasswordVisibility('currentPassword', 'toggleCurrentPassword')"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="new_password" class="form-label">New Password:</label>
                                    <div class="input-group">
                                        <input type="password" name="new_password" class="form-control" id="newPassword" required>
                                        <span class="input-group-text">
                                            <i class="fas fa-eye" id="toggleNewPassword" onclick="togglePasswordVisibility('newPassword', 'toggleNewPassword')"></i>
                                        </span>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_new_password" class="form-label">Confirm New Password:</label>
                                    <div class="input-group">
                                        <input type="password" name="confirm_new_password" class="form-control" id="confirmNewPassword" required>

                                        <span class="input-group-text">
                                            <i class="fas fa-eye" id="toggleConfirmNewPassword" onclick="togglePasswordVisibility('confirmNewPassword', 'toggleConfirmNewPassword')"></i>
                                        </span>
                                    </div>
                                    <small id="passwordMatchMessage" class="text-danger mt-1"></small>

                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Change Password</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>


            <script>
                //change password
                document.getElementById('changePasswordForm').onsubmit = function(event) {
                    event.preventDefault(); // Prevent default form submission

                    // Prepare form data
                    const formData = new FormData(this);

                    fetch('change_password.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Show success message
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Password Changed!',
                                    text: 'Your password has been successfully updated.',
                                    confirmButtonText: 'Okay'
                                }).then(() => {
                                    // Close the modal
                                    const modalElement = document.getElementById('changePasswordModal');
                                    const modalInstance = bootstrap.Modal.getInstance(modalElement);
                                    modalInstance.hide();
                                    resetPasswordFormVisuals();

                                    // Reset the form fields
                                    this.reset(); // Clear the form fields
                                });
                            } else {
                                // Show error message
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: data.message,
                                    confirmButtonText: 'Try Again'
                                });
                            }
                        })
                        .catch(error => {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'An unexpected error occurred. Please try again later.',
                                confirmButtonText: 'Okay'
                            });
                        });
                };

                //

                function togglePasswordVisibility(inputId, iconId) {
                    const input = document.getElementById(inputId);
                    const icon = document.getElementById(iconId);
                    if (input.type === "password") {
                        input.type = "text";
                        icon.classList.remove("fa-eye");
                        icon.classList.add("fa-eye-slash");
                    } else {
                        input.type = "password";
                        icon.classList.remove("fa-eye-slash");
                        icon.classList.add("fa-eye");
                    }
                }

                function confirmUpdate(formId, actionText) {
                    const form = document.getElementById(formId);
                    Swal.fire({
                        title: 'Are you sure?',
                        text: `Do you want to ${actionText}?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: `Yes, ${actionText}!`
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit(); // Submit the form if confirmed
                        }
                    });
                }

                <?php if (isset($_SESSION['success_message']) && !isset($_GET['alert_shown'])): ?>

                    swal("Success!", "<?php echo addslashes($_SESSION['success_message']); ?>", "success")
                        .then(() => {
                            // Reload the page and append a query parameter to prevent showing the alert again
                            const url = new URL(window.location.href);
                            url.searchParams.set('alert_shown', 'true');
                            window.location.href = url.toString();
                        });

                <?php endif; ?>

                <?php if (isset($_SESSION['error_message']) && !isset($_GET['alert_shown'])): ?>

                    swal("Error!", "<?php echo addslashes($_SESSION['error_message']); ?>", "error")
                        .then(() => {
                            // Reload the page and append a query parameter to prevent showing the alert again
                            const url = new URL(window.location.href);
                            url.searchParams.set('alert_shown', 'true');
                            window.location.href = url.toString();
                        });

                <?php endif; ?>

                function previewImage(event) {
                    const input = event.target;
                    const preview = document.getElementById('profileImagePreview');

                    if (input.files && input.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            preview.src = e.target.result;
                        };
                        reader.readAsDataURL(input.files[0]);
                    }
                }

                const newPassword = document.getElementById('newPassword');
                const confirmNewPassword = document.getElementById('confirmNewPassword');
                const matchMessage = document.getElementById('passwordMatchMessage');

                function checkPasswordMatch() {
                    const newVal = newPassword.value;
                    const confirmVal = confirmNewPassword.value;

                    if (confirmVal === "") {
                        matchMessage.textContent = "";
                        confirmNewPassword.classList.remove("is-valid", "is-invalid");
                        return;
                    }

                    if (newVal === confirmVal) {
                        confirmNewPassword.classList.remove("is-invalid");
                        confirmNewPassword.classList.add("is-valid");
                        newPassword.classList.remove("is-invalid");
                        newPassword.classList.add("is-valid");
                        matchMessage.textContent = "Passwords match.";
                        matchMessage.classList.remove("text-danger");
                        matchMessage.classList.add("text-success");
                    } else {
                        confirmNewPassword.classList.remove("is-valid");
                        confirmNewPassword.classList.add("is-invalid");
                        newPassword.classList.remove("is-valid");
                        newPassword.classList.add("is-invalid");
                        matchMessage.textContent = "Passwords do not match.";
                        matchMessage.classList.remove("text-success");
                        matchMessage.classList.add("text-danger");
                    }
                }

                newPassword.addEventListener('input', checkPasswordMatch);
                confirmNewPassword.addEventListener('input', checkPasswordMatch);

                function resetPasswordFormVisuals() {
                    document.getElementById('changePasswordForm').reset();
                    [newPassword, confirmNewPassword].forEach(input => {
                        input.classList.remove("is-valid", "is-invalid");
                    });
                    matchMessage.textContent = "";
                    matchMessage.classList.remove("text-success", "text-danger");
                }
            </script>
</body>

</html>