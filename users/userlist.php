<?php
session_start();
include_once '../connection/conn.php';
$conn = con();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Helper functions for badge classes
function getRoleBadgeClass($role)
{
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
        u.is_archived,
        d.department_name, 
        g.game_name
    FROM 
        users u
    LEFT JOIN 
        games g ON u.game_id = g.game_id 
    LEFT JOIN 
        departments d ON u.department = d.id  
    WHERE 
        (u.firstname LIKE ? OR 
        u.lastname LIKE ? OR 
        u.email LIKE ?) AND
        u.role NOT IN ('superadmin', 'School Admin') AND 
        u.school_id = ? 
        AND u.id != ? AND 
        (
            (u.role = 'committee' AND g.is_archived = 0 AND d.is_archived = 0) OR
            (u.role = 'department admin' AND d.is_archived = 0)
        )
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

    <style>
        /* Base styles */
        .table {
            margin-bottom: 0;
        }

        .table thead th {
            background-color: #2c3e50;
            color: white;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.9rem;
            padding: 15px;
            border: none;
        }

        .table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-color: #eef2f7;
        }

        .card.box {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .card-body {
            padding: 1.5rem;
        }

        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .wrapper {
                padding: 10px;
            }

            #content {
                margin-left: 0;
                padding: 10px;
            }

            .container {
                padding: 0;
            }

            /* Header and Search Section */
            .d-flex.justify-content-between {
                flex-direction: column;
                gap: 1rem;
                align-items: stretch;
            }

            .btn-primary {
                width: 100%;
                margin-top: 0.5rem;
            }

            /* Table Responsive */
            .table-responsive {
                border: 0;
            }

            .table thead {
                display: none;
            }

            .table tbody tr {
                display: block;
                margin-bottom: 1rem;
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }

            .table tbody td {
                display: flex;
                text-align: left;
                padding: 1rem;
                position: relative;
                border-bottom: 1px solid rgba(0, 0, 0, 0.1);
                align-items: center;
            }

            .table tbody td[data-label="Name"] {
                display: block;
            }

            .table tbody td[data-label="Name"] .d-flex.align-items-center.gap-3 {
                display: flex !important;
                align-items: center !important;
                gap: 1rem !important;
            }

            .table tbody td[data-label="Email"],
            .table tbody td[data-label="Role"],
            .table tbody td[data-label="Department"] {
                justify-content: space-between;
            }

            .table tbody td[data-label="Actions"] {
                justify-content: flex-start;
                padding-left: 1rem;
                border-bottom: none;
            }

            .table tbody td[data-label="Actions"]:before {
                display: none;
            }

            .table tbody td[data-label="Actions"] .d-flex.gap-2 {
                display: flex !important;
                gap: 0.5rem !important;
                width: 100%;
                justify-content: flex-start !important;
            }

            /* Make buttons equal width */
            .table tbody td[data-label="Actions"] .btn {
                flex: 1;
                max-width: 120px;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 0.5rem;
                padding: 0.625rem;
            }

            .table tbody td[data-label="Actions"] .btn i {
                margin: 0;
            }

            /* Adjust spacing for the action buttons container */
            .d-flex.gap-2.justify-content-center {
                margin: 0;
                padding: 0;
            }

            .table tbody td:before {
                content: attr(data-label);
                font-weight: 600;
                color: #2c3e50;
                font-size: 0.85rem;
                text-transform: uppercase;
                min-width: 100px;
            }

            .table tbody td[data-label="Name"]:before {
                margin-bottom: 0.5rem;
                display: block;
            }

            /* User Info Layout */
            .d-flex.align-items-center.gap-3 {
                width: 100%;
            }

            .rounded-circle {
                width: 48px !important;
                height: 48px !important;
                flex-shrink: 0;
            }

            /* Badge Adjustments */
            .badge {
                margin-left: auto;
                padding: 0.5rem 0.75rem;
            }

            /* Action Buttons */
            .btn-group {
                display: flex;
                gap: 0.5rem;
            }

            .btn-sm {
                padding: 0.5rem 0.75rem;
            }

            /* Card Adjustments */
            .card.box {
                margin: 0;
                border-radius: 0;
            }

            .card-body {
                padding: 1rem;
            }

            /* Empty State */
            .text-center.py-5 {
                padding: 2rem 1rem !important;
            }

            .text-center.py-5 i {
                font-size: 2.5rem;
                margin-bottom: 1rem;
            }
        }

        @media (max-width: 576px) {
            .table tbody td {
                padding: 0.875rem;
            }

            .btn-sm {
                padding: 0.4rem 0.6rem;
            }

            .badge {
                font-size: 0.75rem;
            }
        }
    </style>
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
                    <div class="w-100">
                        <h4 class="mb-3">User List</h4>
                        <div class="row g-3">
                            <div class="col-md-6 col-12">
                                <form id="searchForm" method="POST" action="userlist.php">
                                    <div class="input-group">
                                        <span class="input-group-text bg-light border-end-0">
                                            <i class="fas fa-search text-muted"></i>
                                        </span>
                                        <input type="text" class="form-control border-start-0" name="search" id="searchInput" placeholder="Search users..." value="<?php echo isset($_POST['search']) ? $_POST['search'] : ''; ?>">
                                    </div>
                                </form>

                            </div>
                            <div class="col-md-6 col-12">
                                <div class="d-flex gap-2">
                                    <!--<?php if ($role === 'School Admin'): ?>
                                    <select class="form-select" id="departmentFilter">
                                        <option value="">All Departments</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?= htmlspecialchars($dept['id']) ?>" 
                                                <?= ($selected_department_id == $dept['id']) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($dept['department_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <?php endif; ?> -->
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCommitteeModal">
                                        <i class="fas fa-plus"></i> Add User
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <style>
                    @media (max-width: 768px) {
                        .d-flex.justify-content-between {
                            flex-direction: column;
                            gap: 1rem;
                        }

                        .input-group {
                            margin-bottom: 1rem;
                        }

                        .form-select {
                            margin-bottom: 1rem;
                        }

                        .btn-primary {
                            width: 100%;
                            margin-left: 0 !important;
                        }

                        .row.g-3 {
                            margin: 0;
                        }

                        .col-md-6 {
                            padding: 0;
                        }

                        .d-flex.gap-2 {
                            flex-direction: column;
                            gap: 0.5rem !important;
                        }

                        h4 {
                            font-size: 1.25rem;
                            margin-bottom: 1rem !important;
                        }

                        .input-group .form-control {
                            height: 42px;
                        }

                        .input-group-text {
                            background-color: #f8f9fa;
                            border-right: none;
                        }

                        .form-control {
                            border-left: none;
                        }

                        .form-control:focus {
                            box-shadow: none;
                            border-color: #dee2e6;
                        }

                        .form-select {
                            height: 42px;
                        }
                    }
                </style>
            </div>

            <div class="card box">
                <div class="card-body">
                    <div class="btn-group portfolio-filter mb-3 mt-0" role="group" aria-label="Portfolio Filter">
                        <button type="button" class="btn btn-outline-primary active filter-btn" data-category="0">
                            Active
                        </button>
                        <button type="button" class="btn btn-outline-secondary filter-btn" data-category="1">
                            Archived
                        </button>
                    </div>


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
                                                <th class="px-4 py-3">Department</th>
                                                <th class="px-4 py-3 text-center">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if ($users_result && mysqli_num_rows($users_result) > 0) {
                                                while ($row = mysqli_fetch_assoc($users_result)) {
                                                    echo '<tr data-category="' . htmlspecialchars($row['is_archived']) . '">';
                                                    echo '<td class="px-4" data-label="Name">';
                                                    echo '<div class="d-flex align-items-center gap-3">';
                                                    echo '<div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">';
                                                    echo '<i class="fas fa-user text-white"></i>';
                                                    echo '</div>';
                                                    echo '<div>';
                                                    echo '<div class="fw-medium">' . htmlspecialchars($row['firstname'] . ' ' . $row['middleinitial'] . ' ' . $row['lastname']) . '</div>';
                                                    echo '</div>';
                                                    echo '</div>';
                                                    echo '</td>';
                                                    echo '<td class="px-4" data-label="Email">' . htmlspecialchars($row['email']) . '</td>';
                                                    echo '<td class="px-4" data-label="Role">';
                                                    echo '<span class="badge ' . getRoleBadgeClass($row['role']) . '">' . htmlspecialchars($row['role']) . '</span>';
                                                    echo '</td>';
                                                    echo '<td class="px-4" data-label="Department">' . htmlspecialchars($row['department_name'] ?? 'N/A') . '</td>';
                                                    echo '<td class="px-4" data-label="Actions">';
                                                    echo '<div class="d-flex gap-2 justify-content-center">';

                                                    // DROPDOWN
                                                    echo '<div class="dropdown">';
                                                    echo '<button class="btn btn-secondary btn-sm dropdown-toggle shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                            Actions
                                                          </button>';
                                                    echo '<ul class="dropdown-menu" style="z-index: 1050; padding: 4px 8px; line-height: 1.2; min-width: 140px;">';

                                                    // Edit button (only show if not archived)
                                                    if ($row['is_archived'] != 1) {
                                                        echo '<li>';
                                                        echo '<button class="dropdown-item" 
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
                                                                    )">
                                                                    Edit
                                                                  </button>';
                                                        echo '</li>';
                                                    }

                                                    // Delete button
                                                    echo '<li>';
                                                    echo '<button class="dropdown-item" 
                                                                onclick="confirmDelete(\'' . htmlspecialchars($row['id']) . '\')">
                                                                Delete
                                                              </button>';
                                                    echo '</li>';

                                                    // Archive/Unarchive button
                                                    echo '<li>';
                                                    echo '<button type="button"
                                                                class="dropdown-item archive-btn"
                                                                data-id="' . htmlspecialchars($row['id']) . '"
                                                                data-table="users"
                                                                data-operation="' . ($row['is_archived'] == 1 ? 'unarchive' : 'archive') . '">
                                                                ' . ($row['is_archived'] == 1 ? 'Unarchive' : 'Archive') . '
                                                              </button>';
                                                    echo '</li>';

                                                    echo '</ul>';
                                                    echo '</div>';



                                                    echo '</div>';
                                                    echo '</td>';
                                                    echo '</tr>';
                                                }
                                            } else {
                                                echo '<tr>';
                                                echo '<td colspan="5" class="text-center py-5">';
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

                    // Show SweetAlert loading indicator
                    Swal.fire({
                        title: 'Submitting...',
                        text: 'Please wait while we process your request.',
                        allowOutsideClick: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });

                    // Send the data via AJAX
                    fetch(actionUrl, {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            // Close the loading SweetAlert
                            Swal.close();

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
                            // Close the loading SweetAlert
                            Swal.close();

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
            <script src="../archive/js/archive.js"></script>
</body>

</html>