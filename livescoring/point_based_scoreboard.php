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
$match_query->close();

// Close the database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scoreboard</title>
    <link rel="stylesheet" href="scb_bsktbll.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <audio id="buzzerSound" src="buzzer/buzzer.mp3"></audio>
    
    <script>
        // Immediately set global game data
        window.gameData = {
            schedule_id: '<?php echo htmlspecialchars($schedule_id); ?>',
            game_id: '<?php echo htmlspecialchars($game_id); ?>',
            teamA_id: '<?php echo htmlspecialchars($teamA_id); ?>',
            teamB_id: '<?php echo htmlspecialchars($teamB_id); ?>',
            match_id: '<?php echo htmlspecialchars($match['match_id']); ?>'
        };
        
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

        // Debug logging
        console.log('Game Data Initialized:', window.gameData);
    </script>

    <!-- Ensure sb-basketball.js is loaded after game data is set -->
    <script src="sb-basketball.js"></script>
</head>

<body>
<!-- Schedule ID -->
<input type="hidden" id="schedule_id" value="<?php echo htmlspecialchars($schedule_id); ?>">
    <input type="hidden" id="match-id" value="<?php echo $match['match_id']; ?>">
    <div class="scoreboard">
        <!-- Header Controls -->
        <div class="header-container">
            <!-- Left Side: End Match Button -->
            <div class="header-controls">
                <button class="score-button end-match" id="end-match-button">
                    <i class="fas fa-stop-circle"></i> End Match
                </button>
            </div>

            <!-- Right Side: Live Stream & Settings -->
            <div class="header-buttons">
                <button class="score-button live-match" onclick="openLiveStreamSettings()">
                    <i class="fas fa-video"></i>
                </button>
                <button class="score-button settings-button" onclick="openSettings()">
                    <i class="fas fa-cog"></i>
                </button>
            </div>
        </div> <!-- Closing .header-container -->

        <!-- Teams and Scores -->
        <div class="team-a-container">
            <div class="team team-a">
                <div class="team-score">
                    <div class="team-name small" onclick="editTeamName('teamA')" style="cursor: pointer;"><?= htmlspecialchars($match['teamA_name']) ?></div>
                    <div class="score-wrapper">
                        <button class="score-button" onclick="updateScore('teamA', -1)">-</button>
                        <div class="team-score score" id="teamAScore">00</div>
                        <button class="score-button" onclick="updateScore('teamA', 1)">+</button>
                    </div>
                </div>
                <div class="foul-container">
                    <div class="foul-label">FOUL</div>
                    <div class="fouls-control">
                        <button class="score-button" onclick="updateFouls('teamA', -1)">-</button>
                        <div class="fouls" id="teamAFouls">0</div>
                        <button class="score-button" onclick="updateFouls('teamA', 1)">+</button>
                    </div>
                    <div class="timeout-label">TIMEOUT</div>
                    <div class="timeouts-control">
                        <button class="score-button" onclick="updateTimeouts('teamA', -1)">-</button>
                        <div class="timeouts" id="teamATimeouts">4</div>
                        <button class="score-button" onclick="updateTimeouts('teamA', 1)">+</button>
                    </div>
                </div>
            </div>
        </div> <!-- Closing .team-a-container -->

        <div class="control-container">
            <!-- Timer Controls -->
            <div class="timer-wrapper">
                <div class="timer-controls" style="margin-bottom: 10px;">
                    <button class="score-button" onclick="startTimer()"><i class="fas fa-play"></i></button>
                    <button class="score-button" onclick="pauseTimer()"><i class="fas fa-pause"></i></button>
                    <button class="score-button" onclick="resetTimer()"><i class="fas fa-redo"></i></button>
                    <button class="score-button" onclick="adjustTimer(-1)">-1s</button>
                    <button class="score-button" onclick="adjustTimer(1)">+1s</button>
                    <button class="score-button" onclick="adjustTimer(-60)">-1m</button>
                    <button class="score-button" onclick="adjustTimer(60)">+1m</button>
                    <button class="score-button" style="background-color: #dc3545;" onclick="playBuzzer()">
                        <i class="fas fa-volume-up"></i> Buzzer
                    </button>
                </div>
                <div class="timer" id="gameTimer">10:00</div>
            </div>

            <div class="center-container">
                <div class="label period-label-1">PERIOD</div>
                <div class="period-control">
                    <button class="score-button" onclick="updatePeriod(-1)">-</button>
                    <div class="period" id="periodCounter">1</div>
                    <button class="score-button" onclick="updatePeriod(1)">+</button>
                </div>
            </div>

            <div class="middle-section">
                <div class="label">SHOT CLOCK</div>
                <div class="shot-clock-control">
                    <button class="score-button" onclick="startShotClock()"><i class="fas fa-play"></i></button>
                    <button class="score-button" onclick="pauseShotClock()"><i class="fas fa-pause"></i></button>
                    <button class="score-button" onclick="resetShotClock()"><i class="fas fa-redo"></i></button>
                </div>
                <div class="shot-clock-control">
                    <!-- <button class="score-button" onclick="updateShotClock(-1)">-</button> -->
                    <div class="shot-clock" id="shotClock">24</div>
                    <!--<button class="score-button" onclick="updateShotClock(1)">+</button>-->
                </div>
            </div>
        </div> <!-- Closing .control-container -->

        <!-- Team B -->
        <div class="team-b-container">
            <div class="team team-b">
                <div class="team-score">
                    <div class="team-name small" onclick="editTeamName('teamB')" style="cursor: pointer;"><?= htmlspecialchars($match['teamB_name']) ?></div>
                    <div class="score-wrapper">
                        <button class="score-button" onclick="updateScore('teamB', -1)">-</button>
                        <div class="team-score score" id="teamBScore">00</div>
                        <button class="score-button" onclick="updateScore('teamB', 1)">+</button>
                    </div>
                </div>
                <div class="foul-container">
                    <div class="foul-label">FOUL</div>
                    <div class="fouls-control">
                        <button class="score-button" onclick="updateFouls('teamB', -1)">-</button>
                        <div class="fouls" id="teamBFouls">0</div>
                        <button class="score-button" onclick="updateFouls('teamB', 1)">+</button>
                    </div>
                    <div class="timeout-label">TIMEOUT</div>
                    <div class="timeouts-control">
                        <button class="score-button" onclick="updateTimeouts('teamB', -1)">-</button>
                        <div class="timeouts" id="teamBTimeouts">4</div>
                        <button class="score-button" onclick="updateTimeouts('teamB', 1)">+</button>
                    </div>
                </div>
            </div>
        </div> <!-- Closing .team-b-container -->
    </div> <!-- Closing .scoreboard -->

    <!-- Settings Modal -->
    <div id="settingsModal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.9); padding: 20px; border-radius: 10px; z-index: 1000;">
        <h3 style="color: white; margin-bottom: 15px;">Game Settings</h3>
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <div>
                <label style="color: white;">Period Length (minutes)</label>
                <input type="number" id="periodLength" value="10" min="1" max="60" style="width: 60px; margin-left: 10px;">
            </div>
            <div>
                <label style="color: white;">Number of Periods</label>
                <input type="number" id="numberOfPeriods" value="4" min="1" max="10" style="width: 60px; margin-left: 10px;">
            </div>
            <div>
                <label style="color: white;">Shot Clock Duration</label>
                <input type="number" id="shotClockDuration" value="24" min="1" max="60" style="width: 60px; margin-left: 10px;">
            </div>
            <div style="display: flex; gap: 10px; justify-content: center; margin-top: 10px;">
                <button class="score-button" onclick="saveSettings()">Save</button>
                <button class="score-button" onclick="closeSettings()">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Live Stream Settings Modal -->
    <div id="liveStreamModal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.9); padding: 20px; border-radius: 10px; z-index: 1000;">
        <h3 style="color: white; margin-bottom: 15px;">Live Stream Settings</h3>
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <div>
                <label style="color: white;">Stream Key</label>
                <input type="text" id="streamKey" style="width: 200px; margin-left: 10px;">
            </div>
            <div>
                <label style="color: white;">Stream URL</label>
                <input type="text" id="streamUrl" style="width: 200px; margin-left: 10px;">
            </div>
            <div style="display: flex; gap: 10px; justify-content: center; margin-top: 10px;">
                <button class="score-button" onclick="startStream()">Start Stream</button>
                <button class="score-button" onclick="closeLiveStreamSettings()">Cancel</button>
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

    <!-- End Match Confirmation Modal 
    <div id="endMatchModal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.9); padding: 20px; border-radius: 10px; z-index: 1000;">
        <h3 style="color: white; margin-bottom: 15px;">End Match?</h3>
        <p style="color: white;">Are you sure you want to end the match?</p>
        <div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
            <button class="score-button" onclick="confirmEndMatch()">Yes, End Match</button>
            <button class="score-button" onclick="closeEndMatchModal()">Cancel</button>
        </div>
    </div> -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        // Function to clear all saved state for this specific match
        function clearMatchState() {
            // Remove all localStorage items related to this match
            const matchStateKeys = [
                'point_based_teamA_score',
                'point_based_teamB_score',
                'point_based_teamA_timeouts',
                'point_based_teamB_timeouts',
                'point_based_currentSet'
            ];

            matchStateKeys.forEach(key => {
                localStorage.removeItem(key);
            });

            // Reset all display elements to default
            document.getElementById("scoreA").innerText = "0";
            document.getElementById("scoreB").innerText = "0";
            document.getElementById("teamA-timeouts").innerText = "0";
            document.getElementById("teamB-timeouts").innerText = "0";
            document.getElementById("currentSet").innerText = "1";
        }

        // Modify the page load event to check for new match
        document.addEventListener('DOMContentLoaded', function() {
            // Check if this is a new match (you might need to pass this information from PHP)
            const isNewMatch = <?= isset($_GET['new_match']) ? 'true' : 'false' ?>;
            
            if (isNewMatch) {
                clearMatchState();
            }

            // Existing loadState logic
            loadState();
        });
    </script>
</body>

</html>