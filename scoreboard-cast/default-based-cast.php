<!DOCTYPE html>
<html lang="en">

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Scoreboard</title>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script type="text/javascript" src="https://www.gstatic.com/cv/js/sender/v1/cast_sender.js?loadCastFramework=1"></script>

    <!-- Stylesheet -->
    <link rel="stylesheet" href="../livescoring/default_scoreboard.css" />
</head>

<body>
    <button class="score-button settings-button" onclick="openSettings()">
        <i class="fas fa-cog"></i>
    </button>

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


            <div style="display: flex; gap: 10px; justify-content: center; margin-top: 10px;">
                <!-- <button class="score-button" onclick="saveSettings()">Save</button> -->
                <button class="score-button player-stats-button" onclick="closeSettings()">Cancel</button>
            </div>
        </div>
    </div>

    <div class="scoreboard">
        <!-- Team A -->
        <div class="team">
            <h2 id="teamAName"></h2>
            <p id="teamAScore"></p>
            <div class="btn-container">
            </div>
        </div>
        <button id="reset-btn" onclick="">VS</button>


        <!-- Team B -->
        <div class="team">
            <h2 id="teamBName"></h2>
            <p id="teamBScore"></p>
            <div class="btn-container">
            </div>
        </div>
    </div>
    <!-- Script-->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="end_match.js"></script>

    <script>
        const urlParams = new URLSearchParams(window.location.search);
        const scheduleId = urlParams.get('schedule_id');

        if (scheduleId) {
            function fetchDefaultScores() {
                fetch(`fetch/fetch_live_default_scores.php?schedule_id=${scheduleId}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.error) {
                            console.warn('*_API Error_*', data.error);
                            return;
                        }

                        console.log('*_Live Default Score Data_*', data);

                        document.getElementById('teamAName').textContent = data.teamA_name;
                        document.getElementById('teamBName').textContent = data.teamB_name;
                        document.getElementById('teamAScore').textContent = data.teamA_score;
                        document.getElementById('teamBScore').textContent = data.teamB_score;
                    })
                    .catch(error => {
                        console.error('*_Fetch Failed_*:', error);
                    });
            }

            setInterval(fetchDefaultScores, 1000);
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