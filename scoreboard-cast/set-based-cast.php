<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set-Based Scoreboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700&display=swap" rel="stylesheet">
    <script type="text/javascript" src="https://www.gstatic.com/cv/js/sender/v1/cast_sender.js?loadCastFramework=1"></script>

    <link rel="stylesheet" href="../livescoring/set-based.css">
</head>

<body>
    <!-- Schedule ID -->

    <!-- End Match Button -->
    <button class="score-button settings-button" onclick="openSettings()">
        <i class="fas fa-cog"></i>
    </button>

    <div class="scoreboard">
        <!-- 🔹 Row 1: Timeouts & Set -->
        <div class="row row-top">
            <!-- Team A Timeouts -->
            <div class="timeout-box">
                <div class="digit-label">Timeout:</div>
                <div class="timeout-buttons">
                    <div class="digit" id="teamA-timeouts">0</div>
                </div>
            </div>

            <!-- Sets Won -->
            <div class="sets-won-container">
                <div class="sets-won-label">SETS WON</div>
                <div class="sets-won-display">
                    <div id="teamA-sets"></div>
                    <span>-</span>
                    <div id="teamB-sets"></div>
                </div>

                <!-- 🔁 Reset Button -->
                <div class="reset-wrapper">
                </div>
            </div>




            <!-- Team B Timeouts -->
            <div class="timeout-box">
                <div class="digit-label">Timeout:</div>

                <div class="timeout-buttons">
                    <div class="digit" id="teamB-timeouts">0</div>
                </div>
            </div>
        </div>

        <!-- 🔸 Row 2: Scores -->
        <div class="row row-bottom">
            <!-- Team A Score -->
            <div class="team" id="teamA">
                <div class=" digit-label">
                    <h2></h2>
                </div>
                <div class="score-wrapper">
                    <div class="digit score" id="scoreA"></div>
                </div>
            </div>

            <!-- Current Set -->
            <div class="set-center">
                <div class="digit-label"> CURRENT SET</div>
                <div class="digit c-set" id="currentSet">1</div>
                <div class="set-buttons">
                    <!-- <button onclick="updateSet(-1)">-</button>
                    <button onclick="updateSet(1)">+</button> -->
                    <!-- <button id="endSetBtn" onclick="ScoreManager.confirmEndSet()">End Set</button> -->
                </div>
            </div>
            <!-- Team B Score -->
            <div class="team" id="teamB">
                <div class="digit-label">
                    <h2></h2>
                </div>
                <div class="score-wrapper">
                    <div class="digit score" id="scoreB"></div>
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


                <div style="display: flex; gap: 10px; justify-content: center; margin-top: 10px;">
                    <!-- <button class="score-button" onclick="saveSettings()">Save</button> -->
                    <button class="score-button" onclick="closeSettings()">Cancel</button>
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


    <!-- SweetAlert2 library -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const scheduleId = urlParams.get('schedule_id');

        if (scheduleId) {
            function fetchSetScoreboard() {
                fetch(`fetch/fetch_live_set_scores.php?schedule_id=${scheduleId}`)
                    .then(res => res.json())
                    .then(data => {
                        console.log('*_Live Set-Based Data_*', data);

                        document.getElementById('scoreA').textContent = data.teamA_score;
                        document.getElementById('scoreB').textContent = data.teamB_score;
                        document.getElementById('teamA-sets').textContent = data.teamA_sets_won;
                        document.getElementById('teamB-sets').textContent = data.teamB_sets_won;
                        document.getElementById('currentSet').textContent = data.current_set;
                        document.getElementById('teamA-timeouts').textContent = data.timeout_teamA;
                        document.getElementById('teamB-timeouts').textContent = data.timeout_teamB;
                        document.querySelector('#teamA h2').textContent = data.teamA_name;
                        document.querySelector('#teamB h2').textContent = data.teamB_name;
                    })
                    .catch(error => {
                        console.error('Set-Based Fetch Error:', error);
                    });
            }

            setInterval(fetchSetScoreboard, 1000);
        } else {
            console.error('Missing schedule_id in URL');
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