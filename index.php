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