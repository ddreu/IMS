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
        case 'superadmin':
            return 'bg-danger';
        default:
            return 'bg-secondary';
    }
}

// Ensure user is logged in and is superadmin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$searchQuery = '';

if (isset($_POST['search'])) {
    $searchQuery = mysqli_real_escape_string($conn, $_POST['search']);
}

// Modified query to show all users across all schools
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
        u.school_id,
        u.game_id,
        u.image,
        d.department_name,
        s.school_name
    FROM 
        users u
    LEFT JOIN 
        departments d ON u.department = d.id
    LEFT JOIN
        schools s ON u.school_id = s.school_id
    WHERE 
        (u.firstname LIKE ? OR 
        u.lastname LIKE ? OR 
        u.email LIKE ?) 
        AND u.id != ?
";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    die('mysqli_prepare() failed: ' . mysqli_error($conn));
}

// Prepare search parameters
$searchLike = "%$searchQuery%";
mysqli_stmt_bind_param($stmt, "sssi", $searchLike, $searchLike, $searchLike, $user_id);
mysqli_stmt_execute($stmt);
$users_result = mysqli_stmt_get_result($stmt);

// Fetch all schools for the filter
$schools_query = "SELECT school_id, school_name FROM schools WHERE school_id != 0 ORDER BY school_name";
$schools_result = mysqli_query($conn, $schools_query);
$schools = mysqli_fetch_all($schools_result, MYSQLI_ASSOC);

// Initialize variables for success and error messages
$successMessage = '';
$errorMessage = '';

