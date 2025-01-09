<?php
session_start();
include_once '../connection/conn.php';
$conn = con();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Helper functions for badge classes
function getRoleBadgeClass($role) {
    switch ($role) {
        case 'School Admin':
            return 'bg-primary';
        case 'Department Admin':
            return 'bg-success';
        case 'Committee':
            return 'bg-info';
        default:
            return 'bg-secondary';
    }
}

// Get selected department ID from URL if present
$selected_department_id = isset($_GET['selected_department_id']) ? intval($_GET['selected_department_id']) : null;

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$school_id = $_SESSION['school_id'];
$department_id = $_SESSION['department_id']; // Get department_id from session

// If selected_department_id is not set, use the Department Admin's department_id if they are logged in
if ($selected_department_id === null && $role === 'Department Admin') {
    $selected_department_id = $department_id;
}

// Prepare SQL statement to fetch user details
$sql = "
    SELECT 
        u.role, 
        u.email, 
        d.id AS department_id,  
        d.department_name,
        u.school_id
    FROM 
        users u
    LEFT JOIN 
        departments d ON u.department = d.id
    WHERE 
        u.id = ?
";
$stmt = mysqli_prepare($conn, $sql);

if ($stmt === false) {
    die('mysqli_prepare() failed: ' . htmlspecialchars(mysqli_error($conn)));
}

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result->num_rows > 0) {
    $user = mysqli_fetch_assoc($result);
    if (!in_array($user['role'], ['School Admin', 'Department Admin'])) {
        header('Location: 404.php');
        exit();
    }

    $_SESSION['school_id'] = $user['school_id'];
    $_SESSION['department_id'] = $user['department_id'];
    $loggedInSchoolId = $_SESSION['school_id'];
} else {
    header('Location: /IMS/login.php');
    exit();
}

$searchQuery = '';
if (isset($_POST['search'])) {
    $searchQuery = mysqli_real_escape_string($conn, $_POST['search']);
}

$sql = "
    SELECT 
        u.id, 
        u.firstname, 
        u.lastname, 
        u.middleinitial, 
        u.age, 
        u.gender, 
        u.email, 
        u.role,
        u.department,
        u.game_id,
        d.department_name, 
        g.game_name
    FROM 
        users u
    LEFT JOIN 
        Games g ON u.game_id = g.game_id 
    LEFT JOIN 
        departments d ON u.department = d.id  
    WHERE 
        (u.firstname LIKE ? OR 
        u.lastname LIKE ? OR 
        u.email LIKE ?) AND
        u.role NOT IN ('superadmin', 'School Admin') AND 
        u.school_id = ? 
        AND u.id != ?
";

if ($selected_department_id !== null) {
    $sql .= " AND u.department = ?";
}

$sql .= " GROUP BY u.id";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    die('mysqli_prepare() failed: ' . mysqli_error($conn));
}

// Prepare search parameters
$searchLike = "%$searchQuery%";
if ($selected_department_id !== null) {
    mysqli_stmt_bind_param($stmt, "sssiii", $searchLike, $searchLike, $searchLike, $loggedInSchoolId, $user_id, $selected_department_id);
} else {
    mysqli_stmt_bind_param($stmt, "sssii", $searchLike, $searchLike, $searchLike, $loggedInSchoolId, $user_id);
}

mysqli_stmt_execute($stmt);
$users_result = mysqli_stmt_get_result($stmt);

// Fetch games associated with the logged-in user's school
$games = [];
$games_query = "SELECT game_id, game_name FROM games WHERE school_id = ?";
$games_stmt = mysqli_prepare($conn, $games_query);

if ($games_stmt) {
    mysqli_stmt_bind_param($games_stmt, "i", $school_id);
    mysqli_stmt_execute($games_stmt);
    $games_result = mysqli_stmt_get_result($games_stmt);

    if ($games_result && mysqli_num_rows($games_result) > 0) {
        $games = mysqli_fetch_all($games_result, MYSQLI_ASSOC);
    }
    mysqli_stmt_close($games_stmt);
}

