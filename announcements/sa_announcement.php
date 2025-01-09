<?php
// Check if the user is logged in
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php'); // Redirect to login page if not logged in
    exit();
}

// Fetch the logged-in user's information
$user_id = $_SESSION['user_id'];
$email = $_SESSION['email'];
$role = $_SESSION['role'];

include_once '../connection/conn.php';
$conn = con(); // Initialize the database connection

$sql = "SELECT role, school_id FROM users WHERE id = ?";

$stmt_select = mysqli_prepare($conn, $sql);

if ($stmt_select === false) {
    die('mysqli_prepare() failed: ' . htmlspecialchars(mysqli_error($conn)));
}

mysqli_stmt_bind_param($stmt_select, "i", $user_id);
mysqli_stmt_execute($stmt_select);
$result = mysqli_stmt_get_result($stmt_select);

if (mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);

    // Check if the logged-in user is a super admin
    if ($user['role'] !== 'superadmin') {
        header('Location: 404.php'); // Redirect if the role is not super admin
        exit();
    }
}

$status = isset($_GET['status']) ? $_GET['status'] : '';
$message = isset($_GET['message']) ? $_GET['message'] : '';

// Fetch announcements excluding those with department_id and school_id both 0
$announcements_query = "
    SELECT a.*, d.department_name, s.school_name, a.image 
    FROM announcement a 
    LEFT JOIN departments d ON a.department_id = d.id 
    LEFT JOIN schools s ON a.school_id = s.school_id 
    WHERE NOT (a.department_id = 0 AND a.school_id = 0) 
    ORDER BY a.id DESC
";

$stmt_announcements = mysqli_prepare($conn, $announcements_query);

if ($stmt_announcements === false) {
    die('mysqli_prepare() failed: ' . htmlspecialchars(mysqli_error($conn)));
}

mysqli_stmt_execute($stmt_announcements);
$announcements_result = mysqli_stmt_get_result($stmt_announcements);

if (!$announcements_result) {
    die('Query failed: ' . mysqli_error($conn));
}

