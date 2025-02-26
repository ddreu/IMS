<?php
session_start();
require_once '../connection/conn.php';
$conn = con();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Retrieve session data
$department_name = $_SESSION['department_name'] ?? null;
$department_id = $_SESSION['department_id'] ?? null;
$school_id = $_SESSION['school_id'] ?? null;
$game_id = $_SESSION['game_id'] ?? null;
$grade_level = $_SESSION['grade_level'] ?? null;

if (!$department_id || !$school_id) {
    die('Error: Required session data is missing.');
}

// Fetch game details
$gameQuery = "SELECT game_name FROM games WHERE game_id = ?";
$gameStmt = $conn->prepare($gameQuery);
$gameStmt->bind_param("i", $game_id);
$gameStmt->execute();
$result = $gameStmt->get_result();
$game = $result->fetch_assoc();

if (!$game) {
    die('Error: Game not found.');
}

// Get department info
$department_query = "SELECT * FROM departments WHERE id = ?";
$stmt = $conn->prepare($department_query);
$stmt->bind_param("i", $department_id);
$stmt->execute();
$department = $stmt->get_result()->fetch_assoc();

// Get grade levels if not college
$grade_levels = [];
if ($department['department_name'] !== 'College') {
    $grade_query = "SELECT DISTINCT gsc.grade_level 
                    FROM grade_section_course gsc 
                    WHERE gsc.department_id = ? 
                    ORDER BY gsc.grade_level";
    $stmt = $conn->prepare($grade_query);
    $stmt->bind_param("i", $department_id);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
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
    <title>Tournament Brackets - <?php echo htmlspecialchars($game['game_name']); ?></title>
    <!-- ✅ Load Bootstrap CSS First -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- ✅ Other Styles -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <link rel="stylesheet" href="../styles/committee.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/jquery-bracket/0.11.1/jquery.bracket.min.css" rel="stylesheet">
    <style>
        /* Loading and error states */
        .bracket-loading,
        .bracket-error {
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px;
        }

        .jQBracket .tools {
            display: none !important;
        }




        /* Container wrapper to force scrolling */
        .bracket-wrapper {
            width: 100%;
            /* Use viewport width */
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 20px;
            margin-left: -15px;
            /* Compensate for container padding */
            margin-right: -15px;
        }


        /* Bracket container styles */
        #bracket-container {
            white-space: nowrap;
            padding: 20px 0;
            width: 100%;
            min-height: 500px;
            /* Ensure minimum height */
            margin-left: -18%;
        }



        /* Ensure the bracket fits within its container */
        .jQBracket {
            min-width: fit-content;
            margin: 0 auto;
            font-size: 12px;
            /* Slightly reduce font size */
        }



        .jQBracket.doubleElimination {
            display: flex !important;
            flex-direction: row !important;
            align-items: flex-start !important;
            margin: 0 !important;
            padding: 20px 0;
        }

        /*
        .jQBracket .bracket,
        .jQBracket .loserBracket,
        .jQBracket .finals {
            float: none !important;
            clear: none !important;
            position: relative !important;
            margin-left: 0 !important;
        }

        
        .jQBracket.doubleElimination .loserBracket {
            margin-top: 30px;
        }

        .jQBracket.doubleElimination .finals {
            margin-top: 0;
            padding-left: 0;
        }

        .jQBracket .team {
            width: 120px !important;
        }

        .jQBracket .round {
            margin-right: 15px;
        }

        .jQBracket .connector {
            border-color: #666 !important;
        }

        .jQBracket.doubleElimination>div {
            display: flex !important;
            gap: 20px;
        } */
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>

