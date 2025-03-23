<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>IMS Download</title>
    <meta name="description" content="Download the latest version of our app">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="home.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body {
            background-color: #343a40;
            color: white;
        }

        .container-section {
            margin: 80px 0 80px;
            display: flex;
            flex-direction: row;
            justify-content: center;
            /* Center the content horizontally */
            align-items: center;
            /* Center vertically */
            gap: 20px;
            /* This will reduce the space between the hero-container and img-section */
        }

        .hero-container {
            padding: 20px;
            flex: 1;
            margin-left: 90px;
            /* Optional: to control left spacing */
        }

        .img-section {
            flex: 1;
            display: flex;
            justify-content: flex-start;
        }

        .hero-container h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        .hero-container p {
            font-size: 1.2rem;
            margin-bottom: 20px;
        }

        .download-btn {
            background-color: #007bff;
            color: white;
            padding: 15px 30px;
            font-size: 1rem;
            border-radius: 5px;
            transition: background-color 0.3s, transform 0.3s;
            text-decoration: none;
        }

        .download-btn:hover {
            background-color: #0056b3;
            transform: scale(1.05);
        }

        .graphic {
            margin-bottom: 0;
            width: 100%;
            max-width: 500px;
            max-height: 500px;
            object-fit: contain;
        }
    </style>
</head>

<body>
    <?php include 'navbarhome.php'; ?>
    <div class="container-section">
        <div class="hero-container">
            <h1>Download Our App!</h1>
            <p>Get the latest version of our app and enjoy all the features!</p>
            <a href="app/IMS.V.1.1.0.apk" class="btn download-btn">
                <i class="fas fa-download"></i> Download APK
            </a>
        </div>
        <div class="img-section">
            <img src="assets/img/app-download.svg" alt="App Graphic" class="graphic">
        </div>
    </div>
    <div class="mt-1">
        <?php include 'footerhome.php'; ?>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
</body>

</html>