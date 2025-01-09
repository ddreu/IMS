<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$school_id = $_SESSION['school_id'];
$school_name = $_SESSION['school_name'];
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['role'];
$department_id = isset($_SESSION['department_id']) ? $_SESSION['department_id'] : null;
$game_id = isset($_SESSION['game_id']) ? $_SESSION['game_id'] : null;

if ($user_role === 'Committee') {
    include '../committee/csidebar.php'; // Sidebar for committee
} else {
    include '../department_admin/sidebar.php';
}

include '../navbar/navbar.php';
// Get department_id from URL if available, otherwise use session
$selected_department_id = isset($_GET['selected_department_id']) ? $_GET['selected_department_id'] : $department_id;

// Get filter values
$searchTerm = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$selected_game_id = isset($_GET['game_id']) ? $_GET['game_id'] : '';
$selected_grade_level = isset($_GET['grade_level']) ? $_GET['grade_level'] : '';

// Debug output for filter values
if (isset($_GET['debug'])) {
    echo "<div style='background: #f8f9fa; padding: 15px; margin: 15px 0; border-radius: 5px;'>";
    echo "<h5>Input Values:</h5>";
    echo "<pre>";
    echo "Department ID (from URL): " . $selected_department_id . "\n";
    echo "Game ID (from dropdown): " . $selected_game_id . "\n";
    echo "Grade Level (from dropdown): " . $selected_grade_level . "\n";
    echo "Status Filter: " . $status_filter . "\n";
    echo "Search Term: " . $searchTerm . "\n";
    echo "</pre>";
    echo "</div>";
}

// Initialize the SQL query and prepare statement
$sql = "
    SELECT DISTINCT
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
        b.grade_level,
        b.department_id
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

// Initialize parameters array
$params = array($searchTerm, $searchTerm, $searchTerm, $searchTerm);
$types = "ssss";

// Add department filter from URL
if (!empty($selected_department_id)) {
    $sql .= " AND b.department_id = ?";
    $params[] = $selected_department_id;
    $types .= "i";
}

// Add game filter from dropdown
if (!empty($selected_game_id)) {
    $sql .= " AND b.game_id = ?";
    $params[] = $selected_game_id;
    $types .= "i";
}

// Add grade level filter from dropdown
if (!empty($selected_grade_level)) {
    $sql .= " AND b.grade_level = ?";
    $params[] = $selected_grade_level;
    $types .= "s";
}

// Add status filter if specified
if ($status_filter === 'upcoming') {
    $sql .= " AND m.status = 'Upcoming'";
} elseif ($status_filter === 'finished') {
    $sql .= " AND m.status = 'Finished'";
}

// Add school_id filter
$sql .= " AND g.school_id = ? ORDER BY 
    CASE 
        WHEN m.status = 'Finished' THEN 1 
        ELSE 0 
    END,
    CASE 
        WHEN s.schedule_date < CURRENT_DATE() THEN 2
        WHEN s.schedule_date = CURRENT_DATE() AND s.schedule_time < CURRENT_TIME() THEN 1
        ELSE 0 
    END,
    s.schedule_date ASC,
    s.schedule_time ASC";

// Add school_id to params
$params[] = $school_id;
$types .= "i";

// Prepare and execute the statement
$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// For debugging
echo "<!-- SQL Query: " . $sql . " -->\n";
echo "<!-- Parameters: " . print_r($params, true) . " -->\n";
echo "<!-- Types: " . $types . " -->\n";
echo "<!-- Number of results: " . ($result ? $result->num_rows : 0) . " -->\n";

// Fetch available games for dropdown
$games_query = "SELECT DISTINCT g.game_id, g.game_name 
                FROM games g 
                WHERE g.school_id = ?
                ORDER BY g.game_name ASC";
$games_stmt = $conn->prepare($games_query);
$games_stmt->bind_param("i", $school_id);
$games_stmt->execute();
$games_result = $games_stmt->get_result();

// Fetch available grade levels for dropdown based on department
if ($selected_department_id) {
    $grade_query = "SELECT DISTINCT grade_level 
                    FROM grade_section_course 
                    WHERE department_id = ? 
                    AND grade_level IS NOT NULL
                    ORDER BY FIELD(grade_level, 
                        'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6',
                        'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'
                    )";
    $grade_stmt = $conn->prepare($grade_query);
    $grade_stmt->bind_param("i", $selected_department_id);
    $grade_stmt->execute();
    $grade_result = $grade_stmt->get_result();
} else {
    // If no department is selected, get all grade levels
    $grade_query = "SELECT DISTINCT grade_level 
                    FROM grade_section_course 
                    WHERE grade_level IS NOT NULL
                    ORDER BY FIELD(grade_level, 
                        'Grade 1', 'Grade 2', 'Grade 3', 'Grade 4', 'Grade 5', 'Grade 6',
                        'Grade 7', 'Grade 8', 'Grade 9', 'Grade 10', 'Grade 11', 'Grade 12'
                    )";
    $grade_stmt = $conn->prepare($grade_query);
    $grade_stmt->execute();
    $grade_result = $grade_stmt->get_result();
}

