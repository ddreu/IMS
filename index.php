<?php
include_once 'connection/conn.php';
$conn = con();
session_start(); // Start the session

// Check if the user is logged in by checking a session variable
$isLoggedIn = isset($_SESSION['user_id']);
// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Fetch schools from the database
$sql = "SELECT school_id, school_name, logo FROM schools WHERE school_id != 0 ORDER BY school_name";
$result = $conn->query($sql);

// Close the connection after use
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Welcome to IMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="index.css">
</head>

<body>
    <!-- Navbar -->
    <header class="navbar">
        <div class="logo">IMS</div>
        <?php if (!$isLoggedIn):
        ?>
            <a href="login.php" class="login-btn">Log In</a>
        <?php endif; ?>
    </header>

    <div class="transition-layer" id="transition-layer">
        <div class="layer translucent"></div>
        <div class="layer solid"></div>
        <!-- <div id="loader" class="loader-container loader-in-transition">
            <div class="loader"></div>
        </div> -->
    </div>


    <section class="hero-section">
        <video autoplay muted loop playsinline id="hero-video">
            <source src="assets/vid/hero-vid.mp4" type="video/mp4">
            Your browser does not support the video tag.
        </video>

        <div class="hero-content">
            <h1>Enhance Your Sports Experience <span class="highlight">Elevate the Game.</span></h1>
            <p>Effortlessly manage your intramurals optimize you workflow track scores, organize schedules, and streamline operations. Take control, stay ahead, and lead your teams to victory—all in one powerful platform.</p>
            <button class="cta-btn" id="open-schools-btn">Select Your School</button>
        </div>
    </section>

    <!-- Schools Section -->
    <section id="schools-section" class="schools-section">
        <button class="close-btn" id="close-btn">✕</button>
        <div class="school-cards-container">
            <?php
            if ($result && $result->num_rows > 0) {
                while ($row = $result->fetch_assoc()) {
                    $logoPath = !empty($row['logo']) ? "uploads/logos/" . $row['logo'] : "assets/default-logo.png";
                    echo "
                <a href='home.php?school_id=" . htmlspecialchars($row['school_id']) . "' 
   class='school-card' 
   style=\"--logo-url: url('" . htmlspecialchars($logoPath) . "');\">
    <div class='logo-overlay'></div>
    <div class='card-content'>
        <img src='" . htmlspecialchars($logoPath) . "' alt='" . htmlspecialchars($row['school_name']) . " Logo' class='school-logo'>
        <h3 class='school-name'>" . htmlspecialchars($row['school_name']) . "</h3>

    </div>
</a>
";
                }
            } else {
                echo "<p class='no-schools'>No schools available at the moment. Please check back later.</p>";
            }
            ?>
        </div>
    </section>
    <!-- 
    <div id="loader" class="loader-container loader-in-transition">
        <div class="loader"></div>
    </div> -->

    <!-- <div id="loader" class="loader-container loader-in-transition">
        <i class="fas fa-basketball-ball basketball-loader"></i>
    </div> -->

    <!-- <div id="loader" class="loader-container loader-in-transition">
        <svg class="basketball-svg" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="500" zoomAndPan="magnify" viewBox="0 0 375 374.999991" height="500" preserveAspectRatio="xMidYMid meet" version="1.0">
            <path class="basketball-line" d="M 187.5 375 C 84.113281 375 0 290.886719 0 187.5 C 0 84.113281 84.113281 0 187.5 0 C 290.886719 0 375 84.097656 375 187.5 C 375 290.902344 290.886719 375 187.5 375 Z M 187.5 16.304688 C 93.097656 16.304688 16.304688 93.097656 16.304688 187.5 C 16.304688 281.902344 93.097656 358.695312 187.5 358.695312 C 281.902344 358.695312 358.695312 281.902344 358.695312 187.5 C 358.695312 93.097656 281.902344 16.304688 187.5 16.304688 Z M 187.5 16.304688 " fill-opacity="1" fill-rule="nonzero" />
            <path class="basketball-line" d="M 363.34375 160.175781 C 360.996094 160.175781 358.664062 159.164062 357.050781 157.207031 C 299.820312 87.878906 224.738281 77.121094 171.683594 80.429688 C 101.902344 84.683594 40.273438 116.199219 23.167969 142.972656 C 20.738281 146.753906 15.699219 147.863281 11.917969 145.449219 C 8.121094 143.039062 7.011719 138 9.441406 134.199219 C 28.824219 103.824219 91.941406 68.949219 170.707031 64.15625 C 250.890625 59.28125 321.585938 88.628906 369.636719 146.835938 C 372.503906 150.308594 372.015625 155.445312 368.542969 158.316406 C 367.011719 159.554688 365.167969 160.175781 363.34375 160.175781 Z M 363.34375 160.175781 " fill-opacity="1" fill-rule="nonzero" />
            <path class="basketball-line" d="M 40.761719 293.476562 C 36.261719 293.476562 32.609375 289.824219 32.609375 285.324219 C 32.609375 265.515625 56.496094 238.175781 86.738281 203.558594 C 120.734375 164.640625 163.042969 116.234375 163.042969 81.523438 C 163.042969 62.773438 158.199219 49.15625 148.648438 41.070312 C 135.113281 29.609375 115.679688 32.476562 115.46875 32.496094 C 111.03125 33.308594 106.824219 30.226562 106.09375 25.792969 C 105.34375 21.359375 108.359375 17.152344 112.792969 16.417969 C 113.871094 16.222656 139.710938 12.148438 159.195312 28.628906 C 172.566406 39.960938 179.347656 57.75 179.347656 81.523438 C 179.347656 122.347656 136.679688 171.195312 99.015625 214.289062 C 74.398438 242.476562 48.914062 271.628906 48.914062 285.324219 C 48.914062 289.824219 45.261719 293.476562 40.761719 293.476562 Z M 40.761719 293.476562 " fill-opacity="1" fill-rule="nonzero" />
            <path class="basketball-line" d="M 163.042969 366.847656 C 161.675781 366.847656 160.273438 366.503906 158.984375 365.773438 C 155.070312 363.523438 153.734375 358.550781 155.96875 354.636719 C 203.496094 271.957031 211.957031 239.398438 211.957031 16.304688 C 211.957031 11.789062 215.609375 8.152344 220.109375 8.152344 C 224.609375 8.152344 228.261719 11.804688 228.261719 16.304688 C 228.261719 234.082031 221.738281 272.933594 170.121094 362.753906 C 168.601562 365.378906 165.863281 366.847656 163.042969 366.847656 Z M 163.042969 366.847656 " fill-opacity="1" fill-rule="nonzero" />
            <path class="basketball-line" d="M 309.78125 326.085938 C 308.59375 326.085938 307.386719 325.824219 306.226562 325.273438 C 302.167969 323.300781 300.488281 318.425781 302.460938 314.378906 C 321.148438 275.933594 304.648438 236.414062 287.183594 194.59375 C 274.238281 163.597656 260.871094 131.574219 260.871094 97.824219 C 260.871094 44.738281 300.554688 40.824219 300.945312 40.792969 C 305.445312 40.351562 309.375 43.746094 309.75 48.246094 C 310.125 52.726562 306.800781 56.675781 302.300781 57.050781 C 299.820312 57.292969 277.175781 60.78125 277.175781 97.824219 C 277.175781 128.316406 289.90625 158.804688 302.21875 188.300781 C 320.300781 231.636719 339 276.425781 317.121094 321.488281 C 315.699219 324.390625 312.800781 326.085938 309.78125 326.085938 Z M 309.78125 326.085938 " fill-opacity="1" fill-rule="nonzero" />
        </svg>
    </div> -->
    <div id="loader" class="loader-container loader-in-transition">
        <svg class="basketball-svg" xmlns="http://www.w3.org/2000/svg" width="200" height="200" viewBox="0 0 400 400" fill="none">
            <path class="basketball-line"
                d="M386.546,128.301c-19.183-49.906-56.579-89.324-105.302-110.99C255.513,5.868,228.272,0.065,200.28,0.065 
      c-79.087,0-150.907,46.592-182.972,118.693c-21.668,48.723-23.036,103.041-3.854,152.944 
      c19.181,49.905,56.578,89.324,105.299,110.992c25.726,11.438,52.958,17.238,80.949,17.24c0.008,0,0.008,0,0.016,0 
      c64.187,0,124.602-30.795,162.104-82.505l3.967-5.719c6.559-9.743,12.238-19.979,16.9-30.469 
      C404.359,232.521,405.728,178.206,386.546,128.301z M306.656,67.229c29.342,23.576,50.05,56.346,58.89,93.178 
      c-26.182,14.254-115.898,58.574-227.678,63.936c-0.22-6.556-0.188-13.204,0.095-19.894c3.054,0.258,6.046,0.392,8.957,0.392 
      c48.011,0,72.144-34.739,95.479-68.341C258.911,112.729,277.523,85.931,306.656,67.229z M200.322,29.683 
      c23.826,0,47.004,4.939,68.891,14.682c3.611,1.607,7.234,3.381,10.836,5.309c-27.852,20.82-45.873,46.773-61.961,69.941 
      c-22.418,32.272-38.612,55.592-71.058,55.592c-2.009,0-4.09-0.088-6.231-0.264c10.624-71.404,45.938-128.484,57.204-145.242 
      C198.778,29.688,199.552,29.683,200.322,29.683z M83.571,75.701c21.39-19.967,48.144-34.277,76.704-41.215 
      c-16.465,28.652-38.163,74.389-47.548,128.982C90.537,147.617,65.38,118.793,83.571,75.701z M44.354,130.786 
      c1.519-3.414,3.15-6.779,4.895-10.094c0.915,4.799,2.234,9.52,3.96,14.139c12.088,32.377,40.379,52.406,55.591,61.219 
      c-0.654,9.672-0.84,19.303-0.548,28.762c-26.46-0.441-52.557-3.223-77.752-8.283C27.604,187.29,32.359,157.756,44.354,130.786z 
      M69.818,288.907c-2.943,3.579-5.339,7.495-7.178,11.717c-11.635-15.948-20.479-33.894-26.052-52.862 
      c24.227,4.182,49.111,6.424,74.187,6.678c0.554,3.955,1.199,7.906,1.931,11.828C99.568,268.702,81.578,274.605,69.818,288.907z 
      M130.784,355.646c-15.528-6.904-29.876-16.063-42.687-27.244c-1.059-8.738,0.472-15.68,4.558-20.658 
      c6.582-8.028,18.771-11.321,27.153-12.666c7.324,23.808,18.148,46.728,32.287,68.381 
      C144.818,361.331,137.693,358.722,130.784,355.646z M193.648,370.185c-19.319-23.783-33.777-49.438-43.082-76.426 
      c22.608,1.221,42.078,8.045,62.571,15.227c25.484,8.926,51.84,18.158,85.997,18.158c4.938,0,9.874-0.189,14.856-0.574 
      C281.376,355.896,238.354,371.788,193.648,370.185z M355.648,269.22c-3.43,7.703-7.519,15.278-12.173,22.555 
      c-15.463,3.785-29.923,5.625-44.119,5.625c-29.753,0-53.479-8.311-76.427-16.35c-23.997-8.41-48.813-17.107-79.65-17.107 
      c-0.267,0-0.534,0-0.802,0.002c-0.686-3.381-1.293-6.764-1.823-10.137c49.176-2.496,99.361-12.211,149.312-28.91 
      c35.29-11.799,62.965-24.643,80.103-33.42C371.438,218.101,366.516,244.771,355.648,269.22z" />
        </svg>
    </div>





    <script>
        // document.addEventListener('DOMContentLoaded', () => {
        //     const openBtn = document.getElementById('open-schools-btn');
        //     const closeBtn = document.getElementById('close-btn');
        //     const schoolsSection = document.getElementById('schools-section');

        //     // Open schools section
        //     openBtn.addEventListener('click', () => {
        //         schoolsSection.style.display = 'flex'; 
        //         setTimeout(() => {
        //             schoolsSection.classList.add('show'); 
        //         }, 10); 
        //     });

        //     // Close schools section
        //     closeBtn.addEventListener('click', () => {
        //         schoolsSection.classList.remove('show'); 
        //         setTimeout(() => {
        //             schoolsSection.style.display = 'none'; 
        //         }, 500); 
        //     });
        // });

        document.addEventListener('DOMContentLoaded', () => {
            const openBtn = document.getElementById('open-schools-btn');
            const closeBtn = document.getElementById('close-btn');
            const schoolsSection = document.getElementById('schools-section');

            // Open schools section
            openBtn.addEventListener('click', () => {
                const transitionLayer = document.getElementById('transition-layer');
                const cards = document.querySelectorAll('.school-card');

                // Immediately show section (behind transition)
                schoolsSection.classList.add('active');

                // Start the slide-up transition
                transitionLayer.classList.add('active');

                // After the slide-up, remove the transition layer
                setTimeout(() => {
                    transitionLayer.classList.remove('active');

                    // Fade in school cards with delay
                    cards.forEach((card, index) => {
                        card.style.animationDelay = `${0.15 * index}s`;
                        card.classList.add('fade-in-card');
                    });
                }, 700); // Matches slide duration
            });



            // Close schools section
            closeBtn.addEventListener('click', () => {
                schoolsSection.classList.remove('active');

                // Reset each card
                document.querySelectorAll('.school-card').forEach(card => {
                    card.classList.remove('fade-in-card');
                    card.style.animationDelay = '';
                });
            });
            document.querySelectorAll('.school-card').forEach(card => {
                card.addEventListener('click', (e) => {
                    e.preventDefault();

                    const href = card.getAttribute('href');
                    const transitionLayer = document.getElementById('transition-layer');
                    const loader = document.getElementById('loader');

                    // Clean previous state
                    transitionLayer.classList.remove('active', 'slide-down');
                    loader.classList.remove('show');

                    // Activate slide-down
                    transitionLayer.classList.add('slide-down', 'active');

                    // Show loader in sync with slide-down animation
                    setTimeout(() => {
                        loader.classList.add('show');
                        document.querySelectorAll('.basketball-line').forEach(path => {
                            path.classList.remove('animate'); // reset if already there
                            void path.offsetWidth; // force reflow
                            path.classList.add('animate');
                        });
                    }, 100); // not too early — midway into the transition



                    // Redirect after everything settles
                    setTimeout(() => {
                        window.location.href = href;
                    }, 2000); // Adjust to match total animation + pause

                });
            });

        });





        // document.addEventListener('DOMContentLoaded', () => {
        //     const openBtn = document.getElementById('open-schools-btn');
        //     const closeBtn = document.getElementById('close-btn');
        //     const schoolsSection = document.getElementById('schools-section');

        //     // Open schools section
        //     openBtn.addEventListener('click', () => {
        //         schoolsSection.classList.add('show');
        //     });

        //     // Close schools section
        //     closeBtn.addEventListener('click', () => {
        //         schoolsSection.classList.remove('show');
        //     });
        // });

        // const container = document.querySelector('.school-cards-container');

        // container.addEventListener('wheel', (event) => {
        //     event.preventDefault();
        //     container.scrollBy({
        //         left: event.deltaY < 0 ? -100 : 100,
        //         behavior: 'smooth'
        //     });
        // });
    </script>
</body>

</html>