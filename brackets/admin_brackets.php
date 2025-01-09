<?php
session_start();
require_once '../connection/conn.php';
$conn = con();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Retrieve session data
$school_id = $_SESSION['school_id'] ?? null;

if (!$school_id) {
    die('Error: Required session data is missing.');
}

// Get all departments for filter
$dept_query = "SELECT id, department_name FROM departments WHERE school_id = ?";
$stmt = $conn->prepare($dept_query);
$stmt->bind_param("i", $school_id);
$stmt->execute();
$departments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get all games for filter
$games_query = "SELECT game_id, game_name FROM games WHERE school_id = ?";
$stmt = $conn->prepare($games_query);
$stmt->bind_param("i", $school_id);
$stmt->execute();
$games = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get all unique grade levels for filter
$grade_query = "SELECT DISTINCT grade_level FROM brackets WHERE grade_level IS NOT NULL ORDER BY grade_level";
$grade_result = $conn->query($grade_query);
$grade_levels = [];
while ($row = $grade_result->fetch_assoc()) {
    if ($row['grade_level']) {
        $grade_levels[] = $row['grade_level'];
    }
}

include '../navbar/navbar.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Tournament Brackets</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/bracket-style.css">
    <link rel="stylesheet" href="../styles/committee.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../styles/dashboard.css">

