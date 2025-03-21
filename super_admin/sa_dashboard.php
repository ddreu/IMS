<?php
include_once '../connection/conn.php';
$conn = con();
session_start();

// Check if session is active and if the role is 'superadmin'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'superadmin') {
    // Redirect to login page if session is not active or role is not superadmin
    header("Location: ../login.php");
    exit(); // Ensure no further code is executed
}

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Query to get the total number of schools
$query = "SELECT COUNT(*) AS total_schools FROM schools WHERE school_id != 0";
$result = $conn->query($query);

// Fetch the total count
$totalSchools = 0;
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $totalSchools = $row['total_schools'];
}

// Query to get the total count of users
$sql = "SELECT COUNT(*) AS total_users FROM users";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$total_users = $row['total_users'];

// Get active users count
$sql = "SELECT COUNT(DISTINCT user_id) AS active_users FROM sessions WHERE expires_at > NOW()";
$result = $conn->query($sql);
$row = $result->fetch_assoc();
$active_users = $row['active_users'];

// Get MySQL uptime
$uptime_query = "SHOW STATUS LIKE 'Uptime'";
$uptime_result = mysqli_query($conn, $uptime_query);
$uptime_row = mysqli_fetch_assoc($uptime_result);
$uptime_seconds = $uptime_row['Value'];

// Convert seconds to days, hours, minutes
$days = floor($uptime_seconds / (24 * 60 * 60));
$hours = floor(($uptime_seconds % (24 * 60 * 60)) / (60 * 60));
$minutes = floor(($uptime_seconds % (60 * 60)) / 60);

// Format uptime string
$uptime_string = '';
if ($days > 0) {
    $uptime_string .= $days . "d ";
}
if ($hours > 0) {
    $uptime_string .= $hours . "h ";
}
if ($minutes > 0) {
    $uptime_string .= $minutes . "m";
}

// Get user roles distribution
$sql = "SELECT role, COUNT(*) as count FROM users GROUP BY role";
$result = $conn->query($sql);
$roles_distribution = [];
while ($row = $result->fetch_assoc()) {
    $roles_distribution[$row['role']] = $row['count'];
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/ad90023682.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="sastyles.css">
    <style>
        .stat-card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease-in-out;
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
            background: #fff;
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

        .roles-chart {
            background: #fff;
            padding: 1.5rem;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .role-item {
            padding: 0.5rem 1rem;
            margin-bottom: 0.5rem;
            border-radius: 5px;
            background: #f8f9fa;
        }

        .role-count {
            font-weight: 600;
            color: #4a90e2;
        }
    </style>
</head>

<body>
    <?php
    $current_page = 'dashboard';
    include '../navbar/navbar.php';
    include 'sa_sidebar.php';
    ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid p-4">
            <!-- Welcome Section -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0 text-gray-800"><strong>Dashboard Overview</strong></h1>
                <div class="date text-muted"><?php echo date('l, F j, Y'); ?></div>
            </div>

            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-school stat-icon"></i>
                            <h6 class="stat-title">Total Schools</h6>
                            <p class="stat-value"><?php echo $totalSchools; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-users stat-icon"></i>
                            <h6 class="stat-title">Total Users</h6>
                            <p class="stat-value"><?php echo $total_users; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-user-check stat-icon"></i>
                            <h6 class="stat-title">Active Users</h6>
                            <p class="stat-value"><?php echo $active_users; ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card stat-card h-100">
                        <div class="card-body text-center">
                            <i class="fas fa-chart-line stat-icon"></i>
                            <h6 class="stat-title">Database Uptime</h6>
                            <p class="stat-value"><?php echo $uptime_string; ?></p>
                            <small class="text-muted">Since last restart</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions & Role Distribution -->
            <div class="row g-4">
                <!-- Quick Actions -->
                <div class="col-lg-8">
                    <div class="dashboard-section">
                        <h2 class="section-title">Quick Actions</h2>
                        <div class="quick-actions">
                            <div class="row g-4">
                                <div class="col-md-3">
                                    <a href="../schools/register_school.php" class="text-decoration-none">
                                        <div class="action-btn">
                                            <i class="fas fa-plus-circle action-icon"></i>
                                            <div>Register School</div>
                                        </div>
                                    </a>
                                </div>
                                <div class="col-md-3">
                                    <a href="../schools/schools.php" class="text-decoration-none">
                                        <div class="action-btn">
                                            <i class="fas fa-school action-icon"></i>
                                            <div>Manage Schools</div>
                                        </div>
                                    </a>
                                </div>
                                <!--<div class="col-md-3">
                                    <a href="../departments/departments.php" class="text-decoration-none">
                                        <div class="action-btn">
                                            <i class="fas fa-building action-icon"></i>
                                            <div>Departments</div>
                                        </div>
                                    </a>
                                </div>-->
                                <div class="col-md-3">
                                    <a href="../profile_settings/profile_settings.php" class="text-decoration-none">
                                        <div class="action-btn">
                                            <i class="fas fa-cog action-icon"></i>
                                            <div>Settings</div>
                                        </div>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Role Distribution -->
                <div class="col-lg-4">
                    <div class="dashboard-section">
                        <h2 class="section-title">User Roles Distribution</h2>
                        <div class="roles-chart">
                            <?php foreach ($roles_distribution as $role => $count): ?>
                                <div class="role-item d-flex justify-content-between align-items-center">
                                    <span><?php echo ucfirst($role); ?></span>
                                    <span class="role-count"><?php echo $count; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php if (isset($_SESSION['success_message']) && isset($_SESSION['success_type']) && $_SESSION['success_type'] === 'superadmin'): ?>
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Welcome!',
                text: '<?php echo $_SESSION['success_message']; ?>',
                timer: 2000,
                showConfirmButton: false
            });
        </script>
        <?php
        unset($_SESSION['success_message']);
        unset($_SESSION['success_type']);
        ?>
    <?php endif; ?>
</body>

</html>