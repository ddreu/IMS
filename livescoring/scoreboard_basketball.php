<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scoreboard</title>
    <style>

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        @font-face {
    font-family: 'Digital7';
    src: url('fonts/digital-7.ttf') format('truetype');
}
        body {
            background-color: black;
            color: white;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
            width: 100vw;
            font-family: 'Digital7', sans-serif;
            overflow: hidden;
        }
        .scoreboard {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 8px;
            width: 60%;
            text-align: center;
        }
        .timer {
            font-size: 6.5vw;
            color: red;
            font-weight: bold;
            text-shadow: 4px 4px 8px rgba(255, 0, 0, 0.9);
            background: black;
            padding: 5px;
            border-radius: 10px;
            margin-top: 20px;
        }
        .teams {
            display: flex;
            justify-content: space-between;
            width: 100%;
            align-items: center;
        }
        .team {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
        }
        .team-name {
            font-size: 3vw;
            font-weight: bold;
            color: white;
            text-shadow: 2px 2px 4px rgba(255, 255, 255, 0.8);
        }
        .score-wrapper {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .team-score {
            font-size: 7vw;
            color: yellow;
            font-weight: bold;
            /*text-shadow: 4px 4px 8px rgba(255, 255, 0, 0.9);*/
            background: black;
            padding: 5px 15px;
            border-radius: 10px;
        }
        .foul-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
        }
        .foul-label, .timeout-label {
            font-size: 2.2vw;
            color: white;
            font-weight: bold;
        }
        .fouls, .timeouts {
            font-size: 4vw;
            color: orange;
            font-weight: bold;
            background: black;
            padding: 5px 10px;
            border-radius: 10px;
            margin-top: 8px;
        }
        .middle-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
            margin-top: -20px;
        }
        .label {
            font-size: 2.2vw;
            font-weight: bold;
            color: white;
        }
        .period {
            font-size: 4.5vw;
            font-weight: bold;
            color: white;
            background: black;
            padding: 5px 10px;
            border-radius: 10px;
        }
        .shot-clock {
            font-size: 4.5vw;
            font-weight: bold;
            color: limegreen;
            background: black;
            padding: 5px 10px;
            border-radius: 10px;
            margin-top: -10px;
            margin-bottom: 30px;
        }
        .center-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 3px;
            width: 100%;
        }
        .score-button {
            font-size: 2vw;
            background-color: gray;
            color: white;
            border: none;
            padding: 4px 8px;
            cursor: pointer;
            border-radius: 5px;
        }
        .fouls-control, .timeouts-control, .period-control {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .shot-clock-control {
            display: flex;
            gap: 5px;
            margin-bottom: 5px;
        }
        .timer-controls {
            display: flex;
            gap: 5px;
            justify-content: center;
        }
        #settingsModal {
            border: 1px solid #333;
        }
        #settingsModal input {
            background: #222;
            color: white;
            border: 1px solid #444;
            padding: 5px;
        }
    </style>
    <audio id="buzzerSound" src="sounds/buzzer.mp3"></audio>
