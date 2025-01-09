<?php
include_once 'connection/conn.php';
$conn = con();


// Check if school_id exists and is not empty in URL params
if (!isset($_GET['school_id']) || empty($_GET['school_id']) || $_GET['school_id'] === '0') {
    header('Location: index.php');
    exit();
}
$announcement_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

$announcement = null;

if ($announcement_id > 0) {
    $query = "SELECT a.title, a.message, a.image, a.created_at, d.department_name 
              FROM announcement a 
              LEFT JOIN departments d ON a.department_id = d.id 
              WHERE a.id = ?";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $announcement_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $announcement = $result->fetch_assoc();
        $date = new DateTime($announcement['created_at']);
        $formatted_date = $date->format('M d, Y');
    }
}

include 'navbarhome.php';

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Announcement Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <link href="home.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
       <style>
    body {
        font-family: 'Arial', sans-serif;
        background-color: #f4f4f9;
    }

    .announcement-header-section {
        background-color: #007bff;
        color: #fff;
        padding: 15px;
        border-radius: 5px;
        text-align: left;
    }

    .announcement-title-text {
    font-size: 3rem; /* Increased font size */
    font-weight: 600;
    text-align: center; /* Center the title */
    margin-bottom: 20px; /* Add some space below the title */
}


    .announcement-meta-text {
        font-size: 1rem;
        color: black;
        text-align: center;
    }

    .announcement-content-section {
        background-color: #fff;
        padding: 20px;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        margin-top: 20px;
    }

    .announcement-content-section p {
    font-size: 1.10rem; /* Increased font size for the p element */
    line-height: 1.6; /* Slightly increased line height for better readability */
}



    .announcement-footer-text {
        margin-top: 20px;
        text-align: center;
        font-size: 0.85rem;
        color: #6c757d;
    }

    .announcement-image-responsive {
        max-width: 60%;  /* Make image responsive */
        height: auto;
        border-radius: 5px;
    }

    .announcement-back-button {
        margin-top: 10px;
        font-size: 1rem;
        color: #007bff;
    }
</style>
</head>
<body>
<div class="mt-5">
    <div class="container py-5">
        <?php if ($announcement): ?>
            <div class="row">
                <!-- Left Column: Title, Meta, Back Button -->
                <div class="col-md-6 mt-5">
                    <div class="announcement-header-section mt-4">
                        <h1 class="announcement-title-text"><?php echo htmlspecialchars($announcement['title']); ?></h1>
                        <p class="announcement-meta-text">Posted on <?php echo $formatted_date; ?> | Department: <?php echo htmlspecialchars($announcement['department_name']); ?></p>
                    </div>

                    <!-- Back to Announcements Button (if needed)
                    <div class="announcement-back-button text-start">
                        <a href="home.php" class="btn btn-secondary">Back to Announcements</a>
                    </div>-->
                </div>

                <!-- Right Column: Image -->
                <div class="col-md-6">
                    <?php if (!empty($announcement['image']) && file_exists('uploads/' . $announcement['image'])): ?>
                        <div class="text-center">
                            <img src="uploads/<?php echo htmlspecialchars($announcement['image']); ?>" class="announcement-image-responsive img-fluid" alt="<?php echo htmlspecialchars($announcement['title']); ?>">
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Content Section Below -->
            <div class="announcement-content-section mt-4 text-center">
                <p><?php echo nl2br(htmlspecialchars($announcement['message'])); ?></p>
            </div>

        <?php else: ?>
            <div class="container mt-5"><h5 class="text-muted">Announcement not found.</h5></div>
        <?php endif; ?>
    </div>
        </div>
        <?php include 'footerhome.php' ?>

        <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script></body>
</html>
