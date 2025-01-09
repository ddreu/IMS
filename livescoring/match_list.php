<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Set timezone
date_default_timezone_set('Asia/Manila');

// Validate and assign session variables
$school_id = $_SESSION['school_id'] ?? null;
$school_name = $_SESSION['school_name'] ?? null;
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['role'] ?? null;
$department_id = $_SESSION['department_id'] ?? null;
$game_id = $_SESSION['game_id'] ?? null;

// Validate filters
$searchTerm = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';
$status_filter = $_GET['status'] ?? '';

// Get the current date and time
$currentDate = date('Y-m-d');
$currentTime = date('H:i:s');

// Build SQL query dynamically
$sql = "
    SELECT 
        s.schedule_id, 
        m.match_id,
        g.game_id, 
        g.game_name, 
        tA.team_name AS teamA_name, 
        tB.team_name AS teamB_name,
        s.schedule_date, 
        s.schedule_time, 
        s.venue,
        m.teamA_id, 
        m.teamB_id, 
        mr.score_teamA AS teamA_score, 
        mr.score_teamB AS teamB_score, 
        mr.winning_team_id,
        m.status,
        m.match_type,
        m.round,
        CASE 
            WHEN s.schedule_date = CURRENT_DATE() THEN 
                ABS(TIME_TO_SEC(TIMEDIFF(s.schedule_time, CURRENT_TIME())))
            ELSE 
                ABS(DATEDIFF(s.schedule_date, CURRENT_DATE())) * 86400
        END as time_difference
    FROM schedules s
    JOIN matches m ON s.match_id = m.match_id
    JOIN brackets b ON m.bracket_id = b.bracket_id
    JOIN games g ON b.game_id = g.game_id
    JOIN teams tA ON m.teamA_id = tA.team_id
    JOIN teams tB ON m.teamB_id = tB.team_id
    LEFT JOIN match_results mr ON m.match_id = mr.match_id
    WHERE (g.game_name LIKE ? OR tA.team_name LIKE ? OR tB.team_name LIKE ? OR s.venue LIKE ?)
    AND tA.team_name NOT IN ('TBD', 'To Be Determined')
    AND tB.team_name NOT IN ('TBD', 'To Be Determined')
";

// Dynamic filters based on user role
$params = [$searchTerm, $searchTerm, $searchTerm, $searchTerm];
$types = "ssss";

if ($user_role === 'Department Admin') {
    $sql .= " AND b.department_id = ?";
    $params[] = $department_id;
    $types .= "i";
} elseif ($user_role === 'Committee') {
    $sql .= " AND b.department_id = ? AND b.game_id = ?";
    $params[] = $department_id;
    $params[] = $game_id;
    $types .= "ii";
}

// Add school ID filter for all roles
$sql .= " AND g.school_id = ?";
$params[] = $school_id;
$types .= "i";

// Status filter
if ($status_filter === 'upcoming') {
    $sql .= " AND m.status = 'Upcoming'";
} elseif ($status_filter === 'finished') {
    $sql .= " AND m.status = 'Finished'";
}

// Add improved ordering
$sql .= " ORDER BY 
    CASE 
        WHEN m.status = 'Live' THEN 0
        WHEN m.status = 'Upcoming' AND s.schedule_date = CURRENT_DATE() THEN 1
        WHEN m.status = 'Upcoming' AND s.schedule_date > CURRENT_DATE() THEN 2
        WHEN m.status = 'Finished' AND s.schedule_date = CURRENT_DATE() THEN 3
        WHEN m.status = 'Finished' THEN 4
    END,
    CASE 
        WHEN s.schedule_date = CURRENT_DATE() THEN
            ABS(TIME_TO_SEC(TIMEDIFF(s.schedule_time, CURRENT_TIME())))
        ELSE 
            (ABS(DATEDIFF(s.schedule_date, CURRENT_DATE())) * 86400) + 
            ABS(TIME_TO_SEC(TIMEDIFF(s.schedule_time, CURRENT_TIME())))
    END ASC";

// Prepare and execute the query
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

