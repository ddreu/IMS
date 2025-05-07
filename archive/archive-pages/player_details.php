<?php
include_once '../../connection/conn.php';
$conn = con();

// Check if player_id is provided
if (!isset($_GET['player_id'])) {
    header("Location: teams.php");
    exit();
}

$player_id = intval($_GET['player_id']);
$grade_section_course_id = isset($_GET['grade_section_course_id']) ? intval($_GET['grade_section_course_id']) : null;

// Fetch player details
$player_sql = "
    SELECT p.*, 
           pi.email,
           pi.phone_number,
           pi.date_of_birth,
           pi.picture,
           pi.height,
           pi.weight,
           pi.position,
           t.team_name,
           t.team_id
    FROM players p
    LEFT JOIN players_info pi ON p.player_id = pi.player_id
    LEFT JOIN teams t ON p.team_id = t.team_id
    WHERE p.player_id = ?
";
$player_stmt = $conn->prepare($player_sql);
$player_stmt->bind_param("i", $player_id);
$player_stmt->execute();
$player_result = $player_stmt->get_result();
$player = $player_result->fetch_assoc();
$player_stmt->close();

// Fetch player stats with aggregated totals
$stats_sql = "
    SELECT 
        pms.*, 
        m.match_identifier, 
        m.match_type,
        m.round,
        m.teamA_id,
        m.teamB_id,
        s.schedule_date,
        s.schedule_time,
        s.venue,
        g.game_name,
        teamA.team_name as teamA_name,
        teamB.team_name as teamB_name
    FROM player_match_stats pms
    LEFT JOIN matches m ON pms.match_id = m.match_id
    LEFT JOIN schedules s ON m.match_id = s.match_id
    LEFT JOIN brackets b ON m.bracket_id = b.bracket_id
    LEFT JOIN games g ON b.game_id = g.game_id
    LEFT JOIN teams teamA ON m.teamA_id = teamA.team_id
    LEFT JOIN teams teamB ON m.teamB_id = teamB.team_id
    WHERE pms.player_id = ?
    ORDER BY s.schedule_date DESC, s.schedule_time DESC
";

$stats_stmt = $conn->prepare($stats_sql);
$stats_stmt->bind_param("i", $player_id);
$stats_stmt->execute();
$stats_result = $stats_stmt->get_result();

// Calculate aggregated stats
$aggregated_stats = [];
$total_matches = 0;
$matches_played = [];

while ($stat = $stats_result->fetch_assoc()) {
    // Count unique matches
    if (!in_array($stat['match_id'], $matches_played)) {
        $matches_played[] = $stat['match_id'];
        $total_matches++;
    }

    // Aggregate stats by game and stat name
    $stat_name = $stat['stat_name'];
    $game_name = $stat['game_name'] ?? 'Unknown Game';

    $key = $game_name . ' - ' . $stat_name;
    if (!isset($aggregated_stats[$key])) {
        $aggregated_stats[$key] = [
            'total' => 0,
            'average' => 0,
            'game_name' => $game_name,
            'stat_name' => $stat_name
        ];
    }
    $aggregated_stats[$key]['total'] += intval($stat['stat_value']);
}

// Calculate averages
foreach ($aggregated_stats as $key => &$stat_data) {
    $stat_data['average'] = $total_matches > 0 ? round($stat_data['total'] / $total_matches, 2) : 0;
}

// Sort aggregated stats by game name and stat name
uksort($aggregated_stats, function ($a, $b) {
    $a_parts = explode(' - ', $a);
    $b_parts = explode(' - ', $b);

    // First compare game names
    $game_compare = strcmp($a_parts[0], $b_parts[0]);
    if ($game_compare !== 0) {
        return $game_compare;
    }

    // If game names are the same, compare stat names
    return strcmp($a_parts[1], $b_parts[1]);
});

// Reset pointer for the match history table
$stats_result->data_seek(0);

?>



