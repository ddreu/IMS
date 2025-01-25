<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Fetch the logged-in user's information
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$department_id = $_SESSION['department_id'];
$school_id = $_SESSION['school_id'];
$department_name = $_SESSION['department_name'];

// Get school information
$school_query = "SELECT school_name, school_code FROM schools WHERE school_id = ?";
$stmt = mysqli_prepare($conn, $school_query);
mysqli_stmt_bind_param($stmt, "i", $school_id);
mysqli_stmt_execute($stmt);
$school_result = mysqli_stmt_get_result($stmt);
$school_info = mysqli_fetch_assoc($school_result);

// Get total users by role
$roles_query = "SELECT role, COUNT(*) as count FROM users WHERE school_id = ? GROUP BY role";
$stmt = mysqli_prepare($conn, $roles_query);
mysqli_stmt_bind_param($stmt, "i", $school_id);
mysqli_stmt_execute($stmt);
$roles_result = mysqli_stmt_get_result($stmt);
$roles_distribution = [];
while($row = mysqli_fetch_assoc($roles_result)) {
    $roles_distribution[$row['role']] = $row['count'];
}

// Get total departments
$dept_query = "SELECT COUNT(*) as dept_count FROM departments WHERE school_id = ?";
$stmt = mysqli_prepare($conn, $dept_query);
mysqli_stmt_bind_param($stmt, "i", $school_id);
mysqli_stmt_execute($stmt);
$dept_result = mysqli_stmt_get_result($stmt);
$dept_count = mysqli_fetch_assoc($dept_result)['dept_count'];

// Get total users
$users_query = "SELECT COUNT(*) as user_count FROM users WHERE school_id = ?";
$stmt = mysqli_prepare($conn, $users_query);
mysqli_stmt_bind_param($stmt, "i", $school_id);
mysqli_stmt_execute($stmt);
$users_result = mysqli_stmt_get_result($stmt);
$total_users = mysqli_fetch_assoc($users_result)['user_count'];

// Get total games
$games_query = "SELECT COUNT(*) as game_count FROM games WHERE school_id = ?";
$stmt = mysqli_prepare($conn, $games_query);
mysqli_stmt_bind_param($stmt, "i", $school_id);
mysqli_stmt_execute($stmt);
$games_result = mysqli_stmt_get_result($stmt);
$total_games = mysqli_fetch_assoc($games_result)['game_count'];

// Get total announcements
$announcement_query = "SELECT COUNT(*) as announcement_count FROM announcement WHERE school_id = ?";
$stmt = mysqli_prepare($conn, $announcement_query);
mysqli_stmt_bind_param($stmt, "i", $school_id);
mysqli_stmt_execute($stmt);
$announcement_result = mysqli_stmt_get_result($stmt);
$total_announcements = mysqli_fetch_assoc($announcement_result)['announcement_count'];

// Get recent announcements
$recent_announcements_query = "SELECT * FROM announcement WHERE school_id = ? ORDER BY created_at DESC LIMIT 5";
$stmt = mysqli_prepare($conn, $recent_announcements_query);
mysqli_stmt_bind_param($stmt, "i", $school_id);
mysqli_stmt_execute($stmt);
$recent_announcements = mysqli_stmt_get_result($stmt);