// Check if there's a message to display
if (isset($_SESSION['message']) && isset($_SESSION['message_type'])) {
    $message = $_SESSION['message'];
    $message_type = $_SESSION['message_type'];
    unset($_SESSION['message']);
    unset($_SESSION['message_type']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Super Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="../styles/dashboard.css">
</head>

<body>
    <div class="wrapper">
        <?php
        $current_page = 'userlist';
        include '../navbar/navbar.php';
        include '../super_admin/sa_sidebar.php';
        ?>

        <div class="content ms-5 ms-lg-6 ps-5 p-4">
            <div class="container-fluid mt-4">
                <div class="container-fluid">
                    <?php if (isset($message)): ?>
                        <div class="alert alert-<?php echo $message_type === 'success' ? 'success' : 'danger'; ?> alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($message); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h2 class="h3">User Management</h2>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                            <i class="fas fa-plus me-2"></i>Add User
                        </button>
                    </div>

                    <div class="card shadow-sm mb-4">
                        <div class="card-body">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <form class="d-flex" method="POST">
                                        <input type="text" name="search" class="form-control me-2" placeholder="Search users..." value="<?php echo htmlspecialchars($searchQuery); ?>">
                                        <button type="submit" class="btn btn-outline-primary">Search</button>
                                    </form>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" id="schoolFilter">
                                        <option value="">All Schools</option>
                                        <?php foreach ($schools as $school): ?>
                                            <option value="<?php echo $school['school_id']; ?>">
                                                <?php echo htmlspecialchars($school['school_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select" id="roleFilter">
                                        <option value="">All Roles</option>
                                        <option value="School Admin">School Admin</option>
                                        <option value="Department Admin">Department Admin</option>
                                        <option value="Committee">Committee</option>
                                    </select>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover align-middle mb-0">
                                    <thead class="bg-light">
                                        <tr>
                                            <th class="px-4 py-3">Name</th>
                                            <th class="px-4 py-3">Email</th>
                                            <th class="px-4 py-3">Role</th>
                                            <th class="px-4 py-3">School</th>
                                            <th class="px-4 py-3">Department</th>
                                            <th class="px-4 py-3">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        if ($users_result && mysqli_num_rows($users_result) > 0) {
                                            while ($row = mysqli_fetch_assoc($users_result)) {
                                                echo '<tr data-school-id="' . htmlspecialchars($row['school_id']) . '">';
                                                echo '<td class="px-4">';
                                                echo '<div class="d-flex align-items-center">';
                                                $image_path = (!empty($row['image']) && file_exists("../uploads/users/" . $row['image']))
                                                    ? "../uploads/users/" . $row['image']
                                                    : "../assets/defaults/default-profile.jpg";

                                                echo '<img src="' . $image_path . '" alt="User Image" class="rounded-circle shadow-sm flex-shrink-0" style="width: 45px; height: 45px; object-fit: cover;">';

                                                echo '<div class="ms-3">';
                                                echo '<div class="fw-bold mb-1">' . htmlspecialchars($row['firstname'] . ' ' . $row['middleinitial'] . ' ' . $row['lastname']) . '</div>';
                                                echo '</div>';
                                                echo '</div>';
                                                echo '</td>';
                                                echo '<td class="px-4">' . htmlspecialchars($row['email']) . '</td>';
                                                echo '<td class="px-4"><span class="badge ' . getRoleBadgeClass($row['role']) . '">' . htmlspecialchars($row['role'] ?? 'N/A') . '</span></td>';
                                                echo '<td class="px-4">' . htmlspecialchars($row['school_name'] ?? 'N/A') . '</td>';
                                                echo '<td class="px-4">' . htmlspecialchars($row['department_name'] ?? 'N/A') . '</td>';
                                                echo '<td class="px-4">';
                                                echo '<div class="d-flex gap-2">';
                                                echo '<button type="button" class="btn btn-sm btn-outline-primary" onclick="openUpdateModal(\'' . $row['id'] . '\', \'' .
                                                    $row['firstname'] . '\', \'' .
                                                    $row['lastname'] . '\', \'' .
                                                    $row['middleinitial'] . '\', \'' .
                                                    $row['age'] . '\', \'' .
                                                    $row['gender'] . '\', \'' .
                                                    $row['email'] . '\', \'' .
                                                    $row['role'] . '\', \'' .
                                                    $row['school_id'] . '\', \'' .
                                                    $row['department'] . '\', \'' .
                                                    ($row['game_id'] ?? '') . '\')">';
                                                echo '<i class="fas fa-edit"></i>';
                                                echo '</button>';
                                                echo '<a href="javascript:void(0)" onclick="deleteUser(' . $row['id'] . ', \'' . htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) . '\')" class="btn btn-sm btn-danger">';
                                                echo '<i class="fas fa-trash"></i>';
                                                echo '</a>';
                                                echo '</div>';
                                                echo '</td>';
                                                echo '</tr>';
                                            }
                                        } else {
                                            echo '<tr><td colspan="6" class="text-center py-5">';
                                            echo '<div class="text-muted">';
                                            echo '<i class="fas fa-users fa-3x mb-3 d-block"></i>';
                                            echo '<p>No users found</p>';
                                            echo '</div>';
                                            echo '</td></tr>';
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
    </div>

    <!-- Include modals -->
    <?php include 'admin_addusermodal.php'; ?>
    <?php include 'admin_updateusermodal.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        // Filter functionality
        document.getElementById('schoolFilter').addEventListener('change', filterTable);
        document.getElementById('roleFilter').addEventListener('change', filterTable);

        function filterTable() {
            const schoolFilter = document.getElementById('schoolFilter').value;
            const roleFilter = document.getElementById('roleFilter').value.toLowerCase();
            const rows = document.querySelectorAll('tbody tr');

            rows.forEach(row => {
                const schoolId = row.getAttribute('data-school-id');
                const role = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                const schoolMatch = !schoolFilter || schoolId === schoolFilter;
                const roleMatch = !roleFilter || role.includes(roleFilter);
                row.style.display = schoolMatch && roleMatch ? '' : 'none';
            });
        }

        function deleteUser(userId, userName) {
            Swal.fire({
                title: 'Are you sure?',
                text: `Do you want to delete ${userName}?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
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
                                    showConfirmButton: true
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        location.reload();
                                    }
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
                            console.error('Error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'An error occurred while deleting the user.'
                            });
                        });
                }
            });
        }

        function handleFormSubmit(form, actionUrl) {
            const formData = new FormData(form);
            fetch(actionUrl, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while processing your request');
                });
            return false;
        }
    </script>
</body>

</html>