// Process the result and group games by user
$users = [];
if ($result && mysqli_num_rows($result) > 0) {
    while ($row = mysqli_fetch_assoc($result)) {
        $user_id_row = $row['id'];
        if (!isset($users[$user_id_row])) {
            $users[$user_id_row] = [
                'id' => $row['id'],
                'firstname' => $row['firstname'] ?? '',
                'lastname' => $row['lastname'] ?? '',
                'middleinitial' => $row['middleinitial'] ?? '',
                'age' => $row['age'] ?? '',
                'gender' => $row['gender'] ?? '',
                'email' => $row['email'] ?? '',
                'role' => $row['role'] ?? '',
                'department' => $row['department_name'] ?? '',
                'games' => []
            ];
        }
        if (!empty($row['game_name'])) {
            $users[$user_id_row]['games'][] = $row['game_name'];
        }
    }
}

$departments = [];
if ($school_id) {
    $department_result = mysqli_query($conn, "SELECT id, department_name FROM departments WHERE school_id = $school_id");

    if ($department_result) {
        $departments = mysqli_fetch_all($department_result, MYSQLI_ASSOC);
    }
}
include '../navbar/navbar.php';

// Initialize variables for success and error messages
$successMessage = '';
$errorMessage = '';

// Check if there's a message to display
if (isset($_SESSION['message']) && isset($_SESSION['message_type'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    // Clear the session message
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);


?>
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            Swal.fire({
                title: "<?php echo ($message_type === 'success') ? 'Success' : 'Error'; ?>",
                text: "<?php echo $message; ?>",
                icon: "<?php echo $message_type; ?>",
                confirmButtonText: "OK"
            }).then(() => {
                // Optionally, you can add redirection or other logic here after the alert is closed
            });
        });
    </script>
<?php
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User List</title>

    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- Custom CSS -->
    <link rel="stylesheet" href="../styles/dashboard.css"> <!-- Adjust the path if needed -->
</head>

