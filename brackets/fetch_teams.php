<?php
session_start();
require_once '../connection/conn.php';

header('Content-Type: application/json');

try {
    // Get parameters from either POST or GET
    $department_id = isset($_REQUEST['department_id']) ? intval($_REQUEST['department_id']) : (isset($_SESSION['department_id']) ? intval($_SESSION['department_id']) : 0);
    $grade_level = isset($_REQUEST['grade_level']) ? $_REQUEST['grade_level'] : null;
    $game_id = isset($_REQUEST['game_id']) ? intval($_REQUEST['game_id']) : (isset($_SESSION['game_id']) ? intval($_SESSION['game_id']) : 0);

    // Debug information
    error_log("Fetching teams with: department_id=$department_id, game_id=$game_id, grade_level=" . ($grade_level ?? 'null'));

    if (!$department_id) {
        throw new Exception('Department ID is required');
    }

    if (!$game_id) {
        throw new Exception('Game ID is required');
    }

    $conn = con();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Build the query based on whether grade_level is provided
    $query = "SELECT t.team_id, t.team_name 
              FROM teams t 
              INNER JOIN grade_section_course gsc ON t.grade_section_course_id = gsc.id 
              WHERE gsc.department_id = ? AND t.game_id = ?";
    $params = [$department_id, $game_id];
    $types = "ii"; // integer for department_id and game_id

    if ($grade_level !== null && $grade_level !== '') {
        $query .= " AND gsc.grade_level = ?";
        $params[] = $grade_level;
        $types .= "s"; // string for grade_level
    }

    $query .= " ORDER BY t.team_name";

    // Debug query
    error_log("Query: $query");
    error_log("Params: " . json_encode($params));

    // Prepare and execute the query
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception('Failed to prepare query: ' . $conn->error);
    }

    // Bind parameters
    $stmt->bind_param($types, ...$params);

    // Execute the query
    if (!$stmt->execute()) {
        throw new Exception('Failed to execute query: ' . $stmt->error);
    }

    $result = $stmt->get_result();
    $teams = [];
    $validTeamsWithHeight = [];

    while ($row = $result->fetch_assoc()) {
        $team = [
            'team_id' => intval($row['team_id']),
            'team_name' => $row['team_name']
        ];

        // Fetch players count for each team
        $playersQuery = "SELECT * FROM players WHERE team_id = ?";
        $stmtPlayers = $conn->prepare($playersQuery);
        $stmtPlayers->execute([$row['team_id']]);
        $playersResult = $stmtPlayers->get_result();
        $players = $playersResult->fetch_all(MYSQLI_ASSOC);

        // Fetch the required player count for the game
        $gameQuery = "SELECT number_of_players FROM games WHERE game_id = ?";
        $stmtGame = $conn->prepare($gameQuery);
        $stmtGame->execute([$game_id]);
        $gameResult = $stmtGame->get_result();
        $gameData = $gameResult->fetch_assoc();

        // If the team player count is correct, proceed to get heights
        if (count($players) === $gameData['number_of_players']) {
            $totalHeight = 0;
            $validPlayerCount = 0;

            // Check if all players have height data
            $allHaveHeight = true;
            foreach ($players as $player) {
                $playerInfoQuery = "SELECT * FROM players_info WHERE player_id = ?";
                $stmtPlayerInfo = $conn->prepare($playerInfoQuery);
                $stmtPlayerInfo->execute([$player['player_id']]);
                $playerInfo = $stmtPlayerInfo->get_result()->fetch_assoc();

                if (!empty($playerInfo['height'])) {
                    // Convert height (e.g., 7'0) to inches and calculate average
                    $heightInches = convertHeightToInches($playerInfo['height']);
                    if ($heightInches) {
                        $totalHeight += $heightInches;
                        $validPlayerCount++;
                    }
                } else {
                    $allHaveHeight = false; // If any player is missing height, flag as false
                    break; // No need to check further if one player is missing height
                }
            }

            // If all players have height data, compute the average height
            if ($allHaveHeight) {
                $avgHeight = $totalHeight / $validPlayerCount;
                $team['avg_height'] = $avgHeight;
                $validTeamsWithHeight[] = $team;
            }
        }

        // Always include the team, regardless of height data
        $teams[] = $team;
    }

    // Return success response with both valid teams with heights and all teams
    echo json_encode([
        'success' => true,
        'valid_teams_with_height' => $validTeamsWithHeight, // Teams with valid heights for seeding
        'teams' => $teams // All teams for default random seeding
    ]);
} catch (Exception $e) {
    error_log("Error in fetch_teams.php: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($conn)) {
        $conn->close();
    }
}

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