</head>
<body>
    <div class="scoreboard">
        <!-- Header Controls -->
        <div class="header-controls" style="position: fixed; top: 10px; width: 100%; display: flex; justify-content: space-between; padding: 0 20px;">
            <!-- Live Stream Button -->
            <button class="score-button" style="background-color: #28a745;" onclick="openLiveStreamSettings()">
                <i class="fas fa-video"></i> Live Stream
            </button>
            
            <!-- End Match Button -->
            <button class="score-button" style="background-color: #dc3545;" onclick="endMatch()">
                <i class="fas fa-stop-circle"></i> End Match
            </button>
        </div>

        <!-- Timer Controls -->
        <div class="timer-controls" style="margin-bottom: 10px;">
            <button class="score-button" onclick="startTimer()">Start</button>
            <button class="score-button" onclick="pauseTimer()">Pause</button>
            <button class="score-button" onclick="resetTimer()">Reset</button>
            <button class="score-button" onclick="adjustTimer(-1)">-1s</button>
            <button class="score-button" onclick="adjustTimer(1)">+1s</button>
            <button class="score-button" onclick="adjustTimer(-60)">-1m</button>
            <button class="score-button" onclick="adjustTimer(60)">+1m</button>
            <button class="score-button" style="background-color: #dc3545;" onclick="playBuzzer()">
                <i class="fas fa-volume-up"></i> Buzzer
            </button>
        </div>
        
        <div class="timer" id="gameTimer">10:00</div>
        <div class="teams">
            <div class="team">
                <div class="team-name" onclick="editTeamName('teamA')" style="cursor: pointer;">HOME</div>
                <div class="score-wrapper">
                    <button class="score-button" onclick="updateScore('teamA', -1)">-</button>
                    <div class="team-score" id="teamAScore">00</div>
                    <button class="score-button" onclick="updateScore('teamA', 1)">+</button>
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
            <div class="center-container">
                <div class="label">PERIOD</div>
                <div class="period-control">
                    <button class="score-button" onclick="updatePeriod(-1)">-</button>
                    <div class="period" id="periodCounter">1</div>
                    <button class="score-button" onclick="updatePeriod(1)">+</button>
                </div>
            </div>
            <div class="team">
                <div class="team-name" onclick="editTeamName('teamB')" style="cursor: pointer;">GUEST</div>
                <div class="score-wrapper">
                    <button class="score-button" onclick="updateScore('teamB', -1)">-</button>
                    <div class="team-score" id="teamBScore">00</div>
                    <button class="score-button" onclick="updateScore('teamB', 1)">+</button>
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
        </div>
        <div class="middle-section">
            <div class="label">SHOT CLOCK</div>
            <div class="shot-clock-control">
                <button class="score-button" onclick="startShotClock()">Start</button>
                <button class="score-button" onclick="pauseShotClock()">Pause</button>
                <button class="score-button" onclick="resetShotClock()">Reset</button>
            </div>
            <div class="shot-clock" id="shotClock">24</div>
        </div>

        <!-- Settings Button -->
        <button class="score-button" onclick="openSettings()" style="position: fixed; top: 10px; right: 10px;">
            Settings
        </button>
    </div>

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

    <!-- End Match Confirmation Modal -->
    <div id="endMatchModal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.9); padding: 20px; border-radius: 10px; z-index: 1000;">
        <h3 style="color: white; margin-bottom: 15px;">End Match?</h3>
        <p style="color: white;">Are you sure you want to end the match?</p>
        <div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
            <button class="score-button" onclick="confirmEndMatch()">Yes, End Match</button>
            <button class="score-button" onclick="closeEndMatchModal()">Cancel</button>
        </div>
    </div>

    <script>
        // Game settings
        let gameSettings = {
            periodLength: 10,
            numberOfPeriods: 4,
            shotClockDuration: 24,
            maxFouls: 5,
            maxTimeouts: 5
        };

        // Timer variables
        let timerInterval;
        let timeLeft;
        let shotClockInterval;
        let shotClockTime;
        let isTimerRunning = false;
        let isShotClockRunning = false;

        // Initialize the game
        function initializeGame() {
            timeLeft = gameSettings.periodLength * 60;
            shotClockTime = gameSettings.shotClockDuration;
            updateDisplays();

            // Initialize settings inputs
            document.getElementById('periodLength').value = gameSettings.periodLength;
            document.getElementById('numberOfPeriods').value = gameSettings.numberOfPeriods;
            document.getElementById('shotClockDuration').value = gameSettings.shotClockDuration;

            // Initialize timeouts
            document.getElementById('teamATimeouts').textContent = gameSettings.maxTimeouts;
            document.getElementById('teamBTimeouts').textContent = gameSettings.maxTimeouts;
        }

        // Update all displays
        function updateDisplays() {
            updateGameTimer();
            updateShotClock();
        }

        // Update game timer display
        function updateGameTimer() {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            document.getElementById('gameTimer').textContent = 
                minutes.toString().padStart(2, '0') + ':' + 
                seconds.toString().padStart(2, '0');
        }

        // Update shot clock display
        function updateShotClock() {
            document.getElementById('shotClock').textContent = 
                shotClockTime.toString().padStart(2, '0');
        }

        // Score update function
        function updateScore(team, change) {
            let scoreElement = document.getElementById(team + 'Score');
            let score = parseInt(scoreElement.textContent);
            score = Math.max(0, score + change);
            scoreElement.textContent = score.toString().padStart(2, '0');
        }

        // Update fouls
        function updateFouls(team, change) {
            let foulsElement = document.getElementById(team + 'Fouls');
            let fouls = parseInt(foulsElement.textContent);
            fouls = Math.max(0, Math.min(gameSettings.maxFouls, fouls + change));
            foulsElement.textContent = fouls;

            // Alert when max fouls reached
            if (fouls >= gameSettings.maxFouls) {
                alert(team === 'teamA' ? 'HOME' : 'GUEST' + ' team has reached maximum fouls!');
            }
        }

        // Update timeouts
        function updateTimeouts(team, change) {
            let timeoutsElement = document.getElementById(team + 'Timeouts');
            let timeouts = parseInt(timeoutsElement.textContent);
            timeouts = Math.max(0, Math.min(gameSettings.maxTimeouts, timeouts + change));
            timeoutsElement.textContent = timeouts;
        }

        // Period controls
        function updatePeriod(change) {
            let periodElement = document.getElementById('periodCounter');
            let period = parseInt(periodElement.textContent);
            period = Math.max(1, Math.min(gameSettings.numberOfPeriods, period + change));
            periodElement.textContent = period;
            
            if (change > 0) {
                // Reset timers for new period
                resetTimer();
                resetShotClock();
            }
        }

        // Timer controls
        function startTimer() {
            if (!isTimerRunning && timeLeft > 0) {
                isTimerRunning = true;
                timerInterval = setInterval(() => {
                    if (timeLeft > 0) {
                        timeLeft--;
                        updateGameTimer();
                    } else {
                        pauseTimer();
                        alert('Period ended!');
                    }
                }, 1000);
            }
        }

        function pauseTimer() {
            clearInterval(timerInterval);
            isTimerRunning = false;
        }

        function resetTimer() {
            pauseTimer();
            timeLeft = gameSettings.periodLength * 60;
            updateGameTimer();
        }

        // Shot clock controls
        function startShotClock() {
            if (!isShotClockRunning && shotClockTime > 0) {
                isShotClockRunning = true;
                shotClockInterval = setInterval(() => {
                    if (shotClockTime > 0) {
                        shotClockTime--;
                        updateShotClock();
                    } else {
                        pauseShotClock();
                        alert('Shot clock expired!');
                    }
                }, 1000);
            }
        }

        function pauseShotClock() {
            clearInterval(shotClockInterval);
            isShotClockRunning = false;
        }

        function resetShotClock() {
            pauseShotClock();
            shotClockTime = gameSettings.shotClockDuration;
            updateShotClock();
        }

        // Settings modal functions
        function openSettings() {
            document.getElementById('settingsModal').style.display = 'block';
        }

        function closeSettings() {
            document.getElementById('settingsModal').style.display = 'none';
        }

        function saveSettings() {
            gameSettings.periodLength = parseInt(document.getElementById('periodLength').value);
            gameSettings.numberOfPeriods = parseInt(document.getElementById('numberOfPeriods').value);
            gameSettings.shotClockDuration = parseInt(document.getElementById('shotClockDuration').value);
            
            // Reset timers with new settings
            resetTimer();
            resetShotClock();
            closeSettings();
        }

        // Team name editing function
        function editTeamName(team) {
            let teamNameElement = document.querySelector(`.team-name[onclick="editTeamName('${team}')"]`);
            teamNameElement.contentEditable = 'true';
            teamNameElement.focus();
            teamNameElement.addEventListener('blur', function() {
                teamNameElement.contentEditable = 'false';
            });
        }

        // Timer adjustment function
        function adjustTimer(seconds) {
            timeLeft = Math.max(0, timeLeft + seconds);
            updateGameTimer();
        }

        // Buzzer function
        function playBuzzer() {
            const buzzer = document.getElementById('buzzerSound');
            buzzer.currentTime = 0;
            buzzer.play();
        }

        // Live Stream functions
        function openLiveStreamSettings() {
            document.getElementById('liveStreamModal').style.display = 'block';
        }

        function closeLiveStreamSettings() {
            document.getElementById('liveStreamModal').style.display = 'none';
        }

        function startStream() {
            const streamKey = document.getElementById('streamKey').value;
            const streamUrl = document.getElementById('streamUrl').value;
            
            if (!streamKey || !streamUrl) {
                alert('Please enter both Stream Key and Stream URL');
                return;
            }

            // Add your streaming logic here
            alert('Stream started!');
            closeLiveStreamSettings();
        }

        // End Match functions
        function endMatch() {
            document.getElementById('endMatchModal').style.display = 'block';
        }

        function closeEndMatchModal() {
            document.getElementById('endMatchModal').style.display = 'none';
        }

        function confirmEndMatch() {
            // Save match data or perform any cleanup
            if (confirm('Are you sure you want to end the match? This action cannot be undone.')) {
                window.location.href = 'index.php'; // Redirect to main page
            }
        }

        // Keyboard shortcuts
        document.addEventListener('keydown', function(event) {
            switch(event.key) {
                case ' ': // Space bar - Start/Pause main timer
                    if (isTimerRunning) {
                        pauseTimer();
                    } else {
                        startTimer();
                    }
                    break;
                case 's': // Start/Pause shot clock
                    if (isShotClockRunning) {
                        pauseShotClock();
                    } else {
                        startShotClock();
                    }
                    break;
                case 'r': // Reset shot clock
                    resetShotClock();
                    break;
                case 'p': // Next period
                    updatePeriod(1);
                    break;
                case 'b': // Previous period
                    updatePeriod(-1);
                    break;
                case '1': // Team A +1
                    updateScore('teamA', 1);
                    break;
                case '2': // Team A -1
                    updateScore('teamA', -1);
                    break;
                case '3': // Team B +1
                    updateScore('teamB', 1);
                    break;
                case '4': // Team B -1
                    updateScore('teamB', -1);
                    break;
                case 'f': // Team A foul
                    updateFouls('teamA', 1);
                    break;
                case 'g': // Team B foul
                    updateFouls('teamB', 1);
                    break;
                case 't': // Team A timeout
                    updateTimeouts('teamA', -1);
                    break;
                case 'y': // Team B timeout
                    updateTimeouts('teamB', -1);
                    break;
                case 'b': // Buzzer
                    playBuzzer();
                    break;
                case 'ArrowLeft': // -1s
                    if (event.ctrlKey) {
                        adjustTimer(-60); // -1m if Ctrl is pressed
                    } else {
                        adjustTimer(-1);
                    }
                    break;
                case 'ArrowRight': // +1s
                    if (event.ctrlKey) {
                        adjustTimer(60); // +1m if Ctrl is pressed
                    } else {
                        adjustTimer(1);
                    }
                    break;
            }
        });

        // Initialize the game when the page loads
        initializeGame();
    </script>
</body>
</html>