<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

// Get the match_id or schedule_id from the URL
$user_role = $_SESSION['role'];
$match_id = isset($_GET['match_id']) ? intval($_GET['match_id']) : 0;
$schedule_id = isset($_GET['schedule_id']) ? $_GET['schedule_id'] : null;
$show_alert = isset($_GET['status']) && $_GET['status'] === 'success';

// Check if the alert has already been shown
if ($show_alert && !isset($_SESSION['alert_shown'])) {
    $_SESSION['alert_shown'] = true; // Set session variable to indicate alert has been shown
}

// Get match details including teams and scores
$match_query = "
    SELECT 
        m.match_id,
        m.bracket_id,
        mr.score_teamA,
        mr.score_teamB,
        tA.team_name as teamA_name,
        tB.team_name as teamB_name,
        tA.team_id as teamA_id,
        tB.team_id as teamB_id,
        b.game_id,
        g.game_name,
        s.schedule_date,
        s.schedule_time,
        s.venue
    FROM matches m
    JOIN match_results mr ON m.match_id = mr.match_id
    JOIN teams tA ON mr.team_A_id = tA.team_id
    JOIN teams tB ON mr.team_B_id = tB.team_id
    JOIN brackets b ON m.bracket_id = b.bracket_id
    JOIN games g ON b.game_id = g.game_id
    LEFT JOIN schedules s ON m.match_id = s.match_id
    WHERE m.match_id = ?";

$stmt = $conn->prepare($match_query);
$stmt->bind_param("i", $match_id);
$stmt->execute();
$match = $stmt->get_result()->fetch_assoc();

if (!$match) {
    die("Match not found");
}

// Get all available stats for this game
$stats_query = "SELECT config_id, stat_name FROM game_stats_config WHERE game_id = ?";
$stats_stmt = $conn->prepare($stats_query);
$stats_stmt->bind_param("i", $match['game_id']);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();
$game_stats = [];
while ($stat = $stats_result->fetch_assoc()) {
    $game_stats[] = $stat;
}

// Function to calculate player score based on stats and position
function calculatePlayerScore($player, $game_stats)
{
    $score = 0;
    $position = strtolower($player['position']);

    foreach ($game_stats as $stat) {
        $stat_name = strtolower($stat['stat_name']);
        if (isset($player['stats'][$stat['stat_name']])) {
            $stat_value = $player['stats'][$stat['stat_name']];

            // Base weights for different stats
            switch ($stat_name) {
                case 'points':
                    $weight = 1.0;
                    // Bonus for high scoring
                    if ($stat_value >= 20) $weight += 0.2;
                    if ($stat_value >= 30) $weight += 0.3;
                    break;

                case 'assists':
                    $weight = 0.7;
                    // Bonus for playmaking
                    if ($stat_value >= 10) $weight += 0.2;
                    break;

                case 'rebounds':
                    $weight = 0.5;
                    // Bonus for double-digit rebounds
                    if ($stat_value >= 10) $weight += 0.2;
                    break;

                case 'steals':
                    $weight = 0.7;
                    // Bonus for exceptional defense
                    if ($stat_value >= 5) $weight += 0.2;
                    break;

                case 'blocks':
                    $weight = 0.7;
                    // Bonus for exceptional defense
                    if ($stat_value >= 3) $weight += 0.2;
                    break;

                default:
                    $weight = 0.3;
            }

            // Position-based adjustments
            switch ($position) {
                case 'guard':
                    if (in_array($stat_name, ['assists', 'steals'])) $weight *= 1.2;
                    break;
                case 'forward':
                    if (in_array($stat_name, ['rebounds', 'points'])) $weight *= 1.2;
                    break;
                case 'center':
                    if (in_array($stat_name, ['blocks', 'rebounds'])) $weight *= 1.2;
                    break;
            }

            $score += $stat_value * $weight;
        }
    }

    // Bonus for all-around performance (if multiple stats are good)
    $good_stats_count = 0;
    foreach ($player['stats'] as $stat_name => $value) {
        switch (strtolower($stat_name)) {
            case 'points':
                if ($value >= 15) $good_stats_count++;
                break;
            case 'assists':
                if ($value >= 5) $good_stats_count++;
                break;
            case 'rebounds':
                if ($value >= 5) $good_stats_count++;
                break;
            case 'steals':
                if ($value >= 2) $good_stats_count++;
                break;
            case 'blocks':
                if ($value >= 2) $good_stats_count++;
                break;
        }
    }

    if ($good_stats_count >= 3) {
        $score *= 1.2; // 20% bonus for all-around performance
    }

    return $score;
}

