<?php
require_once '../connection/conn.php';
session_start();
$user_role = $_SESSION['role'];
$schedule_id = $_GET['schedule_id'] ?? null;
$teamA_id = $_GET['teamA_id'] ?? null;
$teamB_id = $_GET['teamB_id'] ?? null;
$game_id = $_GET['game_id'] ?? null;


if ($schedule_id) {
    $conn = con();

    $sql = "
        SELECT 
            t1.team_name AS teamA_name,
            t2.team_name AS teamB_name,
            g.game_name
        FROM schedules s
        LEFT JOIN matches m ON s.match_id = m.match_id
        LEFT JOIN teams t1 ON m.teamA_id = t1.team_id
        LEFT JOIN teams t2 ON m.teamB_id = t2.team_id
        LEFT JOIN brackets b ON m.bracket_id = b.bracket_id
        LEFT JOIN games g ON b.game_id = g.game_id
        WHERE s.schedule_id = ?
    ";

    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $schedule_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();

    if ($result) {
        $teamA_name = $result['teamA_name'] ?? 'N/A';
        $teamB_name = $result['teamB_name'] ?? 'N/A';
        $game_name = $result['game_name'] ?? 'N/A';
    } else {
        $teamA_name = $teamB_name = $game_name = 'No data found';
    }

    $stmt->close();
    $conn->close();
} else {
    $teamA_name = $teamB_name = $game_name = 'Missing required data';
}


include '../navbar/navbar.php';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Live Stream</title>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" />
    <link rel="stylesheet" href="../styles/committee.css">
    <link rel="stylesheet" href="../styles/dashboard.css">
    <!-- <link rel="stylesheet" href="../../scss/sa.css"> -->
    <style>
        .live-stream-container {
            font-family: 'Poppins', sans-serif;
            /* background-color: #f4f4f9; */
            color: #333;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 20px;
            margin: 0;
        }

        h1 {
            color: #2c3e50;
            margin-bottom: 8px;
            font-size: 24px;
            font-weight: 600;
            text-align: center;
        }

        p {
            color: #555;
            font-size: 16px;
            margin-bottom: 20px;
        }

        .btn-container {
            display: flex;
            gap: 12px;
            margin-bottom: 20px;
        }

        button {
            background-color: #2c3e50;
            color: #fff;
            border: none;
            padding: 12px 20px;
            font-size: 14px;
            border-radius: 8px;
            cursor: pointer;
            transition: background 0.2s ease;
            font-weight: 600;
            outline: none;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        button:hover {
            background-color: #34495e;
        }

        button:active {
            background-color: #1f2c38;
        }

        video {
            width: 100%;
            max-width: 720px;
            border-radius: 12px;
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
            border: 2px solid #ddd;
        }

        .stream-type-selector {
            text-align: center;
        }

        .stream-type-selector h3 {
            margin-bottom: 15px;
            font-size: 1.2em;
            color: #2c3e50;
        }

        .btn-group {
            margin-bottom: 20px;
        }

        #facebookUrlContainer {
            text-align: center;
        }

        .input-group {
            margin: 0 auto;
        }
    </style>
</head>

