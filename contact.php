<?php
include_once 'connection/conn.php';
$conn = con();
session_start(); // Start session

$isLoggedIn = isset($_SESSION['user_id']);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact IMS</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.4/dist/aos.css" rel="stylesheet">

    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="about.css">
    <link rel="stylesheet" href="contact.css">
    <link rel="stylesheet" href="footer.css">
</head>

<body>

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


    <section class="contact-hero">
        <div class="hero-text" data-aos="fade-up">
            <h1>Let's Talk</h1>
            <h2 data-aos="fade-up" data-aos-delay="200">Have Questions? Need Help? We're Here.</h2>
        </div>
    </section>

    <section class="contact-intro" data-aos="fade-up">
        <div class="container text-center">
            <h2 class="intro-heading">Reach Out to Us</h2>
            <p class="intro-subheading">Whether it's setting up your intramurals, solving issues, or simply learning more about IMS — we’re just one message away.</p>
        </div>
    </section>


    <section class="contact-form-section" data-aos="fade-up">
        <div class="container">
            <div class="row align-items-center">

                <!-- Left side: Image -->
                <div class="col-md-6 mb-5 mb-md-0" data-aos="fade-right">
                    <img src="assets/img/11.png" alt="Contact IMS" class="img-fluid contact-side-image" data-aos="zoom-in" data-aos-delay="100">
                </div>

                <!-- Right side: Form -->
                <div class="col-md-6" data-aos="fade-left">
                    <div class="form-wrapper">
                        <h2 class="form-heading">Get In Touch</h2>
                        <form action="process_contact.php" method="POST" class="form">
                            <div class="row">
                                <div class="col-md-6 mb-3" data-aos="fade-up" data-aos-delay="100">
                                    <input type="text" name="name" class="form-control" placeholder="Your Name" required>
                                </div>
                                <div class="col-md-6 mb-3" data-aos="fade-up" data-aos-delay="200">
                                    <input type="email" name="email" class="form-control" placeholder="Your Email" required>
                                </div>
                            </div>
                            <div class="mb-3" data-aos="fade-up" data-aos-delay="300">
                                <textarea name="message" class="form-control" rows="5" placeholder="Your Message..." required></textarea>
                            </div>
                            <div data-aos="fade-up" data-aos-delay="400">
                                <button type="submit" class="send-btn">Send Message</button>
                            </div>
                        </form>

                        <p class="form-note">We'll get back to you within 24 hours.</p>
                    </div>
                </div>

            </div>
        </div>
    </section>


    <?php include 'footerhome.php'; ?>
    <script src="https://unpkg.com/aos@2.3.4/dist/aos.js"></script>
    <script>
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true,
            offset: 120
        });
    </script>

</body>

</html>