<style>
    .player-card {
        background: white;
        border-radius: 20px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        transition: transform 0.3s ease;
        overflow: hidden;
    }

    .player-card:hover {
        transform: translateY(-5px);
    }

    .player-image-container {
        position: relative;
        padding: 20px;
        background: linear-gradient(45deg, var(--primary-color), var(--primary-light));
    }

    .player-image {
        width: 180px;
        height: 180px;
        border-radius: 50%;
        border: 5px solid white;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        object-fit: cover;
        transition: transform 0.3s ease;
    }

    .player-image:hover {
        transform: scale(1.05);
    }

    .player-info {
        padding: 25px;
    }

    .player-name {
        font-size: 1.8rem;
        font-weight: 700;
        color: var(--text-primary);
        margin-bottom: 5px;
    }

    .jersey-number {
        font-size: 1.1rem;
        color: var(--primary-color);
        font-weight: 600;
        margin-bottom: 20px;
    }

    .info-item {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
        padding: 10px;
        border-radius: 10px;
        background: #f8f9fa;
        transition: background-color 0.2s ease;
    }

    .info-item:hover {
        background: #f0f2f5;
    }

    .info-item i {
        width: 25px;
        color: var(--primary-color);
        margin-right: 10px;
    }

    .info-label {
        font-weight: 600;
        color: var(--text-secondary);
        margin-right: 10px;
    }

    .info-value {
        color: var(--text-primary);
        flex: 1;
        text-align: right;
    }

    .stats-card {
        background: white;
        border-radius: 15px;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.05);
        margin-bottom: 20px;
    }

    .stats-header {
        padding: 20px;
        border-bottom: 1px solid #eee;
    }

    .stats-title {
        margin: 0;
        color: #333;
        font-weight: 600;
    }

    .stats-body {
        padding: 20px;
    }

    .stats-summary-card {
        background: #f8f9fa;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 20px;
    }

    .stat-number {
        font-size: 24px;
        font-weight: bold;
        color: #2c3e50;
        margin-bottom: 5px;
    }

    .stat-label {
        color: #666;
        font-size: 14px;
    }

    .game-section {
        margin-bottom: 25px;
    }

    .game-header {
        background: #f8f9fa;
        padding: 10px 15px;
        border-radius: 8px;
        margin-bottom: 15px;
    }

    .game-title {
        margin: 0;
        color: #2c3e50;
        font-weight: 600;
    }

    .match-card {
        border: 1px solid #eee;
        border-radius: 8px;
        margin-bottom: 15px;
        transition: transform 0.2s;
    }

    .match-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
    }

    .match-header {
        padding: 12px 15px;
        background: #f8f9fa;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
        border-bottom: 1px solid #eee;
    }

    .match-body {
        padding: 15px;
    }

    .stat-badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 13px;
        margin: 2px;
        background: #e9ecef;
    }

    .stat-badge.highlight {
        background: #007bff;
        color: white;
    }

    /* body.modal-open {
        padding-right: 0 !important;
        overflow-y: hidden;
    }

    .modal.fade .modal-dialog {
        transition: none !important;
    } */
</style>

