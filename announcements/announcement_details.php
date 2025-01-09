<?php
session_start();
include_once '../connection/conn.php';
$conn = con();

$announcement_id = isset($_GET['id']) ? $_GET['id'] : null;

$status = isset($_GET['status']) ? $_GET['status'] : '';
$message = isset($_GET['message']) ? $_GET['message'] : '';
// Initialize variables for the form
$title = '';
$image = '';
$message = '';
$department_name = '';
$school_name = '';

if ($announcement_id) {
    // Fetch the announcement details
    $sql = "SELECT a.*, d.department_name, s.school_name 
            FROM announcement a 
            LEFT JOIN departments d ON a.department_id = d.id 
            LEFT JOIN schools s ON a.school_id = s.school_id 
            WHERE a.id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $announcement_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if ($announcement = mysqli_fetch_assoc($result)) {
        // Populate the form fields with the announcement data
        $title = htmlspecialchars($announcement['title']);
        $image = htmlspecialchars($announcement['image']);
        $message = htmlspecialchars($announcement['message']);
        $department_name = htmlspecialchars($announcement['department_name']);
        $school_name = htmlspecialchars($announcement['school_name']);
    }
}

include '../navbar/navbar.php';
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $announcement_id ? 'Edit Announcement' : 'Add Announcement'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script src="https://kit.fontawesome.com/ad90023682.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="../super_admin/sastyles.css">
    <style>
        body {
            background-color: #f0f2f5;
            font-family: Arial, sans-serif;
        }

        .announcement-card {
            margin-top: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .announcement-image img {
            width: 100%;
            height: 300px;
            object-fit: cover;
            border-radius: 10px 10px 0 0;
        }

        .announcement-card .card-body {
            padding: 20px;
        }

        .announcement-card .card-body h2 {
            font-weight: bold;
            margin-bottom: 10px;
        }

        .announcement-card .card-body p {
            margin-bottom: 20px;
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            padding: 10px 20px;
            border-radius: 5px;
        }
    </style>
</head>
<?php
$current_page = 'dashboard';

include '../super_admin/sa_sidebar.php';
?>

<body>
    <div class="container mt-5">
        <!--<h1 class="text-center"><?php echo $announcement_id ? 'Edit Announcement' : 'Add Announcement'; ?></h1>-->
        <h1 class="text-center">Announcement</h1>

        <form action="process_announcement.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="announcement_id" value="<?php echo $announcement_id; ?>">
            <input type="hidden" name="department_id" value="<?php echo $department_id; ?>"> <!-- Hidden field for department ID -->
            <input type="hidden" name="school_id" value="<?php echo $school_id; ?>"> <!-- Hidden field for school ID -->
            <div class="row">
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" value="<?php echo $title; ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-3">
                        <label for="image" class="form-label">Image Upload</label>
                        <input type="file" class="form-control" id="image" name="image" onchange="previewImage(event)">
                        <div class="image-preview mt-2" style="border: 2px dashed #007bff; padding: 10px; border-radius: 5px; display: flex; justify-content: center; align-items: center; max-height: 150px; overflow: hidden;">
                            <?php if ($image): ?>
                                <img src="<?php echo $image; ?>" alt="Current Image" class="img-fluid" style="max-height: 150px; max-width: 100%; object-fit: contain;">
                            <?php else: ?>
                                <p class="text-muted">No image uploaded</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mb-3">
                <label for="message" class="form-label">Content</label>
                <textarea class="form-control" id="message" name="message" rows="5" required><?php echo $message; ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary" style="padding: 0.375rem 0.75rem;" onclick="confirmSave(event)">Save</button>
            <a href="sa_announcement.php" class="btn btn-secondary" style="padding: 0.375rem 0.75rem;" onclick="confirmCancel(event)">Cancel</a>
        </form>
    </div>

    <script>
        function previewImage(event) {
            const imagePreview = document.querySelector('.image-preview');
            imagePreview.innerHTML = ''; // Clear previous content
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.alt = 'Uploaded Image';
                    img.className = 'img-fluid';
                    img.style.maxHeight = '150px';
                    img.style.maxWidth = '100%';
                    img.style.objectFit = 'contain';
                    imagePreview.appendChild(img);
                }
                reader.readAsDataURL(file);
            } else {
                imagePreview.innerHTML = '<p class="text-muted">No image uploaded</p>';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            <?php if ($status === 'success'): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Success',
                    text: '<?php echo $message; ?>',
                    confirmButtonText: 'OK'
                });
            <?php elseif ($status === 'error'): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: '<?php echo $message; ?>',
                    confirmButtonText: 'OK'
                });
            <?php endif; ?>
        });

        function confirmSave(event) {
            event.preventDefault(); // Prevent the default form submission
            Swal.fire({
                title: 'Are you sure?',
                text: "Do you want to save this announcement?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, save it!',
                cancelButtonText: 'No, cancel!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // If confirmed, submit the form
                    event.target.closest('form').submit();
                }
            });
        }

        function confirmCancel(event) {
            event.preventDefault(); // Prevent the default action
            Swal.fire({
                title: 'Are you sure?',
                text: "Do you want to cancel the changes?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, cancel it!',
                cancelButtonText: 'No, keep editing!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // If confirmed, redirect to the announcements page
                    window.location.href = event.target.href;
                }
            });
        }
    </script>
</body>

</html>