<?php
include_once 'connection/conn.php';
$conn = con();
include 'navbarhome.php';

// Retrieve filters from URL parameters
$school_id = isset($_GET['school_id']) ? (int)$_GET['school_id'] : null;
$department_id = isset($_GET['department_id']) ? (int)$_GET['department_id'] : null;
$grade_level = isset($_GET['grade_level']) ? $conn->real_escape_string($_GET['grade_level']) : null;

// Base query
$query = "
    SELECT 
        mr.result_id, 
        mr.score_teamA, 
        mr.score_teamB, 
        s.schedule_date, 
        g.game_name,
        tA.team_name AS teamA_name,
        tB.team_name AS teamB_name,
        dA.department_name AS teamA_department,
        dB.department_name AS teamB_department
    FROM 
        match_results mr
    JOIN 
        matches m ON mr.match_id = m.match_id
    JOIN 
        brackets b ON m.bracket_id = b.bracket_id
    JOIN 
        schedules s ON m.match_id = s.match_id
    JOIN 
        teams tA ON m.teamA_id = tA.team_id
    JOIN 
        teams tB ON m.teamB_id = tB.team_id
    LEFT JOIN 
        grade_section_course gscA ON tA.grade_section_course_id = gscA.id
    LEFT JOIN 
        grade_section_course gscB ON tB.grade_section_course_id = gscB.id
    LEFT JOIN 
        departments dA ON gscA.department_id = dA.id
    LEFT JOIN 
        departments dB ON gscB.department_id = dB.id
    JOIN 
        games g ON b.game_id = g.game_id
    WHERE 
        mr.score_teamA IS NOT NULL 
        AND mr.score_teamB IS NOT NULL";

// Apply filters if provided
if ($school_id) {
    $query .= " AND (dA.school_id = $school_id OR dB.school_id = $school_id)";
}
if ($department_id) {
    $query .= " AND (dA.id = $department_id OR dB.id = $department_id)";
}
if ($grade_level) {
    $query .= " AND (gscA.grade_level = '$grade_level' OR gscB.grade_level = '$grade_level')";
}

$query .= " ORDER BY s.schedule_date DESC";

// Execute query
$result = $conn->query($query);

// Debugging line to check for errors
if (!$result) {
    die("Query Failed: " . $conn->error);
}
?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Match Results</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="home.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        
        
    </style>
</head>

<body>

    <div class="page-header-results mt-5">
        <div class="container mt-5 pt-4 pb-4 text-center">
            <h1 class="h3 fw-semibold mb-2">Match Results</h1>
            <p class="mb-0" style="font-size: 0.9rem; opacity: 0.9;">View all completed matches and their outcomes</p>
        </div>
    </div>

    <div class="container pb-4">
        <div class="row">
            <?php if ($result->num_rows > 0): ?>
                <?php while ($row = $result->fetch_assoc()): 
                    $teamAWon = $row['score_teamA'] > $row['score_teamB'];
                    $teamBWon = $row['score_teamB'] > $row['score_teamA'];
                ?>
                    <div class="col-lg-6 mb-3">
                        <div class="card match-card">
                            <div class="game-header">
                                <div class="d-flex align-items-center">
                                    <span class="game-icon">
                                        <i class="fas fa-trophy"></i>
                                    </span>
                                    <h5 class="game-title mb-0">
                                        <?php echo htmlspecialchars($row['game_name']); ?>
                                    </h5>
                                </div>
                                <span class="date-badge">
                                    <i class="far fa-calendar-alt"></i>
                                    <?php echo htmlspecialchars(date('M d, Y', strtotime($row['schedule_date']))); ?>
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="team-info <?php echo $teamAWon ? 'winner' : ''; ?>">
                                        <div class="team-name"><?php echo htmlspecialchars($row['teamA_name']); ?></div>
                                        <div class="department-name"><?php echo htmlspecialchars($row['teamA_department']); ?></div>
                                    </div>
                                    <div class="score-display">
                                        <?php echo htmlspecialchars($row['score_teamA']); ?>
                                        <span class="vs-badge">vs</span>
                                        <?php echo htmlspecialchars($row['score_teamB']); ?>
                                    </div>
                                    <div class="team-info <?php echo $teamBWon ? 'winner' : ''; ?>">
                                        <div class="team-name"><?php echo htmlspecialchars($row['teamB_name']); ?></div>
                                        <div class="department-name"><?php echo htmlspecialchars($row['teamB_department']); ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="no-results">
                        <i class="fas fa-trophy opacity-50"></i>
                        <h3 class="h5 mb-2">No Match Results Yet</h3>
                        <p class="text-muted mb-0" style="font-size: 0.9rem;">Check back later for match results and scores.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
            </div>
    <div class="mt-5">
        <?php include 'footerhome.php'; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
</body>

</html>