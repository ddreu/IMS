<?php
include_once '../connection/conn.php';
$conn = con();
session_start();

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
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Live Scoring</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="set-based_scoreboard.css">

</head>

<body>
    <div class="overlay" onclick="window.closePlayerStats()"></div>
    <div class="main-container">
        <div class="main-content">
            <!-- Header Section -->
            <div class="header-section">
                <button type="button" class="btn btn-danger" id="end-match-button">
                    <i class="fas fa-stop-circle me-2"></i>End Match
                </button>
                <h2 class="text-center mb-4"><?= htmlspecialchars($match['game_name']) ?></h2>
            </div>

            <!-- Live Stream Button -->
            <div class="position-fixed top-0 start-0 m-3">
                <button class="btn btn-success rounded-circle" type="button" data-bs-toggle="modal" data-bs-target="#liveStreamSettingsModal">
                    <i class="fas fa-video"></i>
                </button>
            </div>



            <!-- Toggle Button -->
            <div class="position-fixed top-0 end-0 m-3">
                <button class="btn btn-primary rounded-circle" type="button" data-bs-toggle="collapse" data-bs-target="#matchInfo" aria-expanded="false" aria-controls="matchInfo">
                    <i class="fas fa-info"></i>
                </button>
            </div>

            <!-- Match Info Section -->
            <div id="matchInfo" class="collapse match-info position-fixed" style="top: 50px; right: 20px; width: 250px;">
                <div class="card">
                    <div class="card-body">
                        <h5 class="text-center mb-3">Match Details</h5>
                        <div class="text-center mb-3">
                            <p class="mb-1">
                                <strong>Date:</strong> <?= date('F j, Y', strtotime($match['schedule_date'])) ?>
                                <strong class="ms-3">Time:</strong> <?= date('g:i A', strtotime($match['schedule_time'])) ?>
                            </p>
                            <p class="mb-1"><strong>Venue:</strong> <?= htmlspecialchars($match['venue']) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hidden inputs for IDs -->
            <div class="container-fluid mt-4">
                <input type="hidden" id="schedule_id" value="<?= $schedule_id ?>">
                <input type="hidden" id="game_id" value="<?= $game_id ?>">
                <input type="hidden" id="teamA_id" value="<?= $teamA_id ?>">
                <input type="hidden" id="teamB_id" value="<?= $teamB_id ?>">
                <input type="hidden" id="match_id" value="<?= $match['match_id'] ?>">
            </div>

            <!-- Timer Section -->
            <?php if (!empty($rules['duration_per_period'])): ?>
                <div class="col-12 text-center mb-4">
                    <div class="card border-0 shadow-sm" style="width: 100%; max-width: 600px; margin: auto;">
                        <div class="card-body">
                            <h5 class="card-title">Match Timer</h5>
                            <div class="timer-display mb-3" style="font-size: 3rem;">
                                <span id="minutes">00</span>:<span id="seconds">00</span>
                            </div>
                            <div class="timer-controls d-flex justify-content-center">
                                <button id="timer-start" class="btn btn-success mx-1">
                                    <i class="fas fa-play"></i>
                                </button>
                                <button id="timer-pause" class="btn btn-warning mx-1">
                                    <i class="fas fa-pause"></i>
                                </button>
                                <button id="timer-end" class="btn btn-danger mx-1">
                                    <i class="fas fa-stop"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Period Controls -->
            <?php if (!empty($rules['period_type'])): ?>
                <div class="period-section text-center mb-4">
                    <div class="period-controls">
                        <button type="button" class="btn btn-period" id="decrement-period">
                            <i class="fas fa-minus"></i>
                        </button>
                        <div class="period-display">
                            <input type="text" id="periods" class="period-input" value="1" readonly>
                            <label class="period-label">Period</label>
                        </div>
                        <button type="button" class="btn btn-period" id="increment-period">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Scoring Section -->
            <div class="col-12">
                <div class="row justify-content-center">
                    <!-- Team A Score Card -->
                    <div class="col-md-5 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="team-name"><?= htmlspecialchars($match['teamA_name']) ?></h5>
                            </div>
                            <div class="card-body">
                                <div class="score-section">
                                    <div class="score-display">
                                        <input type="number" id="teamA_score" class="score-input" value="0" min="0">
                                        <label class="score-label">Points</label>
                                    </div>
                                    <div class="score-controls">
                                        <button type="button" class="btn btn-score decrement-btn" data-target="teamA_score" data-increment="1">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <div class="increment-controls">
                                            <?php
                                            $increment_options = explode(',', $rules['score_increment_options']);
                                            foreach ($increment_options as $increment) {
                                                $increment = trim($increment);
                                                echo "<button type='button' class='btn btn-score increment-btn' 
                                                      data-target='teamA_score' data-increment='{$increment}'>
                                                      +{$increment}</button>";
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Team B Score Card -->
                    <div class="col-md-5 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="team-name"><?= htmlspecialchars($match['teamB_name']) ?></h5>
                            </div>
                            <div class="card-body">
                                <div class="score-section">
                                    <div class="score-display">
                                        <input type="number" id="teamB_score" class="score-input" value="0" min="0">
                                        <label class="score-label">Points</label>
                                    </div>
                                    <div class="score-controls">
                                        <button type="button" class="btn btn-score decrement-btn" data-target="teamB_score" data-increment="1">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <div class="increment-controls">
                                            <?php
                                            foreach ($increment_options as $increment) {
                                                $increment = trim($increment);
                                                echo "<button type='button' class='btn btn-score increment-btn' 
                                                      data-target='teamB_score' data-increment='{$increment}'>
                                                      +{$increment}</button>";
                                            }
                                            ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Stream Settings Modal -->
    <div class="modal fade" id="liveStreamSettingsModal" tabindex="-1" aria-labelledby="liveStreamSettingsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title" id="liveStreamSettingsModalLabel">Start Live Stream</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Stream Options -->
                    <div class="list-group">
                        <div class="mb-3">
                            <button class="list-group-item list-group-item-action btn-lg text-center">
                                Use Computer Webcam
                            </button>
                        </div>
                        <div class="mb-3">
                            <button class="list-group-item list-group-item-action btn-lg text-center">
                                Use Phone Camera
                            </button>
                        </div>
                        <div class="mb-3">
                            <button class="list-group-item list-group-item-action btn-lg text-center">
                                Share Screen
                            </button>
                        </div>
                    </div>

                    <!-- Facebook Streaming Option -->
                    <div class="d-grid gap-2 mt-4">
                        <button class="btn btn-lg btn-outline-primary" onclick="startFacebookStream()">
                            Facebook
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <?php include 'player_stats.php'; ?>

    <!-- Bottom Player Stats Bar -->
    <div class="player-stats-bar d-none d-md-flex" onclick="window.togglePlayerStats()">
        <i class="fas fa-users me-2"></i>
        <span>Player Statistics</span>
        <i class="fas fa-chevron-up ms-2"></i>
    </div>

    <nav class="bottom-nav d-md-none">
        <div class="nav-item" onclick="window.toggleGameStats()">
            <i class="fas fa-chart-bar"></i>
            <span>Team Stats</span>
        </div>
        <div class="nav-item" onclick="window.togglePlayerStats()">
            <i class="fas fa-users"></i>
            <span>Player Stats</span>
        </div>
    </nav>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Utility functions
        const utils = {
            getElement: (id) => document.getElementById(id),
            formatTime: (seconds) => {
                const mins = Math.floor(seconds / 60);
                const secs = seconds % 60;
                return `${String(mins).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
            },
            showAlert: (options) => Swal.fire(options)
        };

        // Define global toggle functions
        const playerStatsPanel = document.querySelector('.player-stats');
        const overlay = document.querySelector('.overlay');

        window.togglePlayerStats = function() {
            if (playerStatsPanel && overlay) {
                playerStatsPanel.classList.toggle('active');
                overlay.classList.toggle('active');
                if (playerStatsPanel.classList.contains('active')) {
                    playerStatsManager.loadPlayerStats();
                }
            }
        };

        window.closePlayerStats = function() {
            if (playerStatsPanel && overlay) {
                playerStatsPanel.classList.remove('active');
                overlay.classList.remove('active');
            }
        };

        // Player Stats Manager
        const playerStatsManager = {
            init: function() {
                // Handle clicking outside the panel
                document.addEventListener('click', function(event) {
                    if (playerStatsPanel && playerStatsPanel.classList.contains('active') &&
                        !playerStatsPanel.contains(event.target) &&
                        !event.target.closest('.player-stats-bar') &&
                        !event.target.closest('.nav-item')) {
                        window.closePlayerStats();
                    }
                });

                this.loadPlayerStats();
            },

            loadPlayerStats: function() {
                const teamAList = utils.getElement('teamAList');
                const teamBList = utils.getElement('teamBList');
                const scheduleId = utils.getElement('schedule_id').value;
                const gameId = utils.getElement('game_id').value;
                const teamAId = utils.getElement('teamA_id').value;

                if (!teamAList || !teamBList) return;

                // Clear existing content and show loading
                teamAList.innerHTML = '<li class="list-group-item text-center text-muted">Loading players...</li>';
                teamBList.innerHTML = '<li class="list-group-item text-center text-muted">Loading players...</li>';

                // First fetch players
                fetch(`getPlayersByTeams.php?schedule_id=${scheduleId}`)
                    .then(res => {
                        if (!res.ok) {
                            throw new Error('Failed to fetch players');
                        }
                        return res.json();
                    })
                    .then(players => {
                        if (!Array.isArray(players)) {
                            throw new Error('Invalid player data received');
                        }

                        // Clear loading messages
                        teamAList.innerHTML = '';
                        teamBList.innerHTML = '';

                        // Then fetch game stats
                        return fetch(`get_game_stats_config.php?game_id=${gameId}`)
                            .then(res => {
                                if (!res.ok) {
                                    throw new Error('Failed to fetch game stats config');
                                }
                                return res.json();
                            })
                            .then(stats => {
                                if (!Array.isArray(stats)) {
                                    throw new Error('Invalid stats configuration received');
                                }

                                const template = document.getElementById('playerStatTemplate');

                                players.forEach(player => {
                                    const playerItem = document.createElement('li');
                                    playerItem.className = 'list-group-item';

                                    // Create player header
                                    const playerHeader = document.createElement('div');
                                    playerHeader.className = 'd-flex justify-content-between align-items-center mb-3';
                                    playerHeader.innerHTML = `
                                        <div class="player-name fw-bold">${player.player_name}</div>
                                        <span class="badge bg-secondary">#${player.jersey_number || 'N/A'}</span>
                                    `;
                                    playerItem.appendChild(playerHeader);

                                    // Create stats container
                                    const statsContainer = document.createElement('div');
                                    statsContainer.className = 'player-stats-container';

                                    stats.forEach(stat => {
                                        const statRow = template.content.cloneNode(true);
                                        const statName = statRow.querySelector('.stat-name');
                                        const statValue = statRow.querySelector('.stat-value');
                                        const incrementBtn = statRow.querySelector('.increment-stat');
                                        const decrementBtn = statRow.querySelector('.decrement-stat');

                                        statName.textContent = stat.stat_name;
                                        statValue.setAttribute('data-player-id', player.player_id);
                                        statValue.setAttribute('data-stat-id', stat.config_id);

                                        incrementBtn.onclick = () => {
                                            const currentValue = parseInt(statValue.textContent);
                                            statValue.textContent = currentValue + 1;
                                            this.updatePlayerStat(player.player_id, stat.config_id, currentValue + 1);
                                        };

                                        decrementBtn.onclick = () => {
                                            const currentValue = parseInt(statValue.textContent);
                                            if (currentValue > 0) {
                                                statValue.textContent = currentValue - 1;
                                                this.updatePlayerStat(player.player_id, stat.config_id, currentValue - 1);
                                            }
                                        };

                                        statsContainer.appendChild(statRow);
                                    });

                                    playerItem.appendChild(statsContainer);

                                    // Add to appropriate team list
                                    if (player.team_id === parseInt(teamAId)) {
                                        teamAList.appendChild(playerItem);
                                    } else {
                                        teamBList.appendChild(playerItem);
                                    }
                                });
                            });
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        const errorMessage = '<li class="list-group-item text-center text-danger">Failed to load players. Please try again.</li>';
                        teamAList.innerHTML = errorMessage;
                        teamBList.innerHTML = errorMessage;
                        utils.showAlert({
                            icon: 'error',
                            title: 'Error',
                            text: error.message || 'Failed to load players'
                        });
                    });
            },
            updatePlayerStat: function(playerId, statId, value) {
                const scheduleId = utils.getElement('schedule_id').value;
                fetch(`update_player_stat.php?schedule_id=${scheduleId}`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            player_id: playerId,
                            stat_id: statId,
                            value: value
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            console.error('Error updating stat:', data.error);
                            utils.showAlert({
                                icon: 'error',
                                title: 'Error',
                                text: data.error
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error updating stat:', error);
                        utils.showAlert({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to update player statistic'
                        });
                    });
            }
        };

        // Score Management
        const scoreManager = {
            init: function() {
                // Initialize score buttons
                document.querySelectorAll('.increment-btn, .decrement-btn').forEach(button => {
                    button.addEventListener('click', (e) => {
                        e.preventDefault();
                        const targetId = button.getAttribute('data-target');
                        const increment = parseInt(button.getAttribute('data-increment')) || 1;
                        const change = button.classList.contains('decrement-btn') ? -increment : increment;
                        this.handleScoreChange(targetId, change);
                    });
                });

                // Initialize score inputs
                document.querySelectorAll('.score-input').forEach(input => {
                    input.addEventListener('input', () => {
                        this.updateScore(input.id, input.value);
                    });

                    input.addEventListener('blur', () => {
                        if (input.value === '' || isNaN(input.value)) {
                            this.updateScore(input.id, 0);
                        }
                    });
                });
            },
            handleScoreChange: function(targetId, change) {
                const input = utils.getElement(targetId);
                if (!input) return;

                const currentValue = parseInt(input.value) || 0;
                this.updateScore(targetId, currentValue + change);
            },
            updateScore: function(targetId, newValue) {
                const input = utils.getElement(targetId);
                if (!input) {
                    console.error(`Score input ${targetId} not found`);
                    return;
                }

                // Validate and cap the score
                newValue = Math.max(0, parseInt(newValue) || 0);
                if (gameConfig.pointCap && newValue > gameConfig.pointCap) {
                    newValue = gameConfig.pointCap;
                    utils.showAlert({
                        icon: 'warning',
                        title: 'Point Cap Reached',
                        text: `Maximum points allowed is ${gameConfig.pointCap}`
                    });
                }

                input.value = newValue;
                this.sendUpdate();
            },
            sendUpdate: function() {
                const data = {
                    schedule_id: utils.getElement('schedule_id')?.value,
                    game_id: utils.getElement('game_id')?.value,
                    teamA_id: utils.getElement('teamA_id')?.value,
                    teamB_id: utils.getElement('teamB_id')?.value,
                    teamA_score: parseInt(utils.getElement('teamA_score')?.value) || 0,
                    teamB_score: parseInt(utils.getElement('teamB_score')?.value) || 0,
                    current_period: utils.getElement('periods')?.value || 1,
                    time_remaining: gameState.timer.remaining,
                    timer_status: gameState.timer.status
                };

                if (!data.schedule_id || !data.game_id || !data.teamA_id || !data.teamB_id) {
                    console.error('Required game data missing');
                    return;
                }

                fetch('update_score.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => response.json())
                    .then(result => {
                        if (result.error) {
                            throw new Error(result.error);
                        }
                    })
                    .catch(error => {
                        console.error('Error updating score:', error);
                        utils.showAlert({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to update score'
                        });
                    });
            }
        };

        // Timer Management
        const timerManager = {
            init: function() {
                // Initialize timer controls
                ['start', 'pause', 'end'].forEach(action => {
                    const button = utils.getElement(`timer-${action}`);
                    if (button) {
                        button.addEventListener('click', (e) => {
                            e.preventDefault();
                            this[action]();
                        });
                    }
                });

                // Initialize period controls
                ['increment', 'decrement'].forEach(action => {
                    const button = utils.getElement(`${action}-period`);
                    if (button) {
                        button.addEventListener('click', () => {
                            const periodsInput = utils.getElement('periods');
                            if (!periodsInput) return;

                            let currentPeriod = parseInt(periodsInput.value) || 1;
                            if (action === 'increment' && currentPeriod < gameConfig.maxPeriods) {
                                currentPeriod++;
                            } else if (action === 'decrement' && currentPeriod > 1) {
                                currentPeriod--;
                            }
                            periodsInput.value = currentPeriod;
                            scoreManager.sendUpdate();
                        });
                    }
                });

                // Update initial display
                this.updateDisplay();
            },
            updateDisplay: function() {
                if (!utils.getElement('minutes') || !utils.getElement('seconds')) return;

                const timeString = utils.formatTime(gameState.timer.remaining);
                const [minutes, seconds] = timeString.split(':');
                utils.getElement('minutes').textContent = minutes;
                utils.getElement('seconds').textContent = seconds;

                // Update button states
                const startBtn = utils.getElement('timer-start');
                const pauseBtn = utils.getElement('timer-pause');
                const endBtn = utils.getElement('timer-end');

                if (!startBtn || !pauseBtn || !endBtn) return;

                startBtn.classList.toggle('disabled', gameState.timer.status === 'running');
                pauseBtn.classList.toggle('disabled', gameState.timer.status !== 'running');
                endBtn.classList.toggle('disabled', gameState.timer.status === 'ended');
            },
            start: function() {
                if (gameState.timer.status === 'running') return;

                if (gameState.timer.interval) {
                    clearInterval(gameState.timer.interval);
                }

                gameState.timer.status = 'running';
                this.updateDisplay();
                scoreManager.sendUpdate();

                gameState.timer.interval = setInterval(() => {
                    if (gameState.timer.remaining > 0) {
                        gameState.timer.remaining--;
                        this.updateDisplay();
                        // Send update every 5 seconds to avoid too many requests
                        if (gameState.timer.remaining % 5 === 0) {
                            scoreManager.sendUpdate();
                        }
                    } else {
                        this.end();
                    }
                }, 1000);
            },
            pause: function() {
                if (gameState.timer.status !== 'running') return;

                clearInterval(gameState.timer.interval);
                gameState.timer.interval = null;
                gameState.timer.status = 'paused';
                this.updateDisplay();
                scoreManager.sendUpdate();
            },
            end: function() {
                clearInterval(gameState.timer.interval);
                gameState.timer.interval = null;
                gameState.timer.status = 'ended';
                gameState.timer.remaining = 0;
                this.updateDisplay();
                scoreManager.sendUpdate();
                this.handlePeriodEnd();
            },
            handlePeriodEnd: function() {
                const currentPeriod = parseInt(utils.getElement('periods')?.value) || 1;
                const teamAScore = parseInt(utils.getElement('teamA_score')?.value) || 0;
                const teamBScore = parseInt(utils.getElement('teamB_score')?.value) || 0;

                if (currentPeriod === gameConfig.maxPeriods && teamAScore === teamBScore) {
                    utils.showAlert({
                        title: 'Game Tied!',
                        text: 'Proceed to overtime period?',
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonText: 'Yes',
                        cancelButtonText: 'No'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            if (utils.getElement('periods')) {
                                utils.getElement('periods').value = 'OT';
                            }
                            gameState.timer.remaining = 300; // 5 minutes overtime
                            gameState.timer.status = 'paused';
                            this.updateDisplay();
                            scoreManager.sendUpdate();
                        }
                    });
                } else if (currentPeriod < gameConfig.maxPeriods) {
                    if (utils.getElement('periods')) {
                        utils.getElement('periods').value = currentPeriod + 1;
                    }
                    gameState.timer.remaining = gameConfig.periodDuration;
                    gameState.timer.status = 'paused';
                    this.updateDisplay();
                    scoreManager.sendUpdate();

                    utils.showAlert({
                        title: 'Period Ended',
                        text: `Starting Period ${currentPeriod + 1}`,
                        icon: 'info',
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    utils.showAlert({
                        title: 'Game Over!',
                        text: `Final Score: ${teamAScore} - ${teamBScore}`,
                        icon: 'info'
                    });
                }
            }
        };

        // Initialize all managers
        function initializeAll() {
            playerStatsManager.init();
            scoreManager.init();
            timerManager.init();

            // Initialize end match button
            const endMatchButton = utils.getElement('end-match-button');
            if (endMatchButton) {
                endMatchButton.addEventListener('click', () => {
                    utils.showAlert({
                        title: 'End Match?',
                        text: 'Are you sure you want to end this match?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes',
                        cancelButtonText: 'No'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const scheduleId = utils.getElement('schedule_id').value;
                            const matchId = utils.getElement('match_id').value;

                            fetch('process_end_match.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        schedule_id: scheduleId
                                    })
                                })
                                .then(response => response.json())
                                .then(response => {
                                    if (response.success) {
                                        utils.showAlert({
                                            title: 'Match Ended!',
                                            text: 'The match has concluded successfully.',
                                            icon: 'success',
                                            showCancelButton: true,
                                            confirmButtonText: 'View Summary',
                                            cancelButtonText: 'Back to Matches'
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                window.location.href = `match_summary.php?match_id=${matchId}&status=${response.status}`;
                                            } else {
                                                window.location.href = 'match_list.php';
                                            }
                                        });
                                    } else {
                                        if (response.overtime_required) {
                                            utils.showAlert({
                                                title: 'Overtime Required',
                                                text: response.error,
                                                icon: 'info',
                                                showCancelButton: true,
                                                confirmButtonText: 'Start Overtime',
                                                cancelButtonText: 'Cancel'
                                            }).then((result) => {
                                                if (result.isConfirmed) {
                                                    // Set up overtime period
                                                    utils.getElement('periods').value = 'OT';
                                                    // Reset timer for overtime
                                                    timerManager.setTime(5 * 60); // 5 minutes
                                                    timerManager.pause();
                                                    scoreManager.sendUpdate();
                                                }
                                            });
                                        } else {
                                            throw new Error(response.error || 'Failed to end match');
                                        }
                                    }
                                })
                                .catch(error => {
                                    console.error('Error ending match:', error);
                                    utils.showAlert({
                                        title: 'Error',
                                        text: error.message || 'Failed to end match. Please try again.',
                                        icon: 'error'
                                    });
                                });
                        }
                    });
                });
            }
        }

        // Initialize constants from PHP
        const gameConfig = {
            maxPeriods: <?= json_encode($rules['number_of_periods']) ?>,
            periodDuration: <?= json_encode($rules['duration_per_period'] * 60) ?>,
            timeLimit: <?= json_encode($rules['time_limit']) ?>,
            pointCap: <?= json_encode($rules['point_cap']) ?>,
            gameId: <?= json_encode($game_id) ?>
        };

        // Initialize team IDs
        const teamAId = <?= json_encode($teamA_id) ?>;
        const teamBId = <?= json_encode($teamB_id) ?>;

        // Initialize scores and timer from PHP
        const initialState = {
            teamA: <?= json_encode($match['teamA_score'] ?? 0) ?>,
            teamB: <?= json_encode($match['teamB_score'] ?? 0) ?>,
            currentPeriod: <?= json_encode($match['current_period'] ?? 1) ?>,
            timeRemaining: <?= json_encode($match['time_remaining'] ?? ($rules['duration_per_period'] * 60)) ?>,
            timerStatus: <?= json_encode($match['timer_status'] ?? 'paused') ?>
        };

        // Set initial scores
        document.getElementById('teamA_score').value = initialState.teamA;
        document.getElementById('teamB_score').value = initialState.teamB;
        document.getElementById('periods').value = initialState.currentPeriod;

        // Game state management
        const gameState = {
            timer: {
                remaining: initialState.timeRemaining,
                status: initialState.timerStatus,
                interval: null
            },
            scores: {
                teamA: initialState.teamA,
                teamB: initialState.teamB
            },
            currentPeriod: initialState.currentPeriod
        };

        // When the DOM is ready, initialize everything
        document.addEventListener('DOMContentLoaded', initializeAll);
    </script>
</body>

</html>