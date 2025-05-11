<?php
include_once '../connection/conn.php';
include '../user_logs/logger.php';
$conn = con();
session_start();

// Fetch team names for logging
$team_query = $conn->prepare("
    SELECT 
        (SELECT team_name FROM teams WHERE team_id = ?) AS teamA_name,
        (SELECT team_name FROM teams WHERE team_id = ?) AS teamB_name
");
$team_query->bind_param("ii", $_GET['teamA_id'], $_GET['teamB_id']);
$team_query->execute();
$team_result = $team_query->get_result()->fetch_assoc();

// Prepare log description
$description = "Preparing player statistics for match: " .
    ($team_result['teamA_name'] ?? 'Team A') .
    " vs " .
    ($team_result['teamB_name'] ?? 'Team B');

// Log user action with error handling
try {
    logUserAction(
        $conn,
        $_SESSION['user_id'],
        'Player Statistics',
        'PREPARE',
        $_GET['schedule_id'],
        $description
    );
} catch (Exception $e) {
    // Optional: Log the error or handle it silently
    error_log("Logging error in player_statistics.php: " . $e->getMessage());
}

// Get query parameters
$schedule_id = $_GET['schedule_id'] ?? null;
$teamA_id = $_GET['teamA_id'] ?? null;
$teamB_id = $_GET['teamB_id'] ?? null;
$game_id = $_SESSION['game_id'] ?? null;
$department_id = $_SESSION['department_id'] ?? null;

if (!$schedule_id || !$teamA_id || !$teamB_id || !$game_id || !$department_id) {
    die("Missing required parameters");
}

// Fetch match details
$match_query = $conn->prepare("
    SELECT g.game_name, tA.team_name AS teamA_name, tB.team_name AS teamB_name, 
           s.schedule_date, s.schedule_time, s.venue, s.match_id,
           ls.teamA_score, ls.teamB_score, ls.period as current_period,
           ls.time_remaining, ls.timer_status
    FROM schedules s
    JOIN matches m ON s.match_id = m.match_id
    JOIN brackets b ON m.bracket_id = b.bracket_id
    JOIN games g ON b.game_id = g.game_id
    JOIN teams tA ON m.teamA_id = tA.team_id
    JOIN teams tB ON m.teamB_id = tB.team_id
    LEFT JOIN live_scores ls ON s.schedule_id = ls.schedule_id
    WHERE s.schedule_id = ?");
$match_query->bind_param("i", $schedule_id);
$match_query->execute();
$match = $match_query->get_result()->fetch_assoc();

// Fetch game rules
$rules_query = $conn->prepare("
    SELECT scoring_unit, score_increment_options, period_type, number_of_periods, 
           duration_per_period, time_limit, point_cap, max_fouls, timeouts_per_period
    FROM game_scoring_rules
    WHERE game_id = ? AND department_id = ?");
$rules_query->bind_param("ii", $game_id, $department_id);
$rules_query->execute();
$rules = $rules_query->get_result()->fetch_assoc();

// If no rules found, use defaults
if (!$rules) {
    $rules = [
        'scoring_unit' => 'points',
        'score_increment_options' => '1,2,3',
        'period_type' => 'quarter',
        'number_of_periods' => 4,
        'duration_per_period' => 10,
        'time_limit' => 40,
        'point_cap' => 100,
        'max_fouls' => 5,
        'timeouts_per_period' => 2
    ];
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Player Statistics</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="styles.css">
    <script defer src="script.js"></script>
</head>

<body class="bg-light">
    <div class="container-fluid">
        <div class="row bg-primary text-white py-3 mb-4 align-items-center">
            <div class="col-auto">
                <a href="match_list.php" class="btn btn-outline-light">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>

            <div class="col text-center">
                <h2 class="mb-0">Player Statistics</h2>
            </div>
            <div class="col-auto">
                <button class="btn btn-success" id="submitAllStats">
                    <i class="fas fa-save me-2"></i>Save Stats
                </button>
            </div>
        </div>

        <div class="container-fluid px-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h4 class="text-center my-2">
                        <?= htmlspecialchars($match['teamA_name']) ?> vs <?= htmlspecialchars($match['teamB_name']) ?>
                    </h4>
                </div>
                <div class="card-body p-0">
                    <div class="row g-0">
                        <div class="col-12 col-md-6 border-end-md">
                            <div class="p-3">
                                <h5 class="text-center mb-3"><?= htmlspecialchars($match['teamA_name']); ?></h5>
                                <ul class="list-group" id="teamAList"></ul>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <div class="p-3">
                                <h5 class="text-center mb-3"><?= htmlspecialchars($match['teamB_name']); ?></h5>
                                <ul class="list-group" id="teamBList"></ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <template id="playerStatTemplate">
        <li class="list-group-item d-flex flex-column">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="stat-name fw-bold flex-grow-1"></span>
                <div class="stat-controls d-flex align-items-center">
                    <button class="btn btn-sm btn-outline-danger decrement-stat me-2">
                        <i class="fas fa-minus"></i>
                    </button>
                    <span class="stat-value badge bg-secondary">0</span>
                    <button class="btn btn-sm btn-outline-success increment-stat ms-2">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>
        </li>
    </template>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const gameId = <?= $_SESSION['game_id'] ?>;
            const scheduleId = <?= $_GET['schedule_id'] ?>;
            let playerStats = JSON.parse(localStorage.getItem(`playerStats_${scheduleId}`) || '{}');
            let playerStatsMeta = JSON.parse(localStorage.getItem(`playerStatsMeta_${scheduleId}`) || '{}');



            // Function to save stats to local storage
            function saveStatsToLocalStorage() {
                const statsKey = `playerStats_${scheduleId}`;
                const metaKey = `playerStatsMeta_${scheduleId}`;

                localStorage.setItem(statsKey, JSON.stringify(playerStats));
                localStorage.setItem(metaKey, JSON.stringify(playerStatsMeta));

                console.log(`🧠 *Saved player stats to localStorage* [${statsKey}]`);
                console.table(playerStats);

                console.log(`🧠 *Saved player stats meta to localStorage* [${metaKey}]`);
                console.table(playerStatsMeta);
            }


            // Function to handle stat updates dynamically
            function updateStat(button, increment) {
                const statRow = button.closest('.stat-row');
                const statValue = statRow.querySelector('.stat-value');
                const playerId = statRow.dataset.playerId;
                const statConfigId = statRow.dataset.statConfigId;
                const teamId = statRow.dataset.teamId; // ✅ ADD THIS LINE

                let currentValue = parseInt(statValue.textContent);
                let newValue = Math.max(0, currentValue + increment);

                statValue.textContent = newValue;

                const statKey = `team_${teamId}_player_${playerId}_stat_${statConfigId}`;
                playerStats[statKey] = newValue;

                saveStatsToLocalStorage();

                statValue.classList.add('text-success');
                setTimeout(() => statValue.classList.remove('text-success'), 500);
            }


            // Function to submit all player stats
            function submitPlayerStats() {
                // Collect stats for both teams
                const statsToSubmit = Object.entries(playerStats)
                    .filter(([key, value]) => value > 0)
                    .map(([key, value]) => {
                        const match = key.match(/team_(\d+)_player_(\d+)_stat_(\d+)/);
                        if (!match) return null;
                        const [_, teamId, playerId, statConfigId] = match;
                        return {
                            player_id: parseInt(playerId),
                            stat_config_id: parseInt(statConfigId),
                            stat_value: parseInt(value)
                        };
                    })

                // .map(([key, value]) => {
                //     const [, teamId, playerId, statConfigId] = key.match(/team_(\d+)_player_(\d+)_stat_(\d+)/);
                //     return {
                //         team_id: teamId, // <-- include this
                //         player_id: playerId,
                //         stat_config_id: statConfigId,
                //         stat_value: value
                //     };

                // });

                // Check if there are any stats to submit
                if (statsToSubmit.length === 0) {
                    Swal.fire({
                        icon: 'info',
                        title: 'No Stats to Submit',
                        text: 'Please record some player statistics first.'
                    });
                    return;
                }

                // First confirmation
                Swal.fire({
                    title: 'Save Player Statistics',
                    text: 'Are you sure you want to save player statistics for both teams?',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, Save Stats'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Second confirmation with more details
                        Swal.fire({
                            title: 'Confirm Submission',
                            html: `
                                <p>You are about to submit player statistics for:</p>
                                <strong>Total Stats Entries: ${statsToSubmit.length}</strong>
                            `,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Confirm Submission'
                        }).then((confirmResult) => {
                            if (confirmResult.isConfirmed) {
                                // Send stats to server
                                fetch('save_player_stats.php', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                        },
                                        body: JSON.stringify({
                                            schedule_id: scheduleId,
                                            stats: statsToSubmit
                                        })
                                    })
                                    .then(response => response.json())
                                    .then(data => {
                                        if (data.success) {
                                            // Get match_id from the server response
                                            const matchId = data.match_id; // Assuming the server returns match_id

                                            // Clear local storage after successful submission
                                            localStorage.removeItem('playerStats');

                                            // Redirect to match summary with match_id and schedule_id
                                            window.location.href = `match_summary.php?match_id=${matchId}&schedule_id=${scheduleId}&status=success`;
                                        } else {
                                            Swal.fire({
                                                icon: 'error',
                                                title: 'Submission Failed',
                                                text: data.message || 'Unable to submit player statistics.'
                                            });
                                        }
                                    })
                                    .catch(error => {
                                        console.error('Error:', error);
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Submission Error',
                                            text: 'There was a problem submitting the statistics.'
                                        });
                                    });
                            }
                        });
                    }
                });
            }

            // Fetch game stats configuration
            fetch(`get_game_stats_config.php?game_id=${gameId}`)
                .then(response => response.json())
                .then(stats => {
                    statsConfig = stats;
                    return fetch(`getPlayersByTeams.php?schedule_id=${scheduleId}`);
                })
                .then(response => response.json())
                .then(players => {
                    updatePlayerLists(players, statsConfig);
                })
                .catch(error => console.error('Error initializing player stats:', error));

            function updatePlayerLists(players, stats) {
                const teamAList = document.getElementById('teamAList');
                const teamBList = document.getElementById('teamBList');
                teamAList.innerHTML = '';
                teamBList.innerHTML = '';

                players.forEach(player => {
                    const playerItem = document.createElement('li');
                    playerItem.className = 'list-group-item';
                    playerItem.innerHTML = `<div class='player-name fw-bold'>${player.player_name}</div>`;

                    const statsContainer = document.createElement('div');
                    stats.forEach(stat => {
                        const statKey = `team_${player.team_id}_player_${player.player_id}_stat_${stat.config_id}`;

                        // Restore previous value from local storage if exists
                        playerStats[statKey] = playerStats[statKey] || 0;

                        // ✅ Always render the UI
                        const statRow = document.createElement('div');
                        statRow.className = 'stat-row d-flex justify-content-between align-items-center mb-2';
                        statRow.dataset.teamId = player.team_id;

                        statRow.dataset.playerId = player.player_id;
                        statRow.dataset.statConfigId = stat.config_id;

                        statRow.innerHTML = `
        <span class="stat-name fw-bold flex-grow-1">${stat.stat_name}</span>
        <div class="stat-controls d-flex align-items-center">
            <button class="btn btn-sm btn-outline-danger decrement-stat me-2">
                <i class="fas fa-minus"></i>
            </button>
            <span class="stat-value badge bg-secondary fs-5">${playerStats[statKey]}</span>
            <button class="btn btn-sm btn-outline-success increment-stat ms-2">
                <i class="fas fa-plus"></i>
            </button>
        </div>
    `;

                        // ✅ Save stat name meta (once per stat config)
                        if (!playerStatsMeta[stat.config_id]) {
                            playerStatsMeta[stat.config_id] = {
                                stat_name: stat.stat_name
                            };
                        }

                        // ✅ Add event listeners
                        statRow.querySelector('.increment-stat').addEventListener('click', (e) => updateStat(e.currentTarget, 1));
                        statRow.querySelector('.decrement-stat').addEventListener('click', (e) => updateStat(e.currentTarget, -1));

                        statsContainer.appendChild(statRow);
                    });


                    playerItem.appendChild(statsContainer);
                    (player.team_id === <?= $_GET['teamA_id'] ?> ? teamAList : teamBList).appendChild(playerItem);
                });

                // Add event listener to submit button in header
                document.getElementById('submitAllStats').addEventListener('click', submitPlayerStats);
            }
        });
    </script>
</body>

</html>