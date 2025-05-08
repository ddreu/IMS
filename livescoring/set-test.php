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

// Fetch current sets won for each team
$sets_won_query = $conn->prepare("
    SELECT 
        (SELECT COUNT(*) FROM match_periods_info 
         WHERE match_id = m.match_id AND teamA_id = ?) AS teamA_sets_won,
        (SELECT COUNT(*) FROM match_periods_info 
         WHERE match_id = m.match_id AND teamB_id = ?) AS teamB_sets_won
    FROM matches m
    WHERE m.match_id = ?
");
$sets_won_query->bind_param(
    "iii",
    $match['teamA_id'],
    $match['teamB_id'],
    $match['match_id']
);
$sets_won_query->execute();
$sets_won_result = $sets_won_query->get_result()->fetch_assoc();
$teamA_sets = $sets_won_result['teamA_sets_won'] ?? 0;
$teamB_sets = $sets_won_result['teamB_sets_won'] ?? 0;
$sets_won_query->close();

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set-Based Scoreboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    <script type="text/javascript" src="https://www.gstatic.com/cv/js/sender/v1/cast_sender.js?loadCastFramework=1"></script>

    <link rel="stylesheet" href="set-based.css">
</head>

<body>
    <!-- Schedule ID -->
    <input type="hidden" id="schedule_id" value="<?php echo htmlspecialchars($schedule_id); ?>">
    <input type="hidden" id="match-id" value="<?php echo $match['match_id']; ?>">
    <input type="hidden" id="teamA_id" value="<?php echo htmlspecialchars($teamA_id); ?>">
    <input type="hidden" id="teamB_id" value="<?php echo htmlspecialchars($teamB_id); ?>">
    <input type="hidden" id="game_id" value="<?php echo htmlspecialchars($game_id); ?>">
    <!-- End Match Button -->
    <button class="end-match" onclick="ScoreManager.endMatch()">
        <i class="fas fa-stop-circle"></i> End Match
    </button>

    <button class="score-button settings-button" onclick="openSettings()">
        <i class="fas fa-cog"></i>
    </button>

    <div class="scoreboard">
        <!-- 🔹 Row 1: Timeouts & Set -->
        <div class="row row-top">
            <!-- Team A Timeouts -->
            <div class="timeout-box">
                <!-- <div class="digit-label"><?php echo htmlspecialchars($match['teamA_name']); ?> TO</div> -->
                <div class="digit-label">Timeout:</div>
                <div class="timeout-buttons">
                    <button id="teamA-timeouts-minus" onclick="updateTimeouts('A', -1)">-</button>
                    <div class="digit" id="teamA-timeouts">0</div>
                    <button id="teamA-timeouts-plus" onclick="updateTimeouts('A', 1)">+</button>
                </div>
            </div>

            <!-- Sets Won -->
            <div class="sets-won-container">
                <div class="sets-won-label">SETS WON</div>
                <div class="sets-won-display">
                    <div id="teamA-sets"><?php echo $teamA_sets; ?></div>
                    <span>-</span>
                    <div id="teamB-sets"><?php echo $teamB_sets; ?></div>
                </div>

                <!-- 🔁 Reset Button -->
                <div class="reset-wrapper">
                    <button class="reset-btn" onclick="resetGame()">Reset</button>
                </div>
            </div>




            <!-- Team B Timeouts -->
            <div class="timeout-box">
                <!-- <div class="digit-label"><?php echo htmlspecialchars($match['teamB_name']); ?> TO</div> -->
                <div class="digit-label">Timeout:</div>

                <div class="timeout-buttons">
                    <button id="teamB-timeouts-minus" onclick="updateTimeouts('B', -1)">-</button>
                    <div class="digit" id="teamB-timeouts">0</div>
                    <button id="teamB-timeouts-plus" onclick="updateTimeouts('B', 1)">+</button>
                </div>
            </div>
        </div>

        <!-- 🔸 Row 2: Scores -->
        <div class="row row-bottom">
            <!-- Team A Score -->
            <div class="team" id="teamA">
                <div class=" digit-label">
                    <h2><?php echo htmlspecialchars($match['teamA_name']); ?></h2>
                </div>
                <div class="score-wrapper">
                    <button onclick="updateScore('A', -1)">-</button>
                    <div class="digit score" id="scoreA"><?php echo $match['teamA_score'] ?? 0; ?></div>
                    <button onclick="updateScore('A', 1)">+</button>
                </div>
            </div>

            <!-- Current Set -->
            <div class="set-center">
                <div class="digit-label"> CURRENT SET</div>
                <div class="digit c-set" id="currentSet">1</div>
                <div class="set-buttons">
                    <!-- <button onclick="updateSet(-1)">-</button>
                    <button onclick="updateSet(1)">+</button> -->
                    <button id="endSetBtn" onclick="ScoreManager.confirmEndSet()">End Set</button>
                </div>
            </div>
            <!-- Team B Score -->
            <div class="team" id="teamB">
                <div class="digit-label">
                    <h2><?php echo htmlspecialchars($match['teamB_name']); ?></h2>
                </div>
                <div class="score-wrapper">
                    <button onclick="updateScore('B', -1)">-</button>
                    <div class="digit score" id="scoreB"><?php echo $match['teamB_score'] ?? 0; ?></div>
                    <button onclick="updateScore('B', 1)">+</button>
                </div>
            </div>
        </div>
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
                <button class="score-button fullscreen-button" onclick="toggleFullscreen()">
                    <i class="fas fa-expand"></i>
                </button>

                <!-- <button class="score-button cast-button" onclick="requestCast()">
                    <i class="fas fa-tv"></i> Cast
                </button> -->

                <!-- Dynamic Link to Player Stats -->
                <a
                    href="player_statistics_panel.php?schedule_id=<?php echo $schedule_id; ?>&teamA_id=<?php echo $teamA_id; ?>&teamB_id=<?php echo $teamB_id; ?>&game_id=<?php echo $game_id; ?>"
                    class="score-button player-stats-button">
                    <i class="fas fa-users me-2"></i> Go to Player Stats
                </a>


                <div style="display: flex; gap: 10px; justify-content: center; margin-top: 10px;">
                    <!-- <button class="score-button" onclick="saveSettings()">Save</button> -->
                    <button class="score-button" onclick="closeSettings()">Cancel</button>
                </div>
            </div>
        </div>


    </div>



    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script src="set-test.js"></script>
    <script>
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
        // Function to update the score of Team A or B
        function updateScore(team, change) {
            let scoreElement = document.getElementById(`score${team}`);
            let currentScore = parseInt(scoreElement.innerText);

            // Prevent negative scores
            if (currentScore + change >= 0) {
                scoreElement.innerText = currentScore + change;
                saveState();
            }
        }

        // Function to update the number of sets won
        function updateSetWins(team, change) {
            let setWinsElement = document.getElementById(`team${team}-sets`);
            let currentSets = parseInt(setWinsElement.innerText);

            // Prevent negative set wins
            if (currentSets + change >= 0) {
                setWinsElement.innerText = currentSets + change;
                saveState();

                // Send server update
                if (window.ScoreManager && typeof ScoreManager.sendScoreUpdate === 'function') {
                    ScoreManager.sendScoreUpdate();
                } else {
                    console.warn('ScoreManager not available for set wins update');
                }
            }
        }

        // Function to update the ongoing set number
        function updateSet(change) {
            let setElement = document.getElementById("currentSet");
            let currentSet = parseInt(setElement.innerText);

            // Ensure the set doesn't go below 1
            if (currentSet + change >= 1) {
                setElement.innerText = currentSet + change;
                saveState();
            }
        }

        // Function to end the match
        function endMatch() {
            alert("Match has ended!");
            resetGame();
        }

        function resetGame() {
            document.getElementById("scoreA").innerText = "0";
            document.getElementById("scoreB").innerText = "0";
            document.getElementById("teamA-timeouts").innerText = "0";
            document.getElementById("teamB-timeouts").innerText = "0";
            saveState();
        }

        function saveState() {
            localStorage.setItem('set_test_teamA_timeouts', document.getElementById('teamA-timeouts').innerText);
            localStorage.setItem('set_test_teamB_timeouts', document.getElementById('teamB-timeouts').innerText);
            localStorage.setItem('set_test_teamA_score', document.getElementById('scoreA').innerText);
            localStorage.setItem('set_test_teamB_score', document.getElementById('scoreB').innerText);
            localStorage.setItem('set_test_currentSet', document.getElementById('currentSet').innerText);

            // Add set wins to local storage
            localStorage.setItem('set_test_teamA_sets', document.getElementById('teamA-sets').innerText);
            localStorage.setItem('set_test_teamB_sets', document.getElementById('teamB-sets').innerText);
        }

        function loadState() {
            document.getElementById("scoreA").innerText = localStorage.getItem('set_test_teamA_score') || "0";
            document.getElementById("scoreB").innerText = localStorage.getItem('set_test_teamB_score') || "0";
            document.getElementById("currentSet").innerText = localStorage.getItem('set_test_currentSet') || "1";
            document.getElementById("teamA-timeouts").innerText = localStorage.getItem('set_test_teamA_timeouts') || "0";
            document.getElementById("teamB-timeouts").innerText = localStorage.getItem('set_test_teamB_timeouts') || "0";

            // Load set wins from local storage
            document.getElementById("teamA-sets").innerText = localStorage.getItem('set_test_teamA_sets') || "0";
            document.getElementById("teamB-sets").innerText = localStorage.getItem('set_test_teamB_sets') || "0";
        }

        // Global functions to bridge ScoreManager methods
        function updateTimeouts(team, change) {
            let timeoutsElement = document.getElementById(`team${team}-timeouts`);
            let currentTimeouts = parseInt(timeoutsElement.innerText);

            // Prevent negative timeouts
            if (currentTimeouts + change >= 0) {
                timeoutsElement.innerText = currentTimeouts + change;
                saveState();

                // Trigger AJAX update if ScoreManager is available
                if (window.ScoreManager && typeof ScoreManager.sendScoreUpdate === 'function') {
                    ScoreManager.sendScoreUpdate();
                }
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            loadState();
            const setsDisplays = document.querySelectorAll('.sets-display');
            setsDisplays.forEach(display => {
                display.removeAttribute('contenteditable');
                display.style.cursor = 'default';
            });
        });

        window.castReady = false;

        // Google Cast SDK will call this when available
        window.__onGCastApiAvailable = function(isAvailable) {
            if (isAvailable) {
                const context = cast.framework.CastContext.getInstance();

                context.setOptions({
                    receiverApplicationId: chrome.cast.media.DEFAULT_MEDIA_RECEIVER_APP_ID,
                    autoJoinPolicy: chrome.cast.AutoJoinPolicy.ORIGIN_SCOPED
                });

                console.log("✅ Cast API initialized");
                window.castReady = true;

                // Enable the Cast button if you want (optional)
                const castBtn = document.querySelector(".cast-button");
                if (castBtn) castBtn.disabled = false;
            }
        };

        function requestCast() {
            if (!window.castReady) {
                console.warn(" Cast API not ready yet.");
                return;
            }

            const context = cast.framework.CastContext.getInstance();
            const session = context.getCurrentSession();

            if (!session) {
                context.requestSession().then(() => {
                    console.log("✅ Cast session started.");
                }).catch(err => {
                    console.error(" Cast session failed:", err);
                });
            } else {
                console.log("ℹ️ Already casting.");
            }
        }

        // Optional: disable button until Cast API is ready
        document.addEventListener("DOMContentLoaded", () => {
            const castBtn = document.querySelector(".cast-button");
            if (castBtn) castBtn.disabled = true;
        });
    </script>
    </script>
    <!-- SweetAlert2 library -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>

</html>