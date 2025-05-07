<?php
include_once '../../connection/conn.php';
$conn = con();
session_start();

// Return departments as JSON if school_id is passed via GET (AJAX call)
if (isset($_GET['school_id']) && !isset($_GET['department_id'])) {
    $school_id = intval($_GET['school_id']);
    $departments = [];

    $result = $conn->query("SELECT id, department_name FROM departments WHERE school_id = $school_id AND is_archived = 0");
    while ($row = $result->fetch_assoc()) {
        $departments[] = $row;
    }

    // Pick a random department if there are departments
    $random_department = null;
    if (count($departments) > 0) {
        $random_department = $departments[array_rand($departments)];
    }

    header('Content-Type: application/json');
    echo json_encode([
        'departments' => $departments,
        'random_department' => $random_department
    ]);
    exit;
}
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
    <link href="../style/livescores.css" rel="stylesheet">
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

        /* Mobile-first dropdown styling */
        .form-select.btn-like {
            max-width: 160px;
            /* Adjust to control width */
            background-color: white;
            color: #000;
            border-radius: 0.375rem;
            font-size: 0.9rem;
        }

        /* Optional: Increase spacing slightly between dropdowns on small screens */
        @media (max-width: 576px) {
            .form-select.btn-like {
                max-width: 100%;
            }
        }

        .navbar-bottom {
            position: fixed;
            bottom: 0;
            left: 20px;
            /* Add some margin to the left */
            right: 20px;
            /* Add some margin to the right */
            bottom: 20px;
            background-color: #fff;
            border-top: 1px solid #ddd;
            z-index: 10;
            border-radius: 20px;
            /* Add border radius for rounded corners */
            box-shadow: 0px 8px 16px rgba(0, 0, 0, 0.2);
            /* Stronger shadow */
            /* Add a soft shadow for floating effect */
        }

        .navbar-bottom .nav-link {
            text-align: center;
            padding: 12px;
            font-size: 16px;
        }

        .navbar-bottom .nav-link i {
            font-size: 20px;
            margin-bottom: 5px;
        }


        .navbar-bottom .nav-item {
            flex: 1;
        }

        @media (max-width: 800px) and (min-width: 400px) {
            .navbar-nav {
                display: flex;
                flex-direction: row;
                flex-wrap: nowrap;
                justify-content: space-between;
                width: 100vw;
            }

            .nav-item {
                text-align: center;
            }
        }

        .navbar-bottom .nav-link.active {
            color: rgb(180, 34, 8);
        }

        .navbar-bottom .nav-link:hover {
            color: #007bff;
        }
    </style>
</head>

<body>
    <!-- Navbar -->

    <div class="page-header-live">
        <div class="container mt-5 pt-4 pb-4 text-center">
            <h1 class="h3 fw-semibold mb-2">Live Matches</h1>
            <p class="mb-0" style="font-size: 0.9rem; opacity: 0.9;">Watch matches as they happen in real-time</p>
        </div>
        <div class="container mt-4">
            <div class="d-flex justify-content-center gap-2 flex-wrap">
                <!-- School Dropdown -->
                <select id="school_select" class="form-select btn-like">
                    <option value="">Select School</option>
                    <?php
                    $schools = $conn->query("SELECT * FROM schools WHERE is_archived = 0 AND school_id != 0");
                    while ($row = $schools->fetch_assoc()):
                    ?>
                        <option value="<?= $row['school_id'] ?>"><?= $row['school_name'] ?></option>
                    <?php endwhile; ?>
                </select>

                <!-- Department Dropdown -->
                <select id="departmentSelect" class="form-select btn-like" disabled>
                    <option value="">Select Department</option>
                </select>
            </div>
        </div>
    </div>

    <div class="container pb-4">
        <!-- <div class="container pb-4"> -->
        <div class="match-results">
        </div>
    </div>


    <nav class="navbar navbar-expand-lg navbar-light fixed-bottom navbar-bottom">
        <div class="container-fluid">
            <ul class="navbar-nav d-flex justify-content-between w-100">
                <!-- Live Scores Link -->
                <li class="nav-item">
                    <a class="nav-link active" href="livescores.php">
                        <i class="fas fa-basketball-ball"></i>
                        <br>Live Scores
                    </a>
                </li>

                <!-- Login Link -->
                <li class="nav-item">
                    <a class="nav-link" href="../login.php">
                        <i class="fas fa-sign-in-alt"></i>
                        <br>Login
                    </a>
                </li>
            </ul>
        </div>
    </nav>


    <script>
        $(document).ready(function() {
            // Handle school dropdown change
            $('#school_select').on('change', function() {
                const schoolId = $(this).val();

                if (schoolId) {
                    // Fetch departments via AJAX
                    $.get(window.location.pathname, {
                        school_id: schoolId
                    }, function(data) {
                        const $departmentSelect = $('#departmentSelect');
                        $departmentSelect.empty().append('<option value="">Select Department</option>');

                        // Populate departments
                        data.departments.forEach(dept => {
                            $departmentSelect.append(`<option value="${dept.id}">${dept.department_name}</option>`);
                        });

                        // Enable the department select dropdown
                        $departmentSelect.prop('disabled', false);

                        // Automatically select a random department
                        if (data.random_department) {
                            $departmentSelect.val(data.random_department.id);
                        }

                        // Update URL without reloading
                        const newUrl = new URL(window.location.href);
                        newUrl.searchParams.set('school_id', schoolId);
                        if (data.random_department) {
                            newUrl.searchParams.set('department_id', data.random_department.id);
                        } else {
                            newUrl.searchParams.delete('department_id');
                        }
                        window.history.pushState({}, '', newUrl.toString());
                    });
                } else {
                    // Reset department dropdown
                    $('#departmentSelect').empty().append('<option value="">Select Department</option>').prop('disabled', true);

                    // Remove parameters from URL without reloading
                    const newUrl = new URL(window.location.href);
                    newUrl.searchParams.delete('school_id');
                    newUrl.searchParams.delete('department_id');
                    window.history.pushState({}, '', newUrl.toString());
                }
            });

            // Handle department dropdown change
            $('#departmentSelect').on('change', function() {
                const departmentId = $(this).val();
                const newUrl = new URL(window.location.href);

                if (departmentId) {
                    newUrl.searchParams.set('department_id', departmentId);
                } else {
                    newUrl.searchParams.delete('department_id');
                }

                // Update URL without reloading
                window.history.pushState({}, '', newUrl.toString());
            });
        });

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
                url: '../../fetch_live_scores.php',
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
                    <!-- Team A Section -->
                    <div class="col-12 col-sm-5 col-md-4">
                        <div class="team-section">
                            <div class="team-name">${match.teamA.name}</div>
                            <div class="team-score">${match.teamA.score}</div>
                            <div class="stats-box">
                                ${renderAdditionalInfo(match.teamA, match.teamA.additional_info, match.source_table)}
                            </div>
                        </div>
                    </div>

                    <!-- VS Section (Center) -->
                    <div class="col-12 col-sm-2 col-md-1 text-center">
                        ${renderVsSection(match)}
                    </div>

                    <!-- Team B Section -->
                    <div class="col-12 col-sm-5 col-md-4">
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