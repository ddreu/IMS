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
    <link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-bracket/0.11.1/jquery.bracket.min.css" />
    <style>
        .jQBracket {
            font-family: "Arial", sans-serif;
            margin: 20px auto;
        }
        .jQBracket .team {
            background-color: #fff;
        }
        .jQBracket .team.win {
            background-color: #e8f5e9;
        }
        .jQBracket .team.lose {
            background-color: #ffebee;
        }
        .jQBracket .connector {
            border-color: #90a4ae;
        }
        .jQBracket .connector.highlight {
            border-color: #2196f3;
        }
        .bye-team {
            opacity: 0.5;
            font-style: italic;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row mt-4">
            <div class="col-12">
                <div id="bracket-container"></div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-bracket/0.11.1/jquery.bracket.min.js"></script>
    <script>
        function initBracket(data) {
            if (!data.success) {
                console.error('Error loading bracket:', data.message);
                return;
            }

            const bracketData = data.matches;
            const bracketInfo = data.bracket;

            // Convert matches data to jQuery Bracket format
            let teams = [];
            let results = [];
            
            // Process first round to get teams
            if (bracketData[1]) {
                teams = bracketData[1].map(match => [
                    match.teamA_name === 'BYE' ? null : match.teamA_name,
                    match.teamB_name === 'BYE' ? null : match.teamB_name
                ]);
            }

            // Process all rounds for results
            const rounds = Math.max(...Object.keys(bracketData).filter(k => !isNaN(k)));
            for (let round = 1; round <= rounds; round++) {
                const roundResults = [];
                if (bracketData[round]) {
                    bracketData[round].forEach(match => {
                        if (match.status === 'finished') {
                            roundResults.push([
                                parseInt(match.score_teamA) || 0,
                                parseInt(match.score_teamB) || 0
                            ]);
                        } else {
                            roundResults.push([0, 0]);
                        }
                    });
                }
                results.push(roundResults);
            }

            // Initialize jQuery Bracket
            $('#bracket-container').bracket({
                teamWidth: 150,
                scoreWidth: 40,
                matchMargin: 50,
                roundMargin: 50,
                centerConnectors: true,
                disableHighlight: false,
                disableToolbar: true,
                init: {
                    teams: teams,
                    results: results
                },
                decorator: {
                    edit: function() { return; }, // Disable editing
                    render: function(container, team, score) {
                        if (team === null) {
                            container.addClass('bye-team');
                            container.append('BYE');
                        } else {
                            container.append(team);
                        }
                    }
                }
            });
        }

        // Fetch and initialize the bracket
        fetch('fetch_bracket.php?bracket_id=<?php echo $bracket_id; ?>')
            .then(response => response.json())
            .then(data => initBracket(data))
            .catch(error => console.error('Error:', error));
    </script>
</body>
</html>