<?php
include_once 'connection/conn.php';
$conn = con();


// Check if school_id exists and is not empty in URL params
if (!isset($_GET['school_id']) || empty($_GET['school_id']) || $_GET['school_id'] === '0') {
    header('Location: index.php');
    exit();
}

// If school_id exists but no department_id or empty/zero department_id, fetch departments and randomly assign one
if (!isset($_GET['department_id']) || empty($_GET['department_id']) || $_GET['department_id'] === '0') {
    // Fetch departments for this school
    $query = "SELECT id FROM departments WHERE school_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $_GET['school_id']);
    $stmt->execute();
    $result = $stmt->get_result();

    error_log("Fetching departments for school_id: " . $_GET['school_id']);

    if ($result->num_rows > 0) {
        // Get all department IDs into an array
        $departments = [];
        while ($row = $result->fetch_assoc()) {
            $departments[] = $row['id'];
        }

        error_log("Found departments: " . implode(", ", $departments));

        // Randomly select one department
        $random_department = $departments[array_rand($departments)];
        error_log("Selected random department: " . $random_department);

        // Redirect to the same page with the random department
        $url = $_SERVER['PHP_SELF'] . '?school_id=' . $_GET['school_id'] . '&department_id=' . $random_department;
        if (isset($_GET['grade_level'])) {
            $url .= '&grade_level=' . urlencode($_GET['grade_level']);
        }
        header('Location: ' . $url);
        exit();
    }
}
include 'navbarhome.php';
?>


