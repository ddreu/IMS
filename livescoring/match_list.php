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
$role = $_SESSION['role'];

// Validate filters
$searchTerm = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';
$status_filter = $_GET['status'] ?? '';

// Get the current date and time
$currentDate = date('Y-m-d');
$currentTime = date('H:i:s');

// // Build SQL query dynamically
// $sql = "
//     SELECT 
//         s.schedule_id, 
//         m.match_id,
//         g.game_id, 
//         g.game_name, 
//         tA.team_name AS teamA_name, 
//         tB.team_name AS teamB_name,
//         s.schedule_date, 
//         s.schedule_time, 
//         s.venue,
//         m.teamA_id, 
//         m.teamB_id, 
//         mr.score_teamA AS teamA_score, 
//         mr.score_teamB AS teamB_score, 
//         mr.winning_team_id,
//         m.status,
//         m.match_type,
//         m.round,
//         CASE 
//             WHEN s.schedule_date = CURRENT_DATE() THEN 
//                 ABS(TIME_TO_SEC(TIMEDIFF(s.schedule_time, CURRENT_TIME())))
//             ELSE 
//                 ABS(DATEDIFF(s.schedule_date, CURRENT_DATE())) * 86400
//         END as time_difference
//     FROM schedules s
//     JOIN matches m ON s.match_id = m.match_id
//     JOIN brackets b ON m.bracket_id = b.bracket_id
//     JOIN games g ON b.game_id = g.game_id
//     JOIN teams tA ON m.teamA_id = tA.team_id
//     JOIN teams tB ON m.teamB_id = tB.team_id
//     LEFT JOIN match_results mr ON m.match_id = mr.match_id
//     WHERE (g.game_name LIKE ? OR tA.team_name LIKE ? OR tB.team_name LIKE ? OR s.venue LIKE ?)
//     AND tA.team_name NOT IN ('TBD', 'To Be Determined')
//     AND tB.team_name NOT IN ('TBD', 'To Be Determined')
// ";

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
    AND b.is_archived = 0
    AND g.is_archived = 0
    
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
    <style>
        /* Base styles */
        .match-list-container {
            padding: 15px;
        }

        .filter-section {
            background: #fff;
            padding: 15px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
        }

        .filter-row {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }

        .filter-group {
            flex: 1;
            min-width: 200px;
        }

        .filter-label {
            font-weight: 500;
            margin-bottom: 8px;
            color: #2c3e50;
        }

        .filter-select,
        .filter-input {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #dee2e6;
            border-radius: 6px;
            background-color: #fff;
        }

        .filter-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }

        /* Table styles */
        .match-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 8px;
        }

        .match-table th {
            background: #f8f9fa;
            padding: 12px;
            font-weight: 600;
            color: #2c3e50;
        }

        .match-table td {
            padding: 12px;
            vertical-align: middle;
        }

        /* Status badges */
        .status-badge {
            padding: 6px 12px;
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .status-upcoming {
            background: #e3f2fd;
            color: #0d47a1;
        }

        .status-finished {
            background: #e8f5e9;
            color: #1b5e20;
        }

        /* Mobile styles */
        @media (max-width: 768px) {
            .match-list-container {
                padding: 10px;
            }

            .filter-section {
                padding: 10px;
            }

            .filter-row {
                flex-direction: column;
                gap: 10px;
            }

            .filter-group {
                min-width: 100%;
            }

            .filter-buttons {
                flex-direction: column;
            }

            .filter-buttons .btn {
                width: 100%;
            }

            /* Hide table on mobile */
            .match-table {
                display: none;
            }

            /* Show cards on mobile */
            .match-cards {
                display: block;
            }

            .match-card {
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                margin-bottom: 15px;
                padding: 15px;
            }

            .match-card-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 10px;
            }

            .match-card-title {
                font-weight: 600;
                color: #2c3e50;
            }

            .match-card-type {
                font-size: 0.875rem;
                color: #6c757d;
            }

            .match-card-teams {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin: 15px 0;
                gap: 10px;
            }

            .team-name {
                flex: 1;
                text-align: center;
                font-weight: 500;
            }

            .vs-badge {
                padding: 4px 8px;
                background: #f8f9fa;
                border-radius: 4px;
                font-weight: 600;
                color: #6c757d;
            }

            .match-card-details {
                display: grid;
                gap: 8px;
                margin: 15px 0;
                font-size: 0.9rem;
            }

            .detail-item {
                display: flex;
                justify-content: space-between;
                padding: 4px 0;
                border-bottom: 1px solid #eee;
            }

            .detail-label {
                color: #6c757d;
            }

            .detail-value {
                font-weight: 500;
                color: #2c3e50;
            }

            .match-card-actions {
                display: flex;
                gap: 8px;
                margin-top: 15px;
            }

            .match-card-actions .btn {
                flex: 1;
                padding: 8px;
                font-size: 0.9rem;
            }
        }
    </style>
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
                    fetch('start_match.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded'
                            },
                            body: `schedule_id=${schedule_id}&teamA_id=${teamA_id}&teamB_id=${teamB_id}&game_id=${game_id}`
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                let redirectPage;
                                switch (data.game_type) {
                                    case 'point':
                                        redirectPage = 'point_based_scoreboard.php';
                                        break;
                                    case 'set':
                                        redirectPage = 'set-test.php';
                                        break;
                                    case 'default':
                                    default:
                                        redirectPage = 'default_scoreboard.php';
                                        break;
                                }
                                window.location.href = `${redirectPage}?schedule_id=${schedule_id}&teamA_id=${teamA_id}&teamB_id=${teamB_id}`;
                            } else {
                                Swal.fire('Error', data.message, 'error');
                            }
                        })
                        .catch(error => Swal.fire('Error', 'An unexpected error occurred', 'error'));
                }
            });
        }

        function joinMatch(scheduleId, teamAId, teamBId, gameId) {
            Swal.fire({
                title: 'Record Player Statistics',
                text: 'Do you want to proceed to record player statistics for this match?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, Record',
                cancelButtonText: 'Cancel'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'player_statistics.php?schedule_id=' + scheduleId +
                        '&teamA_id=' + teamAId +
                        '&teamB_id=' + teamBId +
                        '&game_id=' + gameId;
                }
            });
        }
    </script>
