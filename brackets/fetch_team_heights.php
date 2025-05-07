<?php
include('../connection/conn.php');

$game_id = $_GET['game_id'];
$department_id = $_GET['department_id'];
$grade_level = $_GET['grade_level'];

$response = ['success' => false, 'message' => ''];

try {
    // Fetch teams for the given game_id, department_id, and grade_level
    $teamsQuery = "SELECT * FROM teams WHERE game_id = ? AND department_id = ? AND grade_level = ?";
    $stmt = $pdo->prepare($teamsQuery);
    $stmt->execute([$game_id, $department_id, $grade_level]);
    $teams = $stmt->fetchAll();

    if (count($teams) == 0) {
        throw new Exception("No teams found.");
    }

    $validTeams = [];
    foreach ($teams as $team) {
        // Fetch players count for each team
        $playersQuery = "SELECT * FROM players WHERE team_id = ?";
        $stmt = $pdo->prepare($playersQuery);
        $stmt->execute([$team['team_id']]);
        $players = $stmt->fetchAll();

        if (count($players) != $team['number_of_players']) {
            continue; // Skip invalid teams
        }

        $totalHeight = 0;
        $validPlayerCount = 0;

        // Fetch height for each player
        foreach ($players as $player) {
            $playerInfoQuery = "SELECT * FROM players_info WHERE player_id = ?";
            $stmt = $pdo->prepare($playerInfoQuery);
            $stmt->execute([$player['player_id']]);
            $playerInfo = $stmt->fetch();

            if (empty($playerInfo['height'])) {
                continue; // Skip if player has no height info
            }

            // Convert height (e.g., 7'0) to inches
            $heightInches = convertHeightToInches($playerInfo['height']);
            if ($heightInches) {
                $totalHeight += $heightInches;
                $validPlayerCount++;
            }
        }

        // If all players have valid height data
        if ($validPlayerCount == count($players)) {
            $avgHeight = $totalHeight / $validPlayerCount;
            $validTeams[] = ['team_id' => $team['team_id'], 'avg_height' => $avgHeight];
        }
    }

    if (count($validTeams) > 0) {
        $response = ['success' => true, 'valid_teams' => $validTeams];
    } else {
        $response = ['success' => false, 'message' => 'Some teams have invalid data or missing height information.'];
    }
} catch (Exception $e) {
    $response = ['success' => false, 'message' => $e->getMessage()];
}

echo json_encode($response);

function convertHeightToInches($height)
{
    // Example height format: 7'0
    if (preg_match("/(\d+)\'(\d+)\"/", $height, $matches)) {
        $feet = (int) $matches[1];
        $inches = (int) $matches[2];
        return $feet * 12 + $inches;
    }
    return 0;
}