// Fetch departments only if school_id is available
$departments = [];
if ($user['school_id']) {
    $department_result = mysqli_query($conn, "SELECT id, department_name FROM departments WHERE school_id = " . $user['school_id']);

    // If the query is successful, fetch departments
    if ($department_result) {
        $departments = mysqli_fetch_all($department_result, MYSQLI_ASSOC);
    }
}
// Fetching System Announcements (filtered by department_id and school_id = 0)
$system_announcements_result = mysqli_query($conn, "
    SELECT 
        id AS announcement_id,
        title,
        message,
        image,
        created_at
    FROM 
        announcement
    WHERE 
        department_id = 0 AND school_id = 0
");

// Check for errors in the query
if (!$system_announcements_result) {
    die("Database query failed: " . mysqli_error($conn));
}


include '../navbar/navbar.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcements</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/ad90023682.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../super_admin/sastyles.css">
    <style>
        .announcement-table {
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }

        .announcement-table .table {
            margin-bottom: 0;
        }

        .announcement-table thead th {
            background-color: #2c3e50;
            color: white;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.9rem;
            padding: 15px;
            border: none;
        }

        .announcement-table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-color: #eef2f7;
        }

        .announcement-table .btn-group {
            display: flex;
            gap: 5px;
            justify-content: center;
        }

        .announcement-table .btn {
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }

        .announcement-table .btn:hover {
            transform: translateY(-2px);
        }

        .announcement-table .btn-info {
            background-color: #3498db;
            border-color: #3498db;
            color: white;
        }

        .announcement-table .btn-warning {
            background-color: #f1c40f;
            border-color: #f1c40f;
            color: white;
        }

        .announcement-table .btn-danger {
            background-color: #e74c3c;
            border-color: #e74c3c;
        }

        .announcement-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        .announcement-table .title-cell {
            font-weight: 500;
            color: #2c3e50;
            max-width: 400px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .card.box {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .card-body {
            padding: 1.5rem;
        }
    </style>
    <?php
    $current_page = 'dashboard';

    include '../super_admin/sa_sidebar.php';
    ?>
    <!-- Page Header and Action Button -->
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>List of Announcements</h2>
            <button type="button" class="btn btn-primary"
                onclick="window.location.href='announcement_details.php';">
                <i class="fas fa-plus"></i> Add Announcement
            </button>
        </div>
    </div>
    <div class="content container-fluid">
        <div class="row justify-content-center">
            <div class="col-12 col-md-11 col-lg-10">
                <div class="card box">
                    <div class="card-body">
                        <div class="mb-3">
                            <div>
                                <button class="btn btn-outline-primary" id="school_announcement" onclick="toggleTables('school')" type="button">School Announcements</button>
                                <button class="btn btn-outline-primary" id="system_announcement" onclick="toggleTables('system')" type="button">System Announcements</button>
                            </div>
                        </div>

                        <!-- School Announcements Table -->
                        <div class="table-responsive" id="schoolTable">
                            <div class="container-fluid p-0">
                                <div class="card shadow-sm">
                                    <div class="card-body p-0">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th class="px-4 py-3" style="width: 30%">Title</th>
                                                    <th class="px-4 py-3" style="width: 30%">School</th>
                                                    <th class="px-4 py-3" style="width: 20%">Department</th>
                                                    <th class="px-4 py-3 text-center" style="width: 20%">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (mysqli_num_rows($announcements_result) > 0): ?>
                                                    <?php while ($row = mysqli_fetch_assoc($announcements_result)): ?>
                                                        <tr>
                                                            <td class="px-4 text-break">
                                                                <div class="d-flex align-items-center gap-3">
                                                                    <?php if (!empty($row["image"])): ?>
                                                                        <div style="width: 40px; height: 40px; overflow: hidden;" class="rounded-circle bg-light d-flex align-items-center justify-content-center">
                                                                            <img src="<?= htmlspecialchars($row["image"]) ?>" alt="Announcement Image" class="img-fluid" style="object-fit: cover; width: 100%; height: 100%;">
                                                                        </div>
                                                                    <?php else: ?>
                                                                        <div style="width: 40px; height: 40px;" class="rounded-circle bg-secondary d-flex align-items-center justify-content-center">
                                                                            <i class="fas fa-bullhorn text-white"></i>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <div>
                                                                        <div class="fw-medium"><?= htmlspecialchars($row["title"]) ?></div>
                                                                        <small class="text-muted"><?= date('M d, Y', strtotime($row["created_at"])) ?></small>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="px-4">
                                                                <?= htmlspecialchars($row["school_name"] ?? 'N/A') ?>
                                                            </td>
                                                            <td class="px-4">
                                                                <?= htmlspecialchars($row["department_name"] ?? 'All Departments') ?>
                                                            </td>
                                                            <td class="px-4">
                                                                <div class="d-flex gap-2 justify-content-center">
                                                                    <button class="btn btn-info btn-sm text-white shadow-sm view-btn"
                                                                        onclick="window.location.href='announcement_details.php?id=<?= htmlspecialchars($row['id']) ?>'"
                                                                        title="View">
                                                                        <i class="fas fa-eye"></i>
                                                                    </button>
                                                                    <button class="btn btn-primary btn-sm shadow-sm edit-btn"
                                                                        onclick="window.location.href='announcement_details.php?id=<?= htmlspecialchars($row['id']) ?>'"
                                                                        title="Edit">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <button class="btn btn-danger btn-sm shadow-sm delete-btn"
                                                                        data-id="<?= htmlspecialchars($row['id']) ?>"
                                                                        title="Delete"
                                                                        onclick="confirmDelete(event, <?= htmlspecialchars($row['id']) ?>)">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>
                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="4" class="text-center py-5">
                                                            <div class="text-muted">
                                                                <i class="fas fa-bullhorn fa-3x mb-3 d-block"></i>
                                                                <p class="mb-0">No Announcements Found</p>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- System Announcements Table -->
                        <div class="table-responsive hidden" id="systemTable" style="display: none;">
                            <div class="container-fluid p-0">
                                <div class="card shadow-sm">
                                    <div class="card-body p-0">
                                        <table class="table table-hover align-middle mb-0">
                                            <thead class="bg-light">
                                                <tr>
                                                    <th class="px-4 py-3" style="width: 70%">Title</th>
                                                    <th class="px-4 py-3 text-center" style="width: 30%">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (mysqli_num_rows($system_announcements_result) > 0): ?>
                                                    <?php while ($row = mysqli_fetch_assoc($system_announcements_result)): ?>
                                                        <tr>
                                                            <td class="px-4 text-break">
                                                                <div class="d-flex align-items-center gap-3">
                                                                    <?php if (!empty($row["image"])): ?>
                                                                        <div style="width: 40px; height: 40px; overflow: hidden;" class="rounded-circle bg-light d-flex align-items-center justify-content-center">
                                                                            <img src="<?= htmlspecialchars($row["image"]) ?>" alt="Announcement Image" class="img-fluid" style="object-fit: cover; width: 100%; height: 100%;">
                                                                        </div>
                                                                    <?php else: ?>
                                                                        <div style="width: 40px; height: 40px;" class="rounded-circle bg-secondary d-flex align-items-center justify-content-center">
                                                                            <i class="fas fa-bullhorn text-white"></i>
                                                                        </div>
                                                                    <?php endif; ?>
                                                                    <div>
                                                                        <div class="fw-medium"><?= htmlspecialchars($row["title"]) ?></div>
                                                                        <small class="text-muted"><?= date('M d, Y', strtotime($row["created_at"])) ?></small>
                                                                    </div>
                                                                </div>
                                                            </td>
                                                            <td class="px-4">
                                                                <div class="d-flex gap-2 justify-content-center">
                                                                    <button class="btn btn-info btn-sm text-white shadow-sm view-btn"
                                                                        onclick="window.location.href='announcement_details.php?id=<?= htmlspecialchars($row['announcement_id']) ?>'"
                                                                        title="View">
                                                                        <i class="fas fa-eye"></i>
                                                                    </button>
                                                                    <button class="btn btn-primary btn-sm shadow-sm edit-btn"
                                                                        onclick="window.location.href='announcement_details.php?id=<?= htmlspecialchars($row['announcement_id']) ?>'"
                                                                        title="Edit">
                                                                        <i class="fas fa-edit"></i>
                                                                    </button>
                                                                    <button class="btn btn-danger btn-sm shadow-sm delete-btn"
                                                                        data-id="<?= htmlspecialchars($row['announcement_id']) ?>"
                                                                        title="Delete"
                                                                        onclick="confirmDelete(event, <?= htmlspecialchars($row['announcement_id']) ?>)">
                                                                        <i class="fas fa-trash"></i>
                                                                    </button>

                                                                </div>
                                                            </td>
                                                        </tr>
                                                    <?php endwhile; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="2" class="text-center py-5">
                                                            <div class="text-muted">
                                                                <i class="fas fa-bullhorn fa-3x mb-3 d-block"></i>
                                                                <p class="mb-0">No Announcements Found</p>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>


                    </div>
                </div>
            </div>
        </div>


        <script>
            document.addEventListener('DOMContentLoaded', function() {
                <?php if ($status === 'success'): ?>
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: '<?php echo $message; ?>',
                        confirmButtonText: 'OK'
                    });
                <?php elseif ($status === 'error'): ?>
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: '<?php echo $message; ?>',
                        confirmButtonText: 'OK'
                    });
                <?php endif; ?>
            });

            function confirmDelete(event, id) {
                event.preventDefault(); // Prevent the default action (e.g., link behavior)

                // Show SweetAlert confirmation dialog
                Swal.fire({
                    title: 'Are you sure?',
                    text: "Do you want to delete this announcement?",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!',
                    cancelButtonText: 'No, cancel!'
                }).then((result) => {
                    // If confirmed, redirect to process_announcement.php with the announcement ID
                    if (result.isConfirmed) {
                        window.location.href = 'process_announcement.php?delete=' + encodeURIComponent(id);
                    }
                });
            }



            function toggleTables(type) {
                const schoolTable = document.getElementById('schoolTable');
                const systemTable = document.getElementById('systemTable');
                const schoolButton = document.getElementById('school_announcement');
                const systemButton = document.getElementById('system_announcement');

                if (type === 'school') {
                    schoolTable.style.display = 'block'; // Show school table
                    systemTable.style.display = 'none'; // Hide system table
                    schoolButton.classList.add('active'); // Add active class to school button
                    systemButton.classList.remove('active'); // Remove active class from system button
                } else {
                    schoolTable.style.display = 'none'; // Hide school table
                    systemTable.style.display = 'block'; // Show system table
                    systemButton.classList.add('active'); // Add active class to system button
                    schoolButton.classList.remove('active'); // Remove active class from school button
                }
            }

            // Initialize the table visibility based on the default selected button
            document.addEventListener('DOMContentLoaded', function() {
                toggleTables('school'); // Show school table by default
            });
        </script>
        </body>

</html>