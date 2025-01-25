<?php
session_start();
include_once '../connection/conn.php';
include '../user_logs/logger.php';
$conn = con();

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
$game_id = $_SESSION['game_id'];
$game_name = $_SESSION['game_name'];

// Fetch the current hashed password
$sql = "SELECT password FROM Users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $hashed_password = $row['password'];
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

    // Basic validation for fields
    if (empty($firstname) || empty($lastname) || empty($age) || empty($gender) || empty($email)) {
        $_SESSION['error_message'] = "All required fields must be filled out.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $_SESSION['error_message'] = "Invalid email format.";
    } elseif (!is_numeric($age) || $age < 0) {
        $_SESSION['error_message'] = "Age must be a valid number.";
    } else {
        // Fetch current user details for comparison
        $fetch_sql = "SELECT firstname, lastname, middleinitial, age, gender, email FROM Users WHERE id = ?";
        $stmt_fetch = $conn->prepare($fetch_sql);
        $stmt_fetch->bind_param("i", $user_id);
        $stmt_fetch->execute();
        $current_data = $stmt_fetch->get_result()->fetch_assoc();
        $stmt_fetch->close();

        // Update user profile details if validation passes
        $update_profile_sql = "UPDATE Users SET firstname = ?, lastname = ?, middleinitial = ?, age = ?, gender = ?, email = ? WHERE id = ?";
        $stmt_update_profile = $conn->prepare($update_profile_sql);
        $stmt_update_profile->bind_param("ssssisi", $firstname, $lastname, $middleinitial, $age, $gender, $email, $user_id);

        if ($stmt_update_profile->execute()) {
            // Update session variables
            $_SESSION['firstname'] = $firstname;
            $_SESSION['lastname'] = $lastname;
            $_SESSION['middleinitial'] = $middleinitial;
            $_SESSION['age'] = $age;
            $_SESSION['gender'] = $gender;
            $_SESSION['email'] = $email;

            $_SESSION['success_message'] = "Profile updated successfully.";

            // Prepare data for logging
            $new_data = [
                'firstname' => $firstname,
                'lastname' => $lastname,
                'middleinitial' => $middleinitial,
                'age' => $age,
                'gender' => $gender,
                'email' => $email
            ];
            $description = "Updated user profile details.";

            // Log the action
            logUserAction(
                $conn,
                $user_id,
                'Profile',
                'UPDATE',
                $user_id,
                $description
            );
        } else {
            $_SESSION['error_message'] = "Error updating profile: " . $stmt_update_profile->error;
        }
        $stmt_update_profile->close();
    }
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
</head>

<body>
    <nav>
        <?php
        include '../navbar/navbar.php';

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
            <form id="updateUserInfoForm" method="POST" action="profile_settings.php" onsubmit="event.preventDefault(); confirmUpdate('updateUserInfoForm', 'update your profile');">
                <input type="hidden" name="update_profile" value="1"> <!-- Add this line -->

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="firstname" class="form-label">First Name:</label>
                        <input type="text" name="firstname" class="form-control" value="<?php echo htmlspecialchars($firstname); ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label for="lastname" class="form-label">Last Name:</label>
                        <input type="text" name="lastname" class="form-control" value="<?php echo htmlspecialchars($lastname); ?>" required>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="middleinitial" class="form-label">M.I.:</label>
                        <input type="text" name="middleinitial" class="form-control" value="<?php echo htmlspecialchars($middleinitial); ?>" maxlength="2">
                    </div>
                    <div class="col-md-4">
                        <label for="age" class="form-label">Age:</label>
                        <input type="number" name="age" class="form-control" value="<?php echo htmlspecialchars($age); ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label for="gender" class="form-label">Gender:</label>
                        <select name="gender" class="form-select" required>
                            <option value="Male" <?php if ($gender == 'Male') echo 'selected'; ?>>Male</option>
                            <option value="Female" <?php if ($gender == 'Female') echo 'selected'; ?>>Female</option>
                        </select>
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">Email:</label>
                    <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($email); ?>" required>
                </div>

                <?php if ($role !== 'School Admin'): ?>
                    <!-- Role Selection -->
                    <div class="mb-3">
                        <label for="role" class="form-label">Role:</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($role); ?>" disabled>
                    </div>

                    <!-- Department Selection -->
                    <div class="mb-3">
                        <label for="department" class="form-label">Department:</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($department_name); ?>" disabled>
                    </div>

                    <!-- Game Selection -->
                    <div class="mb-3">
                        <label for="game" class="form-label">Game:</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($game_name); ?>" disabled>
                    </div>
                <?php endif; ?>

                <!-- Current Password for Profile Update -->
                <div class="mb-3">
                    <label for="current_password" class="form-label">Current Password:</label>
                    <div class="input-group">
                        <input type="password" name="current_password" class="form-control" required id="currentPassword">
                        <span class="input-group-text">
                            <i class="fas fa-eye" id="toggleCurrentPassword" onclick="togglePasswordVisibility('currentPassword', 'toggleCurrentPassword')"></i>
                        </span>
                    </div>
                </div>

                <div class="mb-3">
                    <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#changePasswordModal">Change Password</button>
                    <button type="submit" class="btn btn-success">Update Profile</button>
                </div>
            </form>



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
                                    $('#changePasswordModal').modal('hide');
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
            </script>
</body>

</html>