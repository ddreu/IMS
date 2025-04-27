<?php
include_once 'connection/conn.php';
$conn = con();
include 'navbarhome.php';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Intramurals Rankings</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <link href="home.css" rel="stylesheet">

    <style>

    </style>
</head>

<body>
    <div class="page-header-rankings mt-5">
        <div class="container mt-5 pt-4 pb-4 text-center">
            <h1 class="h3 fw-semibold mb-2">Rankings & Leaderboards</h1>
            <p class="mb-0" style="font-size: 0.9rem; opacity: 0.9;">Track team performances and standings</p>
        </div>
    </div>

    <div class="container">
        <div class="filter-section">
            <div class="row">
                <div class="col-md-4">
                    <label for="gameFilter" class="form-label">Select Game</label>
                    <select id="gameFilter" class="form-select">
                        <option value="" selected>All Games</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="rankings-card">
            <div class="rankings-header">
                <h5>Current Standings</h5>
            </div>
            <div class="rankings-body">
                <div id="rankingsTable">
                    <div class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="text-muted mt-2 mb-0">Loading rankings...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'footerhome.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const gameFilter = document.getElementById("gameFilter");
            const rankingsTable = document.getElementById("rankingsTable");

            // Function to extract URL parameters
            function getUrlParams() {
                const params = new URLSearchParams(window.location.search);
                return {
                    school_id: params.get("school_id"),
                    department_id: params.get("department_id"),
                    grade_level: params.get("grade_level"),
                };
            }

            // Destructure and get school_id, department_id, and grade_level from URL
            const {
                school_id,
                department_id,
                grade_level
            } = getUrlParams();

            if (school_id) {
                // Populate the game dropdown
                fetch(`rankings/fetch_games.php?school_id=${school_id}`)
                    .then((response) => response.json())
                    .then((data) => {
                        gameFilter.innerHTML = `
                    <option value="" selected>All Games</option>
                    <option value="">Overall</option>`;
                        data.forEach((game) => {
                            const option = document.createElement("option");
                            option.value = game.game_id;
                            option.textContent = game.game_name;
                            gameFilter.appendChild(option);
                        });
                    })
                    .catch((error) => {
                        console.error("Error fetching games:", error);
                    });
            } else {
                console.error("Error: school_id is not defined in the URL.");
            }

            // Fetch and update the rankings table
            function updateRankings(schoolId, departmentId, gradeLevel = null, gameId = null) {
                // Show loading state
                rankingsTable.innerHTML = `
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-2 mb-0">Loading rankings...</p>
                </div>
            `;

                const queryParams = new URLSearchParams({
                    school_id: schoolId,
                    department_id: departmentId,
                    game_id: gameId || "",
                });
                if (gradeLevel) {
                    queryParams.append("grade_level", gradeLevel);
                }

                fetch(`rankings/fetch_rankings.php?${queryParams.toString()}`)
                    .then((response) => response.json())
                    .then((data) => {
                        if (data.error) {
                            rankingsTable.innerHTML = `
                            <div class="no-rankings">
                                <i class="fas fa-exclamation-circle"></i>
                                <h3 class="h5 mb-2">${data.error}</h3>
                                <p class="text-muted mb-0">Please try adjusting your filters.</p>
                            </div>`;
                            return;
                        }


                        // Check if the data contains rankings with all zeros
                        const isAllZero = data.every(team => (team.wins === 0 && team.losses === 0) || team.points === 0);
                        if (isAllZero) {
                            rankingsTable.innerHTML = `
    <div class="container">
        <p class="text-center text-muted">
            <i class="fas fa-trophy" style="color: #FFD700; margin-right: 10px;"></i>
            No data available yet, comeback later.
        </p>
    </div>
`;
                            return;
                        }

                        if (data.length === 0) {
                            rankingsTable.innerHTML = `
                            <div class="no-rankings">
                                <i class="fas fa-trophy"></i>
                                <h3 class="h5 mb-2">No Rankings Available</h3>
                                <p class="text-muted mb-0">No rankings found for the selected filters.</p>
                            </div>`;
                            return;
                        }

                        let tableHtml = `
                        <table id="rankTable" class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="text-center">Rank</th>
                                    <th>Team</th>`;

                        if (data.length > 0 && data[0].is_points) {
                            tableHtml += `
        <th class="text-end">Points</th>
        <th class="text-center"><i class="fas fa-medal" style="color: #FFD700;"></i> Gold</th>
        <th class="text-center"><i class="fas fa-medal" style="color: #C0C0C0;"></i> Silver</th>
        <th class="text-center"><i class="fas fa-medal" style="color: #CD7F32;"></i> Bronze</th>`;
                        } else {
                            tableHtml += `
                            <th class="text-end">Wins</th>
                            <th class="text-end">Losses</th>
                            <th class="text-end">Win Rate</th>`;
                        }

                        tableHtml += `</tr></thead><tbody>`;

                        data.forEach((team, index) => {
                            const rowClass = index === 0 ? 'table-gold' :
                                index === 1 ? 'table-silver' :
                                index === 2 ? 'table-bronze' : '';

                            let rankDisplay;
                            if (index === 0) {
                                rankDisplay = '<i class="fas fa-trophy"></i>';
                            } else if (index === 1) {
                                rankDisplay = '<i class="fas fa-medal"></i>';
                            } else if (index === 2) {
                                rankDisplay = '<i class="fas fa-medal"></i>';
                            } else {
                                rankDisplay = `<span class="text-muted">${index + 1}</span>`;
                            }

                            tableHtml += `
                            <tr class="${rowClass}">
                                <td class="text-center">${rankDisplay}</td>
                                <td>${team.team_name}</td>`;

                            if (team.is_points) {
                                tableHtml += `
        <td class="text-end fw-semibold">${team.wins}</td>
        <td class="text-center">${team.gold || 0}</td>
        <td class="text-center">${team.silver || 0}</td>
        <td class="text-center">${team.bronze || 0}</td>`;
                            } else {
                                const winRate = team.total_matches > 0 ?
                                    ((team.wins / team.total_matches) * 100).toFixed(1) :
                                    '0.0';
                                tableHtml += `
                                <td class="text-end">${team.wins}</td>
                                <td class="text-end">${team.losses}</td>
                                <td class="text-end fw-semibold">${winRate}%</td>`;
                            }

                            tableHtml += `</tr>`;
                        });

                        tableHtml += `</tbody></table>`;
                        rankingsTable.innerHTML = tableHtml;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        rankingsTable.innerHTML = `
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle mb-2" style="font-size: 1.5rem;"></i>
                            <h3 class="h5 mb-2">Error Loading Rankings</h3>
                            <p class="mb-0" style="font-size: 0.9rem;">Please try again later.</p>
                        </div>`;
                    });
            }

            // Event listener for game filter change
            gameFilter.addEventListener("change", function() {
                updateRankings(school_id, department_id, grade_level, gameFilter.value);
            });

            // Get default data from URL and fetch rankings
            if (school_id && department_id) {
                updateRankings(school_id, department_id, grade_level);
            }
        });
    </script>
</body>

</html>