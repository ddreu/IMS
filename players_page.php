<?php
include_once 'connection/conn.php';
$conn = con();
include 'navbarhome.php';

// Get URL parameters
$player_id = isset($_GET['player_id']) ? intval($_GET['player_id']) : 0;
$team_id = isset($_GET['team_id']) ? intval($_GET['team_id']) : 0;
$section_id = isset($_GET['section_id']) ? intval($_GET['section_id']) : null;
$school_id = isset($_GET['school_id']) ? intval($_GET['school_id']) : null;
$department_id = isset($_GET['department_id']) ? intval($_GET['department_id']) : null;
$grade_level = isset($_GET['grade_level']) ? $_GET['grade_level'] : null;

// Store URL parameters for use in links
$url_params = array_filter([
    'school_id' => $school_id,
    'department_id' => $department_id,
    'grade_level' => $grade_level,
    'section_id' => $section_id,
    'team_id' => $team_id
]);

// Function to build URLs with parameters
function buildUrl($base, $params) {
    return $base . '?' . http_build_query($params);
}

// Get player details
$player_query = "SELECT 
                    p.*,
                    t.team_name,
                    g.game_name,
                    pi.email,
                    pi.phone_number,
                    pi.date_of_birth,
                    pi.picture,
                    pi.height,
                    pi.weight,
                    pi.position
                FROM 
                    players p
                LEFT JOIN 
                    teams t ON p.team_id = t.team_id
                LEFT JOIN 
                    games g ON t.game_id = g.game_id
                LEFT JOIN 
                    players_info pi ON p.player_id = pi.player_id
                WHERE 
                    p.player_id = ?";

$stmt = $conn->prepare($player_query);
$stmt->bind_param("i", $player_id);
$stmt->execute();
$player_result = $stmt->get_result();
$player = $player_result->fetch_assoc();

// Get player statistics
$stats_query = "SELECT 
                    pms.*,
                    s.schedule_date as match_date,
                    m.match_type,
                    m.round,
                    t1.team_name as team1_name,
                    t2.team_name as team2_name
                FROM 
                    player_match_stats pms
                JOIN 
                    matches m ON pms.match_id = m.match_id
                JOIN 
                    schedules s ON m.match_id = s.match_id
                JOIN 
                    teams t1 ON m.teamA_id = t1.team_id
                JOIN 
                    teams t2 ON m.teamB_id = t2.team_id
                WHERE 
                    pms.player_id = ?
                ORDER BY 
                    s.schedule_date DESC";

$stmt = $conn->prepare($stats_query);
$stmt->bind_param("i", $player_id);
$stmt->execute();
$stats_result = $stmt->get_result();