include '../navbar/navbar.php';
?>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Match List</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <link rel="stylesheet" href="../styles/committee.css">
    <link rel="stylesheet" href="../styles/dashboard.css">
    <script>
        function startMatch(schedule_id, teamA_id, teamB_id, game_id) {
            Swal.fire({
                title: 'Start Match?',
                text: "Are you sure you want to start this match?",
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Yes, start it!',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Make an AJAX request to start_match.php
                    fetch('start_match.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `schedule_id=${schedule_id}&teamA_id=${teamA_id}&teamB_id=${teamB_id}&game_id=${game_id}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Redirect to live scoring page
                                window.location.href = `live_scoring.php?schedule_id=${schedule_id}&teamA_id=${teamA_id}&teamB_id=${teamB_id}`;
                            } else {
                                Swal.fire('Error', data.message, 'error');
                            }
                        })
                        .catch(error => Swal.fire('Error', 'An unexpected error occurred', 'error'));
                }
            });
        }
    </script>
</head>

<body>

    <nav>
        <?php
        $current_page = 'matchlist';


        // Include the appropriate sidebar based on the user role
        if ($user_role === 'Committee') {
            include '../committee/csidebar.php'; // Sidebar for committee
        } elseif ($user_role === 'Department Admin') {
            include '../department_admin/sidebar.php';
        } elseif ($user_role === 'School Admin') {
            include '../school_admin/schooladminsidebar.php';
        } else {
            include 'default_sidebar.php';
        }
        ?>
    </nav>
    <div class="main">
        <div class="container mt-1">
            <h1>Match Schedule List</h1>

            <!-- Filter by Status -->
            <div class="row mb-3">
                <div class="col-md-4">
                    <select id="status_filter" name="status" class="form-select" onchange="applyFilters()">
                        <option value="">All Status</option>
                        <option value="upcoming" <?= ($status_filter === 'upcoming') ? 'selected' : ''; ?>>Upcoming</option>
                        <option value="finished" <?= ($status_filter === 'finished') ? 'selected' : ''; ?>>Finished</option>
                    </select>
                </div>
                <div class="col-md-8">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" id="search_input" class="form-control" name="search"
                            placeholder="Search by game, team, or venue"
                            value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                            oninput="applyFilters()">
                    </div>
                </div>
            </div>

            <script>
                let filterTimeout;

                function applyFilters() {
                    // Clear any existing timeout
                    clearTimeout(filterTimeout);

                    // Set a new timeout
                    filterTimeout = setTimeout(() => {
                        const status = document.getElementById('status_filter').value;
                        const search = document.getElementById('search_input').value;

                        // Get the current URL without query parameters
                        let url = window.location.href.split('?')[0];

                        // Build query parameters
                        const params = new URLSearchParams();
                        if (status) params.append('status', status);
                        if (search) params.append('search', search);

                        // Redirect to the new URL
                        window.location.href = url + (params.toString() ? '?' + params.toString() : '');
                    }, 500); // Wait for 500ms after the last change before applying filters
                }
            </script>

            <!-- Table to Display Matches -->
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow mt-3">
                            <div class="card-header bg-white py-3">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h4 class="m-0 font-weight-bold text-primary">Matches</h4>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-4">
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Game Name</th>
                                            <th>Match Type</th>
                                            <th>Team A Name</th>
                                            <th>Team B Name</th>
                                            <th>Schedule Date & Time</th>
                                            <th>Venue</th>
                                            <th>Status</th>
                                            <th>Action</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($row = $result->fetch_assoc()): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($row['game_name']); ?></td>
                                                <td>
                                                    <?php
                                                    switch ($row['match_type']) {
                                                        case 'semifinal':
                                                            echo 'Semifinals';
                                                            break;
                                                        case 'final':
                                                            echo 'Finals';
                                                            break;
                                                        case 'third_place':
                                                            echo 'Battle for Third';
                                                            break;
                                                        default:
                                                            echo "Round {$row['round']}";
                                                    }
                                                    ?>
                                                </td>
                                                <td><?= htmlspecialchars($row['teamA_name']); ?></td>
                                                <td><?= htmlspecialchars($row['teamB_name']); ?></td>
                                                <td>
                                                    <?= htmlspecialchars(date("M d, Y", strtotime($row['schedule_date'])) . ', ' . date("g:i A", strtotime($row['schedule_time']))); ?>
                                                </td>
                                                <td><?= htmlspecialchars($row['venue']); ?></td>
                                                <td><?= htmlspecialchars($row['status']); ?></td>
                                                <td>
                                                    <?php if (!empty($row['schedule_id'])): ?>
                                                        <!-- Dropdown Menu -->
                                                        <div class="dropdown">
                                                            <button class="btn btn-primary dropdown-toggle" type="button" id="dropdownMenuButton<?= $row['schedule_id']; ?>"
                                                                data-bs-toggle="dropdown" aria-expanded="false">
                                                                Actions
                                                            </button>
                                                            <ul class="dropdown-menu" aria-labelledby="dropdownMenuButton<?= $row['schedule_id']; ?>">
                                                                <?php if ($row['status'] === 'Upcoming'): ?>
                                                                    <!-- Notify Players action -->
                                                                    <li>
                                                                        <button class="dropdown-item" onclick="notifyPlayers(<?= $row['schedule_id']; ?>, <?= $row['teamA_id']; ?>, <?= $row['teamB_id']; ?>)">
                                                                            Notify Players
                                                                        </button>
                                                                    </li>
                                                                    <?php
                                                                    // Set the default timezone to ensure consistent time handling
                                                                    date_default_timezone_set('Asia/Manila');

                                                                    // Parse the scheduled date and time
                                                                    $scheduleDateTime = strtotime($row['schedule_date'] . ' ' . $row['schedule_time']);

                                                                    // Get the current date and time
                                                                    $currentDateTime = time(); // Using time() for simplicity

                                                                    // Calculate the absolute time difference
                                                                    $timeDiff = abs($scheduleDateTime - $currentDateTime);

                                                                    // Show the button if:
                                                                    // 1. It's the same day
                                                                    // 2. The scheduled time is within 30 minutes (before or after)
                                                                    if (date('Y-m-d', $scheduleDateTime) === date('Y-m-d', $currentDateTime) && $timeDiff <= 1800):
                                                                    ?>
                                                                        <!-- Start Match action -->
                                                                        <li>
                                                                            <button class="dropdown-item" onclick="startMatch(<?= $row['schedule_id']; ?>, <?= $row['teamA_id']; ?>, <?= $row['teamB_id']; ?>, <?= $row['game_id']; ?>)">
                                                                                Start Match
                                                                            </button>
                                                                        </li>
                                                                    <?php endif; ?>
                                                                <?php elseif ($row['status'] === 'Ongoing'): ?>
                                                                    <!-- Continue Match action -->
                                                                    <li>
                                                                        <button class="dropdown-item" onclick="startMatch(<?= $row['schedule_id']; ?>, <?= $row['teamA_id']; ?>, <?= $row['teamB_id']; ?>, <?= $row['game_id']; ?>)">
                                                                            Continue Match
                                                                        </button>
                                                                    </li>
                                                                <?php elseif ($row['status'] === 'Finished'): ?>
                                                                    <!-- View Summary action -->
                                                                    <li>
                                                                        <a class="dropdown-item" href="match_summary.php?match_id=<?= $row['match_id']; ?>">
                                                                            <i class="fas fa-eye"></i> View Summary
                                                                        </a>
                                                                    </li>
                                                                <?php endif; ?>
                                                            </ul>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>

                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <script>
                function notifyPlayers(scheduleId, teamAId, teamBId) {
                    // Initial confirmation
                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'Do you want to notify the players?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, notify!',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Show loading state immediately
                            Swal.fire({
                                title: 'Sending Notifications',
                                html: 'Please wait while we notify the players...',
                                allowOutsideClick: false,
                                allowEscapeKey: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });

                            // Make the API call
                            fetch('notify_players.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded'
                                    },
                                    body: `schedule_id=${scheduleId}&teamA_id=${teamAId}&teamB_id=${teamBId}`
                                })
                                .then(response => response.text())
                                .then(data => {
                                    // Close loading indicator and show result
                                    if (data.includes("success")) {
                                        Swal.fire({
                                            title: 'Success!',
                                            text: 'SMS notifications sent successfully!',
                                            icon: 'success',
                                            confirmButtonText: 'OK',
                                            timer: 3000, // Automatically close after 3 seconds (3000ms)
                                            timerProgressBar: true // Shows a timer progress bar
                                        });
                                    } else {
                                        throw new Error('Failed to send notifications');
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    Swal.fire({
                                        title: 'Error!',
                                        text: 'Failed to send notifications.',
                                        icon: 'error',
                                        confirmButtonText: 'OK'
                                    });
                                });
                        }
                    });
                }
            </script>

</body>

</html>