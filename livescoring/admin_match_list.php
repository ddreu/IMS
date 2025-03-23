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
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$selected_game_id = isset($_GET['game_id']) ? $_GET['game_id'] : '';
$selected_grade_level = isset($_GET['grade_level']) ? $_GET['grade_level'] : '';

// Prepare search terms for SQL
$searchTermWithWildcards = !empty($searchTerm) ? '%' . $searchTerm . '%' : '%';

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
    WHERE (
        CASE 
            WHEN ? = '' THEN 1
            ELSE (
                g.game_name LIKE ? OR 
                tA.team_name LIKE ? OR 
                tB.team_name LIKE ? OR 
                s.venue LIKE ?
            )
        END
    )
    AND tA.team_name NOT IN ('TBD', 'To Be Determined')
    AND tB.team_name NOT IN ('TBD', 'To Be Determined')
    AND b.is_archived = 0
    AND g.is_archived = 0
    AND tA.is_archived = 0
    AND tB.is_archived = 0
";


// Initialize parameters array with search parameters
$params = array($searchTerm, $searchTermWithWildcards, $searchTermWithWildcards, $searchTermWithWildcards, $searchTermWithWildcards);
$types = "sssss";

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
            <div class="filter-section">
                <div class="filter-row">
                    <div class="filter-group">
                        <label class="filter-label" for="searchInput">Search</label>
                        <input type="text" id="searchInput" class="filter-input" placeholder="Search matches...">
                    </div>
                    <div class="filter-group">
                        <label class="filter-label" for="filterGame">Game</label>
                        <select id="filterGame" class="filter-select">
                            <option value="">All Games</option>
                            <?php while ($game = $games_result->fetch_assoc()): ?>
                                <option value="<?= $game['game_id'] ?>" <?= $selected_game_id == $game['game_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($game['game_name']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <!--<div class="filter-group">
                        <label class="filter-label" for="filterGradeLevel">Grade Level</label>
                        <select id="filterGradeLevel" class="filter-select">
                            <option value="">All Grade Levels</option>
                            <?php while ($grade = $grade_result->fetch_assoc()): ?>
                                <option value="<?= $grade['grade_level'] ?>" <?= $selected_grade_level == $grade['grade_level'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($grade['grade_level']) ?>
                                </option>
                            <?php endwhile; ?>
                        </select>
                    </div>-->
                    <div class="filter-group">
                        <label class="filter-label" for="filterStatus">Status</label>
                        <select id="filterStatus" class="filter-select">
                            <option value="">All Status</option>
                            <option value="upcoming" <?= $status_filter == 'upcoming' ? 'selected' : '' ?>>Upcoming</option>
                            <option value="finished" <?= $status_filter == 'finished' ? 'selected' : '' ?>>Finished</option>
                        </select>
                    </div>
                </div>
                <div class="filter-buttons">
                    <button id="applyFilters" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Apply Filters
                    </button>
                    <button id="resetFilters" class="btn btn-secondary">
                        <i class="fas fa-undo"></i> Reset
                    </button>
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

            <!-- Desktop Table View -->
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
                                    case 'Finals':
                                        echo '<span class="badge bg-warning">Finals</span>';
                                        break;
                                    case 'Semi-Finals':
                                        echo '<span class="badge bg-info">Semi-Finals</span>';
                                        break;
                                    case 'Third Place':
                                        echo '<span class="badge bg-secondary">Third Place</span>';
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
                                <span class="detail-value">
                                    <span class="status-badge <?= $row['status'] == 'Upcoming' ? 'status-upcoming' : 'status-finished' ?>">
                                        <?= $row['status'] ?>
                                    </span>
                                </span>
                            </div>
                            <?php if ($row['status'] == 'Finished'): ?>
                                <div class="detail-item">
                                    <span class="detail-label">Score</span>
                                    <span class="detail-value">
                                        <?= $row['teamA_score'] ?? '0' ?> - <?= $row['teamB_score'] ?? '0' ?>
                                    </span>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="match-card-actions">
                            <?php if ($row['status'] == 'Upcoming'): ?>
                                <button class="btn btn-primary" onclick="startMatch(<?= $row['schedule_id'] ?>, <?= $row['teamA_id'] ?>, <?= $row['teamB_id'] ?>, <?= $row['game_id'] ?>)">
                                    <i class="fas fa-play"></i> Start Match
                                </button>
                                <button class="btn btn-info" onclick="notifyPlayers(<?= $row['schedule_id'] ?>, <?= $row['teamA_id'] ?>, <?= $row['teamB_id'] ?>)">
                                    <i class="fas fa-bell"></i> Notify
                                </button>
                            <?php else: ?>
                                <button class="btn btn-secondary" onclick="viewMatchDetails(<?= $row['match_id'] ?>)">
                                    <i class="fas fa-eye"></i> View Details
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
            <script>
                function startMatch(schedule_id, teamA_id, teamB_id, game_id) {
                    Swal.fire({
                        title: 'Start Match?',
                        text: "Are you sure you want to start this match?",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, start it!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            window.location.href = `start_match.php?schedule_id=${schedule_id}&teamA_id=${teamA_id}&teamB_id=${teamB_id}&game_id=${game_id}`;
                        }
                    });
                }
            </script>

            <script>
                function notifyPlayers(scheduleId, teamAId, teamBId) {
                    Swal.fire({
                        title: 'Are you sure?',
                        text: 'Do you want to notify the players?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, notify them!'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Send notification
                            fetch('notify_players.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                    },
                                    body: JSON.stringify({
                                        schedule_id: scheduleId,
                                        teamA_id: teamAId,
                                        teamB_id: teamBId
                                    })
                                })
                                .then(response => response.json())
                                .then(data => {
                                    if (data.success) {
                                        Swal.fire(
                                            'Success!',
                                            'Players have been notified.',
                                            'success'
                                        );
                                    } else {
                                        Swal.fire(
                                            'Error!',
                                            data.message || 'Failed to notify players.',
                                            'error'
                                        );
                                    }
                                })
                                .catch(error => {
                                    console.error('Error:', error);
                                    Swal.fire(
                                        'Error!',
                                        'Failed to notify players.',
                                        'error'
                                    );
                                });
                        }
                    });
                }
            </script>

            <script>
                let filterTimeout;
                let lastSearchValue = '';

                function applyFilters() {
                    // Clear any existing timeout
                    clearTimeout(filterTimeout);

                    // Set a new timeout
                    filterTimeout = setTimeout(() => {
                        const gameId = document.getElementById('filterGame').value;
                        const gradeLevel = document.getElementById('filterGradeLevel').value;
                        const status = document.getElementById('filterStatus').value;
                        const search = document.getElementById('searchInput').value;

                        // Check if search value has changed
                        if (search === lastSearchValue && search.length > 0) {
                            return; // Don't submit if the search value hasn't changed
                        }
                        lastSearchValue = search;

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

                        // Only redirect if we have actual filter values
                        if (gameId || gradeLevel || status || search || deptId) {
                            window.location.href = url + '?' + params.toString();
                        }
                    }, 1000); // Increased timeout to 1 second
                }

                // Add event listeners when document is ready
                document.addEventListener('DOMContentLoaded', function() {
                    // Add change event listeners for dropdowns
                    const filterInputs = [
                        'filterGame',
                        'filterGradeLevel',
                        'filterStatus'
                    ];

                    filterInputs.forEach(id => {
                        const element = document.getElementById(id);
                        if (element) {
                            element.addEventListener('change', applyFilters);
                        }
                    });

                    // Add input event for search with minimum length
                    const searchInput = document.getElementById('searchInput');
                    if (searchInput) {
                        searchInput.addEventListener('input', function(e) {
                            const value = e.target.value.trim();
                            if (value.length === 0 || value.length >= 2) {
                                applyFilters();
                            }
                        });
                    }

                    // Add click event for apply filters button
                    const applyButton = document.getElementById('applyFilters');
                    if (applyButton) {
                        applyButton.addEventListener('click', function() {
                            // Force immediate application of filters
                            clearTimeout(filterTimeout);
                            applyFilters();
                        });
                    }

                    // Add click event for reset filters button
                    const resetButton = document.getElementById('resetFilters');
                    if (resetButton) {
                        resetButton.addEventListener('click', function() {
                            // Reset all filter inputs
                            filterInputs.forEach(id => {
                                const element = document.getElementById(id);
                                if (element) {
                                    element.value = '';
                                }
                            });
                            // Reset search input
                            if (searchInput) {
                                searchInput.value = '';
                            }
                            // Clear the last search value
                            lastSearchValue = '';
                            // Redirect to base URL
                            window.location.href = window.location.href.split('?')[0];
                        });
                    }
                });
            </script>

</body>

</html>