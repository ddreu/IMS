<?php
include_once 'connection/conn.php';
$conn = con();
include 'navbarhome.php';

// Get all URL parameters
$section_id = isset($_GET['section_id']) ? intval($_GET['section_id']) : 0;
$school_id = isset($_GET['school_id']) ? intval($_GET['school_id']) : null;
$department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : null;
$grade_level = isset($_GET['grade_level']) ? $_GET['grade_level'] : null;

// Store URL parameters for use in links
$url_params = array_filter([
    'school_id' => $school_id,
    'department_id' => $department_id,
    'grade_level' => $grade_level,
    'section_id' => $section_id
]);

// Function to build URLs with parameters
function buildUrl($base, $params) {
    return $base . '?' . http_build_query($params);
}

// Get section details
$section_query = "SELECT 
                    gsc.*, 
                    d.department_name
                 FROM 
                    grade_section_course gsc
                 JOIN 
                    departments d ON gsc.department_id = d.id
                 WHERE 
                    gsc.id = ?";
$stmt = $conn->prepare($section_query);
$stmt->bind_param("i", $section_id);
$stmt->execute();
$section_result = $stmt->get_result();
$section = $section_result->fetch_assoc();

// Get teams for this section
$teams_query = "SELECT 
                    t.*,
                    g.game_name,
                    (SELECT COUNT(*) FROM players p WHERE p.team_id = t.team_id) as player_count
                FROM 
                    teams t
                JOIN 
                    games g ON t.game_id = g.game_id
                WHERE 
                    t.grade_section_course_id = ?
                ORDER BY 
                    g.game_name, t.team_name";
$stmt = $conn->prepare($teams_query);
$stmt->bind_param("i", $section_id);
$stmt->execute();
$teams_result = $stmt->get_result();

// Get players for each team
$players_query = "SELECT 
                    p.player_id,
                    p.player_firstname,
                    p.player_lastname,
                    p.jersey_number,
                    p.team_id
                 FROM 
                    players p
                 WHERE 
                    p.team_id = ?
                 ORDER BY 
                    p.jersey_number";
