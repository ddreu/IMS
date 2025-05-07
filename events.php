<?php
include_once 'connection/conn.php';
$conn = con();
include 'navbarhome.php';
// Initialize query
$query = "SELECT DISTINCT s.*, 
                 ta.team_name AS teamA_name, 
                 tb.team_name AS teamB_name, 
                 g.game_name,
                 d.department_name,
                 d.id as department_id,
                 d.school_id,
                 gsc.grade_level,
                 g.is_archived
          FROM schedules s
          JOIN matches m ON s.match_id = m.match_id
          JOIN teams ta ON m.teamA_id = ta.team_id
          JOIN teams tb ON m.teamB_id = tb.team_id
          JOIN brackets b ON m.bracket_id = b.bracket_id
          JOIN games g ON b.game_id = g.game_id
          JOIN grade_section_course gsc ON (ta.grade_section_course_id = gsc.id 
                                      OR tb.grade_section_course_id = gsc.id)
          JOIN departments d ON gsc.department_id = d.id
          WHERE s.schedule_date >= CURDATE() AND g.is_archived = 0";

// Build conditions based on URL parameters
$conditions = [];
if (!empty($_GET['school_id'])) {
    $conditions[] = "d.school_id = " . intval($_GET['school_id']);
}
if (!empty($_GET['department_id'])) {
    $conditions[] = "d.id = " . intval($_GET['department_id']);
}
if (!empty($_GET['grade_level'])) {
    $grade_level = $conn->real_escape_string($_GET['grade_level']);
    $conditions[] = "gsc.grade_level = '$grade_level'";
}

// Append conditions to query
if (!empty($conditions)) {
    $query .= " AND " . implode(" AND ", $conditions);
}

// Add ordering
$query .= " ORDER BY s.schedule_date ASC, s.schedule_time ASC";

// Execute query
$result = $conn->query($query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>Upcoming Events</title>
    <meta name="description" content="Upcoming events and schedules">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="home.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>

    </style>
</head>

<body>
    <div class="page-header-event mt-5">
        <div class="container mt-5 pt-4 pb-4 text-center">
            <h1 class="h3 fw-semibold mb-2">Upcoming Events</h1>
            <p class="mb-0" style="font-size: 0.9rem; opacity: 0.9;">View all scheduled matches and their details</p>
        </div>
    </div>

    <div class="container pb-4">
        <div class="row g-4">
            <?php if ($result && $result->num_rows > 0): ?>
                <?php while ($schedule = $result->fetch_assoc()): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="event-card">
                            <div class="event-header">
                                <div class="d-flex align-items-center">
                                    <span class="game-icon">
                                        <i class="fas fa-trophy"></i>
                                    </span>
                                    <h5 class="event-title"><?php echo htmlspecialchars($schedule['game_name']); ?></h5>
                                </div>
                                <div class="event-date">
                                    <i class="far fa-calendar-alt"></i>
                                    <?php echo date('M d, Y', strtotime($schedule['schedule_date'])); ?>
                                </div>
                            </div>
                            <div class="event-body">
                                <div class="teams-section">
                                    <div class="team-vs">
                                        <div class="team-name"><?php echo htmlspecialchars($schedule['teamA_name']); ?></div>
                                        <span class="vs-badge">VS</span>
                                        <div class="team-name"><?php echo htmlspecialchars($schedule['teamB_name']); ?></div>
                                    </div>
                                </div>
                                <div class="event-info">
                                    <div class="info-item">
                                        <i class="far fa-clock"></i>
                                        <span><?php echo date('g:i A', strtotime($schedule['schedule_time'])); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo htmlspecialchars($schedule['venue']); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="no-events">
                        <i class="far fa-calendar-times opacity-50"></i>
                        <h3 class="h5 mb-2">No Upcoming Events</h3>
                        <p class="text-muted mb-0" style="font-size: 0.9rem;">Check back later for new event schedules.</p>
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