</head>

<body>

    <nav>
        <?php
        $current_page = 'matchlist';
        if ($role == 'Committee') {
            include '../committee/csidebar.php';
        } else if ($role == 'superdmin') {
            include '../superadmin/sa_sidebar.php';
        } else {
            include '../department_admin/sidebar.php';
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
                                <div class="table-responsive d-none d-md-block">
                                    <table class="match-table table table-bordered">
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
                                                                        // date_default_timezone_set('Asia/Manila');

                                                                        // Parse the scheduled date and time
                                                                        // $scheduleDateTime = strtotime($row['schedule_date'] . ' ' . $row['schedule_time']);

                                                                        // Get the current date and time
                                                                        //$currentDateTime = time(); // Using time() for simplicity

                                                                        // Calculate the absolute time difference
                                                                        // $timeDiff = abs($scheduleDateTime - $currentDateTime);

                                                                        // Show the button if:
                                                                        // 1. It's the same day
                                                                        // 2. The scheduled time is within 30 minutes (before or after)
                                                                        // if (date('Y-m-d', $scheduleDateTime) === date('Y-m-d', $currentDateTime) && $timeDiff <= 1800):
                                                                        ?>
                                                                        <!-- Start Match action -->
                                                                        <li>
                                                                            <button class="dropdown-item" onclick="startMatch(<?= $row['schedule_id']; ?>, <?= $row['teamA_id']; ?>, <?= $row['teamB_id']; ?>, <?= $row['game_id']; ?>)">
                                                                                Start Match
                                                                            </button>
                                                                        </li>
                                                                        <?php //endif; 
                                                                        ?>
                                                                    <?php elseif ($row['status'] === 'Ongoing'): ?>
                                                                        <!-- Continue Match action -->
                                                                        <li>
                                                                            <button class="dropdown-item" onclick="startMatch(<?= $row['schedule_id']; ?>, <?= $row['teamA_id']; ?>, <?= $row['teamB_id']; ?>, <?= $row['game_id']; ?>)">
                                                                                Continue Match
                                                                            </button>
                                                                            <button class="dropdown-item" onclick="joinMatch(<?= $row['schedule_id']; ?>, <?= $row['teamA_id']; ?>, <?= $row['teamB_id']; ?>, <?= $row['game_id']; ?>)">
                                                                                Record Stats
                                                                            </button>
                                                                            <a class="dropdown-item"
                                                                                href="live-stream.php?schedule_id=<?= $row['schedule_id']; ?>&teamA_id=<?= $row['teamA_id']; ?>&teamB_id=<?= $row['teamB_id']; ?>&game_id=<?= $row['game_id']; ?>">
                                                                                Live Stream
                                                                            </a>

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

                                <!-- Mobile Card View -->
                                <div class="match-cards d-md-none">
                                    <?php
                                    // Reset result pointer
                                    $result->data_seek(0);
                                    while ($row = $result->fetch_assoc()):
                                    ?>
                                        <div class="match-card">
                                            <div class="match-card-header">
                                                <div class="match-card-title"><?= htmlspecialchars($row['game_name']) ?></div>
                                                <div class="match-card-type">
                                                    <?php
                                                    switch ($row['match_type']) {
                                                        case 'final':
                                                            echo '<span class="badge bg-warning">Finals</span>';
                                                            break;
                                                        case 'semifinal':
                                                            echo '<span class="badge bg-info">Semifinals</span>';
                                                            break;
                                                        case 'third_place':
                                                            echo '<span class="badge bg-secondary">Battle for Third</span>';
                                                            break;
                                                        default:
                                                            echo '<span class="badge bg-primary">Round ' . htmlspecialchars($row['round']) . '</span>';
                                                    }
                                                    ?>
                                                </div>
                                            </div>

                                            <div class="match-card-teams">
                                                <div class="team-name"><?= htmlspecialchars($row['teamA_name']) ?></div>
                                                <div class="vs-badge">VS</div>
                                                <div class="team-name"><?= htmlspecialchars($row['teamB_name']) ?></div>
                                            </div>

                                            <div class="match-card-details">
                                                <div class="detail-item">
                                                    <span class="detail-label">Date</span>
                                                    <span class="detail-value"><?= date('F j, Y', strtotime($row['schedule_date'])) ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Time</span>
                                                    <span class="detail-value"><?= date('g:i A', strtotime($row['schedule_time'])) ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Venue</span>
                                                    <span class="detail-value"><?= htmlspecialchars($row['venue']) ?></span>
                                                </div>
                                                <div class="detail-item">
                                                    <span class="detail-label">Status</span>
                                                    <span class="status-badge <?= $row['status'] === 'Upcoming' ? 'status-upcoming' : ($row['status'] === 'Ongoing' ? 'status-ongoing' : 'status-finished') ?>">
                                                        <?= htmlspecialchars($row['status']) ?>
                                                    </span>
                                                </div>
                                                <?php if ($row['status'] === 'Finished'): ?>
                                                    <div class="detail-item">
                                                        <span class="detail-label">Score</span>
                                                        <span class="detail-value"><?= $row['teamA_score'] ?? '0' ?> - <?= $row['teamB_score'] ?? '0' ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <!-- Action Buttons -->
                                            <div class="match-card-actions">
                                                <?php if ($row['status'] === 'Upcoming'): ?>
                                                    <button class="btn btn-primary" onclick="startMatch(<?= $row['schedule_id'] ?>, <?= $row['teamA_id'] ?>, <?= $row['teamB_id'] ?>, <?= $row['game_id'] ?>)">
                                                        <i class="fas fa-play"></i> Start Match
                                                    </button>
                                                    <button class="btn btn-info" onclick="notifyPlayers(<?= $row['schedule_id'] ?>, <?= $row['teamA_id'] ?>, <?= $row['teamB_id'] ?>)">
                                                        <i class="fas fa-bell"></i> Notify Players
                                                    </button>
                                                <?php elseif ($row['status'] === 'Ongoing'): ?>
                                                    <button class="btn btn-success" onclick="startMatch(<?= $row['schedule_id']; ?>, <?= $row['teamA_id']; ?>, <?= $row['teamB_id']; ?>, <?= $row['game_id']; ?>)">
                                                        <i class="fas fa-play"></i> Continue Match
                                                    </button>
                                                    <button class="btn btn-warning" onclick="joinMatch(<?= $row['schedule_id']; ?>, <?= $row['teamA_id']; ?>, <?= $row['teamB_id']; ?>, <?= $row['game_id']; ?>)">
                                                        <i class="fas fa-clipboard-list"></i> Record Stats
                                                    </button>
                                                    <a href="live-stream.php?schedule_id=<?= $row['schedule_id']; ?>&teamA_id=<?= $row['teamA_id']; ?>&teamB_id=<?= $row['teamB_id']; ?>&game_id=<?= $row['game_id']; ?>" class="btn btn-danger">
                                                        <i class="fas fa-video"></i> Live Stream
                                                    </a>
                                                <?php elseif ($row['status'] === 'Finished'): ?>
                                                    <a href="match_summary.php?match_id=<?= $row['match_id']; ?>" class="btn btn-secondary">
                                                        <i class="fas fa-eye"></i> View Summary
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endwhile; ?>
                                </div>
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