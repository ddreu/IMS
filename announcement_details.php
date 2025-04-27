<?php
include_once 'connection/conn.php';
$conn = con();

// Get parameters
$school_id = isset($_GET['school_id']) ? intval($_GET['school_id']) : 0;
$announcement_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$school_id) {
    header('Location: index.php');
    exit();
}

// Fetch specific announcement
function getAnnouncement($conn, $id)
{
    $query = "SELECT a.title, a.message, a.image, a.created_at, d.department_name 
              FROM announcement a 
              LEFT JOIN departments d ON a.department_id = d.id 
              WHERE a.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    return $stmt->get_result()->fetch_assoc();
}

// Fetch all announcements except the current one
function getOtherAnnouncements($conn, $exclude_id, $school_id)
{
    $query = "SELECT id, title, image FROM announcement WHERE id != ? AND school_id = ? ORDER BY created_at DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ii", $exclude_id, $school_id);
    $stmt->execute();
    return $stmt->get_result();
}

$announcement = $announcement_id ? getAnnouncement($conn, $announcement_id) : null;
$other_announcements = getOtherAnnouncements($conn, $announcement_id, $school_id);

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
        body {
            background-color: #f4f4f9;
        }

        .card-custom {
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            border: none;
            border-radius: 10px;
        }

        .sidebar {
            max-height: 650px;
            overflow-y: auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            padding: 15px;
        }

        .sidebar-card {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            cursor: pointer;
            transition: background-color 0.3s;
            border: 1px solid #eee;
            border-radius: 8px;
            overflow: hidden;
            background: #fff;
        }

        .sidebar-card:hover {
            background-color: #f8f9fa;
        }

        .sidebar-image {
            width: 70px;
            height: 70px;
            object-fit: cover;
        }

        .sidebar-title {
            margin: 0 10px;
            font-size: 0.95rem;
            font-weight: 500;
            color: #333;
            line-height: 1.2;
            text-align: left;
            flex-grow: 1;
        }

        .announcement-image {
            width: 100%;
            max-height: 350px;
            object-fit: cover;
            border-radius: 10px;
        }

        .announcement-content {
            font-size: 1.1rem;
            line-height: 1.7;
        }
    </style>
</head>

<body>
    <div class="container py-5 mt-5">
        <div class="row g-4">
            <div class="col-lg-8">
                <div id="announcementCard" class="card card-custom p-4">
                    <?php if ($announcement): ?>
                        <h1 id="announcementTitle" class="mb-3 text-center"><?php echo htmlspecialchars($announcement['title']); ?></h1>
                        <p id="announcementMeta" class="text-center text-muted">
                            Posted on <?php echo (new DateTime($announcement['created_at']))->format('M d, Y'); ?>
                            | Department: <?php echo htmlspecialchars($announcement['department_name']); ?>
                        </p>

                        <?php if (!empty($announcement['image']) && file_exists('uploads/' . $announcement['image'])): ?>
                            <img id="announcementImage" src="uploads/<?php echo htmlspecialchars($announcement['image']); ?>" alt="Announcement Image" class="announcement-image my-3">
                        <?php else: ?>
                            <img id="announcementImage" src="https://via.placeholder.com/800x300?text=No+Image" alt="No Image" class="announcement-image my-3">
                        <?php endif; ?>

                        <div id="announcementMessage" class="announcement-content mt-3">
                            <?php echo nl2br(htmlspecialchars($announcement['message'])); ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-warning text-center">Announcement not found.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="sidebar">
                    <h5 class="mb-4 text-center">Other Announcements</h5>
                    <?php while ($item = $other_announcements->fetch_assoc()): ?>
                        <div class="sidebar-card" data-id="<?php echo $item['id']; ?>">
                            <?php if (!empty($item['image']) && file_exists('uploads/' . $item['image'])): ?>
                                <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>" class="sidebar-image" alt="Announcement Thumbnail">
                            <?php else: ?>
                                <img src="https://via.placeholder.com/70x70?text=No+Image" class="sidebar-image" alt="No Image">
                            <?php endif; ?>
                            <p class="sidebar-title"><?php echo htmlspecialchars($item['title']); ?></p>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include 'footerhome.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.sidebar-card').click(function() {
                var id = $(this).data('id');
                var schoolId = <?php echo $school_id; ?>;
                window.location.href = 'announcement_details.php?school_id=' + schoolId + '&id=' + id;
            });
        });
    </script>
</body>

</html>