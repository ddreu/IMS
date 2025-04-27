<?php
session_start(); // Start the session at the beginning
include_once '../connection/conn.php';
include '../user_logs/logger.php'; // Include the logger file
$conn = con();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php'); // Adjust the path if needed
    exit();
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$sql = "SELECT role, school_id FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);

if ($stmt === false) {
    die('mysqli_prepare() failed: ' . htmlspecialchars(mysqli_error($conn)));
}

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result->num_rows > 0) {
    $user = mysqli_fetch_assoc($result);

    // Check if the logged-in user is a school admin
    if ($user['role'] === 'Committee') {
        header('Location: 404.php'); // Redirect if the role is not school admin
        exit();
    }

    // Retrieve the logged-in user's school ID
    $school_id = $user['school_id'];
} else {
    // If the user is not found in the database
    header('Location: ../login.php'); // Adjust the path if needed
    exit();
}

// Handle form submission for adding a new game
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_game'])) {
    // Get form data
    $game_name = mysqli_real_escape_string($conn, $_POST['game_name']);
    $number_of_players = (int)$_POST['number_of_players'];
    $category = mysqli_real_escape_string($conn, $_POST['category']);
    $environment = mysqli_real_escape_string($conn, $_POST['environment']);

    // Check if the game name already exists in the user's school
    $check_sql = "SELECT * FROM games WHERE game_name = ? AND school_id = ?";
    $stmt_check = mysqli_prepare($conn, $check_sql);
    mysqli_stmt_bind_param($stmt_check, "si", $game_name, $school_id);
    mysqli_stmt_execute($stmt_check);
    $check_result = mysqli_stmt_get_result($stmt_check);

    if (mysqli_num_rows($check_result) > 0) {
        $_SESSION['error_message'] = "The game is already registered in your school!"; // Set error message
    } else {
        // Insert the new game into the database with the user's school ID
        $insert_sql = "INSERT INTO games (game_name, number_of_players, category, environment, school_id) VALUES (?, ?, ?, ?, ?)";
        $stmt_insert = mysqli_prepare($conn, $insert_sql);
        mysqli_stmt_bind_param($stmt_insert, "sissi", $game_name, $number_of_players, $category, $environment, $school_id);

        if (mysqli_stmt_execute($stmt_insert)) {
            // Prepare a detailed log description
            $log_description = "Added a new game: Name = '{$game_name}', Number of Players = {$number_of_players}, Category = '{$category}', Environment = '{$environment}'.";

            // Call the logUserAction function to log the action
            logUserAction($conn, $user_id, "games", "CREATE", mysqli_insert_id($conn), $log_description);

            $_SESSION['success_message'] = "Game added successfully!"; // Success message
        } else {
            $_SESSION['error_message'] = "Error adding game: " . mysqli_error($conn); // Error message on insert failure
        }
    }

    // Redirect back to the game list to display messages
    header('Location: games.php'); // Redirect to the games page
    exit();
}

// Fetch games from the database for the logged-in user's school and based on the filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Base SQL query for fetching games
$game_sql = "SELECT * FROM games WHERE school_id = ?";
$params = [$school_id];

if ($filter == 'indoor') {
    $game_sql .= " AND environment = 'Indoor'";
} elseif ($filter == 'outdoor') {
    $game_sql .= " AND environment = 'Outdoor'";
}

if (!empty($search)) {
    $game_sql .= " AND game_name LIKE ?"; // Prepare for search
    $params[] = "%" . $search . "%"; // Add search term
}

// Add ORDER BY clause to show most recent games first
$game_sql .= " ORDER BY game_id DESC";

// Prepare the statement
$stmt_games = mysqli_prepare($conn, $game_sql);
if ($stmt_games === false) {
    die('mysqli_prepare() failed: ' . htmlspecialchars(mysqli_error($conn)));
}

// Bind parameters based on the number of parameters
if (count($params) == 1) {
    mysqli_stmt_bind_param($stmt_games, "i", $params[0]);
} else {
    mysqli_stmt_bind_param($stmt_games, "is", $params[0], $params[1]);
}

