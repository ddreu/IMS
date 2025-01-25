<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Scoreboard</title>
    <style>
        body {
            margin: 0;
            background-color: black;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            font-family: 'Arial', sans-serif;
        }

        .scoreboard {
            display: grid;
            grid-template-rows: 1fr 1fr;
            grid-template-columns: 1fr 2fr 1fr;
            gap: 10px;
            width: 90%;
            max-width: 600px;
            background-color: black;
            padding: 20px;
            border: 2px solid #444;
            border-radius: 10px;
        }

        .timer {
            grid-column: 2 / 3;
            font-size: 6rem;
            text-align: center;
            color: red;
        }

        .score {
            grid-column: 2 / 3;
            font-size: 4rem;
            text-align: center;
            color: green;
        }

        .side-button {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
        }

        .side-button button {
            margin: 5px;
            background-color: #444;
            border: none;
            color: yellow;
            font-size: 1.5rem;
            padding: 10px;
            border-radius: 5px;
            cursor: pointer;
        }

        .side-button button:hover {
            background-color: #666;
        }

        .center-buttons {
            grid-column: 2 / 3;
            display: flex;
            justify-content: center;
            gap: 10px;
        }

        .center-buttons button {
            background-color: green;
            border: none;
            color: white;
            font-size: 1rem;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
        }

        .center-buttons button:hover {
            background-color: limegreen;
        }
    </style>
</head>
<body>
    <div class="scoreboard">
        <!-- Timer -->
        <div class="timer">10:00</div>

        <!-- Side Buttons -->
        <div class="side-button">
            <button>Menu</button>
            <button>+</button>
            <button>-</button>
        </div>

        <div class="side-button">
            <button>Edit</button>
            <button>+</button>
            <button>-</button>
        </div>

        <!-- Score -->
        <div class="score">24</div>

        <!-- Center Buttons -->
        <div class="center-buttons">
            <button>14</button>
            <button>Reset</button>
        </div>
    </div>
</body>
</html>