// Include the appropriate sidebar based on the user role

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
                                window.location.href = `live_scoring.php?schedule_id=${schedule_id}&teamA_id=${teamA_id}&teamB_id=${teamBId}`;
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
        ?>
    </nav>
    <div id="content">
        <div class="container mt-1">
            <h1>Match Schedule List</h1>

            <!-- Filter Form -->
            <div class="row mb-3">
                <div class="col-md-3">
                    <select id="game_filter" name="game_id" class="form-select" onchange="applyFilters()">
                        <option value="">All Games</option>
                        <?php while ($game = $games_result->fetch_assoc()): ?>
                            <option value="<?= $game['game_id']; ?>" <?= ($selected_game_id == $game['game_id']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($game['game_name']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="grade_filter" name="grade_level" class="form-select" onchange="applyFilters()">
                        <option value="">All Grade Levels</option>
                        <?php while ($grade = $grade_result->fetch_assoc()): ?>
                            <option value="<?= $grade['grade_level']; ?>" <?= ($selected_grade_level == $grade['grade_level']) ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($grade['grade_level']); ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <select id="status_filter" name="status" class="form-select" onchange="applyFilters()">
                        <option value="">All Status</option>
                        <option value="upcoming" <?= ($status_filter === 'upcoming') ? 'selected' : ''; ?>>Upcoming</option>
                        <option value="finished" <?= ($status_filter === 'finished') ? 'selected' : ''; ?>>Finished</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" id="search_input" class="form-control" name="search"
                            placeholder="Search by game, team, or venue"
                            value="<?= isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>"
                            oninput="applyFilters()">
                    </div>
                </div>
            </div>

            <?php if (isset($_GET['debug'])): ?>
                <div class="alert alert-info">
                    <h4>Debug Information:</h4>
                    <pre>
Filter Values:
Game ID: <?= $selected_game_id ?>
Grade Level: <?= $selected_grade_level ?>
Department ID: <?= $selected_department_id ?>
Status: <?= $status_filter ?>

SQL Query: <?= $sql ?>

Parameters: <?php print_r($params); ?>

Number of results: <?= ($result ? $result->num_rows : 0) ?>
                </pre>
                </div>
            <?php endif; ?>

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
                                                                    <!-- Start Match action -->
                                                                    <!-- <li>
                                                                        <button class="dropdown-item" onclick="startMatch(<?= $row['schedule_id']; ?>, <?= $row['teamA_id']; ?>, <?= $row['teamB_id']; ?>, <?= $row['game_id']; ?>)">
                                                                            Start Match
                                                                        </button>
                                                                    </li>-->
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
                let filterTimeout;

                function applyFilters() {
                    // Clear any existing timeout
                    clearTimeout(filterTimeout);

                    // Set a new timeout
                    filterTimeout = setTimeout(() => {
                        const gameId = document.getElementById('game_filter').value;
                        const gradeLevel = document.getElementById('grade_filter').value;
                        const status = document.getElementById('status_filter').value;
                        const search = document.getElementById('search_input').value;

                        // Get the current URL without query parameters
                        let url = window.location.href.split('?')[0];

                        // Build query parameters
                        const params = new URLSearchParams();
                        if (gameId) params.append('game_id', gameId);
                        if (gradeLevel) params.append('grade_level', gradeLevel);
                        if (status) params.append('status', status);
                        if (search) params.append('search', search);

                        // Get department_id from URL if it exists
                        const urlParams = new URLSearchParams(window.location.search);
                        const deptId = urlParams.get('selected_department_id');
                        if (deptId) params.append('selected_department_id', deptId);

                        // Redirect to the new URL
                        window.location.href = url + (params.toString() ? '?' + params.toString() : '');
                    }, 500); // Wait for 500ms after the last change before applying filters
                }
            </script>

            <script>
                function notifyPlayers(scheduleId, teamAId, teamBId) {
                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'Do you want to notify the players?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Yes, notify!',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            Swal.fire({
                                title: 'Sending Notifications',
                                html: 'Please wait while we notify the players...',
                                allowOutsideClick: false,
                                allowEscapeKey: false,
                                didOpen: () => {
                                    Swal.showLoading();
                                }
                            });

                            fetch('notify_players.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded'
                                    },
                                    body: `schedule_id=${scheduleId}&teamA_id=${teamAId}&teamB_id=${teamBId}`
                                })
                                .then(response => response.text())
                                .then(data => {
                                    // Check for success or error from the response
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
                                        Swal.fire({
                                            title: 'Error!',
                                            text: 'Failed to send notifications.',
                                            icon: 'error',
                                            confirmButtonText: 'OK'
                                        });
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