<body>

    <div class="wrapper">
        <?php
        $current_page = 'committeelist';
        if ($role == 'Committee') {
            include '../committee/csidebar.php';
        } else {
            include '../department_admin/sidebar.php'; // fallback for other roles
        }
        ?>
        <!-- Page Content -->
        <div id="content">


            <!-- Success and Error Messages -->
            <div class="container mt-4">
                <?php
                if (isset($_SESSION['success_message'])) {
                    echo "<script>
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: '{$_SESSION['success_message']}',
            confirmButtonText: 'OK'
        });
    </script>";
                    unset($_SESSION['success_message']);
                }

                if (isset($_SESSION['error_message'])) {
                    echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Error!',
            text: '{$_SESSION['error_message']}',
            confirmButtonText: 'OK'
        });
    </script>";
                    unset($_SESSION['error_message']);
                }
                ?>
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="mb-0">User List</h4>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCommitteeModal">
                            <i class="fas fa-plus"></i> Add User
                        </button>
                    </div>
                </div>
            </div>

            <div class="card box">
                <div class="card-body">
                    <div class="table-responsive">
                        <div class="container-fluid p-0">
                            <div class="card shadow-sm">
                                <div class="card-body p-0">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="px-4 py-3">Name</th>
                                                <th class="px-4 py-3">Email</th>
                                                <th class="px-4 py-3">Role</th>
                                                <th class="px-4 py-3 text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if ($users_result && mysqli_num_rows($users_result) > 0) {
                                                while ($row = mysqli_fetch_assoc($users_result)) {
                                                    echo '<tr>';
                                                    echo '<td class="px-4">';
                                                    echo '<div class="d-flex align-items-center gap-3">';
                                                    echo '<div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">';
                                                    echo '<i class="fas fa-user text-white"></i>';
                                                    echo '</div>';
                                                    echo '<div>';
                                                    echo '<div class="fw-medium">' . htmlspecialchars($row['firstname'] . ' ' . $row['middleinitial'] . ' ' . $row['lastname']) . '</div>';
                                                    echo '<small class="text-muted">' . htmlspecialchars($row['department_name'] ?? 'No Department') . '</small>';
                                                    echo '</div>';
                                                    echo '</div>';
                                                    echo '</td>';
                                                    echo '<td class="px-4">' . htmlspecialchars($row['email']) . '</td>';
                                                    // Display role with game name for committee members
                                                    $roleDisplay = $row['role'];
                                                    if ($row['role'] === 'Committee' && !empty($row['game_name'])) {
                                                        $roleDisplay = htmlspecialchars($row['game_name']) . ' ' . $roleDisplay;
                                                    }
                                                    echo '<td class="px-4"><span class="badge ' . getRoleBadgeClass($row['role']) . '">' . $roleDisplay . '</span></td>';
                                                    echo '<td class="px-4">';
                                                    echo '<div class="d-flex gap-2 justify-content-center">';
                                                    
                                                    // Edit button
                                                    echo '<button type="button" 
                                                            class="btn btn-primary btn-sm shadow-sm " style="width: 38px; height: 32px; padding: 6px 0;"
                                                            onclick="openUpdateModal(
                                                                \'' . htmlspecialchars($row['id']) . '\',
                                                                \'' . htmlspecialchars($row['firstname']) . '\',
                                                                \'' . htmlspecialchars($row['lastname']) . '\',
                                                                \'' . htmlspecialchars($row['middleinitial']) . '\',
                                                                \'' . htmlspecialchars($row['age']) . '\',
                                                                \'' . htmlspecialchars($row['gender']) . '\',
                                                                \'' . htmlspecialchars($row['email']) . '\',
                                                                \'' . htmlspecialchars($row['role']) . '\',
                                                                \'' . htmlspecialchars($row['game_id'] ?? '') . '\',
                                                                \'' . htmlspecialchars($row['department'] ?? '') . '\'
                                                            )"
                                                            title="Edit">';
                                                    echo '<i class="fas fa-edit"></i>';
                                                    echo '</button>';

                                                    // Delete button
                                                    echo '<button type="button" 
                                                            class="btn btn-danger btn-sm shadow-sm" style="width: 38px; height: 32px; padding: 6px 0;"
                                                            onclick="confirmDelete(\'' . htmlspecialchars($row['id']) . '\')"
                                                            title="Delete">';
                                                    echo '<i class="fas fa-trash"></i>';
                                                    echo '</button>';
                                                    
                                                    echo '</div>';
                                                    echo '</td>';
                                                    echo '</tr>';
                                                }
                                            } else {
                                                echo '<tr>';
                                                echo '<td colspan="4" class="text-center py-5">';
                                                echo '<div class="text-muted">';
                                                echo '<i class="fas fa-users fa-3x mb-3 d-block"></i>';
                                                echo '<p class="mb-0">No users found</p>';
                                                echo '</div>';
                                                echo '</td>';
                                                echo '</tr>';
                                            }
                                            ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <?php include 'addusermodal.php'; ?>
            <?php include 'updateusermodal.php'; ?>


            <!-- Bootstrap JS CDN -->
            <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

            <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
            <!-- Include SweetAlert library -->
            <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
            <!-- Custom JS -->
            <script src="../utils.js"></script>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Add event listener for the role selection in the add user modal
                    const addRoleSelect = document.getElementById("role");
                    if (addRoleSelect) {
                        addRoleSelect.addEventListener("change", function() {
                            toggleAssignGameFieldForAddUser(this.value);
                        });
                    }

                    // Add event listener for update role
                    const updateRoleSelect = document.getElementById("update_role");
                    if (updateRoleSelect) {
                        updateRoleSelect.addEventListener("change", function() {
                            toggleAssignGameField(this.value);
                        });
                    }
                });

                // Add event listeners for both modals
                const addForm = document.getElementById('addUserForm');
                const updateForm = document.getElementById('updateCommitteeForm');

                if (addForm) {
                    addForm.addEventListener('submit', function(e) {
                        e.preventDefault(); // Prevent the default form submission
                        if (validateAge(addForm)) { // Check if the age is valid
                            handleFormSubmit(addForm, 'adduser.php');
                        }
                    });
                }

                if (updateForm) {
                    updateForm.addEventListener('submit', function(e) {
                        e.preventDefault(); // Prevent the default form submission
                        if (validateAge(updateForm)) { // Check if the age is valid
                            handleFormSubmit(updateForm, 'update_user.php');
                        }
                    });
                }

                function validateAge(form) {
                    const ageField = form.querySelector('[name="age"], #update_age'); // Select by name or id for update modal
                    if (ageField) {
                        const age = parseInt(ageField.value, 10); // Ensure we get the number value
                        if (age < 18) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Age Restriction',
                                text: 'Age must be 18 or older.',
                                confirmButtonText: 'OK'
                            });
                            return false; // Prevent form submission if age is invalid
                        }
                    } else {
                        console.error('Age field not found!');
                    }
                    return true; // Allow form submission if age is valid
                }

                function handleFormSubmit(form, actionUrl) {
                    const formData = new FormData(form);

                    // Send the data via AJAX
                    fetch(actionUrl, {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.status === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: data.message,
                                showConfirmButton: true
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Close any open modals
                                    const modals = document.querySelectorAll('.modal');
                                    modals.forEach(modal => {
                                        const modalInstance = bootstrap.Modal.getInstance(modal);
                                        if (modalInstance) {
                                            modalInstance.hide();
                                        }
                                    });
                                    
                                    // Reset the form
                                    form.reset();
                                    
                                    // Reload the page to show updated data
                                    window.location.reload();
                                }
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: data.message || 'An error occurred',
                                confirmButtonText: 'OK'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error!',
                            text: 'An unexpected error occurred. Please try again.',
                            confirmButtonText: 'OK'
                        });
                    });
                }

                function toggleAssignGameFieldForAddUser(role) {
                    const assignGameDiv = document.getElementById('add_assignGameDiv');
                    if (role === "Committee") {
                        assignGameDiv.style.display = "block";
                    } else {
                        assignGameDiv.style.display = "none";
                        document.getElementById('assign_game').value = "";
                    }
                }

                function toggleAssignGameField(role) {
                    const assignGameDiv = document.getElementById('update_assignGameDiv');
                    if (role === "Committee") {
                        assignGameDiv.style.display = "block"; // Show the assign game field for update
                    } else {
                        assignGameDiv.style.display = "none"; // Hide the assign game field
                        document.getElementById('update_assign_game').value = ""; // Clear value if not a committee
                    }
                }

                function confirmDelete(userId) {
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "Once deleted, you will not be able to recover this user's data!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, delete it!',
                        cancelButtonText: 'No, keep it'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Use AJAX instead of form submission
                            const formData = new FormData();
                            formData.append('user_id', userId);

                            fetch('delete_user.php', {
                                method: 'POST',
                                body: formData
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.status === 'success') {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Deleted!',
                                        text: data.message,
                                        showConfirmButton: false,
                                        timer: 1500
                                    }).then(() => {
                                        // Reload the page after successful deletion
                                        window.location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error!',
                                        text: data.message
                                    });
                                }
                            })
                            .catch(error => {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: 'An error occurred while deleting the user.'
                                });
                            });
                        } else {
                            Swal.fire('Cancelled', 'User data is safe!', 'info');
                        }
                    });
                }

                function openUpdateModal(userId, firstname, lastname, middleinitial, age, gender, email, role, games, department) {
                    document.getElementById('update_user_id').value = userId;
                    document.getElementById('update_firstname').value = firstname;
                    document.getElementById('update_lastname').value = lastname;
                    document.getElementById('update_middleinitial').value = middleinitial;
                    document.getElementById('update_age').value = age;
                    document.getElementById('update_gender').value = gender;
                    document.getElementById('update_email').value = email;
                    document.getElementById('update_role').value = role;

                    // Prefill the game selection
                    const updateGames = document.getElementById('update_assign_game');
                    updateGames.value = games ? games : ""; // Ensure the game is set if provided

                    // Prefill department selection
                    document.getElementById('update_dept_level').value = department; // Set department

                    // Show/hide assign game based on role
                    toggleAssignGameField(role);

                    // Show the modal (Bootstrap modal)
                    const updateModal = new bootstrap.Modal(document.getElementById('updateCommitteeModal'));
                    updateModal.show();
                }

                // Function to confirm update using SweetAlert
                function confirmUpdate() {
                    return Swal.fire({
                        title: 'Are you sure?',
                        text: 'Do you really want to update this committee member\'s details?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, update it!',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Grab the form data and trigger the AJAX form submission
                            const form = document.getElementById('updateCommitteeForm');
                            handleFormSubmit(form, 'update_user.php');
                        }
                    });
                }

                // Event listener for update confirmation
                document.getElementById('confirmUpdateBtn').addEventListener('click', function(event) {
                    event.preventDefault(); // Prevent the default form submission
                    confirmUpdate(); // Call the SweetAlert confirmation function
                });
            </script>

</body>

</html>