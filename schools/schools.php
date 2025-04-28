<?php
include_once '../connection/conn.php';
$conn = con();
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    // Redirect to login page if session is not active or role is not superadmin
    header("Location: ../login.php");
    exit(); // Ensure no further code is executed
}

$departments = [];
$query = "SELECT id, department_name, school_id FROM departments WHERE is_archived = 0";
$result = $conn->query($query);
while ($dept = $result->fetch_assoc()) {
    $departments[] = $dept;
}

$games = [];
$query = "SELECT game_id, game_name, school_id FROM games WHERE is_archived = 0";
$result = $conn->query($query);
while ($game = $result->fetch_assoc()) {
    $games[] = $game;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../super_admin/sastyles.css">
</head>

<body>

    <?php
    $current_page = 'schools';
    include '../navbar/navbar.php';
    include '../super_admin/sa_sidebar.php';
    ?>
    <!-- Main Content -->
    <div class="main-content">
        <header class="header">
            <h1>Registered Schools</h1>
        </header>
        <div class="container mt-4">
            <div class="d-flex justify-content-end mb-3">
                <a href="register_school.php" class="btn btn-success">
                    <i class="fas fa-plus"></i> Register School
                </a>

            </div>
            <div class="table-responsive">
                <table class="table table-bordered table-striped">
                    <thead class="table-dark">
                        <tr>
                            <th>Logo</th>
                            <th>School Name</th>
                            <th>School Code</th>
                            <th>Address</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $sql = "SELECT school_id, school_name, school_code, address, logo FROM schools WHERE school_id != 0";
                        $result = $conn->query($sql);
                        if ($result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td class='text-center' style='width: 100px;'>";
                                if (!empty($row['logo']) && file_exists("../uploads/logos/" . $row['logo'])) {
                                    echo "<img src='../uploads/logos/" . $row['logo'] . "' alt='School Logo' class='img-fluid' style='max-height: 50px;'>";
                                } else {
                                    echo "<i class='fas fa-school fa-2x text-secondary'></i>";
                                }
                                echo "</td>";
                                echo "<td>" . htmlspecialchars($row['school_name']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['school_code']) . "</td>";
                                echo "<td>" . htmlspecialchars($row['address']) . "</td>";
                                echo "<td>
    <div class='dropdown'>
        <button class='btn btn-secondary btn-sm dropdown-toggle' type='button' data-bs-toggle='dropdown' aria-expanded='false'>
            Actions
        </button>
        <ul class='dropdown-menu'>
            <li>
                <button class='dropdown-item' 
                    data-bs-toggle='modal' 
                    data-bs-target='#openSchoolModal'
                    data-school-id='{$row['school_id']}'>
                    Open School
                </button>
            </li>
            <li>
                <button class='dropdown-item edit-btn' data-id='{$row['school_id']}'>
                    Edit
                </button>
            </li>
            <li>
                <a class='dropdown-item text-danger' href='javascript:void(0)' onclick='deleteSchool({$row['school_id']}, \"" . htmlspecialchars($row['school_name']) . "\")'>
                    Delete
                </a>
            </li>
        </ul>
    </div>
</td>";

                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' class='text-center'>No schools registered yet.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Open School Modal -->
    <div class="modal fade" id="openSchoolModal" tabindex="-1" aria-labelledby="openSchoolModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="openSchoolModalLabel">Open School</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="openSchoolForm" method="POST" action="school-dashboard-process.php">
                        <input type="hidden" id="selectedSchoolId" name="school_id">

                        <!-- Dashboard Type -->
                        <div class="mb-3">
                            <label for="dashboardType" class="form-label">Select Dashboard:</label>
                            <select class="form-select" id="dashboardType" name="dashboard_type" required>
                                <option value="">Select Type</option>
                                <option value="school_admin">School Admin</option>
                                <option value="dept_admin">Department Admin</option>
                                <option value="committee">Committee</option>
                            </select>
                        </div>

                        <!-- Department Selection (for Dept Admin) -->
                        <div class="mb-3 d-none" id="departmentSelection">
                            <label for="department" class="form-label">Select Department:</label>
                            <select class="form-select" id="department" name="department_id">
                                <!-- Options will be populated by JS -->
                            </select>
                        </div>

                        <!-- Game Selection (for Committee) -->
                        <div class="mb-3 d-none" id="gameSelection">
                            <label for="game" class="form-label">Select Game:</label>
                            <select class="form-select" id="game" name="game_id">
                                <!-- Options will be populated by JS -->
                            </select>
                        </div>

                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" id="openDashboardBtn" class="btn btn-primary">
                                Open Dashboard
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>




    <?php if (isset($_SESSION['error_message'])): ?>
        <script>
            // Show SweetAlert error message
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: '<?php echo $_SESSION['error_message']; ?>'
            });
        </script>
        <?php unset($_SESSION['error_message']); // Clear session message 
        ?>
    <?php endif; ?>


    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const editButtons = document.querySelectorAll('.edit-btn');

            // Edit button click handler
            editButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const schoolId = button.getAttribute('data-id'); // Get school ID
                    window.location.href = `update_school.php?school_id=${schoolId}`; // Redirect to edit page
                });
            });

        });
    </script>

    <script>
        function deleteSchool(schoolId, schoolName) {
            Swal.fire({
                title: 'Are you sure?',
                text: `Do you want to delete ${schoolName}? This will also delete all associated users and departments.`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Yes, delete it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    const formData = new FormData();
                    formData.append('school_id', schoolId);

                    fetch('delete_school.php', {
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
                                text: 'An error occurred while deleting the school.'
                            });
                        });
                }
            });
        }
    </script>
    <script>
        const departments = <?php echo json_encode($departments); ?>;
        const games = <?php echo json_encode($games); ?>;
    </script>
    <script src="js/schools.js"></script>
</body>

</html>