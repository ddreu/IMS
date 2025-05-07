<?php
require_once '../connection/conn.php';
$conn = con();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Retrieve session data
$school_id = $_GET['school_id'] ?? $_SESSION['school_id'];
$department_id = $_GET['department_id'] ?? $_SESSION['department_id'] ?? null;
$game_id = $_GET['game_id'] ?? $_SESSION['game_id'] ?? null;
$grade_level = $_GET['grade_level'] ?? null;

// if (!$school_id) {
//     die('Error: Required session data is missing.');
// }

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

<!-- <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script> -->
<!-- 
<script src="https://d3js.org/d3.v7.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-bracket/0.11.1/jquery.bracket.min.js"></script> -->
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





    <!-- Existing Brackets Table -->
    <div class="row">
        <div class="col">

            <div class="table-responsive">
                <table id="bracketsTable" class="table table-striped table-bordered">
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
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Bracket Display Section -->
    <!-- <button id="printBracket">Print Bracket</button> -->

    <!-- <div id="bracket-container" class="mt-4"></div> -->
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