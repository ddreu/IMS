<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Live Scoring</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <link rel="stylesheet" href="livescoring.css">
</head>

<body>
    <div class="overlay" onclick="window.closePlayerStats()"></div>
    <div class="main-container">
        <div class="main-content">
            <!-- Header Section -->
            <div class="header-section">
                <button type="button" class="btn btn-danger" id="end-match-button">
                    <i class="fas fa-stop-circle me-2"></i>End Match
                </button>
                <h2 class="text-center mb-4">Basketball Game</h2> <!-- Static Game Name -->
            </div>

            <!-- Live Stream Button -->
            <div class="position-fixed top-0 start-0 m-3">
                <button class="btn btn-success rounded-circle" type="button" data-bs-toggle="modal" data-bs-target="#liveStreamSettingsModal">
                    <i class="fas fa-video"></i>
                </button>
            </div>

            <!-- Toggle Button -->
            <div class="position-fixed top-0 end-0 m-3">
                <button class="btn btn-primary rounded-circle" type="button" data-bs-toggle="collapse" data-bs-target="#matchInfo" aria-expanded="false" aria-controls="matchInfo">
                    <i class="fas fa-info"></i>
                </button>
            </div>

            <!-- Match Info Section -->
            <div id="matchInfo" class="collapse match-info position-fixed" style="top: 50px; right: 20px; width: 250px;">
                <div class="card">
                    <div class="card-body">
                        <h5 class="text-center mb-3">Match Details</h5>
                        <div class="text-center mb-3">
                            <p class="mb-1">
                                <strong>Date:</strong> January 28, 2025 <!-- Static Date -->
                                <strong class="ms-3">Time:</strong> 5:00 PM <!-- Static Time -->
                            </p>
                            <p class="mb-1"><strong>Venue:</strong> Main Arena</p> <!-- Static Venue -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timer Section -->
            <div class="col-12 text-center mb-4">
                <div class="card border-0 shadow-sm" style="width: 100%; max-width: 600px; margin: auto;">
                    <div class="card-body">
                        <h5 class="card-title">Match Timer</h5>
                        <div class="timer-display mb-3" style="font-size: 3rem;">
                            <span id="minutes">00</span>:<span id="seconds">00</span>
                        </div>
                        <div class="timer-controls d-flex justify-content-center">
                            <button id="timer-start" class="btn btn-success mx-1">
                                <i class="fas fa-play"></i>
                            </button>
                            <button id="timer-pause" class="btn btn-warning mx-1">
                                <i class="fas fa-pause"></i>
                            </button>
                            <button id="timer-end" class="btn btn-danger mx-1">
                                <i class="fas fa-stop"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Period Controls -->
            <div class="period-section text-center mb-4">
                <div class="period-controls">
                    <button type="button" class="btn btn-period" id="decrement-period">
                        <i class="fas fa-minus"></i>
                    </button>
                    <div class="period-display">
                        <input type="text" id="periods" class="period-input" value="1" readonly>
                        <label class="period-label">Period</label>
                    </div>
                    <button type="button" class="btn btn-period" id="increment-period">
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            </div>

            <!-- Scoring Section -->
            <div class="col-12">
                <div class="row justify-content-center">
                    <!-- Team A Score Card -->
                    <div class="col-md-5 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="team-name">Team A</h5>
                            </div>
                            <div class="card-body">
                                <div class="score-section">
                                    <div class="score-display">
                                        <input type="number" id="teamA_score" class="score-input" value="0" min="0">
                                        <label class="score-label">Points</label>
                                    </div>
                                    <div class="score-controls">
                                        <button type="button" class="btn btn-score decrement-btn" data-target="teamA_score" data-increment="1">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <button type="button" class="btn btn-score increment-btn" data-target="teamA_score" data-increment="1">
                                            +1
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Team B Score Card -->
                    <div class="col-md-5 mb-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="team-name">Team B</h5>
                            </div>
                            <div class="card-body">
                                <div class="score-section">
                                    <div class="score-display">
                                        <input type="number" id="teamB_score" class="score-input" value="0" min="0">
                                        <label class="score-label">Points</label>
                                    </div>
                                    <div class="score-controls">
                                        <button type="button" class="btn btn-score decrement-btn" data-target="teamB_score" data-increment="1">
                                            <i class="fas fa-minus"></i>
                                        </button>
                                        <button type="button" class="btn btn-score increment-btn" data-target="teamB_score" data-increment="1">
                                            +1
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Stream Settings Modal -->
    <div class="modal fade" id="liveStreamSettingsModal" tabindex="-1" aria-labelledby="liveStreamSettingsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header border-bottom-0">
                    <h5 class="modal-title" id="liveStreamSettingsModalLabel">Start Live Stream</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Stream Options -->
                    <div class="list-group">
                        <div class="mb-3">
                            <button class="list-group-item list-group-item-action btn-lg text-center">
                                Use Computer Webcam
                            </button>
                        </div>
                        <div class="mb-3">
                            <button class="list-group-item list-group-item-action btn-lg text-center">
                                Use Phone Camera
                            </button>
                        </div>
                        <div class="mb-3">
                            <button class="list-group-item list-group-item-action btn-lg text-center">
                                Share Screen
                            </button>
                        </div>
                    </div>

                    <!-- Facebook Streaming Option -->
                    <div class="d-grid gap-2 mt-4">
                        <button class="btn btn-lg btn-outline-primary" onclick="startFacebookStream()">
                            Facebook
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <?php include 'player_stats.php'; ?>

    <!-- Bottom Player Stats Bar -->
    <div class="player-stats-bar d-none d-md-flex" onclick="window.togglePlayerStats()">
        <i class="fas fa-users me-2"></i>
        <span>Player Statistics</span>
        <i class="fas fa-chevron-up ms-2"></i>
    </div>

    <nav class="bottom-nav d-md-none">
        <div class="nav-item" onclick="window.toggleGameStats()">
            <i class="fas fa-chart-bar"></i>
            <span>Team Stats</span>
        </div>
        <div class="nav-item" onclick="window.togglePlayerStats()">
            <i class="fas fa-users"></i>
            <span>Player Stats</span>
        </div>
    </nav>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>