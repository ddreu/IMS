<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

// Get user information
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$department_id = $_SESSION['department_id'];
$school_id = $_SESSION['school_id'];
$department_name = $_SESSION['department_name'];

// Get department information
$dept_query = "SELECT d.*, s.school_name FROM departments d 
               JOIN schools s ON d.school_id = s.school_id 
               WHERE d.id = ?";
$stmt = mysqli_prepare($conn, $dept_query);
mysqli_stmt_bind_param($stmt, "i", $department_id);
mysqli_stmt_execute($stmt);
$dept_result = mysqli_stmt_get_result($stmt);
$department_info = mysqli_fetch_assoc($dept_result);

// Get total committees
$committees_query = "SELECT COUNT(*) as committee_count FROM users 
                    WHERE role = 'Committee' 
                    AND school_id = ? 
                    AND department = ?";
$stmt = mysqli_prepare($conn, $committees_query);
mysqli_stmt_bind_param($stmt, "ii", $school_id, $department_id);
mysqli_stmt_execute($stmt);
$committees_result = mysqli_stmt_get_result($stmt);
$total_committees = mysqli_fetch_assoc($committees_result)['committee_count'];

// Get total announcements
$announcements_query = "SELECT COUNT(*) as announcement_count FROM announcement 
                       WHERE department_id = ?";
$stmt = mysqli_prepare($conn, $announcements_query);
mysqli_stmt_bind_param($stmt, "i", $department_id);
mysqli_stmt_execute($stmt);
$announcements_result = mysqli_stmt_get_result($stmt);
$total_announcements = mysqli_fetch_assoc($announcements_result)['announcement_count'];

// Get recent announcements
$recent_announcements_query = "SELECT * FROM announcement 
                             WHERE department_id = ? 
                             ORDER BY created_at DESC LIMIT 5";
$stmt = mysqli_prepare($conn, $recent_announcements_query);
mysqli_stmt_bind_param($stmt, "i", $department_id);
mysqli_stmt_execute($stmt);
$recent_announcements = mysqli_stmt_get_result($stmt);

// Get sections/courses/strands count
$items_query = "";
$item_label = "";
switch ($department_name) {
    case 'College':
        $items_query = "SELECT COUNT(*) as count FROM grade_section_course 
                       WHERE department_id = ? AND course_name IS NOT NULL";
        $item_label = "Courses";
        break;
    case 'SHS':
        $items_query = "SELECT COUNT(*) as count FROM grade_section_course 
                       WHERE department_id = ? AND strand IS NOT NULL";
        $item_label = "Strands";
        break;
    default:
        $items_query = "SELECT COUNT(*) as count FROM grade_section_course 
                       WHERE department_id = ? AND section_name IS NOT NULL";
        $item_label = "Sections";
}
$stmt = mysqli_prepare($conn, $items_query);
mysqli_stmt_bind_param($stmt, "i", $department_id);
mysqli_stmt_execute($stmt);
$items_result = mysqli_stmt_get_result($stmt);
$total_items = mysqli_fetch_assoc($items_result)['count'];

// Get total games for the school
$games_query = "SELECT COUNT(*) as games_count FROM games WHERE school_id = ?";
$stmt = mysqli_prepare($conn, $games_query);
mysqli_stmt_bind_param($stmt, "i", $school_id);
mysqli_stmt_execute($stmt);
$games_result = mysqli_stmt_get_result($stmt);
$total_games = mysqli_fetch_assoc($games_result)['games_count'];

// Check for success message
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
    <title>Department Admin Dashboard</title>
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

        .department-info {
            background: white;
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .department-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .school-name {
            color: #666;
            font-size: 1rem;
        }

        /* General styles for the content section */
        .content-section {
            background-color: #ffffff;
            border: 1px solid #e3e6f0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .section-header {
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 18px;
            color: #333;
            margin: 0;
            display: flex;
            align-items: center;
            font-weight: bold;
        }

        .section-title i {
            margin-right: 10px;
            color: #6c757d;
            /* Grey icon color */
        }

        /* Styling the list of activities */
        .activity-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            margin-bottom: 15px;
            border-bottom: 1px solid #f8f9fa;
            padding-bottom: 15px;
        }

        .activity-icon {
            color: #4e73df;
            /* Icon color, can be adjusted */
            font-size: 24px;
            margin-right: 15px;
            min-width: 30px;
            text-align: center;
        }

        .activity-details {
            flex: 1;
        }

        .activity-title {
            font-size: 16px;
            color: #4e73df;
            /* Link color, can be adjusted */
            margin: 0 0 5px 0;
            font-weight: 500;
        }

        .activity-user {
            font-size: 14px;
            color: #858796;
            margin: 0;
            display: flex;
            align-items: center;
        }

        .activity-user i {
            margin-right: 5px;
        }

        .activity-time {
            font-size: 14px;
            color: #858796;
            display: flex;
            align-items: center;
        }

        .activity-time i {
            margin-right: 5px;
        }

        /* Responsive adjustments for smaller screens */
        @media (max-width: 768px) {
            .section-title {
                flex-direction: column;
                align-items: flex-start;
            }

            .activity-item {
                flex-direction: column;
                align-items: start;
            }
        }
    </style>
</head>
<?php include '../navbar/navbar.php'; ?>

