<?php
session_start();

// Enable error reporting for debugging (disable in production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once '../connection/conn.php';
$conn = con();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php"); // Redirect to login if not authenticated
    exit();
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];
$user_department_id = $_SESSION['department_id'];
$user_department_name = $_SESSION['department_name'];
$school_id = $_SESSION['school_id'];
$user_game_name = $_SESSION['game_name'] ?? null;
$user_game_id = $_SESSION['game_id'] ?? null;

// Get URL parameters
$selected_department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : null;
$selected_grade_level = isset($_GET['grade_level']) ? $_GET['grade_level'] : null;
$selected_game_id = isset($_GET['game_id']) ? intval($_GET['game_id']) : null;

// Fetch departments
$departments = [];
if ($school_id) {
    $department_result = mysqli_query($conn, "SELECT id, department_name FROM departments WHERE school_id = $school_id");
    if ($department_result) {
        $departments = mysqli_fetch_all($department_result, MYSQLI_ASSOC);
    }
}

// Fetch games for the school
$games = [];
$games_query = "SELECT game_id, game_name FROM games WHERE school_id = ?";
$stmt = $conn->prepare($games_query);
$stmt->bind_param("i", $school_id);
$stmt->execute();
$games_result = $stmt->get_result();
while ($row = $games_result->fetch_assoc()) {
    $games[] = $row;
}
$stmt->close();

// Only fetch grade levels if department is selected
$grade_levels = [];
if ($selected_department_id) {
    $grade_query = "SELECT DISTINCT gsc.grade_level 
                   FROM grade_section_course gsc 
                   WHERE gsc.department_id = ? 
                   AND gsc.grade_level IS NOT NULL 
                   AND gsc.grade_level != ''
                   ORDER BY CAST(gsc.grade_level AS UNSIGNED)";

    $stmt = $conn->prepare($grade_query);
    if ($stmt) {
        $stmt->bind_param("i", $selected_department_id);
        $stmt->execute();
        $grade_result = $stmt->get_result();
        while ($row = $grade_result->fetch_assoc()) {
            $grade_levels[] = $row['grade_level'];
        }
        $stmt->close();
    }
}

// Function to fetch schedules filtered by the user's school
function getAllSchedules($conn, $school_id)
{
    // $query = "SELECT gs.schedule_id, m.match_id, m.match_type, m.status,
    //                  br.game_id, br.department_id, br.grade_level, 
    //                  ta.team_name AS teamA_name, tb.team_name AS teamB_name, 
    //                  gs.schedule_date, gs.schedule_time, gs.venue, 
    //                  d.department_name, g.game_name
    //           FROM schedules gs
    //           JOIN matches m ON gs.match_id = m.match_id
    //           JOIN brackets br ON m.bracket_id = br.bracket_id
    //           JOIN teams ta ON m.teamA_id = ta.team_id
    //           JOIN teams tb ON m.teamB_id = tb.team_id
    //           JOIN departments d ON br.department_id = d.id
    //           JOIN schools s ON d.school_id = s.school_id
    //           JOIN games g ON br.game_id = g.game_id
    //           WHERE s.school_id = ? 
    //           AND g.is_archived = 0 OR b.is_archived = 0
    //           ORDER BY gs.schedule_date, gs.schedule_time";

    $query = "SELECT gs.schedule_id, gs.is_archived, m.match_id, m.match_type, m.status,
       br.game_id, br.department_id, br.grade_level, 
       ta.team_name AS teamA_name, tb.team_name AS teamB_name, 
       gs.schedule_date, gs.schedule_time, gs.venue, 
       d.department_name, g.game_name
FROM schedules gs
JOIN matches m ON gs.match_id = m.match_id
JOIN brackets br ON m.bracket_id = br.bracket_id
JOIN teams ta ON m.teamA_id = ta.team_id
JOIN teams tb ON m.teamB_id = tb.team_id
JOIN departments d ON br.department_id = d.id
JOIN schools s ON d.school_id = s.school_id
JOIN games g ON br.game_id = g.game_id
WHERE s.school_id = ? 
  AND g.is_archived = 0 
  AND br.is_archived = 0 
  AND d.is_archived = 0 
  AND m.is_archived = 0 
  AND ta.is_archived = 0 
  AND tb.is_archived = 0
ORDER BY gs.schedule_date, gs.schedule_time";



    $stmt = $conn->prepare($query);
    if (!$stmt) {
        return [];
    }

    $stmt->bind_param("i", $school_id);
    $stmt->execute();
    $result = $stmt->get_result();

    $schedules = [];
    while ($row = $result->fetch_assoc()) {
        // Format the match type for display
        $matchTypeDisplay = '';
        switch ($row['match_type']) {
            case 'semifinal':
                $matchTypeDisplay = 'Semifinals';
                break;
            case 'final':
                $matchTypeDisplay = 'Finals';
                break;
            case 'third_place':
                $matchTypeDisplay = 'Battle for Third';
                break;
            default:
                $matchTypeDisplay = "Round 1";
        }

        // Convert time to 12-hour format
        $time = DateTime::createFromFormat('H:i:s', $row['schedule_time'], new DateTimeZone('Asia/Manila'));
        $formatted_time = $time ? $time->format('h:i A') : $row['schedule_time'];

        // Add formatted data to the schedule
        $schedules[] = [
            'id' => $row['schedule_id'],
            'title' => $matchTypeDisplay . ': ' . $row['teamA_name'] . ' vs ' . $row['teamB_name'],
            'start' => $row['schedule_date'] . 'T' . $row['schedule_time'],
            'match_identifier' => $row['match_id'],
            'match_type' => $row['match_type'],
            'department_name' => $row['department_name'],
            'grade_level' => $row['grade_level'],
            'teamA_name' => $row['teamA_name'],
            'teamB_name' => $row['teamB_name'],
            'venue' => $row['venue'],
            'formatted_time' => $formatted_time,
            'game_name' => $row['game_name'],
            'match_status' => $row['status'],
            'extendedProps' => [
                'department_id' => $row['department_id'],
                'game_id' => $row['game_id'],
                'game_name' => $row['game_name'],
                'venue' => $row['venue'],
                'time_12hr' => $formatted_time,
                'grade_level' => $row['grade_level']
            ]
        ];
    }

    $stmt->close();
    return $schedules;
}

