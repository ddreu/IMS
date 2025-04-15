<?php
include_once 'connection/conn.php';
$conn = con();
session_start();
$school_id = isset($_GET['school_id']) ? intval($_GET['school_id']) : 0;

?>

<!doctype html>
<html lang="en">

<head>
    <!-- Required meta tags -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Games Directory</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="home.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    <style>
        body {
            background-color: #f5f7fa;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        .page-header {
            background: #3949ab;
            color: white;
            padding: 1.5rem 0;
            margin-bottom: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .page-header::after {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
            background: linear-gradient(135deg, rgba(63, 81, 181, 0.1) 0%, rgba(63, 81, 181, 0) 100%);
        }

        .games-container {
            background: white;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            padding: 2rem;
            margin-bottom: 2rem;
        }

        .letter-section {
            margin-bottom: 2rem;
            position: relative;
        }

        .letter-header {
            font-size: 2rem;
            font-weight: 700;
            color: #3949ab;
            margin-bottom: 1rem;
            display: inline-block;
            position: relative;
        }

        .letter-header::after {
            content: '';
            position: absolute;
            bottom: -4px;
            left: 0;
            width: 2rem;
            height: 3px;
            background: #3949ab;
            border-radius: 2px;
        }

        .game-list {
            padding-left: 0;
            list-style: none;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 1rem;
        }

        .game-item {
            background: #f8fafc;
            border-radius: 8px;
            padding: 1rem;
            transition: all 0.2s ease;
            border: 1px solid #e2e8f0;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .game-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
            background: white;
            border-color: #3949ab;
        }

        .game-icon {
            width: 32px;
            height: 32px;
            background: #3949ab;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1rem;
        }

        .game-name {
            color: #2d3748;
            font-weight: 500;
            font-size: 0.95rem;
            margin: 0;
        }

        .empty-letter {
            color: #a0aec0;
            font-style: italic;
            grid-column: 1 / -1;
            text-align: center;
            padding: 1rem;
            background: #f8fafc;
            border-radius: 8px;
            border: 1px dashed #e2e8f0;
        }

        .search-box {
            position: relative;
            margin-bottom: 2rem;
        }

        .search-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            color: #2d3748;
            background: white;
            transition: all 0.2s ease;
        }

        .search-input:focus {
            outline: none;
            border-color: #3949ab;
            box-shadow: 0 0 0 3px rgba(63, 81, 181, 0.1);
        }

        .search-icon {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
        }

        @media (max-width: 768px) {
            .games-container {
                padding: 1rem;
            }

            .game-list {
                grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            }

            .letter-header {
                font-size: 1.75rem;

            }

            .page-header {
                margin-top: -1.7rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navbar -->
    <?php include 'navbarhome.php'; ?>

    <div class="page-header">
        <div class="container mt-5 pt-4 pb-4 text-center">
            <h1 class="h3 fw-semibold mb-2">Games Directory</h1>
            <p class="mb-0" style="font-size: 0.9rem; opacity: 0.9;">Browse all available games in the intramurals</p>
        </div>
    </div>

    <div class="container">
        <div class="games-container">
            <div class="search-box">
                <i class="fas fa-search search-icon"></i>
                <input type="text" class="search-input" id="gameSearch" placeholder="Search for a game...">
            </div>

            <?php
            // Fetch games from the database, excluding game_id = 0
            $query = "SELECT game_name FROM games WHERE game_id != 0 AND school_id = $school_id ORDER BY game_name ASC";
            $result = mysqli_query($conn, $query);

            // Function to get appropriate icon for a sport
            function getSportIcon($gameName)
            {
                $gameName = strtolower($gameName);

                // Sport-specific icons
                if (strpos($gameName, 'basketball') !== false) return 'basketball-ball';
                if (strpos($gameName, 'volleyball') !== false) return 'volleyball-ball';
                if (strpos($gameName, 'football') !== false || strpos($gameName, 'soccer') !== false) return 'futbol';
                if (strpos($gameName, 'baseball') !== false) return 'baseball-ball';
                if (strpos($gameName, 'tennis') !== false || strpos($gameName, 'badminton') !== false) return 'table-tennis';
                if (strpos($gameName, 'chess') !== false) return 'chess';
                if (strpos($gameName, 'running') !== false || strpos($gameName, 'marathon') !== false) return 'running';
                if (strpos($gameName, 'swimming') !== false) return 'swimmer';
                if (strpos($gameName, 'cycling') !== false || strpos($gameName, 'bike') !== false) return 'bicycle';
                if (strpos($gameName, 'boxing') !== false) return 'fist-raised';
                if (strpos($gameName, 'dance') !== false) return 'music';
                if (strpos($gameName, 'bowling') !== false) return 'bowling-ball';

                // Default sport icon for other games
                return 'medal';
            }

            $games = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $firstLetter = strtoupper($row['game_name'][0]);
                $games[$firstLetter][] = $row;
            }

            // Define the alphabet
            $alphabet = range('A', 'Z');

            // Display each letter section
            foreach ($alphabet as $letter):
                $hasGames = isset($games[$letter]) && !empty($games[$letter]);
            ?>
                <div class="letter-section" id="section-<?= $letter ?>" <?= !$hasGames ? 'style="display: none;"' : '' ?>>
                    <h2 class="letter-header"><?= $letter ?></h2>
                    <ul class="game-list">
                        <?php if ($hasGames): ?>
                            <?php foreach ($games[$letter] as $game): ?>
                                <li class="game-item">
                                    <div class="game-icon">
                                        <i class="fas fa-<?= getSportIcon($game['game_name']) ?>"></i>
                                    </div>
                                    <span class="game-name"><?= htmlspecialchars($game['game_name']) ?></span>
                                </li>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <li class="empty-letter">No games available for letter <?= $letter ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php include 'footerhome.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('gameSearch');
            const gameItems = document.querySelectorAll('.game-item');
            const letterSections = document.querySelectorAll('.letter-section');

            searchInput.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();

                letterSections.forEach(section => {
                    const games = section.querySelectorAll('.game-item');
                    let hasVisibleGames = false;

                    games.forEach(game => {
                        const gameName = game.querySelector('.game-name').textContent.toLowerCase();
                        const shouldShow = gameName.includes(searchTerm);
                        game.style.display = shouldShow ? '' : 'none';
                        if (shouldShow) hasVisibleGames = true;
                    });

                    section.style.display = hasVisibleGames ? '' : 'none';
                });
            });
        });
    </script>
</body>

</html>