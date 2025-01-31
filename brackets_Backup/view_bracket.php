<?php
require_once '../connection/conn.php';
session_start();

if (!isset($_GET['bracket_id'])) {
    die('Bracket ID not provided');
}

$bracket_id = intval($_GET['bracket_id']);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Tournament Bracket</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .tournament-bracket {
            display: flex;
            overflow-x: auto;
            padding: 20px;
            min-height: 600px;
        }
        .round {
            margin: 0 30px;
            min-width: 220px;
        }
        .round-title {
            text-align: center;
            margin-bottom: 20px;
            font-weight: 500;
            color: #495057;
        }
        .match {
            position: absolute;
            width: 220px;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }
        .team {
            padding: 10px 15px;
            border-bottom: 1px solid #dee2e6;
            display: flex;
            align-items: center;
        }
        .team:last-child {
            border-bottom: none;
        }
        .team.winner {
            background-color: #e8f5e9;
            font-weight: bold;
        }
        .team-name {
            flex-grow: 1;
            margin-right: 10px;
        }
        .team-score {
            font-weight: bold;
            margin-right: 5px;
        }
        .winner-check {
            color: #4caf50;
        }
        .third-place-match {
            position: absolute;
            right: 50px;
            bottom: 50px;
            width: 220px;
        }
        .match-label {
            text-align: center;
            padding: 5px;
            background: #f8f9fa;
            border-top: 1px solid #dee2e6;
            font-size: 0.9em;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div id="bracket" class="tournament-bracket">
            <!-- Bracket will be rendered here -->
        </div>
    </div>

    <script>
        function getRoundTitle(roundIndex, totalRounds) {
            if (roundIndex === totalRounds - 1) return 'Finals';
            if (roundIndex === totalRounds - 2) return 'Semifinals';
            if (roundIndex === totalRounds - 3) return 'Quarterfinals';
            return `Round ${roundIndex + 1}`;
        }

        function createMatchHTML(match, position = null, isThirdPlace = false) {
            const matchClass = isThirdPlace ? 'third-place-match' : 'match';
            const style = position !== null ? `style="top: ${position}px"` : '';
            
            // Check if match is finished and has a winner
            const isFinished = match.status === 'Finished';
            const teamAWinner = isFinished && parseInt(match.winning_team_id) === parseInt(match.teamA_id);
            const teamBWinner = isFinished && parseInt(match.winning_team_id) === parseInt(match.teamB_id);

            return `
                <div class="${matchClass}" ${style} data-match-id="${match.match_id}">
                    <div class="team ${teamAWinner ? 'winner' : ''}">
                        <span class="team-name">${match.teamA_name || '---'}</span>
                        ${isFinished ? `<span class="team-score">${match.score_teamA || '0'}</span>` : ''}
                        ${teamAWinner ? '<span class="winner-check">✓</span>' : ''}
                    </div>
                    <div class="team ${teamBWinner ? 'winner' : ''}">
                        <span class="team-name">${match.teamB_name || '---'}</span>
                        ${isFinished ? `<span class="team-score">${match.score_teamB || '0'}</span>` : ''}
                        ${teamBWinner ? '<span class="winner-check">✓</span>' : ''}
                    </div>
                    ${isThirdPlace ? '<div class="match-label">Third Place Match</div>' : ''}
                </div>
            `;
        }

        function renderBracket(bracketData) {
            const bracket = document.getElementById('bracket');
            bracket.innerHTML = '';

            const rounds = bracketData.rounds;
            const totalRounds = rounds.length;

            rounds.forEach((round, roundIndex) => {
                const roundDiv = document.createElement('div');
                roundDiv.className = 'round';
                
                // Add round title
                const titleDiv = document.createElement('div');
                titleDiv.className = 'round-title';
                titleDiv.textContent = getRoundTitle(roundIndex, totalRounds);
                roundDiv.appendChild(titleDiv);

                const matchesDiv = document.createElement('div');
                matchesDiv.className = 'matches-wrapper';
                matchesDiv.style.position = 'relative';

                round.matches.forEach((match, matchIndex) => {
                    const spacing = 150;
                    const position = matchIndex * spacing;
                    matchesDiv.innerHTML += createMatchHTML(match, position);
                });

                roundDiv.appendChild(matchesDiv);
                bracket.appendChild(roundDiv);
            });

            // Add third place match if it exists
            if (bracketData.thirdPlaceMatch) {
                const thirdPlaceHTML = createMatchHTML(bracketData.thirdPlaceMatch, null, true);
                bracket.insertAdjacentHTML('beforeend', thirdPlaceHTML);
            }
        }

        // Fetch and render the bracket
        fetch('fetch_bracket.php?bracket_id=<?php echo $_GET["bracket_id"]; ?>')
            .then(response => response.json())
            .then(data => renderBracket(data))
            .catch(error => console.error('Error:', error));
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>