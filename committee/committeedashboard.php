<?php
session_start();

include_once '../connection/conn.php';
$conn = con();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php'); // Redirect to login page if not logged in
    exit();
}

// echo "Welcome, " . $_SESSION['user_id'] . "!";

// Check the user's role using the session data
// if ($_SESSION['role'] !== 'Committee') {
//     header('Location: ../login.php');
//     exit();
// }

// Use `game_id` and `game_name` directly from session
$assigned_game_id = $_SESSION['game_id'] ?? null;
$assigned_game_name = $_SESSION['game_name'] ?? 'Committee Dashboard'; // Default name

// Use department info from session if available
$assigned_department_id = $_SESSION['department_id'] ?? null;
$_SESSION['department_name'] = $_SESSION['department_name'] ?? null;
$_SESSION['school_name'] = $_SESSION['school_name'] ?? null;

// Fetch games for the sidebar
$games_sql = "SELECT game_name FROM games";
$games_result = $conn->query($games_sql);

// Fetch the teams assigned to the user's selected game
$teams = [];
if ($assigned_game_id && $assigned_department_id) {
    $teams_sql = "SELECT t.team_name 
        FROM teams t 
        JOIN grade_section_course g ON t.grade_section_course_id = g.id 
        WHERE t.game_id = ? AND g.department_id = ?";

    $teams_stmt = mysqli_prepare($conn, $teams_sql);
    mysqli_stmt_bind_param($teams_stmt, "ii", $assigned_game_id, $assigned_department_id);
    mysqli_stmt_execute($teams_stmt);
    $teams_result = mysqli_stmt_get_result($teams_stmt);

    while ($team = mysqli_fetch_assoc($teams_result)) {
        $teams[] = $team;
    }
    mysqli_stmt_close($teams_stmt);
}

// Fetch ongoing tournaments count
$ongoing_count = 0;
if ($assigned_game_id && $assigned_department_id) {
    $ongoing_tournaments_sql = "SELECT COUNT(*) as ongoing_count 
                                FROM brackets 
                                WHERE status = 'Ongoing' 
                                AND game_id = ? 
                                AND department_id = ?";
    $stmt = $conn->prepare($ongoing_tournaments_sql);
    $stmt->bind_param("ii", $assigned_game_id, $assigned_department_id);
    $stmt->execute();
    $ongoing_result = $stmt->get_result();
    $ongoing_count = $ongoing_result->fetch_assoc()['ongoing_count'] ?? 0;
    $stmt->close();
}

// Fetch bracket status
$current_status = 'No Tournament';
if ($assigned_game_id && $assigned_department_id) {
    $bracket_status_sql = "SELECT status 
                           FROM brackets 
                           WHERE game_id = ? 
                           AND department_id = ?
                           LIMIT 1";
    $stmt = $conn->prepare($bracket_status_sql);
    $stmt->bind_param("ii", $assigned_game_id, $assigned_department_id);
    $stmt->execute();
    $bracket_result = $stmt->get_result();
    $bracket_status = $bracket_result->fetch_assoc();
    $stmt->close();

    $current_status = $bracket_status ? $bracket_status['status'] : 'No Tournament';
}

// Fetch upcoming scheduled matches count
$total_scheduled_matches = 0;
if ($assigned_game_id && $assigned_department_id) {
    $scheduled_matches_sql = "SELECT COUNT(*) as total_scheduled
        FROM schedules s
        JOIN matches m ON s.match_id = m.match_id
        JOIN brackets b ON m.bracket_id = b.bracket_id
        WHERE m.status = 'Upcoming'
        AND b.game_id = ?
        AND b.department_id = ?
        AND s.schedule_date > NOW()";

    $stmt = $conn->prepare($scheduled_matches_sql);
    $stmt->bind_param("ii", $assigned_game_id, $assigned_department_id);
    $stmt->execute();
    $scheduled_matches_result = $stmt->get_result();
    $scheduled_matches = $scheduled_matches_result->fetch_assoc();
    $total_scheduled_matches = $scheduled_matches['total_scheduled'] ?? 0;
    $stmt->close();
}

// Set up badge classes for different statuses
$badge_class = [
    'Group Stage' => 'bg-primary text-white',
    'Quarter Finals' => 'bg-info text-white',
    'ongoing' => 'bg-warning text-dark',
    'Finals' => 'bg-danger text-white',
    'Complete' => 'bg-success text-white',
    'Completed' => 'bg-success text-white',
    'No Tournament' => 'bg-secondary text-white'
];

// Get phase display text
$phase_text = ucfirst($current_status) . ' Stage';

// Check for success message
$successMessage = $_SESSION['success_message'] ?? null;
unset($_SESSION['success_message']); // Clear success message after use


//echo "<!-- Assigned Game ID: " . $assigned_game_id . " -->";
//echo "<!-- Assigned Game Name: " . $assigned_game_name . " -->";
//echo "<!-- Current Status: " . $current_status . " -->";
//echo "<!-- Total Scheduled Matches: " . $total_scheduled_matches . " -->";

?>

