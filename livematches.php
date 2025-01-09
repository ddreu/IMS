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
    <link href="home.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <!-- jQuery for AJAX -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <style>

    </style>
</head>

<body>
    <!-- Navbar -->
    <?php
    include 'navbarhome.php'; ?>

    <div class="page-header-live">
        <div class="container mt-5 pt-4 pb-4 text-center">
            <h1 class="h3 fw-semibold mb-2">Live Matches</h1>
            <p class="mb-0" style="font-size: 0.9rem; opacity: 0.9;">Watch matches as they happen in real-time</p>
        </div>
    </div>

    <div class="container pb-4">
        <div class="match-results">
            <!-- Content will be dynamically loaded here -->
        </div>
    </div>

    <?php include 'footerhome.php' ?>

    <script>
        function fetchLiveScores() {
            $.ajax({
                url: 'fetch_live_scores.php',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    const matchResultsContainer = $('.match-results');
                    matchResultsContainer.empty();

                    // Check if data is an error response
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

                    // Ensure data is an array
                    const matches = Array.isArray(data) ? data : [data];

                    if (matches.length === 0) {
                        matchResultsContainer.html(`
                            <div class="text-center p-4">
                                <i class="fas fa-basketball-ball"></i>
                                <h3 class="h5 mb-2">No Live Matches</h3>
                                <p class="text-muted mb-0" style="font-size: 0.9rem;">There are no ongoing matches at the moment. Check back later!</p>
                            </div>
                        `);
                        return;
                    }

                    matches.forEach(match => {
                        const formattedDate = new Date(match.schedule_date).toLocaleDateString('en-US', {
                            weekday: 'long',
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });

                        const matchCard = `
                            <div class="match-result-card">
                                <div class="match-header">
                                    <div class="d-flex align-items-center">
                                        <span class="game-icon">
                                            <i class="fas fa-basketball-ball"></i>
                                        </span>
                                        <h5 class="match-title">${match.game_name}</h5>
                                    </div>
                                    <div class="match-date">
                                        <i class="far fa-calendar-alt"></i>
                                        ${formattedDate}
                                    </div>
                                </div>
                                <div class="match-body">
                                    <div class="row align-items-center">
                                        <div class="col-md-5">
                                            <div class="team-section">
                                                <div class="team-name">${match.teamA_name}</div>
                                                <div class="team-score">${match.teamA_score}</div>
                                                <div class="stats-box">
                                                    ${match.has_timeouts ? `
                                                        <div class="stat-item">
                                                            <span class="stat-label">Timeouts</span>
                                                            <span>${match.timeout_teamA || 0}/${match.timeout_per_team}</span>
                                                        </div>
                                                    ` : ''}
                                                    ${match.has_fouls ? `
                                                        <div class="stat-item">
                                                            <span class="stat-label">Fouls</span>
                                                            <span>${match.foul_teamA || 0}/${match.max_fouls_per_team}</span>
                                                        </div>
                                                    ` : ''}
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-2">
                                            <div class="vs-section">
                                                <div>VS</div>
                                                <div class="period-info">Period ${match.period}</div>
                                                ${match.time_remaining !== null ? `
                                                    <div class="timer-display">${match.time_formatted}</div>
                                                    <div class="timer-status ${match.timer_status}">${match.timer_status}</div>
                                                ` : ''}
                                            </div>
                                        </div>
                                        <div class="col-md-5">
                                            <div class="team-section">
                                                <div class="team-name">${match.teamB_name}</div>
                                                <div class="team-score">${match.teamB_score}</div>
                                                <div class="stats-box">
                                                    ${match.has_timeouts ? `
                                                        <div class="stat-item">
                                                            <span class="stat-label">Timeouts</span>
                                                            <span>${match.timeout_teamB || 0}/${match.timeout_per_team}</span>
                                                        </div>
                                                    ` : ''}
                                                    ${match.has_fouls ? `
                                                        <div class="stat-item">
                                                            <span class="stat-label">Fouls</span>
                                                            <span>${match.foul_teamB || 0}/${match.max_fouls_per_team}</span>
                                                        </div>
                                                    ` : ''}
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

        // Start fetching when document is ready
        $(document).ready(function() {
            fetchLiveScores();
            setInterval(fetchLiveScores, 5000);
        });
    </script>
</body>

</html>