// Function to get player stats
function getPlayerStats($conn, $team_id, $match_id)
{
    $players_query = "
        SELECT 
            p.player_id,
            p.player_lastname,
            p.player_firstname,
            p.jersey_number,
            p.team_id,
            pi.picture,
            pi.position,
            pi.height,
            pi.weight,
            ps.stat_name,
            ps.stat_value
        FROM players p
        LEFT JOIN player_match_stats ps ON p.player_id = ps.player_id AND ps.match_id = ?
        LEFT JOIN players_info pi ON p.player_id = pi.player_id
        WHERE p.team_id = ?
        ORDER BY p.jersey_number";

    $stmt = $conn->prepare($players_query);
    $stmt->bind_param("ii", $match_id, $team_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $players = [];
    while ($row = $result->fetch_assoc()) {
        $player_id = $row['player_id'];
        if (!isset($players[$player_id])) {
            $players[$player_id] = [
                'player_id' => $player_id,
                'player_lastname' => $row['player_lastname'],
                'player_firstname' => $row['player_firstname'],
                'jersey_number' => $row['jersey_number'],
                'team_id' => $row['team_id'],
                'picture' => $row['picture'],
                'position' => $row['position'],
                'height' => $row['height'],
                'weight' => $row['weight'],
                'stats' => []
            ];
        }
        if ($row['stat_name']) {
            $players[$player_id]['stats'][$row['stat_name']] = $row['stat_value'];
        }
    }
    return array_values($players);
}

// Get player stats for both teams
$teamA_players = getPlayerStats($conn, $match['teamA_id'], $match_id);
$teamB_players = getPlayerStats($conn, $match['teamB_id'], $match_id);

// Find the Player of the Game
$all_players = array_merge($teamA_players, $teamB_players);
$potg = null;
$highest_score = 0;

foreach ($all_players as $player) {
    $player_score = calculatePlayerScore($player, $game_stats);
    if ($player_score > $highest_score) {
        $highest_score = $player_score;
        $potg = $player;
    }
}

if ($highest_score === 0) {
    $potg = null;
}

// Get player's team name
$potg_team = ($potg) ? ($match['teamA_id'] == $potg['team_id'] ? $match['teamA_name'] : $match['teamB_name']) : '';

// Determine the winner
$winner = '';
$score_difference = abs($match['score_teamA'] - $match['score_teamB']);
if ($match['score_teamA'] > $match['score_teamB']) {
    $winner = $match['teamA_name'];
} elseif ($match['score_teamB'] > $match['score_teamA']) {
    $winner = $match['teamB_name'];
}

// Generate match summary
$match_summary = "";
if ($winner) {
    $match_summary = "$winner wins by $score_difference points in this intense match!";
} else {
    $match_summary = "The match ended in a draw with both teams showing great performance!";
}

// Fetch all sets, sorted by timestamp
$set_scores_query = "
    SELECT 
        period_number, 
        score_teamA, 
        score_teamB, 
        timestamp 
    FROM match_periods_info 
    WHERE match_id = ? 
    ORDER BY timestamp DESC"; // Sort by timestamp in descending order to get the latest first

$set_scores_stmt = $conn->prepare($set_scores_query);
$set_scores_stmt->bind_param("i", $match_id);
$set_scores_stmt->execute();
$set_scores_result = $set_scores_stmt->get_result();

$set_scores = [];
$final_set = null;

// Loop through the sets and mark the final one
while ($row = $set_scores_result->fetch_assoc()) {
    // If this is the first set in the sorted list, it's the latest set (final set)
    if ($final_set === null) {
        $row['is_final_set'] = true;  // Mark the latest set as the final one
        $final_set = $row;  // Store the final set
    } else {
        $row['is_final_set'] = false; // All other sets are not final
    }
    $set_scores[] = $row;
}



// include '../navbar/navbar.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Match Summary</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script> -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <link rel="stylesheet" href="../styles/committee.css">
    <link rel="stylesheet" href="../styles/dashboard.css">
    <style>
        :root {
            --primary-color: #2c3e50;
            --secondary-color: #34495e;
            --accent-color: #3498db;
            --background-color: #f8f9fa;
            --card-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
        }

        body {
            background-color: var(--background-color);
        }

        .team-logo {
            width: 80px;
            height: 80px;
            object-fit: contain;
            transition: transform 0.3s ease;
        }

        .team-logo:hover {
            transform: scale(1.1);
        }

        .score-display {
            font-size: 3.5rem;
            font-weight: bold;
            color: var(--primary-color);
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .match-info {
            background-color: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: var(--card-shadow);
            transition: transform 0.3s ease;
        }

        .match-info:hover {
            transform: translateY(-5px);
        }

        .stats-card {
            border: none;
            border-radius: 15px;
            box-shadow: var(--card-shadow);
            margin-bottom: 30px;
            overflow: hidden;
            transition: transform 0.3s ease;
        }

        .stats-card:hover {
            transform: translateY(-5px);
        }

        .player-stats {
            font-size: 0.9rem;
        }

        .match-summary {
            background-color: white;
            padding: 20px;
            border-radius: 15px;
            margin: 20px 0;
            box-shadow: var(--card-shadow);
        }

        .potg-card {
            background: linear-gradient(135deg, #2c3e50, #3498db);
            color: white;
            padding: 25px;
            border-radius: 15px;
            margin: 20px 0;
            box-shadow: var(--card-shadow);
        }

        .potg-stats {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 10px;
            padding: 15px;
            margin-top: 15px;
        }

        .stat-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 5px 10px;
            border-radius: 15px;
            margin: 5px;
            display: inline-block;
        }

        .player-image {
            width: 150px;
            height: 150px;
            object-fit: cover;
            border-radius: 50%;
            border: 4px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease;
        }

        .player-image:hover {
            transform: scale(1.05);
        }

        @media (max-width: 768px) {
            .score-display {
                font-size: 2.5rem;
            }

            .team-logo {
                width: 60px;
                height: 60px;
            }

            .container {
                padding: 10px;
            }

            .match-info {
                padding: 15px;
            }

            .table-responsive {
                font-size: 0.8rem;
            }
        }

        @media (max-width: 576px) {
            .score-display {
                font-size: 2rem;
            }

            h1 {
                font-size: 1.5rem;
            }

            h2 {
                font-size: 1.2rem;
            }
        }
    </style>
</head>

<body>
    <?php include '../navbar/navbar.php';
    ?>
    <nav>
        <?php
        $current_page = 'matchlist';


        // Include the appropriate sidebar based on the user role
        if ($user_role === 'Committee') {
            include '../committee/csidebar.php'; // Sidebar for committee
        } else {
            include '../department_admin/sidebar.php';
        }
        ?>
    </nav>
    <div class="main">
        <div class="container mt-4">

            <div class="container py-4">
                <!-- Match Header -->
                <div class="match-info text-center">
                    <h1 class="h3 mb-4"><?php echo htmlspecialchars($match['game_name']); ?> Match Summary</h1>
                    <div class="row align-items-center">
                        <div class="col-4 text-end">
                            <h2 class="h4"><?php echo htmlspecialchars($match['teamA_name']); ?></h2>
                            <!--<img src="../team_logos/<?php echo $match['teamA_id']; ?>.png" alt="Team A Logo" class="team-logo"> -->
                        </div>
                        <div class="col-4">
                            <div class="score-display">
                                <?php echo $match['score_teamA']; ?> - <?php echo $match['score_teamB']; ?>
                            </div>
                            <?php if ($match['schedule_date']): ?>
                                <div class="text-muted">
                                    <i class="fas fa-calendar"></i> <?php echo date('F j, Y', strtotime($match['schedule_date'])); ?><br>
                                    <i class="fas fa-clock"></i> <?php echo date('g:i A', strtotime($match['schedule_time'])); ?><br>
                                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($match['venue']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="col-4 text-start">
                            <h2 class="h4"><?php echo htmlspecialchars($match['teamB_name']); ?></h2>
                            <!--<img src="../team_logos/<?php echo $match['teamB_id']; ?>.png" alt="Team B Logo" class="team-logo"> -->
                        </div>
                    </div>
                </div>

                <!-- Regular Set Scores Section -->
                <?php if (!empty($set_scores)): ?>
                    <!-- <div class="container mt-4"> -->
                    <div class="card">
                        <div class="card-header">
                            <h3>Set Scores</h3>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Set</th>
                                            <th><?php echo htmlspecialchars($match['teamA_name']); ?></th>
                                            <th><?php echo htmlspecialchars($match['teamB_name']); ?></th>
                                            <th>Timestamp</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $teamA_sets_won = 0;
                                        $teamB_sets_won = 0;
                                        foreach ($set_scores as $set):
                                            // Only display regular sets, not the final set
                                            if (!$set['is_final_set']):
                                                if ($set['score_teamA'] > $set['score_teamB']) {
                                                    $teamA_sets_won++;
                                                } else {
                                                    $teamB_sets_won++;
                                                }
                                        ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($set['period_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($set['score_teamA']); ?></td>
                                                    <td><?php echo htmlspecialchars($set['score_teamB']); ?></td>
                                                    <td><?php echo htmlspecialchars($set['timestamp']); ?></td>
                                                </tr>
                                        <?php endif;
                                        endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <!-- Displaying sets won above the table -->
                            <div class="text-center mt-3">
                                <h5>Sets Won</h5>
                                <p><?php echo htmlspecialchars($match['teamA_name']); ?>: <?php echo $teamA_sets_won; ?> Sets | <?php echo htmlspecialchars($match['teamB_name']); ?>: <?php echo $teamB_sets_won; ?> Sets</p>
                            </div>

                            <!-- Display the final set summary, but keep it separate -->
                            <?php if ($final_set): ?>
                                <div class="mt-4">
                                    <h5 class="text-center">Final Set Summary</h5>
                                    <div class="table-responsive">
                                        <table class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>Final Set</th>
                                                    <th><?php echo htmlspecialchars($match['teamA_name']); ?></th>
                                                    <th><?php echo htmlspecialchars($match['teamB_name']); ?></th>
                                                    <th>Timestamp</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>Final Set</td>
                                                    <td><?php echo htmlspecialchars($final_set['score_teamA']); ?></td>
                                                    <td><?php echo htmlspecialchars($final_set['score_teamB']); ?></td>
                                                    <td><?php echo htmlspecialchars($final_set['timestamp']); ?></td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <!-- </div> -->
                <?php endif; ?>



                <!-- Match Summary -->
                <div class="match-summary">
                    <h3 class="h5"><i class="fas fa-chart-line"></i> Match Overview</h3>
                    <p><?php echo htmlspecialchars($match_summary); ?></p>
                </div>

                <!-- Player of the Game Card -->
                <?php if ($potg): ?>
                    <div class="potg-card">
                        <div class="row align-items-center">
                            <div class="col-md-4 text-center">
                                <?php
                                $player_image = '../uploads/players/default.png'; // Default image path
                                if (!empty($potg['picture']) && file_exists('../uploads/players/' . $potg['picture'])) {
                                    $player_image = '../uploads/players/' . $potg['picture'];
                                }
                                ?>
                                <img src="<?php echo htmlspecialchars($player_image); ?>"
                                    alt="Player Image"
                                    class="player-image mb-3">
                                <h3 class="h4">Player of the Game</h3>
                                <p class="text-light mb-0"><?php echo htmlspecialchars($potg['position']); ?></p>
                                <p class="text-light-50">
                                    <?php echo htmlspecialchars($potg['height']); ?> cm |
                                    <?php echo htmlspecialchars($potg['weight']); ?> kg
                                </p>
                            </div>
                            <div class="col-md-8">
                                <h2 class="h3 mb-3">
                                    #<?php echo htmlspecialchars($potg['jersey_number']); ?>
                                    <?php echo htmlspecialchars($potg['player_firstname'] . ' ' . $potg['player_lastname']); ?>
                                </h2>
                                <p class="mb-2"><?php echo htmlspecialchars($potg_team); ?></p>
                                <div class="potg-stats">
                                    <?php foreach ($game_stats as $stat): ?>
                                        <?php if (isset($potg['stats'][$stat['stat_name']])): ?>
                                            <span class="stat-badge">
                                                <?php echo htmlspecialchars($stat['stat_name']); ?>:
                                                <?php echo htmlspecialchars($potg['stats'][$stat['stat_name']]); ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>


                <!-- Player Statistics -->
                <div class="row">
                    <!-- Team A Stats -->
                    <div class="col-md-6">
                        <div class="card stats-card">
                            <div class="card-header bg-light">
                                <h3 class="h5 mb-0"><?php echo htmlspecialchars($match['teamA_name']); ?> Player Statistics</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover player-stats">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Player</th>
                                                <?php foreach ($game_stats as $stat): ?>
                                                    <th><?php echo htmlspecialchars($stat['stat_name']); ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($teamA_players as $player): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($player['jersey_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($player['player_firstname'] . ' ' . $player['player_lastname']); ?></td>
                                                    <?php foreach ($game_stats as $stat): ?>
                                                        <td><?php echo isset($player['stats'][$stat['stat_name']]) ? htmlspecialchars($player['stats'][$stat['stat_name']]) : '0'; ?></td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Team B Stats -->
                    <div class="col-md-6">
                        <div class="card stats-card">
                            <div class="card-header bg-light">
                                <h3 class="h5 mb-0"><?php echo htmlspecialchars($match['teamB_name']); ?> Player Statistics</h3>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover player-stats">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Player</th>
                                                <?php foreach ($game_stats as $stat): ?>
                                                    <th><?php echo htmlspecialchars($stat['stat_name']); ?></th>
                                                <?php endforeach; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($teamB_players as $player): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($player['jersey_number']); ?></td>
                                                    <td><?php echo htmlspecialchars($player['player_firstname'] . ' ' . $player['player_lastname']); ?></td>
                                                    <?php foreach ($game_stats as $stat): ?>
                                                        <td><?php echo isset($player['stats'][$stat['stat_name']]) ? htmlspecialchars($player['stats'][$stat['stat_name']]) : '0'; ?></td>
                                                    <?php endforeach; ?>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <!-- Load Bootstrap 5.3.3 bundle (includes Popper.js) -->
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

        <?php if (isset($_SESSION['alert_shown']) && $_SESSION['alert_shown']) : ?>
            <script>
                Swal.fire({
                    title: 'Match Ended!',
                    text: 'The match has concluded successfully.',
                    icon: 'success',
                    confirmButtonText: 'OK'
                }).then(() => {
                    <?php unset($_SESSION['alert_shown']); ?>
                });
            </script>
        <?php endif; ?>


</body>

</html>