<div class="row">
    <!-- Back button -->


    <!-- Player Information Card -->
    <div class="col-md-4">
        <div class="player-card">
            <div class="player-image-container text-center">
                <?php if (!empty($player['picture'])): ?>
                    <img src="<?= htmlspecialchars($player['picture']) ?>" alt="Player Picture" class="player-image">
                <?php else: ?>
                    <img src="../uploads/players/default.png" alt="Default Picture" class="player-image">
                <?php endif; ?>
            </div>
            <div class="player-info">
                <h3 class="player-name"><?= htmlspecialchars($player['player_firstname'] . ' ' . $player['player_middlename'] . ' ' . $player['player_lastname']) ?></h3>
                <div class="jersey-number">Jersey #<?= htmlspecialchars($player['jersey_number']) ?></div>

                <div class="info-item">
                    <i class="fas fa-users"></i>
                    <span class="info-label">Team:</span>
                    <span class="info-value"><?= htmlspecialchars($player['team_name']) ?></span>
                </div>

                <?php if (!empty($player['position'])): ?>
                    <div class="info-item">
                        <i class="fas fa-running"></i>
                        <span class="info-label">Position:</span>
                        <span class="info-value"><?= htmlspecialchars($player['position']) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($player['height'])): ?>
                    <div class="info-item">
                        <i class="fas fa-ruler-vertical"></i>
                        <span class="info-label">Height:</span>
                        <span class="info-value"><?= html_entity_decode(htmlspecialchars($player['height'])) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($player['weight'])): ?>
                    <div class="info-item">
                        <i class="fas fa-weight"></i>
                        <span class="info-label">Weight:</span>
                        <span class="info-value"><?= htmlspecialchars($player['weight']) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($player['date_of_birth'])): ?>
                    <div class="info-item">
                        <i class="fas fa-birthday-cake"></i>
                        <span class="info-label">Birthday:</span>
                        <span class="info-value"><?= date('M d, Y', strtotime($player['date_of_birth'])) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($player['email'])): ?>
                    <div class="info-item">
                        <i class="fas fa-envelope"></i>
                        <span class="info-label">Email:</span>
                        <span class="info-value"><?= htmlspecialchars($player['email']) ?></span>
                    </div>
                <?php endif; ?>

                <?php if (!empty($player['phone_number'])): ?>
                    <div class="info-item">
                        <i class="fas fa-phone"></i>
                        <span class="info-label">Phone:</span>
                        <span class="info-value"><?= htmlspecialchars($player['phone_number']) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Player Stats Card -->
    <div class="col-md-8">
        <div class="stats-card">
            <div class="stats-header">
                <h4 class="stats-title">Player Statistics</h4>
            </div>
            <div class="stats-body">
                <?php if ($stats_result->num_rows > 0): ?>
                    <!-- Overall Summary -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="stats-summary-card text-center">
                                <div class="stat-number"><?= $total_matches ?></div>
                                <div class="stat-label">Total Matches Played</div>
                            </div>
                        </div>
                        <?php
                        // Calculate total points and average per game
                        $total_points = 0;
                        $points_matches = 0;
                        foreach ($aggregated_stats as $key => $stat) {
                            if (
                                stripos($stat['stat_name'], 'point') !== false ||
                                stripos($stat['stat_name'], 'score') !== false
                            ) {
                                $total_points += $stat['total'];
                                $points_matches++;
                            }
                        }
                        $avg_points = $points_matches > 0 ? round($total_points / $points_matches, 1) : 0;
                        ?>
                        <div class="col-md-4">
                            <div class="stats-summary-card text-center">
                                <div class="stat-number"><?= $total_points ?></div>
                                <div class="stat-label">Total Points</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stats-summary-card text-center">
                                <div class="stat-number"><?= $avg_points ?></div>
                                <div class="stat-label">Average Points per Game</div>
                            </div>
                        </div>
                    </div>

                    <!-- Stats by Game -->
                    <?php
                    $current_game = '';
                    $game_stats = [];

                    // Group stats by game
                    foreach ($aggregated_stats as $key => $stat) {
                        $game_name = $stat['game_name'];
                        if (!isset($game_stats[$game_name])) {
                            $game_stats[$game_name] = [];
                        }
                        $game_stats[$game_name][] = $stat;
                    }

                    foreach ($game_stats as $game_name => $stats):
                    ?>
                        <div class="game-section">
                            <div class="game-header">
                                <h5 class="game-title"><?= htmlspecialchars($game_name) ?></h5>
                            </div>

                            <!-- Game Stats Summary -->
                            <div class="table-responsive mb-4">
                                <table class="table table-bordered table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Statistic</th>
                                            <th>Total</th>
                                            <th>Average per Match</th>
                                            <th>Best Performance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($stats as $stat): ?>
                                            <tr>
                                                <td><strong><?= htmlspecialchars($stat['stat_name']) ?></strong></td>
                                                <td><?= htmlspecialchars($stat['total']) ?></td>
                                                <td><?= htmlspecialchars($stat['average']) ?></td>
                                                <td>
                                                    <?php
                                                    // Find best performance for this stat
                                                    $stats_result->data_seek(0);
                                                    $best_value = 0;
                                                    $best_match = '';
                                                    while ($match = $stats_result->fetch_assoc()) {
                                                        if (
                                                            $match['game_name'] === $game_name &&
                                                            $match['stat_name'] === $stat['stat_name'] &&
                                                            $match['stat_value'] > $best_value
                                                        ) {
                                                            $best_value = $match['stat_value'];
                                                            $best_match = htmlspecialchars($match['teamA_name']) . ' vs ' . htmlspecialchars($match['teamB_name']);
                                                        }
                                                    }
                                                    echo $best_value . ' (' . $best_match . ')';
                                                    ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endforeach; ?>

                    <!-- Recent Matches -->
                    <h5 class="mb-3">Recent Match History</h5>
                    <?php
                    $stats_result->data_seek(0);
                    $shown_matches = [];
                    while ($stat = $stats_result->fetch_assoc()):
                        // Show each match only once
                        if (in_array($stat['match_id'], $shown_matches)) continue;
                        $shown_matches[] = $stat['match_id'];
                        if (count($shown_matches) > 5) break; // Show only last 5 matches
                    ?>
                        <div class="match-card">
                            <div class="match-header">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <strong class="d-block mb-1">
                                            <?= htmlspecialchars($stat['teamA_name']) ?> vs <?= htmlspecialchars($stat['teamB_name']) ?>
                                        </strong>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($stat['match_type']) ?> - Round <?= htmlspecialchars($stat['round']) ?>
                                        </small>
                                    </div>
                                    <div class="col-md-6 text-md-end">
                                        <div><?= date('M d, Y', strtotime($stat['schedule_date'])) ?></div>
                                        <small class="text-muted"><?= date('h:i A', strtotime($stat['schedule_time'])) ?></small>
                                    </div>
                                </div>
                            </div>
                            <div class="match-body">
                                <div class="row">
                                    <div class="col-md-4">
                                        <small class="text-muted">Venue</small>
                                        <div><?= htmlspecialchars($stat['venue']) ?></div>
                                    </div>
                                    <div class="col-md-8">
                                        <small class="text-muted">Performance</small>
                                        <div>
                                            <?php
                                            // Get all stats for this match
                                            $stats_result->data_seek(0);
                                            $match_stats = [];
                                            while ($match_stat = $stats_result->fetch_assoc()) {
                                                if ($match_stat['match_id'] === $stat['match_id']) {
                                                    $match_stats[] = $match_stat;
                                                }
                                            }
                                            foreach ($match_stats as $match_stat):
                                                $is_highlight = (stripos($match_stat['stat_name'], 'point') !== false ||
                                                    stripos($match_stat['stat_name'], 'score') !== false);
                                            ?>
                                                <span class="stat-badge <?= $is_highlight ? 'highlight' : '' ?>">
                                                    <?= htmlspecialchars($match_stat['stat_name']) ?>:
                                                    <?= htmlspecialchars($match_stat['stat_value']) ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-chart-bar fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No statistics available for this player yet</h5>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>