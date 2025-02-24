<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Double Elimination Bracket - 6 Teams</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-bracket/0.11.1/jquery.bracket.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-bracket/0.11.1/jquery.bracket.min.js"></script>
    <style>
        body {
            font-family: Arial, sans-serif;
        }

        #bracket {
            margin: 20px;
        }
    </style>
</head>

<body>
    <h1>Double Elimination Tournament Bracket - 6 Teams</h1>
    <div id="bracket"></div>

    <script>
        var doubleEliminationData = {
            teams: [
                ["Team 1", "Team 2"],
                ["Team 3", "Team 4"],
                ["Team 5", "Team 6"],
                ["Team 7", "Team 8"]
            ],
            results: [
                [ // Winners' Bracket
                    [
                        [1, 2], // Match 1: Team 1 vs Team 2
                        [3, 4], // Match 2: Team 3 vs Team 4
                        [5, 6], // Match 3: Team 5 vs Team 6
                        [7, 8] // Match 4: Team 7 vs Team 8
                    ],
                    [
                        [9, 10], // Match 5
                        [11, 12] // Match 6
                    ],
                    [
                        [13, 14] // Match 7
                    ]
                ],
                [ // Losers' Bracket
                    [
                        [15, 16], // Match 8
                        [17, 18] // Match 9
                    ],
                    [
                        [19, 20] // Match 10
                    ],
                    [
                        [21, 22] // Match 11
                    ],
                    [
                        [23, 24] // Match 12
                    ]
                ],
                [ // Grand Final
                    [
                        [25, 26] // Match 13
                    ],
                    [
                        [27, 28] // Match 14
                    ]
                ]
            ]
        };

        $(function() {
            $('#bracket').bracket({
                init: doubleEliminationData,
                skipConsolationRound: true, // Ensures no extra matches for 3rd place
                skipGrandFinalComeback: false
            });
        });
    </script>
</body>

</html>