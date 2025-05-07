<?php

session_start();

?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Live Matches</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <!-- <link href="home.css" rel="stylesheet"> -->
    <link href="topnav.css" rel="stylesheet">
    <link href="livescore.css" rel="stylesheet">
    <link href="footer.css" rel="stylesheet">

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- jQuery for AJAX -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <!-- <script src="https://download.agora.io/sdk/release/AgoraRTC_N.js"></script> -->

    <style>
        .team-score-display {
            padding: 10px;
            margin: 5px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .team-name {
            font-weight: bold;
        }

        .score {
            font-size: 1.2em;
            color: #2c3e50;
        }

        .timer-status {
            font-size: 0.8em;
            color: #666;
            margin-top: 2px;
            text-transform: capitalize;
        }

        .timer-display {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 2px;
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <?php include 'navbarhome.php'; ?>

    <div class="page-header-live">
        <div class="container mt-5 pt-4 pb-4 text-center">
            <h1 class="h3 fw-semibold mb-2">Live Matches</h1>
            <p class="mb-0" style="font-size: 0.9rem; opacity: 0.9;">Watch matches as they happen in real-time</p>
        </div>
    </div>

    <div class="container pb-4">
        <div class="match-results">
        </div>
    </div>

    <?php include 'footerhome.php' ?>

    <script>
        // Helper function to render additional info
        function renderAdditionalInfo(team, additionalInfo, sourceTable) {
            let additionalInfoHtml = '';

            if (sourceTable === 'live_scores') {
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

        // Helper function to render VS section
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

        function fetchLiveScores(callback = null) {
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
                    if (callback) {
                        callback(data);
                        return;
                    }

                    const matchResultsContainer = $('.match-results');
                    matchResultsContainer.empty();

                    if (data.error) {
                        matchResultsContainer.html(`
                            <div class="text-center p-4">
                                <i class="fas fa-exclamation-circle text-danger"></i>
                                <h3 class="h5 mb-2">Error</h3>
                                <p class="text-muted mb-0" style="font-size: 0.9rem;">${data.message}</p>
                            </div>
                        `);
                        return;
                    }

                    const matches = Array.isArray(data) ? data : [data];
                    if (matches.length === 0) {
                        matchResultsContainer.html(`
                            <div class="text-center p-4">
                                <i class="fas fa-basketball-ball" style="color: #808080;"></i>
                                <h3 class="h5 mb-2">No Live Matches</h3>
                                <p class="text-muted mb-0" style="font-size: 0.9rem;">There are no ongoing matches at the moment. Check back later!</p>
                            </div>
                        `);
                        return;
                    }

                    matches.forEach(match => {
                        const liveIndicator = `<span class="badge bg-success">Live</span>`;
                        const matchCard = `
                            <div class="match-result-card" data-match-id="${match.schedule_id}">
                                <div class="match-header">
                                    <div class="d-flex align-items-center">
                                        <span class="game-icon">
                                            <i class="fas fa-basketball-ball"></i>
                                        </span>
                                        <h5 class="match-title">${match.game_name}</h5>
                                    </div>
                                    <div class="match-date">
                                        <i class="far fa-calendar-alt"></i>
                                        ${match.formatted_date || 'Date Not Available'}
                                    </div>
                                </div>
                                <div>
                                    <div class="live-indicator d-flex justify-content-between align-items-center" style="margin-left: 10px; margin-right: 10px;">
                                        <div class="live-indicator-item">
                                            ${liveIndicator}
                                        </div>
                                        <button onclick='viewStream(${JSON.stringify(match)})' class="btn btn-sm btn-danger">
                                            <i class="fas fa-video"></i> Watch Live
                                        </button>
                                    </div>
                                </div>
                                <div class="match-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-5">
                                            <div class="team-section">
                                                <div class="team-name">${match.teamA.name}</div>
                                                <div class="team-score">${match.teamA.score}</div>
                                                <div class="stats-box">
                                                    ${renderAdditionalInfo(match.teamA, match.teamA.additional_info, match.source_table)}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            ${renderVsSection(match)}
                                        </div>
                                        <div class="col-md-5">
                                            <div class="team-section">
                                                <div class="team-name">${match.teamB.name}</div>
                                                <div class="team-score">${match.teamB.score}</div>
                                                <div class="stats-box">
                                                    ${renderAdditionalInfo(match.teamB, match.teamB.additional_info, match.source_table)}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        matchResultsContainer.append(matchCard);
                    });
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching live scores:', error);
                    if (callback) {
                        callback({
                            error: true,
                            message: 'Failed to fetch live scores'
                        });
                        return;
                    }

                    $('.match-results').html(`
                        <div class="text-center p-4">
                            <i class="fas fa-exclamation-circle text-danger"></i>
                            <h3 class="h5 mb-2">Error</h3>
                            <p class="text-muted mb-0" style="font-size: 0.9rem;">Failed to fetch live scores. Please try again later.</p>
                        </div>
                    `);
                }
            });
        }

        function viewStream(match) {
            const scheduleId = match.schedule_id;
            const teamA_id = match.teamA.id;
            const teamB_id = match.teamB.id;
            const game_id = match.game_id;
            const department_id = new URLSearchParams(window.location.search).get('department_id');
            const grade_level = new URLSearchParams(window.location.search).get('grade_level');
            const currentQueryString = window.location.search.substring(1); // Get the current query string without the '?'

            console.log('Opening stream viewer for match:', {
                scheduleId,
                teamA_id,
                teamB_id,
                game_id,
                game_name: match.game_name,
                department_id,
                grade_level,
                query_string: currentQueryString
            });

            // Redirect to live.php with match parameters and original query string
            window.location.href = `live.php?${currentQueryString}&schedule_id=${scheduleId}&teamA_id=${teamA_id}&teamB_id=${teamB_id}&game_id=${game_id}&game_name=${encodeURIComponent(match.game_name)}`;
        }

        // Start fetching when document is ready
        $(document).ready(function() {
            fetchLiveScores();
            setInterval(fetchLiveScores, 1000);
        });
    </script>
</body>

</html>