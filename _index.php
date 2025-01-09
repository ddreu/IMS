<?php
include_once 'connection/conn.php';
$conn = con();

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
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: #000;
            color: #fff;
            overflow-x: hidden;
            min-height: 100vh;
        }

        .hero-section {
            height: 100vh;
            width: 100%;
            background: linear-gradient(rgba(0,0,0,0.7), rgba(0,0,0,0.7)), url('images/index.png');
            background-size: cover;
            background-position: center;
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .hero-content {
            text-align: left;
            padding: 2rem;
            width: 100%;
            max-width: 1200px;
            margin-bottom: 200px;
        }

        .year-text {
            font-size: 5rem;
            font-weight: 700;
            line-height: 1;
            background: linear-gradient(45deg, #ff3366, #ff6b6b);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            margin-bottom: 0.5rem;
        }

        .main-title {
            font-size: 3rem;
            font-weight: 700;
            margin-bottom: 1rem;
            line-height: 1.2;
        }

        .schools-grid-container {
            position: absolute;
            bottom: 0; /* Align to the bottom */
            left: 50%;
            transform: translateX(-50%);
            width: 90%;
            max-width: 1400px;
            border-radius: 20px 20px 0 0; /* Only round top corners */
            overflow: hidden;
        }

        .schools-grid {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(30px);
            -webkit-backdrop-filter: blur(30px);
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            padding: 1.5rem;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .school-card {
            position: relative;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 15px;
            padding: 1.5rem;
            text-decoration: none;
            color: white;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            flex-direction: column;
            align-items: center;
            text-align: center;
            gap: 1rem;
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.1);
            overflow: hidden;
            height: 180px;
        }

        .school-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(45deg, rgba(255, 255, 255, 0.1), rgba(255, 255, 255, 0));
            border-radius: 15px;
            opacity: 0;
            transition: opacity 0.4s ease;
        }

        .school-card:hover {
            transform: translateY(-3px) scale(1.02);
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.2);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .school-card:hover::before {
            opacity: 1;
        }

        .school-logo {
            width: 110px;
            height: 110px;
            object-fit: contain;
            border-radius: 10px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.08);
            transition: all 0.3s ease;
            filter: drop-shadow(0 2px 4px rgba(0, 0, 0, 0.1));
        }

        .school-card:hover .school-logo {
            transform: scale(1.05);
            background: rgba(255, 255, 255, 0.12);
        }

        .school-name {
            font-size: 1rem;
            font-weight: 500;
            line-height: 1.3;
            letter-spacing: 0.3px;
            position: relative;
            z-index: 1;
        }

        .navbar {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            padding: 1rem 2rem;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(10px);
            z-index: 1000;
            height: 60px;
        }

        .navbar-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            max-width: 1400px;
            margin: 0 auto;
            height: 100%;
        }

        .logo {
            font-size: 1.25rem;
            font-weight: 700;
            color: white;
            margin-left: 3rem;
        }

        .login-btn {
            background: linear-gradient(45deg, #ff3366, #ff6b6b);
            color: white;
            padding: 0.5rem 1.25rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 0.9rem;
            margin-right: 3rem;
        }

        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255, 51, 102, 0.3);
        }

        @media (max-width: 1400px) {
            .schools-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (max-width: 1024px) {
            .schools-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .year-text {
                font-size: 4rem;
            }
            .main-title {
                font-size: 2.5rem;
            }
        }

        @media (max-width: 768px) {
            .schools-grid {
                grid-template-columns: repeat(2, 1fr);
                padding: 1rem;
            }
            .school-card {
                height: 150px;
                padding: 1rem;
            }
            .school-logo {
                width: 50px;
                height: 50px;
            }
            .year-text {
                font-size: 3rem;
            }
            .main-title {
                font-size: 2rem;
            }
        }

        @media (max-width: 480px) {
            .schools-grid {
                grid-template-columns: 1fr;
            }
            .schools-grid-container {
                width: 95%;
            }
        }
    </style>
</head>

<body>
    <header>
        <nav class="navbar">
            <div class="navbar-container">
                <div class="logo">IMS</div>
                <a href="login.php" class="login-btn">Login</a>
            </div>
        </nav>
    </header>

    <main class="hero-section">
        <div class="hero-content">
            <div class="year-text">INTRAMURALS</div>
            <h1 class="main-title">MANAGEMENT<br>SYSTEM</h1>
        </div>
        
        <div class="schools-grid-container">
            <div class="schools-grid">
                <?php
                if ($result && $result->num_rows > 0) {
                    while ($row = $result->fetch_assoc()) {
                        // Get the logo path from uploads directory
                        $logoPath = !empty($row['logo']) ? "uploads/logos/" . $row['logo'] : "assets/default-logo.png";
                        
                        echo "<a href='home.php?school_id=" . $row['school_id'] . "' class='school-card'>";
                        echo "<img src='" . htmlspecialchars($logoPath) . "' alt='" . htmlspecialchars($row['school_name']) . " Logo' class='school-logo'>";
                        echo "<span class='school-name'>" . htmlspecialchars($row['school_name']) . "</span>";
                        echo "</a>";
                    }
                } else {
                    echo "<div class='no-schools'><p>No schools available</p></div>";
                }
                ?>
            </div>
        </div>
    </main>
</body>
</html>