<?php
session_start();
include_once '../connection/conn.php';
$conn = con();
// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php'); // Redirect to login page if not logged in
    exit();
}

// Fetch the logged-in user's information
$user_id = $_SESSION['user_id'];
$email = $_SESSION['email'];
$role = $_SESSION['role'];

$sql = "SELECT role, school_id FROM users WHERE id = ?";

$stmt_select = mysqli_prepare($conn, $sql);

if ($stmt_select === false) {
    die('mysqli_prepare() failed: ' . htmlspecialchars(mysqli_error($conn)));
}

mysqli_stmt_bind_param($stmt_select, "i", $user_id);
mysqli_stmt_execute($stmt_select);
$result = mysqli_stmt_get_result($stmt_select);

if (mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);

    // Check if the logged-in user is a school admin or super admin
    if ($user['role'] === 'Committee') {
        header('Location: 404.php'); // Redirect if the role is not admin
        exit();
    }

    // Get the school_id for filtering announcements
    $school_id = $user['school_id'];
} else {
    // If the user is not found in the database
    header('Location: ims/login.php');
    exit();
}

// Initialize variables for success and error messages
$successMessage = '';
$errorMessage = '';

// Success/Error Messages Logic
if (isset($_SESSION['success_message'])) {
    $successMessage = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $errorMessage = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Fetch announcements specific to the logged-in school admin's school with department information
$announcements_query = "SELECT a.*, d.department_name 
                       FROM announcement a 
                       LEFT JOIN departments d ON a.department_id = d.id 
                       WHERE a.school_id = ? 
                       ORDER BY a.id DESC";
$stmt_announcements = mysqli_prepare($conn, $announcements_query);

if ($stmt_announcements === false) {
    die('mysqli_prepare() failed: ' . htmlspecialchars(mysqli_error($conn)));
}

mysqli_stmt_bind_param($stmt_announcements, "i", $school_id);
mysqli_stmt_execute($stmt_announcements);
$announcements_result = mysqli_stmt_get_result($stmt_announcements);

if (!$announcements_result) {
    die('Query failed: ' . mysqli_error($conn));
}
// Fetch departments only if school_id is available
$departments = [];
if ($school_id) {
    $department_result = mysqli_query($conn, "SELECT id, department_name FROM departments WHERE school_id = $school_id");

    // If the query is successful, fetch departments
    if ($department_result) {
        $departments = mysqli_fetch_all($department_result, MYSQLI_ASSOC);
    }
}
include '../navbar/navbar.php';

?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Announcement</title>

    <!-- Bootstrap CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <!-- Font Awesome CDN for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../styles/dashboard.css">
    <!-- SweetAlert2 CDN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <!-- Custom CSS for table styling -->
    <style>
        .announcement-table {
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            border-radius: 10px;
            overflow: hidden;
        }
        
        .announcement-table .table {
            margin-bottom: 0;
        }
        
        .announcement-table thead th {
            background-color: #2c3e50;
            color: white;
            font-weight: 500;
            text-transform: uppercase;
            font-size: 0.9rem;
            padding: 15px;
            border: none;
        }
        
        .announcement-table tbody td {
            padding: 15px;
            vertical-align: middle;
            border-color: #eef2f7;
        }
        
        .announcement-table .btn-group {
            display: flex;
            gap: 5px;
            justify-content: center;
        }
        
        .announcement-table .btn {
            padding: 0.4rem 0.8rem;
            border-radius: 6px;
            transition: all 0.3s ease;
        }
        
        .announcement-table .btn:hover {
            transform: translateY(-2px);
        }
        
        .announcement-table .btn-info {
            background-color: #3498db;
            border-color: #3498db;
            color: white;
        }
        
        .announcement-table .btn-warning {
            background-color: #f1c40f;
            border-color: #f1c40f;
            color: white;
        }
        
        .announcement-table .btn-danger {
            background-color: #e74c3c;
            border-color: #e74c3c;
        }
        
        .announcement-table tbody tr:hover {
            background-color: #f8f9fa;
        }
        
        .announcement-table .title-cell {
            font-weight: 500;
            color: #2c3e50;
            max-width: 400px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }
        
        .card.box {
            border: none;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        
        .card-body {
            padding: 1.5rem;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php
        $current_page = 'adminannouncement';
        include '../department_admin/sidebar.php';
        ?>


        <!-- Page Content -->
        <div id="content">


            <!-- Page Header and Action Button -->
            <div class="container mt-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>List of Announcements</h2>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addAnnouncementModal">
                            <i class="fas fa-plus"></i> Add Announcement
                        </button>
                </div>
            </div>

            <div class="card box">
                <div class="card-body">
                    <div class="table-responsive">
                        <div class="container-fluid p-0">
                            <div class="card shadow-sm">
                                <div class="card-body p-0">
                                    <table class="table table-hover align-middle mb-0">
                                        <thead class="bg-light">
                                            <tr>
                                                <th class="px-4 py-3" style="width: 50%">Title</th>
                                                <th class="px-4 py-3" style="width: 20%">Department</th>
                                                <th class="px-4 py-3 text-center" style="width: 30%">Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (mysqli_num_rows($announcements_result) > 0): ?>
                                                <?php while ($row = mysqli_fetch_assoc($announcements_result)): ?>
                                                    <tr>
                                                        <td class="px-4 text-break">
                                                            <div class="d-flex align-items-center gap-3">
                                                                <?php if (!empty($row["image"])): ?>
                                                                    <div style="width: 40px; height: 40px; overflow: hidden;" class="rounded-circle bg-light d-flex align-items-center justify-content-center">
                                                                        <img src="<?= htmlspecialchars($row["image"]) ?>" alt="Announcement Image" class="img-fluid" style="object-fit: cover; width: 100%; height: 100%;">
                                                                    </div>
                                                                <?php else: ?>
                                                                    <div style="width: 40px; height: 40px;" class="rounded-circle bg-secondary d-flex align-items-center justify-content-center">
                                                                        <i class="fas fa-bullhorn text-white"></i>
                                                                    </div>
                                                                <?php endif; ?>
                                                                <div>
                                                                    <div class="fw-medium"><?= htmlspecialchars($row["title"]) ?></div>
                                                                    <small class="text-muted"><?= date('M d, Y', strtotime($row["created_at"])) ?></small>
                                                                </div>
                                                            </div>
                                                        </td>
                                                        <td class="px-4">
                                                            <?= htmlspecialchars($row["department_name"] ?? 'All Departments') ?>
                                                        </td>
                                                        <td class="px-4">
                                                            <div class="d-flex gap-2 justify-content-center">
                                                                <button class="btn btn-info btn-sm text-white shadow-sm view-btn" 
                                                                    data-id="<?= htmlspecialchars($row['id']) ?>" 
                                                                    title="View">
                                                                    <i class="fas fa-eye"></i>
                                                                </button>
                                                                <button class="btn btn-primary btn-sm shadow-sm edit-btn"
                                                                    data-id="<?= htmlspecialchars($row['id']) ?>"
                                                                    data-title="<?= htmlspecialchars($row['title']) ?>"
                                                                    data-message="<?= htmlspecialchars($row['message']) ?>"
                                                                    data-imagePath="<?= htmlspecialchars($row['image']) ?>"
                                                                    title="Edit">
                                                                    <i class="fas fa-edit"></i>
                                                                </button>
                                                                <button class="btn btn-danger btn-sm shadow-sm delete-btn" 
                                                                    data-id="<?= htmlspecialchars($row['id']) ?>" 
                                                                    title="Delete">
                                                                    <i class="fas fa-trash"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endwhile; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="3" class="text-center py-5">
                                                        <div class="text-muted">
                                                            <i class="fas fa-bullhorn fa-3x mb-3 d-block"></i>
                                                            <p class="mb-0">No Announcements Found</p>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>


            <!-- Add Announcement Modal -->
            <div class="modal fade" id="addAnnouncementModal" tabindex="-1" aria-labelledby="addAnnouncementModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="addAnnouncementModalLabel">Add Announcement</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form action="addannouncement.php" method="POST" enctype="multipart/form-data">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title:</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                                <div class="mb-3">
                                    <label for="message" class="form-label">Message:</label>
                                    <textarea class="form-control" id="message" name="message" rows="3" required></textarea>
                                </div>
                                <?php if ($role === 'School Admin'): ?>
                                <div class="mb-3">
                                    <label for="department" class="form-label">Department</label>
                                    <select class="form-control" id="department" name="department_id">
                                        <option value="">Select Department</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?= htmlspecialchars($dept['id']) ?>">
                                                <?= htmlspecialchars($dept['department_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php endif; ?>
                                <div class="mb-3">
                                    <label for="image" class="form-label">Image</label>
                                    <input type="file" class="form-control" id="image" name="image">
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Add Announcement</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>


            <!-- View Announcement Modal -->
            <div class="modal fade" id="announcementModal" tabindex="-1" aria-labelledby="announcementModalLabel" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="announcementModalLabel">Announcement Title</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center">
                            <p id="modalMessage" class="mb-3">Announcement message will appear here.</p>
                            <img id="modalImage" src="" alt="Announcement Image" class="img-fluid rounded" style="max-width: 100%; height: auto;">
                        </div>
                    </div>
                </div>
            </div>



            <!-- Edit Announcement Modal -->
            <div class="modal fade" id="editAnnouncementModal" tabindex="-1" aria-labelledby="editAnnouncementModalLabel" aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editAnnouncementModalLabel">Edit Announcement</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <form id="editAnnouncementForm" action="edit_announcement.php" method="POST" enctype="multipart/form-data" onsubmit="return confirmEdit(event)">
                                <input type="hidden" name="announcement_id" id="edit_announcement_id">
                                <div class="mb-3">
                                    <label for="edit_title" class="form-label">Title</label>
                                    <input type="text" class="form-control" id="edit_title" name="title" required>
                                </div>
                                <div class="mb-3">
                                    <label for="edit_message" class="form-label">Message</label>
                                    <textarea class="form-control" id="edit_message" name="message" rows="3" required></textarea>
                                </div>
                                <?php if ($role === 'School Admin'): ?>
                                <div class="mb-3">
                                    <label for="edit_department" class="form-label">Department</label>
                                    <select class="form-control" id="edit_department" name="department_id">
                                        <option value="">Select Department</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?= htmlspecialchars($dept['id']) ?>">
                                                <?= htmlspecialchars($dept['department_name']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <?php endif; ?>
                                <div class="mb-3">
                                    <label for="edit_image" class="form-label">Image</label>
                                    <div id="imagePreviewContainer" class="mb-2">
                                        <img id="imagePreview" src="" alt="Image preview" style="max-width: 200px; display: none;">
                                    </div>
                                    <input type="file" class="form-control" id="edit_image" name="image" accept="image/*">
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                    <button type="submit" class="btn btn-primary">Save Changes</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <script>
                function confirmEdit(event) {
                    event.preventDefault(); // Prevent form from submitting immediately
                    
                    Swal.fire({
                        title: 'Are you sure?',
                        text: "Do you want to save these changes?",
                        icon: 'question',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: 'Yes, save changes',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // If user confirms, submit the form
                            document.getElementById('editAnnouncementForm').submit();
                        }
                    });
                    
                    return false; // Prevent form from submitting normally
                }

                // Add this to your existing JavaScript
                function editAnnouncement(id) {
                    fetch('get_announcement.php?id=' + id)
                        .then(response => response.json())
                        .then(data => {
                            document.getElementById('edit_announcement_id').value = data.id;
                            document.getElementById('edit_title').value = data.title;
                            document.getElementById('edit_message').value = data.message;
                            
                            // Set department if it exists and user is School Admin
                            const departmentSelect = document.getElementById('edit_department');
                            if (departmentSelect && data.department_id) {
                                departmentSelect.value = data.department_id;
                            }

                            // Show current image preview if exists
                            const imagePreview = document.getElementById('imagePreview');
                            if (data.image) {
                                imagePreview.src = `../uploads/${data.image}`;
                                imagePreview.style.display = 'block';
                            } else {
                                imagePreview.style.display = 'none';
                            }

                            // Show the modal
                            new bootstrap.Modal(document.getElementById('editAnnouncementModal')).show();
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            alert('Error loading announcement details');
                        });
                }

                // Add preview for new image upload
                document.getElementById('edit_image').addEventListener('change', function(e) {
                    const imagePreview = document.getElementById('imagePreview');
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            imagePreview.src = e.target.result;
                            imagePreview.style.display = 'block';
                        }
                        reader.readAsDataURL(this.files[0]);
                    }
                });
            </script>

            <script>
                // Handle success and error messages
                <?php if (isset($successMessage) && !empty($successMessage)): ?>
                    Swal.fire({
                        icon: 'success',
                        title: 'Success!',
                        text: '<?php echo $successMessage; ?>',
                        showConfirmButton: false,
                        timer: 1500
                    });
                <?php endif; ?>

                <?php if (isset($errorMessage) && !empty($errorMessage)): ?>
                    Swal.fire({
                        icon: 'error',
                        title: 'Error!',
                        text: '<?php echo $errorMessage; ?>'
                    });
                <?php endif; ?>

                document.addEventListener('DOMContentLoaded', () => {
                    // Handle view button click
                    document.querySelectorAll('.view-btn').forEach(button => {
                        button.addEventListener('click', () => {
                            const announcementId = button.getAttribute('data-id');
                            fetch('fetch_announcement.php', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/x-www-form-urlencoded'
                                    },
                                    body: `id=${announcementId}`
                                })
                                .then(response => response.json())
                                .then(data => {
                                    // Update modal content
                                    document.getElementById('announcementModalLabel').textContent = data.title || 'Announcement';
                                    document.getElementById('modalMessage').textContent = data.message || 'No message available.';
                                    document.getElementById('modalImage').src = data.image_url || 'default-image.jpg';

                                    // Show the modal
                                    const viewModal = new bootstrap.Modal(document.getElementById('announcementModal'));
                                    viewModal.show();
                                })
                                .catch(error => console.error('Error fetching announcement:', error));
                        });
                    });

                    // Handle edit button click
                    document.querySelectorAll('.edit-btn').forEach(button => {
                        button.addEventListener('click', () => {
                            const id = button.getAttribute('data-id');
                            editAnnouncement(id);
                        });
                    });

                    // Handle delete button click
                    document.querySelectorAll('.delete-btn').forEach(button => {
                        button.addEventListener('click', () => {
                            const announcementId = button.getAttribute('data-id');
                            Swal.fire({
                                title: 'Are you sure?',
                                text: "You won't be able to revert this!",
                                icon: 'warning',
                                showCancelButton: true,
                                confirmButtonColor: '#3085d6',
                                cancelButtonColor: '#d33',
                                confirmButtonText: 'Yes, delete it!'
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    fetch('delete_announcement.php', {
                                            method: 'POST',
                                            headers: {
                                                'Content-Type': 'application/x-www-form-urlencoded'
                                            },
                                            body: `announcement_id=${announcementId}`
                                        })
                                        .then(response => response.text())
                                        .then(() => {
                                            Swal.fire('Deleted!', 'Your announcement has been deleted.', 'success')
                                                .then(() => location.reload());
                                        })
                                        .catch(() => {
                                            Swal.fire('Error!', 'There was a problem deleting the announcement.', 'error');
                                        });
                                }
                            });
                        });
                    });
                });
            </script>

</body>

</html>