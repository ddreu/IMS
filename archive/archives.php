<?php
session_start();
require_once '../connection/conn.php';
$conn = con();
$role = $_SESSION['role'];
$school_id = $_SESSION['school_id'] ?? null;

$allowedTables = ['announcements', 'games', 'teams', 'department-teams', 'players', 'brackets', 'matches', 'leaderboards'];
$table = isset($_GET['table']) && in_array($_GET['table'], $allowedTables) ? $_GET['table'] : null;
$year = $_GET['year'] ?? '';

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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="../styles/dashboard.css">
    <link rel="stylesheet" href="../super_admin/sastyles.css">

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
    <div class="main-content">
        <!-- <div class="container mt-5 margin-start"> -->
        <div class="flex-container d-flex justify-content-between align-items-center mb-4">
            <!-- Heading (Left side) -->
            <h2 class="mb-0">Archived Items: </br><span> <?= $table ? ucfirst($table) : '' ?> for Year <?= $year ?></span></h2>

            <!-- Dropdown (Right side in a card) -->
            <div class="card shadow-lg border-0 rounded-3 mb-0">
                <div class="card-body">
                    <!-- School Dropdown inside a flex container -->
                    <div class="d-flex align-items-center">
                        <?php if ($role === 'superadmin'): ?>
                            <!-- Show dropdown only for superadmin -->
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
                        <?php else: ?>
                            <!-- Hidden input for School Admin and Committee -->
                            <input type="hidden" id="school" name="school_id" value="<?= htmlspecialchars($school_id) ?>">
                        <?php endif; ?>

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
                                <?php if ($option !== 'department-teams' && $option !== 'players') : ?>
                                    <option value="<?= $option ?>" <?= $table === $option ? 'selected' : '' ?>>
                                        <?= ucfirst($option) ?>
                                    </option>
                                <?php endif; ?>
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
        <!-- <div class="card shadow-sm border-0 rounded-3">
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
        </div> -->

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
    <script>
        window.userRole = <?= json_encode($role) ?>;
        window.userSchoolId = <?= json_encode($school_id) ?>;
    </script>
    <!-- jsPDF -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
    <!-- html2canvas -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" />

    <!-- jQuery (required by DataTables) -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>

    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>

    <script src="https://d3js.org/d3.v7.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-bracket/0.11.1/jquery.bracket.min.js"></script>

    <script src="js/utils.js"></script>
    <script src="archive-page-js/announcements.js"></script>
    <script src="archive-page-js/brackets.js"></script>
    <script src="archive-page-js/department-teams.js"></script>
    <script src="archive-page-js/leaderboards.js"></script>
    <script>
        $(document).ready(function() {
            // Check if the table has any rows of data before initializing the DataTable
            if ($('#matchTable tbody tr').length > 0) {
                $('#matchTable').DataTable({
                    pageLength: 10,
                    lengthMenu: [5, 10, 25, 50, 100],
                    order: [
                        [4, 'asc']
                    ], // Order by date/time column
                    columnDefs: [{
                        orderable: false,
                        targets: -1
                    }] // Disable sorting on 'Action' column
                });
            }
        });

        $(document).ready(function() {
            // Check if the table has any rows of data before initializing the DataTable
            if ($('#gamesTable tbody tr').length > 0) {
                $("#gamesTable").DataTable({
                    pageLength: 10,
                    lengthMenu: [5, 10, 25, 50, 100],
                    order: [
                        [0, "asc"]
                    ],
                    columnDefs: [{
                        orderable: false,
                        targets: []
                    }]
                });
            }
        });

        $(document).ready(function() {
            // Loop through tables with IDs starting with 'datatable_' and initialize DataTable if they contain data
            $("table[id^='datatable_']").each(function() {
                if ($(this).find('tbody tr').length > 0) {
                    $(this).DataTable({
                        pageLength: 10,
                        lengthMenu: [5, 10, 25, 50],
                        order: [],
                        columnDefs: [{
                            orderable: false,
                            targets: -1
                        }] // Disable sorting on the action column
                    });
                }
            });
        });

        document.addEventListener("DOMContentLoaded", function() {
            // Loop through tables with IDs starting with 'teamsTable_' and initialize DataTable if they contain data
            document.querySelectorAll('table[id^="teamsTable_"]').forEach(function(table) {
                if (table.querySelectorAll('tbody tr').length > 0) {
                    new DataTable(table, {
                        pageLength: 10,
                        lengthMenu: [5, 10, 25, 50, 100],
                        ordering: true,
                        columnDefs: [{
                            targets: -1,
                            orderable: false
                        }]
                    });
                }
            });
        });



        // $(document).ready(function() {
        //     $('#matchTable').DataTable({
        //         pageLength: 10,
        //         lengthMenu: [5, 10, 25, 50, 100],
        //         order: [
        //             [4, 'asc']
        //         ], // Order by date/time column
        //         columnDefs: [{
        //                 orderable: false,
        //                 targets: -1
        //             } // Disable sorting on 'Action' column
        //         ]
        //     });
        // });
        // $(document).ready(function() {
        //     $("#gamesTable").DataTable({
        //         pageLength: 10,
        //         lengthMenu: [5, 10, 25, 50, 100],
        //         order: [
        //             [0, "asc"]
        //         ],
        //         columnDefs: [{
        //             orderable: false,
        //             targets: []
        //         }]
        //     });
        // });
        // $(document).ready(function() {
        //     $("table[id^='datatable_']").each(function() {
        //         $(this).DataTable({
        //             pageLength: 10,
        //             lengthMenu: [5, 10, 25, 50],
        //             order: [],
        //             columnDefs: [{
        //                     orderable: false,
        //                     targets: -1
        //                 } // Disable sorting on the action column
        //             ]
        //         });
        //     });
        // });
        // document.addEventListener("DOMContentLoaded", function() {
        //     document.querySelectorAll('table[id^="teamsTable_"]').forEach(function(table) {
        //         new DataTable(table, {
        //             pageLength: 10,
        //             lengthMenu: [5, 10, 25, 50, 100],
        //             ordering: true,
        //             columnDefs: [{
        //                 targets: -1,
        //                 orderable: false
        //             }]
        //         });
        //     });
        // });
        // $(document).ready(function() {
        //     $("#announcementsTable").DataTable({
        //         pageLength: 10,
        //         lengthMenu: [5, 10, 25, 50, 100],
        //         columnDefs: [{
        //             orderable: false,
        //             targets: -1
        //         }],
        //     });
        // });
    </script>
</body>

</html>