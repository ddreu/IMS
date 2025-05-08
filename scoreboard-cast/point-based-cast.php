<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Scoreboard</title>
    <link rel="stylesheet" href="../livescoring/scb_bsktbll.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


    <audio id="buzzerSound" src="buzzer/buzzer.mp3"></audio>


    <!-- Ensure sb-basketball.js is loaded after game data is set -->
</head>

<body>
    <!-- Header Controls -->
    <div class="header-container">


        <!-- Right Side: Live Stream & Settings -->
        <div class="header-buttons">
            <!-- <button class="score-button live-match" onclick="openLiveStreamSettings()">
                    <i class="fas fa-video"></i>
                </button> -->
            <button class="score-button settings-button" onclick="openSettings()">
                <i class="fas fa-cog"></i>
            </button>
        </div>
    </div>
    <!-- Schedule ID -->
    <div class="scoreboard">
        <!-- Header Controls -->


        <!-- Teams and Scores -->
        <div class="team-a-container">
            <div class="team team-a">
                <div class="team-score">
                    <div class="team-name small" onclick="editTeamName('teamA')" style="cursor: pointer;"></div>
                    <div class="score-wrapper">
                        <!-- <button class="score-button" onclick="updateScore('teamA', -1)">-</button> -->
                        <div class="team-score score" id="teamAScore">00</div>
                        <!-- <button class="score-button" onclick="updateScore('teamA', 1)">+</button> -->
                    </div>
                </div>
                <div class="foul-container">
                    <div class="foul-label">FOUL</div>
                    <div class="fouls-control">
                        <!-- <button class="score-button" onclick="updateFouls('teamA', -1)">-</button> -->
                        <div class="fouls" id="teamAFouls">0</div>
                        <!-- <button class="score-button" onclick="updateFouls('teamA', 1)">+</button> -->
                    </div>
                    <div class="timeout-label">TIMEOUT</div>
                    <div class="timeouts-control">
                        <!-- <button class="score-button" onclick="updateTimeouts('teamA', -1)">-</button> -->
                        <div class="timeouts" id="teamATimeouts">4</div>
                        <!-- <button class="score-button" onclick="updateTimeouts('teamA', 1)">+</button> -->
                    </div>
                </div>
            </div>
        </div> <!-- Closing .team-a-container -->

        <div class="control-container">
            <!-- Timer Controls -->
            <div class="timer-wrapper">
                <div class="timer-controls" style="margin-bottom: 10px;">
                    <!-- <button class="score-button" onclick="startTimer()"><i class="fas fa-play"></i></button>
                    <button class="score-button" onclick="pauseTimer()"><i class="fas fa-pause"></i></button>
                    <button class="score-button" onclick="resetTimer()"><i class="fas fa-redo"></i></button>
                    <button class="score-button" onclick="adjustTimer(-1)">-1s</button>
                    <button class="score-button" onclick="adjustTimer(1)">+1s</button>
                    <button class="score-button" onclick="adjustTimer(-60)">-1m</button>
                    <button class="score-button" onclick="adjustTimer(60)">+1m</button>
                    <button class="score-button" style="background-color: #dc3545;" onclick="playBuzzer()">
                        <i class="fas fa-volume-up"></i> Buzzer
                    </button> -->
                </div>
                <div class="timer" id="gameTimer">10:00</div>
            </div>
            <div class="middle-container">
                <div class="center-container">

                    <div class="period-control">
                        <!-- <button class="score-button" onclick="updatePeriod(-1)">-</button> -->
                        <div class="period" id="periodCounter">1</div>
                        <!-- <button class="score-button" onclick="updatePeriod(1)">+</button> -->
                    </div>
                    <div class="label period-label-1">PERIOD</div>
                </div>

                <div class="middle-section">

                    <!-- <div class="shot-clock-control">
                        <button class="score-button" onclick="startShotClock()"><i class="fas fa-play"></i></button>
                        <button class="score-button" onclick="pauseShotClock()"><i class="fas fa-pause"></i></button>
                        <button class="score-button" onclick="resetShotClock()"><i class="fas fa-redo"></i></button>
                    </div> -->
                    <div class="shot-clock-control">
                        <!-- <button class="score-button" onclick="updateShotClock(-1)">-</button> -->
                        <div class="shot-clock" id="shotClock">24</div>
                        <!--<button class="score-button" onclick="updateShotClock(1)">+</button>-->
                    </div>
                    <div class="label">SHOT CLOCK</div>
                </div>
            </div>
        </div> <!-- Closing .control-container -->

        <!-- Team B -->
        <div class="team-b-container">
            <div class="team team-b">
                <div class="team-score">
                    <div class="team-name small" onclick="editTeamName('teamB')" style="cursor: pointer;"></div>
                    <div class="score-wrapper">
                        <div class="team-score score" id="teamBScore">00</div>
                    </div>
                </div>
                <div class="foul-container">
                    <div class="foul-label">FOUL</div>
                    <div class="fouls-control">
                        <div class="fouls" id="teamBFouls">0</div>
                    </div>
                    <div class="timeout-label">TIMEOUT</div>
                    <div class="timeouts-control">
                        <div class="timeouts" id="teamBTimeouts">4</div>
                    </div>
                </div>
            </div>
        </div> <!-- Closing .team-b-container -->
    </div> <!-- Closing .scoreboard -->


    <div id="settingsModal" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0,0,0,0.9); padding: 20px; border-radius: 10px; z-index: 1000;">
        <h3 style="color: white; margin-bottom: 15px;">Game Settings</h3>
        <div style="display: flex; flex-direction: column; gap: 10px;">

            <button class="score-button fullscreen-button" onclick="toggleFullscreen()">
                <i class="fas fa-expand"></i>
            </button>

            <!-- <button class="score-button cast-button" onclick="requestCast()">
                <i class="fas fa-tv"></i> Cast
            </button> -->



            <div style="display: flex; gap: 10px; justify-content: center; margin-top: 10px;">
                <button class="score-button" onclick="saveSettings()">Save</button>
                <button class="score-button" onclick="closeSettings()">Cancel</button>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const scheduleId = urlParams.get('schedule_id');

        // Ensure the schedule_id is available before making the API call
        if (scheduleId) {
            window.gameData = {
                schedule_id: scheduleId
            };

            function formatTime(seconds) {
                const minutes = Math.floor(seconds / 60);
                const remainingSeconds = seconds % 60;
                return `${minutes < 10 ? '0' : ''}${minutes}:${remainingSeconds < 10 ? '0' : ''}${remainingSeconds}`;
            }

            setInterval(() => {
                fetch(`fetch/fetch_live_scores.php?schedule_id=${window.gameData.schedule_id}`)
                    .then(res => res.json())
                    .then(data => {
                        console.log(data); // Log the data for debugging

                        // Format the time_remaining before updating the display
                        const formattedTime = formatTime(data.time_remaining);

                        document.getElementById('teamAScore').textContent = data.teamA_score;
                        document.getElementById('teamBScore').textContent = data.teamB_score;
                        document.getElementById('teamAFouls').textContent = data.teamAFouls;
                        document.getElementById('teamBFouls').textContent = data.teamBFouls;
                        document.getElementById('teamATimeouts').textContent = data.teamATimeouts;
                        document.getElementById('teamBTimeouts').textContent = data.teamBTimeouts;
                        document.getElementById('periodCounter').textContent = data.current_period;
                        document.getElementById('gameTimer').textContent = formattedTime; // Use the formatted time
                        document.getElementById('shotClock').textContent = data.shot_clock;
                        document.querySelector('.team-a .team-name').textContent = data.teamA_name;
                        document.querySelector('.team-b .team-name').textContent = data.teamB_name;
                    });
            }, 1000);

        } else {
            console.error('schedule_id is missing in the URL');
        }

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