<body>
    <nav>
        <?php include '../committee/csidebar.php'; ?>
    </nav>

    <div class="main">
        <?php include 'round-robin_modal.php'; ?>

        <div class="container mt-4">
            <div class="row mb-4">
                <div class="col">
                    <h2><?php echo htmlspecialchars($game['game_name']); ?> Tournament Brackets</h2>
                    <p>Department: <?php echo htmlspecialchars($department_name); ?></p>
                    <?php if ($grade_level): ?>
                        <p>Grade Level: <?php echo htmlspecialchars($grade_level); ?></p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Existing Brackets Section -->
            <?php
            // Fetch existing brackets for this game/department
            $bracketQuery = "SELECT 
                b.*, 
                d.department_name,
                g.game_name,
                (
                    SELECT COUNT(DISTINCT CASE 
                        WHEN m.teamA_id > 0 THEN m.teamA_id 
                        ELSE NULL 
                    END) + 
                    COUNT(DISTINCT CASE 
                        WHEN m.teamB_id > 0 THEN m.teamB_id 
                        ELSE NULL 
                    END)
                    FROM matches m 
                    WHERE m.bracket_id = b.bracket_id
                    AND m.round = 1  -- Only count teams from first round
                    AND m.match_type = 'regular'  -- Only regular matches, not finals/third place
                ) as total_teams,
                COUNT(m.match_id) as total_matches,
                b.bracket_type 
                FROM brackets b 
                LEFT JOIN matches m ON b.bracket_id = m.bracket_id 
                LEFT JOIN departments d ON b.department_id = d.id
                LEFT JOIN games g ON b.game_id = g.game_id
                WHERE b.game_id = ? AND b.department_id = ? 
                GROUP BY b.bracket_id 
                ORDER BY b.created_at DESC";
            $bracketStmt = $conn->prepare($bracketQuery);
            $bracketStmt->bind_param("ii", $game_id, $department_id);
            $bracketStmt->execute();
            $bracketResult = $bracketStmt->get_result();
            $existingBrackets = [];
            while ($row = $bracketResult->fetch_assoc()) {
                $existingBrackets[] = $row;
            }
            ?>
            <?php if (!empty($existingBrackets)): ?>
                <div class="row mb-4">
                    <div class="col">
                        <h3>Existing Brackets</h3>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Game</th>
                                        <th>Department</th>
                                        <?php if ($department_name !== 'College'): ?>
                                            <th>Grade Level</th>
                                        <?php endif; ?>
                                        <th>Type</th>
                                        <th>Total Teams</th>
                                        <th>Rounds</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($existingBrackets as $bracket): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($bracket['game_name']); ?></td>
                                            <td><?php echo htmlspecialchars($bracket['department_name']); ?></td>
                                            <?php if ($department_name !== 'College'): ?>
                                                <td><?php echo htmlspecialchars($bracket['grade_level'] ?? 'N/A'); ?></td>
                                            <?php endif; ?>
                                            <td><?php echo ucwords(preg_replace('/[^a-zA-Z0-9 ]/', '', $bracket['bracket_type'])); ?></td>
                                            <td><?php echo $bracket['total_teams']; ?></td>
                                            <td><?php echo $bracket['rounds']; ?></td>
                                            <td><?php echo ucfirst($bracket['status']); ?></td>
                                            <td>
                                                <?php if ($bracket['bracket_type'] === 'round_robin'): ?>
                                                    <button class="btn btn-primary btn-sm view-round-robin"
                                                        data-bracket-id="<?php echo $bracket['bracket_id']; ?>">
                                                        <i class="fas fa-table"></i> View Schedule
                                                    </button>
                                                <?php else: ?>
                                                    <button class="btn btn-primary btn-sm view-bracket"
                                                        data-bracket-id="<?php echo $bracket['bracket_id']; ?>">
                                                        <i class="fas fa-sitemap"></i> View Bracket
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-danger btn-sm" onclick="deleteBracket(<?php echo $bracket['bracket_id']; ?>)">
                                                    <i class="fas fa-trash"></i> Delete
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- New Bracket Generation Section -->
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <h4>Create New Bracket</h4>
                                <button type="button" id="generate-bracket" class="btn btn-primary mt-3">Generate Bracket</button>

                            </div>
                            <div class="card-body">
                                <form id="bracketForm">
                                    <?php if ($department['department_name'] !== 'College'): ?>
                                        <div class="form-group">
                                            <label for="gradeLevel">Grade Level:</label>
                                            <select class="form-control" id="gradeLevelSelect" name="gradeLevel">
                                                <option value="">All Grade Levels</option>
                                                <?php foreach ($grade_levels as $grade): ?>
                                                    <option value="<?php echo htmlspecialchars($grade); ?>">
                                                        <?php echo htmlspecialchars($grade); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    <?php endif; ?>
                                    <div class="form-group">
                                        <label for="bracketType">Bracket Type:</label>
                                        <select class="form-control" id="bracketTypeSelect" name="bracketType">
                                            <option value="single">Single Elimination</option>
                                            <option value="double">Double Elimination</option>
                                            <option value="single_round_robin">Single Round Robin</option>

                                        </select>
                                    </div>
                                </form>

                                <!-- Add a loading state -->
                                <div id="bracket-loading" class="bracket-loading d-none">
                                    <div class="spinner-border" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </div>
                                <!-- Bracket container -->
                                <div class="bracket-wrapper">

                                    <div id="bracket-content"></div>
                                </div>

                                <div id="bracket-container"></div>
                                <button id="save-bracket" class="btn btn-success mb-3">Save Bracket</button>

                            </div>


                        </div>
                        <!-- ✅ Load jQuery First (Required for jQuery Bracket) -->
                        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

                        <!-- ✅ Load jQuery Bracket AFTER jQuery -->
                        <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-bracket/0.11.1/jquery.bracket.min.js"></script>

                        <!-- ✅ Load Bootstrap JS at the Very End -->
                        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

                        <!-- ✅ Load Your Custom Scripts Last -->
                        <script src="bracket-manager.js"></script>
                        <script src="bracket-state.js"></script>

                        <script src="double-bracket-manager.js"></script>
                        <?php include 'handler.php'; ?>

                        <script>
                            $(document).ready(function() {
                                // Initialize bracket managers as null
                                window.singleBracketManager = null;
                                window.currentBracketManager = null;

                                // Verify jQuery and plugins
                                if (typeof jQuery === 'undefined') {
                                    console.error('jQuery is not loaded');
                                } else {
                                    console.log('jQuery version:', jQuery.fn.jquery);
                                }

                                if (typeof jQuery.fn.bracket === 'undefined') {
                                    console.error('jQuery bracket plugin is not loaded');
                                } else {
                                    console.log('jQuery bracket plugin is loaded');
                                }
                            });
                        </script>
                    </div>
                </div>
            </div>
        </div>


        <script>
            // Ensure view bracket functionality works after page load
            $(document).ready(function() {
                // Rebind click event for view-bracket buttons
                $('.view-bracket').off('click').on('click', function() {
                    const bracketId = $(this).data('bracket-id');
                    console.log('View Bracket clicked:', bracketId);

                    $('#bracket-content').empty(); // Clear the content


                    // Fetch bracket data
                    fetch('fetch_bracket.php?bracket_id=' + bracketId)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Clear existing bracket
                                $('#bracket-container').empty();

                                // ENHANCED DEBUGGING
                                console.group('Bracket Data Validation');
                                console.log('Raw data from server:', JSON.parse(JSON.stringify(data)));

                                // Validate basic data structure
                                if (!data.success) {
                                    console.error('Fetch failed:', data.message);
                                    $('#bracket-container').html(`
                                            <div class="alert alert-danger">
                                                Failed to load bracket: ${data.message || 'Unknown error'}
                                            </div>
                                        `);
                                    return;
                                }

                                // Validate matches object
                                if (!data.matches || typeof data.matches !== 'object') {
                                    console.error('Invalid matches structure:', data.matches);
                                    $('#bracket-container').html(`
                                            <div class="alert alert-warning">
                                                No valid matches found in bracket data.
                                            </div>
                                        `);
                                    return;
                                }

                                // Detailed matches logging
                                console.log('Matches Object Keys:', Object.keys(data.matches));
                                Object.keys(data.matches)
                                    .filter(key => !isNaN(parseInt(key)))
                                    .forEach(round => {
                                        console.log(`Round ${round} Matches:`, data.matches[round]);
                                    });

                                // Validate third-place match if exists
                                if (data.matches['third-place']) {
                                    console.log('Third Place Match:', data.matches['third-place']);
                                }

                                // Get all round numbers except 'third-place'
                                const roundNumbers = Object.keys(data.matches)
                                    .filter(key => key !== 'third-place' && !isNaN(parseInt(key)))
                                    .sort((a, b) => parseInt(a) - parseInt(b));

                                console.log('Sorted Round Numbers:', roundNumbers);

                                // Detailed round validation
                                roundNumbers.forEach(round => {
                                    const roundMatches = data.matches[round];
                                    console.group(`Round ${round} Validation`);
                                    console.log('Total Matches:', roundMatches.length);

                                    roundMatches.forEach((match, index) => {
                                        console.log(`Match ${index + 1} Details:`, {
                                            matchId: match.match_id,
                                            matchNumber: match.match_number,
                                            teamA: {
                                                id: match.teamA_id,
                                                name: match.teamA_name
                                            },
                                            teamB: {
                                                id: match.teamB_id,
                                                name: match.teamB_name
                                            },
                                            score: {
                                                teamA: match.score_teamA,
                                                teamB: match.score_teamB
                                            },
                                            status: match.status,
                                            winningTeam: match.winning_team_id
                                        });
                                    });
                                    console.groupEnd();
                                });

                                console.groupEnd();

                                // Calculate the total number of teams in first round
                                const totalTeams = data.matches['1'] ? data.matches['1'].length * 2 : 0;

                                // Initialize empty teams array
                                const teams = [];

                                // Fill teams array with actual teams or empty slots
                                if (data.matches['1'] && data.matches['1'].length > 0) {
                                    data.matches['1'].forEach(match => {
                                        teams.push([
                                            match.teamA_name || 'TBD',
                                            match.teamB_name || 'TBD'
                                        ]);
                                    });
                                }

                                console.log('Teams array:', teams);

                                // Initialize results array with proper structure
                                // Each round should have the correct number of matches
                                const results = [];
                                let matchesInRound = Math.floor(totalTeams / 2);

                                roundNumbers.forEach(roundNum => {
                                    const roundResults = [];
                                    const roundMatches = data.matches[roundNum] || [];

                                    // Fill this round with the correct number of matches
                                    for (let i = 0; i < matchesInRound; i++) {
                                        const match = roundMatches[i];
                                        if (match) {
                                            // If we have actual match data
                                            if (match.status === 'Finished' &&
                                                match.score_teamA !== null &&
                                                match.score_teamB !== null) {
                                                roundResults.push([
                                                    parseInt(match.score_teamA) || 0,
                                                    parseInt(match.score_teamB) || 0
                                                ]);
                                            } else if (match.winning_team_id !== null) {
                                                roundResults.push(
                                                    match.winning_team_id === match.teamA_id ? [1, 0] : [0, 1]
                                                );
                                            } else if (match.teamA_id === -1 || match.teamB_id === -1) {
                                                roundResults.push(
                                                    match.teamA_id === -1 ? [0, 1] : [1, 0]
                                                );
                                            } else {
                                                roundResults.push([0, 0]);
                                            }
                                        } else {
                                            // Fill with empty match result
                                            roundResults.push([0, 0]);
                                        }
                                    }

                                    results.push(roundResults);
                                    // Number of matches in next round is half of current round
                                    matchesInRound = Math.floor(matchesInRound / 2);
                                });

                                console.log('Final results array:', results);

                                // Ensure we have at least one team
                                if (teams.length === 0) {
                                    console.error('No teams available');
                                    $('#bracket-container').html('<div class="alert alert-warning">No teams available for the bracket.</div>');
                                    return;
                                }

                                const bracketData = {
                                    teams: teams,
                                    results: results
                                };

                                console.log('Final bracket data:', bracketData);

                                // Initialize the bracket with current state
                                $('#bracket-container').bracket({
                                    teamWidth: 150,
                                    scoreWidth: 40,
                                    matchMargin: 50,
                                    roundMargin: 50,
                                    init: bracketData,
                                    save: function() {}, // Read-only mode
                                    decorator: {
                                        edit: function() {}, // Disable editing
                                        render: function(container, data, score, state) {
                                            container.empty();
                                            if (data === null) {
                                                container.append("BYE");
                                            } else if (data === "TBD") {
                                                container.append("TBD");
                                            } else {
                                                container.append(data);
                                                if (score !== undefined && score !== null) {
                                                    container.append($('<div>', {
                                                        class: 'score',
                                                        text: score
                                                    }));
                                                }
                                            }
                                        }
                                    }
                                });
                                // Add back button if it doesn't exist
                                if ($('#backToBrackets').length === 0) {
                                    const backButton = $('<button>', {
                                        id: 'backToBrackets',
                                        class: 'btn btn-secondary mt-3',
                                        html: '<span><i class="fas fa-times"></i></span>'
                                    }).click(function() {
                                        showBracketList();
                                    });
                                    $('#bracket-container').before(backButton);
                                }


                                // Hide the generate button
                                $('#generate-bracket').hide();

                            } else {
                                console.error('Error loading bracket:', data.message);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Failed to load bracket: ' + data.message
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to load bracket. Please try again.'
                            });
                        });
                });
            });
        </script>
        <script>
            function showBracketList() {
                // Show the generate button and hide back button
                $('#generate-bracket').show();
                $('#backToBrackets').remove();

                // Show the bracket actions
                $('#bracketActions').show();

                $('#bracket-container').html(`
            <div class="text-center p-5">
                <h4>Welcome to Tournament Bracket Generator</h4>
                <p>Select a bracket type and click "Generate Bracket" to begin.</p>
            </div>
        `);

                // Show the bracket list table
                $('#bracketListContainer').show();

                // Reload the bracket list
                loadBrackets();
            }



            function deleteBracket(bracketId) {
                Swal.fire({
                    title: 'Are you sure?',
                    text: "This will permanently delete this bracket and all its matches. This action cannot be undone!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Yes, delete it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            url: 'delete_bracket.php',
                            method: 'POST',
                            data: {
                                bracket_id: bracketId
                            },
                            success: function(response) {
                                if (response.success) {
                                    Swal.fire({
                                        title: 'Success!',
                                        text: 'Bracket has been deleted successfully.',
                                        icon: 'success'
                                    }).then(() => {
                                        // Reload the page to show the saved bracket
                                        window.location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        title: 'Error!',
                                        text: response.message,
                                        icon: 'error'
                                    });
                                }
                            },
                            error: function() {
                                Swal.fire({
                                    title: 'Error!',
                                    text: 'Failed to delete bracket. Please try again.',
                                    icon: 'error'
                                });
                            }
                        });
                    }
                });
            }

            function loadBrackets() {
                $.ajax({
                    url: 'fetch_bracket.php',
                    method: 'GET',
                    data: {
                        game_id: <?php echo $game_id; ?>,
                        dept_id: <?php echo $department_id; ?>
                    },
                    success: function(response) {
                        if (response.success) {
                            const brackets = response.brackets;
                            let tableBody = '';

                            brackets.forEach(function(bracket) {
                                tableBody += `
                                        <tr>
                                            <td>${bracket.bracket_name}</td>
                                            <td>${bracket.created_at}</td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="viewBracket(${bracket.bracket_id})">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger" onclick="deleteBracket(${bracket.bracket_id})">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    `;
                            });

                            if (brackets.length === 0) {
                                tableBody = '<tr><td colspan="3" class="text-center">No brackets found</td></tr>';
                            }

                            $('#bracketTableBody').html(tableBody);
                        } else {
                            console.error('Failed to load brackets:', response.message);
                        }
                    },
                    error: function() {
                        console.error('Failed to load brackets');
                    }
                });
            }
        </script>
        <script>
            $('#save-bracket').on('click', async function() {
                try {
                    const bracketType = $('#bracketTypeSelect').val();
                    const bracketManager = bracketType === 'double' ?
                        window.currentBracketManager :
                        window.singleBracketManager;

                    if (!bracketManager) {
                        throw new Error('No bracket manager available');
                    }

                    const bracketState = bracketManager.getCurrentBracketState();
                    if (!bracketState) {
                        throw new Error('No bracket structure available');
                    }

                    // Add bracket type to the state
                    bracketState.bracket_type = bracketType;

                    await bracketManager.saveBracket(bracketState);

                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Bracket saved successfully!'
                    });
                } catch (error) {
                    console.error('Error saving bracket:', error);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to save bracket: ' + error.message
                    });
                }
            });
        </script>
        <script>
            // Replace the existing round robin view handler
            $('.view-round-robin').on('click', function() {
                const bracketId = $(this).data('bracket-id');

                // Show loading state
                Swal.fire({
                    title: 'Loading...',
                    text: 'Fetching tournament schedule',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Fetch round robin data
                fetch('fetch_round_robin.php?bracket_id=' + bracketId)
                    .then(response => {
                        console.log('Raw response:', response);
                        return response.json();
                    })
                    .then(data => {
                        console.log('Parsed data:', data);
                        Swal.close();

                        if (data.success) {
                            // Populate tournament info
                            $('#tournamentGame').text(data.tournament_info.game_name);
                            $('#tournamentDept').text(data.tournament_info.department_name);
                            $('#tournamentStatus').text(data.tournament_info.status);
                            $('#tournamentTeams').text(data.tournament_info.total_teams);

                            // Populate scoring rules inputs
                            if (data.scoring_rules) {
                                $('#viewWinPoints').val(data.scoring_rules.win_points);
                                $('#viewDrawPoints').val(data.scoring_rules.draw_points);
                                $('#viewLossPoints').val(data.scoring_rules.loss_points);
                                $('#viewBonusPoints').val(data.scoring_rules.bonus_points);
                            }

                            // Populate standings table
                            const standingsBody = $('#standingsTableBody');
                            standingsBody.empty();

                            let rank = 1;
                            data.standings.forEach((team) => {
                                const rankEmoji = rank === 1 ? '1️⃣' : rank === 2 ? '2️⃣' : rank === 3 ? '3️⃣' : rank;
                                const rowClass = rank === 1 ? 'table-success' :
                                    rank === 2 ? 'table-info' :
                                    rank === 3 ? 'table-warning' : '';
                                standingsBody.append(`
                                        <tr class="${rowClass}">
                                            <td>${rankEmoji}</td>
                                            <td><strong>${team.team_name}</strong></td>
                                            <td>${team.played}</td>
                                            <td>${team.wins}</td>
                                            <td>${team.draw}</td>
                                            <td>${team.losses}</td>
                                            <td>${team.bonus_points}</td>
                                            <td><strong>${team.total_points}</strong></td>
                                            <td>
                                                <button class="btn btn-sm btn-success add-bonus" 
                                                        data-team-id="${team.team_id}" 
                                                        data-bracket-id="${bracketId}">
                                                    <i class="fas fa-plus"></i> Add Bonus
                                                </button>
                                            </td>
                                        </tr>
                                    `);
                                rank++;
                            });

                            // Add bonus points handler
                            $(document).on('click', '.add-bonus', async function() {
                                const teamId = $(this).data('team-id');
                                const bracketId = $(this).data('bracket-id');

                                const {
                                    value: bonusPoints
                                } = await Swal.fire({
                                    title: 'Add Bonus Points',
                                    input: 'number',
                                    inputLabel: 'Enter bonus points',
                                    inputValue: 0,
                                    showCancelButton: true,
                                    inputValidator: (value) => {
                                        if (!value) {
                                            return 'Please enter a number';
                                        }
                                    }
                                });

                                if (bonusPoints) {
                                    try {
                                        const response = await fetch('save_round_robin_points.php', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json'
                                            },
                                            body: JSON.stringify({
                                                bracket_id: bracketId,
                                                team_id: teamId,
                                                bonus_points: parseInt(bonusPoints),
                                                action: 'add_bonus'
                                            })
                                        });

                                        const result = await response.json();

                                        if (result.success) {
                                            Swal.fire({
                                                icon: 'success',
                                                title: 'Success',
                                                text: 'Bonus points added successfully!'
                                            }).then(() => {
                                                // Refresh the standings
                                                $('.view-round-robin[data-bracket-id="' + bracketId + '"]').click();
                                            });
                                        } else {
                                            throw new Error(result.message);
                                        }
                                    } catch (error) {
                                        console.error('Error adding bonus points:', error);
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error',
                                            text: 'Failed to add bonus points: ' + error.message
                                        });
                                    }
                                }
                            });

                            // Populate matches table
                            const matchesBody = $('#matchesTableBody');
                            matchesBody.empty();

                            data.matches.forEach(match => {
                                const scores = match.status.toLowerCase() === 'finished' ?
                                    `${match.score_teamA || '-'} - ${match.score_teamB || '-'}` :
                                    '-';

                                matchesBody.append(`
                                        <tr>
                                            <td>Round ${match.round}</td>
                                            <td>Match ${match.match_number}</td>
                                            <td>${match.teamA_name || 'TBD'}</td>
                                            <td>${scores}</td>
                                            <td>${match.teamB_name || 'TBD'}</td>
                                            <td>
                                                <span class="badge bg-${match.status.toLowerCase() === 'pending' ? 'warning' : 'success'}">
                                                    ${match.status}
                                                </span>
                                            </td>
                                        </tr>
                                    `);
                            });

                            // Add save points handler
                            $('#savePoints').off('click').on('click', async function() {
                                try {
                                    const response = await fetch('save_round_robin_points.php', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json'
                                        },
                                        body: JSON.stringify({
                                            bracket_id: bracketId,
                                            win_points: parseInt($('#viewWinPoints').val()) || 0,
                                            draw_points: parseInt($('#viewDrawPoints').val()) || 0,
                                            loss_points: parseInt($('#viewLossPoints').val()) || 0,
                                            bonus_points: parseInt($('#viewBonusPoints').val()) || 0
                                        })
                                    });

                                    const result = await response.json();

                                    if (result.success) {
                                        Swal.fire({
                                            icon: 'success',
                                            title: 'Success',
                                            text: 'Scoring points updated successfully!'
                                        });
                                    } else {
                                        throw new Error(result.message);
                                    }
                                } catch (error) {
                                    console.error('Error saving points:', error);
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: 'Failed to update scoring points: ' + error.message
                                    });
                                }
                            });

                            // Show the modal
                            const modal = new bootstrap.Modal(document.getElementById('viewRoundRobinModal'));
                            modal.show();
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.message || 'Failed to load tournament schedule'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Detailed error:', error);
                        Swal.close();
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to load tournament schedule. Error: ' + error.message
                        });
                    });
            });
        </script>
    </div>
    </div>

</body>

</html>