<?php
include '../navbar/navbar.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($assigned_game_name . ' Committee Dashboard'); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="stylesheet" href="../styles/committee.css">
    <style>
        :root {
            --primary-color: #2563eb;
            --primary-light: #3b82f6;
            --primary-dark: #1d4ed8;
            --success-color: #059669;
            --warning-color: #d97706;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --bg-light: #f1f5f9;
            --border-color: #e2e8f0;
        }

        body {
            background-color: var(--bg-light);
            color: var(--text-primary);
        }

        .main {
            padding: 2rem;
            position: relative;
            overflow: hidden;
        }

        .main::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 300px;
            height: 300px;
            background: linear-gradient(45deg, var(--primary-color) 0%, transparent 70%);
            opacity: 0.05;
            border-radius: 50%;
            transform: translate(150px, -150px);
            z-index: 0;
        }

        .dashboard-header {
            margin-bottom: 2.5rem;
            position: relative;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .dashboard-header::after {
            content: '';
            position: absolute;
            bottom: -1px;
            left: 0;
            width: 100px;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-color), transparent);
            border-radius: 3px;
        }

        .dashboard-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.75rem;
            letter-spacing: -0.025em;
            line-height: 1.2;
        }

        .dashboard-subtitle {
            color: var(--text-secondary);
            font-size: 1.1rem;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .dashboard-subtitle i {
            color: var(--primary-color);
            font-size: 1rem;
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
            gap: 1.75rem;
            margin-bottom: 2.5rem;
            position: relative;
            z-index: 1;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 2rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
            display: flex;
            align-items: center;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, rgba(255, 255, 255, 0.1) 0%, transparent 100%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .stat-card:hover::before {
            opacity: 1;
        }

        .stat-icon {
            width: 64px;
            height: 64px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1.5rem;
            transition: all 0.3s ease;
            position: relative;
        }

        .stat-card:hover .stat-icon {
            transform: scale(1.1) rotate(-5deg);
        }

        .stat-icon::after {
            content: '';
            position: absolute;
            width: 100%;
            height: 100%;
            border-radius: 20px;
            background: inherit;
            filter: blur(8px);
            opacity: 0.4;
            z-index: -1;
            transition: all 0.3s ease;
        }

        .stat-card:hover .stat-icon::after {
            transform: scale(1.2);
            opacity: 0.6;
        }

        .stat-info {
            flex: 1;
            position: relative;
        }

        .stat-value {
            font-size: 2.25rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
            line-height: 1.1;
            letter-spacing: -0.025em;
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 1rem;
            margin: 0.25rem 0 0;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .content-section {
            background: white;
            border-radius: 16px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
            border: 1px solid var(--border-color);
            margin-bottom: 1.75rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .content-section:hover {
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.1);
        }

        .section-header {
            padding: 1.75rem;
            border-bottom: 1px solid var(--border-color);
            background: linear-gradient(to right, white, #f8fafc);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .section-title i {
            color: var(--primary-color);
            font-size: 1.35rem;
            opacity: 0.9;
        }

        .section-content {
            padding: 1.75rem;
        }

        .activity-list {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .activity-item {
            display: flex;
            align-items: center;
            padding: 1.5rem 0;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
            position: relative;
        }

        .activity-item::before {
            content: '';
            position: absolute;
            left: -1.75rem;
            right: -1.75rem;
            top: 0;
            bottom: 0;
            background: var(--bg-light);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .activity-item:hover::before {
            opacity: 1;
        }

        .activity-item:last-child {
            border-bottom: none;
            padding-bottom: 0;
        }

        .activity-icon {
            width: 48px;
            height: 48px;
            border-radius: 14px;
            background: var(--bg-light);
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1.25rem;
            color: var(--primary-color);
            transition: all 0.3s ease;
            position: relative;
            z-index: 1;
        }

        .activity-item:hover .activity-icon {
            transform: scale(1.1) rotate(-5deg);
            background: var(--primary-color);
            color: white;
        }

        .activity-details {
            flex: 1;
            position: relative;
            z-index: 1;
        }

        .activity-title {
            font-size: 1rem;
            color: var(--text-primary);
            margin: 0;
            font-weight: 600;
            margin-bottom: 0.35rem;
        }

        .activity-time {
            font-size: 0.9rem;
            color: var(--text-secondary);
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .activity-time i {
            font-size: 0.8rem;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.25rem;
            padding: 1.75rem;
        }

        .action-btn {
            padding: 1.25rem 1.5rem;
            border: 1px solid var(--border-color);
            border-radius: 14px;
            background: white;
            color: var(--text-primary);
            text-align: center;
            text-decoration: none;
            font-size: 1rem;
            font-weight: 500;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.75rem;
            position: relative;
            overflow: hidden;
        }

        .action-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, var(--primary-color), #dbdcde);
            opacity: 0;
            transition: opacity 0.3s ease;
            z-index: 0;
        }

        .action-btn:hover {
            color: white;
            border-color: transparent;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
        }

        .action-btn:hover::before {
            opacity: 1;
        }

        .action-btn i,
        .action-btn span {
            position: relative;
            z-index: 1;
        }

        .action-btn i {
            font-size: 1.25rem;
        }

        .tournament-status {
            padding: 1.75rem;
        }

        .status-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
            padding-bottom: 1.25rem;
            border-bottom: 1px solid var(--border-color);
        }

        .status-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }

        .status-label {
            color: var(--text-secondary);
            font-size: 1rem;
            font-weight: 500;
        }

        .status-value {
            font-weight: 600;
            color: var(--text-primary);
        }

        .badge {
            padding: 0.6rem 1.2rem;
            border-radius: 10px;
            font-weight: 500;
            font-size: 0.95rem;
            letter-spacing: 0.01em;
        }

        .bg-primary {
            background: linear-gradient(45deg, #dbeafe, #eff6ff);
            color: var(--primary-color);
        }

        .progress-container {
            margin-top: 1.25rem;
        }

        .progress {
            height: 10px;
            border-radius: 5px;
            background-color: var(--bg-light);
            overflow: hidden;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .progress-bar {
            background: linear-gradient(90deg, var(--primary-color), var(--primary-light));
            border-radius: 5px;
            transition: width 0.4s ease;
        }


        @media (max-width: 768px) {
            .main {
                padding: 1rem;
            }

            .stats-container {
                grid-template-columns: 1fr;
            }

            .stat-card {
                padding: 1.25rem;
            }

            .stat-icon {
                width: 48px;
                height: 48px;
            }

            .stat-value {
                font-size: 1.5rem;
            }

            .section-header {
                padding: 1.25rem;
            }

            .section-content {
                padding: 1.25rem;
            }

            .quick-actions {
                grid-template-columns: 1fr;
            }

            .activity-item {
                padding: 1rem 0;
            }

            .dashboard-title {
                font-size: 1.5rem;
            }
        }
    </style>
</head>

<body>
    <nav>
        <?php
        $current_page = 'committeedashboard';
        include 'csidebar.php';
        ?>
    </nav>
    <div class="mt-4">
        <div class="container-fluid">
            <section class="main">
                <div class="dashboard-header d-flex align-items-center justify-content-between">
                    <!-- Dashboard Title and Subtitle -->
                    <div>
                        <h1 class="dashboard-title mb-0">
                            <?php echo htmlspecialchars($assigned_game_name); ?> Dashboard
                        </h1>
                        <p class="dashboard-subtitle mb-0">
                            <?php echo htmlspecialchars($_SESSION['department_name']); ?> Department
                        </p>
                    </div>

                    <!-- Info Button -->
                    <button type="button" class="btn btn-info btn-circle" data-bs-toggle="modal" data-bs-target="#manualModal" title="Read Manual">
                        <i class="fas fa-info-circle"> Manual</i>
                    </button>
                </div>

                <?php include "committee_manual.php"; ?>

                <div class="stats-container">
                    <div class="stat-card">
                        <div class="stat-icon blue">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-info">
                            <h3 class="stat-value"><?php echo count($teams); ?></h3>
                            <p class="stat-label">Total Teams</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon green">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-info">
                            <h3 class="stat-value"><?php echo $total_scheduled_matches; ?></h3>
                            <p class="stat-label">Scheduled Matches</p>
                        </div>
                    </div>

                    <div class="stat-card">
                        <div class="stat-icon orange">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="stat-info">
                            <h3 class="stat-value"><?= $ongoing_count ?></h3>
                            <p class="stat-label">Active Tournament<?= $ongoing_count !== 1 ? 's' : '' ?></p>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-8">
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

                    <div class="col-lg-4">
                        <div class="content-section">
                            <div class="section-header">
                                <h2 class="section-title">
                                    <i class="fas fa-bolt"></i>
                                    Quick Actions
                                </h2>
                            </div>
                            <div class="quick-actions">
                                <a href="../teams/teams.php" class="action-btn">
                                    <i class="fas fa-users"></i>
                                    Manage Teams
                                </a>
                                <a href="../schedule/schedule.php" class="action-btn">
                                    <i class="fas fa-calendar-alt"></i>
                                    Schedule Match
                                </a>
                            </div>
                        </div>

                        <div class="content-section">
                            <div class="section-header">
                                <h2 class="section-title">
                                    <i class="fas fa-chart-line"></i>
                                    Tournament Progress
                                </h2>
                            </div>
                            <div class="tournament-status">
                                <div class="status-item">
                                    <span class="status-label">Current Phase</span>
                                    <span class="badge <?= $badge_class[$current_status] ?? 'badge-secondary' ?>"><?= htmlspecialchars($current_status) ?></span>
                                </div>
                                <!-- <div class="status-item">
                                    <span class="status-label">Status</span>
                                    <span class="status-value"><?= $current_status === 'Completed' ? 'Tournament Complete' : 'In Progress' ?></span>
                                </div>-->
                                <div class="progress-container">
                                    <div class="progress">
                                        <div class="progress-bar" role="progressbar"
                                            style="width: <?= $current_status === 'Completed' ? '100' : '50' ?>%"
                                            aria-valuenow="<?= $current_status === 'Completed' ? '100' : '50' ?>"
                                            aria-valuemin="0"
                                            aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <?php if (!empty($successMessage)): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: '<?php echo htmlspecialchars($successMessage); ?>',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000
                });
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