<style>
        .tournament-bracket {
            position: relative;
            display: flex;
            padding: 20px;
            overflow-x: auto;
            min-height: 500px;
        }

        .round {
            flex: 0 0 220px;
            margin: 0 15px;
            position: relative;
        }

        .round-header {
            text-align: center;
            margin-bottom: 15px;
            font-weight: bold;
            color: #495057;
            font-size: 0.9em;
        }

        .matches-wrapper {
            position: relative;
        }

        .match {
            position: absolute;
            width: 200px;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .team {
            padding: 8px 12px;
            border-bottom: 1px solid #dee2e6;
            background: #f8f9fa;
            display: flex;
            align-items: center;
        }

        .team:last-child {
            border-bottom: none;
        }

        .team.winner {
            background: #e8f5e9;
            font-weight: bold;
        }

        .team-name {
            flex-grow: 1;
            margin-right: 8px;
            font-size: 0.9em;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .team-score {
            font-weight: bold;
            margin-right: 4px;
            font-size: 0.9em;
        }

        .winner-check {
            color: #4caf50;
            font-size: 0.9em;
        }

        .third-place-match {
            position: absolute;
            right: 40px;
            bottom: 40px;
            width: 200px;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .match-label {
            text-align: center;
            font-size: 0.8em;
            color: #6c757d;
            padding: 4px;
            border-top: 1px solid #dee2e6;
            background: #f8f9fa;
        }

        .bracket-empty {
            text-align: center;
            padding: 40px;
            color: #6c757d;
            background: #f8f9fa;
            border-radius: 4px;
            margin: 20px 0;
        }

        .bracket-empty i {
            font-size: 48px;
            margin-bottom: 20px;
            color: #dee2e6;
        }

        .bracket-empty p {
            margin: 0;
            font-size: 1.1em;
        }
    </style>
</head>

<body>
    <nav>
        <?php include '../department_admin/sidebar.php'; ?>
    </nav>

    <div id="content">
        <div class="container mt-4">
            <div class="row mb-4">
                <div class="col">
                    <h2>View Tournament Brackets</h2>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="filter-section">
                <div class="row">
                    <div class="col-md-3">
                        <label for="departmentFilter" class="form-label">Department</label>
                        <select class="form-select" id="departmentFilter">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                                <option value="<?php echo $dept['id']; ?>">
                                    <?php echo htmlspecialchars($dept['department_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="gameFilter" class="form-label">Game</label>
                        <select class="form-select" id="gameFilter">
                            <option value="">All Games</option>
                            <?php foreach ($games as $game): ?>
                                <option value="<?php echo $game['game_id']; ?>">
                                    <?php echo htmlspecialchars($game['game_name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="gradeLevelFilter" class="form-label">Grade Level</label>
                        <select class="form-select" id="gradeLevelFilter">
                            <option value="">All Grade Levels</option>
                            <?php foreach ($grade_levels as $grade): ?>
                                <option value="<?php echo htmlspecialchars($grade); ?>">
                                    <?php echo htmlspecialchars($grade); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button class="btn btn-primary w-100" onclick="applyFilters()">Apply Filters</button>
                    </div>
                </div>
            </div>

            <!-- Existing Brackets Table -->
            <div class="row">
                <div class="col">
                    <div class="table-responsive">
                        <table class="table table-striped table-bordered">
                            <thead>
                                <tr>
                                    <th>Game</th>
                                    <th>Department</th>
                                    <th>Grade Level</th>
                                    <th>Total Teams</th>
                                    <th>Status</th>
                                    <th>Created At</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody id="bracketsTableBody">
                                <!-- Table content will be loaded dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Bracket Display Section -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Tournament Bracket</h3>
                    <button id="backToBrackets" class="btn btn-secondary" onclick="showBracketList()" style="display: none;">
                        <i class="fas fa-times"></i> Close
                    </button>
                </div>
                <div class="card-body">
                    <div id="bracket-container">
                        <!-- Bracket will be displayed here -->
                        <div class="bracket-empty">
                            <i class="fas fa-trophy"></i>
                            <p>Select a bracket from the table to view it.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function loadBrackets(filters = {}) {
            $.ajax({
                url: 'fetch_admin_brackets.php',
                method: 'GET',
                data: filters,
                success: function(response) {
                    if (response.success) {
                        const brackets = response.data;
                        const tbody = $('#bracketsTableBody');
                        tbody.empty();

                        if (brackets.length === 0) {
                            tbody.append('<tr><td colspan="7" class="text-center">No brackets found</td></tr>');
                            return;
                        }

                        brackets.forEach(bracket => {
                            const row = `
                                <tr>
                                    <td>${bracket.game_name}</td>
                                    <td>${bracket.department_name}</td>
                                    <td>${bracket.grade_level || 'N/A'}</td>
                                    <td>${bracket.total_teams}</td>
                                    <td>${bracket.status}</td>
                                    <td>${new Date(bracket.created_at).toLocaleDateString()}</td>
                                    <td>
                                        <button class="btn btn-primary btn-sm" onclick="viewBracket(${bracket.bracket_id})">
                                            View Bracket
                                        </button>
                                    </td>
                                </tr>
                            `;
                            tbody.append(row);
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Failed to load brackets'
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading brackets:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load brackets. Please try again.'
                    });
                }
            });
        }

        function applyFilters() {
            const filters = {
                department_id: $('#departmentFilter').val(),
                game_id: $('#gameFilter').val(),
                grade_level: $('#gradeLevelFilter').val()
            };
            loadBrackets(filters);
        }

        function viewBracket(bracketId) {
            // Hide the empty bracket message if it exists
            $('.bracket-empty').hide();

            // Show loading state
            $('#bracket-container').html(`
                <div class="loading">
                    <i class="fas fa-spinner fa-spin"></i>
                    <p>Loading bracket...</p>
                </div>
            `);

            // Show back button
            $('#backToBrackets').show();

            // Load bracket data
            $.ajax({
                url: 'fetch_bracket.php',
                method: 'POST',
                data: {
                    bracket_id: bracketId
                },
                success: function(response) {
                    if (response.success) {
                        const rounds = response.matches;
                        let bracketHTML = '<div class="tournament-bracket">';

                        // Calculate total rounds for spacing
                        const numRounds = Object.keys(rounds).filter(key => !isNaN(key)).length;
                        const firstRoundMatches = rounds[1] ? rounds[1].length : 0;
                        const totalHeight = firstRoundMatches * 100;

                        // Create rounds
                        Object.keys(rounds).forEach((roundIndex, index) => {
                            if (roundIndex !== 'third-place') {
                                bracketHTML += `
                                    <div class="round">
                                        <div class="round-header">${getRoundName(index, numRounds - 1)}</div>
                                        <div class="matches-wrapper" style="height: ${totalHeight}px">
                                `;

                                const matchesInRound = rounds[roundIndex].length;
                                const spacing = totalHeight / matchesInRound;

                                rounds[roundIndex].forEach((match, matchIndex) => {
                                    const position = (spacing * matchIndex) + (spacing - 80) / 2;
                                    bracketHTML += createMatchHTML(match, roundIndex, matchIndex, position);
                                });

                                bracketHTML += '</div></div>';
                            }
                        });

                        // Add third place match if it exists
                        if (rounds['third-place']) {
                            bracketHTML += createMatchHTML(rounds['third-place'], 'third-place', 0, null, true);
                        }

                        bracketHTML += '</div>';
                        $('#bracket-container').html(bracketHTML);

                        // Scroll to the bracket
                        $('html, body').animate({
                            scrollTop: $('#bracket-container').offset().top - 20
                        }, 500);
                    } else {
                        $('#bracket-container').html(`
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i>
                                ${response.message || 'Failed to load bracket'}
                            </div>
                        `);
                    }
                },
                error: function() {
                    $('#bracket-container').html(`
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle"></i>
                            Failed to load bracket. Please try again.
                        </div>
                    `);
                }
            });
        }

        function showBracketList() {
            // Hide back button
            $('#backToBrackets').hide();

            // Clear the bracket container and show the empty message
            $('#bracket-container').html(`
                <div class="bracket-empty">
                    <i class="fas fa-trophy"></i>
                    <p>Select a bracket from the table to view it.</p>
                </div>
            `);
        }

        function getRoundName(index, totalRounds) {
            if (index === totalRounds) return 'Finals';
            if (index === totalRounds - 1) return 'Semifinals';
            if (index === totalRounds - 2) return 'Quarterfinals';
            return `Round ${index + 1}`;
        }

        function createMatchHTML(match, roundIndex, matchIndex, position, isThirdPlace = false) {
            const matchClass = isThirdPlace ? 'third-place-match' : 'match';
            const style = position !== null ? `style="top: ${position}px"` : '';

            // Check if match is finished and has a winner
            const isFinished = match.status === 'Finished';
            const teamAWinner = isFinished && parseInt(match.winning_team_id) === parseInt(match.teamA_id);
            const teamBWinner = isFinished && parseInt(match.winning_team_id) === parseInt(match.teamB_id);

            return `
                <div class="${matchClass}" ${style} data-match-id="${match.match_id}">
                    <div class="team ${match.teamA_id === 0 ? 'team-tbd' : ''} ${teamAWinner ? 'winner' : ''} team-top">
                        <span class="team-name">${match.teamA_name || '---'}</span>
                        ${isFinished ? `<span class="team-score">${match.score_teamA || '0'}</span>` : ''}
                        ${teamAWinner ? '<span class="winner-check">✓</span>' : ''}
                    </div>
                    <div class="team ${match.teamB_id === 0 ? 'team-tbd' : ''} ${teamBWinner ? 'winner' : ''} team-bottom">
                        <span class="team-name">${match.teamB_name || '---'}</span>
                        ${isFinished ? `<span class="team-score">${match.score_teamB || '0'}</span>` : ''}
                        ${teamBWinner ? '<span class="winner-check">✓</span>' : ''}
                    </div>
                    ${isThirdPlace ? '<div class="match-label">Third Place Match</div>' : ''}
                </div>
            `;
        }

        // Load brackets when page loads
        $(document).ready(function() {
            loadBrackets();
        });
    </script>
</body>

</html>