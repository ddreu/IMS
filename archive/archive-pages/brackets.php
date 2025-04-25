<?php
require_once '../connection/conn.php';
$conn = con();

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

// Retrieve session data
$school_id = $_SESSION['school_id'] ?? null;

if (!$school_id) {
    die('Error: Required session data is missing.');
}

// Get all departments for filter
$dept_query = "SELECT id, department_name FROM departments WHERE school_id = ?";
$stmt = $conn->prepare($dept_query);
$stmt->bind_param("i", $school_id);
$stmt->execute();
$departments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get all games for filter
$games_query = "SELECT game_id, game_name FROM games WHERE school_id = ?";
$stmt = $conn->prepare($games_query);
$stmt->bind_param("i", $school_id);
$stmt->execute();
$games = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get all unique grade levels for filter
$grade_query = "SELECT DISTINCT grade_level FROM brackets WHERE grade_level IS NOT NULL ORDER BY grade_level";
$grade_result = $conn->query($grade_query);
$grade_levels = [];
while ($row = $grade_result->fetch_assoc()) {
    if ($row['grade_level']) {
        $grade_levels[] = $row['grade_level'];
    }
}

?>

<!-- <!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Tournament Brackets</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"> -->
<!-- <link rel="stylesheet" href="../styles/committee.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script> -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="../styles/dashboard.css"> -->
<script src="https://d3js.org/d3.v7.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-bracket/0.11.1/jquery.bracket.min.js"></script>
<link href="https://cdnjs.cloudflare.com/ajax/libs/jquery-bracket/0.11.1/jquery.bracket.min.css" rel="stylesheet">

<style>
    /* Bracket Container Styles */
    #bracket-container {
        background: #fff;
        padding: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        margin: 20px auto;
        width: 100%;
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
    }

    /*
        .jQBracket {
            min-width: 1000px !important;
            min-height: 500px !important;
            padding: 40px 20px !important;
            font-size: 14px !important;
        }

        .jQBracket .tools {
            display: none !important;
        }

        .jQBracket .round {
            margin-right: 50px !important;
            min-width: 150px !important;
        }

        .jQBracket .round:last-child {
            margin-right: 20px !important;
        }

        .jQBracket .team {
            width: 200px !important;
            height: 35px !important;
            background-color: #ffffff !important;
            border: 1px solid #e0e0e0 !important;
            border-radius: 4px !important;
            margin: 2px 0 !important;
            display: flex !important;
            align-items: center !important;
        }

        .jQBracket .connector {
            border-color: #ccc !important;
            border-width: 2px !important;
        }

        .jQBracket .connector.filled {
            border-color: #666 !important;
        }

        .jQBracket .connector div.connector {
            border-width: 2px !important;
        }

        .jQBracket .score {
            min-width: 40px !important;
            padding: 3px 5px !important;
            background-color: #f8f9fa !important;
            border-left: 1px solid #e0e0e0 !important;
            text-align: center !important;
        }

        .jQBracket .label {
            width: calc(100% - 45px) !important;
            padding: 5px 10px !important;
            white-space: nowrap !important;
            overflow: hidden !important;
            text-overflow: ellipsis !important;
        }

        .bracket-wrapper {
            width: 100%;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            padding-bottom: 20px;
        }

        @media (max-width: 768px) {
            #bracket-container {
                padding: 10px 5px;
                margin: 10px 0;
            }

            .jQBracket {
                font-size: 12px !important;
                padding: 20px 10px !important;
            }

            .jQBracket .team {
                min-width: 180px !important;
                height: 30px !important;
            }

            .jQBracket .label {
                padding: 3px 8px !important;
            }

            .jQBracket .score {
                min-width: 35px !important;
                padding: 3px !important;
            }
        }

        .bracket-loading,
        .bracket-error {
            min-height: 200px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 20px;
        } */
    /* 
    #bracketModal .modal-dialog {
        max-width: 95vw;
        width: auto;
    }

    #bracketModal .modal-body {
        overflow-x: auto;
    }

    #viewBracketModal .modal-body {
        overflow-x: auto;
        max-height: 90vh;
    }

    #bracketModalContainer {
        min-height: 600px;
    }

    #bracketModalContainer .jQBracket {
        min-height: 100% !important;
        padding-bottom: 30px !important;
    } */

    #viewBracketModal .modal-dialog {
        max-width: 98vw;
        margin: 1rem auto;
    }

    #viewBracketModal .modal-content {
        height: 90vh;
        display: flex;
        flex-direction: column;
    }

    #viewBracketModal .modal-body {
        flex: 1 1 auto;
        overflow-x: auto;
        overflow-y: auto;
    }

    #bracketModalContainer {
        min-height: 800px;
        height: 100%;
    }
