<?php
include_once '../connection/conn.php';
$conn = con();

$deptName = '';
$gradeLabel = '';

// Get department_id and grade_level from URL
$deptId = $_GET['department_id'] ?? null;
$gradeLevel = $_GET['grade_level'] ?? ($_GET['course_id'] ?? null);

if ($deptId) {
    $stmt = $conn->prepare("SELECT department_name FROM departments WHERE id = ?");
    $stmt->bind_param("i", $deptId);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        $deptName = $row['department_name'];
    }
    $stmt->close();
}

$labelText = $deptName;
if ($deptName && strtolower($deptName) !== 'college' && $gradeLevel) {
    $labelText .= " - " . htmlspecialchars($gradeLevel);
}

?>

<style>
    /* Base styles for rankings */
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

    /* Rank icons */
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

    /* Mobile Responsive Styles */
    @media (max-width: 768px) {
        .main {
            margin-left: 0;
            padding: 15px;
        }

        .container {
            padding: 0;
        }

        /* Filter Section */
        .row.mb-4 {
            margin: 0;
            gap: 1rem;
        }

        .col-md-4 {
            padding: 0;
        }

        .form-select {
            width: 100%;
            margin-bottom: 0.5rem;
        }

        /* Card Adjustments */
        .card {
            border-radius: 0;
            margin: 0 -15px;
        }

        .card-body {
            padding: 1rem;
        }

        /* Header Section */
        .col.d-flex {
            flex-direction: column;
            gap: 1rem;
            padding: 1rem;
        }

        .col.d-flex h4 {
            font-size: 1.25rem;
            margin: 0 !important;
        }

        #resetLeaderboardBtn {
            width: 100%;
            margin: 0 !important;
        }

        /* Table Responsive */
        .table-responsive {
            margin: 0 -1rem;
        }

        #rankTable {
            margin: 0;
        }

        #rankTable thead {
            display: none;
        }

        #rankTable tbody tr {
            display: block;
            margin-bottom: 1rem;
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        #rankTable tbody td {
            display: flex;
            text-align: left;
            padding: 0.75rem 1rem;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            align-items: center;
        }

        #rankTable tbody td:before {
            content: attr(data-label);
            font-weight: 600;
            width: 120px;
            min-width: 120px;
        }

        #rankTable tbody td:last-child {
            border-bottom: none;
        }

        /* Rank Number and Icon */
        #rankTable td:first-child {
            font-size: 1.1rem;
            font-weight: 600;
            justify-content: flex-start;
        }

        #rankTable td:first-child i {
            margin-right: 0.5rem;
        }

        /* Score Column */
        #rankTable td:last-child {
            font-weight: 600;
            color: #2c3e50;
        }

        /* Medal Colors on Mobile */
        #rankTable tr.table-gold,
        #rankTable tr.table-silver,
        #rankTable tr.table-bronze {
            border-left: 4px solid;
        }

        #rankTable tr.table-gold {
            border-left-color: #ffd700;
        }

        #rankTable tr.table-silver {
            border-left-color: #c0c0c0;
        }

        #rankTable tr.table-bronze {
            border-left-color: #cd7f32;
        }
    }

    @media (max-width: 576px) {
        .main {
            padding: 10px;
        }

        h2 {
            font-size: 1.5rem;
            margin-bottom: 1rem;
        }

        .form-label {
            font-size: 0.9rem;
        }

        #rankTable td:before {
            width: 100px;
            min-width: 100px;
            font-size: 0.85rem;
        }
    }
</style>





<div class="container">
    <h2 class="text-center mt-4">Leaderboards</h2>

    <div class="row">
        <div class="col-12">
            <div class="d-flex align-items-center">
                <div class="form-check form-switch ms-auto me-3">
                    <input class="form-check-input" type="checkbox" id="toggleViewBtn">
                    <label class="form-check-label" for="toggleViewBtn" id="toggleViewLabel">Show Player Rankings</label>
                </div>
            </div>
        </div>
    </div>
    <!-- Rankings Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mt-3">
                <div class="row align-items-center">
                    <div class="col d-flex justify-content-between align-items-center">
                        <h4 class="m-0 font-weight-bold text-primary p-3">Rankings</h4>
                    </div>
                    <h5 class="m-0 text-muted text-center flex-grow-1"><?= $labelText ?: '' ?></h5>


                </div>
                <div class="card-body">
                    <div id="rankingsTable" class="table-responsive">
                        <p class="text-center text-muted">Please select a department to view rankings.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>