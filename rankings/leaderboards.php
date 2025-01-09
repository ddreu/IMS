<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

$role = $_SESSION['role'];
include '../navbar/navbar.php';

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Leaderboards</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <link rel="stylesheet" href="../styles/committee.css">
    <link rel="stylesheet" href="../styles/dashboard.css">
    <style>
        #rankTable tr.table-gold {
            background-color: #fff2b2 !important;
        }

        #rankTable tr.table-silver {
            background-color: #e8e8e8 !important;
        }

        #rankTable tr.table-bronze {
            background-color: #deb887 !important;
            color: #4a4a4a !important;
        }

        #rankTable tr.table-gold td,
        #rankTable tr.table-silver td,
        #rankTable tr.table-bronze td {
            background-color: transparent !important;
        }

        /* New styles for rank icons */
        #rankTable td:first-child i {
            font-size: 1.2rem;
            transition: transform 0.2s ease;
        }

        #rankTable tr:hover td:first-child i {
            transform: scale(1.2);
        }

        #rankTable tr.table-gold td:first-child i {
            text-shadow: 0 0 10px rgba(255, 215, 0, 0.5);
        }

        #rankTable tr.table-silver td:first-child i {
            text-shadow: 0 0 10px rgba(192, 192, 192, 0.5);
        }

        #rankTable tr.table-bronze td:first-child i {
            text-shadow: 0 0 10px rgba(205, 127, 50, 0.5);
        }
    </style>
</head>