$successMessage = '';
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    $successType = $_SESSION['success_type'];
    unset($_SESSION['success_message']);
    unset($_SESSION['success_type']);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>School Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../styles/dashboard.css">
    <style>
        .stat-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease-in-out;
            background: white;
            height: 100%;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #4a90e2;
        }
        .stat-title {
            color: #666;
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0;
        }
        .dashboard-section {
            margin-bottom: 2rem;
        }
        .section-title {
            color: #333;
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #4a90e2;
        }
        .quick-actions {
            background: white;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .action-btn {
            padding: 1rem;
            border-radius: 10px;
            text-align: center;
            background: #f8f9fa;
            transition: all 0.2s ease-in-out;
        }
        .action-btn:hover {
            background: #e9ecef;
            transform: translateY(-3px);
        }
        .action-icon {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            color: #4a90e2;
        }
        .announcement-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
        }
        .announcement-item {
            padding: 1rem;
            border-bottom: 1px solid #eee;
        }
        .announcement-item:last-child {
            border-bottom: none;
        }
        .announcement-date {
            color: #666;
            font-size: 0.9rem;
        }
        .school-info {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
        .school-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .school-code {
            color: #666;
            font-size: 1rem;
        }
    </style>
</head>

<body>
    <?php
    $current_page = 'admindashboard';
    include '../navbar/navbar.php';
    include '../department_admin/sidebar.php';
    ?>
<div id="content">
    <div class="main-content">
        <div class="container-fluid p-4">
            <!-- School Info Section -->
<div class="school-info d-flex justify-content-between align-items-center">
    <!-- School Details -->
    <div>
        <h1 class="school-name"><?php echo htmlspecialchars($school_info['school_name']); ?></h1>
        <div class="school-code">School Code: <?php echo htmlspecialchars($school_info['school_code']); ?></div>
    </div>

    <!-- Info Button -->
    <button type="button" class="btn btn-info btn-circle" data-bs-toggle="modal" data-bs-target="#manualModal" title="Read Manual">
        <i class="fas fa-info-circle"> Manual</i>
    </button>
</div>
 <?php include'school_admin_manual.php'; ?>

            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card">
                        <div class="card-body text-center">
                            <i class="fas fa-users stat-icon"></i>
                            <h6 class="stat-title">Total Users</h6>
                            <p class="stat-value"><?php echo $total_users; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card">
                        <div class="card-body text-center">
                            <i class="fas fa-building stat-icon"></i>
                            <h6 class="stat-title">Departments</h6>
                            <p class="stat-value"><?php echo $dept_count; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card">
                        <div class="card-body text-center">
                            <i class="fas fa-trophy stat-icon"></i>
                            <h6 class="stat-title">Active Games</h6>
                            <p class="stat-value"><?php echo $total_games; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="stat-card">
                        <div class="card-body text-center">
                            <i class="fas fa-bullhorn stat-icon"></i>
                            <h6 class="stat-title">Announcements</h6>
                            <p class="stat-value"><?php echo $total_announcements; ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions & Recent Announcements -->
            <div class="row g-4">
                <!-- Quick Actions -->
                <div class="col-lg-8">
                    <div class="dashboard-section">
                        <h2 class="section-title">Quick Actions</h2>
                        <div class="quick-actions">
                            <div class="row g-4">
                                <div class="col-md-3">
                                    <a href="../departments/departments.php" class="text-decoration-none">
                                        <div class="action-btn">
                                            <i class="fas fa-building action-icon"></i>
                                            <div>Departments</div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="../games/games.php" class="text-decoration-none">
                                        <div class="action-btn">
                                            <i class="fas fa-trophy action-icon"></i>
                                            <div>Games</div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="../announcements/adminannouncement.php" class="text-decoration-none">
                                        <div class="action-btn">
                                            <i class="fas fa-bullhorn action-icon"></i>
                                            <div>Announcements</div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="../users/userlist.php" class="text-decoration-none">
                                        <div class="action-btn">
                                            <i class="fas fa-users action-icon"></i>
                                            <div>Users</div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Announcements -->
                <div class="col-lg-4">
                    <div class="dashboard-section">
                        <h2 class="section-title">Recent Announcements</h2>
                        <div class="announcement-card">
                            <?php if (mysqli_num_rows($recent_announcements) > 0): ?>
                                <?php while ($announcement = mysqli_fetch_assoc($recent_announcements)): ?>
                                    <div class="announcement-item">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($announcement['title']); ?></h6>
                                        <div class="announcement-date">
                                            <i class="far fa-calendar-alt me-1"></i>
                                            <?php echo date('M d, Y', strtotime($announcement['created_at'])); ?>
                                        </div>
                                    </div>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <div class="text-center text-muted">No recent announcements</div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (!empty($successMessage)): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: '<?php echo $successType === "registration" ? "Registration Complete!" : "Login Successful!"; ?>',
                text: '<?php echo htmlspecialchars($successMessage); ?>',
                timer: 3000,
                showConfirmButton: false
            });
        </script>
    <?php endif; ?>
</body>

</html>