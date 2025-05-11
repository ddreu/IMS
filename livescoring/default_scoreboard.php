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

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Scoreboard - <?php echo htmlspecialchars($match['game_name'] ?? 'Game'); ?></title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="text/javascript" src="https://www.gstatic.com/cv/js/sender/v1/cast_sender.js?loadCastFramework=1"></script>

    <!-- Stylesheet -->
    <link rel="stylesheet" href="default_scoreboard.css" />
</head>

<body>


    <!-- End Match Button -->
    <button type="button" class="end-match right" id="end-match-button">
        <i class="fas fa-stop-circle"></i> End Match
    </button>
    <button class="score-button settings-button" onclick="openSettings()">
        <i class="fas fa-cog"></i>
    </button>

    <!-- Schedule ID -->
    <input type="hidden" id="schedule_id" value="<?php echo htmlspecialchars($schedule_id); ?>">
    <input type="hidden" id="match-id" value="<?php echo $match['match_id']; ?>">

    <div id="settingsModal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.9); padding: 20px; border-radius: 10px; z-index: 1000;">
        <h3 style="color: white; margin-bottom: 15px;">Game Settings</h3>
        <div style="display: flex; flex-direction: column; gap: 10px;">
            <div hidden>
                <label style="color: white;">Period Length (minutes)</label>
                <input type="number" id="periodLength" value="10" min="1" max="60" style="width: 60px; margin-left: 10px;">
            </div>
            <div hidden>
                <label style="color: white;">Number of Periods</label>
                <input type="number" id="numberOfPeriods" value="4" min="1" max="10" style="width: 60px; margin-left: 10px;">
            </div>
            <div hidden>
                <label style="color: white;">Shot Clock Duration</label>
                <input type="number" id="shotClockDuration" value="24" min="1" max="60" style="width: 60px; margin-left: 10px;">
            </div>
            <button class="score-button fullscreen-button player-stats-button" onclick="toggleFullscreen()">
                <i class="fas fa-expand"></i>
            </button>

            <!-- <button class="score-button cast-button player-stats-button" onclick="requestCast()">
                <i class="fas fa-tv"></i> Cast
            </button> -->

            <!-- Dynamic Link to Player Stats -->
            <a
                href="player_statistics_panel.php?schedule_id=<?php echo $schedule_id; ?>&teamA_id=<?php echo $teamA_id; ?>&teamB_id=<?php echo $teamB_id; ?>&game_id=<?php echo $game_id; ?>"
                class="score-button player-stats-button">
                <i class="fas fa-users me-2"></i> Go to Player Stats
            </a>

            <div>
                <label style="color: white;">
                    <input type="checkbox" id="syncPlayerStatsToScore" style="margin-right: 8px;">
                    Sync Player Stats to Score
                </label>
            </div>



            <div style="display: flex; gap: 10px; justify-content: center; margin-top: 10px;">
                <!-- <button class="score-button" onclick="saveSettings()">Save</button> -->
                <button class="score-button player-stats-button" onclick="closeSettings()">Cancel</button>
            </div>
        </div>
    </div>

    <div class="scoreboard">
        <!-- Team A -->
        <div class="team">
            <h2 id="teamAName"><?php echo htmlspecialchars($match['teamA_name'] ?? 'Team A'); ?></h2>
            <p id="teamAScore"><?php echo htmlspecialchars($match['teamA_score'] ?? '0'); ?></p>
            <div class="btn-container">
                <button onclick="updateScore('teamA', -1)">-</button>
                <button onclick="updateScore('teamA', 1)">+</button>
            </div>
        </div>

        <button id="reset-btn" onclick="resetScores()">Reset</button>

        <!-- Team B -->
        <div class="team">
            <h2 id="teamBName"><?php echo htmlspecialchars($match['teamB_name'] ?? 'Team B'); ?></h2>
            <p id="teamBScore"><?php echo htmlspecialchars($match['teamB_score'] ?? '0'); ?></p>
            <div class="btn-container">
                <button onclick="updateScore('teamB', -1)">-</button>
                <button onclick="updateScore('teamB', 1)">+</button>
            </div>
        </div>
    </div>
    <!-- Script-->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="end_match.js"></script>
    <script src="stats-sync.js"></script>
    <script>
        let schedule_id = <?php echo json_encode($schedule_id); ?>;
        let game_id = <?php echo json_encode($game_id); ?>;
        let teamA_id = <?php echo json_encode($teamA_id); ?>;
        let teamB_id = <?php echo json_encode($teamB_id); ?>;
        let match_id = <?php echo json_encode($match['match_id']); ?>;

        // Initialize scores with database values
        let teamAScore = <?php echo json_encode($match['teamA_score'] ?? 0); ?>;
        let teamBScore = <?php echo json_encode($match['teamB_score'] ?? 0); ?>;

        // Get references to the HTML elements that display the scores
        let teamAScoreValue = document.getElementById("teamAScore");
        let teamBScoreValue = document.getElementById("teamBScore");


        window.gameData = {
            schedule_id: '<?php echo htmlspecialchars($schedule_id); ?>',
            game_id: '<?php echo htmlspecialchars($game_id); ?>',
            teamA_id: '<?php echo htmlspecialchars($teamA_id); ?>',
            teamB_id: '<?php echo htmlspecialchars($teamB_id); ?>',
            match_id: '<?php echo htmlspecialchars($match['match_id']); ?>'
        };
        // Set the initial values in the UI
        teamAScoreValue.innerText = teamAScore;
        teamBScoreValue.innerText = teamBScore;

        function applySyncToggle(enabled) {
            localStorage.setItem("syncPlayerStatsToScore", enabled);
            disableManualScoreButtons(enabled); // 🆕 this line handles button toggling
            console.log("*_Sync player stats to score_* is", enabled ? "*_ENABLED_*" : "*_DISABLED_*");

            // Optional: auto trigger score sync when enabling
            if (enabled && typeof syncTeamScoresIfEnabled === "function") {
                syncTeamScoresIfEnabled();
            }
        }

        function disableManualScoreButtons(disable) {
            const buttons = document.querySelectorAll(
                ".btn-container button, #reset-btn"
            );
            buttons.forEach(btn => {
                btn.disabled = disable;
                btn.title = disable ? "Disabled while stats sync is active" : "";
            });
        }


        function syncTeamScoresIfEnabled() {
            console.log("Syncing team scores...");

            if (localStorage.getItem("syncPlayerStatsToScore") !== "true") return;

            disableManualScoreButtons(true);

            if (!window.syncedTeamScores) {
                console.warn("⚠️ syncedTeamScores not available. Skipping sync.");
                return;
            }

            const {
                teamA,
                teamB
            } = window.syncedTeamScores;

            const newTeamAScore = parseInt(teamA) || 0;
            const newTeamBScore = parseInt(teamB) || 0;

            // Calculate deltas
            const deltaA = newTeamAScore - teamAScore;
            const deltaB = newTeamBScore - teamBScore;

            if (deltaA !== 0) updateScore("teamA", deltaA);
            if (deltaB !== 0) updateScore("teamB", deltaB);

            console.log("*✅ Synced scores using updateScore()*", {
                teamA: newTeamAScore,
                teamB: newTeamBScore
            });
        }


        // State preservation functions
        const stateManager = {
            save: function() {
                const state = {
                    teamAScore: teamAScore,
                    teamBScore: teamBScore
                };
                localStorage.setItem('defaultScoreboardState', JSON.stringify(state));
            },
            restore: function() {
                const savedState = localStorage.getItem('defaultScoreboardState');
                if (savedState) {
                    const state = JSON.parse(savedState);

                    // Restore scores
                    teamAScore = state.teamAScore;
                    teamBScore = state.teamBScore;

                    // Update UI
                    teamAScoreValue.innerText = teamAScore;
                    teamBScoreValue.innerText = teamBScore;
                }
            }
        };

        // Function to update score in UI and database
        function updateScore(team, change) {
            let scoreElement = document.getElementById(team + "Score");
            let currentScore = parseInt(scoreElement.innerText) || 0;
            let newScore = currentScore + change;

            if (newScore < 0) return; // Prevent negative scores

            scoreElement.innerText = newScore;

            // Update the variables
            if (team === "teamA") {
                teamAScore = newScore;
            } else if (team === "teamB") {
                teamBScore = newScore;
            }

            // Save state and sync with database
            stateManager.save();
            syncScoreWithDatabase();
        }

        // Function to sync scores with the database
        function syncScoreWithDatabase() {
            fetch('update_default_scores.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        schedule_id: schedule_id,
                        game_id: <?php echo json_encode($game_id); ?>,
                        teamA_id: <?php echo json_encode($teamA_id); ?>,
                        teamB_id: <?php echo json_encode($teamB_id); ?>,
                        teamA_score: teamAScore,
                        teamB_score: teamBScore
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (!data.success) {
                        console.error("Error updating score:", data.message);
                    }
                })
                .catch(error => console.error("Network error:", error));
        }

        // Function to reset scores
        function resetScores() {
            teamAScore = 0;
            teamBScore = 0;
            teamAScoreValue.innerText = teamAScore;
            teamBScoreValue.innerText = teamBScore;

            // Save state and sync with database
            stateManager.save();
            syncScoreWithDatabase();
        }

        // Restore state on page load
        document.addEventListener('DOMContentLoaded', function() {
            stateManager.restore();

            const syncCheckbox = document.getElementById("syncPlayerStatsToScore");
            const syncEnabled = localStorage.getItem("syncPlayerStatsToScore") === "true";

            if (syncCheckbox) {
                syncCheckbox.checked = syncEnabled;
                applySyncToggle(syncEnabled); // 🧠 This ensures sync logic runs immediately

                syncCheckbox.addEventListener("change", function() {
                    applySyncToggle(this.checked);
                });
            }

            // 🆕 Poll for updated synced scores if sync is enabled
            if (syncEnabled) {
                const poll = setInterval(() => {
                    if (window.syncedTeamScores) {
                        clearInterval(poll);
                        syncTeamScoresIfEnabled();
                    }
                }, 300);
            }
        });


        function toggleFullscreen() {
            const docElm = document.documentElement;

            if (!document.fullscreenElement) {
                if (docElm.requestFullscreen) {
                    docElm.requestFullscreen();
                } else if (docElm.mozRequestFullScreen) {
                    /* Firefox */
                    docElm.mozRequestFullScreen();
                } else if (docElm.webkitRequestFullscreen) {
                    /* Chrome, Safari & Opera */
                    docElm.webkitRequestFullscreen();
                } else if (docElm.msRequestFullscreen) {
                    /* IE/Edge */
                    docElm.msRequestFullscreen();
                }
            } else {
                if (document.exitFullscreen) {
                    document.exitFullscreen();
                } else if (document.mozCancelFullScreen) {
                    document.mozCancelFullScreen();
                } else if (document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                } else if (document.msExitFullscreen) {
                    document.msExitFullscreen();
                }
            }
        }

        function openSettings() {
            document.getElementById('settingsModal').style.display = 'block';
        }

        function closeSettings() {
            document.getElementById('settingsModal').style.display = 'none';
        }
    </script>

</body>

</html>