</style>





<div class="container mt-4">
    <div class="row mb-4">
        <div class="col">
            <h2>Tournament Brackets</h2>
        </div>
    </div>

    <!-- Filter Section -->
    <!-- <div class="filter-section">
            <div class="row">
                <div class="col-md-3">
                    <label for="departmentFilter" class="form-label">Department</label>
                    <select class="form-select" id="departmentFilter">
                        <option value="">All Departments</option>
                        <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>">
                                <?php echo htmlspecialchars($dept['department_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="gameFilter" class="form-label">Game</label>
                    <select class="form-select" id="gameFilter">
                        <option value="">All Games</option>
                        <?php foreach ($games as $game): ?>
                            <option value="<?php echo $game['game_id']; ?>">
                                <?php echo htmlspecialchars($game['game_name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="gradeLevelFilter" class="form-label">Grade Level</label>
                    <select class="form-select" id="gradeLevelFilter">
                        <option value="">All Grade Levels</option>
                        <?php foreach ($grade_levels as $grade): ?>
                            <option value="<?php echo htmlspecialchars($grade); ?>">
                                <?php echo htmlspecialchars($grade); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary w-100" onclick="applyFilters()">Apply Filters</button>
                </div>
            </div>
        </div> -->

    <!-- Filter Buttons -->
    <!-- <div class="d-flex justify-content-center mb-2 mt-3">
            <div class="btn-group w-auto portfolio-filter" role="group" aria-label="Portfolio Filter">
                <button type="button" class="btn btn-outline-primary active filter-btn" data-category="0">
                    Active
                </button>
                <button type="button" class="btn btn-outline-secondary filter-btn" data-category="1">
                    Archived
                </button>
            </div>
        </div> -->


    <!-- Existing Brackets Table -->
    <div class="row">
        <div class="col">

            <div class="table-responsive">
                <table class="table table-striped table-bordered">

                    <thead>
                        <tr>
                            <th>Game</th>
                            <th>Department</th>
                            <th>Grade Level</th>
                            <th>Total Teams</th>
                            <th>Status</th>
                            <th>Bracket Type</th>
                            <th>Created At</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="bracketsTableBody">
                        <!-- Table content will be loaded dynamically -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bracket Display Section -->
    <!-- <button id="printBracket">Print Bracket</button> -->

    <div id="bracket-container" class="mt-4"></div>
</div>

<div class="modal fade" id="viewBracketModal" tabindex="-1" aria-labelledby="viewBracketModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewBracketModalLabel">View Bracket</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="bracketModalContainer" class="bracket-wrapper"></div>
            </div>
        </div>
    </div>
</div>


<!-- <script>
    function loadBrackets(filters = {}) {
        $.ajax({
            url: 'fetch_admin_brackets.php',
            method: 'GET',
            data: filters,
            success: function(response) {
                if (response.success) {
                    const brackets = response.data;
                    const tbody = $('#bracketsTableBody');
                    tbody.empty();

                    if (brackets.length === 0) {
                        tbody.append('<tr><td colspan="7" class="text-center">No brackets found</td></tr>');
                        return;
                    }

                    brackets.forEach(bracket => {
                        const row = `
<tr data-category="${bracket.is_archived}">
            <td>${bracket.game_name}</td>
            <td>${bracket.department_name}</td>
            <td>${bracket.grade_level || 'N/A'}</td>
            <td>${bracket.total_teams}</td>
            <td>${bracket.status}</td>
            <td>${bracket.bracket_type}</td>
            <td>${new Date(bracket.created_at).toLocaleDateString()}</td>
            <td>
                <div class="dropdown">
                    <button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Actions
                    </button>
                    <ul class="dropdown-menu">
                        ${
                            bracket.is_archived == 0 
                                ? ( // If NOT archived — show view, download, archive, delete
                                    bracket.bracket_type === 'round_robin'
                                        ? `
                                            <li>
                                                <button class="dropdown-item" onclick="viewRoundRobin(${bracket.bracket_id})">
                                                    View Round Robin
                                                </button>
                                            </li>
                                            <li>
                                                <button class="dropdown-item" onclick="downloadBracket(${bracket.bracket_id}, 'round_robin')">
                                                    Download Round Robin
                                                </button>
                                            </li>
                                        `
                                        : bracket.bracket_type === 'double'
                                            ? `
                                                <li>
                                                    <button class="dropdown-item" onclick="viewDoubleElimination(${bracket.bracket_id})">
                                                        View Double Elimination
                                                    </button>
                                                </li>
                                            `
                                            : `
                                                <li>
                                                    <button class="dropdown-item" onclick="viewBracket(${bracket.bracket_id})">
                                                        View Bracket
                                                    </button>
                                                </li>
                                            `
                                )
                                : '' // If archived — don't show view and download buttons
                        }
                        <li>
                            <button type="button"
                                class="dropdown-item archive-btn"
                                data-id="${bracket.bracket_id}"
                                data-table="brackets"
                                data-operation="${bracket.is_archived == 1 ? 'unarchive' : 'archive'}"
                                style="padding: 4px 12px; line-height: 1.2;">
                                ${bracket.is_archived == 1 ? 'Unarchive' : 'Archive'}
                            </button>
                        </li>
                        <li>
                            <button class="dropdown-item text-danger" onclick="deleteBracket(${bracket.bracket_id})">
                                Delete
                            </button>
                        </li>
                    </ul>
                </div>
            </td>
        </tr>


                            `;
                        tbody.append(row);
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: response.message || 'Failed to load brackets'
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading brackets:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load brackets. Please try again.'
                });
            }
        });
    }

    function applyFilters() {
        const filters = {
            department_id: $('#departmentFilter').val(),
            game_id: $('#gameFilter').val(),
            grade_level: $('#gradeLevelFilter').val()
        };
        loadBrackets(filters);
    }

    function viewBracket(bracketId) {
        console.log('View Bracket clicked:', bracketId);

        $('#bracket-content').empty(); // Clear the content

        // Fetch bracket data
        fetch('fetch_bracket.php?bracket_id=' + bracketId)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Clear existing bracket
                    $('#bracket-container').empty();

                    // ENHANCED DEBUGGING
                    console.group('Bracket Data Validation');
                    console.log('Raw data from server:', JSON.parse(JSON.stringify(data)));

                    // Validate data structure
                    if (!data.matches || typeof data.matches !== 'object') {
                        console.error('Invalid matches structure:', data.matches);
                        $('#bracket-container').html(`
                        <div class="alert alert-warning">
                            No valid matches found in bracket data.
                        </div>
                    `);
                        return;
                    }

                    // Get all round numbers except 'third-place'
                    const roundNumbers = Object.keys(data.matches)
                        .filter(key => key !== 'third-place' && !isNaN(parseInt(key)))
                        .sort((a, b) => parseInt(a) - parseInt(b));

                    console.log('Sorted Round Numbers:', roundNumbers);

                    // Calculate the total number of teams in first round
                    const totalTeams = data.matches['1'] ? data.matches['1'].length * 2 : 0;

                    // Initialize teams array
                    const teams = [];
                    if (data.matches['1'] && data.matches['1'].length > 0) {
                        data.matches['1'].forEach(match => {
                            teams.push([
                                match.teamA_name || 'TBD',
                                match.teamB_name || 'TBD'
                            ]);
                        });
                    }

                    console.log('Teams array:', teams);

                    // Initialize results array
                    const results = [];
                    let matchesInRound = Math.floor(totalTeams / 2);

                    roundNumbers.forEach(roundNum => {
                        const roundResults = [];
                        const roundMatches = data.matches[roundNum] || [];

                        for (let i = 0; i < matchesInRound; i++) {
                            const match = roundMatches[i];
                            if (match) {
                                if (match.status === 'Finished' &&
                                    match.score_teamA !== null &&
                                    match.score_teamB !== null) {
                                    roundResults.push([
                                        parseInt(match.score_teamA) || 0,
                                        parseInt(match.score_teamB) || 0
                                    ]);
                                } else if (match.winning_team_id !== null) {
                                    roundResults.push(
                                        match.winning_team_id === match.teamA_id ? [1, 0] : [0, 1]
                                    );
                                } else if (match.teamA_id === -1 || match.teamB_id === -1) {
                                    roundResults.push(
                                        match.teamA_id === -1 ? [0, 1] : [1, 0]
                                    );
                                } else {
                                    roundResults.push([0, 0]);
                                }
                            } else {
                                roundResults.push([0, 0]);
                            }
                        }

                        results.push(roundResults);
                        matchesInRound = Math.floor(matchesInRound / 2);
                    });

                    console.log('Final results array:', results);

                    if (teams.length === 0) {
                        console.error('No teams available');
                        $('#bracket-container').html('<div class="alert alert-warning">No teams available for the bracket.</div>');
                        return;
                    }

                    const bracketData = {
                        teams: teams,
                        results: results
                    };

                    console.log('Final bracket data:', bracketData);

                    // // Initialize the bracket
                    // $('#bracket-container').bracket({
                    //     teamWidth: 150,
                    //     scoreWidth: 40,
                    //     matchMargin: 50,
                    //     roundMargin: 50,
                    //     init: bracketData,
                    //     save: function() {}, // Read-only mode
                    //     decorator: {
                    //         edit: function() {}, // Disable editing
                    //         render: function(container, data, score, state) {
                    //             container.empty();
                    //             if (data === null) {
                    //                 container.append("BYE");
                    //             } else if (data === "TBD") {
                    //                 container.append("TBD");
                    //             } else {
                    //                 container.append(data);
                    //                 if (score !== undefined && score !== null) {
                    //                     container.append($('<div>', {
                    //                         class: 'score',
                    //                         text: score
                    //                     }));
                    //                 }
                    //             }
                    //         }
                    //     }
                    // });


                    $('#bracket-container').bracket({
                        teamWidth: 150,
                        scoreWidth: 40,
                        matchMargin: 50,
                        roundMargin: 50,
                        init: bracketData,
                        decorator: {
                            edit: function() {}, // Disable editing
                            render: function(container, data, score, state) {
                                container.empty();
                                if (data === null) {
                                    container.append("BYE");
                                } else if (data === "TBD") {
                                    container.append("TBD");
                                } else {
                                    container.append(data);
                                }
                            }
                        }
                    });


                    // Create a container for buttons
                    if ($('#bracketButtonsContainer').length === 0) {
                        const buttonContainer = $('<div>', {
                            id: 'bracketButtonsContainer',
                            class: 'd-flex justify-content-between mt-3' // ✅ Flexbox to align buttons
                        });

                        // Add Export to PDF button
                        const exportButton = $('<button>', {
                            id: 'exportBracket',
                            class: 'btn btn-success me-2',
                            text: 'Export as PDF'
                        }).click(exportBracketToPDF);

                        // Add back button
                        const backButton = $('<button>', {
                            id: 'backToBrackets',
                            class: 'btn btn-secondary',
                            html: '<span><i class="fas fa-times"></i></span>'
                        }).click(function() {
                            showBracketList();
                        });

                        // Append buttons to container
                        buttonContainer.append(backButton, exportButton);
                        $('#bracket-container').before(buttonContainer);
                    }


                    // Hide the generate button
                    $('#generate-bracket').hide();

                } else {
                    console.error('Error loading bracket:', data.message);
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Failed to load bracket: ' + data.message
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load bracket. Please try again.'
                });
            });
    }


    // //PDF BRACKET
    // function exportBracketToPDF() {
    //     Swal.fire({
    //         title: 'Exporting...',
    //         text: 'Please wait while the bracket is being exported.',
    //         allowOutsideClick: false,
    //         didOpen: () => {
    //             Swal.showLoading(); // ✅ Show loading spinner
    //         }
    //     });

    //     const element = document.getElementById('bracket-container');
    //     html2canvas(element, {
    //         scale: 2
    //     }).then((canvas) => {
    //         const imgData = canvas.toDataURL('image/png');
    //         const {
    //             jsPDF
    //         } = window.jspdf;
    //         const pdf = new jsPDF('landscape');
    //         const imgWidth = 280;
    //         const imgHeight = (canvas.height * imgWidth) / canvas.width;
    //         pdf.addImage(imgData, 'PNG', 10, 10, imgWidth, imgHeight);
    //         pdf.save('bracket.pdf');

    //         // ✅ Close loading state and show success toast using SweetAlert
    //         Swal.close();
    //         Swal.fire({
    //             icon: 'success',
    //             title: 'Bracket exported successfully!',
    //             toast: true,
    //             position: 'top',
    //             showConfirmButton: false,
    //             timer: 3000
    //         });
    //     }).catch((error) => {
    //         // ✅ Close loading state and show error toast using SweetAlert
    //         Swal.close();
    //         Swal.fire({
    //             icon: 'error',
    //             title: 'Failed to export bracket.',
    //             text: 'Please try again.',
    //             toast: true,
    //             position: 'top',
    //             showConfirmButton: false,
    //             timer: 3000
    //         });
    //         console.error('Error exporting bracket:', error);
    //     });
    // }

    //PDF BRACKET
    async function exportBracketToPDF() {
        Swal.fire({
            title: 'Exporting...',
            text: 'Please wait while the bracket is being exported.',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        const element = document.getElementById('bracket-container');
        html2canvas(element, {
            scale: 2
        }).then(async (canvas) => {
            const imgData = canvas.toDataURL('image/png');
            const {
                jsPDF
            } = window.jspdf;
            const pdf = new jsPDF('landscape');
            const imgWidth = 280;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            pdf.addImage(imgData, 'PNG', 10, 10, imgWidth, imgHeight);

            Swal.close();

            try {
                // ✅ Use File System Access API for file picker
                const fileHandle = await window.showSaveFilePicker({
                    suggestedName: 'bracket.pdf',
                    types: [{
                        description: 'PDF Document',
                        accept: {
                            'application/pdf': ['.pdf']
                        }
                    }]
                });

                const writable = await fileHandle.createWritable();
                const pdfBlob = pdf.output('blob');
                await writable.write(pdfBlob);
                await writable.close();

                toastr.success('Bracket exported successfully!');
            } catch (error) {
                console.error('Error saving file:', error);
                toastr.error('Failed to export bracket. Please try again.');
            }
        }).catch((error) => {
            Swal.close();
            toastr.error('Failed to export bracket. Please try again.');
            console.error('Error exporting bracket:', error);
        });
    }




    // Load brackets when page loads
    $(document).ready(function() {
        loadBrackets();
        // Wrap bracket container with wrapper div
        $('#bracket-container').wrap('<div class="bracket-wrapper"></div>');
    });

    function loadRoundRobin(bracketId) {
        Swal.fire({
            title: 'Loading...',
            text: 'Fetching tournament schedule',
            allowOutsideClick: false,
            didOpen: () => {
                Swal.showLoading();
            }
        });

        fetch('fetch_round_robin.php?bracket_id=' + bracketId)
            .then(response => response.json())
            .then(data => {
                Swal.close();

                if (data.success) {
                    // Populate tournament info
                    $('#tournamentGame').text(data.tournament_info.game_name);
                    $('#tournamentDept').text(data.tournament_info.department_name);
                    $('#tournamentStatus').text(data.tournament_info.status);
                    $('#tournamentTeams').text(data.tournament_info.total_teams);

                    // Populate scoring rules inputs
                    if (data.scoring_rules) {
                        $('#viewWinPoints').val(data.scoring_rules.win_points);
                        $('#viewDrawPoints').val(data.scoring_rules.draw_points);
                        $('#viewLossPoints').val(data.scoring_rules.loss_points);
                        $('#viewBonusPoints').val(data.scoring_rules.bonus_points);
                    }

                    // Populate standings table
                    const standingsBody = $('#standingsTableBody');
                    standingsBody.empty();

                    let rank = 1;
                    data.standings.forEach((team) => {
                        const rankEmoji = rank === 1 ? '1️⃣' : rank === 2 ? '2️⃣' : rank === 3 ? '3️⃣' : rank;
                        const rowClass = rank === 1 ? 'table-success' :
                            rank === 2 ? 'table-info' :
                            rank === 3 ? 'table-warning' : '';

                        standingsBody.append(`
                        <tr class="${rowClass}">
                            <td>${rankEmoji}</td>
                            <td><strong>${team.team_name}</strong></td>
                            <td>${team.played}</td>
                            <td>${team.wins}</td>
                            <td>${team.draw}</td>
                            <td>${team.losses}</td>
                            <td>${team.bonus_points}</td>
                            <td><strong>${team.total_points}</strong></td>
                            <td>
                                <button class="btn btn-sm btn-success add-bonus" 
                                        data-team-id="${team.team_id}" 
                                        data-bracket-id="${bracketId}">
                                    <i class="fas fa-plus"></i> Add Bonus
                                </button>
                            </td>
                        </tr>
                    `);
                        rank++;
                    });

                    // Add bonus points handler
                    $('#standingsTableBody').off('click', '.add-bonus').on('click', '.add-bonus', async function() {
                        const teamId = $(this).data('team-id');
                        const bracketId = $(this).data('bracket-id');

                        const {
                            value: bonusPoints
                        } = await Swal.fire({
                            title: 'Add Bonus Points',
                            input: 'number',
                            inputLabel: 'Enter bonus points',
                            inputValue: 0,
                            showCancelButton: true,
                            inputValidator: (value) => {
                                if (!value || isNaN(value) || value < 0) {
                                    return 'Please enter a valid positive number';
                                }
                            }
                        });

                        if (bonusPoints) {
                            try {
                                const response = await fetch('save_round_robin_points.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        bracket_id: bracketId,
                                        team_id: teamId,
                                        bonus_points: parseInt(bonusPoints),
                                        action: 'add_bonus'
                                    })
                                });

                                const result = await response.json();

                                if (result.success) {
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success',
                                        text: 'Bonus points added successfully!'
                                    }).then(() => {
                                        loadRoundRobin(bracketId); // Refresh the standings
                                    });
                                } else {
                                    throw new Error(result.message);
                                }
                            } catch (error) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Failed to add bonus points: ' + error.message
                                });
                            }
                        }
                    });

                    // Populate matches table
                    const matchesBody = $('#matchesTableBody');
                    matchesBody.empty();

                    data.matches.forEach(match => {
                        const scores = match.status.toLowerCase() === 'finished' ?
                            `${match.score_teamA || '-'} - ${match.score_teamB || '-'}` :
                            '-';

                        matchesBody.append(`
                        <tr>
                            <td>Round ${match.round}</td>
                            <td>Match ${match.match_number}</td>
                            <td>${match.teamA_name || 'TBD'}</td>
                            <td>${scores}</td>
                            <td>${match.teamB_name || 'TBD'}</td>
                            <td>
                                <span class="badge bg-${match.status.toLowerCase() === 'pending' ? 'warning' : 'success'}">
                                    ${match.status}
                                </span>
                            </td>
                        </tr>
                    `);
                    });

                    // Save points handler
                    $('#savePoints').off('click').on('click', async function() {
                        try {
                            const response = await fetch('save_round_robin_points.php', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json'
                                },
                                body: JSON.stringify({
                                    bracket_id: bracketId,
                                    win_points: parseInt($('#viewWinPoints').val()) || 0,
                                    draw_points: parseInt($('#viewDrawPoints').val()) || 0,
                                    loss_points: parseInt($('#viewLossPoints').val()) || 0,
                                    bonus_points: parseInt($('#viewBonusPoints').val()) || 0
                                })
                            });

                            const result = await response.json();

                            if (result.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success',
                                    text: 'Scoring points updated successfully!'
                                });
                            } else {
                                throw new Error(result.message);
                            }
                        } catch (error) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to update scoring points: ' + error.message
                            });
                        }
                    });

                    // Show the modal
                    const modal = new bootstrap.Modal(document.getElementById('viewRoundRobinModal'));
                    modal.show();

                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: data.message || 'Failed to load tournament schedule'
                    });
                }
            })
            .catch(error => {
                Swal.close();
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to load tournament schedule. Error: ' + error.message
                });
            });
    }

    // Bind the event
    $(document).on('click', '.view-round-robin', function() {
        const bracketId = $(this).data('bracket-id');
        loadRoundRobin(bracketId);
    });

    function deleteBracket(bracketId) {
        Swal.fire({
            title: 'Are you sure?',
            text: "This will permanently delete this bracket and all its matches. This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3085d6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                $.ajax({
                    url: 'delete_bracket.php',
                    method: 'POST',
                    data: {
                        bracket_id: bracketId
                    },
                    success: function(response) {
                        if (response.success) {
                            Swal.fire({
                                title: 'Success!',
                                text: 'Bracket has been deleted successfully.',
                                icon: 'success'
                            }).then(() => {
                                // Reload the page to show the saved bracket
                                window.location.reload();
                            });
                        } else {
                            Swal.fire({
                                title: 'Error!',
                                text: response.message,
                                icon: 'error'
                            });
                        }
                    },
                    error: function() {
                        Swal.fire({
                            title: 'Error!',
                            text: 'Failed to delete bracket. Please try again.',
                            icon: 'error'
                        });
                    }
                });
            }
        });
    }
</script> -->