<?php
session_start();

require_once 'connection/conn.php';

// Function to get stream details
function getStreamDetails($schedule_id)
{
    $conn = con(); // Get the connection using conn()
    $stmt = $conn->prepare("SELECT stream_type, stream_url FROM stream_urls WHERE schedule_id = ? ORDER BY created_at DESC LIMIT 1");
    $stmt->bind_param("i", $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result(); // Get result for MySQLi
    return $result->fetch_assoc(); // Fetch associative array
}

$conn = con();
$stream_details = getStreamDetails($_GET['schedule_id'] ?? 0);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Live Stream Viewer</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="home.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- jQuery for AJAX -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://download.agora.io/sdk/release/AgoraRTC_N.js"></script>

    <style>
        .nav-container {
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            transition: transform 0.3s ease-in-out;
        }

        .nav-container.nav-hidden {
            transform: translateY(-100%);
        }

        .mt {
            margin-top: 90px;
        }

        .mt-5 {
            margin-top: 73px !important;
        }

        .video-container {
            display: grid;
            grid-template-rows: auto 180px;
            gap: 20px;
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            position: relative;
        }

        .video-wrapper {
            position: relative;
            width: 100%;
            padding-top: 56.25%;
            /* 16:9 Aspect Ratio */
            background: #000;
            border-radius: 8px;
            overflow: hidden;
        }

        .fullscreen-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background: rgba(0, 0, 0, 0.6);
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 4px;
            cursor: pointer;
            z-index: 9999;
            display: flex;
            align-items: center;
            gap: 5px;
            transition: background-color 0.2s ease;
        }

        .fullscreen-btn:hover {
            background: rgba(0, 0, 0, 0.8);
        }

        .fullscreen-btn i {
            font-size: 1.1em;
        }

        .fullscreen-btn span {
            font-size: 0.9em;
        }

        #streamContainer {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
        }

        #streamContainer video {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        .video-wrapper.fullscreen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            padding-top: 0;
            z-index: 9998;
        }

        .video-wrapper.fullscreen+.score-container {
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: auto;
            min-width: 300px;
            max-width: 500px;
            /* background: rgba(0, 0, 0, 0.6); */
            z-index: 9999;
            height: auto;
            min-height: auto;
            padding: 0px 15px;
            padding-bottom: 0;
            border-radius: 8px;
            backdrop-filter: blur(5px);
        }

        .video-wrapper.fullscreen+.score-container .match-body {
            gap: 15px;
            padding: 0;
        }

        .video-wrapper.fullscreen+.score-container .team-name {
            color: rgba(0, 0, 0, 0.95);
            font-size: 0.85em;
            max-width: 120px;
        }

        .video-wrapper.fullscreen+.score-container .team-score {
            color: #ff4757;
            font-size: 1.5em;
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
        }

        .video-wrapper.fullscreen+.score-container .vs-section {
            min-width: 40px;
            color: rgba(0, 0, 0, 0.8);
            font-size: 0.8em;
        }

        .video-wrapper.fullscreen+.score-container .stat-item {
            font-size: 0.75em;
            margin: 1px 0;
            color: rgba(0, 0, 0, 0.8);
        }

        .video-wrapper.fullscreen+.score-container .stat-label {
            color: rgba(0, 0, 0, 0.7);
        }

        .video-wrapper.fullscreen+.score-container .period-info {
            color: rgba(0, 0, 0, 0.9);
            font-size: 0.8em;
        }

        .video-wrapper.fullscreen+.score-container .stats-box {
            margin-top: 2px;
        }

        .score-container {
            width: 100%;
            height: 180px;
            padding: 15px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 8px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .match-result-card {
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .match-body {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 30px;
            width: 100%;
            max-width: 800px;
        }

        .team-section {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
        }

        .team-name {
            font-size: 1.1em;
            font-weight: bold;
            color: #2c3e50;
            margin: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 200px;
        }

        .team-score {
            font-size: 2.5em;
            font-weight: bold;
            color: #e74c3c;
            line-height: 1;
        }

        .vs-section {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 5px;
            min-width: 80px;
        }

        .stats-box {
            font-size: 0.9em;
            margin-top: 5px;
        }

        .stat-item {
            display: flex;
            justify-content: center;
            gap: 8px;
            color: #2c3e50;
            font-size: 0.9em;
        }

        #streamStatus {
            margin-bottom: 15px;
        }

        .back-button {
            margin: 15px 0;
        }

        .score-container h3 {
            margin-top: 0;
            margin-bottom: 10px;
            font-size: 1.5rem;
        }

        /* Responsive breakpoints */
        @media (max-width: 1200px) {
            .video-container {
                height: calc(100vh - 220px);
            }
        }

        @media (max-width: 992px) {
            .video-container {
                height: calc(100vh - 200px);
                grid-template-rows: 60% 40%;
            }
        }

        @media (max-width: 768px) {
            .mt {
                margin-top: 80px;
            }

            .video-container {
                grid-template-rows: auto 150px;
                gap: 15px;
            }

            .score-container {
                height: 150px;
                padding: 10px;
            }

            .match-body {
                gap: 15px;
            }

            .team-name {
                font-size: 1em;
                max-width: 150px;
            }

            .team-score {
                font-size: 2em;
            }

            .vs-section {
                min-width: 60px;
            }

            .stats-box {
                font-size: 0.8em;
            }

            .video-wrapper.fullscreen+.score-container {
                bottom: 10px;
                min-width: 280px;
                padding: 4px 10px;
            }

            .video-wrapper.fullscreen+.score-container .match-body {
                gap: 10px;
            }

            .video-wrapper.fullscreen+.score-container .team-name {
                font-size: 0.75em;
                max-width: 100px;
            }

            .video-wrapper.fullscreen+.score-container .team-score {
                font-size: 1.3em;
            }

            .video-wrapper.fullscreen+.score-container .vs-section {
                min-width: 30px;
                font-size: 0.7em;
            }

            .video-wrapper.fullscreen+.score-container .stat-item {
                font-size: 0.7em;
            }
        }

        @media (max-width: 576px) {
            .mt {
                margin-top: 70px;
            }

            .video-container {
                grid-template-rows: auto 130px;
                gap: 10px;
            }

            .score-container {
                height: 130px;
                padding: 8px;
            }

            .match-body {
                gap: 10px;
            }

            .team-name {
                font-size: 0.9em;
                max-width: 120px;
            }

            .team-score {
                font-size: 1.8em;
            }

            .vs-section {
                min-width: 50px;
            }

            .stats-box {
                font-size: 0.75em;
            }
        }

        .facebook-embed {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #000;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .facebook-embed iframe {
            width: 100%;
            height: 100%;
            background: #000;
        }

        .video-wrapper.fullscreen .facebook-embed {
            width: 100vw;
            height: 100vh;
        }

        .video-wrapper.fullscreen .facebook-embed iframe {
            width: 100vw;
            height: 100vh;
        }
    </style>
</head>

<body>
    <div class="nav-container">
        <?php include 'navbarhome.php'; ?>
    </div>

    <div class="page-header-live mt-5">
        <div class="container mt-5 pt-4 pb-4 text-center">
            <h1 class="h3 fw-semibold mb-2">Live Matches</h1>
            <p class="mb-0" style="font-size: 0.9rem; opacity: 0.9;">Watch matches as they happen in real-time</p>
        </div>
    </div>

    <div class="container mt">
        <!-- <div class="back-button">
            <a href="livematches.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> Back to Live Matches
            </a>
        </div> -->

        <h2 class="text-center mb-4">Live Stream - <?php echo htmlspecialchars($_GET['game_name'] ?? ''); ?></h2>

        <!-- <div id="streamStatus" class="alert d-none"></div> -->

        <div class="video-container">
            <div class="video-wrapper">
                <button class="fullscreen-btn">
                    <i class="fas fa-expand"></i>
                    <span>Fullscreen</span>
                </button>
                <div id="streamContainer">
                    <?php if ($stream_details && $stream_details['stream_type'] === 'facebook'): ?>
                        <div class="facebook-embed">
                            <iframe
                                src="<?php echo htmlspecialchars($stream_details['stream_url']); ?>"
                                width="100%"
                                height="100%"
                                style="border:none;overflow:hidden"
                                scrolling="no"
                                frameborder="0"
                                allowfullscreen="true"
                                allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share"
                                allowFullScreen="true">
                            </iframe>
                        </div>
                    <?php else: ?>
                        <div class="no-stream-placeholder">
                            <i class="fas fa-video-slash"></i>
                            <h3>No Live Stream Available</h3>
                            <p>The stream has not started yet. Please check back later.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="score-container">
                <div id="scoreDisplay"></div>
            </div>
        </div>


    </div>

    <?php include 'footerhome.php' ?>

    <script>
        // Add navbar hide/show functionality
        let lastScrollTop = 0;
        const navbar = document.querySelector('.nav-container');
        const scrollThreshold = 10; // Minimum scroll amount before hiding/showing

        window.addEventListener('scroll', () => {
            const scrollTop = window.pageYOffset || document.documentElement.scrollTop;

            if (Math.abs(scrollTop - lastScrollTop) <= scrollThreshold) return;

            if (scrollTop > lastScrollTop && scrollTop > navbar.offsetHeight) {
                // Scrolling down
                navbar.classList.add('nav-hidden');
            } else {
                // Scrolling up
                navbar.classList.remove('nav-hidden');
            }

            lastScrollTop = scrollTop;
        });

        // Helper functions from livematches.php
        function renderAdditionalInfo(team, additionalInfo, sourceTable) {
            let additionalInfoHtml = '';

            if (sourceTable === 'live_scores') {
                // if (additionalInfo.period) {
                //     additionalInfoHtml += `
                //         <div class="stat-item">
                //             <span class="stat-label">Period</span>
                //             <span>${additionalInfo.period}</span>
                //         </div>
                //     `;
                // }
                // if (additionalInfo.timer) {
                //     additionalInfoHtml += `
                //         <div class="stat-item">
                //             <span class="stat-label">Time</span>
                //             <span>${additionalInfo.timer}</span>
                //         </div>
                //     `;
                // }
                if (additionalInfo.fouls !== null) {
                    additionalInfoHtml += `
                        <div class="stat-item">
                            <span class="stat-label">Fouls</span>
                            <span>${additionalInfo.fouls}</span>
                        </div>
                    `;
                }
                if (additionalInfo.timeouts !== null) {
                    additionalInfoHtml += `
                        <div class="stat-item">
                            <span class="stat-label">Timeouts</span>
                            <span>${additionalInfo.timeouts}</span>
                        </div>
                    `;
                }
            } else if (sourceTable === 'live_set_scores') {
                if (additionalInfo.sets_won !== null) {
                    additionalInfoHtml += `
                        <div class="stat-item">
                            <span class="stat-label">Sets Won</span>
                            <span>${additionalInfo.sets_won}</span>
                        </div>
                    `;
                }
                // if (additionalInfo.current_set) {
                //     additionalInfoHtml += `
                //         <div class="stat-item">
                //             <span class="stat-label">Current Set</span>
                //             <span>${additionalInfo.current_set}</span>
                //         </div>
                //     `;
                // }
                if (additionalInfo.timeouts !== null) {
                    additionalInfoHtml += `
                        <div class="stat-item">
                            <span class="stat-label">Timeouts</span>
                            <span>${additionalInfo.timeouts}</span>
                        </div>
                    `;
                }
            }

            return additionalInfoHtml;
        }

        function renderVsSection(match) {
            let vsContent = '<div class="vs-section">';
            vsContent += '<div>VS</div>';

            if (match.source_table === 'live_scores') {
                vsContent += `
                    <div class="period-info">
                        Period ${match.teamA.additional_info.period}
                    </div>
                    ${match.teamA.additional_info.timer ? `
                        <div class="timer-display">
                            ${match.teamA.additional_info.timer}
                            <div class="timer-status">(${match.teamA.additional_info.timer_status})</div>
                        </div>
                    ` : ''}
                `;
            } else if (match.source_table === 'live_set_scores' && match.teamA.additional_info.current_set) {
                vsContent += `
                    <div class="period-info">
                        Set ${match.teamA.additional_info.current_set}
                    </div>
                `;
            }

            vsContent += '</div>';
            return vsContent;
        }

        // Get URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const scheduleId = parseInt(urlParams.get('schedule_id')); // Convert to number
        const teamA_id = urlParams.get('teamA_id');
        const teamB_id = urlParams.get('teamB_id');
        const game_id = urlParams.get('game_id');
        const game_name = urlParams.get('game_name');

        // Debug log the parameters
        console.log('URL Parameters:', {
            scheduleId,
            teamA_id,
            teamB_id,
            game_id,
            game_name
        });

        let client;
        let scoreUpdateInterval;

        // Fullscreen toggle functionality
        const videoWrapper = document.querySelector('.video-wrapper');
        const fullscreenBtn = document.querySelector('.fullscreen-btn');
        const streamContainer = document.getElementById('streamContainer');

        fullscreenBtn.addEventListener('click', () => {
            if (videoWrapper.classList.contains('fullscreen')) {
                videoWrapper.classList.remove('fullscreen');
                fullscreenBtn.innerHTML = '<i class="fas fa-expand"></i><span>Fullscreen</span>';
            } else {
                videoWrapper.classList.add('fullscreen');
                fullscreenBtn.innerHTML = '<i class="fas fa-compress"></i><span>Exit Fullscreen</span>';
            }
        });

        // Update the stream initialization
        async function initializeStream() {
            console.log('Initializing streaming context');
            const APP_ID = "60a61e72ebf84ea39be8d203e3269b9c";
            const CHANNEL_NAME = `match_${scheduleId}_${game_id}`;
            console.log('Connecting to channel:', CHANNEL_NAME);

            try {
                client = AgoraRTC.createClient({
                    mode: "live",
                    codec: "vp8"
                });

                await client.setClientRole("audience");
                await client.join(APP_ID, CHANNEL_NAME, null, null);

                client.on("user-published", async (user, mediaType) => {
                    console.log(`User published: ${mediaType}`);
                    await client.subscribe(user, mediaType);

                    if (mediaType === "video") {
                        // Clear the placeholder and create video element
                        streamContainer.innerHTML = '<video id="viewerVideo" autoplay playsinline></video>';
                        const remoteVideoTrack = user.videoTrack;
                        remoteVideoTrack.play("viewerVideo");
                    }

                    if (mediaType === "audio") {
                        const remoteAudioTrack = user.audioTrack;
                        remoteAudioTrack.play();
                    }
                });

                client.on("user-unpublished", (user) => {
                    console.log("User unpublished");
                    // Show placeholder when stream ends
                    streamContainer.innerHTML = `
                        <div class="no-stream-placeholder">
                            <i class="fas fa-video-slash"></i>
                            <h3>Stream Ended</h3>
                            <p>The live stream has ended.</p>
                        </div>
                    `;
                });

                updateStatus("Connected to live stream");
            } catch (error) {
                console.error("Error watching live stream:", error);
                updateStatus(`Failed to watch live stream: ${error.message}`, true);
            }
        }

        function updateStatus(message, isError = false) {
            console.log('Status update:', message, isError ? '(error)' : '');
            const statusDiv = document.getElementById('streamStatus');
            statusDiv.className = `alert ${isError ? 'alert-danger' : 'alert-info'}`;
            statusDiv.textContent = message;
            statusDiv.classList.remove('d-none');
        }

        function updateScores() {
            function getUrlParams() {
                const params = new URLSearchParams(window.location.search);
                return {
                    department_id: params.get("department_id"),
                    grade_level: params.get("grade_level"),
                };
            }

            const {
                department_id,
                grade_level
            } = getUrlParams();

            $.ajax({
                url: 'fetch_live_scores.php',
                method: 'GET',
                dataType: 'json',
                data: {
                    department_id: department_id,
                    grade_level: grade_level
                },
                success: function(data) {
                    if (data.error) {
                        console.error('Error updating scores:', data.message);
                        return;
                    }

                    const matches = Array.isArray(data) ? data : [data];
                    console.log('Received matches:', matches); // Debug log all matches

                    // Convert schedule_id to number for comparison
                    const currentMatch = matches.find(m => parseInt(m.schedule_id) === scheduleId);
                    console.log('Found current match:', currentMatch); // Debug log found match

                    if (currentMatch) {
                        const scoreDisplay = document.getElementById('scoreDisplay');
                        const matchCard = `
                            <div class="match-result-card" data-match-id="${currentMatch.schedule_id}">
                                <div class="match-body">
                                    <div class="team-section">
                                        <div class="team-name">${currentMatch.teamA.name}</div>
                                        <div class="team-score">${currentMatch.teamA.score}</div>
                                        <div class="stats-box">
                                            ${renderAdditionalInfo(currentMatch.teamA, currentMatch.teamA.additional_info, currentMatch.source_table)}
                                        </div>
                                    </div>
                                    ${renderVsSection(currentMatch)}
                                    <div class="team-section">
                                        <div class="team-name">${currentMatch.teamB.name}</div>
                                        <div class="team-score">${currentMatch.teamB.score}</div>
                                        <div class="stats-box">
                                            ${renderAdditionalInfo(currentMatch.teamB, currentMatch.teamB.additional_info, currentMatch.source_table)}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        scoreDisplay.innerHTML = matchCard;
                    } else {
                        console.log('No match found for schedule_id:', scheduleId); // Debug log if no match found
                        const scoreDisplay = document.getElementById('scoreDisplay');
                        scoreDisplay.innerHTML = `
                            <div class="alert alert-info">
                                No live scores available for this match at the moment.
                            </div>
                        `;
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching live scores:', error);
                    const scoreDisplay = document.getElementById('scoreDisplay');
                    scoreDisplay.innerHTML = `
                        <div class="alert alert-danger">
                            Failed to fetch live scores. Please try again later.
                        </div>
                    `;
                }
            });
        }

        // Initialize when the page loads
        $(document).ready(function() {
            <?php if (!$stream_details || $stream_details['stream_type'] !== 'facebook'): ?>
                initializeStream();
            <?php endif; ?>
            updateScores();
            scoreUpdateInterval = setInterval(updateScores, 1000);
        });

        // Clean up when the page is unloaded
        window.addEventListener('beforeunload', function() {
            if (scoreUpdateInterval) {
                clearInterval(scoreUpdateInterval);
            }
            if (client) {
                client.leave();
            }
        });
    </script>
</body>

</html>