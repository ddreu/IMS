<?php
session_start();
include 'connection/conn.php';
$conn = con();

// Ensure user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Committee') {
    header("Location: index.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch main game assigned in `users` table
$mainGameQuery = "SELECT g.game_id, g.game_name 
                  FROM users u
                  JOIN games g ON u.game_id = g.game_id
                  WHERE u.id = ?";
$stmtMainGame = mysqli_prepare($conn, $mainGameQuery);
mysqli_stmt_bind_param($stmtMainGame, "i", $user_id);
mysqli_stmt_execute($stmtMainGame);
$mainGameResult = mysqli_stmt_get_result($stmtMainGame);

// Fetch additional games from `committee_games`
$extraGamesQuery = "SELECT g.game_id, g.game_name 
                    FROM committee_games cg
                    JOIN games g ON cg.game_id = g.game_id
                    WHERE cg.committee_id = ?";
$stmtExtraGames = mysqli_prepare($conn, $extraGamesQuery);
mysqli_stmt_bind_param($stmtExtraGames, "i", $user_id);
mysqli_stmt_execute($stmtExtraGames);
$extraGamesResult = mysqli_stmt_get_result($stmtExtraGames);

// Handle game selection via GET request
if (isset($_GET['game_id'])) {
    $selected_game_id = $_GET['game_id'];

    // Verify selected game belongs to user
    $verifyQuery = "SELECT g.game_name 
                    FROM games g
                    WHERE g.game_id = ? 
                    AND (g.game_id = (SELECT game_id FROM users WHERE id = ?) 
                        OR g.game_id IN (SELECT game_id FROM committee_games WHERE committee_id = ?))";
    $stmtVerify = mysqli_prepare($conn, $verifyQuery);
    mysqli_stmt_bind_param($stmtVerify, "iii", $selected_game_id, $user_id, $user_id);
    mysqli_stmt_execute($stmtVerify);
    $verifyResult = mysqli_stmt_get_result($stmtVerify);

    if ($row = mysqli_fetch_assoc($verifyResult)) {
        $_SESSION['game_id'] = $selected_game_id;
        $_SESSION['game_name'] = $row['game_name'];
        $_SESSION['success_message'] = "Welcome to Committee Dashboard!";
        header("Location: committee/committeedashboard.php");
        exit();
    } else {
        $_SESSION['error_message'] = "Invalid game selection!";
        header("Location: select_game.php");
        exit();
    }

    mysqli_stmt_close($stmtVerify); // Free resources
}

// **Close Statements and Connection**
mysqli_stmt_close($stmtMainGame);
mysqli_stmt_close($stmtExtraGames);
mysqli_close($conn);
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select a Dashboard</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        body {
            background-image: url('images/gradient.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            text-align: center;
        }

        .container {
            padding: 40px;
            border-radius: 15px;
            color: white;
            max-width: 500px;
            box-shadow: 0px 4px 10px rgba(0, 0, 0, 0.3);
        }

        h1 {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 20px;
        }

        .dashboard-option {
            display: block;
            text-decoration: none;
            color: white;
            background: #007bff;
            padding: 15px;
            margin: 10px 0;
            border-radius: 10px;
            font-size: 1.2rem;
            font-weight: 500;
            transition: all 0.3s ease-in-out;
        }

        .dashboard-option:hover {
            background: #0056b3;
            transform: scale(1.05);
        }
    </style>
</head>

<body>

    <div class="container">
        <h1>Select a Dashboard</h1>

        <!-- Display main assigned game -->
        <?php if ($mainGame = mysqli_fetch_assoc($mainGameResult)) : ?>
            <a href="select_dashboard.php?game_id=<?= $mainGame['game_id'] ?>" class="dashboard-option">
                <?= htmlspecialchars($mainGame['game_name']) ?> Dashboard
            </a>
        <?php endif; ?>

        <!-- Display additional games -->
        <?php while ($extraGame = mysqli_fetch_assoc($extraGamesResult)) : ?>
            <a href="select_dashboard.php?game_id=<?= $extraGame['game_id'] ?>" class="dashboard-option">
                <?= htmlspecialchars($extraGame['game_name']) ?> Dashboard
            </a>
        <?php endwhile; ?>
    </div>

    <?php if (isset($_SESSION['success_message'])): ?>
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    toast: true,
                    position: 'top',
                    text: '<?php echo htmlspecialchars($_SESSION['success_message']); ?>',
                    showConfirmButton: false,
                    timer: 3000
                });
            });
        </script>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>
</body>

</html>