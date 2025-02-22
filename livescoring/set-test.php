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

    <div class="set-container">
    
        <div class="team-sets-container">
            <span class="team-label"><?php echo htmlspecialchars($match['teamA_name']); ?> Sets:</span>
            <span id="teamA-sets" 
                  class="sets-display" 
                  data-team="A" 
                  style="user-select: none; pointer-events: none;"><?php echo $teamA_sets; ?></span>
        </div>
</div>

    </div>
    <div class="scoreboard">
        <!-- Teams & Scores -->

        <!-- Ongoing Set Control -->
        <div class="set-control">
            <h3>Ongoing Set</h3>
            <div class="set-buttons">
                <button onclick="updateSet(-1)">-</button>
                <span id="currentSet">1</span>
                <button onclick="updateSet(1)">+</button>
            </div>
            <div class="end-set-container">
                <button id="endSetBtn" class="btn btn-danger" onclick="ScoreManager.confirmEndSet()">End Set</button>
            </div>
        </div>
        <div class="teams-container">

            <!-- Team A -->
            <div class="team" id="teamA">


                <h2><?php echo htmlspecialchars($match['teamA_name']); ?></h2>
                <div class="score-wrapper">
                    <button onclick="updateScore('A', -1)">-</button>
                    <span id="scoreA"><?php echo $match['teamA_score'] ?? 0; ?></span>
                    <button onclick="updateScore('A', 1)">+</button>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            Timeouts
                        </div>
                        <div class="card-body">
                            <div class="timeout-container">
                                <button id="teamA-timeouts-minus" class="btn btn-sm btn-outline-danger" onclick="updateTimeouts('A', -1)">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <span id="teamA-timeouts" class="h4 mb-0">0</span>
                                <button id="teamA-timeouts-plus" class="btn btn-sm btn-outline-success" onclick="updateTimeouts('A', 1)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>      
                </div>
            </div>
            <button class="reset-btn" onclick="resetGame()">Reset</button>

            <!-- Team B -->
            <div class="team" id="teamB">
                <h2><?php echo htmlspecialchars($match['teamB_name']); ?></h2>
                <div class="score-wrapper">
                    <button onclick="updateScore('B', -1)">-</button>
                    <span id="scoreB"><?php echo $match['teamB_score'] ?? 0; ?></span>
                    <button onclick="updateScore('B', 1)">+</button>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            Timeouts
                        </div>
                        <div class="card-body">
                            <div class="timeout-container">
                                <button id="teamB-timeouts-minus" class="btn btn-sm btn-outline-danger" onclick="updateTimeouts('B', -1)">
                                    <i class="fas fa-minus"></i>
                                </button>
                                <span id="teamB-timeouts" class="h4 mb-0">0</span>
                                <button id="teamB-timeouts-plus" class="btn btn-sm btn-outline-success" onclick="updateTimeouts('B', 1)">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>




    </div>
    <div class="set-container">
        <div class="team-sets-container">
            <span class="team-label"><?php echo htmlspecialchars($match['teamB_name']); ?> Sets:</span>
            <span id="teamB-sets" 
                  class="sets-display" 
                  data-team="B" 
                  style="user-select: none; pointer-events: none;"><?php echo $teamB_sets; ?></span>
        </div>
</div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script src="set-test.js"></script>
    <script>
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
    </script>
    <!-- SweetAlert2 library -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</body>

</html>