<body>


    <?php
    $current_page = 'leaderboards';


    if ($role == 'Committee') {
        include '../committee/csidebar.php';
    } else {
        include '../department_admin/sidebar.php'; // fallback for other roles
    }
    ?>



    <div class="main">
        <div class="container">
            <h2 class="text-center mt-4">Leaderboards</h2>
            <div class="row mb-4">
                <div class="col-md-4">
                    <label for="departmentFilter" class="form-label">Department</label>
                    <select id="departmentFilter" class="form-select">
                        <option value="" selected>Select Department</option>
                        <!-- Department options populated dynamically -->
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="gradeLevelFilter" class="form-label">Grade Level</label>
                    <select id="gradeLevelFilter" class="form-select" disabled>
                        <option value="" selected>Select Grade Level</option>
                        <!-- Grade level options populated dynamically -->
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="gameFilter" class="form-label">Game</label>
                    <select id="gameFilter" class="form-select">
                        <option value="" selected>Select Game</option>
                        <option value="">Overall</option>
                        <!-- Game options populated dynamically -->
                    </select>
                </div>
            </div>

            <!-- Rankings Table -->
            <div class="container-fluid">
                <div class="row">
                    <div class="col-12">
                        <div class="card shadow mt-3">
                            <div class="row align-items-center">
                                <div class="col d-flex justify-content-between align-items-center">
                                    <h4 class="m-0 font-weight-bold text-primary p-3">Rankings</h4>
                                    <?php if ($role !== 'Committee'): ?>
                                        <button id="resetLeaderboardBtn" class="btn btn-danger me-3">Reset Leaderboard</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="rankingsTable">
                                    <p class="text-center text-muted">Please select a department to view rankings.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Function to load departments
            function loadDepartments() {
                fetch('fetch_departments.php')
                    .then(response => response.json())
                    .then(data => {
                        const select = document.getElementById('departmentFilter');
                        if (!select) return;

                        select.innerHTML = '<option value="">Select Department</option>';
                        data.forEach(dept => {
                            select.innerHTML += `<option value="${dept.id}">${dept.department_name}</option>`;
                        });
                    })
                    .catch(error => console.error('Error loading departments:', error));
            }

            // Function to load games
            function loadGames() {
                fetch('fetch_games.php')
                    .then(response => response.json())
                    .then(data => {
                        const select = document.getElementById('gameFilter');
                        if (!select) return;

                        // Keep the default options
                        select.innerHTML = `
                            <option value="" selected>Select Game</option>
                            <option value="">Overall</option>`;
                        // Add the games from the database
                        data.forEach(game => {
                            select.innerHTML += `<option value="${game.game_id}">${game.game_name}</option>`;
                        });
                    })
                    .catch(error => console.error('Error loading games:', error));
            }

            // Function to load grade levels based on department
            function loadGradeLevels(departmentId) {
                const select = document.getElementById('gradeLevelFilter');
                if (!select) return;

                const gradeLevelContainer = select.closest('.col-md-4');
                const departmentSelect = document.getElementById('departmentFilter');
                if (!departmentSelect) return;

                const selectedDepartment = departmentSelect.options[departmentSelect.selectedIndex]?.text;

                // Hide grade level dropdown for College department
                if (selectedDepartment === 'College') {
                    gradeLevelContainer.style.display = 'none';
                    select.disabled = true;
                    loadRankings(); // Reload rankings without grade level
                    return;
                }

                // Show grade level dropdown for other departments
                gradeLevelContainer.style.display = 'block';

                if (!departmentId) {
                    select.innerHTML = '<option value="">Select Grade Level</option>';
                    select.disabled = true;
                    return;
                }

                fetch(`fetch_grade_levels.php?department_id=${departmentId}`)
                    .then(response => response.json())
                    .then(data => {
                        select.innerHTML = '<option value="">Select Grade Level</option>';
                        data.forEach(grade => {
                            select.innerHTML += `<option value="${grade}">${grade}</option>`;
                        });
                        select.disabled = false;
                    })
                    .catch(error => console.error('Error loading grade levels:', error));
            }

            // Function to load rankings
            function loadRankings() {
                const department = document.getElementById('departmentFilter')?.value || '';
                const gradeLevel = document.getElementById('gradeLevelFilter')?.value || '';
                const game = document.getElementById('gameFilter')?.value || '';
                const rankingsDiv = document.getElementById('rankingsTable');

                if (!rankingsDiv) return;

                // If no department is selected, show a message
                if (!department) {
                    rankingsDiv.innerHTML = '<p class="text-center text-muted">Please select a department to view rankings.</p>';
                    return;
                }

                fetch(`fetch_rankings.php?department_id=${department}&grade_level=${gradeLevel}&game_id=${game}`)
                    .then(response => response.json())
                    .then(data => {
                        // Check for error message
                        if (data.error) {
                            rankingsDiv.innerHTML = `<p class="text-center text-muted">${data.error}</p>`;
                            return;
                        }

                        if (data.length === 0) {
                            rankingsDiv.innerHTML = '<p class="text-center text-muted">No rankings available for the selected filters.</p>';
                            return;
                        }

                        let tableHtml = `
                            <table id="rankTable" class="table table-striped">
                                <thead>
                                    <tr>
                                        <th class="text-center">Rank</th>
                                        <th>Team</th>`;

                        // Check if we're showing points or wins/losses
                        if (data.length > 0 && data[0].is_points) {
                            tableHtml += `
                                        <th>Points</th>`;
                        } else {
                            tableHtml += `
                                        <th>Wins</th>
                                        <th>Losses</th>
                                        <th>Win Rate</th>`;
                        }

                        tableHtml += `
                                    </tr>
                                </thead>
                                <tbody>`;

                        data.forEach((team, index) => {
                            const rowClass = index === 0 ? 'table-gold' :
                                index === 1 ? 'table-silver' :
                                index === 2 ? 'table-bronze' : '';

                            // Create rank display with icons
                            let rankDisplay;
                            if (index === 0) {
                                rankDisplay = '<i class="fas fa-trophy" style="color: #FFD700;"></i>';
                            } else if (index === 1) {
                                rankDisplay = '<i class="fas fa-medal" style="color: #C0C0C0;"></i>';
                            } else if (index === 2) {
                                rankDisplay = '<i class="fas fa-medal" style="color: #CD7F32;"></i>';
                            } else {
                                rankDisplay = index + 1;
                            }

                            tableHtml += `
                                <tr class="${rowClass}">
                                    <td class="text-center">${rankDisplay}</td>
                                    <td>${team.team_name}</td>`;

                            if (team.is_points) {
                                tableHtml += `
                                    <td>${team.wins}</td>`; // Using wins field for points
                            } else {
                                const winRate = team.total_matches > 0 ?
                                    ((team.wins / team.total_matches) * 100).toFixed(1) :
                                    '0.0';
                                tableHtml += `
                                    <td>${team.wins}</td>
                                    <td>${team.losses}</td>
                                    <td>${winRate}%</td>`;
                            }

                            tableHtml += `</tr>`;
                        });

                        tableHtml += `</tbody></table>`;
                        rankingsDiv.innerHTML = tableHtml;
                    })
                    .catch(error => {
                        console.error('Error loading rankings:', error);
                        document.getElementById('rankingsTable').innerHTML =
                            '<p class="text-center text-danger">Error loading rankings. Please try again.</p>';
                    });
            }

            // Add event listeners with null checks
            const departmentFilter = document.getElementById('departmentFilter');
            const gradeLevelFilter = document.getElementById('gradeLevelFilter');
            const gameFilter = document.getElementById('gameFilter');
            const resetButton = document.getElementById('resetLeaderboardBtn');

            if (departmentFilter) {
                departmentFilter.addEventListener('change', function() {
                    loadGradeLevels(this.value);
                    loadRankings();
                });
            }

            if (gradeLevelFilter) {
                gradeLevelFilter.addEventListener('change', loadRankings);
            }

            if (gameFilter) {
                gameFilter.addEventListener('change', loadRankings);
            }

            // Only add reset button listener if it exists (non-Committee roles)
            if (resetButton) {
                resetButton.addEventListener('click', function() {
                    // Show initial choice dialog
                    Swal.fire({
                        title: 'Reset Options',
                        html: `
                            <div class="d-grid gap-2">
                                <button type="button" class="btn btn-danger" id="resetAllBtn">
                                    Reset All Leaderboards
                                </button>
                                <button type="button" class="btn btn-primary" id="resetSelectiveBtn">
                                    Reset Specific Leaderboards
                                </button>
                            </div>
                        `,
                        showConfirmButton: false,
                        showCancelButton: true,
                        cancelButtonText: 'Cancel',
                        didOpen: () => {
                            // Reset All button handler
                            document.getElementById('resetAllBtn').addEventListener('click', function() {
                                Swal.fire({
                                    title: 'Confirm Complete Reset',
                                    text: 'This will reset ALL leaderboards, points, wins, andlosses for the entire school. This action cannot be undone!',
                                    icon: 'warning',
                                    showCancelButton: true,
                                    confirmButtonColor: '#d33',
                                    cancelButtonColor: '#3085d6',
                                    confirmButtonText: 'Yes, reset everything!',
                                    cancelButtonText: 'Cancel'
                                }).then((result) => {
                                    if (result.isConfirmed) {
                                        // Show loading state
                                        Swal.fire({
                                            title: 'Resetting All Data...',
                                            html: 'Please wait while we reset all leaderboards...',
                                            allowOutsideClick: false,
                                            didOpen: () => {
                                                Swal.showLoading();
                                            }
                                        });

                                        // Send reset all request
                                        fetch('reset_leaderboards.php', {
                                                method: 'POST',
                                                headers: {
                                                    'Content-Type': 'application/json'
                                                },
                                                body: JSON.stringify({
                                                    resetType: 'all'
                                                })
                                            })
                                            .then(response => response.json())
                                            .then(data => {
                                                if (data.status === 'success') {
                                                    Swal.fire({
                                                        icon: 'success',
                                                        title: 'Reset Successful',
                                                        text: data.message,
                                                        showConfirmButton: false,
                                                        timer: 1500
                                                    }).then(() => {
                                                        loadRankings();
                                                    });
                                                } else {
                                                    throw new Error(data.message);
                                                }
                                            })
                                            .catch(error => {
                                                Swal.fire({
                                                    icon: 'error',
                                                    title: 'Reset Failed',
                                                    text: error.message || 'An error occurred while resetting the leaderboards.',
                                                    confirmButtonColor: '#d33'
                                                });
                                            });
                                    }
                                });
                            });

                            // Selective Reset button handler
                            document.getElementById('resetSelectiveBtn').addEventListener('click', function() {
                                fetch('fetch_departments.php')
                                    .then(response => response.json())
                                    .then(departments => {
                                        Swal.fire({
                                            title: 'Reset Leaderboard Settings',
                                            html: `
                                                <form id="resetForm" class="text-start">
                                                    <div class="mb-3">
                                                        <label for="department" class="form-label">Department</label>
                                                        <select class="form-select" id="department" required>
                                                            <option value="">Select Department</option>
                                                            ${departments.map(dept => 
                                                                `<option value="${dept.id}">${dept.department_name}</option>`
                                                            ).join('')}
                                                        </select>
                                                    </div>
                                                    <div class="mb-3" id="gradeLevelContainer">
                                                        <label for="gradeLevel" class="form-label">Grade Level</label>
                                                        <select class="form-select" id="gradeLevel" required>
                                                            <option value="">Select Grade Level</option>
                                                        </select>
                                                    </div>
                                                    <div class="mb-3" id="gameContainer">
                                                        <label for="game" class="form-label">Game</label>
                                                        <select class="form-select" id="game" required>
                                                            <option value="">Select Game</option>
                                                        </select>
                                                    </div>
                                                </form>`,
                                            showCancelButton: true,
                                            confirmButtonText: 'Next',
                                            cancelButtonText: 'Cancel',
                                            didOpen: () => {
                                                const departmentSelect = document.getElementById('department');
                                                const gradeLevelContainer = document.getElementById('gradeLevelContainer');
                                                const gradeLevelSelect = document.getElementById('gradeLevel');
                                                const gameSelect = document.getElementById('game');

                                                departmentSelect.addEventListener('change', function() {
                                                    const departmentId = this.value;
                                                    const selectedDepartment = this.options[this.selectedIndex].text;

                                                    // Handle College department
                                                    if (selectedDepartment === 'College') {
                                                        gradeLevelContainer.style.display = 'none';
                                                        gradeLevelSelect.required = false;
                                                        gradeLevelSelect.value = '';
                                                    } else {
                                                        gradeLevelContainer.style.display = 'block';
                                                        gradeLevelSelect.required = true;
                                                    }

                                                    // Clear existing options
                                                    gradeLevelSelect.innerHTML = '<option value="">Select Grade Level</option>';
                                                    gameSelect.innerHTML = '<option value="">Select Game</option>';

                                                    if (departmentId) {
                                                        // Only fetch grade levels if not College
                                                        if (selectedDepartment !== 'College') {
                                                            fetch(`fetch_grade_levels.php?department_id=${departmentId}`)
                                                                .then(response => response.json())
                                                                .then(data => {
                                                                    data.forEach(grade => {
                                                                        gradeLevelSelect.add(new Option(grade, grade));
                                                                    });
                                                                });
                                                        }

                                                        // Fetch games
                                                        fetch(`fetch_games.php?department_id=${departmentId}`)
                                                            .then(response => response.json())
                                                            .then(data => {
                                                                data.forEach(game => {
                                                                    gameSelect.add(new Option(game.game_name, game.game_id));
                                                                });
                                                            });
                                                    }
                                                });
                                            },
                                            preConfirm: () => {
                                                const form = document.getElementById('resetForm');
                                                const department = document.getElementById('department');
                                                const gradeLevel = document.getElementById('gradeLevel');
                                                const game = document.getElementById('game');

                                                if (!department.value) {
                                                    Swal.showValidationMessage('Please select a department');
                                                    return false;
                                                }

                                                // Validate grade level only if not College and not All
                                                if (department.options[department.selectedIndex].text !== 'College' && !gradeLevel.value) {
                                                    Swal.showValidationMessage('Please select a grade level');
                                                    return false;
                                                }

                                                if (!game.value) {
                                                    Swal.showValidationMessage('Please select a game');
                                                    return false;
                                                }

                                                return {
                                                    department: department.value,
                                                    gradeLevel: gradeLevel.value,
                                                    game: game.value
                                                };
                                            }
                                        }).then((result) => {
                                            if (result.isConfirmed) {
                                                // Show final confirmation
                                                Swal.fire({
                                                    title: 'Confirm Reset',
                                                    text: 'This will reset all Points, Wins, and Losses for the selected items. This action cannot be undone!',
                                                    icon: 'warning',
                                                    showCancelButton: true,
                                                    confirmButtonColor: '#d33',
                                                    cancelButtonColor: '#3085d6',
                                                    confirmButtonText: 'Yes, reset it!',
                                                    cancelButtonText: 'Cancel'
                                                }).then((confirmResult) => {
                                                    if (confirmResult.isConfirmed) {
                                                        // Show loading state
                                                        Swal.fire({
                                                            title: 'Resetting...',
                                                            html: 'Please wait while we reset the selected items...',
                                                            allowOutsideClick: false,
                                                            didOpen: () => {
                                                                Swal.showLoading();
                                                            }
                                                        });

                                                        // Make the reset request
                                                        fetch('reset_leaderboards.php', {
                                                                method: 'POST',
                                                                headers: {
                                                                    'Content-Type': 'application/json'
                                                                },
                                                                body: JSON.stringify(result.value)
                                                            })
                                                            .then(response => response.json())
                                                            .then(data => {
                                                                if (data.status === 'success') {
                                                                    Swal.fire({
                                                                        icon: 'success',
                                                                        title: 'Reset Successful',
                                                                        text: data.message,
                                                                        showConfirmButton: false,
                                                                        timer: 1500
                                                                    }).then(() => {
                                                                        loadRankings();
                                                                    });
                                                                } else {
                                                                    throw new Error(data.message);
                                                                }
                                                            })
                                                            .catch(error => {
                                                                Swal.fire({
                                                                    icon: 'error',
                                                                    title: 'Reset Failed',
                                                                    text: error.message || 'An error occurred while resetting the leaderboard.',
                                                                    confirmButtonColor: '#d33'
                                                                });
                                                            });
                                                    }
                                                });
                                            }
                                        });
                                    })
                                    .catch(error => {
                                        console.error('Error loading departments:', error);
                                        Swal.fire({
                                            icon: 'error',
                                            title: 'Error',
                                            text: 'Failed to load departments. Please try again.',
                                        });
                                    });
                            });
                        }
                    });
                });
            }

            // Initial load
            loadDepartments();
            loadGames();
        });
    </script>
</body>

</html>