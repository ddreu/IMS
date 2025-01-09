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
        g.game_name
    FROM matches m
    JOIN match_results mr ON m.match_id = mr.match_id
    JOIN teams tA ON mr.team_A_id = tA.team_id
    JOIN teams tB ON mr.team_B_id = tB.team_id
    JOIN brackets b ON m.bracket_id = b.bracket_id
    JOIN games g ON b.game_id = g.game_id
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

// Function to get player stats
function getPlayerStats($conn, $team_id, $match_id)
{
    $players_query = "
        SELECT 
            p.player_id,
            p.player_lastname,
            p.player_firstname,
            p.jersey_number,
            ps.stat_name,
            ps.stat_value
        FROM players p
        LEFT JOIN player_match_stats ps ON p.player_id = ps.player_id AND ps.match_id = ?
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
include '../navbar/navbar.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Match Summary</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <link rel="stylesheet" href="../styles/committee.css">
    <link rel="stylesheet" href="../styles/dashboard.css">
    <style>
        .score-display {
            font-size: 2rem;
            font-weight: bold;
            text-align: center;
            padding: 1rem;
            background-color: #f8f9fa;
            border-radius: 10px;
            margin: 1rem 0;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .team-name {
            font-size: 2rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 1rem;
        }

        .player-card {
            background-color: #fff;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }

        .player-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .player-name {
            font-weight: 600;
            color: #495057;
            font-size: 1.25rem;
        }

        .jersey-number {
            background-color: #e9ecef;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            font-weight: 600;
            margin-right: 0.5rem;
        }

        .stat-item {
            display: inline-block;
            margin-right: 1rem;
            color: #6c757d;
            font-size: 0.9rem;
        }

        .stat-value {
            font-weight: 600;
            color: #212529;
        }

        .winner {
            color: #28a745;
        }

        .loser {
            color: #dc3545;
        }

        .back-button {
            margin-top: 2rem;
            text-align: center;
        }
    </style>
</head>

<body>

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
    <div class="container mt-4">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <h2 class="text-center mb-4"><?= htmlspecialchars($match['game_name']) ?> Match Summary</h2>
                <div class="text-start mb-4 back-button">
                    <a href="<?= $user_role === 'Committee' ? 'match_list.php' : 'admin_match_list.php' ?>" class="btn btn-primary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Matches
                    </a>
                </div>

                <!-- Score Display -->
                <div class="score-display">
                    <div class="row align-items-center">
                        <div class="col-5 text-end">
                            <span class="<?= $match['score_teamA'] > $match['score_teamB'] ? 'winner' : 'loser' ?>">
                                <?= htmlspecialchars($match['teamA_name']) ?>
                            </span>
                        </div>
                        <div class="col-2">
                            <span class="score">
                                <?= $match['score_teamA'] ?> - <?= $match['score_teamB'] ?>
                            </span>
                        </div>
                        <div class="col-5 text-start">
                            <span class="<?= $match['score_teamB'] > $match['score_teamA'] ? 'winner' : 'loser' ?>">
                                <?= htmlspecialchars($match['teamB_name']) ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Player Stats -->
                <div class="row mt-4">
                    <!-- Team A Players -->
                    <div class="col-md-6">
                        <h3 class="team-name"><?= htmlspecialchars($match['teamA_name']) ?></h3>
                        <?php foreach ($teamA_players as $player): ?>
                            <div class="player-card">
                                <div class="player-name mb-2">
                                    <span class="jersey-number">#<?= htmlspecialchars($player['jersey_number']) ?></span>
                                    <?= htmlspecialchars($player['player_firstname']) ?> <?= htmlspecialchars($player['player_lastname']) ?>
                                </div>
                                <div class="player-stats">
                                    <?php foreach ($game_stats as $stat): ?>
                                        <div class="stat-item">
                                            <?= htmlspecialchars($stat['stat_name']) ?>:
                                            <span class="stat-value">
                                                <?= isset($player['stats'][$stat['stat_name']]) ? $player['stats'][$stat['stat_name']] : '0' ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Team B Players -->
                    <div class="col-md-6">
                        <h3 class="team-name"><?= htmlspecialchars($match['teamB_name']) ?></h3>
                        <?php foreach ($teamB_players as $player): ?>
                            <div class="player-card">
                                <div class="player-name mb-2">
                                    <span class="jersey-number">#<?= htmlspecialchars($player['jersey_number']) ?></span>
                                    <?= htmlspecialchars($player['player_firstname']) ?> <?= htmlspecialchars($player['player_lastname']) ?>
                                </div>
                                <div class="player-stats">
                                    <?php foreach ($game_stats as $stat): ?>
                                        <div class="stat-item">
                                            <?= htmlspecialchars($stat['stat_name']) ?>:
                                            <span class="stat-value">
                                                <?= isset($player['stats'][$stat['stat_name']]) ? $player['stats'][$stat['stat_name']] : '0' ?>
                                            </span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>



                <script>
                    <?php if (isset($_SESSION['alert_shown'])) : ?>
                        Swal.fire({
                            title: 'Match Ended!',
                            text: 'The match has concluded successfully.',
                            icon: 'success',
                            confirmButtonText: 'OK'
                        });
                        <?php unset($_SESSION['alert_shown']); // Clear the session variable after showing the alert 
                        ?>
                    <?php endif; ?>
                </script>
</body>

</html>