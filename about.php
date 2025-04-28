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


// Close the connection after use
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>About IMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">

    <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">

    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="about.css">
    <link rel="stylesheet" href="footer.css">
</head>

<body>


    <!-- <header class="navbar">
        <div class="logo">IMS</div>
        <nav class="nav-links">
            <a href="index.php">Home</a>
            <a href="about.php">About</a>
            <a href="contact.php">Contact</a>
        </nav>
        <?php if (!$isLoggedIn): ?>
            <a href="login.php" class="login-btn">Log In</a>
        <?php endif; ?>
    </header> -->

    <header class="navbar">
        <div class="logo">IMS</div>
        <nav class="nav-links">
            <a href="index.php">Home</a>
            <a href="about.php">About</a>
            <a href="contact.php">Contact</a>
        </nav>
        <div class="social-icons d-flex gap-2 me-3">
            <!-- <a href="https://facebook.com" class="text-white small-icon" target="_blank" rel="noopener noreferrer">
                <i class="fab fa-facebook-f"></i>
            </a> -->
            <a href="mailto:andrewbucedeguzman@gmail.com" class="text-white small-icon">
                <i class="fas fa-envelope"></i>
            </a>
            <a href="https://github.com/ddreu" class="text-white small-icon" target="_blank" rel="noopener noreferrer">
                <i class="fab fa-github"></i>
            </a>
        </div>
        <?php if (!$isLoggedIn): ?>
            <a href="login.php" class="login-btn">Log In</a>
        <?php endif; ?>
    </header>


    <section class="about-hero">
        <div class="hero-text" data-aos="fade-up" data-aos-delay="200">
            <h1 data-aos="fade-down">Manage. <span>Compete.</span> <span>Celebrate.</span></h1>
            <h2 data-aos="fade-up" data-aos-delay="200">The Future of Intramurals Management</h2>

        </div>

        <!-- Floating Box Below Hero -->
        <div class="hero-floating-box" data-aos="fade-up" data-aos-duration="1200" data-aos-delay="300">
            <div class="floating-box-content">

                <!-- Column 1 -->
                <div class="floating-column" data-aos="zoom-in" data-aos-delay="400" data-aos-duration="800">

                    <div class="floating-icon-wrapper">
                        <i class="fas fa-trophy"></i>
                    </div>
                    <h3>Live Leaderboards</h3>
                    <p>Track top teams and players in real-time with dynamic stats.</p>
                </div>

                <!-- Divider -->
                <div class="vertical-line" data-aos="fade" data-aos-delay="500" data-aos-duration="700"></div>

                <!-- Column 2 -->
                <div class="floating-column" data-aos="zoom-in" data-aos-delay="600" data-aos-duration="800">
                    <div class="floating-icon-wrapper">

                        <i class="fas fa-video"></i>
                    </div>
                    <h3>Live Streams</h3>
                    <p>Broadcast your matches and events for students and supporters.</p>
                </div>

                <!-- Divider -->
                <div class="vertical-line" data-aos="fade" data-aos-delay="700" data-aos-duration="700"></div>

                <!-- Column 3 -->
                <div class="floating-column" data-aos="zoom-in" data-aos-delay="800" data-aos-duration="800">
                    <div class="floating-icon-wrapper">

                        <i class="fas fa-calendar-alt"></i>
                    </div>
                    <h3>Event Scheduling</h3>
                    <p>Manage match times, venues, and event days seamlessly.</p>
                </div>

            </div>
        </div>


    </section>


    <section class="about-intro" data-aos="fade-up">
        <h2>About IMS</h2>
        <p>IMS is a powerful multi-school platform designed for managing intramurals events. Event managers can effortlessly organize, schedule, track scores, stream live games, and showcase team and player leaderboards — all in real time. Every school gets its own customized portal where students can stay updated and engaged with their intramurals.</p>
    </section>

    <section class="about-features-section" data-aos="fade-up">
        <div class="container">
            <div class="row align-items-center">

                <!-- Left Column: Image -->
                <div class="col-md-5">
                    <div class="triangle-background" data-aos="fade-up" data-aos-duration="2000"></div>




                    <img src="assets/img/12.png" alt="Intramurals Event" class="img-fluid feature-side-image" />
                </div>

                <!-- Right Column: Cards -->
                <div class="col-md-7">
                    <div class="row g-4">

                        <!-- Card 1 -->
                        <div class="col-md-6">
                            <div class="feature-card" data-aos="flip-up" data-aos-delay="100">
                                <div class="row align-items-center">
                                    <div class="col-3 text-center">
                                        <i class="fas fa-trophy fa-2x feature-icon"></i>
                                    </div>
                                    <div class="col-9">
                                        <h3>Live Scores</h3>
                                        <p>Real-time updates on matches and standings.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 2 -->
                        <div class="col-md-6">
                            <div class="feature-card" data-aos="flip-up" data-aos-delay="200">
                                <div class="row align-items-center">
                                    <div class="col-3 text-center">
                                        <i class="fas fa-video fa-2x feature-icon"></i>
                                    </div>
                                    <div class="col-9">
                                        <h3>Live Streams</h3>
                                        <p>Watch games from anywhere, live and direct.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 3 -->
                        <div class="col-md-6">
                            <div class="feature-card" data-aos="flip-up" data-aos-delay="300">
                                <div class="row align-items-center">
                                    <div class="col-3 text-center">
                                        <i class="fas fa-chart-line fa-2x feature-icon"></i>
                                    </div>
                                    <div class="col-9">
                                        <h3>Leaderboards</h3>
                                        <p>Top teams and players ranked live with stats.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Card 4 -->
                        <div class="col-md-6">
                            <div class="feature-card" data-aos="flip-up" data-aos-delay="400">
                                <div class="row align-items-center">
                                    <div class="col-3 text-center">
                                        <i class="fas fa-calendar-alt fa-2x feature-icon"></i>
                                    </div>
                                    <div class="col-9">
                                        <h3>Event Scheduling</h3>
                                        <p>Plan games, matches, and ceremonies easily.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                    </div> <!-- End of inner row -->
                </div>

            </div> <!-- End of main row -->
        </div> <!-- End of container -->
    </section>

    <section class="about-value-section">
        <div class="container">
            <div class="row align-items-center">

                <!-- Text Column -->
                <div class="col-md-6" data-aos="fade-right" data-aos-delay="100">
                    <h2 class="value-heading">Empowering Schools, Streamlining Events</h2>
                    <p class="value-paragraph">
                        Managing intramurals can be chaotic — scattered schedules, unclear scores, disconnected teams.
                        IMS centralizes everything in one sleek platform: live updates, player stats, match streams, and organized event management.
                        No more confusion. Just smooth, winning experiences for every student, coach, and event organizer.
                    </p>
                </div>

                <!-- Image Column -->
                <div class="col-md-6" data-aos="fade-left" data-aos-delay="300">
                    <img src="assets/img/14.png" alt="IMS Value Proposition" class="img-fluid value-side-image">
                </div>

            </div>
        </div>
    </section>

    <section class="about-contact-cta" data-aos="fade-up">
        <div class="container text-center">
            <h2 class="contact-heading">Got Questions? Let's Connect!</h2>
            <p class="contact-subheading">We're here to help. Whether it's setting up your intramurals, technical support, or learning more about IMS — just reach out.</p>
            <a href="contact.php" class="contact-cta-btn">Contact Us</a>
        </div>
    </section>


    <?php include 'footerhome.php'; ?>
    <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
    <script>
        // AOS.init({
        //     duration: 900,
        //     easing: 'ease-in-out',
        //     once: true,
        //     offset: 150
        // });

        AOS.init({
            duration: 1000,
            easing: 'ease-in-out-cubic',
            offset: 150,
            once: true
        });
    </script>

</body>

</html>