<!DOCTYPE html>

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Home</title>
    <meta name="description" content="">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="home.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* Rankings Table Styles */
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

        /* New styles for rank icons */
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

        .teams-column {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.25rem;
        }

        .vs-text {
            font-size: 0.8rem;
            color: #666;
            margin: 0.1rem 0;
        }

        .announcement-section {
            background-color: #f8f9fa;
            padding: 60px 0;
        }

        .section-title {
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .title-underline {
            width: 50px;
            height: 3px;
            background: #6A1B9A;
            margin: 0 auto 2rem;
        }

        .title-underline-live {
            width: 50px;
            height: 3px;
            background: red;
            margin: 0 auto 2rem;
        }

        .announcement-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            border: none;
            border-radius: 12px;
            overflow: hidden;
        }

        .hover-lift:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1) !important;
        }

        .carousel-control-prev,
        .carousel-control-next {
            width: 40px;
            height: 40px;
            background: rgba(106, 27, 154, 0.8);
            border-radius: 50%;
            top: 50%;
            transform: translateY(-50%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .carousel:hover .carousel-control-prev,
        .carousel:hover .carousel-control-next {
            opacity: 1;
        }

        .carousel-control-prev {
            left: -20px;
        }

        .carousel-control-next {
            right: -20px;
        }

        .carousel-indicators {
            bottom: -40px;
        }

        .carousel-indicators button {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #6A1B9A;
            opacity: 0.5;
            margin: 0 5px;
        }

        .carousel-indicators button.active {
            opacity: 1;
        }

        @media (max-width: 768px) {

            .carousel-control-prev,
            .carousel-control-next {
                display: none;
            }
        }

        .default-announcement-image {
            height: 200px;
            background: linear-gradient(45deg, #6A1B9A, #9C27B0);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .default-announcement-image i {
            font-size: 4rem;
            color: rgba(255, 255, 255, 0.8);
            transition: transform 0.3s ease;
        }

        .announcement-card:hover .default-announcement-image i {
            transform: scale(1.1);
        }
    </style>
</head>

<body>

    <!-- Header -->
    <section class="welcome-hero">
        <div class="hero-overlay">
            <div class="hero-content">
                <h1>Welcome to our Annual Intramurals!</h1>
                <p>
                    It's time to unleash your inner athlete, build new friendships,
                    and celebrate the thrill of competition. Let's make this year's event
                    a true showcase of teamwork, sportsmanship, and fun. Get ready to play,
                    cheer, and create unforgettable memories. Let the games begin!</p>
            </div>
        </div>
    </section>


    <div class="announcement-section py-5 bg-light">
        <div class="container">
            <div class="text-center mb-4">
                <h4 class="section-title">Announcements</h4>
                <div class="title-underline"></div>
            </div>

            <div id="announcementCarousel" class="carousel slide" data-bs-ride="carousel">
                <div class="carousel-inner">
                    <?php
                    $department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : 0;
                    $school_id = isset($_GET['school_id']) ? intval($_GET['school_id']) : 0;

                    // Query for announcements with department_id = 0 and school_id = 0 (these will always show and be prioritized)
                    $query_special = "SELECT a.id, a.title, a.message, a.image, a.created_at, d.department_name 
                                  FROM announcement a 
                                  LEFT JOIN departments d ON a.department_id = d.id 
                                  WHERE a.department_id = 0 AND a.school_id = 0 
                                  ORDER BY a.created_at DESC";

                    // Query for other announcements, applying filters for department_id and school_id
                    $query_filtered = "SELECT a.id, a.title, a.message, a.image, a.created_at, d.department_name 
                                   FROM announcement a 
                                   LEFT JOIN departments d ON a.department_id = d.id 
                                   WHERE 1=1"; // Start with the general query

                    // Apply filters if department_id or school_id is provided
                    if ($department_id > 0) {
                        $query_filtered .= " AND a.department_id = ?";
                    }

                    if ($school_id > 0) {
                        $query_filtered .= " AND a.school_id = ?";
                    }

                    // Order by creation date for the filtered results
                    $query_filtered .= " ORDER BY a.created_at DESC";

                    // Prepare and execute the special query (priority for department_id = 0 and school_id = 0)
                    $stmt_special = $conn->prepare($query_special);
                    if ($stmt_special->execute()) {
                        $special_results = $stmt_special->get_result();
                        $special_cards = [];
                        while ($row = $special_results->fetch_assoc()) {
                            $special_cards[] = $row;
                        }
                    } else {
                        echo '<div class="alert alert-danger">Error loading priority announcements</div>';
                    }

                    // Prepare and execute the filtered query
                    $stmt_filtered = $conn->prepare($query_filtered);
                    if ($department_id > 0 || $school_id > 0) {
                        $params = [];
                        $types = '';
                        if ($department_id > 0) {
                            $params[] = $department_id;
                            $types .= "i";
                        }
                        if ($school_id > 0) {
                            $params[] = $school_id;
                            $types .= "i";
                        }
                        $stmt_filtered->bind_param($types, ...$params);
                    }

                    if ($stmt_filtered->execute()) {
                        $filtered_results = $stmt_filtered->get_result();
                        $filtered_cards = [];
                        while ($row = $filtered_results->fetch_assoc()) {
                            $filtered_cards[] = $row;
                        }
                    } else {
                        echo '<div class="alert alert-danger">Error loading filtered announcements</div>';
                    }

                    // Merge the results: Special announcements first, then filtered ones
                    $cards = array_merge($special_cards, $filtered_cards);

                    if (count($cards) > 0) {
                        // Group cards into sets of 3
                        $card_groups = array_chunk($cards, 3);

                        foreach ($card_groups as $index => $group) {
                            echo '<div class="carousel-item ' . ($index === 0 ? 'active' : '') . '">
                                <div class="row g-4">';

                            foreach ($group as $row) {
                                $date = new DateTime($row['created_at']);
                                $formatted_date = $date->format('M d, Y');

                                // Check if image exists and is not empty
                                $has_image = !empty($row['image']) && file_exists('uploads/' . $row['image']);

                                echo '<div class="col-md-4">
                                    <a href="announcement_details.php?id=' . $row['id'] . '&school_id=' . $_GET['school_id'] . '&department_id=' . $_GET['department_id'] . '" class="text-decoration-none">
                                    <div class="announcement-card card h-100 shadow-sm hover-lift">
                                        <div class="position-relative">';

                                if ($has_image) {
                                    echo '<img src="uploads/' . htmlspecialchars($row['image']) . '" 
                                           class="card-img-top" 
                                           style="height: 200px; object-fit: cover;" 
                                           alt="' . htmlspecialchars($row['title']) . '">';
                                } else {
                                    echo '<div class="default-announcement-image">
                                        <i class="fas fa-bullhorn"></i>
                                      </div>';
                                }

                                echo ($row['department_name'] ? '<span class="badge bg-primary position-absolute top-0 end-0 m-3">' . htmlspecialchars($row['department_name']) . '</span>' : '') . '
                                        </div>
                                        <div class="card-body p-4">
                                            <h5 class="card-title mb-2 text-truncate">' . htmlspecialchars($row['title']) . '</h5>
                                            <p class="card-text text-muted">' . htmlspecialchars(substr($row['message'], 0, 100)) . '...</p>
                                        </div>
                                        <div class="card-footer bg-transparent border-top-0 p-4 pt-0">
                                            <small class="text-muted">
                                                <i class="far fa-calendar-alt me-2"></i>' . $formatted_date . '
                                            </small>
                                        </div>
                                    </div>
                                    </a>
                                </div>';
                            }

                            echo '</div></div>';
                        }
                    } else {
                        echo '<div class="carousel-item active">
                            <div class="text-center py-5">
                                <i class="fas fa-bullhorn fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No announcements available</h5>
                            </div>
                          </div>';
                    }
                    ?>
                </div>




                <!-- Carousel Controls -->

                <button class="carousel-control-prev" type="button" data-bs-target="#announcementCarousel" data-bs-slide="prev">
                    <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Previous</span>
                </button>
                <button class="carousel-control-next" type="button" data-bs-target="#announcementCarousel" data-bs-slide="next">
                    <span class="carousel-control-next-icon" aria-hidden="true"></span>
                    <span class="visually-hidden">Next</span>
                </button>

            </div>
        </div>
    </div>

    <!-- Carousel Indicators -->
    <div class="carousel-indicators">
        <?php
        if (isset($card_groups)) {
            foreach ($card_groups as $index => $group) {
                echo '<button type="button" 
                                         data-bs-target="#announcementCarousel" 
                                         data-bs-slide-to="' . $index . '" 
                                         ' . ($index === 0 ? 'class="active"' : '') . '
                                         aria-label="Slide ' . ($index + 1) . '"></button>';
            }
        }
        ?>
    </div>
    </div>
    </div>
    </div>



    <div class="container mt-5 mb-4">
        <div class="text-center mb-4">
            <h4 class="section-title">Ongoing Matches</h4>
            <div class="title-underline-live"></div>
        </div>
        <div class="container pb-4 mb-5 mt-4">
            <div class="match-results">
                <!-- Content will be dynamically loaded here -->
            </div>
        </div>
    </div>



    <!-- First Ranking Table -->
    <div class="rankings-card-home">
        <div class="rankings-header">
            <h5>Current Standings</h5>
        </div>
        <div class="rankings-body">
            <div id="rankingsTable">
                <div class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-muted mt-2 mb-0">Loading rankings...</p>
                </div>
            </div>
        </div>
    </div>
    </div>


    <!-- Events Section -->

    <div class="container pb-4 mb-5 mt-4">
        <div class="row g-4">
            <div class="col-12">
                <!-- Card for Schedules -->
                <div class="card">
                    <div class="card-header text-center">
                        <h4 class="mb-0">Events</h4>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <?php
                            // Fetch `department_id` and `grade_level` from the URL, if available
                            $department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : null;
                            $grade_level = isset($_GET['grade_level']) ? htmlspecialchars($_GET['grade_level']) : null;

                            // Build the query dynamically based on the parameters
                            $query = "
                            SELECT DISTINCT 
                                g.game_name,
                                m.match_type,
                                tA.team_name as teamA_name,
                                tB.team_name as teamB_name,
                                s.schedule_date,
                                s.schedule_time,
                                s.venue
                            FROM schedules s
                            JOIN matches m ON s.match_id = m.match_id
                            JOIN brackets b ON m.bracket_id = b.bracket_id
                            JOIN games g ON b.game_id = g.game_id
                            JOIN teams tA ON m.teamA_id = tA.team_id
                            JOIN teams tB ON m.teamB_id = tB.team_id
                            WHERE s.schedule_date >= CURDATE()
                        ";

                            // Add conditions for `department_id` and `grade_level` if they are set
                            if ($department_id) {
                                $query .= " AND b.department_id = ?";
                            }
                            if ($grade_level) {
                                $query .= " AND b.grade_level = ?";
                            }

                            // Append ordering and limiting
                            $query .= " 
                            ORDER BY s.schedule_date ASC, s.schedule_time ASC 
                            LIMIT 5
                        ";

                            // Prepare the query
                            $stmtUpcoming = $conn->prepare($query);

                            // Bind parameters dynamically
                            $params = [];
                            if ($department_id) {
                                $params[] = $department_id;
                            }
                            if ($grade_level) {
                                $params[] = $grade_level;
                            }

                            // Bind the parameters to the query
                            $stmtUpcoming->bind_param(str_repeat("s", count($params)), ...$params);

                            // Execute the query
                            $stmtUpcoming->execute();
                            $resultUpcoming = $stmtUpcoming->get_result();

                            // Display the results
                            if ($resultUpcoming && $resultUpcoming->num_rows > 0) {
                                while ($row = $resultUpcoming->fetch_assoc()) {
                                    echo '
                                <div class="col-md-6 col-lg-4">
                                    <div class="event-card">
                                        <div class="event-header">
                                            <div class="d-flex align-items-center">
                                                <span class="game-icon">
                                                    <i class="fas fa-trophy"></i>
                                                </span>
                                                <h5 class="event-title">' . htmlspecialchars($row['game_name']) . '</h5>
                                            </div>
                                            <div class="event-date">
                                                <i class="far fa-calendar-alt"></i>
                                                ' . date('M d, Y', strtotime($row['schedule_date'])) . '
                                            </div>
                                        </div>
                                        <div class="event-body">
                                            <div class="teams-section">
                                                <div class="team-vs">
                                                    <div class="team-name">' . htmlspecialchars($row['teamA_name']) . '</div>
                                                    <span class="vs-badge">VS</span>
                                                    <div class="team-name">' . htmlspecialchars($row['teamB_name']) . '</div>
                                                </div>
                                            </div>
                                            <div class="event-info">
                                                <div class="info-item">
                                                    <i class="far fa-clock"></i>
                                                    <span>' . date('g:i A', strtotime($row['schedule_time'])) . '</span>
                                                </div>
                                                <div class="info-item">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <span>' . htmlspecialchars($row['venue']) . '</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>';
                                }
                            } else {
                                echo '
                            <div class="col-12">
                                <div class="no-events">
                                    <i class="far fa-calendar-times opacity-50"></i>
                                    <h3 class="h5 mb-2">No Upcoming Events</h3>
                                    <p class="text-muted mb-0" style="font-size: 0.9rem;">Check back later for new event schedules.</p>
                                </div>
                            </div>';
                            }
                            ?>
                        </div>
                        <a href="events.php?school_id=<?php echo $_GET['school_id']; ?>&department_id=<?php echo $_GET['department_id']; ?>" class="view-all-link">View All Events</a>
                    </div>
                </div>
            </div>
        </div>
    </div>



    <!-- Match Results Section -->
    <div class="container pb-4">
        <div class="row g-4">
            <div class="col-12">

                <div class="card">
                    <div class="card-header text-center">
                        <h4 class="mb-0">Recent Match Results</h4>
                    </div>
                    <div class="card-body">
                        <div class="row g-4">
                            <?php
                            // Ensure school_id is present in the URL parameters
                            if (empty($_GET['school_id'])) {
                                echo '<div class="alert alert-danger">School ID is required to view results.</div>';
                                exit;
                            }

                            // Base query to fetch match results
                            $query = "
            SELECT 
                mr.result_id,
                mr.match_id,
                mr.game_id,
                mr.team_A_id,
                mr.team_B_id,
                mr.score_teamA,
                mr.score_teamB,
                mr.winning_team_id,
                s.schedule_date,
                s.schedule_time,
                s.venue,
                tA.team_name AS teamA_name,
                tB.team_name AS teamB_name,
                gscA.grade_level AS teamA_grade_level,
                gscB.grade_level AS teamB_grade_level,
                dA.department_name AS teamA_department,
                dB.department_name AS teamB_department,
                g.game_name  -- Add this line to fetch the game_name from the games table
            FROM 
                match_results mr
            JOIN 
                matches m ON mr.match_id = m.match_id
            JOIN 
                schedules s ON m.match_id = s.match_id
            JOIN 
                teams tA ON mr.team_A_id = tA.team_id
            JOIN 
                teams tB ON mr.team_B_id = tB.team_id
            JOIN 
                grade_section_course gscA ON tA.grade_section_course_id = gscA.id
            JOIN 
                grade_section_course gscB ON tB.grade_section_course_id = gscB.id
            JOIN 
                departments dA ON gscA.department_id = dA.id
            JOIN 
                departments dB ON gscB.department_id = dB.id
            JOIN 
                games g ON mr.game_id = g.game_id  -- Add this JOIN to link to the games table
            WHERE 
                dA.school_id = ? 
                AND dB.school_id = ?
        ";

                            // Initialize parameters array
                            $params = [$_GET['school_id'], $_GET['school_id']];

                            // Apply optional filters based on URL parameters
                            if (!empty($_GET['department_id'])) {
                                $query .= " AND (dA.id = ? OR dB.id = ?)";
                                $params[] = $_GET['department_id'];
                                $params[] = $_GET['department_id'];
                            }

                            if (!empty($_GET['grade_level'])) {
                                $query .= " AND (gscA.grade_level = ? OR gscB.grade_level = ?)";
                                $params[] = $_GET['grade_level'];
                                $params[] = $_GET['grade_level'];
                            }

                            // Add ordering and limit
                            $query .= " ORDER BY s.schedule_date DESC LIMIT 5";

                            // Prepare the statement
                            $stmtRecentMatches = $conn->prepare($query);

                            // Bind parameters dynamically
                            $stmtRecentMatches->bind_param(str_repeat('s', count($params)), ...$params);

                            // Execute and fetch results
                            if ($stmtRecentMatches->execute()) {
                                $resultRecentMatches = $stmtRecentMatches->get_result();

                                if ($resultRecentMatches->num_rows > 0) {
                                    while ($row = $resultRecentMatches->fetch_assoc()) {
                                        $teamAWon = $row['score_teamA'] > $row['score_teamB'];
                                        $teamBWon = $row['score_teamB'] > $row['score_teamA'];
                                        echo '
                    <div class="col-lg-6 mb-3">
                        <div class="card match-card">
                            <div class="game-header">
                                <div class="d-flex align-items-center">
                                    <span class="game-icon">
                                        <i class="fas fa-trophy"></i>
                                    </span>
                                    <h5 class="game-title">' . htmlspecialchars($row['game_name']) . '</h5>
                                </div>
                                <span class="date-badge">
                                    <i class="far fa-calendar-alt"></i>
                                    ' . htmlspecialchars(date('M d, Y', strtotime($row['schedule_date']))) . '
                                </span>
                            </div>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div class="team-info ' . ($teamAWon ? 'winner' : '') . '">
                                        <div class="team-name">' . htmlspecialchars($row['teamA_name']) . '</div>
                                        <div class="department-name">' . htmlspecialchars($row['teamA_department']) . '</div>
                                    </div>
                                    <div class="score-display">
                                        ' . htmlspecialchars($row['score_teamA']) . '
                                        <span class="vs-badge">vs</span>
                                        ' . htmlspecialchars($row['score_teamB']) . '
                                    </div>
                                    <div class="team-info ' . ($teamBWon ? 'winner' : '') . '">
                                        <div class="team-name">' . htmlspecialchars($row['teamB_name']) . '</div>
                                        <div class="department-name">' . htmlspecialchars($row['teamB_department']) . '</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>';
                                    }
                                } else {
                                    echo '<div class="col-12">
                        <div class="no-results">
                            <i class="fas fa-trophy opacity-50"></i>
                            <h3 class="h5 mb-2">No Match Results Yet</h3>
                            <p class="text-muted mb-0" style="font-size: 0.9rem;">Check back later for match results and scores.</p>
                        </div>
                    </div>';
                                }
                            } else {
                                echo '<div class="col-12">
                    <div class="no-results">
                        <i class="fas fa-trophy opacity-50"></i>
                        <h3 class="h5 mb-2">An error occurred while fetching match results.</h3>
                    </div>
                </div>';
                            }
                            ?>
                        </div>
                        <a href="results.php?school_id=<?php echo $_GET['school_id']; ?>&department_id=<?php echo $_GET['department_id']; ?>" class="view-all-link">View All Results</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>



    <?php include 'footerhome.php' ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script>
        function fetchLiveScores() {
            // Function to extract URL parameters
            function getUrlParams() {
                const params = new URLSearchParams(window.location.search);
                return {
                    department_id: params.get("department_id"),
                    grade_level: params.get("grade_level"),
                };
            }

            // Get the parameters from the URL
            const {
                department_id,
                grade_level
            } = getUrlParams();

            $.ajax({
                url: 'fetch_live_scores.php',
                method: 'GET',
                dataType: 'json',
                data: {
                    department_id: department_id, // Use the dynamically retrieved department_id
                    grade_level: grade_level // Use the dynamically retrieved grade_level
                },
                success: function(data) {
                    const matchResultsContainer = $('.match-results');
                    matchResultsContainer.empty();

                    // Check if data is an error response
                    if (data.error) {
                        matchResultsContainer.html(`
                        <div class="text-center p-4">
                            <i class="fas fa-exclamation-circle text-danger"></i>
                            <h3 class="h5 mb-2">Error</h3>
                            <p class="text-muted mb-0" style="font-size: 0.9rem;">${data.message}</p>
                        </div>
                    `);
                        return;
                    }

                    // Ensure data is an array
                    const matches = Array.isArray(data) ? data : [data];

                    if (matches.length === 0) {
                        matchResultsContainer.html(`
                        <div class="text-center p-4">
                            <i class="fas fa-basketball-ball" style="color: #808080;"></i>
                            <h3 class="h5 mb-2">No Live Matches</h3>
                            <p class="text-muted mb-0" style="font-size: 0.9rem;">There are no ongoing matches at the moment. Check back later!</p>
                        </div>
                    `);
                        return;
                    }

                    matches.forEach(match => {
                        const formattedDate = new Date(match.schedule_date).toLocaleDateString('en-US', {
                            weekday: 'long',
                            year: 'numeric',
                            month: 'long',
                            day: 'numeric'
                        });

                        // Static live indicator
                        const liveIndicator = `<span class="badge bg-success">Live</span>`; // Static badge

                        const matchCard = `
        <div class="match-result-card">
            <div class="match-header">
                <div class="d-flex align-items-center">
                    <span class="game-icon">
                        <i class="fas fa-basketball-ball"></i>
                    </span>
                    <h5 class="match-title">${match.game_name}</h5>
                </div>
                <div class="match-date">
                    <i class="far fa-calendar-alt"></i>
                    ${formattedDate}
                </div>
            </div>
            <div>
            <div class="live-indicator d-flex justify-content-end">
                    ${liveIndicator}
                </div>
                    </div>
            <div class="match-body">
                <div class="row align-items-center">
                    <div class="col-md-5">
                   
                        <div class="team-section">
                            <div class="team-name">${match.teamA_name}</div>
                            <div class="team-score">${match.teamA_score}</div>
                            <div class="stats-box">
                                ${match.has_timeouts ? `
                                    <div class="stat-item">
                                        <span class="stat-label">Timeouts</span>
                                        <span>${match.timeout_teamA || 0}/${match.timeout_per_team}</span>
                                    </div>
                                ` : ''}
                                ${match.has_fouls ? `
                                    <div class="stat-item">
                                        <span class="stat-label">Fouls</span>
                                        <span>${match.foul_teamA || 0}/${match.max_fouls_per_team}</span>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="vs-section">
                            <div>VS</div>
                            <div class="period-info">Period ${match.period}</div>
                            ${match.time_remaining !== null ? `
                                <div class="timer-display">${match.time_formatted}</div>
                                <div class="timer-status ${match.timer_status}">${match.timer_status}</div>
                            ` : ''}
                        </div>
                        
                    </div>
                    
                    <div class="col-md-5">
                        <div class="team-section">
                            <div class="team-name">${match.teamB_name}</div>
                            <div class="team-score">${match.teamB_score}</div>
                            <div class="stats-box">
                                ${match.has_timeouts ? `
                                    <div class="stat-item">
                                        <span class="stat-label">Timeouts</span>
                                        <span>${match.timeout_teamB || 0}/${match.timeout_per_team}</span>
                                    </div>
                                ` : ''}
                                ${match.has_fouls ? `
                                    <div class="stat-item">
                                        <span class="stat-label">Fouls</span>
                                        <span>${match.foul_teamB || 0}/${match.max_fouls_per_team}</span>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                    </div>
                </div>
                
            </div>
        </div>
    `;
                        matchResultsContainer.append(matchCard);
                    });
                },
                error: function(xhr, status, error) {
                    console.error('Error fetching live scores:', error);
                    $('.match-results').html(`
                    <div class="text-center p-4">
                        <i class="fas fa-exclamation-circle text-danger"></i>
                        <h3 class="h5 mb-2">Error</h3>
                        <p class="text-muted mb-0" style="font-size: 0.9rem;">Failed to fetch live scores. Please try again later.</p>
                    </div>
                `);
                }
            });
        }

        // Start fetching when document is ready
        $(document).ready(function() {
            fetchLiveScores();
            setInterval(fetchLiveScores, 5000);
        });

        // Rankings code...
        document.addEventListener("DOMContentLoaded", function() {
            const rankingsTable = document.getElementById("rankingsTable");

            // Function to extract URL parameters
            function getUrlParams() {
                const params = new URLSearchParams(window.location.search);
                return {
                    school_id: params.get("school_id"),
                    department_id: params.get("department_id"),
                    grade_level: params.get("grade_level"),
                };
            }

            // Fetch and update the rankings table
            function updateRankings(schoolId, departmentId, gradeLevel = null, gameId = null) {
                const queryParams = new URLSearchParams({
                    school_id: schoolId,
                    department_id: departmentId,
                    game_id: gameId || "", // If no game is selected, pass an empty string
                });
                if (gradeLevel) {
                    queryParams.append("grade_level", gradeLevel);
                }

                fetch(`rankings/fetch_rankings.php?${queryParams.toString()}`)
                    .then((response) => response.json())
                    .then((data) => {
                        // Check for error message
                        if (data.error) {
                            rankingsTable.innerHTML = `<p class="text-center text-muted">${data.error}</p>`;
                            return;
                        }

                        // Check if the data contains rankings with all zeros
                        const isAllZero = data.every(team => (team.wins === 0 && team.losses === 0) || team.points === 0);
                        if (isAllZero) {
                            rankingsTable.innerHTML = `
    <div class="container">
        <div class="text-center">
            <div class="d-flex flex-column align-items-center">
                <i class="fas fa-spinner" style="color: #808080; margin-bottom: 10px;"></i>
                <p class="text-muted mb-0">
                    No data available yet, comeback later.
                </p>
            </div>
        </div>
    </div>
`;

                            return;
                        }

                        if (data.length === 0) {
                            rankingsTable.innerHTML = '<p class="text-center text-muted">No rankings available for the selected filters.</p>';
                            return;
                        }

                        let tableHtml = `
                    <table id="rankTable" class="table table-striped text-white">
                        <thead>
                            <tr>
                                <th class="text-center">Rank</th>
                                <th>Team</th>`;

                        // Check if we're showing points or wins/losses
                        if (data.length > 0 && data[0].is_points) {
                            tableHtml += `
                                <th>Points</th>`;
                        } else {
                            tableHtml += `
                                <th>Wins</th>
                                <th>Losses</th>
                                <th>Win Rate</th>`;
                        }

                        tableHtml += `
                            </tr>
                        </thead>
                        <tbody>`;

                        data.forEach((team, index) => {
                            const rowClass = index === 0 ? 'table-gold' :
                                index === 1 ? 'table-silver' :
                                index === 2 ? 'table-bronze' : '';

                            // Create rank display with icons
                            let rankDisplay;
                            if (index === 0) {
                                rankDisplay = '<i class="fas fa-trophy" style="color: #FFD700;"></i>';
                            } else if (index === 1) {
                                rankDisplay = '<i class="fas fa-medal" style="color: #C0C0C0;"></i>';
                            } else if (index === 2) {
                                rankDisplay = '<i class="fas fa-medal" style="color: #CD7F32;"></i>';
                            } else {
                                rankDisplay = index + 1;
                            }

                            tableHtml += `
                        <tr class="${rowClass}">
                            <td class="text-center">${rankDisplay}</td>
                            <td>${team.team_name}</td>`;

                            if (team.is_points) {
                                tableHtml += `
                            <td>${team.wins}</td>`; // Using wins field for points
                            } else {
                                const winRate = team.total_matches > 0 ?
                                    ((team.wins / team.total_matches) * 100).toFixed(1) :
                                    '0.0';
                                tableHtml += `
                            <td>${team.wins}</td>
                            <td>${team.losses}</td>
                            <td>${winRate}%</td>`;
                            }

                            tableHtml += `</tr>`;
                        });

                        tableHtml += `</tbody></table>`;
                        rankingsTable.innerHTML = tableHtml;
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        rankingsTable.innerHTML = '<p class="text-center text-danger">Error loading rankings. Please try again.</p>';
                    });
            }

            // Get default data from URL and fetch rankings
            const {
                school_id,
                department_id,
                grade_level
            } = getUrlParams();
            if (school_id && department_id) {
                updateRankings(school_id, department_id, grade_level);
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const carousel = new bootstrap.Carousel(document.getElementById('announcementCarousel'), {
                interval: 5000, // 5 seconds per slide
                touch: true, // Enable touch swiping on mobile
                ride: 'carousel'
            });
        });
    </script>

</body>

</html>