$players_stmt = $conn->prepare($players_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Section Teams</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="home.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        body {
            background-color: #f5f7fa;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        .page-header {
            background: #673ab7;
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 2rem;
        }
        .section-info {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .team-card {
            background: white;
            border-radius: 12px;
            border: 1px solid rgba(0,0,0,0.1);
            margin-bottom: 1.5rem;
            transition: all 0.3s ease;
        }
        .team-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .team-header {
            padding: 1.25rem;
            border-bottom: 1px solid rgba(0,0,0,0.1);
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .team-header:hover {
            background: #f8f9fa;
        }
        .team-name {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2c3e50;
            margin: 0;
        }
        .game-badge {
            background: #e9ecef;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.9rem;
            color: #495057;
        }
        .player-list {
            padding: 1.25rem;
            background: #f8f9fa;
            display: none;
        }
        .player-item {
            display: flex;
            align-items: center;
            padding: 0.75rem;
            border-radius: 8px;
            margin-bottom: 0.5rem;
            background: white;
            transition: all 0.2s ease;
            text-decoration: none;
            color: inherit;
        }
        .player-item:hover {
            background: #e9ecef;
            transform: translateX(5px);
        }
        .jersey-number {
            background: #673ab7;
            color: white;
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-weight: 600;
        }
        .back-link {
            color: white;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        .back-link:hover {
            color: rgba(255,255,255,0.9);
        }
        .back-link i {
            margin-right: 0.5rem;
        }
        .strand-badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            background: #e3f2fd;
            color: #1976d2;
            border-radius: 4px;
            font-size: 0.85rem;
            margin-left: 1rem;
        }
        .record-badge {
            font-size: 0.9rem;
            color: #666;
            margin-left: 1rem;
        }
    </style>
</head>
<body>
    <div class="page-header mt-5">
        <div class="container mt-5 pt-4 pb-4 text-center">
           <!-- <?php
            // Remove section_id from back link parameters
            $back_params = $url_params;
            unset($back_params['section_id']);
            $back_url = buildUrl('teams.php', $back_params);
            ?>
            <a href="<?= $back_url ?>" class="back-link">
                <i class="fas fa-arrow-left"></i>
                Back to Sections
            </a>-->
            <h1 class="h3 fw-semibold mb-2">Section Teams</h1>
            <p class="mb-0" style="font-size: 0.9rem; opacity: 0.9;">Browse through our sports teams and discover their achievements. Click on a team to view their roster and performance statistics.</p>
        </div>
    </div>


    <div class="container mb-5">
        <?php if ($section): ?>
            <div class="section-info">
                <h2 class="h4 mb-3"><?= htmlspecialchars($section['department_name']) ?></h2>
                <div class="row mb-4">
                    <div class="col-md-4">
                        <p class="mb-1"><strong>Department:</strong> <?= htmlspecialchars($section['department_name']) ?></p>
                    </div>
                    <?php if ($section['department_name'] !== 'College'): ?>
                    <div class="col-md-4">
                        <p class="mb-1"><strong>Grade Level:</strong> <?= htmlspecialchars($section['grade_level']) ?></p>
                    </div>
                    <div class="col-md-4">
                        <p class="mb-1"><strong>Section:</strong> <?= htmlspecialchars($section['section_name']) ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php if (!empty($section['strand'])): ?>
                    <div class="mt-2">
                        <strong>Strand:</strong> 
                        <span class="strand-badge"><?= htmlspecialchars($section['strand']) ?></span>
                    </div>
                <?php endif; ?>
            </div>

            <?php if ($teams_result->num_rows > 0): ?>
                <?php while ($team = $teams_result->fetch_assoc()): ?>
                    <div class="team-card">
                        <div class="team-header" onclick="togglePlayers(<?= $team['team_id'] ?>)">
                            <h3 class="team-name">
                                <?= htmlspecialchars($team['team_name']) ?>
                                <small class="text-muted ms-2">(<?= $team['player_count'] ?> players)</small>
                                <span class="record-badge">
                                    <i class="fas fa-trophy text-success"></i> <?= $team['wins'] ?? 0 ?>
                                    <i class="fas fa-times text-danger ms-2"></i> <?= $team['losses'] ?? 0 ?>
                                </span>
                            </h3>
                            <span class="game-badge">
                                <?= htmlspecialchars($team['game_name']) ?>
                            </span>
                        </div>
                        <div id="players-<?= $team['team_id'] ?>" class="player-list">
                            <?php
                            $players_stmt->bind_param("i", $team['team_id']);
                            $players_stmt->execute();
                            $players_result = $players_stmt->get_result();
                            while ($player = $players_result->fetch_assoc()):
                                // Add current URL parameters to player details link
                                $player_params = $url_params;
                                $player_params['player_id'] = $player['player_id'];
                                $player_params['team_id'] = $team['team_id'];
                                $player_url = buildUrl('players_page.php', $player_params);
                            ?>
                                <a href="<?= $player_url ?>" class="player-item">
                                    <span class="jersey-number"><?= htmlspecialchars($player['jersey_number']) ?></span>
                                    <span><?= htmlspecialchars($player['player_firstname'] . ' ' . $player['player_lastname']) ?></span>
                                </a>
                            <?php endwhile; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="alert alert-info">
                    No teams found for this section.
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-danger">
                Section not found.
            </div>
        <?php endif; ?>
    </div>
<?php include 'footerhome.php' ?>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
    <script>
        function togglePlayers(teamId) {
            const playerList = document.getElementById(`players-${teamId}`);
            if (playerList.style.display === 'block') {
                playerList.style.display = 'none';
            } else {
                // Hide all other player lists
                document.querySelectorAll('.player-list').forEach(list => {
                    list.style.display = 'none';
                });
                playerList.style.display = 'block';
            }
        }
    </script>
</body>
</html>
