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
    <script src="https://d3js.org/d3.v7.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-bracket/0.11.1/jquery.bracket.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/jquery-bracket/0.11.1/jquery.bracket.min.css" rel="stylesheet">

<style>
    /* Bracket Container Styles */
    #bracket-container {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin: 20px auto;
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    /* Force minimum dimensions to maintain structure */
    .jQBracket {
        min-width: 1000px !important; /* Ensure minimum width */
        min-height: 500px !important; /* Ensure minimum height */
        padding: 40px 20px !important;
        font-size: 14px !important;
    }

    .jQBracket .tools {
        display: none !important; /* Hide tools to prevent structure breaking */
    }

    /* Maintain round spacing */
    .jQBracket .round {
        margin-right: 50px !important;
        min-width: 150px !important;
    }

    .jQBracket .round:last-child {
        margin-right: 20px !important;
    }

    /* Team and match box sizing */
    .jQBracket .team {
        width: 200px !important;
        height: 35px !important;
        background-color: #ffffff !important;
        border: 1px solid #e0e0e0 !important;
        border-radius: 4px !important;
        margin: 2px 0 !important;
        display: flex !important;
        align-items: center !important;
    }

    /* Maintain connector lines */
    .jQBracket .connector {
        border-color: #ccc !important;
        border-width: 2px !important;
    }

    .jQBracket .connector.filled {
        border-color: #666 !important;
    }

    .jQBracket .connector div.connector {
        border-width: 2px !important;
    }

    /* Score styles */
    .jQBracket .score {
        min-width: 40px !important;
        padding: 3px 5px !important;
        background-color: #f8f9fa !important;
        border-left: 1px solid #e0e0e0 !important;
        text-align: center !important;
    }

    /* Team label styles */
    .jQBracket .label {
        width: calc(100% - 45px) !important; /* Account for score width */
        padding: 5px 10px !important;
        white-space: nowrap !important;
        overflow: hidden !important;
        text-overflow: ellipsis !important;
    }

    /* Container wrapper to force scrolling */
    .bracket-wrapper {
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        padding-bottom: 20px; /* Space for scrollbar */
    }

    /* Mobile specific adjustments */
    @media (max-width: 768px) {
        #bracket-container {
            padding: 10px 5px;
            margin: 10px 0;
        }

        .jQBracket {
            font-size: 12px !important;
            padding: 20px 10px !important;
        }

        /* Maintain minimum dimensions even on mobile */
        .jQBracket .team {
            min-width: 180px !important;
            height: 30px !important;
        }

        .jQBracket .label {
            padding: 3px 8px !important;
        }

        .jQBracket .score {
            min-width: 35px !important;
            padding: 3px !important;
        }
    }

    /* Loading and error states */
    .bracket-loading, .bracket-error {
        min-height: 200px;
        display: flex;
        align-items: center;
        justify-content: center;
        text-align: center;
        padding: 20px;
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
            <div id="bracket-container" class="mt-4"></div>
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
            $('#bracket-container').html('<div class="text-center"><div class="spinner-border" role="status"></div><div>Loading bracket...</div></div>');
            
            // Fetch bracket data
            fetch('fetch_bracket.php?bracket_id=' + bracketId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Clear existing bracket
                        $('#bracket-container').empty();
                        
                        // Convert matches data to teams array and results
                        let teams = [];
                        let results = [];
                        let matchData = {}; // Store match data for reference
                        
                        if (data.matches[1]) { // First round matches
                            teams = data.matches[1].map(match => {
                                // Store match data for later reference
                                matchData[`${match.round}-${match.match_number}`] = match;
                                return [
                                    match.teamA_id === -1 ? null : (match.teamA_id === -2 ? 'TBD' : match.teamA_name),
                                    match.teamB_id === -1 ? null : (match.teamB_id === -2 ? 'TBD' : match.teamB_name)
                                ];
                            });
                        }

                        // Process all rounds for results
                        const rounds = Math.max(...Object.keys(data.matches));
                        for (let round = 1; round <= rounds; round++) {
                            if (data.matches[round]) {
                                const roundResults = data.matches[round].map(match => {
                                    // Store match data
                                    matchData[`${match.round}-${match.match_number}`] = match;
                                    
                                    // If match is finished, show actual scores
                                    if (match.status === 'finished') {
                                        return [
                                            parseInt(match.score_teamA) || 0,
                                            parseInt(match.score_teamB) || 0
                                        ];
                                    }
                                    // For unfinished matches, show empty scores
                                    return [0, 0];
                                });
                                results.push(roundResults);
                            }
                        }

                        // Initialize bracket with loaded data
                        $('#bracket-container').bracket({
                            teamWidth: 150,
                            scoreWidth: 40,
                            matchMargin: 50,
                            roundMargin: 50,
                            centerConnectors: true,
                            init: {
                                teams: teams,
                                results: results
                            },
                            decorator: {
                                edit: function() {}, // Empty edit function for read-only teams
                                render: function(container, team, score, state) {
                                    container.css({
                                        'cursor': 'default',
                                        'background-color': '#f8f9fa'
                                    });
                                    
                                    // Clear the container first
                                    container.empty();
                                    
                                    if (team === null) {
                                        container.addClass('bye-team bye');
                                        container.append('BYE');
                                    } else {
                                        container.append(team);
                                    }
                                    
                                    // Get match data for this position
                                    const match = matchData[`${state.round + 1}-${state.match + 1}`];
                                    
                                    if (match && match.status !== 'finished' && team !== null) {
                                        // Create score input for unfinished matches
                                        const scoreInput = $('<input>')
                                            .addClass('score-input form-control form-control-sm')
                                            .attr('type', 'number')
                                            .attr('min', '0')
                                            .attr('data-match-id', match.match_id)
                                            .attr('data-team', state.pos === 0 ? 'A' : 'B')
                                            .val(score || 0)
                                            .css({
                                                'width': '40px',
                                                'display': 'inline-block',
                                                'padding': '2px',
                                                'height': 'auto'
                                            });
                                        
                                        container.append(scoreInput);
                                        
                                        // Add save button if both teams are present
                                        if (match.teamA_id && match.teamB_id && 
                                            state.pos === 1) { // Only add button once per match
                                            const saveBtn = $('<button>')
                                                .addClass('btn btn-sm btn-primary save-score')
                                                .attr('data-match-id', match.match_id)
                                                .text('Save')
                                                .css({
                                                    'margin-left': '5px',
                                                    'padding': '2px 5px'
                                                });
                                            container.append(saveBtn);
                                        }
                                    } else if (score !== undefined) {
                                        container.append('<div class="score">' + score + '</div>');
                                    }
                                }
                            }
                        });

                        // Handle score saving
                        $(document).on('click', '.save-score', function() {
                            const matchId = $(this).data('match-id');
                            const scoreA = $(`input[data-match-id="${matchId}"][data-team="A"]`).val();
                            const scoreB = $(`input[data-match-id="${matchId}"][data-team="B"]`).val();
                            
                            // Validate scores
                            if (scoreA === scoreB) {
                                alert('Scores cannot be equal. There must be a winner.');
                                return;
                            }
                            
                            fetch('update_match.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                },
                                body: JSON.stringify({
                                    match_id: matchId,
                                    score_teamA: parseInt(scoreA),
                                    score_teamB: parseInt(scoreB)
                                })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if (data.success) {
                                    // Reload the bracket to show updated scores
                                    viewBracket(bracketId);
                                } else {
                                    alert('Failed to update scores: ' + data.message);
                                }
                            })
                            .catch(error => {
                                console.error('Error:', error);
                                alert('Failed to update scores. Please try again.');
                            });
                        });
                    } else {
                        console.error('Error loading bracket:', data.message);
                        $('#bracket-container').html('<div class="alert alert-danger">Error loading bracket</div>');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    $('#bracket-container').html('<div class="alert alert-danger">Error loading bracket</div>');
                });
        }

        // Load brackets when page loads
        $(document).ready(function() {
            loadBrackets();
            // Wrap bracket container with wrapper div
            $('#bracket-container').wrap('<div class="bracket-wrapper"></div>');
        });
    </script>
</body>

</html>