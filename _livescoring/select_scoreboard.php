<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Choose Scoreboard Type</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: "Poppins", sans-serif;
        }

        body {
            background: linear-gradient(to right, #000000, rgb(31, 13, 145));
            color: white;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            text-align: center;
        }

        .container {
            max-width: 600px;
            padding: 20px;
            background: #1e1e1e;
            border-radius: 10px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.4);
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .scoreboard-options {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .scoreboard-card {
            background: #252527;
            padding: 15px;
            border-radius: 8px;
            cursor: pointer;
            transition: transform 0.3s ease, background 0.3s ease;
        }

        .scoreboard-card:hover {
            transform: scale(1.05);
            background: #07e387;
            color: #000;
        }

        .scoreboard-card img {
            width: 100%;
            border-radius: 5px;
        }

        .scoreboard-card h2 {
            margin-top: 10px;
        }

        .scoreboard-card p {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        @media (min-width: 768px) {
            .scoreboard-options {
                flex-direction: row;
            }

            .scoreboard-card {
                flex: 1;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1>Select Your Scoreboard</h1>
        <p>Choose the right scoreboard for your game.</p>

        <div class="scoreboard-options">
            <div class="scoreboard-card" onclick="selectScoreboard('point_based_scoreboard.php')">
                <!--<img src="point-based.jpg" alt="Point Based Scoreboard">-->
                <h2>Point-Based</h2>
                <p>Best for sports like **Basketball, etc.** where points determine the winner.</p>
            </div>

            <div class="scoreboard-card" onclick="selectScoreboard('set-test.php')">
                <!--<img src="set-based.jpg" alt="Set Based Scoreboard">-->
                <h2>Set-Based</h2>
                <p>Ideal for **Volleyball, Tennis, and Badminton**, where sets determine the match winner.</p>
            </div>

            <div class="scoreboard-card" onclick="selectScoreboard('default_scoreboard.php')">
                <!--<img src="default.jpg" alt="Default Scoreboard">-->
                <h2>Default</h2>
                <p>Use for **Board Games, Esports, or Custom Games** that require simple score tracking.</p>
            </div>
        </div>
    </div>

    <script>
        function selectScoreboard(page) {
            window.location.href = page;
        }
    </script>
</body>

</html>