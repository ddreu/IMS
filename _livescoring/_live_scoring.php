<?php
include_once '../connection/conn.php';
$conn = con();
session_start();

// Get query parameters
$schedule_id = $_GET['schedule_id'];
$teamA_id = $_GET['teamA_id'];
$teamB_id = $_GET['teamB_id'];
$game_id = $_SESSION['game_id'];
$department_id = $_SESSION['department_id'];

// Fetch match details
$match_query = $conn->prepare("
    SELECT g.game_name, tA.team_name AS teamA_name, tB.team_name AS teamB_name, 
           s.schedule_date, s.schedule_time, s.venue, s.match_id
    FROM schedules s
    JOIN matches m ON s.match_id = m.match_id
    JOIN brackets b ON m.bracket_id = b.bracket_id
    JOIN games g ON b.game_id = g.game_id
    JOIN teams tA ON m.teamA_id = tA.team_id
    JOIN teams tB ON m.teamB_id = tB.team_id
    WHERE s.schedule_id = ?");
$match_query->bind_param("i", $schedule_id);
$match_query->execute();
$match = $match_query->get_result()->fetch_assoc();

$rules_query = $conn->prepare("
    SELECT scoring_unit, score_increment_options, period_type, number_of_periods, 
           duration_per_period, time_limit, point_cap, max_fouls, timeouts_per_period
    FROM game_scoring_rules
    WHERE game_id = ? AND department_id = ?");

$rules_query->bind_param("ii", $game_id, $department_id);
$rules_query->execute();
$rules = $rules_query->get_result()->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Live Scoring</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="livescoring.css">
    <style>
        .player-stats-header {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            align-items: center;
            justify-content: space-between;
            position: relative;
            background: #f8f9fa;
        }

        .player-stats-header .handle {
            width: 40px;
            height: 4px;
            background: #dee2e6;
            border-radius: 2px;
            position: absolute;
            top: 8px;
            left: 50%;
            transform: translateX(-50%);
            cursor: pointer;
        }

        .player-stats-header .btn-close {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
        }

        .player-stats-header h4 {
            flex: 1;
            text-align: center;
            margin-top: 0.5rem;
        }

        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 999;
            display: none;
        }

        .overlay.active {
            display: block;
        }

        .player-stats {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 80vh;
            background: white;
            z-index: 1000;
            transform: translateY(100%);
            transition: transform 0.3s ease;
        }

        .player-stats.active {
            transform: translateY(0);
        }

        .timer-section {
            margin: 20px 0;
        }
        .timer-display {
            font-size: 2.5rem;
            font-weight: bold;
            font-family: monospace;
            text-align: center;
            margin: 10px 0;
        }
        .timer-controls {
            display: flex;
            justify-content: center;
            gap: 10px;
        }
        .timer-controls button {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
    </style>
</head>

<body>
    <div class="overlay" onclick="window.closePlayerStats()"></div>
    <div class="main-container">
        <div class="main-content">
            <button type="button" class="btn btn-danger" id="end-match-button">
                <i class="fas fa-stop-circle me-2"></i>End Match
            </button>

            <div class="match-header text-center">
                <h2><?= htmlspecialchars($match['game_name']); ?></h2>
                <h4><?= htmlspecialchars($match['teamA_name']); ?> vs. <?= htmlspecialchars($match['teamB_name']); ?></h4>
                <p>
                    <i class="far fa-calendar-alt me-2"></i><?= htmlspecialchars(date("M d, Y", strtotime($match['schedule_date']))); ?>
                    <i class="far fa-clock ms-3 me-2"></i><?= htmlspecialchars(date("g:i A", strtotime($match['schedule_time']))); ?>
                    <i class="fas fa-map-marker-alt ms-3 me-2"></i><?= htmlspecialchars($match['venue']); ?>
                </p>
                
                <!-- Timer Section -->
                <div class="timer-section mt-3">
                    <div class="row justify-content-center">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title"><?= htmlspecialchars($rules[0]['period_type']); ?> Timer</h5>
                                    <div class="timer-display">
                                        <span id="timer-minutes">00</span>:<span id="timer-seconds">00</span>
                                    </div>
                                    <div class="timer-controls mt-2">
                                        <button class="btn btn-sm btn-success" id="timer-start">
                                            <i class="fas fa-play"></i>
                                        </button>
                                        <button class="btn btn-sm btn-warning" id="timer-pause">
                                            <i class="fas fa-pause"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger" id="timer-end">
                                            <i class="fas fa-stop"></i>
                                        </button>
                                    </div>
                                    <div id="timer-status"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="container-fluid mt-4">
                <!-- Hidden inputs for IDs -->
                <input type="hidden" id="schedule_id" value="<?= $schedule_id ?>">
                <input type="hidden" id="game_id" value="<?= $game_id ?>">
                <input type="hidden" id="teamA_id" value="<?= $teamA_id ?>">
                <input type="hidden" id="teamB_id" value="<?= $teamB_id ?>">
                <input type="hidden" id="match_id" value="<?= $match['match_id'] ?>">

                <?php if (!empty($rules[0]['period_type'])): ?>
                <div class="period-control text-center mb-4">
                    <h5 class="period-title"><?= htmlspecialchars($rules[0]['period_type']); ?> <span class="period-max">(Max: <?= htmlspecialchars($rules[0]['number_of_periods']); ?>)</span></h5>
                    <div class="period-controls">
                        <button type="button" class="btn btn-period" id="decrement-period">
                            <i class="fas fa-minus"></i>
                        </button>
                        <div class="period-display">
                            <input type="text" name="periods" id="periods" class="period-input" value="1" readonly>
                            <label class="period-label">Current Period</label>
                        </div>
                        <button type="button" class="btn btn-period" id="increment-period">
                            <i class="fas fa-plus"></i>
                        </button>
                    </div>
                </div>
                <?php endif; ?>

                <div class="row justify-content-center">
                    <!-- Team A Score Card -->
                    <div class="col-md-5">
                        <div class="card score-card">
                            <div class="card-header">
                                <h5 class="team-name"><?= htmlspecialchars($match['teamA_name']); ?></h5>
                            </div>
                            <div class="card-body">
                                <div class="score-section">
                                    <div class="score-display">
                                        <input type="number" id="teamA_score" name="teamA_score" class="score-input" value="<?= htmlspecialchars($match['teamA_score'] ?? 0) ?>" min="0">
                                        <label class="score-label">Points</label>
                                    </div>
                                    <div class="score-controls">
                                        <button type="button" class="btn btn-score decrement-btn" data-target="teamA_score" data-increment="1">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <div class="increment-controls">
                                            <?php
                                            // Parse score increment options from rules
                                            $increment_options = explode(',', $rules[0]['score_increment_options']);
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
                    <div class="col-md-5">
                        <div class="card score-card">
                            <div class="card-header">
                                <h5 class="team-name"><?= htmlspecialchars($match['teamB_name']); ?></h5>
                            </div>
                            <div class="card-body">
                                <div class="score-section">
                                    <div class="score-display">
                                        <input type="number" id="teamB_score" name="teamB_score" class="score-input" value="<?= htmlspecialchars($match['teamB_score'] ?? 0) ?>" min="0">
                                        <label class="score-label">Points</label>
                                    </div>
                                    <div class="score-controls">
                                        <button type="button" class="btn btn-score decrement-btn" data-target="teamB_score" data-increment="1">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <div class="increment-controls">
                                            <?php
                                            // Parse score increment options from rules
                                            $increment_options = explode(',', $rules[0]['score_increment_options']);
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

            <?php if (!empty($rules[0]['period_type'])): ?>
            <?php endif; ?>

        </div>

        <!-- Team Stats Panel -->
        <?php if ($rules[0]['timeouts_per_period'] > 0 || $rules[0]['max_fouls'] > 0): ?>
        <div class="stats-sidebar">
            <div class="stats-sidebar-header">
                <div class="handle"></div>
                <h4 class="mb-0">Team Stats</h4>
            </div>
            <div class="stats-sidebar-content">
                <!-- Team A Stats -->
                <div class="stats-section">
                    <h5 class="stats-title text-center"><?= htmlspecialchars($match['teamA_name']); ?></h5>
                    <?php if ($rules[0]['timeouts_per_period'] > 0): ?>
                    <div class="mb-3">
                        <p class="mb-2 text-center">Timeouts (Max: <?= $rules[0]['timeouts_per_period']; ?>)</p>
                        <div class="d-flex justify-content-center align-items-center">
                            <button type="button" class="btn btn-outline-secondary btn-score timeout-decrement" data-target="teamA_timeout">-</button>
                            <input type="number" name="teamA_timeout" id="teamA_timeout" class="period-input mx-2" value="<?= $rules[0]['timeouts_per_period']; ?>" min="0" max="<?= $rules[0]['timeouts_per_period']; ?>">
                            <button type="button" class="btn btn-outline-secondary btn-score timeout-increment" data-target="teamA_timeout">+</button>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($rules[0]['max_fouls'] > 0): ?>
                    <div>
                        <p class="mb-2 text-center">Team Fouls (Max: <?= $rules[0]['max_fouls']; ?>)</p>
                        <div class="d-flex justify-content-center align-items-center">
                            <button type="button" class="btn btn-outline-secondary btn-score foul-decrement" data-target="teamA_fouls">-</button>
                            <input type="number" name="teamA_fouls" id="teamA_fouls" class="period-input mx-2" value="0" min="0" max="<?= $rules[0]['max_fouls']; ?>">
                            <button type="button" class="btn btn-outline-secondary btn-score foul-increment" data-target="teamA_fouls">+</button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Team B Stats -->
                <div class="stats-section">
                    <h5 class="stats-title text-center"><?= htmlspecialchars($match['teamB_name']); ?></h5>
                    <?php if ($rules[0]['timeouts_per_period'] > 0): ?>
                    <div class="mb-3">
                        <p class="mb-2 text-center">Timeouts (Max: <?= $rules[0]['timeouts_per_period']; ?>)</p>
                        <div class="d-flex justify-content-center align-items-center">
                            <button type="button" class="btn btn-outline-secondary btn-score timeout-decrement" data-target="teamB_timeout">-</button>
                            <input type="number" name="teamB_timeout" id="teamB_timeout" class="period-input mx-2" value="<?= $rules[0]['timeouts_per_period']; ?>" min="0" max="<?= $rules[0]['timeouts_per_period']; ?>">
                            <button type="button" class="btn btn-outline-secondary btn-score timeout-increment" data-target="teamB_timeout">+</button>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php if ($rules[0]['max_fouls'] > 0): ?>
                    <div>
                        <p class="mb-2 text-center">Team Fouls (Max: <?= $rules[0]['max_fouls']; ?>)</p>
                        <div class="d-flex justify-content-center align-items-center">
                            <button type="button" class="btn btn-outline-secondary btn-score foul-decrement" data-target="teamB_fouls">-</button>
                            <input type="number" name="teamB_fouls" id="teamB_fouls" class="period-input mx-2" value="0" min="0" max="<?= $rules[0]['max_fouls']; ?>">
                            <button type="button" class="btn btn-outline-secondary btn-score foul-increment" data-target="teamB_fouls">+</button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Player Stats Panel -->
        <div class="player-stats">
            <div class="player-stats-header">
                <div class="handle"></div>
                <h4 class="mb-0">Player Statistics</h4>
                <button type="button" class="btn-close" onclick="window.closePlayerStats()"></button>
            </div>
            <div class="player-stats-content">
                <div class="container">
                    <div class="row">
                        <div class="col-md-6">
                            <h5 class="text-center"><?= htmlspecialchars($match['teamA_name']); ?></h5>
                            <ul class="list-group" id="teamAList">
                                <li class="list-group-item text-center text-muted">Loading players...</li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h5 class="text-center"><?= htmlspecialchars($match['teamB_name']); ?></h5>
                            <ul class="list-group" id="teamBList">
                                <li class="list-group-item text-center text-muted">Loading players...</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>

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

     
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const maxPeriods = <?= json_encode($rules[0]['number_of_periods']); ?>;
                const maxFouls = <?= json_encode($rules[0]['max_fouls']); ?>;
                const maxTimeouts = <?= json_encode($rules[0]['timeouts_per_period']); ?>;
                const pointCap = <?= json_encode($rules[0]['point_cap']); ?>;
                const timeLimit = <?= json_encode($rules[0]['time_limit']); ?>;
                const durationPerPeriod = <?= json_encode($rules[0]['duration_per_period']); ?>;
                const teamAList = document.getElementById('teamAList');
                const teamBList = document.getElementById('teamBList');
                const scheduleId = document.getElementById('schedule_id').value;
                const gameId = document.getElementById('game_id').value;
                const teamAId = document.getElementById('teamA_id').value;
                const teamBId = document.getElementById('teamB_id').value;
                const playerStatsPanel = document.querySelector('.player-stats');
                const overlay = document.querySelector('.overlay');
                const matchId = <?= json_encode($match['match_id']); ?>;

                // Define toggle functions
                window.togglePlayerStats = function() {
                    playerStatsPanel.classList.toggle('active');
                    overlay.classList.toggle('active');
                    document.body.style.overflow = playerStatsPanel.classList.contains('active') ? 'hidden' : 'auto';
                };

                window.closePlayerStats = function() {
                    playerStatsPanel.classList.remove('active');
                    overlay.classList.remove('active');
                    document.body.style.overflow = 'auto';
                };

                // Handle clicking outside the panel
                document.addEventListener('click', function(event) {
                    if (playerStatsPanel.classList.contains('active') && 
                        !playerStatsPanel.contains(event.target) && 
                        !event.target.closest('.player-stats-bar') && 
                        !event.target.closest('.nav-item')) {
                        window.closePlayerStats();
                    }
                });

                // Function to initialize player stats
                function initializePlayerStats() {
                    const teamAList = document.getElementById('teamAList');
                    const teamBList = document.getElementById('teamBList');
                    
                    // Clear existing content
                    teamAList.innerHTML = '';
                    teamBList.innerHTML = '';

                    // First fetch players
                    fetch(`getPlayersByTeams.php?schedule_id=${scheduleId}`)
                        .then(res => res.json())
                        .then(players => {
                            // Then fetch game stats
                            fetch(`get_game_stats_config.php?game_id=${gameId}`)
                                .then(res => res.json())
                                .then(stats => {
                                    players.forEach(player => {
                                        const playerItem = document.createElement('div');
                                        playerItem.className = 'list-group-item';
                                        playerItem.innerHTML = `
                                            <div class="player-name mb-2 fw-bold">
                                                #${player.jersey_number} ${player.player_name}
                                            </div>
                                            <div class="stats-container"></div>
                                        `;

                                        const statsContainer = playerItem.querySelector('.stats-container');

                                        stats.forEach(stat => {
                                            const statRow = document.createElement('div');
                                            statRow.className = 'player-stat-row mb-2';
                                            statRow.innerHTML = `
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div class="stat-name">${stat.stat_name}</div>
                                                    <div class="stat-controls d-flex align-items-center gap-2">
                                                        <button class="btn btn-sm btn-danger decrement-stat">-</button>
                                                        <span class="stat-value" data-player-id="${player.player_id}" data-stat-id="${stat.config_id}">0</span>
                                                        <button class="btn btn-sm btn-success increment-stat">+</button>
                                                    </div>
                                                </div>
                                            `;

                                            const statValue = statRow.querySelector('.stat-value');
                                            const incrementBtn = statRow.querySelector('.increment-stat');
                                            const decrementBtn = statRow.querySelector('.decrement-stat');

                                            // Add click handlers for increment/decrement
                                            incrementBtn.onclick = () => {
                                                const currentValue = parseInt(statValue.textContent);
                                                statValue.textContent = currentValue + 1;
                                                updatePlayerStat(player.player_id, stat.config_id, currentValue + 1);
                                            };

                                            decrementBtn.onclick = () => {
                                                const currentValue = parseInt(statValue.textContent);
                                                if (currentValue > 0) {
                                                    statValue.textContent = currentValue - 1;
                                                    updatePlayerStat(player.player_id, stat.config_id, currentValue - 1);
                                                }
                                            };

                                            statsContainer.appendChild(statRow);
                                        });

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
                            console.error('Error initializing player stats:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to load player statistics'
                            });
                        });
                }

                // Function to update player stat
                function updatePlayerStat(playerId, statId, value) {
                    const scheduleId = new URLSearchParams(window.location.search).get('schedule_id');
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
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.error
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error updating stat:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to update player statistic'
                        });
                    });
                }

                // Initialize player stats when page loads
                initializePlayerStats();

                // Function to send score updates to the server
                function sendScoreUpdate() {
                    const scheduleId = document.getElementById('schedule_id')?.value;
                    const gameId = document.getElementById('game_id')?.value;
                    const teamAId = document.getElementById('teamA_id')?.value;
                    const teamBId = document.getElementById('teamB_id')?.value;
                    const teamAScore = document.getElementById('teamA_score');
                    const teamBScore = document.getElementById('teamB_score');
                    const periodInput = document.getElementById('periods');

                    if (!scheduleId || !gameId || !teamAId || !teamBId || !teamAScore || !teamBScore) {
                        console.error('Required elements not found');
                        return;
                    }

                    const data = {
                        schedule_id: scheduleId,
                        game_id: gameId,
                        teamA_id: teamAId,
                        teamB_id: teamBId,
                        teamA_score: parseInt(teamAScore.value) || 0,
                        teamB_score: parseInt(teamBScore.value) || 0,
                        current_period: periodInput ? (parseInt(periodInput.value) || 1) : 1
                    };

                    fetch('update_score.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            console.error('Error updating score:', data.error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.error
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error updating score:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to update score'
                        });
                    });
                }

                // Function to handle increment/decrement
                function handleScoreChange(targetId, change) {
                    const input = document.getElementById(targetId);
                    if (!input) {
                        console.error(`Score input element ${targetId} not found`);
                        return;
                    }

                    const currentValue = parseInt(input.value) || 0;
                    const newValue = Math.max(0, currentValue + change);
                    
                    updateScore(targetId, newValue);
                }

                // Initialize score buttons
                function initializeScoreButtons() {
                    // Clear any existing handlers
                    const scoreButtons = document.querySelectorAll('.increment-btn, .decrement-btn');
                    scoreButtons.forEach(button => {
                        const newButton = button.cloneNode(true);
                        button.parentNode.replaceChild(newButton, button);
                    });

                    // Add handlers for increment buttons
                    document.querySelectorAll('.increment-btn').forEach(button => {
                        button.onclick = function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            const targetId = this.getAttribute('data-target');
                            const increment = parseInt(this.getAttribute('data-increment')) || 1;
                            if (targetId) {
                                handleScoreChange(targetId, increment);
                            }
                        };
                    });

                    // Add handlers for decrement buttons
                    document.querySelectorAll('.decrement-btn').forEach(button => {
                        button.onclick = function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            const targetId = this.getAttribute('data-target');
                            if (targetId) {
                                handleScoreChange(targetId, -1);
                            }
                        };
                    });

                    // Add handlers for score inputs
                    document.querySelectorAll('.score-input').forEach(input => {
                        input.setAttribute('min', '0');
                        
                        input.oninput = function() {
                            if (this.id) {
                                updateScore(this.id, this.value);
                            }
                        };
                        
                        input.onblur = function() {
                            if (this.id && (this.value === '' || isNaN(this.value))) {
                                updateScore(this.id, 0);
                            }
                        };
                    });
                }

                // Initialize score buttons when page loads
                initializeScoreButtons();

                // Single function to handle all score updates
                function updateScore(targetId, newValue) {
                    const input = document.getElementById(targetId);
                    if (!input) {
                        console.error(`Score input element ${targetId} not found`);
                        return;
                    }

                    // Ensure value is a number and not negative
                    newValue = Math.max(0, parseInt(newValue) || 0);
                    
                    // Check point cap
                    if (pointCap && newValue > pointCap) {
                        newValue = pointCap;
                        Swal.fire({
                            icon: 'warning',
                            title: 'Point Cap Reached',
                            text: `Maximum points allowed is ${pointCap}`
                        });
                    }
                    
                    input.value = newValue;
                    sendScoreUpdate();
                }

                // Function to send score updates to the server
                function sendScoreUpdate() {
                    const teamAScore = document.getElementById('teamA_score');
                    const teamBScore = document.getElementById('teamB_score');
                    const periodInput = document.getElementById('periods');
                    
                    if (!teamAScore || !teamBScore) {
                        console.error('Score elements not found');
                        return;
                    }

                    const currentPeriod = periodInput ? parseInt(periodInput.value) || 1 : 1;
                    
                    fetch('update_score.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({
                            schedule_id: document.getElementById('schedule_id').value,
                            teamA_score: parseInt(teamAScore.value) || 0,
                            teamB_score: parseInt(teamBScore.value) || 0,
                            current_period: currentPeriod
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.error) {
                            console.error('Error updating score:', data.error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: data.error
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error updating score:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Failed to update score'
                        });
                    });
                }

                // Handle timeout and foul buttons separately
                document.querySelectorAll('.timeout-increment, .timeout-decrement, .foul-increment, .foul-decrement')
                    .forEach(button => {
                        button.onclick = function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            const target = document.getElementById(button.getAttribute('data-target'));
                            let currentValue = parseInt(target.value) || 0;

                            if (button.classList.contains('timeout-increment')) {
                                if (currentValue < maxTimeouts) currentValue += 1;
                            } else if (button.classList.contains('timeout-decrement')) {
                                currentValue = Math.max(0, currentValue - 1);
                            } else if (button.classList.contains('foul-increment')) {
                                if (currentValue < maxFouls) currentValue += 1;
                            } else if (button.classList.contains('foul-decrement')) {
                                currentValue = Math.max(0, currentValue - 1);
                            }

                            target.value = currentValue;
                            sendScoreUpdate();
                        };
                    });

                // Remove any other increment/decrement handlers
                const oldHandlers = document.querySelectorAll('.increment-btn, .decrement-btn');
                oldHandlers.forEach(button => {
                    const newButton = button.cloneNode(true);
                    button.parentNode.replaceChild(newButton, button);
                });

                // Score update function
                function sendScoreUpdate() {
                    const data = {
                        schedule_id: document.getElementById('schedule_id').value,
                        game_id: document.getElementById('game_id').value,
                        teamA_id: document.getElementById('teamA_id').value,
                        teamB_id: document.getElementById('teamB_id').value,
                        teamA_score: parseInt(document.getElementById("teamA_score").value) || 0,
                        teamB_score: parseInt(document.getElementById("teamB_score").value) || 0,
                        timeout_teamA: parseInt(document.getElementById("teamA_timeout").value) || 0,
                        timeout_teamB: parseInt(document.getElementById("teamB_timeout").value) || 0,
                        foul_teamA: parseInt(document.getElementById("teamA_fouls").value) || 0,
                        foul_teamB: parseInt(document.getElementById("teamB_fouls").value) || 0,
                        periods: document.getElementById("periods").value,
                        time_remaining: timer.remaining,
                        timer_status: timer.status
                    };

                    console.log('Sending data:', data);

                    fetch('update_live_score.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify(data)
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error(`HTTP error! status: ${response.status}`);
                        }
                        return response.json();
                    })
                    .then(result => {
                        if (result.status === 'error') {
                            throw new Error(result.message || 'Unknown error occurred');
                        }
                        console.log('Update successful:', result);
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: error.message || 'Failed to update scores. Please try again.'
                        });
                    });
                }

                // Timer initialization
                const periodDuration = <?= json_encode($rules[0]['duration_per_period'] * 60); ?>; // Convert minutes to seconds
                
                let timer = {
                    remaining: periodDuration,
                    status: 'paused',
                    interval: null
                };

                function updateTimerDisplay() {
                    const minutes = Math.floor(timer.remaining / 60);
                    const seconds = timer.remaining % 60;
                    document.getElementById('timer-minutes').textContent = minutes.toString().padStart(2, '0');
                    document.getElementById('timer-seconds').textContent = seconds.toString().padStart(2, '0');
                    
                    // Update timer status display
                    const timerStatusDisplay = document.getElementById('timer-status');
                    if (timerStatusDisplay) {
                        timerStatusDisplay.textContent = timer.status.charAt(0).toUpperCase() + timer.status.slice(1);
                    }

                    // Update button states
                    const startBtn = document.getElementById('timer-start');
                    const pauseBtn = document.getElementById('timer-pause');
                    const endBtn = document.getElementById('timer-end');

                    if (timer.status === 'running') {
                        startBtn.classList.add('disabled');
                        pauseBtn.classList.remove('disabled');
                        endBtn.classList.remove('disabled');
                    } else if (timer.status === 'paused') {
                        startBtn.classList.remove('disabled');
                        pauseBtn.classList.add('disabled');
                        endBtn.classList.remove('disabled');
                    } else if (timer.status === 'ended') {
                        startBtn.classList.add('disabled');
                        pauseBtn.classList.add('disabled');
                        endBtn.classList.add('disabled');
                    }
                }

                // Initial timer display
                updateTimerDisplay();

                function startTimer() {
                    if (timer.status !== 'running') {
                        // Clear any existing interval first
                        if (timer.interval) {
                            clearInterval(timer.interval);
                            timer.interval = null;
                        }
                        
                        timer.status = 'running';
                        updateTimerDisplay();
                        sendScoreUpdate();

                        timer.interval = setInterval(() => {
                            if (timer.remaining > 0) {
                                timer.remaining--;
                                updateTimerDisplay();
                                sendScoreUpdate();
                            } else {
                                clearInterval(timer.interval);
                                timer.interval = null;
                                timer.status = 'ended';
                                updateTimerDisplay();
                                sendScoreUpdate();
                                handlePeriodEnd();
                            }
                        }, 1000);
                    }
                }

                function pauseTimer() {
                    if (timer.status === 'running') {
                        clearInterval(timer.interval);
                        timer.interval = null;
                        timer.status = 'paused';
                        updateTimerDisplay();
                        sendScoreUpdate();
                    }
                }

                function endTimer() {
                    clearInterval(timer.interval);
                    timer.interval = null;
                    timer.status = 'ended';
                    timer.remaining = 0;
                    updateTimerDisplay();
                    sendScoreUpdate();
                    handlePeriodEnd();
                }

                // Handle period changes
                function handlePeriodEnd() {
                    const currentPeriod = parseInt(document.getElementById('periods').value);
                    const teamAScore = parseInt(document.getElementById('teamA_score').value) || 0;
                    const teamBScore = parseInt(document.getElementById('teamB_score').value) || 0;
                    
                    if (currentPeriod === maxPeriods && teamAScore === teamBScore) {
                        // Game is tied at the end of regulation
                        Swal.fire({
                            title: 'Game Tied!',
                            text: 'Proceed to overtime period?',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonText: 'Yes',
                            cancelButtonText: 'No'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                // Start overtime period
                                document.getElementById('periods').value = 'OT';
                                // Set overtime duration (5 minutes)
                                timer.remaining = 5 * 60;
                                timer.status = 'paused';
                                updateTimerDisplay();
                                sendScoreUpdate();
                            }
                        });
                    } else if (currentPeriod < maxPeriods) {
                        // Normal period end, automatically start next period
                        document.getElementById('periods').value = currentPeriod + 1;
                        timer.remaining = periodDuration;
                        timer.status = 'paused';
                        updateTimerDisplay();
                        sendScoreUpdate();
                        
                        // Notify user
                        Swal.fire({
                            title: 'Period Ended',
                            text: `Starting Period ${currentPeriod + 1}`,
                            icon: 'info',
                            timer: 2000,
                            showConfirmButton: false
                        });
                    } else {
                        // Game is over with a winner
                        Swal.fire({
                            title: 'Game Over!',
                            text: `Final Score: Team A ${teamAScore} - Team B ${teamBScore}`,
                            icon: 'info'
                        });
                    }
                }

                // Event listeners for timer controls
                document.getElementById('timer-start').onclick = function(e) {
                    e.preventDefault();
                    startTimer();
                };
                
                document.getElementById('timer-pause').onclick = function(e) {
                    e.preventDefault();
                    pauseTimer();
                };
                
                document.getElementById('timer-end').onclick = function(e) {
                    e.preventDefault();
                    Swal.fire({
                        title: 'End Period?',
                        text: 'This will end the current period and start the next one.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Yes',
                        cancelButtonText: 'No'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            endTimer();
                        }
                    });
                };

                // Period increment/decrement handlers
                document.getElementById('increment-period').onclick = function() {
                    const periodInput = document.getElementById('periods');
                    const currentPeriod = parseInt(periodInput.value) || 1;
                    if (currentPeriod < maxPeriods) {
                        periodInput.value = currentPeriod + 1;
                        timer.remaining = periodDuration;
                        timer.status = 'paused';
                        updateTimerDisplay();
                        sendScoreUpdate();
                    }
                };

                document.getElementById('decrement-period').onclick = function() {
                    const periodInput = document.getElementById('periods');
                    const currentPeriod = parseInt(periodInput.value) || 1;
                    if (currentPeriod > 1) {
                        periodInput.value = currentPeriod - 1;
                        timer.remaining = periodDuration;
                        timer.status = 'paused';
                        updateTimerDisplay();
                        sendScoreUpdate();
                    }
                };

                // Add keyboard shortcuts for timer control
                document.addEventListener('keydown', function(e) {
                    if (e.target.tagName === 'INPUT') return; // Don't trigger if typing in input field
                    
                    switch(e.key) {
                        case ' ': // Space bar
                            e.preventDefault();
                            if (timer.status === 'running') {
                                pauseTimer();
                            } else if (timer.status === 'paused') {
                                startTimer();
                            }
                            break;
                        case 'e': // End timer
                            if (timer.status !== 'ended') {
                                e.preventDefault();
                                document.getElementById('timer-end').click();
                            }
                            break;
                    }
                });

                // Add escape key handler
                document.onkeydown = function(e) {
                    if (e.key === 'Escape') {
                        window.closePlayerStats();
                    }
                };

                // Add score validation
                function validateScore(score) {
                    if (pointCap && score > pointCap) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Point Cap Reached',
                            text: `Maximum points allowed is ${pointCap}`
                        });
                        return false;
                    }
                    return true;
                }

                // Update score event listeners
                document.querySelectorAll('.increment-btn, .decrement-btn').forEach(button => {
                    button.onclick = function() {
                        const target = document.getElementById(button.getAttribute('data-target'));
                        const increment = parseInt(button.getAttribute('data-increment')) || 1;
                        const currentValue = parseInt(target.value) || 0;
                        const newValue = button.classList.contains('decrement-btn') ? 
                                        currentValue - increment : 
                                        currentValue + increment;
                        
                        if (validateScore(newValue)) {
                            target.value = newValue;
                            // Trigger score update to server
                            updateScore(target.id, newValue);
                        }
                    };
                });

                // End match handler
                document.getElementById("end-match-button").onclick = function() {
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "You won't be able to undo this action!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, end match'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            const scheduleId = document.getElementById("schedule_id").value;
                            const matchId = document.getElementById("match_id").value;

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
                                    Swal.fire({
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
                                        // Handle overtime situation
                                        Swal.fire({
                                            title: 'Overtime Required',
                                            text: response.error,
                                            icon: 'info',
                                            showCancelButton: true,
                                            confirmButtonText: 'Start Overtime',
                                            cancelButtonText: 'Cancel'
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                // Set up overtime period
                                                document.getElementById('periods').value = 'OT';
                                                // Reset timer to 5 minutes for overtime
                                                timer.remaining = 5 * 60; // 5 minutes in seconds
                                                timer.status = 'paused';
                                                updateTimerDisplay();
                                                sendScoreUpdate();
                                            }
                                        });
                                    } else {
                                        throw new Error(response.error || 'An error occurred while ending the match.');
                                    }
                                }
                            })
                            .catch(error => {
                                console.error('Error ending match:', error);
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: error.message || 'An error occurred while ending the match.'
                                });
                            });
                        }
                    });
                };

                // Handle panel sliding
                const handle = playerStatsPanel.querySelector('.handle');
                let isDragging = false;
                let startY = 0;
                let startTransform = 0;

                handle.onmousedown = startDragging;
                document.onmousemove = drag;
                document.onmouseup = stopDragging;

                handle.ontouchstart = e => {
                    startDragging(e.touches[0]);
                };
                document.ontouchmove = e => {
                    drag(e.touches[0]);
                };
                document.ontouchend = stopDragging;

                function startDragging(e) {
                    isDragging = true;
                    startY = e.clientY;
                    startTransform = getCurrentTransform();
                    playerStatsPanel.style.transition = 'none';
                }

                function drag(e) {
                    if (!isDragging) return;
                    
                    const delta = startY - e.clientY;
                    const newTransform = Math.min(0, Math.max(-window.innerHeight + 60, startTransform + delta));
                    playerStatsPanel.style.transform = `translateY(${newTransform}px)`;
                }

                function stopDragging() {
                    if (!isDragging) return;
                    
                    isDragging = false;
                    playerStatsPanel.style.transition = 'transform 0.3s ease';
                    const currentTransform = getCurrentTransform();
                    
                    if (currentTransform > -window.innerHeight / 2) {
                        playerStatsPanel.style.transform = 'translateY(0)';
                    } else {
                        playerStatsPanel.style.transform = `translateY(${-window.innerHeight + 60}px)`;
                    }
                }

                function getCurrentTransform() {
                    const transform = window.getComputedStyle(playerStatsPanel).transform;
                    const matrix = new DOMMatrix(transform);
                    return matrix.m42;
                }
            });
        </script>