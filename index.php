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
    <style>
        /* General Reset */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

body {
    background: #111; /* Dark theme */
    color: #fff;
    overflow-x: hidden;
    min-height: 100vh;
}

/* Navbar Styling */
.navbar {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    padding: 1rem 2rem;
    background: transparent; /* Transparent background */
    z-index: 1000;
    display: flex;
    justify-content: space-between;
    align-items: center;
}


.navbar .logo {
    padding: 0.75rem 2rem;

    font-size: 1.5rem;
    font-weight: bold;
    color: white; /* Ensure text is still visible */
}

.navbar .login-btn {
    padding: 0.75rem 2rem;
    font-size: 1rem;
    font-weight: bold;
    color: #fff;
    background: #6200ea;
    border-radius: 50px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.navbar .login-btn:hover {
    background: #4500b5;
}

/* Hero Section */
.hero-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 5rem 2rem;
    height: 100vh;
    background: url('images/index.png') no-repeat center center / cover;
    color: white;
}

.hero-content {
    margin-left: 2rem;
    max-width: 50%;
    color: white;
}

.hero-content h1 {
    font-size: 3rem;
    line-height: 1.2;
    font-weight: 700;
    margin-bottom: 1rem;
}

.hero-content p {
    font-size: 1.1rem;
    line-height: 1.6;
    margin-bottom: 2rem;
}

.hero-content .cta-btn {
    padding: 0.75rem 2rem;
    font-size: 1rem;
    font-weight: bold;
    color: #fff;
    background: #6200ea;
    border-radius: 50px;
    text-decoration: none;
    transition: all 0.3s ease;
}

.hero-content .cta-btn:hover {
    background: #4500b5;
}

/* Schools Section */
.schools-section {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    background: #4500b500;
    padding: 1rem;
    border-radius: 15px;
    max-width: 400px;
    box-shadow: 0 8px 15px rgba(0, 0, 0, 0.8);
}

.schools-section h2 {
    font-size: 1.8rem;
    font-weight: bold;
    text-align: center;
    margin-bottom: 1.5rem;
    color: #fff;
    text-transform: uppercase;
    letter-spacing: 1.5px;
    text-shadow: 0 3px 6px rgba(0, 0, 0, 0.7);
}

/* Grid Layout */
.school-cards-container {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 1.5rem;
    justify-content: center;
}

/* Individual School Card */
.school-card {
    background: rgba(255, 255, 255, 0.1);
    border-radius: 15px;
    text-align: center;
    padding: 1.5rem;
    transition: transform 0.3s ease, box-shadow 0.3s ease, background 0.3s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.8rem;
}

.school-card:hover {
    transform: scale(1.1);
    background: rgba(255, 255, 255, 0.2);
}

/* School Logo */
.school-logo {
    width: 90px;
    height: 90px;
    object-fit: cover;
    margin-bottom: 0.8rem;
    border-radius: 50%;
    border: 3px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.5), 0 0 8px rgba(255, 255, 255, 0.25);
    transition: transform 0.3s ease;
}

.school-logo:hover {
    transform: scale(1.08);
}

/* School Name */
.school-name {
    font-size: 1.2rem;
    font-weight: bold;
    color: #f5f5f5;
    text-transform: capitalize;
    text-shadow: 0 3px 6px rgba(0, 0, 0, 0.8);
}

/* View Button */
.view-btn {
    padding: 0.5rem 1.2rem;
    font-size: 0.9rem;
    font-weight: bold;
    color: #fff;
    background: linear-gradient(135deg, #ff5722, #ff9800);
    border-radius: 30px;
    text-decoration: none;
    transition: all 0.3s ease;
    box-shadow: 0 3px 8px rgba(0, 0, 0, 0.3), 0 0 8px rgba(255, 87, 34, 0.5);
}

.view-btn:hover {
    background: linear-gradient(135deg, #e64a19, #ff5722);
    box-shadow: 0 6px 12px rgba(0, 0, 0, 0.5), 0 0 12px rgba(255, 87, 34, 0.8);
}

/* Responsive Design */
@media (max-width: 768px) {
    .schools-section {
        right: 1rem;
        left: 1rem;
        padding: 1.5rem;
    }

    .school-cards-container {
        grid-template-columns: 1fr;
    }

    .school-card {
        padding: 1rem;
    }
}

/* Fade-in Animation */
@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

    </style>
</head>
<body>
   <!-- Navbar -->
<header class="navbar">
    <div class="logo">IMS</div>
    <?php if (!$isLoggedIn): // Show the login button only if the user is not logged in ?>
        <a href="login.php" class="login-btn">Log In</a>
    <?php endif; ?>
</header>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <h1>Welcome to <br>Intramurals Management System</h1>
            <p>Manage all aspects of your school's intramurals with ease. From scheduling matches to tracking scores, our system makes event management simple and efficient.</p>
            <!--<a href="#schools" class="cta-btn">Select Your School</a>-->
        </div>
    </section>

<!-- Schools Section -->
<section id="schools" class="schools-section">
    <div class="school-cards-container">
        <?php
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $logoPath = !empty($row['logo']) ? "uploads/logos/" . $row['logo'] : "assets/default-logo.png";
                echo "
                <a href='home.php?school_id=" . htmlspecialchars($row['school_id']) . "' class='school-card'>
                    <div class='card-content'>
                        <img src='" . htmlspecialchars($logoPath) . "' alt='" . htmlspecialchars($row['school_name']) . " Logo' class='school-logo'>
                        <h3 class='school-name'>" . htmlspecialchars($row['school_name']) . "</h3>
                    </div>
                </a>";
            }
        } else {
            echo "<p class='no-schools'>No schools available at the moment. Please check back later.</p>";
        }
        ?>
    </div>
</section>


</body>
</html>
