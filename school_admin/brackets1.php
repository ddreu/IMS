<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bracket Generator</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }

        form {
            margin-bottom: 20px;
        }

        .round {
            display: flex;
            justify-content: space-between;
            margin-bottom: 20px;
        }

        .match {
            width: 200px;
            text-align: center;
        }

        input[type="text"] {
            width: 80px;
        }

        .bracket-container {
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 5px;
        }
    </style>
</head>

<body>

    <h1>Bracket Generator</h1>
    <form id="bracketForm">
        <label for="bracketType">Bracket Type:</label>
        <select id="bracketType" required>
            <option value="single">Single Elimination</option>
            <option value="double">Double Elimination</option>
            <!-- Add more types if needed -->
        </select>

        <label for="numTeams">Number of Teams:</label>
        <input type="number" id="numTeams" min="2" required>

        <button type="submit">Generate Bracket</button>
    </form>

    <div id="bracketContainer" class="bracket-container"></div>

    <script>
        document.getElementById('bracketForm').addEventListener('submit', function(event) {
            event.preventDefault();

            const bracketType = document.getElementById('bracketType').value;
            const numTeams = parseInt(document.getElementById('numTeams').value);

            const bracketContainer = document.getElementById('bracketContainer');
            bracketContainer.innerHTML = ''; // Clear previous brackets

            if (bracketType === 'single') {
                generateSingleEliminationBracket(numTeams, bracketContainer);
            } else if (bracketType === 'double') {
                generateDoubleEliminationBracket(numTeams, bracketContainer);
            }
        });

        function generateSingleEliminationBracket(numTeams, container) {
            // Ensure numTeams is a power of 2
            if ((numTeams & (numTeams - 1)) !== 0) {
                alert('Please enter a power of 2 for number of teams.');
                return;
            }

            const rounds = Math.log2(numTeams);
            for (let i = 0; i < rounds; i++) {
                const roundDiv = document.createElement('div');
                roundDiv.className = 'round';
                for (let j = 0; j < Math.pow(2, rounds - i - 1); j++) {
                    const matchDiv = document.createElement('div');
                    matchDiv.className = 'match';
                    matchDiv.innerHTML = `<input type="text" placeholder="Team A" /> vs <input type="text" placeholder="Team B" />`;
                    roundDiv.appendChild(matchDiv);
                }
                container.appendChild(roundDiv);
            }
        }

        function generateDoubleEliminationBracket(numTeams, container) {
            // Logic for double elimination bracket layout
            // For simplicity, you can replicate similar structure as single elimination
            const rounds = Math.log2(numTeams) + 1; // Adjust for double elimination
            for (let i = 0; i < rounds; i++) {
                const roundDiv = document.createElement('div');
                roundDiv.className = 'round';
                for (let j = 0; j < Math.pow(2, rounds - i - 1); j++) {
                    const matchDiv = document.createElement('div');
                    matchDiv.className = 'match';
                    matchDiv.innerHTML = `<input type="text" placeholder="Team A" /> vs <input type="text" placeholder="Team B" />`;
                    roundDiv.appendChild(matchDiv);
                }
                container.appendChild(roundDiv);
            }
        }
    </script>
</body>

</html>