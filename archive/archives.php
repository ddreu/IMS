<?php
session_start();
require_once '../connection/conn.php';
$conn = con();
$role = $_SESSION['role'];

$allowedTables = ['announcements', 'games', 'teams', 'department-teams', 'players', 'brackets', 'matches', 'leaderboards'];
$table = isset($_GET['table']) && in_array($_GET['table'], $allowedTables) ? $_GET['table'] : null;
$year = isset($_GET['year']) ? $_GET['year'] : '';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Archived Items</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <link rel="stylesheet" href="../styles/dashboard.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<style>
    .margin-start {
        margin-left: 8vw;
    }
</style>

<body>
    <?php
    include '../navbar/navbar.php';

    $current_page = 'archives';

    if ($role == 'Committee') {
        include 'csidebar.php';
    } elseif ($role == 'superadmin') {
        include '../super_admin/sa_sidebar.php';
    } else {
        include '../department_admin/sidebar.php';
    }
    ?>

    <div class="container mt-5 margin-start">
        <div class="flex-container d-flex justify-content-between align-items-center mb-4">
            <!-- Heading (Left side) -->
            <h2 class="mb-0">Archived Items: </br><span> <?= $table ? ucfirst($table) : '' ?> for Year <?= $year ?></span></h2>

            <!-- Dropdown (Right side in a card) -->
            <div class="card shadow-lg border-0 rounded-3 mb-0">
                <div class="card-body">
                    <!-- School Dropdown inside a flex container -->
                    <div class="d-flex align-items-center">
                        <label for="school" class="form-label mb-0 me-2">School:</label>
                        <select class="form-select" id="school" name="school_id">
                            <option value="">Select School</option>
                            <?php
                            $schools = $conn->query("SELECT school_id, school_name FROM schools WHERE school_id != 0");
                            while ($row = $schools->fetch_assoc()) {
                                $selected = (isset($_GET['school_id']) && $_GET['school_id'] == $row['school_id']) ? 'selected' : '';
                                echo "<option value='{$row['school_id']}' $selected>{$row['school_name']}</option>";
                            }
                            ?>
                        </select>

                        <!-- Archive Year Dropdown -->
                        <label for="archive_year" class="form-label mb-0 ms-2 me-2">Year:</label>
                        <select class="form-select" id="archive_year" name="year" disabled>
                            <option value="">Select Year</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>


        <div class="card shadow-sm border-0 rounded-3 mb-3">
            <div class="card-body">
                <div class="row g-3 justify-content-center">


                    <!-- Table Dropdown -->
                    <div class="col-md-3">
                        <label for="table" class="form-label">Table</label>
                        <select class="form-select" name="table" id="table">
                            <option value="">Select Table</option>
                            <?php foreach ($allowedTables as $option) : ?>
                                <option value="<?= $option ?>" <?= $table === $option ? 'selected' : '' ?>>
                                    <?= ucfirst($option) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>


                    <!-- Department Dropdown -->
                    <div class="col-md-3">
                        <label for="department" class="form-label">Department</label>
                        <select class="form-select" id="department" name="department_id" disabled>
                            <option value="">Select Department</option>
                        </select>
                    </div>
                    <!-- Grade/Section/Course Dropdown -->
                    <div class="col-md-3">
                        <label for="course" class="form-label">Grade</label>
                        <select class="form-select" id="course" name="course_id" disabled>
                            <option value="">Select Grade</option>
                        </select>
                    </div>


                    <div class="col-md-3">
                        <label for="game" class="form-label">Game</label>
                        <select class="form-select" id="game" name="game_id" disabled>
                            <option value="">Select Game</option>
                        </select>
                    </div>

                </div>
            </div>
        </div>

        <!-- New Row -->
        <div class="card shadow-sm border-0 rounded-3">
            <div class="card-body">
                <div class="row g-3">

                    <div class="col">
                        <label for="search" class="form-label">Search</label>
                        <div class="input-group">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                            <input type="text" class="form-control" id="search" placeholder="Search...">
                        </div>
                    </div>


                </div>
            </div>
        </div>

        <!-- Section to Load the Archive Table -->
        <div id="archiveTableContainer" class="card shadow-sm border-0 rounded-3 mt-3">
            <div class="card-body" id="archiveTableContent">
                <?php
                if ($table && $year) {
                    $includePath = "archive-pages/$table.php";
                    if (file_exists($includePath)) {
                        include $includePath;
                    } else {
                        echo "<p class='text-danger'>Archive page not found for selected table.</p>";
                    }
                } else {
                    echo "<p class='text-muted'>Select a table and year to view its content.</p>";
                }
                ?>
            </div>
        </div>


    </div>
    <!-- jsPDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <!-- html2canvas -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="js/utils.js"></script>
    <script src="archive-page-js/announcements.js"></script>
    <script src="archive-page-js/brackets.js"></script>
    <script src="archive-page-js/department-teams.js"></script>
    <script src="archive-page-js/leaderboards.js"></script>
</body>

</html>