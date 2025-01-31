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
    <link rel="stylesheet" href="css/bracket-style.css">
    <link rel="stylesheet" href="../styles/committee.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://d3js.org/d3.v7.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
                                                <button class="btn btn-primary btn-sm" onclick="viewBracket(<?php echo $bracket['bracket_id']; ?>)">
                                                    View
                                                </button>
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
                                <h3 class="card-title">Tournament Bracket - <?php echo htmlspecialchars($game['game_name']); ?></h3>
                                <div class="card-tools">
                                    <button id="generateBracket" class="btn btn-primary">
                                        <i class="fas fa-plus"></i> Generate New Bracket
                                    </button>
                                </div>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($grade_levels)): ?>
                                    <div class="form-group">
                                        <label for="gradeLevelSelect">Filter by Grade Level:</label>
                                        <select class="form-control" id="gradeLevelSelect">
                                            <option value="">All Grade Levels</option>
                                            <?php foreach ($grade_levels as $level): ?>
                                                <option value="<?php echo htmlspecialchars($level); ?>">
                                                    <?php echo htmlspecialchars($level); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                <?php endif; ?>

                                <div id="bracket-container">
                                    <!-- Bracket will be generated here -->
                                    <div class="bracket-empty">
                                        <i class="fas fa-trophy"></i>
                                        <p>Click "Generate New Bracket" to create one or select a bracket in the table to view it.</p>
                                    </div>
                                </div>

                                <div class="mt-3" id="bracketActions" style="display: none;">
                                    <button id="save-bracket" class="btn btn-success" disabled>
                                        Save Bracket
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                // Pass PHP variables to JavaScript
                window.BRACKET_CONFIG = {
                    departmentId: <?php echo $department_id; ?>,
                    departmentName: <?php echo json_encode($department['department_name']); ?>,
                    gameId: <?php echo $game_id; ?>,
                    gradeLevel: null // Will be set by grade level dropdown
                };
            </script>
            <script src="bracketgen.js"></script>
        </div>
    </div>

</body>

</html>

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
                    $('#generateBracket').hide();
                    if (!$('#backToBrackets').length) {
                        $('#generateBracket').after(`
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
        $('#generateBracket').show();
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