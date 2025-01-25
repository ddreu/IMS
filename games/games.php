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
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addGameModal"><i class="fas fa-plus"></i> Add Game</button>
                    <?php endif; ?>
                </div>
            </div>

            <!--<form method="GET" action="games.php" class="mb-3">
                <div class="d-flex">
                    <input type="text" name="search" class="form-control me-2" placeholder="Search for games..." value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                    <button type="submit" class="btn btn-primary">Search</button>
                </div>
            </form>-->



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


            <div class="card box">
                <div class="card-body">
                    <div class="table-responsive">
                        <div class="container-fluid p-0">
                            <!-- Filter Buttons -->
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <div class="btn-group" role="group">
                                        <a href="games.php?filter=all" class="btn <?php echo $filter === 'all' || !isset($filter) ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                            <i class="fas fa-list me-2"></i>All
                                        </a>
                                        <a href="games.php?filter=indoor" class="btn <?php echo $filter === 'indoor' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                            <i class="fas fa-home me-2"></i>Indoor
                                        </a>
                                        <a href="games.php?filter=outdoor" class="btn <?php echo $filter === 'outdoor' ? 'btn-primary' : 'btn-outline-primary'; ?>">
                                            <i class="fas fa-sun me-2"></i>Outdoor
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

                            <!-- Games Table -->
                            <div class="card shadow-sm">
                                <div class="card-body p-0">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="px-4 py-3">Game Name</th>
                                                <th class="px-4 py-3">Players per Team</th>
                                                <th class="px-4 py-3">Category</th>
                                                <th class="px-4 py-3">Environment</th>
                                                <?php if ($user['role'] === 'School Admin') : ?>
                                                    <th class="px-4 py-3 text-center">Actions</th>
                                                <?php endif; ?>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
                                            if ($game_result && mysqli_num_rows($game_result) > 0) {
                                                while ($game = mysqli_fetch_assoc($game_result)) {
                                                    echo '<tr>';
                                                    echo '<td class="px-4">' . htmlspecialchars($game['game_name']) . '</td>';
                                                    echo '<td class="px-4">' . htmlspecialchars($game['number_of_players']) . '</td>';
                                                    echo '<td class="px-4">';
                                                    // Add badge for category
                                                    $categoryClass = '';
                                                    switch ($game['category']) {
                                                        case 'Team Sports':
                                                            $categoryClass = 'bg-primary';
                                                            break;
                                                        case 'Individual Sports':
                                                            $categoryClass = 'bg-success';
                                                            break;
                                                        case 'Dual Sports':
                                                            $categoryClass = 'bg-info';
                                                            break;
                                                    }
                                                    echo '<span class="badge ' . $categoryClass . '">' . htmlspecialchars($game['category']) . '</span>';
                                                    echo '</td>';
                                                    echo '<td class="px-4">';
                                                    // Add badge for environment
                                                    $envClass = $game['environment'] === 'Indoor' ? 'bg-secondary' : 'bg-success';
                                                    echo '<span class="badge ' . $envClass . '">' . htmlspecialchars($game['environment']) . '</span>';
                                                    echo '</td>';

                                                    if ($user['role'] === 'School Admin') {
                                                        echo '<td class="px-4">';
                                                        echo '<div class="d-flex gap-2 justify-content-center">';

                                                        // Edit button
                                                        echo '<button onclick="openUpdateModal(' .
                                                            htmlspecialchars($game['game_id']) . ', \'' .
                                                            htmlspecialchars($game['game_name']) . '\', ' .
                                                            htmlspecialchars($game['number_of_players']) . ', \'' .
                                                            htmlspecialchars($game['category']) . '\', \'' .
                                                            htmlspecialchars($game['environment']) . '\')" ' .
                                                            'class="btn btn-primary btn-sm mx-1" style="width: 38px; height: 32px; padding: 6px 0;">' .
                                                            '<i class="fas fa-edit"></i></button>';

                                                        // Delete button and form
                                                        echo '<form id="deleteForm_' . $game['game_id'] . '" action="delete_game.php" method="POST" class="d-inline">';
                                                        echo '<input type="hidden" name="game_id" value="' . htmlspecialchars($game['game_id']) . '">';
                                                        echo '<button type="button" onclick="confirmDelete(' . htmlspecialchars($game['game_id']) . ')" ' .
                                                            'class="btn btn-danger btn-sm mx-1" style="width: 38px; height: 32px; padding: 6px 0;">' .
                                                            '<i class="fas fa-trash"></i></button>';
                                                        echo '</form>';

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
</body>

</html>