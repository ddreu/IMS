<?php
include_once '../connection/conn.php';
$conn = con();
session_start();

$match_id = $_GET['match_id'] ?? null;

if (!$match_id) {
    die("Match ID is required");
}

// Fetch match details
$match_query = $conn->prepare("
    SELECT g.game_name, tA.team_name AS teamA_name, tB.team_name AS teamB_name
    FROM matches m
    JOIN games g ON m.game_id = g.game_id
    JOIN teams tA ON m.teamA_id = tA.team_id
    JOIN teams tB ON m.teamB_id = tB.team_id
    WHERE m.match_id = ?");
$match_query->bind_param("i", $match_id);
$match_query->execute();
$match = $match_query->get_result()->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Stream - <?= htmlspecialchars($match['game_name']) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <style>
        #streamVideo {
            width: 100%;
            max-width: 1280px;
            margin: 0 auto;
            display: block;
        }
    </style>
</head>
<body class="bg-dark text-light">
    <div class="container mt-4">
        <h1 class="text-center mb-4">
            <?= htmlspecialchars($match['teamA_name']) ?> 
            vs 
            <?= htmlspecialchars($match['teamB_name']) ?>
        </h1>
        
        <video id="streamVideo" autoplay playsinline></video>
    </div>

    <script>
        // Connect to WebSocket server
        const ws = new WebSocket('ws://localhost:8080');
        const video = document.getElementById('streamVideo');
        let mediaSource;
        let sourceBuffer;

        ws.onopen = () => {
            console.log('Connected to streaming server');
            
            // Initialize MediaSource
            mediaSource = new MediaSource();
            video.src = URL.createObjectURL(mediaSource);
            
            mediaSource.onsourceopen = () => {
                sourceBuffer = mediaSource.addSourceBuffer('video/webm;codecs=vp8,opus');
            };
        };

        ws.onmessage = (event) => {
            // Convert blob data to ArrayBuffer
            event.data.arrayBuffer().then(buffer => {
                if (sourceBuffer && !sourceBuffer.updating) {
                    sourceBuffer.appendBuffer(buffer);
                }
            });
        };

        ws.onclose = () => {
            console.log('Disconnected from streaming server');
        };
    </script>
</body>
</html>
