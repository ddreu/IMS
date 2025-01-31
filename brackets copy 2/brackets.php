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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../styles/committee.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://d3js.org/d3.v7.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-bracket/0.11.1/jquery.bracket.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="bracket-manager.js"></script>
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
        <?php include '../committee/csidebar.php'; ?>
    </nav>

    <div class="main">
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
                COUNT(m.match_id) as total_matches 
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
                                            <td><?php echo $bracket['total_teams']; ?></td>
                                            <td><?php echo $bracket['rounds']; ?></td>
                                            <td><?php echo ucfirst($bracket['status']); ?></td>
                                            <td>
                                                <button class="btn btn-primary btn-sm view-bracket" data-bracket-id="<?php echo $bracket['bracket_id']; ?>">View</button>
                                                <button class="btn btn-danger btn-sm" onclick="deleteBracket(<?php echo $bracket['bracket_id']; ?>)">
                                                    Delete
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
                                                    Grade <?php echo htmlspecialchars($grade); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <?php endif; ?>
                                    <button type="button" id="generate-bracket" class="btn btn-primary mt-3">Generate Bracket</button>
                                </form>
                                
                                <!-- Bracket Container -->
                                <div class="container mt-4">
                                    <div class="row">
                                        <div class="col-12">
                                            <button id="save-bracket" class="btn btn-primary mb-3">Save Bracket</button>
                                        </div>
                                    </div>
                                    <div id="bracket-container"></div>
                                </div>

                                <script>
                                    let bracketManager;
                                    let generatedStructure;

                                    function createBracketPairings(teams, totalSlots) {
                                        // Create array of team indices for seeding
                                        let seeds = [];
                                        for (let i = 0; i < totalSlots; i++) {
                                            seeds[i] = i < teams.length ? i : -1; // -1 represents BYE
                                        }

                                        // Function to get proper seed position
                                        function getSeedPosition(seed, roundSize) {
                                            if (seed < 0) return -1; // BYE position
                                            
                                            // Standard tournament seeding pattern
                                            let position;
                                            if (seed % 2 === 0) {
                                                position = seed / 2;
                                            } else {
                                                position = roundSize - 1 - Math.floor(seed / 2);
                                            }
                                            return position;
                                        }

                                        // Create properly seeded pairs
                                        const pairs = [];
                                        for (let i = 0; i < totalSlots/2; i++) {
                                            pairs[i] = [null, null];
                                        }

                                        // Place teams according to seeding
                                        seeds.forEach((seedIndex, seed) => {
                                            if (seedIndex === -1) return; // Skip BYE positions
                                            const position = getSeedPosition(seed, totalSlots/2);
                                            const isTop = seed % 2 === 0;
                                            
                                            if (position >= 0 && position < pairs.length) {
                                                const team = teams[seedIndex];
                                                pairs[position][isTop ? 0 : 1] = team ? team.team_name : null;
                                            }
                                        });

                                        return pairs;
                                    }

                                    $(document).ready(function() {
                                        bracketManager = new BracketManager({
                                            gameId: <?php echo $game_id; ?>,
                                            departmentId: <?php echo $department_id; ?>,
                                            gradeLevel: <?php echo isset($grade_level) ? $grade_level : 'null'; ?>
                                        });

                                        $('#generate-bracket').click(async function() {
                                            try {
                                                $(this).prop('disabled', true);
                                                $('#bracket-container').html('<div class="text-center"><div class="spinner-border" role="status"></div><div>Generating bracket...</div></div>');

                                                const teams = await bracketManager.fetchTeams();
                                                
                                                // Calculate rounds needed based on team count
                                                const totalTeams = teams.length;
                                                const rounds = Math.ceil(Math.log2(totalTeams));
                                                const totalSlots = Math.pow(2, rounds);
                                                
                                                // Create properly seeded team pairings
                                                const bracketTeams = createBracketPairings(teams, totalSlots);
                                                console.log('Seeded teams:', bracketTeams);

                                                // Initialize empty results array
                                                const results = [];
                                                for (let i = 0; i < rounds; i++) {
                                                    results.push([]);
                                                }

                                                // Create initial bracket data
                                                const initialBracketData = {
                                                    teams: bracketTeams,
                                                    results: results
                                                };

                                                // Generate initial structure
                                                generatedStructure = bracketManager.convertBracketData(initialBracketData, teams, rounds);
                                                console.log('Initial bracket structure:', generatedStructure);

                                                // Initialize the bracket with jQuery Bracket
                                                $('#bracket-container').bracket({
                                                    teamWidth: 150,
                                                    scoreWidth: 40,
                                                    matchMargin: 50,
                                                    roundMargin: 50,
                                                    init: initialBracketData,
                                                    save: async function(data, userData) {
                                                        // Update structure when bracket changes
                                                        generatedStructure = bracketManager.convertBracketData(data, teams, rounds);
                                                        console.log('Updated bracket structure:', generatedStructure);
                                                    },
                                                    decorator: {
                                                        edit: function(container, data, doneCb) {
                                                            const input = $('<select>').addClass('form-control form-control-sm');
                                                            input.append($('<option>').val('').text('Select Team'));
                                                            input.append($('<option>').val('BYE').text('BYE').prop('selected', data === null));
                                                            
                                                            // Get all currently selected teams
                                                            const selectedTeams = [];
                                                            $('.jQBracket .label').each(function() {
                                                                const teamName = $(this).text().trim();
                                                                if (teamName && teamName !== 'BYE') {
                                                                    selectedTeams.push(teamName);
                                                                }
                                                            });
                                                            
                                                            // Add available teams
                                                            teams.forEach(function(team) {
                                                                if (!selectedTeams.includes(team.team_name) || team.team_name === data) {
                                                                    const option = $('<option>')
                                                                        .val(team.team_name)
                                                                        .text(team.team_name)
                                                                        .prop('selected', team.team_name === data);
                                                                    
                                                                    input.append(option);
                                                                }
                                                            });
                                                            
                                                            container.html(input);
                                                            
                                                            input.on('change', function() {
                                                                const selectedValue = $(this).val();
                                                                doneCb(selectedValue === 'BYE' || selectedValue === '' ? null : selectedValue);
                                                            });
                                                        },
                                                        render: function(container, team, score) {
                                                            container.empty();
                                                            if (team === null) {
                                                                container.addClass('bye-team bye');
                                                                container.append('BYE');
                                                            } else {
                                                                container.append(team);
                                                            }
                                                        }
                                                    }
                                                });

                                                $('#save-bracket').prop('disabled', false);
                                                
                                            } catch (error) {
                                                console.error('Error generating bracket:', error);
                                                Swal.fire({
                                                    icon: 'error',
                                                    title: 'Error',
                                                    text: error.message
                                                });
                                                $('#bracket-container').empty();
                                            } finally {
                                                $(this).prop('disabled', false);
                                            }
                                        });

                                        $('#save-bracket').off('click').on('click', async function() {
                                            try {
                                                if (!generatedStructure) {
                                                    throw new Error('No bracket structure available');
                                                }
                                                
                                                const result = await bracketManager.saveBracket(generatedStructure);
                                                if (result.success) {
                                                    Swal.fire({
                                                        title: 'Success!',
                                                        text: 'Bracket has been saved successfully.',
                                                        icon: 'success'
                                                    }).then(() => {
                                                        location.reload();
                                                    });
                                                } else {
                                                    throw new Error(result.message);
                                                }
                                            } catch (error) {
                                                console.error('Error saving bracket:', error);
                                                Swal.fire({
                                                    title: 'Error!',
                                                    text: error.message,
                                                    icon: 'error'
                                                });
                                            }
                                        });
                                    });
                                </script>
                            </div>
                            <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-bracket/0.11.1/jquery.bracket.min.js"></script>
                            <link href="https://cdnjs.cloudflare.com/ajax/libs/jquery-bracket/0.11.1/jquery.bracket.min.css" rel="stylesheet">
                            <script>
                                $(document).ready(function() {
                                    // Wrap bracket container with wrapper div
                                    $('#bracket-container').wrap('<div class="bracket-wrapper"></div>');
                                });
                            </script>
                        </div>
                    </div>
                </div>
            </div>


            <script>
                // Add click handler for view button
                $('.view-bracket').on('click', function() {
                    const bracketId = $(this).data('bracket-id');
                    
                    // Fetch bracket data
                    fetch('fetch_bracket.php?bracket_id=' + bracketId)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Clear existing bracket
                                $('#bracket-container').empty();
                                
                                // Process matches data for jQuery bracket format
                                console.log('Raw data from server:', data);
                                
                                // Get all round numbers except 'third-place'
                                const roundNumbers = Object.keys(data.matches)
                                    .filter(key => key !== 'third-place' && !isNaN(parseInt(key)))
                                    .sort((a, b) => parseInt(a) - parseInt(b));
                                
                                console.log('Round numbers:', roundNumbers);
                                
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
                                                    match.winning_team_id === match.teamA_id ? 
                                                    [1, 0] : [0, 1]
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
                                    save: function(){}, // Read-only mode
                                    decorator: {
                                        edit: function(){}, // Disable editing
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
                                        text: 'Back to Brackets List'
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
            </script>
            <script>
                function viewBracket(bracketId) {
                    // Hide the empty bracket message if it exists
                    $('.bracket-empty').hide();

                    // Show loading state
                    $('#bracket-container').html('<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Loading bracket...</div>');

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
                                const totalHeight = firstRoundMatches * 100; // Reduced height between matches

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
                                            const position = (spacing * matchIndex) + (spacing - 80) / 2; // Reduced match height
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

                                // Hide the generate button and show a back button
                                $('#generate-bracket').hide();
                                if (!$('#backToBrackets').length) {
                                    $('#generate-bracket').after(`
                                        <button id="backToBrackets" class="btn btn-secondary" onclick="showBracketList()">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    `);
                                }

                                // Hide the bracket actions
                                $('#bracketActions').hide();

                                // Scroll to the bracket
                                $('html, body').animate({
                                    scrollTop: $('#bracket-container').offset().top - 20
                                }, 500);
                            } else {
                                $('#bracket-container').html('<div class="alert alert-danger">' + response.message + '</div>');
                            }
                        },
                        error: function() {
                            $('#bracket-container').html('<div class="alert alert-danger">Failed to load bracket</div>');
                        }
                    });
                }

                function showBracketList() {
                    // Show the generate button and hide back button
                    $('#generate-bracket').show();
                    $('#backToBrackets').remove();

                    // Show the bracket actions
                    $('#bracketActions').show();

                    // Clear the bracket container and show the empty message
                    $('#bracket-container').html(`
                        <div class="bracket-empty">
                            <i class="fas fa-trophy"></i>
                            <p>Click "Generate New Bracket" to create one or select a bracket in the table to view it.</p>
                        </div>
                    `);

                    // Show the bracket list table
                    $('#bracketListContainer').show();

                    // Reload the bracket list
                    loadBrackets();
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
                            <div class="team ${match.teamA_id === 0 ? 'team-tbd' : ''} ${teamAWinner ? 'winner' : ''}">
                                <span class="team-name">${match.teamA_name || '---'}</span>
                                ${isFinished ? `<span class="team-score">${match.score_teamA || '0'}</span>` : ''}
                                ${teamAWinner ? '<span class="winner-check">✓</span>' : ''}
                            </div>
                            <div class="team ${match.teamB_id === 0 ? 'team-tbd' : ''} ${teamBWinner ? 'winner' : ''}">
                                <span class="team-name">${match.teamB_name || '---'}</span>
                                ${isFinished ? `<span class="team-score">${match.score_teamB || '0'}</span>` : ''}
                                ${teamBWinner ? '<span class="winner-check">✓</span>' : ''}
                            </div>
                            ${isThirdPlace ? '<div class="match-label">Third Place Match</div>' : ''}
                        </div>
                    `;
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
                                        Swal.fire(
                                            'Deleted!',
                                            response.message,
                                            'success'
                                        ).then(() => {
                                            // Reload the page to show the saved bracket
                                            window.location.reload();
                                        });
                                    } else {
                                        Swal.fire(
                                            'Error',
                                            response.message,
                                            'error'
                                        );
                                    }
                                },
                                error: function() {
                                    Swal.fire(
                                        'Error',
                                        'Failed to delete bracket. Please try again.',
                                        'error'
                                    );
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
        </div>
    </div>

</body>

</html>