<body>
    <div class="wrapper">
        <?php
        $current_page = 'admindashboard';
        // include '../navbar/navbar.php';
        include '../department_admin/sidebar.php';

        ?>

        <div id="content">

            <div class="container-fluid p-4">
                <!-- Department Info Section -->

                <div class="department-info d-flex justify-content-between align-items-center">
                    <div>
                        <h1 class="department-name"><?php echo htmlspecialchars($department_info['department_name']); ?> Department</h1>
                        <div class="school-name"><?php echo htmlspecialchars($department_info['school_name']); ?></div>
                    </div>
                    <!-- Info Button -->
                    <button type="button" class="btn btn-info btn-circle" data-bs-toggle="modal" data-bs-target="#manualModal" title="Read Manual">
                        <i class="fas fa-info-circle"> Manual</i>
                    </button>

                    <?php include 'department_admin_manual.php'; ?>

                </div>

                <!-- Statistics Cards -->
                <div class="row g-4 mb-4">
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-users stat-icon"></i>
                                <h6 class="stat-title">Committee Members</h6>
                                <p class="stat-value"><?php echo $total_committees; ?></p>
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
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-graduation-cap stat-icon"></i>
                                <h6 class="stat-title"><?php echo htmlspecialchars($item_label); ?></h6>
                                <p class="stat-value"><?php echo $total_items; ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-3 col-md-6">
                        <div class="stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-medal stat-icon"></i>
                                <h6 class="stat-title">Games</h6>
                                <p class="stat-value"><?php echo $total_games; ?></p>
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
                                        <a href="../users/userlist.php" class="text-decoration-none">
                                            <div class="action-btn">
                                                <i class="fas fa-users action-icon"></i>
                                                <div>Committees</div>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="../announcement/adminannouncement.php" class="text-decoration-none">
                                            <div class="action-btn">
                                                <i class="fas fa-bullhorn action-icon"></i>
                                                <div>Announcements</div>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="../rankingss/leaderboards.php" class="text-decoration-none">
                                            <div class="action-btn">
                                                <i class="fas fa-trophy action-icon"></i>
                                                <div>Leaderboards</div>
                                            </div>
                                        </a>
                                    </div>
                                    <div class="col-md-3">
                                        <a href="../games/games.php" class="text-decoration-none">
                                            <div class="action-btn">
                                                <i class="fas fa-medal action-icon"></i>
                                                <div>Games</div>
                                            </div>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Recent Announcements -->
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

                    <div class="col-lg-4">
                        <div class="content-section">
                            <div class="section-header">
                                <h2 class="section-title">
                                    <i class="fas fa-history"></i>
                                    Recent Activity
                                </h2>
                            </div>
                            <div class="section-content">
                                <ul class="activity-list" id="recentActivityList">
                                    <!-- Activities will be populated here -->
                                </ul>
                            </div>
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
                toast: true,
                position: 'top-end',
                showConfirmButton: false
            });
        </script>
    <?php endif; ?>
    <script>
        // Function to format the timestamp to "time ago" format
        function timeAgo(timestamp) {
            const now = new Date();
            const past = new Date(timestamp);
            const diffInSeconds = Math.floor((now - past) / 1000);

            if (diffInSeconds < 60) return 'just now';
            if (diffInSeconds < 3600) return Math.floor(diffInSeconds / 60) + ' minutes ago';
            if (diffInSeconds < 86400) return Math.floor(diffInSeconds / 3600) + ' hours ago';
            if (diffInSeconds < 604800) return Math.floor(diffInSeconds / 86400) + ' days ago';
            return past.toLocaleDateString();
        }

        // Function to get appropriate icon based on log action
        function getActionIcon(action) {
            const iconMap = {
                'CREATE': 'fa-plus-circle',
                'UPDATE': 'fa-edit',
                'DELETE': 'fa-trash',
                'LOGIN': 'fa-sign-in-alt',
                'LOGOUT': 'fa-sign-out-alt'
            };
            return iconMap[action] || 'fa-history';
        }

        // Function to fetch and display recent activities
        function fetchRecentActivities() {
            fetch('../user_logs/fetch_logs.php')
                .then(response => response.json())
                .then(data => {
                    console.log('Response data:', data); // Log the response to see its structure
                    const activityList = document.getElementById('recentActivityList');
                    activityList.innerHTML = ''; // Clear existing items

                    // Display only the first 5 items
                    data.data.slice(0, 5).forEach(log => {
                        const li = document.createElement('li');
                        li.className = 'activity-item';
                        li.innerHTML = `
                        <div class="activity-icon">
                            <i class="fas ${getActionIcon(log.log_action)}"></i>
                        </div>
                        <div class="activity-details">
                            <h4 class="activity-title">${log.log_description}</h4>
                            <p class="activity-user">
                                <i class="fas fa-user"></i> ${log.full_name}
                            </p>
                            <p class="activity-time">
                                <i class="fas fa-clock"></i> ${timeAgo(log.log_time)}
                            </p>
                        </div>
                    `;
                        activityList.appendChild(li);
                    });
                })
                .catch(error => {
                    console.error('Error fetching recent activities:', error);
                });
        }

        // Fetch activities when page loads
        document.addEventListener('DOMContentLoaded', fetchRecentActivities);

        // Refresh activities every 5 minutes
        setInterval(fetchRecentActivities, 300000);
    </script>
</body>

</html>

<?php $conn->close(); ?>