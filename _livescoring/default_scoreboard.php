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

    <!-- Stylesheet -->
    <link rel="stylesheet" href="default_scoreboard.css" />
</head>

<body>


    <!-- End Match Button -->
    <button type="button" class="end-match right" id="end-match-button">
        <i class="fas fa-stop-circle"></i> End Match
    </button>

    <!-- Schedule ID -->
    <input type="hidden" id="schedule_id" value="<?php echo htmlspecialchars($schedule_id); ?>">
    <input type="hidden" id="match-id" value="<?php echo $match['match_id']; ?>">


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

        // Set the initial values in the UI
        teamAScoreValue.innerText = teamAScore;
        teamBScoreValue.innerText = teamBScore;

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
        });
    </script>

</body>

</html>