// Fetch schedules only for the user's school
$schedules = getAllSchedules($conn, $school_id);

$schedules_json = json_encode($schedules, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

// Check for success and error messages from GET parameters
$successMessage = isset($_GET['success']) ? $_GET['success'] : null;
$errorMessage = isset($_GET['error']) ? $_GET['error'] : null;

// Fetch departments only if school_id is available
$departments = [];
if ($school_id) {
    $department_result = mysqli_query($conn, "SELECT id, department_name FROM departments WHERE school_id = $school_id");

    // If the query is successful, fetch departments
    if ($department_result) {
        $departments = mysqli_fetch_all($department_result, MYSQLI_ASSOC);
    }
}

include '../navbar/navbar.php';

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule</title>
    <style>
    </style>
    <link href='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.css' rel='stylesheet' />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../styles/dashboard.css">
    <link rel="stylesheet" href="../styles/calendar.css">
    <link rel="stylesheet" href="../styles/committee.css">

</head>

<body>


    <?php
    $current_page = 'schedule';

    if ($role == 'Committee') {
        include '../committee/csidebar.php';
    } else {
        include '../department_admin/sidebar.php'; // fallback for other roles
    }
    ?>


    <!-- Main Content -->

    <div class="main">

        <h1 style="text-align:center;">Game Schedules</h1>

        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card shadow mt-5">
                        <div class="card-header bg-white py-3">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h4 class="m-0 font-weight-bold text-primary">Schedules</h4>
                                </div>

                                <div class="col-auto">
                                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#scheduleModal">
                                        <i class="fas fa-plus"></i> Create Schedule
                                    </button>
                                </div>

                            </div>
                        </div>


                        <div class="card-body p-4">
                            <!-- Add filter controls -->
                            <div class="filter-container">
                                <div class="filter-row">
                                    <div class="filter-group">
                                        <label class="filter-label" for="filterDepartment">Department</label>
                                        <select class="filter-select" id="filterDepartment">
                                            <option value="">All Departments</option>
                                            <?php foreach ($departments as $dept): ?>
                                                <option value="<?= $dept['id'] ?>" <?= $selected_department_id == $dept['id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($dept['department_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="filter-group">
                                        <label class="filter-label" for="filterGradeLevel">Grade Level</label>
                                        <select class="filter-select" id="filterGradeLevel">
                                            <option value="">All Grade Levels</option>
                                            <?php if (!empty($grade_levels)): ?>
                                                <?php foreach ($grade_levels as $grade): ?>
                                                    <option value="<?= htmlspecialchars($grade) ?>"
                                                        <?= ($selected_grade_level == $grade) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($grade) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                    <div class="filter-group">
                                        <label class="filter-label" for="filterGame">Game</label>
                                        <select class="filter-select" id="filterGame">
                                            <option value="">All Games</option>
                                            <?php foreach ($games as $game): ?>
                                                <option value="<?= $game['game_id'] ?>" <?= $selected_game_id == $game['game_id'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($game['game_name']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="filter-buttons">
                                    <button id="applyFilters" class="btn btn-primary">
                                        <i class="fas fa-filter"></i> Apply Filters
                                    </button>
                                    <button id="resetFilters" class="btn btn-secondary">
                                        <i class="fas fa-undo"></i> Reset
                                    </button>
                                    <button id="exportSchedules" class="btn btn-success">
                                        <i class="fas fa-file-excel"></i> Export Schedules
                                    </button>
                                </div>
                            </div>
                            <div id="calendar"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>


    <?php include 'schedulemodals.php'; ?>


    <script src='https://cdn.jsdelivr.net/npm/fullcalendar@5.10.1/main.min.js'></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize the schedule modal
            const scheduleModal = new bootstrap.Modal(document.getElementById('scheduleModal'));
            const scheduleModalElement = document.getElementById('scheduleModal');
            const eventDetailsModal = document.getElementById('eventDetailsModal');
            const editScheduleModal = document.getElementById('editScheduleModal');

            // Add focus management for modals
            [eventDetailsModal, editScheduleModal, scheduleModalElement].forEach(modal => {
                if (modal) {
                    modal.addEventListener('hide.bs.modal', function() {
                        document.activeElement?.blur();
                    });
                }
            });

            // Listen for schedule modal show event
            scheduleModalElement.addEventListener('show.bs.modal', function() {
                handleGradeLevelVisibility();
                fetchGradeLevels();
                fetchMatches();
            });

            // Call handleGradeLevelVisibility on page load for users with fixed department
            if ('<?php echo $role; ?>' === 'Committee' || '<?php echo $role; ?>' === 'Department Admin') {
                handleGradeLevelVisibility();
            }

            // Function to fetch matches
            function fetchMatches() {
                const matchSelect = document.getElementById('match');
                const departmentId = document.querySelector('input[name="department_id"]')?.value || document.getElementById('department')?.value;
                const gameId = document.querySelector('input[name="game_id"]')?.value || document.getElementById('game')?.value;
                const gradeLevel = document.getElementById('grade_level')?.value;

                console.log('Fetching matches with:', {
                    departmentId,
                    gameId,
                    gradeLevel
                }); // Debug log

                if (!departmentId || !gameId) {
                    console.log('Department ID or Game ID not available:', {
                        departmentId,
                        gameId
                    });
                    return;
                }

                let url = `fetch_matches.php?department_id=${departmentId}&game_id=${gameId}`;
                if (gradeLevel) {
                    url += `&grade_level=${encodeURIComponent(gradeLevel)}`;
                }

                fetch(url)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(response => {
                        if (response.error) {
                            throw new Error(response.error);
                        }

                        // Log debug information
                        console.log('Debug info:', response.debug);

                        const matches = response.matches || [];
                        matchSelect.innerHTML = '<option value="">Select a Match</option>';

                        // Group matches by type
                        const matchTypes = {
                            'regular': 'Regular Rounds',
                            'semifinal': 'Semi Finals',
                            'final': 'Finals',
                            'third_place': 'Third Place'
                        };

                        // Create groups for each match type
                        Object.entries(matchTypes).forEach(([type, label]) => {
                            const typeMatches = matches.filter(m => m.match_type === type);
                            if (typeMatches.length > 0) {
                                const optgroup = document.createElement('optgroup');
                                optgroup.label = label;

                                typeMatches.forEach(match => {
                                    const option = document.createElement('option');
                                    option.value = match.match_id;
                                    option.textContent = match.display;
                                    option.setAttribute('data-team1-id', match.team1_id);
                                    option.setAttribute('data-team2-id', match.team2_id);
                                    optgroup.appendChild(option);
                                });

                                matchSelect.appendChild(optgroup);
                            }
                        });

                        if (matches.length === 0) {
                            matchSelect.innerHTML = '<option value="">No available matches</option>';
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching matches:', error);
                        matchSelect.innerHTML = '<option value="">Error loading matches</option>';
                    });
            }

            // Function to fetch grade levels
            function fetchGradeLevels() {
                const deptSelectElement = document.getElementById('department');
                const gradeLevelSelectElement = document.getElementById('grade_level');
                const gradeLevelContainer = document.getElementById('gradeLevelContainer');

                if (!deptSelectElement || !gradeLevelSelectElement || !gradeLevelContainer) {
                    console.log('Missing required elements for fetching grade levels');
                    return;
                }

                let departmentId = deptSelectElement.disabled ?
                    deptSelectElement.options[0].value :
                    deptSelectElement.value;

                if (!departmentId) {
                    console.log('No department ID available');
                    return;
                }

                console.log('Fetching grade levels for department:', departmentId);

                fetch(`../rankings/fetch_grade_levels.php?department_id=${encodeURIComponent(departmentId)}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Grade levels response:', data);

                        if (data.error) {
                            throw new Error(data.error);
                        }

                        // Clear existing options except the first one
                        while (gradeLevelSelectElement.options.length > 1) {
                            gradeLevelSelectElement.remove(1);
                        }

                        // Sort grade levels numerically
                        const sortedLevels = data.sort((a, b) => {
                            const numA = parseInt(a.replace(/\D/g, ''));
                            const numB = parseInt(b.replace(/\D/g, ''));
                            return numA - numB;
                        });

                        // Add new options
                        sortedLevels.forEach(level => {
                            const option = document.createElement('option');
                            option.value = level;
                            option.textContent = level;
                            gradeLevelSelectElement.appendChild(option);
                        });

                        // Show/hide container based on department
                        handleGradeLevelVisibility();
                        // Fetch matches after grade levels are loaded
                        fetchMatches();
                    })
                    .catch(error => {
                        console.error('Error fetching grade levels:', error);
                        gradeLevelSelectElement.innerHTML = '<option value="">Error loading grade levels</option>';
                    });
            }

            // Function to check if department is College and handle grade level visibility
            function handleGradeLevelVisibility() {
                // Handle modal grade level
                const deptSelectElement = document.getElementById('department');
                const gradeLevelSelectElement = document.getElementById('grade_level');
                const gradeLevelContainer = document.getElementById('gradeLevelContainer');

                // Handle filter grade level
                const filterDeptSelect = document.getElementById('filterDepartment');
                const filterGradeLevelContainer = document.getElementById('filterGradeLevelContainer');
                const filterGradeLevelSelect = document.getElementById('filterGradeLevel');

                // Handle modal grade level visibility
                if (deptSelectElement && gradeLevelContainer && gradeLevelSelectElement) {
                    let departmentName = '';
                    if (deptSelectElement.disabled) {
                        // For Committee/Department Admin users with fixed department
                        departmentName = deptSelectElement.options[0].text;
                    } else {
                        // For School Admin users
                        departmentName = deptSelectElement.options[deptSelectElement.selectedIndex]?.text || '';
                    }

                    if (departmentName.toLowerCase() === 'college') {
                        gradeLevelContainer.style.display = 'none';
                        gradeLevelSelectElement.removeAttribute('required');
                        gradeLevelSelectElement.value = ''; // Reset value when hidden
                    } else {
                        gradeLevelContainer.style.display = 'block';
                        if (deptSelectElement.value) {
                            gradeLevelSelectElement.setAttribute('required', 'required');
                        }
                    }
                }

                // Handle filter grade level visibility
                if (filterDeptSelect && filterGradeLevelContainer && filterGradeLevelSelect) {
                    let departmentName = '';
                    if (filterDeptSelect.disabled) {
                        departmentName = filterDeptSelect.options[0].text;
                    } else {
                        departmentName = filterDeptSelect.options[filterDeptSelect.selectedIndex]?.text || '';
                    }

                    if (departmentName.toLowerCase() === 'college') {
                        filterGradeLevelContainer.style.display = 'none';
                        filterGradeLevelSelect.value = '';
                    } else {
                        filterGradeLevelContainer.style.display = 'block';
                    }
                }
            }

            // Add event listeners for changes
            const deptSelect = document.getElementById('department');
            const filterDeptSelect = document.getElementById('filterDepartment');
            const gradeSelect = document.getElementById('grade_level');
            const gameSelect = document.getElementById('game');

            if (deptSelect) {
                deptSelect.addEventListener('change', function() {
                    handleGradeLevelVisibility();
                    fetchGradeLevels();
                });
            }

            if (filterDeptSelect) {
                filterDeptSelect.addEventListener('change', function() {
                    handleGradeLevelVisibility();
                    fetchGradeLevelsForDepartment(this.value);
                });
            }

            if (gradeSelect) {
                gradeSelect.addEventListener('change', fetchMatches);
            }

            if (gameSelect) {
                gameSelect.addEventListener('change', function() {
                    console.log('Game changed:', this.value);
                    fetchMatches();
                });
            }

            // Initial fetch for Committee and Department Admin
            if ('<?php echo $role; ?>' === 'Committee' || '<?php echo $role; ?>' === 'Department Admin') {
                handleGradeLevelVisibility();
                fetchMatches(); // Fetch matches immediately since department and/or game are fixed
            }

            // Function to get today's date in YYYY-MM-DD format
            function getTodayFormatted() {
                const today = new Date();
                const dd = String(today.getDate()).padStart(2, '0');
                const mm = String(today.getMonth() + 1).padStart(2, '0');
                const yyyy = today.getFullYear();
                return `${yyyy}-${mm}-${dd}`;
            }

            // Set minimum date for schedule_date input
            const scheduleDateInput = document.getElementById('schedule_date');
            const scheduleTimeInput = document.getElementById('schedule_time');

            if (scheduleDateInput) {
                // Comment out min attribute setting
                // scheduleDateInput.min = getTodayFormatted();

                // Comment out date validation
                // scheduleDateInput.addEventListener('change', function() {
                //     const selectedDate = new Date(this.value);
                //     const today = new Date();
                //     today.setHours(0, 0, 0, 0); // Reset time part for accurate date comparison

                //     if (selectedDate < today) {
                //         const dateError = document.getElementById('dateError');
                //         dateError.textContent = 'Please select a future date.';
                //         dateError.style.display = 'block';
                //     } else {
                //         const dateError = document.getElementById('dateError');
                //         dateError.textContent = '';
                //         dateError.style.display = 'none';
                //     }
                // });
            }

            // Function to validate date
            function validateDate(dateInput, errorElement) {
                // Comment out date validation
                // const selectedDate = new Date(dateInput.value);
                // const today = new Date();
                // today.setHours(0, 0, 0, 0);

                // if (selectedDate < today) {
                //     showTimedError(errorElement, 'Please select a future date.');
                //     dateInput.value = '';
                //     return false;
                // }
                return true;
            }

            // Function to validate time
            function validateTime(timeInput, errorElement) {
                const selectedTime = timeInput.value;
                const [hours] = selectedTime.split(':').map(Number);

                // Commenting out time validation
                // if (hours < 7 || hours >= 19) {
                //     showTimedError(errorElement, 'Please select a time between 7:00 AM and 7:00 PM.');
                //     timeInput.value = ''; // Clear the invalid time
                //     return false;
                // }
                return true;
            }

            // Set time restrictions (7 AM to 7 PM)
            if (scheduleTimeInput) {
                const timeError = document.getElementById('timeError');
                const dateError = document.getElementById('dateError');
                // Commenting out time restrictions
                // scheduleTimeInput.min = '07:00';
                // scheduleTimeInput.max = '19:00';

                // Set minimum date to today for create modal
                if (scheduleDateInput) {
                    // Comment out min attribute setting
                    // scheduleDateInput.min = getTodayFormatted();

                    // Comment out date validation
                    // scheduleDateInput.addEventListener('change', function() {
                    //     const selectedDate = new Date(this.value);
                    //     const today = new Date();
                    //     today.setHours(0, 0, 0, 0); // Reset time part for accurate date comparison

                    //     if (selectedDate < today) {
                    //         showTimedError(dateError, 'Please select a future date.');
                    //         this.value = ''; // Clear the invalid date
                    //         return;
                    //     }
                    // });
                }

                // Time validation
                scheduleTimeInput.addEventListener('change', function() {
                    const selectedTime = this.value;
                    const [hours] = selectedTime.split(':').map(Number);

                    // Commenting out time validation
                    // if (hours < 7 || hours >= 19) {
                    //     showTimedError(timeError, 'Please select a time between 7:00 AM and 7:00 PM.');
                    //     this.value = ''; // Clear the invalid time
                    // }
                });

                // Set a default time if needed
                if (!scheduleTimeInput.value) {
                    scheduleTimeInput.value = '07:00';
                }

                // Clear errors when modal is hidden
                scheduleModalElement.addEventListener('hidden.bs.modal', function() {
                    if (timeError) {
                        timeError.style.display = 'none';
                        timeError.textContent = '';
                    }
                    if (dateError) {
                        dateError.style.display = 'none';
                        dateError.textContent = '';
                    }
                });
            }

            // Apply same restrictions to edit modal
            const editScheduleDateInput = document.getElementById('edit_schedule_date');
            const editScheduleTimeInput = document.getElementById('edit_schedule_time');
            const editTimeError = document.getElementById('editTimeError');
            const editDateError = document.getElementById('editDateError');

            if (editScheduleDateInput && editScheduleTimeInput) {
                // Commenting out time restrictions
                // editScheduleTimeInput.min = '07:00';
                // editScheduleTimeInput.max = '19:00';

                // Set minimum date to today for edit modal
                // editScheduleDateInput.min = getTodayFormatted();

                // Date validation
                editScheduleDateInput.addEventListener('change', function() {
                    const selectedDate = new Date(this.value);
                    const today = new Date();
                    today.setHours(0, 0, 0, 0); // Reset time part for accurate date comparison

                    // Comment out date validation
                    // if (selectedDate < today) {
                    //     showTimedError(editDateError, 'Please select a future date.');
                    //     this.value = ''; // Clear the invalid date
                    // }
                });

                // Time validation
                editScheduleTimeInput.addEventListener('change', function() {
                    const selectedTime = this.value;
                    const [hours] = selectedTime.split(':').map(Number);

                    // Commenting out time validation
                    // if (hours < 7 || hours >= 19) {
                    //     showTimedError(editTimeError, 'Please select a time between 7:00 AM and 7:00 PM.');
                    //     this.value = ''; // Clear the invalid time
                    // }
                });
            }

            // Grade Level Handling
            const departmentSelect = document.getElementById('department');
            const gradeLevelContainer = document.getElementById('gradeLevelContainer');
            const gradeLevelSelect = document.getElementById('grade_level');

            if (departmentSelect) {
                departmentSelect.addEventListener('change', function() {
                    const selectedDepartment = this.options[this.selectedIndex].text;
                    gradeLevelSelect.innerHTML = '<option value="">Select Grade Level</option>';

                    if (selectedDepartment === 'Elementary') {
                        gradeLevelContainer.style.display = 'block';
                        for (let i = 1; i <= 6; i++) {
                            gradeLevelSelect.add(new Option(i, i));
                        }
                    } else if (selectedDepartment === 'JHS') {
                        gradeLevelContainer.style.display = 'block';
                        for (let i = 7; i <= 10; i++) {
                            gradeLevelSelect.add(new Option(i, i));
                        }
                    } else if (selectedDepartment === 'SHS') {
                        gradeLevelContainer.style.display = 'block';
                        for (let i = 11; i <= 12; i++) {
                            gradeLevelSelect.add(new Option(i, i));
                        }
                    } else {
                        gradeLevelContainer.style.display = 'none';
                    }
                });
            }

            // Calendar setup
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: window.innerWidth < 768 ? 'timeGridDay' : 'timeGridWeek',
                events: <?php echo json_encode($schedules); ?>,
                headerToolbar: {
                    left: 'prev,next today',
                    center: 'title',
                    right: 'dayGridMonth,timeGridWeek,timeGridDay'
                },
                // Commenting out time restrictions
                // slotMinTime: '07:00:00', // Start time at 7 AM
                // slotMaxTime: '20:00:00', // End time at 7 PM
                nowIndicator: true, // Optional: Shows the current time indicator
                eventDidMount: function(info) {
                    // Add Bootstrap cursor-pointer class
                    info.el.classList.add('cursor-pointer');
                },
                editable: false,
                eventClick: function(info) {
                    const event = info.event;
                    const eventModal = document.getElementById('eventDetailsModal');
                    const modalInstance = new bootstrap.Modal(eventModal);

                    // Format date as Month Day, Year
                    const options = {
                        year: 'numeric',
                        month: 'long',
                        day: 'numeric'
                    };
                    const formattedDate = event.start.toLocaleDateString('en-US', options);

                    // Set event details in modal
                    console.log(event);
                    document.getElementById('detail-department').textContent = event.extendedProps.department_name;
                    document.getElementById('detail-title').textContent = event.title;
                    document.getElementById('detail-game').textContent = event.extendedProps.game_name;
                    document.getElementById('detail-match-type').textContent = event.title.split(':')[0];
                    document.getElementById('detail-teams').textContent = event._def.extendedProps.teamA_name + ' vs ' + event._def.extendedProps.teamB_name;
                    document.getElementById('detail-date').textContent = formattedDate;
                    document.getElementById('detail-time').textContent = event._def.extendedProps.time_12hr;
                    document.getElementById('detail-venue').textContent = event._def.extendedProps.venue;

                    // Store event ID for edit/cancel operations
                    eventModal.setAttribute('data-event-id', event.id);

                    // Control button visibility based on user role and permissions
                    const editButton = document.getElementById('editButton');
                    const cancelButton = document.getElementById('cancelButton');
                    const userRole = '<?php echo $role; ?>';
                    const userDepartmentId = '<?php echo $user_department_id; ?>';
                    const userGameId = '<?php echo $user_game_id; ?>';
                    const matchStatus = event.extendedProps.match_status;

                    // Debug logging
                    console.log('User Role:', userRole);
                    console.log('User Department ID:', userDepartmentId);
                    console.log('Event Department ID:', event.extendedProps.department_id);
                    console.log('Match Status:', matchStatus);

                    if (editButton && cancelButton) {
                        // First check if match is finished
                        if (matchStatus === 'Finished') {
                            editButton.style.display = 'none';
                            cancelButton.style.display = 'none';
                        } else {
                            if (userRole === 'School Admin') {
                                // School Admin can see all buttons
                                editButton.style.display = 'inline-block';
                                cancelButton.style.display = 'inline-block';
                            } else if (userRole === 'Department Admin') {
                                // Convert IDs to strings for comparison
                                const eventDeptId = String(event.extendedProps.department_id);
                                const userDeptId = String(userDepartmentId);

                                // For Department Admin, show buttons only if department matches
                                const showButtons = eventDeptId === userDeptId;
                                console.log('Department Admin - Show Buttons:', showButtons);

                                editButton.style.display = showButtons ? 'inline-block' : 'none';
                                cancelButton.style.display = showButtons ? 'inline-block' : 'none';
                            } else if (userRole === 'Committee') {
                                // Convert IDs to strings for comparison
                                const eventDeptId = String(event.extendedProps.department_id);
                                const eventGameId = String(event.extendedProps.game_id);
                                const userDeptId = String(userDepartmentId);
                                const userGId = String(userGameId);

                                // For Committee, show buttons only if both department and game match
                                const showButtons = eventDeptId === userDeptId && eventGameId === userGId;
                                console.log('Committee - Show Buttons:', showButtons);

                                editButton.style.display = showButtons ? 'inline-block' : 'none';
                                cancelButton.style.display = showButtons ? 'inline-block' : 'none';
                            }
                        }
                    }

                    // Show the modal
                    modalInstance.show();

                    // Setup edit button click handler
                    if (editButton) {
                        editButton.onclick = function() {
                            // Remove focus before hiding modal
                            editButton.blur();
                            // Hide details modal
                            bootstrap.Modal.getInstance(eventModal).hide();

                            // Populate edit form
                            document.getElementById('edit_schedule_id').value = event.id;
                            document.getElementById('edit_match_id').value = event.extendedProps.match_id;

                            // Get the raw date string from the event and format it
                            const rawDate = event.startStr.split('T')[0];
                            document.getElementById('edit_schedule_date').value = rawDate;

                            // Get time from the event's raw time
                            const timeString = event.startStr.split('T')[1].substring(0, 5);
                            document.getElementById('edit_schedule_time').value = timeString;

                            document.getElementById('edit_venue').value = event.extendedProps.venue;

                            // Show edit modal
                            const editModal = new bootstrap.Modal(document.getElementById('editScheduleModal'));
                            editModal.show();
                        };
                    }

                    // Setup cancel button click handler
                    if (cancelButton) {
                        cancelButton.onclick = function() {
                            const scheduleId = document.getElementById('eventDetailsModal').getAttribute('data-event-id');

                            Swal.fire({
                                title: 'Are you sure?',
                                text: "Do you want to cancel this schedule?",
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#d33',
                                cancelButtonColor: '#3085d6',
                                confirmButtonText: 'Yes, cancel it!'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    fetch('cancel_schedule.php', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/json'
                                            },
                                            body: JSON.stringify({
                                                schedule_id: scheduleId
                                            })
                                        })
                                        .then(response => response.json())
                                        .then(data => {
                                            if (data.success) {
                                                Swal.fire({
                                                    title: 'Canceled!',
                                                    text: 'The schedule has been canceled successfully.',
                                                    icon: 'success',
                                                    confirmButtonText: 'OK'
                                                }).then(() => {
                                                    bootstrap.Modal.getInstance(document.getElementById('eventDetailsModal')).hide();
                                                    location.reload();
                                                });
                                            } else {
                                                Swal.fire({
                                                    title: 'Error!',
                                                    text: data.message || 'An error occurred while canceling the schedule.',
                                                    icon: 'error',
                                                    confirmButtonText: 'OK'
                                                });
                                            }
                                        })
                                        .catch(error => {
                                            Swal.fire({
                                                title: 'Error!',
                                                text: 'An unexpected error occurred: ' + error,
                                                icon: 'error',
                                                confirmButtonText: 'OK'
                                            });
                                        });
                                }
                            });
                        };
                    }
                }
            });
            calendar.render();

            // Function to update URL parameters
            function updateUrlParams(params) {
                const url = new URL(window.location.href);
                Object.keys(params).forEach(key => {
                    if (params[key]) {
                        url.searchParams.set(key, params[key]);
                    } else {
                        url.searchParams.delete(key);
                    }
                });
                window.history.pushState({}, '', url);
            }

            // Function to fetch grade levels for a department
            function fetchGradeLevelsForDepartment(departmentId) {
                console.log('Fetching grade levels for department:', departmentId);
                const gradeLevelSelect = document.getElementById('filterGradeLevel');

                if (!departmentId) {
                    gradeLevelSelect.innerHTML = '<option value="">All Grade Levels</option>';
                    return;
                }

                fetch(`../api/get_grade_levels.php?department_id=${departmentId}`)
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        console.log('Received grade levels:', data);
                        gradeLevelSelect.innerHTML = '<option value="">All Grade Levels</option>';

                        if (Array.isArray(data)) {
                            data.forEach(grade => {
                                if (grade) { // Only add non-empty grade levels
                                    const option = document.createElement('option');
                                    option.value = grade;
                                    option.textContent = grade;
                                    gradeLevelSelect.appendChild(option);
                                }
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error fetching grade levels:', error);
                        gradeLevelSelect.innerHTML = '<option value="">Error loading grade levels</option>';
                    });
            }

            // Add event listener for department change
            document.getElementById('filterDepartment').addEventListener('change', function() {
                const departmentId = this.value;
                console.log('Department changed to:', departmentId);
                fetchGradeLevelsForDepartment(departmentId);
            });

            // Initial load if department is selected
            const initialDepartment = document.getElementById('filterDepartment').value;
            if (initialDepartment) {
                fetchGradeLevelsForDepartment(initialDepartment);
            }

            // Add filter functionality
            document.getElementById('applyFilters').addEventListener('click', function() {
                const departmentFilter = document.getElementById('filterDepartment').value;
                const gradeLevelFilter = document.getElementById('filterGradeLevel').value;
                const gameFilter = document.getElementById('filterGame').value;

                // Update URL parameters
                updateUrlParams({
                    department_id: departmentFilter,
                    grade_level: gradeLevelFilter,
                    game_id: gameFilter
                });

                // Get all events
                const allEvents = <?php echo $schedules_json; ?>;

                // Filter events based on selected criteria
                const filteredEvents = allEvents.filter(event => {
                    const matchesDepartment = !departmentFilter || event.extendedProps.department_id.toString() === departmentFilter;
                    const matchesGradeLevel = !gradeLevelFilter || event.extendedProps.grade_level?.toString() === gradeLevelFilter;
                    const matchesGame = !gameFilter || event.extendedProps.game_id.toString() === gameFilter;
                    return matchesDepartment && matchesGradeLevel && matchesGame;
                });

                // Remove existing events and add filtered ones
                calendar.removeAllEvents();
                calendar.addEventSource(filteredEvents);
            });

            // Reset filters
            document.getElementById('resetFilters').addEventListener('click', function() {
                document.getElementById('filterDepartment').value = '';
                document.getElementById('filterGradeLevel').value = '';
                document.getElementById('filterGame').value = '';

                // Clear URL parameters
                updateUrlParams({
                    department_id: '',
                    grade_level: '',
                    game_id: ''
                });

                // Reset to show all events
                calendar.removeAllEvents();
                calendar.addEventSource(<?php echo $schedules_json; ?>);
            });

            // Initialize filters if URL parameters exist
            if (<?php echo $selected_department_id ? 'true' : 'false'; ?>) {
                fetchGradeLevelsForDepartment(<?php echo $selected_department_id; ?>);
            }
            if (<?php echo ($selected_department_id || $selected_grade_level || $selected_game_id) ? 'true' : 'false'; ?>) {
                document.getElementById('applyFilters').click();
            }

            // Handle edit schedule form submission
            const editScheduleForm = document.getElementById('editScheduleForm');
            if (editScheduleForm) {
                editScheduleForm.addEventListener('submit', function(event) {
                    event.preventDefault();
                    const formData = new FormData(editScheduleForm);

                    fetch('update_schedule.php', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: 'Schedule updated successfully'
                                }).then(() => {
                                    // Close the modal
                                    const editModal = bootstrap.Modal.getInstance(document.getElementById('editScheduleModal'));
                                    editModal.hide();
                                    // Refresh the page to update the calendar
                                    window.location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error!',
                                    text: data.message || 'Failed to update schedule'
                                });
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            Swal.fire({
                                icon: 'error',
                                title: 'Error!',
                                text: 'An error occurred while updating the schedule'
                            });
                        });
                });
            }

            // Handle form submission
            document.getElementById('scheduleForm').addEventListener('submit', function(e) {
                e.preventDefault();

                const formData = new FormData(this);

                fetch('create_schedule.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Close the modal
                            const modal = bootstrap.Modal.getInstance(document.getElementById('scheduleModal'));
                            modal.hide();

                            // Show success message with SweetAlert2
                            Swal.fire({
                                title: 'Success!',
                                text: data.message,
                                icon: 'success',
                                timer: 1500,
                                showConfirmButton: false
                            }).then(() => {
                                window.location.reload();
                            });
                        } else {
                            // Show error message with SweetAlert2
                            Swal.fire({
                                title: 'Error!',
                                text: data.message,
                                icon: 'error',
                                confirmButtonColor: '#d33'
                            });
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        // Show error message with SweetAlert2
                        Swal.fire({
                            title: 'Error!',
                            text: 'An unexpected error occurred. Please try again.',
                            icon: 'error',
                            confirmButtonColor: '#d33'
                        });
                    });
            });
        });
    </script>
    <script src="js/schedule.js"></script>
</body>

</html>

<div class="modal event-details-modal fade" id="eventDetailsModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Match Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="event-info">
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-trophy"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Match Type</div>
                            <div class="info-value" id="eventMatchType">-</div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-gamepad"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Game</div>
                            <div class="info-value" id="eventGame">-</div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Teams</div>
                            <div class="info-value" id="eventTeams">-</div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-calendar-alt"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Date & Time</div>
                            <div class="info-value" id="eventDateTime">-</div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-map-marker-alt"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Venue</div>
                            <div class="info-value" id="eventVenue">-</div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Department</div>
                            <div class="info-value" id="eventDepartment">-</div>
                        </div>
                    </div>
                    <div class="info-item">
                        <div class="info-icon">
                            <i class="fas fa-layer-group"></i>
                        </div>
                        <div class="info-content">
                            <div class="info-label">Grade Level</div>
                            <div class="info-value" id="eventGradeLevel">-</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <?php if ($role !== 'Committee'): ?>
                    <button type="button" class="btn btn-primary" id="editEventBtn">
                        <i class="fas fa-edit"></i> Edit Schedule
                    </button>
                    <button type="button" class="btn btn-danger" id="cancelEventBtn">
                        <i class="fas fa-times"></i> Cancel Match
                    </button>
                <?php endif; ?>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>