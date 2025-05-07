<?php
// include_once '../connection/conn.php';
// include '../user_logs/logger.php'; // Include the logger file
// $conn = con();
// session_start();

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
// Get user school ID
$sql = "SELECT school_id FROM users WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
if ($stmt === false) {
    die('mysqli_prepare() failed: ' . htmlspecialchars(mysqli_error($conn)));
}

mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if ($result->num_rows === 0) {
    header('Location: ../login.php');
    exit();
}

$user = mysqli_fetch_assoc($result);

// School ID: from GET or fallback to user's
$school_id = isset($_GET['school_id']) ? intval($_GET['school_id']) : $user['school_id'];

// Year: optional, from GET
$year = isset($_GET['year']) ? intval($_GET['year']) : null;

// Search & filter
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

// Base SQL + school ID
$game_sql = "SELECT * FROM games WHERE school_id = ? AND is_archived = 1";
$params = [$school_id];
$types = "i";

// Optional: environment filter
if ($filter === 'indoor') {
    $game_sql .= " AND environment = 'Indoor'";
} elseif ($filter === 'outdoor') {
    $game_sql .= " AND environment = 'Outdoor'";
}

// Optional: search term
if (!empty($search)) {
    $game_sql .= " AND game_name LIKE ?";
    $params[] = "%" . $search . "%";
    $types .= "s";
}

// Optional: filter by year
if ($year) {
    $game_sql .= " AND YEAR(archived_at) = ?";
    $params[] = $year;
    $types .= "i";
}

// Final sort
$game_sql .= " ORDER BY game_id DESC";

// Prepare and bind
$stmt_games = mysqli_prepare($conn, $game_sql);
if ($stmt_games === false) {
    die('mysqli_prepare() failed: ' . htmlspecialchars(mysqli_error($conn)));
}

mysqli_stmt_bind_param($stmt_games, $types, ...$params);
mysqli_stmt_execute($stmt_games);
$game_result = mysqli_stmt_get_result($stmt_games);

// Fetch departments
$departments = [];
if ($school_id) {
    $department_result = mysqli_query($conn, "SELECT id, department_name FROM departments WHERE school_id = $school_id");
    if ($department_result) {
        $departments = mysqli_fetch_all($department_result, MYSQLI_ASSOC);
    }
}
?>

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



<div class="wrapper">


    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>List of Games</h2>

        </div>

        <!-- <div class="filter-section">
                <div class="row g-3"> -->
        <!-- <div class="col-md-6">
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
                    </div> -->
        <!-- <div class="col-md-6">
                        <form method="GET" action="games.php" class="d-flex gap-2">
                            <input type="text" name="search" class="form-control" placeholder="Search games..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-search"></i>
                            </button>
                        </form>
                    </div> -->
        <!-- </div>
            </div> -->

        <div class="card box">
            <div class="card-body">

                <div class="table-responsive">
                    <table id="gamesTable" class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4 py-3">Game Name</th>
                                <th class="px-4 py-3">Players per Team</th>
                                <th class="px-4 py-3">Category</th>
                                <th class="px-4 py-3">Environment</th>

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