// Calculate career totals and averages
$career_stats = [];
$match_count = 0;
while ($stat = $stats_result->fetch_assoc()) {
    $match_count++;
    if (!isset($career_stats[$stat['stat_name']])) {
        $career_stats[$stat['stat_name']] = [
            'total' => 0,
            'matches' => []
        ];
    }
    $career_stats[$stat['stat_name']]['total'] += $stat['stat_value'];
    $career_stats[$stat['stat_name']]['matches'][] = [
        'value' => $stat['stat_value'],
        'match_date' => $stat['match_date'],
        'team1_name' => $stat['team1_name'],
        'team2_name' => $stat['team2_name'],
        'match_type' => $stat['match_type'],
        'round' => $stat['round']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Player Profile</title>

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
        .profile-card {
            background: white;
            border-radius: 12px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        .profile-picture {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
        }
        .player-name {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .player-info {
            color: #6c757d;
            margin-bottom: 0.25rem;
        }
        .stat-value {
            font-size: 1.25rem;
            font-weight: 600;
            color: #2c3e50;
        }
        .stat-label {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .match-history {
            margin-top: 2rem;
        }
        .match-card {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border: 1px solid #e0e0e0;
        }
        .match-card:hover {
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            transform: translateY(-2px);
            transition: all 0.3s ease;
        }
        .match-date {
            color: #6c757d;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .match-teams {
            font-weight: 600;
            font-size: 1.1rem;
            color: #2c3e50;
            margin: 0.75rem 0;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid #eee;
        }
        .match-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-top: 1rem;
        }
        .match-stat {
            background: #f8f9fa;
            padding: 0.6rem 1rem;
            border-radius: 8px;
            font-size: 0.95rem;
            border: 1px solid #e9ecef;
            color: #495057;
            transition: all 0.2s ease;
        }
        .match-stat:hover {
            background: #e9ecef;
            transform: translateY(-1px);
        }
        .match-stat strong {
            color: #2c3e50;
            margin-left: 0.25rem;
        }
        .badge {
            padding: 0.5rem 0.75rem;
            font-weight: 500;
        }
        .badge.bg-info {
            background-color: #0dcaf0 !important;
            color: #000;
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
    </style>
</head>
<body>

<div class="container mt-5 pt-4 pb-4 text-center">
    <div class="container mb-5">
        <?php if ($player): ?>
            <div class="profile-card text-center">
                <?php
                // Handle player image path
                $player_image = 'uploads/players/default.png'; // Default image
                if (!empty($player['picture'])) {
                    // Remove the ../ from the start of the path since we're already in root
                    $image_path = str_replace('../', '', $player['picture']);
                    if (file_exists($image_path)) {
                        $player_image = $image_path;
                    }
                }
                ?>
                <img src="<?= htmlspecialchars($player_image) ?>" 
                     alt="<?= htmlspecialchars($player['player_firstname'] . ' ' . $player['player_lastname']) ?>" 
                     class="profile-picture">
                <h2 class="player-name">
                    <?= htmlspecialchars($player['player_firstname'] . ' ' . $player['player_lastname']) ?>
                </h2>
                <p class="player-info">
                    <strong>Team:</strong> <?= htmlspecialchars($player['team_name']) ?> 
                    (<?= htmlspecialchars($player['game_name']) ?>)
                </p>
                <p class="player-info">
                    <strong>Jersey Number:</strong> <?= htmlspecialchars($player['jersey_number']) ?>
                </p>
                <?php if (!empty($player['position'])): ?>
                    <p class="player-info">
                        <strong>Position:</strong> <?= htmlspecialchars($player['position']) ?>
                    </p>
                <?php endif; ?>
                <?php if (!empty($player['height']) || !empty($player['weight'])): ?>
                    <p class="player-info">
                        <?php if (!empty($player['height'])): ?>
                            <strong>Height:</strong> <?=html_entity_decode (htmlspecialchars($player['height'])) ?> cm
                        <?php endif; ?>
                        <?php if (!empty($player['weight'])): ?>
                            <?php if (!empty($player['height'])): ?> | <?php endif; ?>
                            <strong>Weight:</strong> <?= htmlspecialchars($player['weight']) ?> kg
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
            </div>
                            </div>
            <?php if (!empty($career_stats)): ?>
                <h3 class="h4 mb-3">Career Statistics</h3>
                <div class="row">
                    <?php foreach ($career_stats as $stat_name => $stat_data): ?>
                        <div class="col-md-4">
                            <div class="stats-card">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <div class="stat-value"><?= number_format($stat_data['total']) ?></div>
                                        <div class="stat-label">Total <?= htmlspecialchars(ucwords(str_replace('_', ' ', $stat_name))) ?></div>
                                    </div>
                                    <div class="text-end">
                                        <div class="stat-value"><?= number_format($stat_data['total'] / $match_count, 1) ?></div>
                                        <div class="stat-label">Average per Match</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <div class="match-history">
                    <h3 class="h4 mb-3 text-center">Match History</h3>
                    <?php
                    // Reorganize matches by match date
                    $matches = [];
                    foreach ($career_stats as $stat_name => $stat_data) {
                        foreach ($stat_data['matches'] as $match) {
                            $match_key = $match['match_date'] . '-' . $match['team1_name'] . '-' . $match['team2_name'];
                            if (!isset($matches[$match_key])) {
                                $matches[$match_key] = [
                                    'date' => $match['match_date'],
                                    'team1_name' => $match['team1_name'],
                                    'team2_name' => $match['team2_name'],
                                    'match_type' => $match['match_type'],
                                    'round' => $match['round'],
                                    'stats' => []
                                ];
                            }
                            $matches[$match_key]['stats'][$stat_name] = $match['value'];
                        }
                    }
                    
                    // Sort matches by date (newest first)
                    uasort($matches, function($a, $b) {
                        return strtotime($b['date']) - strtotime($a['date']);
                    });
                    
                    foreach ($matches as $match):
                    ?>
                        <div class="match-card text-start">
                            <div class="match-date d-flex justify-content-between align-items-center">
                                <span><?= date('F j, Y', strtotime($match['date'])) ?></span>
                                <span class="badge bg-info">
                                    <?= ucfirst($match['match_type']) ?> - Round <?= $match['round'] ?>
                                </span>
                            </div>
                            <div class="match-teams">
                                <?= htmlspecialchars($match['team1_name']) ?> vs <?= htmlspecialchars($match['team2_name']) ?>
                            </div>
                            <div class="match-stats">
                                <?php foreach ($match['stats'] as $stat_name => $value): ?>
                                    <div class="match-stat">
                                        <?= htmlspecialchars(ucwords(str_replace('_', ' ', $stat_name))) ?>: 
                                        <strong><?= $value ?></strong>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    No match statistics available for this player.
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="alert alert-danger">
                Player not found.
            </div>
        <?php endif; ?>
    </div>
<?php include 'footerhome.php' ?>
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.min.js"></script>
</body>
</html>