mysqli_stmt_execute($stmt_games);
$game_result = mysqli_stmt_get_result($stmt_games);

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
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>List of Games</title>

    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Font Awesome CDN for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../styles/dashboard.css">
    <style>
        /* Base styles */
        .card.box {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }

        .table thead th {
            background-color: #2c3e50;
            color: white;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.9rem;
        }

        /* Filter section styles */
        .filter-section {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }

        .search-box {
            position: relative;
        }

        .search-box .form-control {
            padding-left: 2.5rem;
        }

        .search-box i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }

        /* Mobile Responsive Styles */
        @media (max-width: 768px) {
            .wrapper {
                padding: 0;
            }

            #content {
                margin-left: 0;
                padding: 15px;
            }

            .container {
                padding: 0;
            }

            /* Header Section */
            .d-flex.justify-content-between {
                flex-direction: column;
                gap: 1rem;
            }

            h2 {
                font-size: 1.5rem;
                margin-bottom: 0.5rem;
            }

            .btn-primary {
                width: 100%;
            }

            /* Filter Section */
            .row.mb-4 {
                margin: 0;
            }

            .btn-group {
                width: 100%;
                margin-bottom: 1rem;
            }

            .btn-group .btn {
                flex: 1;
                white-space: nowrap;
                padding: 0.5rem;
            }

            .search-box {
                width: 100%;
            }

            /* Table Responsive */
            .card.box {
                border-radius: 0;
                margin: 0 -15px;
            }

            .card-body {
                padding: 0;
            }

            .table-responsive {
                margin: 0;
            }

            .table {
                margin: 0;
            }

            .table thead {
                display: none;
            }

            .table tbody tr {
                display: block;
                margin-bottom: 1rem;
                background: #fff;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
                padding: 1rem;
            }

            .table tbody td {
                display: flex;
                text-align: left;
                padding: 0.5rem 0;
                border: none;
                align-items: center;
            }

            .table tbody td:before {
                content: attr(data-label);
                font-weight: 600;
                width: 140px;
                min-width: 140px;
                color: #2c3e50;
            }

            /* Action buttons in table */
            .table .btn-group {
                width: auto;
                margin: 0;
            }

            .table .btn {
                padding: 0.4rem 0.8rem;
            }

            /* Modal Adjustments */
            .modal-dialog {
                margin: 0.5rem;
            }

            .modal-content {
                border-radius: 8px;
            }

            .modal-body {
                padding: 1rem;
            }

            /* Empty State */
            .text-center.py-4 {
                padding: 2rem 1rem !important;
            }

            .text-center.py-4 i {
                font-size: 2.5rem;
                margin-bottom: 1rem;
            }
        }

        /* Small Mobile Adjustments */
        @media (max-width: 576px) {
            #content {
                padding: 10px;
            }

            .table tbody td:before {
                width: 120px;
                min-width: 120px;
            }

            .btn-sm {
                padding: 0.25rem 0.5rem;
                font-size: 0.875rem;
            }
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php $current_page = 'games';
        include '../department_admin/sidebar.php'; ?>
        <div id="content">
            <?php
            // Display success message
            if (isset($_SESSION['success_message'])): ?>
                <script>
                    Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: <?php echo json_encode($_SESSION['success_message']); ?>,
                        showConfirmButton: true
                    });
                </script>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>

            <?php
            // Display error message
            if (isset($_SESSION['error_message'])): ?>
                <script>
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: <?php echo json_encode($_SESSION['error_message']); ?>,
                        showConfirmButton: true
                    });
                </script>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <div class="container mt-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>List of Games</h2>
                    <?php if ($user['role'] === 'School Admin') : ?>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGameModal">
                            <i class="fas fa-plus"></i> Add Game
                        </button>
                    <?php endif; ?>
                </div>

                <div class="filter-section">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="btn-group w-100" role="group">
                                <a href="?filter=all<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"
                                    class="btn btn-outline-primary <?= $filter === 'all' ? 'active' : '' ?>">
                                    All Games
                                </a>
                                <a href="?filter=indoor<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"
                                    class="btn btn-outline-primary <?= $filter === 'indoor' ? 'active' : '' ?>">
                                    Indoor
                                </a>
                                <a href="?filter=outdoor<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"
                                    class="btn btn-outline-primary <?= $filter === 'outdoor' ? 'active' : '' ?>">
                                    Outdoor
                                </a>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <form method="GET" action="games.php" class="d-flex gap-2">
                                <input type="text" name="search" class="form-control" placeholder="Search games..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="card box">
                    <div class="card-body">
                        <!-- Filter Buttons -->
                        <div class="btn-group portfolio-filter mb-3 mt-0" role="group" aria-label="Portfolio Filter">
                            <button type="button" class="btn btn-outline-primary active filter-btn" data-category="0">
                                Active
                            </button>
                            <button type="button" class="btn btn-outline-secondary filter-btn" data-category="1">
                                Archived
                            </button>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="bg-light">
                                    <tr>
                                        <th class="px-4 py-3">Game Name</th>
                                        <th class="px-4 py-3">Players per Team</th>
                                        <th class="px-4 py-3">Category</th>
                                        <th class="px-4 py-3">Environment</th>
                                        <th class="px-4 py-3 text-center">Actions</th>

                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($game_result && mysqli_num_rows($game_result) > 0) {
                                        while ($game = mysqli_fetch_assoc($game_result)) {
                                            echo '<tr data-category="' . htmlspecialchars($game['is_archived']) . '">';
                                            echo '<td data-label="Game Name" class="px-4">' . htmlspecialchars($game['game_name']) . '</td>';
                                            echo '<td data-label="Players" class="px-4">' . htmlspecialchars($game['number_of_players']) . '</td>';
                                            echo '<td data-label="Category" class="px-4">' . htmlspecialchars($game['category']) . '</td>';
                                            echo '<td data-label="Environment" class="px-4">' . htmlspecialchars($game['environment']) . '</td>';


                                            if ($user['role'] === 'School Admin' || $user['role'] === 'Department Admin' || $user['role'] === 'superadmin') {
                                                echo '<td data-label="Actions" class="px-4 text-center">';
                                                echo '<div class="btn-group">';

                                                // Dropdown Toggle
                                                echo '<button class="btn btn-secondary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 38px; height: 32px; padding: 6px 0;">';
                                                echo '⋮'; // Three dots symbol
                                                echo '</button>';

                                                // Dropdown Menu

                                                echo '<ul class="dropdown-menu">';

                                                // Edit Button (Accessible for School Admin and Super Admin if not archived)
                                                if (($user['role'] === 'School Admin' || $user['role'] === 'superadmin') && $game['is_archived'] != 1) {
                                                    echo '<li>';
                                                    echo '<button onclick="openUpdateModal(' . htmlspecialchars($game['game_id']) . ', \'' .
                                                        htmlspecialchars($game['game_name']) . '\', ' .
                                                        htmlspecialchars($game['number_of_players']) . ', \'' .
                                                        htmlspecialchars($game['category']) . '\', \'' .
                                                        htmlspecialchars($game['environment']) . '\')" ' .
                                                        'class="dropdown-item" style="padding: 4px 12px; line-height: 1.2;">';
                                                    echo 'Edit';
                                                    echo '</button>';
                                                    echo '</li>';
                                                }

                                                // Delete Button (Accessible for School Admin and Super Admin)
                                                if ($user['role'] === 'School Admin' || $user['role'] === 'superadmin') {
                                                    echo '<li style="margin: 0; padding: 0; list-style: none;">';
                                                    echo '<form id="deleteForm_' . $game['game_id'] . '" action="delete_game.php" method="POST" style="margin: 0; padding: 0;">';
                                                    echo '<input type="hidden" name="game_id" value="' . htmlspecialchars($game['game_id']) . '">';
                                                    echo '<button type="button" onclick="confirmDelete(' . htmlspecialchars($game['game_id']) . ')" ' .
                                                        'class="dropdown-item" style="padding: 4px 12px; line-height: 1.2; margin: 0; width: 100%;">';
                                                    echo 'Delete';
                                                    echo '</button>';
                                                    echo '</form>';
                                                    echo '</li>';
                                                }

                                                // Archive/Unarchive Button (Accessible for School Admin and Super Admin)
                                                if ($user['role'] === 'School Admin' || $user['role'] === 'superadmin') {
                                                    echo '<li style="margin: 0; padding: 0; list-style: none;">';
                                                    echo '<button type="button" class="dropdown-item archive-btn" ' .
                                                        'data-id="' . htmlspecialchars($game['game_id']) . '" ' .
                                                        'data-table="games" ' .
                                                        'data-operation="' . ($game['is_archived'] == 1 ? 'unarchive' : 'archive') . '" ' .
                                                        'style="padding: 4px 12px; line-height: 1.2; margin: 0; width: 100%;">';
                                                    echo ($game['is_archived'] == 1 ? 'Unarchive' : 'Archive');
                                                    echo '</button>';
                                                    echo '</li>';
                                                }

                                                // "Open as Committee" Button (Accessible for School Admin, Department Admin, and Super Admin)
                                                echo '<li>';
                                                echo '<button 
                                                    type="button" 
                                                    class="dropdown-item open-committee-btn" 
                                                    data-game-id="' . htmlspecialchars($game['game_id']) . '"';

                                                if (isset($_SESSION['role'])) {
                                                    echo ' data-role="' . htmlspecialchars($_SESSION['role']) . '"';
                                                }
                                                if (isset($_SESSION['department_id'])) {
                                                    echo ' data-department-id="' . htmlspecialchars($_SESSION['department_id']) . '"';
                                                }

                                                echo '>Open as Committee</button>';
                                                echo '</li>';



                                                echo '</ul>';
                                                echo '</div>';
                                                echo '</td>';
                                            }


                                            echo '</tr>';
                                        }
                                    } else {
                                        echo '<tr><td colspan="' . ($user['role'] === 'School Admin' ? '5' : '4') . '" class="text-center py-4">';
                                        echo '<div class="text-muted">';
                                        echo '<i class="fas fa-gamepad fa-3x mb-3"></i>';
                                        echo '<p class="mb-0">No games found</p>';
                                        echo '</div>';
                                        echo '</td></tr>';
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>




    <!-- Select Department Modal -->
    <div class="modal fade" id="selectDepartmentModal" tabindex="-1" aria-labelledby="selectDepartmentModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="selectDepartmentModalLabel">Select Department</h5>
                    <!-- Removed close button to prevent closing -->
                </div>
                <?php
                if (isset($_SESSION['user_id'], $_SESSION['role'], $_SESSION['school_id'])) {
                    $role = strtolower($_SESSION['role']);
                    $school_id = $_SESSION['school_id'];

                    // Always load departments for School Admin and Super Admin
                    if ($role === 'school admin' || $role === 'superadmin') {
                        $stmt = $conn->prepare("SELECT id, department_name FROM departments WHERE school_id = ? AND is_archived = 0");
                        $stmt->bind_param("i", $school_id);
                        $stmt->execute();
                        $result = $stmt->get_result();
                    }
                }
                ?>
                <div class="modal-body">
                    <select id="departmentDropdown" class="form-select">
                        <option value="" disabled selected>Select a department</option>
                        <?php
                        if (!empty($result) && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo '<option value="' . htmlspecialchars($row['id']) . '" data-name="' . htmlspecialchars($row['department_name']) . '">' . htmlspecialchars($row['department_name']) . '</option>';
                            }
                        } else {
                            echo '<option value="" disabled>No departments available</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmOpenCommittee">Open</button>
                </div>
            </div>
        </div>
    </div>





    <!-- Update Game Modal -->
    <div class="modal fade" id="updateGameModal" tabindex="-1" aria-labelledby="updateGameModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="updateGameModalLabel">Update Game</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form id="updateGameForm" method="post" action="update_game.php">
                    <div class="modal-body">
                        <input type="hidden" name="game_id" id="update_game_id">

                        <div class="mb-3">
                            <label for="update_game_name" class="form-label">Game Name:</label>
                            <input type="text" class="form-control" name="game_name" id="update_game_name" required>
                        </div>

                        <div class="mb-3">
                            <label for="update_number_of_players" class="form-label">Number of Players per Team:</label>
                            <input type="number" class="form-control" name="number_of_players" id="update_number_of_players" min="1" required>
                        </div>

                        <div class="mb-3">
                            <label for="update_category" class="form-label">Category:</label>
                            <select name="category" id="update_category" class="form-select" required>
                                <option value="Team Sports">Team Sports</option>
                                <option value="Individual Sports">Individual Sports</option>
                                <option value="Dual Sports">Dual Sports</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="update_environment" class="form-label">Environment:</label>
                            <select name="environment" id="update_environment" class="form-select" required>
                                <option value="Indoor">Indoor</option>
                                <option value="Outdoor">Outdoor</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary" id="updateGameBtn">Update Game</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>

    <!-- Add Game Modal -->
    <div class="modal fade" id="addGameModal" tabindex="-1" aria-labelledby="addGameModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addGameModalLabel">Add New Game</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="post" action="games.php">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="game_name" class="form-label">Game Name:</label>
                            <input type="text" class="form-control" name="game_name" id="game_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="number_of_players" class="form-label">Number of Players per Team:</label>
                            <input type="number" class="form-control" name="number_of_players" id="number_of_players" min="1" required>
                        </div>
                        <div class="mb-3">
                            <label for="category" class="form-label">Category:</label>
                            <select name="category" id="category" class="form-select" required>
                                <option value="Team Sports">Team Sports</option>
                                <option value="Individual Sports">Individual Sports</option>
                                <option value="Dual Sports">Dual Sports</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="environment" class="form-label">Environment:</label>
                            <select name="environment" id="environment" class="form-select" required>
                                <option value="Indoor">Indoor</option>
                                <option value="Outdoor">Outdoor</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" name="add_game" class="btn btn-primary">Add Game</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bootstrap and jQuery Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.0/js/bootstrap.bundle.min.js"></script>
    <!-- Include SweetAlert library -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
        $(document).ready(function() {
            // Show Add Game Modal
            $('#open-popup').click(function() {
                $('#add-popup-overlay').fadeIn();
            });

            // Close Add Game Modal
            $('#close-popup, #cancel-add').click(function() {
                $('#add-popup-overlay').fadeOut();
            });

            // Show Update Game Modal
            window.openUpdateModal = function(id, name, number, category, environment) {
                $('#update_game_id').val(id);
                $('#update_game_name').val(name);
                $('#update_number_of_players').val(number);
                $('#update_category').val(category);
                $('#update_environment').val(environment);
                $('#updateGameModal').modal('show');
            };

            // Close Update Game Modal
            $('#update-close-popup, #cancel-update').click(function() {
                $('#updateGameModal').modal('hide');
            });

            // Add confirmation for update form submission
            $('#updateGameForm').on('submit', function(e) {
                e.preventDefault();
                const form = this;

                Swal.fire({
                    title: 'Confirm Update',
                    text: "Are you sure you want to update this game?",
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, update it!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        $.ajax({
                            type: "POST",
                            url: "update_game.php",
                            data: $(form).serialize(),
                            dataType: 'json',
                            success: function(response) {
                                if (response.status === 'success') {
                                    $('#updateGameModal').modal('hide');
                                    Swal.fire({
                                        icon: 'success',
                                        title: 'Success',
                                        text: response.message,
                                        showConfirmButton: false,
                                        timer: 1500
                                    }).then(function() {
                                        location.reload();
                                    });
                                } else {
                                    Swal.fire({
                                        icon: 'error',
                                        title: 'Error',
                                        text: response.message
                                    });
                                }
                            },
                            error: function(xhr, status, error) {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'There was an error updating the game: ' + error
                                });
                            }
                        });
                    }
                });
            });
        });

        function confirmDelete(gameId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "Do you really want to delete this game?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, delete it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    document.getElementById('deleteForm_' + gameId).submit();
                }
            });
        }
    </script>
    <script src="../archive/js/archive.js"></script>
    <script src="js/games.js"></script>
</body>

</html>