<body>
    <nav>
        <?php
        $current_page = 'matchlist';


        // Include the appropriate sidebar based on the user role
        if ($user_role === 'Committee') {
            include '../committee/csidebar.php'; // Sidebar for committee
        } elseif ($user_role === 'Department Admin') {
            include '../department_admin/sidebar.php';
        } elseif ($user_role === 'School Admin') {
            include '../school_admin/schooladminsidebar.php';
        } else {
            include 'default_sidebar.php';
        }
        ?>
    </nav>
    <div class="live-stream-container">
        <h1>Live Stream for:</h1>
        <p>Game ID: <?= $game_name ?> | Teams: <?= $teamA_name ?> vs <?= $teamB_name ?></p>

        <div class="stream-type-selector mb-4">
            <h3>Select Stream Type</h3>
            <div class="btn-group" role="group">
                <input type="radio" class="btn-check" name="streamType" id="camera" value="camera" checked>
                <label class="btn btn-outline-primary" for="camera">Live Stream</label>

                <!-- <input type="radio" class="btn-check" name="streamType" id="screen" value="screen">
                <label class="btn btn-outline-primary" for="screen">Screen Share</label> -->

                <input type="radio" class="btn-check" name="streamType" id="facebook" value="facebook">
                <label class="btn btn-outline-primary" for="facebook">Facebook Live</label>
            </div>
        </div>

        <!-- Facebook URL input -->
        <div id="facebookUrlContainer" class="mb-4" style="display: none;">
            <div class="input-group" style="max-width: 600px; margin: 0 auto;">
                <input type="text" id="facebookUrl" class="form-control" placeholder="Enter Facebook Live URL">
                <button class="btn btn-primary" onclick="saveFacebookUrl()">Save URL</button>
            </div>
        </div>

        <!-- Agora Stream Controls -->
        <div id="agoraControls" class="btn-container">
            <button onclick="startLive('camera')" id="startCameraBtn">Start with Camera</button>
            <button onclick="startLive('screen')" id="startScreenBtn">Start with Screen</button>
            <button onclick="stopLive()" id="stopBtn">Stop Live Stream</button>
        </div>

        <video id="localVideo" autoplay playsinline muted></video>
    </div>
    <script src="https://download.agora.io/sdk/release/AgoraRTC_N.js"></script>
    <script>
        const APP_ID = "60a61e72ebf84ea39be8d203e3269b9c";
        const CHANNEL_NAME = `match_<?= $schedule_id ?>_<?= $game_id ?>`; // Dynamic channel name
        const TOKEN = null;

        let client;
        let localVideoTrack;
        let localAudioTrack;

        // Request permissions for microphone and camera
        async function requestPermissions() {
            try {
                await navigator.mediaDevices.getUserMedia({
                    video: true,
                    audio: true
                });
                console.log("Permissions granted");
            } catch (error) {
                console.error("Permission denied:", error);
                alert(`Permission denied: ${error.message}`);
            }
        }

        window.onload = () => {
            requestPermissions();
        };

        async function startLive(type) {
            try {
                client = AgoraRTC.createClient({
                    mode: "live",
                    codec: "vp8"
                });
                await client.setClientRole("host");

                // Join using the dynamic channel name
                await client.join(APP_ID, CHANNEL_NAME, TOKEN, null);

                if (type === "camera") {
                    [localAudioTrack, localVideoTrack] = await Promise.all([
                        AgoraRTC.createMicrophoneAudioTrack(),
                        AgoraRTC.createCameraVideoTrack()
                    ]);
                } else if (type === "screen") {
                    localVideoTrack = await AgoraRTC.createScreenVideoTrack();
                    localAudioTrack = await AgoraRTC.createMicrophoneAudioTrack();
                } else {
                    alert("Invalid stream type");
                    return;
                }

                // Play video stream
                localVideoTrack.play("localVideo");

                // Publish stream
                await client.publish([localVideoTrack, localAudioTrack]);

                alert("Live stream started!");
            } catch (error) {
                console.error("Error starting live stream:", error);
                alert(`Failed to start live stream: ${error.message}`);
            }
        }

        async function stopLive() {
            try {
                if (localVideoTrack) {
                    localVideoTrack.stop();
                    localVideoTrack.close();
                }
                if (localAudioTrack) {
                    localAudioTrack.stop();
                    localAudioTrack.close();
                }
                if (client) {
                    await client.leave();
                }
                alert("Live stream stopped!");
            } catch (error) {
                console.error("Error stopping live stream:", error);
                alert(`Failed to stop live stream: ${error.message}`);
            }
        }

        // Add this to your existing JavaScript
        const streamTypeInputs = document.querySelectorAll('input[name="streamType"]');
        const facebookUrlContainer = document.getElementById('facebookUrlContainer');
        const agoraControls = document.getElementById('agoraControls');

        streamTypeInputs.forEach(input => {
            input.addEventListener('change', (e) => {
                if (e.target.value === 'facebook') {
                    facebookUrlContainer.style.display = 'block';
                    agoraControls.style.display = 'none';
                    if (localVideoTrack) {
                        stopLive(); // Stop any existing Agora stream
                    }
                } else {
                    facebookUrlContainer.style.display = 'none';
                    agoraControls.style.display = 'flex';
                }
            });
        });

        async function saveFacebookUrl() {
            const url = document.getElementById('facebookUrl').value;
            if (!url) {
                alert('Please enter a Facebook Live URL');
                return;
            }

            // Format the URL for embedding
            let embedUrl = url;
            if (!url.includes('plugins/video.php')) {
                // If it's a direct Facebook URL, convert it to embed URL
                embedUrl = `https://www.facebook.com/plugins/video.php?href=${encodeURIComponent(url)}&show_text=false&autoplay=true`;
            }

            try {
                const response = await fetch('save_stream_url.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        schedule_id: <?= $schedule_id ?>,
                        stream_url: embedUrl,
                        stream_type: 'facebook'
                    })
                });

                const data = await response.json();
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: 'Facebook Live URL saved successfully! Make sure the stream is currently active.',
                        confirmButtonColor: '#3085d6'
                    });
                } else {
                    throw new Error(data.message || 'Failed to save URL');
                }
            } catch (error) {
                console.error('Error saving Facebook URL:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Failed to save Facebook Live URL: ' + error.message,
                    confirmButtonColor: '#d33'
                });
            }
        }

        // Add helper text for Facebook URL input
        document.getElementById('facebookUrl').placeholder = 'Enter Facebook Live URL (e.g., https://www.facebook.com/username/live)';
    </script>
</body>

</html>